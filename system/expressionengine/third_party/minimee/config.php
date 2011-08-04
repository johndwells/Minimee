<?php
if (! defined('MIMIMEE_VER'))
{
	define('MIMIMEE_NAME', 'Minimee');
	define('MIMIMEE_VER',  '1.1.3');
	define('MIMIMEE_AUTHOR',  'John D Wells');
	define('MIMIMEE_DOCS',  'http://johndwells.com/software/minimee');
	define('MIMIMEE_DESC',  'Minimee: minimize & combine your CSS and JS files. For EE2 only.');
	
}

$config['name'] = MIMIMEE_NAME;
$config['version'] = MIMIMEE_VER;
$config['nsm_addon_updater']['versions_xml'] = 'http://johndwells.com/software/versions/minimee';
