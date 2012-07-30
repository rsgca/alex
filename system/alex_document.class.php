<?php
/**
 * Alex - Convert Burkes Peerage to GEDCOM
 *
 * Parses a Burkes Peerage format and converts it to GEDCOM
 *
 * @package Alex
 * @subpackage Alex_document
 * @author Richard Greenwood
 */
require ('system/alex_person.class.php');
require ('system/alex_relation.class.php');
require ('system/alex_spouse.class.php');
require ('system/alex_inlaw.class.php');

class Alex_document
{
	public $doc;
	public $total_records;
	public $next_record;
	public $gender_variations = array();
	
	public function __construct( $doc = 'burkes.txt' ) 
	{	
		$this->doc = $this->conform_doc( $this->setup_doc( $doc ) );
		$this->total_records = count( $this->doc );
		$this->next_record = $this->total_records+1;
		
		$this->gender_variations['M'][0] = 'husb'; 
		$this->gender_variations['F'][0] = 'wife';
	}

	/**
	 * Get People
	 * create a new Alex_person obj for each line in our conformed burkes doc
	 * @returns array of Alex_people objects
	 */
	public function get_people($debug = FALSE) 
	{		
		// get primary persons		
		foreach ($this->doc as $line_num => $line)
		{			
			if ( $this->is_orphaned_line($line) == FALSE )
			{
				$primary[$line_num] = new Alex_relation($this->doc, $line_num, $line);
				$review[$line_num]['l'] = $line;
				$review[$line_num]['r'] = new Alex_relation($this->doc, $line_num, $line);				
			}
			else
			{
				//append orphaned lines' ID and value to owning individual 
				$i = $line_num-1;
				while ($i > 1) 
				{
					if ( Alex_relation::get_indent_length($this->doc[$i]) == Alex_relation::get_indent_length($line) && $this->is_orphaned_line($this->doc[$i]) == FALSE)
					{
						$combined_lines = $primary[$i]->subject . ' ' . sprintf('@F%04d@',$line_num) . ' ' . $line;
						
						$primary[$i] = new Alex_relation($this->doc, $i, $combined_lines);
						$review[$i]['l'] = $combined_lines;
						$review[$i]['r'] = new Alex_relation($this->doc, $i, $combined_lines);
						break;
					}
					$i--;
				}
			}
		}
		
		// get secondary (inline) persons for first marriage and maintain family associations
		$i = $this->next_record;
		foreach ($primary as $key => $person)
		{
			if ( $person->is_married() && $person->spouse_name_exists() )
			{
				$person_meta['id'] = $i;
				
				// create spouse individual
				$secondary[$person_meta['id']] = new Alex_spouse($this->doc, $key, $person->get_spouse(1), $person_meta );
				$review[$key]['s']             = new Alex_spouse($this->doc, $key, $person->get_spouse(1), $person_meta );
				$i++;
				
				// create spouse father individual
				if ($secondary[$person_meta['id']]->get_father())
				{
					$father_meta['id'] = $i;
					$father_meta['fams'] = $i;
					
					$secondary[$father_meta['id']] = new Alex_inlaw($this->doc, $key, $secondary[$person_meta['id']]->get_father(), $father_meta);
					$review[$key]['f']             = new Alex_inlaw($this->doc, $key, $secondary[$person_meta['id']]->get_father(), $father_meta);
					
					$person_meta['father'] = $i;
					$i++;
				}
				
				// create spouse mother individual
				if ($secondary[$person_meta['id']]->get_mother())
				{				
					$mother_meta['id'] = $i;
					$mother_meta['fams'] = (isset($father_meta['fams'])) ? $father_meta['fams'] : $i;
					
					$secondary[$mother_meta['id']] = new Alex_inlaw($this->doc, $key, $secondary[$person_meta['id']]->get_mother(), $mother_meta);
					$review[$key]['m']             = new Alex_inlaw($this->doc, $key, $secondary[$person_meta['id']]->get_mother(), $mother_meta);
					
					$person_meta['mother'] = $i;
					$i++;
				}
				
				// replace spouse indiv with attached parent info
				if ( isset($person_meta['father']) || isset($person_meta['mother']) )
				{
					$secondary[$person_meta['id']] = new Alex_spouse($this->doc, $key, $person->get_spouse(), $person_meta );
					$review[$key]['s']             = new Alex_spouse($this->doc, $key, $person->get_spouse(), $person_meta );
				}
			}	
		}
		
		$array = ($debug) ? $review : $primary + $secondary;
		return $array;
	}
	
	
	/** 
	 * Get Family
	 * @return array
	 */
	public function get_families()
	{
		unset($fams);
		
		$indi = $this->get_people();
		foreach ($indi as $person)
		{
			//fams
			if ( $person->get_fams() ) 
			{
				foreach ($person->get_fams() as $key => $value)
				{
					$fams[$value]['id'] = $value;
					$fams[$value][$this->gender_variations[$person->get_sex()][0]] = $person->get_id();
				}
			}
			
			//famc
			if ( $person->get_famc() )
			{
				$fams[$person->get_famc()]['children'][] = $person->get_id();
			}
		}
		
		return $fams; 
	}
	/**
	 * Document setup
	 * @return array
	 */
	private function setup_doc($path)
	{
		//todo check to see if file exists
		$file = file($path, FILE_IGNORE_NEW_LINES);

		// unshift array to start at 1 not zero
		array_unshift($file, '');
		unset($file[0]);
		
		return $file;
	}
	
