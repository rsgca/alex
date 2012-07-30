<?php
/**
 * Alex - Convert Burkes Peerage to GEDCOM
 *
 * Parses a Burkes Peerage format and converts it to GEDCOM
 *
 * @package Alex
 * @subpackage alex_spouse
 * @author Richard Greenwood
 *
 * example possible burkes inline value
 * Mary Alice, 4th dau. of George Franklin, of Haddenham, and Thame, by his wife Colubery, dau. of Marmaduke Beke, of Dinton (whose ... the Lord Protector), and grandau. of Richard and Colubery Beke, of Haddenham Manor, and gt. gt. granddau. of Sir Richard Lovelace, of Horley, Berks (see BURKE’s DEP),
 * Lynley, yr. dau. of Euan J. Henderson, of Cheviot, N.Z.
 */
class Alex_spouse extends Alex_person
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
			if ( preg_match('/^(.+?)(,|of| \(|$)/', $this->subject, $preg_name) )
			{
				$fullname = explode(' ', trim($this->capitalize_name(preg_replace('/(\(|\))/','',preg_replace($this->npfx, '', $preg_name[1])))));
				
				$name['first'] = $fullname[0];
				
				// if there is more than one name recorded
				if (count($fullname) > 1)
				{					
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
				}
				else
				{
					// if father's name is recorded
					if ( $this->get_father_surname() !== FALSE )
					{
						$name['last'] = $this->get_father_surname();
					}
					else
					{
						$name['last'] = 'unknown';
					}
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
		$fams[0] = sprintf($this->format_f, $this->line_num);
		return $fams;
	}	
	
	/**
	 * FAMC
	 * famc = father's individual id
	 */
	public function get_famc()
	{	
		if (isset($this->meta['father'])) 
		{
			return sprintf($this->format_f, $this->meta['father']);
		}
		else
		{ 
			return FALSE;
		}
	}
	
	/**
	 * get father
	 *
	 */
	public function get_father()
	{
		if ( preg_match ('/^(?:.*?)(?:dau\.|son|descendant) of (.+?)(?:, by his .{0,9}wife|, and granddau\. of|$)/', $this->subject, $preg_inlaws) )
		{
			return $preg_inlaws[1];
		}
		else
		{
			return FALSE;
		}		
	}
	
	/**
	 * get father surname
	 * @used-by get name
	 */
	public function get_father_surname()
	{
		if ( preg_match ('/(?:dau\.|son|descendant) of (.+?)(,| of| \(|$)/', $this->subject, $preg_father_name) )
		{
			$fullname = explode(' ', trim($this->capitalize_name($preg_father_name[1])));
			return end($fullname);
		}
		else
		{
			return FALSE;
		}
	}
	
	/**
	 * Mother
	 *
	 */
	public function get_mother()
	{
		if ( preg_match ('/(?:dau\.|son|descendant).*?by his .{0,9}wife (.*)/', $this->subject, $preg_inlaws) )
		{
			return $preg_inlaws[1];
		}
		else
		{
			return FALSE;
		}
	}
	

	/**
	 * 2nd Spouse Marriages
	 * if the SPOUSE re-marries after the primary person dies
	 * not implemented
	 */
	public function get_spouse($number)
	{
		//$number must equal 2
	}
	
}