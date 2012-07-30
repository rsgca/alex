<?php
/**
 * Alex - Convert Burkes Peerage to GEDCOM
 *
 * Parses a Burkes Peerage format and converts it to GEDCOM
 *
 * @package Alex
 * @subpackage alex_person
 * @author Richard Greenwood
 */
abstract class Alex_person
{
	public $doc;
	public $line_num;
	public $subject;
	public $id;
	protected $meta;
	protected $surnames = array();
	protected $format_i = '@I%04d@';
	protected $format_f = '@F%04d@';
	protected $format_n = '%04d';
	protected $gender_variations = array();
	protected $npfx_list = 'Ald.|Brig.|Capt.|Col.|Dr.|G\/Capt.|Hon.|Lt. Cmdr.|Lt. Col.|Prof.|Rev.|Sir|Surg. Cmdr.';
	protected $npfx;	
	protected $open_bracket_neg;
	protected $open_bracket_aff;
	protected $female_names = 'system/includes/names-female.txt';

	public function __construct( array $doc, $line_num, $subject, $meta = NULL ) {
		$this->doc = $doc;
		$this->line_num = $line_num;	
		$this->id = (isset($meta['id'])) ? $meta['id'] : $line_num;
		$this->meta = $meta; // used by spouse person obj to get fams info
		$this->subject = $subject;
		
		$this->surnames['default'] = 'Greenwood';
		$this->surnames[] = 'Greenwoode';
		$this->surnames[] = 'Greenewoode';
		$this->surnames[] = 'Ward';
		
		// separate items with a pipe |
		$this->npfx = preg_replace('/\./','\\.', $this->npfx_list );
		$this->npfx = '(' . $this->npfx_list . ')';
		
		$this->open_bracket_neg = ( get_class($this) == 'Alex_spouse' ) ? '' : '(?<!\()';
		$this->open_bracket_aff = ( get_class($this) == 'Alex_spouse' ) ? '' : '(?=\()';
		
	}
	
	// Force Extending class to define this method
	abstract protected function get_name();
	abstract protected function get_fams();
	abstract protected function get_famc();	
	
	/**
	 * Get formatted ID
	 * @return string
	 */
	public function get_id()
	{
		return sprintf($this->format_i, $this->id);
	}
	
	/**
	 * Get Name Prefx
	 * matches name prefixes inside a bracket
	 * @return string|bool
	 */
	public function get_npfx() 
	{	
		$pattern = ( get_class($this) == 'Alex_relation' ) ? '(?:.*?)\(' : '';
		
		if ( preg_match('/^'.$pattern.$this->npfx.'/',$this->subject, $preg_npfx) )
		{
			return $preg_npfx[1];
		}
		else
		{
			return FALSE;
		}
	}
	
	/** 
	 * NOTE
	 *	get original burkes entree in note field for ref
	 * @return string
	 */
	public function get_note()
	{
		return 'Source [I'.sprintf($this->format_n,$this->line_num) . '] ' . trim($this->subject);
	}
	
	/**
	 * Sex
	 * match first name against list of known female names
	 * @return string
	 */
	public function get_sex($firstname = NULL)
	{	
		$list = file($this->female_names, FILE_IGNORE_NEW_LINES);		
		$firstname = (isset($firstname)) ? $firstname : $this->get_name('first');
		$gender = (in_array(strtolower($firstname), $list))? 'F' : 'M';
		return $gender;
	}
	
	/**
	 * Birthday
	 * 
	 * match
	 * b. 23 June, 1730
	 * b. ca. Aug. 1728
	 * b. 1818/19
	 * b. 1703/4
	 * don't match inside bracket for the alex_person obj except 
	 * (b. 22 Sept. 1920)
	 * @return bool
	 */
	public function get_birt()
	{
		if ( preg_match('/\b'.$this->open_bracket_neg.'b\.(.*?\d{4})(\/\d{1,2})?/', $this->subject, $preg_birt) )
		{
			return trim(preg_replace('/(,|\.)/', '', $preg_birt[1]));
		}
		else
		{
			return FALSE;
		}
	}
	
	/**
	 * Baptism
	 * similar to birthday
	 * Don't match bapt inside brackets
	 * @return string|bool 
	 */
	public function get_bapm()
	{
		if ( preg_match('/\b'.$this->open_bracket_neg.'bapt\.(.*?\d{4})(\/\d{1,2})?/', $this->subject, $preg_bapt) )
		{
			return trim(preg_replace('/(,|\.)/', '', $preg_bapt[1]));
		}
		else
		{
			return FALSE;
		}
	}
	
	/**
	 * Death
	 * similar to birthday
	 * Don't match death inside brackets
	 * @return string|bool 
	 */
	public function get_deat()
	{
		if ( preg_match('/\b'.$this->open_bracket_neg.'d\.(?:s\.p\.s\.|s\.p\.m\.|s\.p\.| unm| | m| young| in)?([^()]*?\d{4})(\/\d{1,2})?/', $this->subject, $preg_deat) )
		{
			return trim(preg_replace('/(,|\.)/', '', $preg_deat[1]));
		}
		else
		{
			return FALSE;
		}
	}
	
	/**
	 * Burial
	 * similar to birthday
	 *	Don't match bur inside brackets
	 * @return string|bool
	 */
	public function get_buri()
	{
		if ( preg_match('/\b'.$this->open_bracket_neg.'bur\.(.{1,12}\d{4})(\/\d{1,2})?/', $this->subject, $preg_buri) )
		{
			return trim(preg_replace('/(,|\.)/', '', $preg_buri[1]));
		} 
		else
		{
			return FALSE;	
		}
	}	

	/** 
	 * Capitalize Names
	 * @used-by get_name()
	 * @parameter string
	 * @return string
	 */
	protected function capitalize_name($name) 
	{
	    $name = strtolower($name);
	    $name = join("'", array_map('ucwords', explode("'", $name)));
	    $name = join("-", array_map('ucwords', explode("-", $name)));
	    $name = join("(", array_map('ucwords', explode("(", $name)));
	    if (preg_match('/mac.{2}/i',$name)) // match against the name "Mace"
	    {
			$name = join("Mac", array_map('ucwords', explode("Mac", $name))); 
	    }
	    $name = join("Mc", array_map('ucwords', explode("Mc", $name)));
	    return $name;
	}
	
	/** 
	 * Is Roman Numeral
	 * for our one instance of a father being EDWARD III
	 * @used-by get_father_surname()
	 * @parameter string
	 * @return string
	 */
	protected function is_roman($roman)
	{
		if (preg_match('/^(?:XL|L|L?(?:IX|X{1,3}|X{0,3}(?:IX|IV|V|V?I{1,3})))$/i',$roman))
		{
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}
	
}