	/**
	 * Document conform
	 * @return array
	 */
	private function conform_doc($array)
	{			
		foreach ($array as $key => &$value)
		{
			// Cleanup white space
			$value = preg_replace('/ {2,}/', ' ', $value);
			$value = preg_replace('/,([^\s])/', ', \1', $value);
			$value = preg_replace('/ ,/', ',', $value);
			$value = preg_replace('/ \t|\t /', '\t', $value);  
         
			// Remove living bullet
			$value = preg_replace('/•/', '', $value);
			$value = preg_replace('/\*/', '', $value);
         
			// conform_doc date approximations
			$value = preg_replace('/\bca\./', 'ABT', $value);
			$value = preg_replace('/\bbefore/', 'BEF', $value);
			$value = preg_replace('/\bafter/', 'AFT', $value);
         
			// conform_doc months
			$value = preg_replace('/\bJan\.?\b/i', 'JAN', $value);
			$value = preg_replace('/\bFeb\.?\b/i', 'FEB', $value);
			$value = preg_replace('/\bMarch,?/', 'MAR', $value);
			$value = preg_replace('/\bApril,?/i', 'APR', $value);
			$value = preg_replace('/(?<=\d\s)\bMay,?\b/i', 'MAY', $value);
			$value = preg_replace('/(?<=\d\s)\bJune,?/i', 'JUN', $value);
			$value = preg_replace('/\bJuly,?\b/i', 'JUL', $value);
			$value = preg_replace('/\bAug\.?\b/i', 'AUG', $value);
			$value = preg_replace('/\bSept\.?\b/i', 'SEP', $value);
			$value = preg_replace('/\bOct\.?\b/i', 'OCT', $value);
			$value = preg_replace('/\bNov\.?\b/i', 'NOV', $value);
			$value = preg_replace('/\bDec\.?\b/i', 'DEC', $value);
			
			// Conform NPRX
			$value = preg_replace('/(Surg\.)‑(Cmdr\.)/i', '\1 \2', $value);
			
			//'d messes with the death date parser
			$value = preg_replace('/Commn’d/i', 'Commanded', $value);
			$value = preg_replace('/Comm’d/i', 'Commanded', $value);
			
			$value = preg_replace('/m\.diss/', 'm. diss', $value);
		}
		
		return $array;
	}

	/**
	 * Is orphaned line
	 *
	 * Check if line starts with "He" or "She"
	 * This means there's an additional marriage / family for one individual
	 * @return bool
	 */ 
	private function is_orphaned_line($line)
	{
		if ( preg_match('/^\t*S?He/', $line) )
		{
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}
}