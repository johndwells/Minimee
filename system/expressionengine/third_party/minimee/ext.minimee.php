<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

// our helper will require_once() everything else we need
require_once PATH_THIRD . 'minimee/classes/Minimee_helper.php';

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2003 - 2011, EllisLab, Inc.
 * @license		http://expressionengine.com/user_guide/license.html
 * @link		http://expressionengine.com
 * @since		Version 2.0
 * @filesource
 */
 
// ------------------------------------------------------------------------

/**
 * Minimee: minimize & combine your CSS and JS files. Minify your HTML. For EE2 only.
 * @author John D Wells <http://johndwells.com>
 * @license http://www.opensource.org/licenses/bsd-license.php BSD license
 * @link	http://johndwells.com/software/minimee
 */
class Minimee_ext {

	/**
	 * EE, obviously
	 */
	private $EE;


	/**
	 * Standard Extension stuff
	 */
	public $name			= MINIMEE_NAME;
	public $version			= MINIMEE_VER;
	public $description		= MINIMEE_DESC;
	public $docs_url		= MINIMEE_DOCS;
	public $settings 		= array();
	public $settings_exist	= 'y';


	/**
	 * Our magical config class
	 */
	public $config;


	/**
	 * Reference to our cache
	 */
	public $cache;


	// ------------------------------------------------------


	/**
	 * Constructor
	 *
	 * NOTE: We never use the $settings variable passed to us,
	 * because we want our Minimee_config object to always be in charge.
	 *
	 * @param 	mixed	Settings array - only passed when activating a hook
	 * @return void
	 */
	public function __construct($settings = array())
	{
		// Got EE?
		$this->EE =& get_instance();

		// grab a reference to our cache
		$this->cache =& Minimee_helper::cache();

		// grab instance of our config object
		$this->config = Minimee_helper::config();
	}
	// ------------------------------------------------------


	/**
	 * Activate Extension
	 * 
	 * @return void
	 */
	public function activate_extension()
	{
		// reset our runtime to 'factory' defaults, and return as array
		$settings = $this->config->factory()->to_array();
	
		// template_post_parse hook
		$this->EE->db->insert('extensions', array(
			'class'		=> __CLASS__,
			'hook'		=> 'template_post_parse',
			'method'	=> 'template_post_parse',
			'settings'	=> serialize($settings),
			'priority'	=> 10,
			'version'	=> $this->version,
			'enabled'	=> 'y'
		));

		// EE Debug Toolbar hook
		$this->EE->db->insert('extensions', array(
			'class'		=> __CLASS__,
			'hook'		=> 'ee_debug_toolbar_add_panel',
			'method'	=> 'ee_debug_toolbar_add_panel',
			'settings'	=> serialize($settings),
			'priority'	=> 10,
			'version'	=> $this->version,
			'enabled'	=> 'y'
		));

		Minimee_helper::log('Extension has been activated.', 3);
	}
	// ------------------------------------------------------


	/**
	 * Disable Extension
	 *
	 * @return void
	 */
	public function disable_extension()
	{
		$this->EE->db->where('class', __CLASS__);
		$this->EE->db->delete('extensions');

		Minimee_helper::log('Extension has been disabled.', 3);
	}
	// ------------------------------------------------------


