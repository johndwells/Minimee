<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once PATH_THIRD . 'minimee/config.php';
require_once PATH_THIRD . 'minimee/models/Minimee_config.php';
require_once PATH_THIRD . 'minimee/models/Minimee_logger.php';

class Minimee_helper
{
	public static function cache()
	{
		// be sure we have a cache set up
		if ( ! isset(get_instance()->session->cache['minimee']))
		{
			get_instance()->session->cache['minimee'] = array();
		}

		// alias our cache for shorthand		
		return get_instance()->session->cache['minimee'];
	}

	public static function library($which)
	{
		// update our include_path only once
		if ( ! isset(get_instance()->session->cache['include_path']))
		{
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
}