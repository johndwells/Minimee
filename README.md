#Minimee

Minimize, combine & cache your CSS and JS files. Minify your HTML. Because size DOES matter.

* Author: [John D Wells](http://johndwells.com)
* [Full Documentation](http://johndwells.com/software/minimee)
* [On @devot-ee](http://devot-ee.com/add-ons/minimee)
* [Forum Support](http://devot-ee.com/add-ons/support/minimee/)


# Version 2.x

## Requirements:

* PHP5
* ExpressionEngine 2.1 or later
* For HTML Minification, EE2.4 or later is required
* For the "Croxton Queue" (see below), EE2.4 or later is required


## Optional Requirements:

* If using `{stylesheet=}` or external URLs, either cURL or file_get_contents() is required
* If using `file_get_contents()`, PHP must be compiled with support for fopen wrappers (allow_url_fopen must be set to true in PHP.ini)
* If using `file_get_contents()` and combining/minifying files over https, PHP must be compiled with OpenSSL support

# Description

Minimee watches your filesystem for changes to your CSS & JS assets, and automatically combines, minifies & caches these assets whenever changes are detected. Use Minimee to effortlessly improve the speed of your site by delivering fewer, smaller CSS & JS assets.

Version 2's substantial re-write has ushered in a host of changes big and small. __Depending on your setup, you may need to make some adjustments prior to _upgrading_ from Minimee 1.x.__ See 'Upgrading from 1.x' below for more.

Minimee is inspired and influenced by [SL Combinator](http://experienceinternet.co.uk/software/sl-combinator/) from Experience Internet, and [Carabiner Asset Management Library](http://codeigniter.com/wiki/Carabiner/) from Tony Dewan. It is released under a BSD License.

Complete and up-to-date documentation can be found on [Minimee's homepage](http://johndwells.com/software/minimee).


# Key Features

## New for 2.x:

* New `exp:minimee:link` tag returns just the URL to your minimee'd asset
* Hooks for 3rd party integration (see [Minimee+LESS](https://github.com/johndwells/Minimee-LESS))
* ALL settings can be specified via config or extension, and then overridden at the tag level
* Path & URL settings can now be relative to site root
* Technique available to have different settings for each CSS/JS asset
* Ability to disable or override the URLs prepended to relative image & @import paths in CSS
* New 'priority' parameter allows you to queue assets into a specific order
* For EE2.4 and above, assets may be queue'd after exp:minimee:display is parsed
* Verbose template debugging messages to help easily track down problems
* Can detect when fully-qualified URLs are in fact local files, and process them as such
* Individually turn off and on minification & combining for all assets (CSS, JS and HTML)

## Since 1.x:

* For EE2.4 and above, can minify your HTML
* Works with the global variables, even {stylesheet}
* Works with external files, over cURL or file_get_contents()
* Embed combined & minified content directly inline to your template
* Compatible with server-side compression & caching


# Behaviour Changes in 2.x

Configuring via Global Variables is no longer supported, and configuring via EE's $config variable has changed; consult the Upgrade notes for more.

The 'debug' setting has been removed. Now, simply turn on EE's "Template Debugging", visit your front end and search the page for "Minimee [" - all Notice, Warning and Error messages will be reported.

With Minimee 1.x if you were to set both `combine="no"` and `minify="no"`, Minimee would disable itself and not run at all.  Now, Minimee will still create cached files of what assets it parses, yet they will simply not be combined into a single file, and not be minified.

A new "Cachebust" setting allows you to manually trigger Minimee to create new cache files. For most setups this is unneccessary, however edge cases will find this useful - such as when [Minimee+LESS](https://github.com/johndwells/Minimee-LESS) needs to be re-run due to a modified `@import` file which Minimee is unable to detect.


# Upgrading from 1.x

## Filename Case Sensitivity

There is a file who's name has **_changed case_**, which may go unrecognised with versioning systems such as SVN/Git; this will cause EE to throw big nasty errors if 1.x is overwritten with 2.x and this case change is not maintained. The file is:

/system/expressionengine/third_party/minimee/libraries/**JSM**in.php

Once you have upgraded Minimee on your server, either through deployment or FTP, you should make sure that this file has its proper case as above.


## Configuration Changes

If you have Minimee 1x installed and are using the Extension, there is nothing you will need to do prior to overwriting system/expressionengine/third_party/minimee.

However **if you have configured Minimee via EE's `$config` or Global Variables**, please note:

* Configuring via global variables is **no longer supported**
* When configuring via EE's `$config`, setting keys have changed to be a single array. See below for details.

# Installation

1. Copy the minimee folder to your system/expressionengine/third_party folder
2. Create a cache folder on your server, accessible by a URL, and ensure permissions allow for reading & writing
3. Follow "Configuration" steps below


# Configuration

_Note: **All settings are now optional**. Out of the box and left un-configured, Minimee 2.x will look for a 'cache' folder at the root of your site, e.g. `http://yoursite.com/cache`. If you would like to change the cache location, then at a minimum you must specify "Cache Path" and "Cache URL" values._

## Config via Extension

content soon

## Config via EE's `$config`

Configuring Minimee via EE's `$config` has the advantage of not requiring any DB calls to initialise or run, saving precious time on your page loads. Going this route requires editing your /system/expressionengine/config/config.php file; alternatively you are encouraged to adopt any of the community-developed "bootstrap" methods, such as:

* [NSM Config Boostrap](http://ee-garage.com/nsm-config-bootstrap) from EE-Garage
* [EE Master Config](https://github.com/focuslabllc/ee-master-config) from Focus Lab LLC
* [This little-known gist](https://gist.github.com/1329538) from @airways

To configure Minimee via EE's `$config` array, the following values are available:

	$config['minimee'] = array(
		
		/**
		 * The base path of your local source assets.
		 * Defaults to site's FCPATH
		 */
		'base_path'			=> '/path/to/site.com',

		/**
		 * The base URL of your local source assets.
		 * Defaults to $EE->config->item('base_url')
		 */
		'base_url'			=> 'http://site.com',
		
		/**
		 * An optional unique 'cachebusting' string to force Minimee to generate a new cache.
		 */
		'cachebust'			=> '',
		
		/**
		 * The path to the cache folder.
		 * Defaults to site's FCPATH + '/cache'
		 */
		'cache_path'		=> '/path/to/site.com/cache',
		
		/**
		 * The URL to the cache folder.
		 * Defaults to $EE->config->item('base_url') + '/cache'
		 */
		'cache_url'			=> 'http://site.com/cache',
		
		/**
		 * Turn on or off ALL combining of assets. 'yes' or 'no'.
		 * Not to be mixed with 'combine_css' or 'combine_js'.
		 * Values: 'yes' or 'no'
		 * Default: yes
		 */
		'combine'			=> 'yes',

		/**
		 * Turn on or off combining of CSS assets only. 'yes' or 'no'.
		 * Values: 'yes' or 'no'
		 * Default: yes
		 */
		'combine_css'		=> 'yes',

		/**
		 * Turn on or off combining of JS assets only. 'yes' or 'no'.
		 * Values: 'yes' or 'no'
		 * Default: yes
		 */
		'combine_js'		=> 'yes',
		
		/**
		 * Whether or not to prepend the base URL to relative @import and image paths in CSS. 'yes' or 'no'.
		 * Values: 'yes' or 'no'
		 * Default: yes
		 */
		'css_prepend_mode'	=> 'yes',
		
		/**
		 * Override the URL used when prepending URL to relative @import and image paths in CSS.
		 * Defaults to Base URL.
		 */
		'css_prepend_url'	=> '/path/to/site.com',

		/**
		 * Turn on or off ALL minifying. 'yes' or 'no'.
		 * Not to be mixed with 'minify_css', 'minify_html' or 'minify_js'.
		 * Values: 'yes' or 'no'
		 * Default: yes
		 */
		'minify'			=> 'yes',

		/**
		 * Turn on or off minifying of CSS assets. 'yes' or 'no'.
		 * Values: 'yes' or 'no'
		 * Default: yes
		 */
		'minify_css'		=> 'yes',

		/**
		 * Turn on or off minifying of JS assets.
		 * Values: 'yes' or 'no'
		 * Default: no
		 */
		'minify_html'		=> 'no',

		/**
		 * Turn on or off minifying of JS assets.
		 * Values: 'yes' or 'no'
		 * Default: yes
		 */
		'minify_js'			=> 'yes',
		
		/**
		 * Specify the method with which Minimee should fetch external & {stylesheet=} assets.
		 * Values: 'auto', 'fgc', or 'curl'
		 * Default: auto
		 */
		'remote_mode'		=> 'auto'
	);

# Usage

## CSS:

	{exp:minimee:css}
		<link href="css/reset.css" rel="stylesheet" type="text/css" />
		<link href="css/webfonts.css" rel="stylesheet" type="text/css" />
		<link href="css/global.css" rel="stylesheet" type="text/css" />
		<link href="css/forms.css" rel="stylesheet" type="text/css" />
	{/exp:minimee:css}
	
	{!-- will render something like: --}
	<link href="http://site.com/cache/fbcff33e698e21d577744cf663ad5653.css?m=1298784510" rel="stylesheet" type="text/css" />

## JS:

	{exp:minimee:js}
		<script src="/js/mylibs/jquery.easing.js" type="text/javascript"></script>
		<script src="/js/mylibs/jquery.cycle.js" type="text/javascript"></script>
		<script src="/js/mylibs/jquery.forms.js" type="text/javascript"></script>
		<script src="/js/scripts.js" type="text/javascript"></script>
		<script src="/js/plugins.js" type="text/javascript"></script>
	{/exp:minimee:js}
	
	{!- will render something like: --}
	<script src="http://site.com/cache/16b6345ae6f4b24dd2b1cba102cbf2fa.js?m=1298784512" type="text/javascript"></script>


# Special Notes / FAQs

## How Minimee creates cache filenames

content soon

## Cleaning your Cache folder

content soon

## Manual 'Cachebusting'

content soon

## The 'Croxton Queue' for EE2.4+

[Mark Croxton](https://github.com/croxton) submitted this [feature request](http://devot-ee.com/add-ons/support/minimee/viewthread/4552#15417) to delay the processing of `exp:minimee:display` until all other template parsing has been completed, by way of leveraging EE2.4's new `template_post_parse` hook. It was a brilliant idea and indication of his mad scientist skills. In his wise words:

> _"Then you would never need worry about parse order when injecting assets into the header or footer of your page using queues."_

This, combined with the new `priority=""` parameter, means you can do something like:

	{exp:minimee:display css="header_css"}
	
	{!-- sometime LATER in EE's parse order --}
	
	{exp:minimee:css queue="header_css" priority="10"}
		<link href="css/forms.css" rel="stylesheet" type="text/css" />
	{/exp:minimee:css}
	
	{!-- and even later in parse order, also note the priority --}
	
	{exp:minimee:css queue="header_css" priority="0"}
		<link href="css/reset.css" rel="stylesheet" type="text/css" />
	{/exp:minimee:css}

And then what ends up happening is that `exp:minimee:display` outputs a cached css that contains, in this order:

1. css/reset.css (first because of priority="0")
2. css/forms.css (second because of priority="10")



## SSL: mixing `http` & `https`

content soon

## Different settings for different files

content soon

## Specifying the template/format of Minimee's cached link/script tags

content soon

## Does Minimee process any `@import` CSS assets?

No. But [Minimee+LESS](https://github.com/johndwells/Minimee-LESS) does.