	/**
	 * Method for template_post_parse hook
	 *
	 * @param 	array	Array of debug panels
	 * @param 	arrat	A collection of toolbar settings and values
	 * @return 	array	The amended array of debug panels
	 */
	public function ee_debug_toolbar_add_panel($panels, $view)
	{
		// do nothing if not a page
		if(REQ != 'PAGE') return $panels;

		// play nice with others
		$panels = ($this->EE->extensions->last_call != '' ? $this->EE->extensions->last_call : $panels);
	
		$panels['minimee'] = new Eedt_panel_model();
		$panels['minimee']->set_name('minimee');
		$panels['minimee']->set_button_label("Minimee");
		$panels['minimee']->set_button_icon("data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAyhpVFh0WE1MOmNvbS5hZG9iZS54bXAAAAAAADw/eHBhY2tldCBiZWdpbj0i77u/IiBpZD0iVzVNME1wQ2VoaUh6cmVTek5UY3prYzlkIj8+IDx4OnhtcG1ldGEgeG1sbnM6eD0iYWRvYmU6bnM6bWV0YS8iIHg6eG1wdGs9IkFkb2JlIFhNUCBDb3JlIDUuNS1jMDIxIDc5LjE1NTc3MiwgMjAxNC8wMS8xMy0xOTo0NDowMCAgICAgICAgIj4gPHJkZjpSREYgeG1sbnM6cmRmPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5LzAyLzIyLXJkZi1zeW50YXgtbnMjIj4gPHJkZjpEZXNjcmlwdGlvbiByZGY6YWJvdXQ9IiIgeG1sbnM6eG1wPSJodHRwOi8vbnMuYWRvYmUuY29tL3hhcC8xLjAvIiB4bWxuczp4bXBNTT0iaHR0cDovL25zLmFkb2JlLmNvbS94YXAvMS4wL21tLyIgeG1sbnM6c3RSZWY9Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC9zVHlwZS9SZXNvdXJjZVJlZiMiIHhtcDpDcmVhdG9yVG9vbD0iQWRvYmUgUGhvdG9zaG9wIENDIDIwMTQgKE1hY2ludG9zaCkiIHhtcE1NOkluc3RhbmNlSUQ9InhtcC5paWQ6NjQxMzVDNTBGRDdCMTFFMzhDQzk5MzI3QzQ4QkE1NDUiIHhtcE1NOkRvY3VtZW50SUQ9InhtcC5kaWQ6NjQxMzVDNTFGRDdCMTFFMzhDQzk5MzI3QzQ4QkE1NDUiPiA8eG1wTU06RGVyaXZlZEZyb20gc3RSZWY6aW5zdGFuY2VJRD0ieG1wLmlpZDpFQkM3RkNGNUZENzUxMUUzOENDOTkzMjdDNDhCQTU0NSIgc3RSZWY6ZG9jdW1lbnRJRD0ieG1wLmRpZDpFQkM3RkNGNkZENzUxMUUzOENDOTkzMjdDNDhCQTU0NSIvPiA8L3JkZjpEZXNjcmlwdGlvbj4gPC9yZGY6UkRGPiA8L3g6eG1wbWV0YT4gPD94cGFja2V0IGVuZD0iciI/PpUOrpsAAAAQSURBVHjaYvj//z8DQIABAAj8Av7bok0WAAAAAElFTkSuQmCC");
		$panels['minimee']->set_panel_contents($this->EE->load->view('eedebug_panel', array('logs' => Minimee_helper::get_log()), TRUE));

		if(Minimee_helper::log_has_error())
		{
			$panels['minimee']->set_panel_css_class('flash');
		}

		return $panels;
	}
	// ------------------------------------------------------


	/**
	 * Alias for backwards-compatibility with M1
	 */
	public function minify_html($template, $sub, $site_id)
	{
		return $this->template_post_parse($template, $sub, $site_id);
	}
	// ------------------------------------------------------



