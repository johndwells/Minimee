<?php
if (! defined('MINIMEE_VER'))
{
	define('MINIMEE_NAME', 'Minimee');
	define('MINIMEE_VER',  '1.1.7');
	define('MINIMEE_AUTHOR',  'John D Wells');
	define('MINIMEE_DOCS',  'http://johndwells.com/software/minimee');
	define('MINIMEE_DESC',  'Minimee: minimize & combine your CSS and JS files. For EE2 only.');
	
}

$config['name'] = MINIMEE_NAME;
$config['version'] = MINIMEE_VER;
$config['nsm_addon_updater']['versions_xml'] = 'http://johndwells.com/software/versions/minimee';
