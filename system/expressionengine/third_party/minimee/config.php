<?php
if (! defined('MINIMEE_VER'))
{
	define('MINIMEE_NAME', 'Minimee');
	define('MINIMEE_VER',  '2.1.13');
	define('MINIMEE_AUTHOR',  'John D Wells');
	define('MINIMEE_DOCS',  'http://johndwells.github.com/Minimee');
	define('MINIMEE_DESC',  'Minimee: minimize & combine your CSS and JS files. Minify your HTML. For EE2 only.');
}

$config['name'] = MINIMEE_NAME;
$config['version'] = MINIMEE_VER;
$config['nsm_addon_updater']['versions_xml'] = 'http://johndwells.com/software/versions/minimee';