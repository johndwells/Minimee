<?php

	if ($config_loc == 'db')
	{

		echo form_open('C=addons_extensions'.AMP.'M=save_extension_settings'.AMP.'file=minimee');
		

		$this->table->set_template($cp_pad_table_template);
		$this->table->set_heading(
		    array('data' => lang('basic_config'), 'style' => 'width:50%;'),
		    lang('setting')
		);

		foreach ($settings as $key => $val)
		{
			$left = (lang('note_' . $key)) ? lang($key, $key) . '<small style="display:block;font-weight:normal;margin-top:0.5em">' . lang('note_' . $key) . '</small>' : lang($key, $key);
			$this->table->add_row($left, $val);
		}
		echo $this->table->generate();

		$this->table->clear();
		$this->table->set_template($cp_pad_table_template);
		$this->table->set_heading(
		    array('data' => lang('advanced_config'), 'style' => 'width:50%;'),
		    lang('setting')
		);

		foreach ($settings_advanced as $key => $val)
		{
			$left = (lang('note_' . $key)) ? lang($key, $key) . '<small style="display:block;font-weight:normal;margin-top:0.5em">' . lang('note_' . $key) . '</small>' : lang($key, $key);
			$this->table->add_row($left, $val);
		}
		
?>
		<p><a href="#" id="minimee_advanced_handle"><?php echo lang('advanced_config'); ?></a><br /><br /></p>
		<div id="minimee_advanced_table"><?php echo $this->table->generate(); ?></div>
<?php

		echo '<p>' . form_submit('submit', lang('save'), 'class="submit"') . '</p>';
		$this->table->clear();
		echo form_close();
	}
	else
	{
		echo '<div class="alert info">' . lang('config_loc_caution_' . $config_loc) . '</div>';
	}
?>

	<script type="text/javascript">
		jQuery(function($) {
			(function($) {
				var $MIN_ADV_TABLE = $('#minimee_advanced_table'),
					$MIN_ADV_HANDLE = $('#minimee_advanced_handle').parent('p');
				$MIN_ADV_TABLE.hide();
				$MIN_ADV_HANDLE.click(function(e) {
					e.preventDefault();
					$MIN_ADV_HANDLE.slideUp();
					$MIN_ADV_TABLE.slideDown();
				});
				$('th', $MIN_ADV_TABLE).click(function(e) {
					e.preventDefault();
					$MIN_ADV_HANDLE.slideDown();
					$MIN_ADV_TABLE.slideUp();
				});
		    })(jQuery);
		});
	</script>

<?php
/* End of file settings_form.php */
/* Location: ./system/expressionengine/third_party/minimee/views/settings_form.php */