	/**
	 * Method for template_post_parse hook
	 *
	 * @param 	string	Parsed template string
	 * @param 	bool	Whether is a sub-template or not
	 * @param 	string	Site ID
	 * @return 	string	Template string, possibly minified
	 */
	public function template_post_parse($template, $sub, $site_id)
	{
		// play nice with others
		if (isset($this->EE->extensions->last_call) && $this->EE->extensions->last_call)
		{
			$template = $this->EE->extensions->last_call;
		}

		// do nothing if not final template
		if ($sub !== FALSE)
		{
			return $template;
		}
		
		// see if we need to post-render any plugin methods
		if (isset($this->cache['template_post_parse']))
		{
			if ( ! class_exists('Minimee'))
			{
				include_once PATH_THIRD . 'minimee/pi.minimee.php';
			}

			// create a new instance of Minimee each time to guarantee defaults
			$m = new Minimee();

			// save our TMPL values to put back into place once finished
			$tagparams = $this->EE->TMPL->tagparams;

			// loop through & call each method
			foreach($this->cache['template_post_parse'] as $needle => $tag)
			{
				Minimee_helper::log('Calling Minimee::display("' . $tag['method'] . '") during template_post_parse: ' . serialize($tag['tagparams']), 3);
				
				$this->EE->TMPL->tagparams = $tag['tagparams'];

				// our second parameter tells Minimee we are calling from template_post_parse
				$out = $m->display($tag['method'], TRUE);

				// replace our needle with output
				$template = str_replace(LD.$needle.RD, $out, $template);

				// reset Minimee for next loop
				$m->reset();
			}
			
			// put things back into place
			$this->EE->TMPL->tagparams = $tagparams;
		}
		
		// do nothing if not (likely) html!
		if ( ! preg_match('/webpage|static/i', $this->EE->TMPL->template_type))
		{
			return $template;
		}
		
		// Are we configured to run through HTML minifier?
		if ($this->config->is_no('minify_html'))
		{
			Minimee_helper::log('HTML minification is disabled.', 3);
			return $template;
		}

		// is Minimee nonetheless disabled?
		if ($this->config->is_yes('disable'))
		{
			Minimee_helper::log('HTML minification aborted because Minimee has been disabled completely.', 3);
			return $template;
		}

		// we've made it this far, so...
		Minimee_helper::log('Running HTML minification.', 3);

		Minimee_helper::library('html');

		// run css & js minification?
		$opts = array();
		if($this->config->is_yes('minify_css'))
		{
			$opts['cssMinifier'] = array('Minify_CSS', 'minify');
		}
		if($this->config->is_yes('minify_js'))
		{
			$opts['jsMinifier'] = array('JSMin', 'minify');
		}

		return Minify_HTML::minify($template, $opts);
	}
	// ------------------------------------------------------


	/**
	 * Save settings
	 *
	 * @return 	void
	 */
	public function save_settings()
	{
		if (empty($_POST))
		{
			Minimee_helper::log($this->EE->lang->line('unauthorized_access'), 1);
		}
		
		else
		{
			// grab our posted form
			$settings = $_POST;
			
			// checkboxes are funny: if they don't exist in post, they must be explicitly added and set to "no"
			$checkboxes = array(
				'combine_css',
				'combine_js',
				'minify_css',
				'minify_html',
				'minify_js'
			);
			
			foreach($checkboxes as $key)
			{
				if ( ! isset($settings[$key]))
				{
					$settings[$key] = 'no';
				}
			}
	
			// run our $settings through sanitise_settings()
			$settings = $this->config->sanitise_settings(array_merge($this->config->get_allowed(), $settings));
			
			// update db
			$this->EE->db->where('class', __CLASS__)
						 ->update('extensions', array('settings' => serialize($settings)));
			
			Minimee_helper::log('Extension settings have been saved.', 3);

			// save the environment			
			unset($settings);

			// let frontend know we succeeeded
			$this->EE->session->set_flashdata(
				'message_success',
			 	$this->EE->lang->line('preferences_updated')
			);

			$this->EE->functions->redirect(BASE.AMP.'C=addons_extensions'.AMP.'M=extension_settings'.AMP.'file=minimee');
		}
	}
	// ------------------------------------------------------


