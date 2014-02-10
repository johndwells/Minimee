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
	 * History of logging for EE Debug Toolbar
	 */
	private static $_log = array();


	/**
	 * Flag for whether to 'flash' our toolbar tab
	 */
	private static $_log_has_error = FALSE;


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
	public static function config()
	{
		if (self::$_config === FALSE)
		{
			self::$_config = new Minimee_config();
		}
		
		return self::$_config;
	}
	// ------------------------------------------------------


	/**
	 * Fetch our static log
	 *
	 * @return 	Array	Array of logs
	 */
	public static function get_log()
	{
		return self::$_log;
	}
	// ------------------------------------------------------


	/**
	 * Fetch our static log
	 *
	 * @return 	Array	Array of logs
	 */
	public static function log_has_error()
	{
		return self::$_log_has_error;
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
		// a few housekeeping items before we start loading our libraries
		if ( ! isset(get_instance()->session->cache['loader']))
		{
			// try to bump our memory limits for good measure
			@ini_set('memory_limit', '12M');
			@ini_set('memory_limit', '16M');
			@ini_set('memory_limit', '32M');
			@ini_set('memory_limit', '64M');
			@ini_set('memory_limit', '128M');
			@ini_set('memory_limit', '256M');

			// Latest changes to Minify adopt a "loader" over sprinkled require's
			require_once(PATH_THIRD . 'minimee/libraries/Minify/Loader.php');
			Minify_Loader::register();

			// don't do this again
			get_instance()->session->cache['loader'] = TRUE;
		}

		// require_once our library of choice
		switch ($which) :

			case ('minify') :
				if ( ! class_exists('Minify_CSS'))
				{
					require_once(PATH_THIRD . 'minimee/libraries/Minify/CSS.php');
				}
			break;

			case ('cssmin') :
				if ( ! class_exists('CSSmin'))
				{
					// this sucks, but it's a case-insensitivity issue that we need to protect ourselves against
					if (glob(PATH_THIRD . 'minimee/libraries/CSSmin.php'))
					{
						require_once(PATH_THIRD . 'minimee/libraries/CSSmin.php');
					}
				
					else
					{
						self::log('CSSMin.php in minimee/libraries needs to be renamed to the proper capitalisation of "CSSmin.php".', 2);
						require_once(PATH_THIRD . 'minimee/libraries/CSSMin.php');
					}
				}
			break;
			
			case ('css_urirewriter') :
				if ( ! class_exists('Minify_CSS_UriRewriter'))
				{
					require_once(PATH_THIRD . 'minimee/libraries/Minify/CSS/UriRewriter.php');
				}
			break;

			case ('curl') :
				if ( ! class_exists('EpiCurl'))
				{
					require_once(PATH_THIRD . 'minimee/libraries/EpiCurl.php');
				}
			break;
			
			case ('jsmin') :
			
				if ( ! class_exists('JSMin'))
				{
					// this sucks, but it's a case-insensitivity issue that we need to protect ourselves against
					if (glob(PATH_THIRD . 'minimee/libraries/JSM*n.php'))
					{
						require_once(PATH_THIRD . 'minimee/libraries/JSMin.php');
					}
				
					else
					{
						self::log('jsmin.php in minimee/libraries needs to be renamed to the proper capitalisation of "JSMin.php".', 2);
						require_once(PATH_THIRD . 'minimee/libraries/jsmin.php');
					}
				}
			break;
			
			case ('jsminplus') :
				if ( ! class_exists('JSMinPlus'))
				{
					require_once(PATH_THIRD . 'minimee/libraries/JSMinPlus.php');
				}
			break;
			
			case ('html') :
				if ( ! class_exists('Minify_HTML'))
				{
					require_once(PATH_THIRD . 'minimee/libraries/Minify/HTML.php');
				}
			break;

		endswitch;
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

		// save our log for EE Debug Toolbar
		self::$_log[] = array($severity, $message);
		if($severity == 'ERROR')
		{
			self::$_log_has_error = TRUE;
		}

		// basic EE logging
		log_message($severity, MINIMEE_NAME . ": {$message}");

		// Can we also log our message to the template debugger?
		if (REQ == 'PAGE')
		{
			get_instance()->TMPL->log_item(MINIMEE_NAME . " [{$severity}]: {$message}");
		}
	}
	// ------------------------------------------------------


	/**
	 * Returns an array of all public properties of our Minimee plugin.
	 * Used to easily reset() to defaults.
	 *
	 * @return 	array	Array of public properties of Minimee class
	 */
	public static function minimee_class_vars()
	{
		$m = new Minimee;
		return get_class_vars(get_class($m));
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

			default :
				return FALSE;
			break;

		endswitch;

		if ( ! preg_match_all($pat, $haystack, $matches, PREG_PATTERN_ORDER))
		{
			return FALSE;
		}
		
		// free memory where possible
		unset($pat);

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
	public static function replace_url_with($url, $with, $haystack)
	{
		// protocol-agnostic URL
		$agnostic_url = substr($url, strpos($url, '//') + 2, strlen($url));

		// pattern search & replace
		return preg_replace('@(https?:)?\/\/' . $agnostic_url . '@', $with, $haystack);
	}
}
// END CLASS

/* End of file Minimee_helper.php */
/* Location: ./system/expressionengine/third_party/minimee/classes/Minimee_helper.php */