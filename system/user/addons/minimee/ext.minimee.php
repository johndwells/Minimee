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
		ee()->db->insert('extensions', array(
			'class'		=> __CLASS__,
			'hook'		=> 'template_post_parse',
			'method'	=> 'template_post_parse',
			'settings'	=> serialize($settings),
			'priority'	=> 10,
			'version'	=> $this->version,
			'enabled'	=> 'y'
		));

		// EE Debug Toolbar hook
		ee()->db->insert('extensions', array(
			'class'		=> __CLASS__,
			'hook'		=> 'ee_debug_toolbar_add_panel',
			'method'	=> 'ee_debug_toolbar_add_panel',
			'settings'	=> serialize($settings),
			'priority'	=> 10,
			'version'	=> $this->version,
			'enabled'	=> 'y'
		));

		// CE Cache ce_cache_pre_save hook
		ee()->db->insert('extensions', array(
			'class'		=> __CLASS__,
			'hook'		=> 'ce_cache_pre_save',
			'method'	=> 'ce_cache_pre_save',
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
		ee()->db->where('class', __CLASS__);
		ee()->db->delete('extensions');

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
		$panels = (ee()->extensions->last_call != '' ? ee()->extensions->last_call : $panels);

		$panels['minimee'] = new Eedt_panel_model();
		$panels['minimee']->set_name('minimee');
		$panels['minimee']->set_button_label("Minimee");
		$panels['minimee']->set_button_icon("data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAyhpVFh0WE1MOmNvbS5hZG9iZS54bXAAAAAAADw/eHBhY2tldCBiZWdpbj0i77u/IiBpZD0iVzVNME1wQ2VoaUh6cmVTek5UY3prYzlkIj8+IDx4OnhtcG1ldGEgeG1sbnM6eD0iYWRvYmU6bnM6bWV0YS8iIHg6eG1wdGs9IkFkb2JlIFhNUCBDb3JlIDUuNS1jMDIxIDc5LjE1NTc3MiwgMjAxNC8wMS8xMy0xOTo0NDowMCAgICAgICAgIj4gPHJkZjpSREYgeG1sbnM6cmRmPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5LzAyLzIyLXJkZi1zeW50YXgtbnMjIj4gPHJkZjpEZXNjcmlwdGlvbiByZGY6YWJvdXQ9IiIgeG1sbnM6eG1wPSJodHRwOi8vbnMuYWRvYmUuY29tL3hhcC8xLjAvIiB4bWxuczp4bXBNTT0iaHR0cDovL25zLmFkb2JlLmNvbS94YXAvMS4wL21tLyIgeG1sbnM6c3RSZWY9Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC9zVHlwZS9SZXNvdXJjZVJlZiMiIHhtcDpDcmVhdG9yVG9vbD0iQWRvYmUgUGhvdG9zaG9wIENDIDIwMTQgKE1hY2ludG9zaCkiIHhtcE1NOkluc3RhbmNlSUQ9InhtcC5paWQ6NjQxMzVDNTBGRDdCMTFFMzhDQzk5MzI3QzQ4QkE1NDUiIHhtcE1NOkRvY3VtZW50SUQ9InhtcC5kaWQ6NjQxMzVDNTFGRDdCMTFFMzhDQzk5MzI3QzQ4QkE1NDUiPiA8eG1wTU06RGVyaXZlZEZyb20gc3RSZWY6aW5zdGFuY2VJRD0ieG1wLmlpZDpFQkM3RkNGNUZENzUxMUUzOENDOTkzMjdDNDhCQTU0NSIgc3RSZWY6ZG9jdW1lbnRJRD0ieG1wLmRpZDpFQkM3RkNGNkZENzUxMUUzOENDOTkzMjdDNDhCQTU0NSIvPiA8L3JkZjpEZXNjcmlwdGlvbj4gPC9yZGY6UkRGPiA8L3g6eG1wbWV0YT4gPD94cGFja2V0IGVuZD0iciI/PpUOrpsAAAAQSURBVHjaYvj//z8DQIABAAj8Av7bok0WAAAAAElFTkSuQmCC");
		$panels['minimee']->set_panel_contents(ee()->load->view('eedebug_panel', array('logs' => Minimee_helper::get_log()), TRUE));

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
	 * Hook for CE Cache
	 *
	 * @param string $template
	 * @param string $type 'fragment' or 'static'
	 */
	public function ce_cache_pre_save($template, $type)
	{
		// play nice with others
		if (isset(ee()->extensions->last_call) && ee()->extensions->last_call)
		{
			$template = ee()->extensions->last_call;
		}

		// Are we configured to run HTML minification on this hook?
		if ($this->config->minify_html_hook != 'ce_cache_pre_save')
		{
			Minimee_helper::log('HTML minification is not configured to run when saving CE Cache contents.', 3);
			return $template;
		}

		// do and done
		Minimee_helper::log('HTML minification is configured to run whenever saving CE Cache contents.', 3);
		return $this->_minify_html($template);
	}
	// ------------------------------------------------------


	/**
	 * Method for template_post_parse hook
	 *
	 * @param 	string	Parsed template string
	 * @param 	bool	Whether is a sub-template (partial as of EE 2.8) or not
	 * @param 	string	Site ID
	 * @return 	string	Template string, possibly minified
	 */
	public function template_post_parse($template, $sub, $site_id)
	{
		// play nice with others
		if (isset(ee()->extensions->last_call) && ee()->extensions->last_call)
		{
			$template = ee()->extensions->last_call;
		}

		// do nothing if not final template
		if ($sub !== FALSE)
		{
			return $template;
		}

		// do nothing if not (likely) html!
		if ( ! preg_match('/webpage|static/i', ee()->TMPL->template_type))
		{
			return $template;
		}

		// attempt to post-process Minimee's display tag
		$template = $this->_display_post_parse($template);

		// Are we configured to run HTML minification on this hook?
		if ($this->config->minify_html_hook != 'template_post_parse')
		{
			Minimee_helper::log('HTML minification is not configured to run during template_post_parse.', 3);
			return $template;
		}

		// do and done
		Minimee_helper::log('HTML minification is configured to run during the final call to template_post_parse.', 3);
		return $this->_minify_html($template);
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
			Minimee_helper::log(ee()->lang->line('unauthorized_access'), 1);
		}

		else
		{
			// grab our posted form
			$settings = $_POST;

			// checkboxes now come in as an array,
			// but we want to cast them to a string, so as to be compatible with our config service
			$checkboxes = array(
				'combine_css',
				'combine_js',
				'minify_css',
				'minify_html',
				'minify_js'
			);

			foreach($checkboxes as $key)
			{
				if (isset($settings[$key]))
				{
					$settings[$key] = $settings[$key][0];
				} else {
					$settings[$key] = 'no';
				}
			}

			// run our $settings through sanitise_settings()
			$settings = $this->config->sanitise_settings(array_merge($this->config->get_allowed(), $settings));

			// update db
			ee()->db->where('class', __CLASS__)
						 ->update('extensions', array('settings' => serialize($settings)));

			Minimee_helper::log('Extension settings have been saved.', 3);

			// save the environment
			unset($settings);

			// make an alert but defer until next request
			ee('CP/Alert')->makeInline('minimee-save-settings-success')
						  ->asSuccess()
						  ->cannotClose()
						  ->withTitle(lang('preferences_updated'))
						  ->defer();

			// return to the settings form
			ee()->functions->redirect(ee('CP/URL')->make('addons/settings/minimee'));
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
		// Merge the contents of our db with the allowed
		$current = array_merge($this->config->get_allowed(), $current);

		if(($this->config->location != 'db'))
		{
			ee('CP/Alert')->makeInline('config-warning')
						  ->asWarning()
						  ->cannotClose()
						  ->withTitle(lang('config_location_warning_title'))
						  ->addToBody(lang('config_location_warning'))
						  ->now();
		}

		// view vars
		$vars = array(
			'cp_page_title' => lang('preferences'),
			'base_url' => ee('CP/URL')->make('addons/settings/minimee/save'),
			'save_btn_text' => 'btn_save_settings',
			'save_btn_text_working' => 'btn_saving',
			'sections' => array(
				'basic_config' => array(
					array(
						'title' => 'disable',
						'fields' => array(
							'disable' => array(
								'type' => 'yes_no',
								'value' => $current['disable']
							)
						)
					),
					array(
						'title' => 'cache_path',
						'desc' => 'cache_path_note',
						'fields' => array(
							'cache_path' => array(
								'type' => 'text',
								'value' => $current['cache_path']
							)
						)
					),
					array(
						'title' => 'cache_url',
						'desc' => 'cache_url_note',
						'fields' => array(
							'cache_url' => array(
								'type' => 'text',
								'value' => $current['cache_url']
							)
						)
					),
					array(
						'title' => 'combine',
						'desc' => 'combine_note',
						'fields' => array(
							'combine_css' => array(
								'type' => 'checkbox',
								'choices' => array(
									'yes' => 'CSS'
								),
								'value' => $current['combine_css']
							),
							'combine_js' => array(
								'type' => 'checkbox',
								'choices' => array(
									'yes' => 'JS'
								),
								'value' => $current['combine_js']
							)
						)
					),
					array(
						'title' => 'minify',
						'desc' => 'minify_note',
						'fields' => array(
							'minify_css' => array(
								'type' => 'checkbox',
								'choices' => array(
									'yes' => 'CSS'
								),
								'value' => $current['minify_css']
							),
							'minify_js' => array(
								'type' => 'checkbox',
								'choices' => array(
									'yes' => 'JS'
								),
								'value' => $current['minify_js']
							),
							'minify_html' => array(
								'type' => 'checkbox',
								'choices' => array(
									'yes' => 'HTML'
								),
								'value' => $current['minify_html']
							)
						)
					)
				),
				'advanced_config' => array(
					array(
						'title' => 'base_path',
						'desc' => 'base_path_note',
						'fields' => array(
							'base_path' => array(
								'type' => 'text',
								'value' => $current['base_path']
							)
						)
					),
					array(
						'title' => 'base_url',
						'desc' => 'base_url_note',
						'fields' => array(
							'base_url' => array(
								'type' => 'text',
								'value' => $current['base_url']
							)
						)
					),
					array(
						'title' => 'cachebust',
						'desc' => 'cachebust_note',
						'fields' => array(
							'cachebust' => array(
								'type' => 'text',
								'value' => $current['cachebust']
							)
						)
					),
					array(
						'title' => 'cleanup',
						'desc' => 'cleanup_note',
						'fields' => array(
							'cleanup' => array(
								'type' => 'yes_no',
								'value' => $current['cleanup']
							)
						)
					),
					array(
						'title' => 'hash_method',
						'desc' => 'hash_method_note',
						'fields' => array(
							'hash_method' => array(
								'type' => 'select',
								'choices' => array(
									'sha1' => lang('sha1'),
									'md5' => lang('md5'),
									'sanitize' => lang('sanitize')
								),
								'value' => $current['hash_method']
							)
						)
					),
					array(
						'title' => 'css_prepend_mode',
						'desc' => 'css_prepend_mode_note',
						'fields' => array(
							'css_prepend_mode' => array(
								'type' => 'inline_radio',
								'choices' => array(
									'no' => lang('Off'),
									'yes' => lang('On')
								),
								'value' => $current['css_prepend_mode']
							)
						)
					),
					array(
						'title' => 'css_prepend_url',
						'desc' => 'css_prepend_url_note',
						'fields' => array(
							'css_prepend_url' => array(
								'type' => 'text',
								'value' => $current['css_prepend_url']
							)
						)
					),
					array(
						'title' => 'css_library',
						'desc' => 'css_library_note',
						'fields' => array(
							'css_library' => array(
								'type' => 'select',
								'choices' => array(
									'minify' => lang('minify'),
									'cssmin' => lang('cssmin')
								),
								'value' => $current['css_library']
							)
						)
					),
					array(
						'title' => 'js_library',
						'desc' => 'js_library_note',
						'fields' => array(
							'js_library' => array(
								'type' => 'select',
								'choices' => array(
									'jsmin' => lang('jsmin'),
									'jsminplus' => lang('jsminplus')
								),
								'value' => $current['js_library']
							)
						)
					),
					array(
						'title' => 'remote_mode',
						'desc' => 'remote_mode_note',
						'fields' => array(
							'remote_mode' => array(
								'type' => 'select',
								'choices' => array(
									'auto' => lang('auto'),
									'curl' => lang('curl'),
									'fgc' => lang('fgc')
								),
								'value' => $current['remote_mode']
							)
						)
					),
					array(
						'title' => 'save_gz',
						'desc' => 'save_gz_note',
						'fields' => array(
							'save_gz' => array(
								'type' => 'yes_no',
								'value' => $current['save_gz']
							)
						)
					),
					array(
						'title' => 'minify_html_hook',
						'desc' => 'minify_html_hook_note',
						'fields' => array(
							'minify_html_hook' => array(
								'type' => 'select',
								'choices' => array(
									'template_post_parse' => lang('template_post_parse'),
									'ce_cache_pre_save' => lang('ce_cache_pre_save')
								),
								'value' => $current['minify_html_hook']
							)
						)
					)
				)
			)
		);

		// return our view
		return ee('View')->make('minimee:settings_form')->render($vars);
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

		// update table row with version
		ee()->db->where('class', __CLASS__);
		ee()->db->update(
					'extensions',
					array('version' => $this->version)
		);

		Minimee_helper::log('Upgrade complete. Now running ' . $this->version, 3);
	}
	// ------------------------------------------------------

	/**
	 * Helper function to find & process any queue'd plugin tags
	 *
	 * @param string $template
	 * @return string
	 */
	protected function _display_post_parse($template)
	{
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
			$tagparams = ee()->TMPL->tagparams;

			// loop through & call each method
			foreach($this->cache['template_post_parse'] as $needle => $tag)
			{
				Minimee_helper::log('Calling Minimee::display("' . $tag['method'] . '") during template_post_parse: ' . serialize($tag['tagparams']), 3);

				ee()->TMPL->tagparams = $tag['tagparams'];

				// our second parameter tells Minimee we are calling from template_post_parse
				$out = $m->display($tag['method'], TRUE);

				// replace our needle with output
				$template = str_replace(LD.$needle.RD, $out, $template);

				// reset Minimee for next loop
				$m->reset();
			}

			// put things back into place
			ee()->TMPL->tagparams = $tagparams;
		}

		return $template;
	}
	// ------------------------------------------------------


	/**
	 * Run html minification on template tagdata
	 *
	 * @param string $template
	 * @return 	string
	 */
	protected function _minify_html($template)
	{
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

}
