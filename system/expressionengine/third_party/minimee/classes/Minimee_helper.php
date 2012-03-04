<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once PATH_THIRD . 'minimee/config.php';
require_once PATH_THIRD . 'minimee/classes/Minimee_config.php';

/**
 * Minimee Helper
 * @author John D Wells <http://johndwells.com>
 * @license http://www.opensource.org/licenses/bsd-license.php BSD license
 * @link	http://johndwells.com/software/minimee
 */
class Minimee_helper {

	/**
	 * Logging levels
	 */
	private static $_levels = array(
		1 => 'ERROR',
		2 => 'DEBUG',
		3 => 'INFO'
	);
	
	/**
	 * Our 'Singleton' config
	 */
	private static $_config = FALSE;


	// ----------------------------------------------


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

			self::log('Session cache has been created.', 3);
		}
		
		return $ee->session->cache['minimee'];
	}
	// ------------------------------------------------------


	/**
	 * Fetch/create singleton instance of config
	 *
	 * @return 	Array	Instance Minimee_config
	 */
	public static function config($extend = array())
	{
		if (self::$_config === FALSE)
		{
			self::$_config = new Minimee_config();
		}
		
		// by 'extend' we mean merge runtime with defaults
		if ($extend)
		{
			// clear out any previous runtime settings & extend
			self::$_config->extend($extend);
		}
		
		return self::$_config;
	}
	// ------------------------------------------------------


	/**
	 * Determine if string is valid URL
	 *
	 * @param 	string	String to test
	 * @return 	bool	TRUE if yes, FALSE if no
	 */
	public static function is_url($string)
	{
		// from old _isURL() file from Carabiner Asset Management Library
		// modified to support leading with double slashes
		return (preg_match('@((https?:)?//([-\w\.]+)+(:\d+)?(/([\w/_\.]*(\?\S+)?)?)?)@', $string) > 0);
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
			self::log('PHP\'s include_path has been updated.', 3);

			set_include_path(PATH_THIRD . 'minimee/libraries' . PATH_SEPARATOR . get_include_path());
			
			get_instance()->session->cache['include_path'] = TRUE;
		}

		// require_once our library
		switch($which) {
			case('css') :
				require_once('Minify/CSS.php');
			break;
			
			case('css_urirewriter') :
				require_once('Minify/CSS/UriRewriter.php');
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
	 * Log method
	 *
	 * By default will pass message to log_message();
	 * Also will log to template if rendering a PAGE.
	 *
	 * @access  public
	 * @param   string      $message        The log entry message.
	 * @param   int         $severity       The log entry 'level'.
	 * @return  void
	 */
	public static function log($message, $severity = 1)
	{
		// translate our severity number into text
		$severity = (array_key_exists($severity, self::$_levels)) ? self::$_levels[$severity] : self::$_levels[1];

		// basic EE logging
		log_message($severity, $message);

		// If not in CP, let's also log to template
		if (REQ == 'PAGE')
		{
			get_instance()->TMPL->log_item(MINIMEE_NAME . " [{$severity}]: {$message}");
		}

		// If we are in CP and encounter an error, throw a nasty show_message()
		if (REQ == 'CP' && $severity == self::$_levels[1])
		{
			show_error(MINIMEE_NAME . " [{$severity}]: {$message}");
		}
	}
	// ------------------------------------------------------


	/**
	 * Helper function to parse content looking for CSS and JS tags.
	 * Returns array of links found.

	 * @param 	string	String to search
	 * @param 	string	Which type of tags to search for - CSS or JS
	 * @return 	array	Array of found matches
	 */
	public static function preg_match_by_type($haystack, $type)
	{
		// let's find the location of our cache files
		switch (strtolower($type)) :

			case 'css' :
				$pat = "/<link{1}.*?href=['|\"']{1}(.*?)['|\"]{1}[^>]*>/i";
			break;

			case 'js' :
				$pat = "/<script{1}.*?src=['|\"]{1}(.*?)['|\"]{1}[^>]*>(.*?)<\/script>/i";
			break;

		endswitch;

		if ( ! preg_match_all($pat, $haystack, $matches, PREG_PATTERN_ORDER))
		{
			return FALSE;
		}
		
		// free memory where possible
		unset($pat, $haystack);

		return $matches;
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
		if ($url)
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
	
	
	/**
	 * A protocol-agnostic function to replace URL with path
	 *
	 * @param 	string	base url
	 * @param 	boolean	base path
	 * @return 	string	String to perform replacement upon
	 */
	public static function replace_url_with_path($url, $path, $haystack)
	{
		// protocol-agnostic URL
		$agnostic_url = substr($url, strpos($url, '//') + 2, strlen($url));

		// pattern search & replace
		return $path . preg_replace('@(https?:)?\/\/' . $agnostic_url . '@', '', $haystack);
	}
}
// END CLASS

/* End of file Minimee_helper.php */
/* Location: ./system/expressionengine/third_party/minimee/models/Minimee_helper.php */