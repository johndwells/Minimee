<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Minimee Logger
 * @author John D Wells <http://johndwells.com>
 * @license http://www.opensource.org/licenses/bsd-license.php BSD license
 * @link	http://johndwells.com/software/minimee
 */
class Minimee_logger
{
	/**
	 * Logging levels
	 */
	private static $_levels = array(
		1 => 'ERROR',
		2 => 'DEBUG',
		3 => 'INFO'
	);


	// ----------------------------------------------


	/**
	 * Convenience method: error()
	 *
	 * Logs a level 1 message
	 */
	public function error($message)
	{
		Minimee_logger::log($message, 1);
	}
	// ------------------------------------------------------


	/**
	 * Convenience method: debug()
	 *
	 * Logs a level 2 message
	 */
	public function debug($message)
	{
		Minimee_logger::log($message, 2);
	}
	// ------------------------------------------------------


	/**
	 * Convenience method: info()
	 *
	 * Logs a level 1 message
	 */
	public function info($message)
	{
		Minimee_logger::log($message, 3);
	}
	// ------------------------------------------------------


	/**
	 * Log method
	 *
	 * By default will pass message to log_message();
	 * Also will log to template if rendering a PAGE.
	 *
	 * Defined as static so that Minimee_config and classes can still call it
	 *
	 * @access  public
	 * @param   string      $message        The log entry message.
	 * @param   int         $severity       The log entry 'level'.
	 * @return  void
	 */
	public static function log($message, $severity = 1)
	{
		// translate our severity number into text
		$severity = (array_key_exists($severity, Minimee_logger::$_levels)) ? Minimee_logger::$_levels[$severity] : Minimee_logger::$_levels[1];

		// basic logging
		log_message($severity, $message);
		
		// If not in CP, let's also log to template
		if (REQ == 'PAGE')
		{
			get_instance()->TMPL->log_item(MINIMEE_NAME . " [{$severity}]: {$message}");
		}
		
		// If we are in CP and encounter an error, throw a nasty show_message()
		if (REQ == 'CP' && $severity == Minimee_logger::$_levels[1])
		{
			show_error(MINIMEE_NAME . " [{$severity}]: {$message}");
		}

	}
	// ------------------------------------------------------
	
}
// END CLASS

/* End of file Minimee_logger.php */
/* Location: ./system/expressionengine/third_party/minimee/models/Minimee_logger.php */