	/**
	 * Settings Form
	 *
	 * @param	Array	Current settings from DB
	 * @return 	void
	 */
	public function settings_form($current)
	{
		$this->EE->load->helper('form');
		$this->EE->load->library('table');

		// Merge the contents of our db with the allowed
		$current = array_merge($this->config->get_allowed(), $current);

		// Used to determine if any advanced settings have been changed
		$clean = $this->config->sanitise_settings($this->config->get_allowed());
		$basic = array('disable', 'cache_path', 'cache_url', 'combine_css', 'combine_js', 'minify_css', 'minify_js', 'minify_html');

		// remove basic settings
		$diff = array_diff(array_keys($clean), $basic);
		$hide_advanced_on_startup = 'TRUE';

		foreach($diff as $key)
		{
			if($clean[$key] != $current[$key])
			{
				$hide_advanced_on_startup = FALSE;
				break;
			}
		}

		// view vars		
		$vars = array(
			'config_warning' => ($this->config->location != 'db') ? lang('config_location_warning') : '',
			'form_open' => form_open('C=addons_extensions'.AMP.'M=save_extension_settings'.AMP.'file=minimee'),
			'settings' => $current,
			'hide_advanced_on_startup' => $hide_advanced_on_startup,
			'flashdata_success' => $this->EE->session->flashdata('message_success')
			);

		// return our view
		return $this->EE->load->view('settings_form', $vars, TRUE);			
	}
	// ------------------------------------------------------


	/**
	 * Update Extension
	 *
	 * @param 	string	String value of current version
	 * @return 	mixed	void on update / false if none
	 */
	public function update_extension($current = '')
	{
		/**
		 * Up-to-date
		 */
		if ($current == '' OR $current == $this->version)
		{
			return FALSE;
		}
		
		/**
		 * 2.0.0
		 * 
		 * - refactor to use new Minimee_config object
		 */
		if (version_compare($current, '2.0.0', '<'))
		{
			$query = $this->EE->db
							->select('settings')
							->from('extensions')
							->where('class', __CLASS__)
							->limit(1)
							->get();
			
			if ($query->num_rows() > 0)
			{
				$settings = unserialize($query->row()->settings);

				// migrate combine
				if(array_key_exists('combine', $settings))
				{
					$settings['combine_css'] = $settings['combine'];
					$settings['combine_js'] = $settings['combine'];
					unset($settings['combine']);
				}

				// migrate minify
				if(array_key_exists('minify', $settings))
				{
					$settings['minify_css'] = $settings['minify'];
					$settings['minify_js'] = $settings['minify'];
					$settings['minify_html'] = $settings['minify'];
					unset($settings['minify']);
				}

				// Sanitise & merge to get a complete up-to-date array of settings
				$settings = $this->config->sanitise_settings(array_merge($this->config->get_allowed(), $settings));
				
				// update db				
				$this->EE->db
						->where('class', __CLASS__)
						->update('extensions', array(
							'hook'		=> 'template_post_parse',
							'method'	=> 'template_post_parse',
							'settings' => serialize($settings)
						));
			}

			$query->free_result();			

			Minimee_helper::log('Upgraded to 2.0.0', 3);
		}

		
		/**
		 * 2.1.8
		 * 
		 * - Include debug panel via EE Debug Toolbar
		 */
		if (version_compare($current, '2.1.8', '<'))
		{
			// grab a copy of our settings
			$query = $this->EE->db
							->select('settings')
							->from('extensions')
							->where('class', __CLASS__)
							->limit(1)
							->get();
			
			if ($query->num_rows() > 0)
			{
				$settings = $query->row()->settings;
			}
			else
			{
				$settings = serialize($this->config->factory()->to_array());
			}
			
			// add extension hook
			$this->EE->db->insert('extensions', array(
				'class'		=> __CLASS__,
				'hook'		=> 'ee_debug_toolbar_add_panel',
				'method'	=> 'ee_debug_toolbar_add_panel',
				'settings'	=> $settings,
				'priority'	=> 10,
				'version'	=> $this->version,
				'enabled'	=> 'y'
			));

			$query->free_result();

			Minimee_helper::log('Upgraded to 2.1.8', 3);
		}

		// update table row with version
		$this->EE->db->where('class', __CLASS__);
		$this->EE->db->update(
					'extensions', 
					array('version' => $this->version)
		);

		Minimee_helper::log('Upgrade complete. Now running ' . $this->version, 3);
	}
	// ------------------------------------------------------

}
// END CLASS

	
/* End of file ext.minimee.php */ 
/* Location: ./system/expressionengine/third_party/minimee/ext.minimee.php */