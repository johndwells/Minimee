<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once PATH_THIRD . 'minimee/config.php';
require_once PATH_THIRD . 'minimee/models/Minimee_config.php';
require_once PATH_THIRD . 'minimee/models/Minimee_logger.php';

/**
 * Minimee Helper
 * @author John D Wells <http://johndwells.com>
 * @license http://www.opensource.org/licenses/bsd-license.php BSD license
 * @link	http://johndwells.com/software/minimee
 */
class Minimee_helper
{
	/**
	 * Create an alias to our cache
	 *
	 * @return 	Array	Our cache in EE->session->cache
	 */
	public static function &cache()
	{
		$ee =& get_instance();

		// be sure we have a cache set up
		if ( ! isset($ee->session->cache['minimee']))
		{
			$ee->session->cache['minimee'] = array();

			Minimee_logger::log('Session cache has been set up.', 3);
		}
		
		// alias our cache for shorthand		
		return $ee->session->cache['minimee'];
	}
	// ------------------------------------------------------


	/**
	 * Loads our requested library
	 *
	 * On first call it will adjust the include_path, for Minify support
	 *
	 * @param 	string	Name of library to require
	 * @return 	void
	 */
	public static function library($which)
	{
		// update our include_path only once
		if ( ! isset(get_instance()->session->cache['include_path']))
		{
			Minimee_logger::log('include path has been updated.', 3);

			set_include_path(PATH_THIRD . 'minimee/libraries' . PATH_SEPARATOR . get_include_path());
			
			get_instance()->session->cache['include_path'] = TRUE;
		}

		// require_once our library
		switch($which) {
			case('css') :
				require_once('Minify/CSS.php');
			break;
			
			case('curl') :
				require_once('EpiCurl.php');
			break;
			
			case('js') :
				require_once('JSMin.php');
			break;
			
			case('html') :
				require_once('Minify/HTML.php');
			break;
		}
	}
	// ------------------------------------------------------


	/**
	 * Modified remove_double_slashes()
	 *
	 * If the string passed is a URL, it will preserve leading double slashes
	 *
	 * @param 	string	String to remove double slashes from
	 * @param 	boolean	True if string is a URL
	 * @return 	string	String without double slashes
	 */
	public static function remove_double_slashes($string, $url = FALSE)
	{
		// is our string a URL?
		if($url)
		{
			// regex pattern removes all double slashes, preserving http:// and '//' at start
			return preg_replace("#([^:])//+#", "\\1/", $string);
		}
		
		// nope just a path
		else
		{
			// regex pattern removes all double slashes - straight from EE->functions->remove_double_slashes();
			return preg_replace("#(^|[^:])//+#", "\\1/", $string);
		}
	}
	// ------------------------------------------------------
}
// END CLASS

/* End of file Minimee_helper.php */
/* Location: ./system/expressionengine/third_party/minimee/models/Minimee_helper.php */