<?php
if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once PATH_THIRD . 'minimee/config.php';
require_once PATH_THIRD . 'minimee/models/Minimee_config.php';
require_once PATH_THIRD . 'minimee/models/Minimee_logger.php';

/**
 * Minimee: minimize & combine your CSS and JS files. For EE2 only.
 * @author John D Wells <http://johndwells.com>
 * @license http://www.opensource.org/licenses/bsd-license.php BSD license
 * @link	http://johndwells.com/software/minimee
 */
class Minimee_ext {

	public $name			= MINIMEE_NAME;
	public $version			= MINIMEE_VER;
	public $description		= MINIMEE_DESC;
	public $docs_url		= MINIMEE_DOCS;
	public $settings_exist	= 'y';

	public $EE;

	public $log;
	public $config;

	/**
	 * Constructor
	 *
	 * NOTE: We never use the $settings variable passed to us,
	 * because we want our Minimee_config object to always be in charge.
	 * There is an edge case where someone has configured Minimee via an extension,
	 * and then moved to config bootstrap. The bootstrap takes precedence.
	 *
	 * @param 	mixed	Settings array - only passed when activating a hook
	 * @return void
	 */
	public function __construct($settings = array())
	{
		$this->EE =& get_instance();

		// create our logger
		$this->log = new Minimee_logger();

		// create our config object
		$this->config = new Minimee_config();
		
		$this->log->info('Extension has been instantiated.');
	}
	// ------------------------------------------------------


	/**
	 * Activate Extension
	 * 
	 * @return void
	 */
	public function activate_extension()
	{
		$data = array(
			'class'		=> __CLASS__,
			'hook'		=> 'template_post_parse',
			'method'	=> 'minify_html',
			'settings'	=> serialize(array()),
			'priority'	=> 10,
			'version'	=> $this->version,
			'enabled'	=> 'y'
		);
		
		$this->EE->db->insert('extensions', $data);

		$this->log->info('Extension has been activated.');
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

		$this->log->info('Extension has been disabled.');
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
	public function minify_html($template, $sub, $site_id)
	{
		// play nice with others
		if (isset($this->EE->extensions->last_call) && $this->EE->extensions->last_call)
		{
			$template = $this->EE->extensions->last_call;
		}

		// do nothing if not final template
		if($sub !== FALSE)
		{
			return $template;
		}
		
		// do not run through HTML minifier?
		if($this->config->no('minify') || $this->config->no('minify_html'))
		{
			return $template;
		}

		$this->log->info('Running HTML minification.');

		// we've made it this far, so...		
		// include our needed HTML library
		require_once('libraries/HTML.php');
		return Minify_HTML::minify($template);
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
			$this->log->error($this->EE->lang->line('unauthorized_access'));
		}

		// Protected by our sanitise_settings() method, we are safe to pass all of $_POST
		$settings = $this->config->sanitise_settings(array_merge($this->config->allowed, $_POST));
		
		$this->EE->db->where('class', __CLASS__)
					 ->update('extensions', array('settings' => serialize($settings)));
		
		$this->EE->session->set_flashdata(
			'message_success',
		 	$this->EE->lang->line('preferences_updated')
		);

		$this->log->info('Extension settings have been saved.');
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

		// view vars		
		$vars = array('config_loc' => $this->config->location);
		
		// begin with data that either has disabled or not
		if($this->config->location == 'db')
		{
			$extra = '';
			$data = array();
		}
		else
		{
			$extra = ' disabled="disabled"';
			$data = array(
				'disabled' => 'disabled'
			);
		}
		
		// NOTE: we are NOT sanitising so that our contents come straight from the DB
		$current = array_merge($this->config->allowed, $current);

		$no_yes_options = array(
			'no'	=> lang('no'),
			'yes' 	=> lang('yes')
		);
		
		$yes_no_options = array(
			'yes' 	=> lang('yes'),
			'no'	=> lang('no')
		);
		
		$remote_mode_options = array(
			'auto' 	=> lang('auto'), 
			'curl'	=> lang('curl'),
			'fgc' 	=> lang('fgc'),
		);
		
		$vars['settings'] = array(
			'cache_path'	=> form_input(array_merge($data, array('name' => 'cache_path', 'id' => 'cache_path', 'value' => $current['cache_path']))),
			'cache_url'		=> form_input(array_merge($data, array('name' => 'cache_url', 'id' => 'cache_url', 'value' => $current['cache_url']))),
			'minify_html'	=> form_dropdown('minify_html', $no_yes_options, $current['minify_html'], 'id="minify_html" ' . $extra),
			);
		
		$vars['settings_advanced'] = array(
			'disable'		=> form_dropdown('disable', $no_yes_options, $current['disable'], 'id="disable" ' . $extra),
			'remote_mode'	=> form_dropdown('remote_mode', $remote_mode_options, $current['remote_mode'], 'id="remote_mode" ' . $extra),
			'base_path'		=> form_input(array_merge($data, array('name' => 'base_path', 'id' => 'base_path', 'value' => $current['base_path']))),
			'base_url'		=> form_input(array_merge($data, array('name' => 'base_url', 'id' => 'base_url', 'value' => $current['base_url']))),
		);
		
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
		if ($current < '2.0.0')
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

				// Sanitise & merge to get a complete up-to-date array of settings
				$settings = $this->config->sanitise_settings(array_merge($this->config->allowed, $settings));
				
				// update db				
				$this->EE->db
						->where('class', __CLASS__)
						->update('extensions', array(
							'hook'		=> 'template_post_parse',
							'method'	=> 'minify_html',
							'settings' => serialize($settings)
						));
			}
			
			$this->log->info('Upgraded to 2.0.0');
		}

		// update table row with version
		$this->EE->db->where('class', __CLASS__);
		$this->EE->db->update(
					'extensions', 
					array('version' => $this->version)
		);

		$this->log->info('Upgrade complete. Now running ' . $this->version);
	}
	// ------------------------------------------------------

}
// END CLASS

	
/* End of file ext.minimee.php */ 
/* Location: ./system/expressionengine/third_party/minimee/ext.minimee.php */