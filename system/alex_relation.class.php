<?php
/**
 * Alex - Convert Burkes Peerage to GEDCOM
 *
 * Parses a Burkes Peerage format and converts it to GEDCOM
 *
 * @package Alex
 * @subpackage alex_primary_person
 * @author Richard Greenwood
 */
class Alex_relation extends Alex_person
{
	/**
	 * Get Name
	 * match names at start of line
	 * ends match at , of ([digit] ([9 characters or more]
	 * @parameter string
	 * @return array|bool 'first', 'last' and 'given'
	 */
	public function get_name($part = 'given')
	{
		if ( in_array($part, array('first','last','given')) )
		{
			if ( preg_match('/^\t*(\(?\d(\)|\w\b)?\.?)? ?(.+?)(,|of| \(|\.$|$)/', $this->subject, $preg_name) )
			{
				$fullname = explode(' ', trim($this->capitalize_name(preg_replace('/(\(|\))/','',$preg_name[3]))));
				
				$name['first'] = $fullname[0];
				
				if ( in_array(end($fullname), $this->surnames) )
				{		
					$name['last'] = end($fullname);
					array_pop($fullname);
				}
				else 
				{
					$name['last'] = $this->surnames['default'];
				}
				
				$name['given'] = implode(' ', $fullname);
				
				return $name[$part];
			}
			else
			{	
				return FALSE;
			}
		}
		else
		{
			return 'ERROR: invalid get_name arg';
		}
	}

	/**
	 * FAMS
	 * If indi has a child or a spouse, use the line number as fams id
	 * grab any additional family ids
	 * @return array|bool
	*/
	public function get_fams()
	{	
		if ( $this->has_child() || $this->is_married() ) 
		{
			// grab line number for fams
			$fams[0] = $this->line_num;
			// grab additional fams from non-1st marriages
			preg_match_all('/@F(\d{4})@/', $this->subject, $preg_fams);
			// merge the two
			$fams = array_merge($fams, $preg_fams[1]);
			//format combined array
			array_walk($fams, array($this, 'format_id'), 'f');
			
			return $fams;
		}
		else
		{
			return FALSE;
		}
	}	
	
	/**
	 * FORMAT_ID
	 * used in get_fams() callback
	 * formats an array of ids to match the family or individual id format
	 */
	private function format_id(&$value, $key, $format)
	{
		$format = ($format == 'f')? $this->format_f : $this->format_i;
		$value = sprintf($format, $value);
	}
	
	/**
	 * FAMC
	 * Get id from first previous line with smaller indent
	 * @return string 
	 */
	public function get_famc()
	{	
		$i = $this->line_num-1;
		while ($i > 1)
		{	    					
			if ( self::get_indent_length($this->doc[$i]) < self::get_indent_length($this->subject)  )
			{
				return sprintf($this->format_f, $i);
				break;
			}
			$i--;
		}
	}

	/**
	 * Get Spouse
	 * 	 
	 * example: ...m. 1stly, 19 SEP. 1987 (m.diss. by div. ABT 1990), Lynley, yr. dau. of Euan J. Henderson, of Cheviot, N.Z. He m. 2ndly,...
	 * We only get the first marriage
	 * @parameter int
	 * @return string
	 */
	public function get_spouse($number = NULL)
	{
		if ( $this->is_married() ) // are they married?
		{
			if ($number == 1 || $number == NULL) // only return spouses of the first marriage
			{
				if ( $this->spouse_name_exists() ) // for lines with a marriage date but no name
				{
					if ( preg_match('/\bm\. (?:1stly, )?(?:[^(]*?\d{4}(?:\/\d{1,2})?)?(.*?)(?: @F| (H|Sh)e m. 2ndly| and has|, and had|, and was|, living|,? and d\.|, and drowned|$)/', $this->subject, $preg_marriage_first) )
					{
						$spouse = preg_replace ('/his .{0,9}cousin, /','',$preg_marriage_first[1]);
						$spouse = preg_replace ('/\(m\. diss.*?\),? /','',$spouse);
						$spouse = preg_replace ('/^, /','',$spouse);
						return $spouse;
					}
					else
					{
						return 'ERROR: preg_match() failed.'; 
					}
				}
				else
				{
					return FALSE;
				}
			}
			else
			{
				return "ERROR: get_spouse() arg ($number) not supported.";
			}
		}
		else
		{
			return FALSE;
		}
	}
	
	/**
	 * Get marriage date
	 * not currently used
	 * @parameter int
	 * @return string
	 */
	public function get_marriage_date($number = NULL)
	{
		if ( $this->is_married() )
		{
			if ($number === 1 || $number == NULL)
			{
				preg_match('/\bm\.\s(1stly, )?([^(]*\d{4}(\/\d{1,2})?)( \(.*?\)),?( his cousin )?(.*?)( @F| 2ndly| and has|, and had|, and was|, living|, and d\.|, and drowned)/', $this->subject, $preg_marriage_first);
				//$spouse_record = $preg_marriage_first[6];
				$marr_date = $preg_marriage_first[2];
				
				$date = ( empty($marr_date) == FALSE ) ? preg_replace('/(,|\.)/', '', $marr_date) : FALSE;
				return $date;
			}
			else
			{
				return "ERROR: get_marriage_date() arg ($number) not supported.";
			}
		}
		else
		{
			return FALSE;
		}
		
	}

	/**
	 * Is married?
	 * Check if individual is married or not
	 * @parameter string|NULL
	 * @return bool
	 */
	public function is_married($line = NULL) 
	{
		$line = (isset($line)) ? $line : $this->subject;
		
		if ( preg_match('/\b(m\.)\s/', $line) )
		{
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}
	
	/**
	 * Is spouse named?
	 * Check if spouse name is recorded
	 * @parameter string|NULL
	 * @return bool
	 */
	public function spouse_name_exists($line = NULL) 
	{
		$line = (isset($line)) ? $line : $this->subject;
		
		if ( preg_match('/\bm\. (1stly, )?([^(]*?\d{4}(\/\d{1,2})?)?, and /', $line) )
		{
			return FALSE;
		}
		else
		{
			return TRUE;
		}
	}
	
	/**
	 * Has child?
	 *	Check if individual is married or not
	 * @return bool
	 */
	public function has_child($key = NULL)
	{
		$key = (isset($key)) ? $key : $this->line_num;
		$next_key = ($key == count($this->doc)) ? $key : $key+1;
		//$next_key = $key++;
		
		if ( self::get_indent_length($this->doc[$next_key]) > self::get_indent_length($this->doc[$key]) )
		{
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}
	
	/**
	 * Get indent
	 * get line indent length
	 * @return int
	 * @todo possibly move this function to parent class
	 */
	public static function get_indent_length($line) 
	{		
		preg_match('/^[\t]*/', $line, $preg_indent);
		return strlen($preg_indent[0]);
	}

}