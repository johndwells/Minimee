#Minimee

Minimize, combine & cache your CSS and JS files. Minify your HTML. Because size DOES matter.

* [Full Documentation](http://johndwells.com/software/minimee)
* [On @devot-ee](http://devot-ee.com/add-ons/minimee)
* [Forum Support](http://devot-ee.com/add-ons/support/minimee/)


# Version 2.x (current BETA)

_Beta means be wary of using in production environments. Beta also means your feedback is hugely appreciated._


## Requirements:

* PHP5
* ExpressionEngine 2.1 or later
* For HTML Minification, EE2.4 or later is required
* For the "Croxton Queue" (see below), EE2.4 or later is required


## Optional Requirements:

* If using `{stylesheet=}` or external URLs, either cURL or file_get_contents() is required
* If using `file_get_contents()`, PHP must be compiled with support for fopen wrappers (allow_url_fopen must be set to true in PHP.ini)
* If using `file_get_contents()` and combining/minifying files over https, PHP must be compiled with OpenSSL support


## Companion Add-Ons

The architecture of Minimee2 has given me the opportunity to build other add-ons that extend Minimee's capabilities.  So if you're curious, have a look at:

* [MSMinimee](https://github.com/johndwells/MSMinimee) - a module that brings full MSM-compatibility to Minimee
* [Minimee+LESS](https://github.com/johndwells/Minimee-LESS) - adds LESS processing to Minimee



# Description

"Minimee combines and compresses JavaScript and CSS files, thereby reducing file sizes and HTTP requests, and turning your puddle of online molasses into a digital fire hose." _- Stephen Lewis, founder, [Experience Internet](http://experienceinternet.co.uk/)_

Minimee watches your filesystem for changes to your CSS & JS assets, and automatically combines, minifies & caches these assets whenever changes are detected. It can also detect changes to stylesheet templates (whether saved as files or not).

Version 2's substantial re-write has ushered in a host of changes big and small. __Depending on your setup, you may need to make some adjustments prior to _upgrading_ from Minimee 1.x.__ See 'Upgrading from 1.x' below for more.

Minimee is inspired and influenced by [SL Combinator](http://experienceinternet.co.uk/software/sl-combinator/) from Experience Internet, and [Carabiner Asset Management Library](http://codeigniter.com/wiki/Carabiner/) from Tony Dewan. It is released under a BSD License.

Complete and up-to-date documentation can be found on [Minimee's homepage](http://johndwells.com/software/minimee).


# Key Features

## New for 2.x:

* Hooks for 3rd party integration (see [Minimee+LESS](https://github.com/johndwells/Minimee-LESS))
* ALL settings can be specified via config or extension, and then overridden at the tag level
* Path & URL settings can now be relative to site root
* New `exp:minimee:link` tag returns just the URL to your minimee'd asset
* Allow for different settings for each CSS/JS asset
* Disable or override the URLs which are prepended to image & @import paths in CSS
* New `priority=""` parameter allows you to queue assets into a specific order
* For EE2.4 and above, assets are queue'd **after** the `exp:minimee:display` tag is parsed
* New `cleanup=""` setting for automatically deleting expired caches
* Verbose template debugging messages to help easily track down errors
* Improved handling of fully-qualified URLs of local assets
* Individually turn off and on minification & combining for all assets (CSS, JS and HTML)

## Since 1.x:

* For EE2.4 and above, can minify your HTML
* Works with EE global variables and `{stylesheet=""}`
* Works with external files, over cURL or file_get_contents()
* Embed combined & minified content directly inline to your template
* Compatible with server-side compression & caching


# Significant Changes in 2.x

### Configuration
Configuring via Global Variables is no longer supported, and __configuring via EE's $config variable has changed__; consult the Upgrade notes for more.

### Debug
The `debug="yes"` setting has been removed. Instead, simply turn on EE's [Template Debugging](http://expressionengine.com/user_guide/cp/admin/output_and_debugging_preferences.html), visit your front end and search the debugging output for messages prefixed with:

* __Minimee [INFO]:__ Debugging messages at important stages in Minimee's processing
* __Minimee [DEBUG]:__ Indicates a potential issue to resolve
* __Minimee [ERROR]:__ Something has gone wrong, and Minimee has failed

### combine="no" and minify="no" no longer disables
With Minimee 1.x if you were to set both `combine="no"` and `minify="no"`, Minimee would disable itself and not run at all.  Now, Minimee will continue to run, and still create cached files of what assets it parses (they will simply not be combined into a single file, and/or not be minified).

_Note: In the case of CSS, there still may be minimal processing to prepend URLs to @import() and image url() values so that your styles continue to work as expected._

### Cachebusting
A new "Cachebust" setting allows you to manually trigger Minimee to create new cache files. For most setups this is unneccessary, however edge cases will find this useful - such as when [Minimee+LESS](https://github.com/johndwells/Minimee-LESS) needs to be re-run due to a modified `@import` file,  which Minimee is unable to detect.


# Upgrading from 1.x

### Filename Case Sensitivity

There is a file who's name has **_changed case_**, which may go unrecognised with versioning systems such as SVN/Git; while a check is in place to account for this, it is recommended that you double-check the filename's casing has been properly maintained. The file is:

/system/expressionengine/third_party/minimee/libraries/**JSM**in.php

Once you have upgraded Minimee on your server, either through deployment or FTP, you should make sure that this file has its proper case as above.


### Configuration Changes

If you have Minimee 1x installed and are using it's Extension, there is nothing you will need to do prior to overwriting system/expressionengine/third_party/minimee.

However **if you have configured Minimee via EE's `$config` or Global Variables**, please note:

* Configuring via global variables is **no longer supported**
* When configuring via EE's `$config`, **setting keys have changed to be a single array**. See below for details.

# Installation

1. Copy the minimee folder to your system/expressionengine/third_party folder
2. Create a cache folder on your server, accessible by a URL, and ensure permissions allow for reading & writing
3. Follow "Configuration" steps below


# Configuration

_Out-of-the-box and left un-configured, Minimee 2.x will look for a 'cache' folder at the root of your site, e.g. `http://yoursite.com/cache`. However this is not recommended in a production setting, as Minimee will first make a database query to check if the Extension is installed. Therefore it is recommended at a minimum to specify Minimee's "Cache Path" and "Cache URL" values._

## Config via Extension

For a visual guide of Minimee's Extension, visit the [Full Documentation](http://johndwells.com/software/minimee).

## Config via EE's `$config`

Configuring Minimee via EE's `$config` has the advantage of not requiring any DB calls to initialise or run, saving precious time on your page loads. Going this route requires editing your /system/expressionengine/config/config.php file; alternatively you are encouraged to adopt any of the community-developed "bootstrap" methods, such as:

* [NSM Config Boostrap](http://ee-garage.com/nsm-config-bootstrap) from [EE-Garage](http://ee-garage.com)
* [EE Master Config](https://github.com/focuslabllc/ee-master-config) from [Focus Lab LLC](http://focuslabllc.com)
* [This little-known gist](https://gist.github.com/1329538) from [@airways](https://twitter.com/airways)

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
		 * An optional unique 'cachebusting' string to force Minimee to generate a new cache whenever updated.
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
		 * When 'yes', Minimee will attempt to delete caches
		 * it has determined to have expired.
		 * Values: 'yes' or 'no'
		 * Default: no
		 */
		'cleanup'		=> 'no',

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
		 * Specify which minification library to use for your CSS.
		 * Values: 'minify' or 'cssmin'
		 * Default: minify
		 */
		'css_library'	=> 'minify',
		
		/**
		 * Whether or not to prepend the base URL to relative
		 * @import and image paths in CSS. 'yes' or 'no'.
		 * Values: 'yes' or 'no'
		 * Default: yes
		 */
		'css_prepend_mode'	=> 'yes',
		
		/**
		 * Override the URL used when prepending URL to relative
		 * @import and image paths in CSS.
		 * Defaults to Base URL.
		 */
		'css_prepend_url'	=> '/path/to/site.com',

		/**
		 * Specify which minification library to use for your JS.
		 * Values: 'jsmin' or 'jsminplus'
		 * Default: jsmin
		 */
		'css_library'	=> 'jsmin',

		/**
		 * Turn on or off ALL minifying. 'yes' or 'no'.
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
		 * Turn on or off minifying of the template HTML.
		 * Values: 'yes' or 'no'
		 * Default: yes
		 */
		'minify_html'		=> 'yes',

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

A cache filename begins with an md5() hash created from the list of files being cached together.  It then appends the last modified timestamp, and if a cachebusting string has been specified, comes next. The result format is:

	// md5.timestamp.cachebust.ext
	03b5614606d3e8d2f28d0b07f802fbbb.1332460675.v2.5.css

This approach means that:

* Any change to the list of files Minimee is to process will result in a new cache filename
* Changing Minimee's settings, _with the exception of the cachebust string_, will __NOT__ create a new cache filename.


## Keeping your Cache folder tidy

The new "cleanup" setting will delete any cache files it has determined have expired. This is done by looking at the timestamp segment of the filename (see above).

Minimee will not attempt to clean up files that are simply older than some unspecified time. Nor will it know to delete caches that are now obsolete, e.g. were created out of a combination of files that is no longer used any more.

__This is not recommended for production mode, since this introduces a risk that Minimee deletes an asset that another browser may still be attempting to download.__


## How to use the 'cachebust'

Coming soon.

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

Note that this parse-order-be-damned technique is available for all of Minimee's tags which harness "queueing", namely:

* `{exp:minimee:display}`
* `{exp:minimee:embed}`
* `{exp:minimee:link}`


## SSL: mixing `http` & `https`

Coming soon.

## Different settings for different assets

Coming soon.

## Specifying the format of Minimee's link & script tags

Coming soon.

## Does Minimee process `@import`'ed CSS assets?

No. But [Minimee+LESS](https://github.com/johndwells/Minimee-LESS) does.