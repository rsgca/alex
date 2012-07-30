<?php
/**
 * Alex - Convert Burkes Peerage to GEDCOM
 *
 * Parses a Burkes Peerage format and converts it to GEDCOM
 *
 * @package Alex
 * @subpackage Alex_inlaw
 * @author Richard Greenwood
 *
 */
class Alex_inlaw extends Alex_person
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
			if ( preg_match('/(.*?)(,| of| \(|$)/', $this->subject, $preg_name) )
			{
				$fullname = explode(' ', $this->capitalize_name(preg_replace('/(\(|\))/','',preg_replace($this->npfx, '', $preg_name[1]))));
				
				$name['first'] = $fullname[0];
				
				// if father's name is recorded
				if ( $this->get_father_surname() !== FALSE )
				{
					// if the last name has been recorded in both locations (after spouse's given AND after the father's given)
					if ( end($fullname) == $this->get_father_surname() )
					{						
						$name['last'] = end($fullname);
						array_pop($fullname);
					}
					else
					{
						$name['last'] = $this->get_father_surname();
					}
				}
				else 
				{
					$name['last'] = end($fullname);
					array_pop($fullname);
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
	 * @return array
	 */
	public function get_fams()
	{	
		$fams[0] = sprintf($this->format_f, $this->meta['fams']);
		return $fams;
	}		
	
	/**
	 * FAMC
	 * we don't implement this 
	 */
	public function get_famc()
	{
		return FALSE;
	}
	
	/**
	 * get father surname
	 * @used-by get_name()
	 */
	public function get_father_surname()
	{	
		if ( preg_match ('/(?:dau\.|son|descendant) (?:and co-heiress )?of (.+?)(?:,|of| \()/', $this->subject, $preg_father_name) )
		{
			$fullname = explode(' ', $this->capitalize_name($preg_father_name[1]));
			
			if ( $this->is_roman(end($fullname)) )
			{
				return FALSE;
			}
			else
			{
				return end($fullname);
			}
		}
		else
		{
			return FALSE;
		}
	}
	
	
}