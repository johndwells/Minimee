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

Minimize, combine & cache your CSS and JS files. Minify your HTML. Because size DOES matter.

Version 2 has enjoyed a substantial re-write, ushering in a host of changes big and small. __Depending on your setup, you may need to make some adjustments prior to installing Minimee 2__.

Minimee is inspired and influenced by [SL Combinator](http://experienceinternet.co.uk/software/sl-combinator/) from Experience Internet, and [Carabiner Asset Management Library](http://codeigniter.com/wiki/Carabiner/) from Tony Dewan. It is released under a BSD License.

Complete and up-to-date documentation can be found on [Minimee's homepage](http://johndwells.com/software/minimee).


# Key Features

## New for 2.x:

* New `exp:minimee:link` tag returns just the URL to your minimee'd asset
* Hooks for 3rd party integration (see [Minimee+LESS](https://github.com/johndwells/Minimee-LESS))
* ALL settings can be specified via config or extension, and then overridden at the tag level
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

With Minimee 1.x if you were to set both combine="no" and minify="no", Minimee would disable itself and not run at all.  Now, Minimee will still create cached files of what assets it parses, yet they will simply not be combined into a single file, and not be minified.

A new "Cachebust" setting allows you to manually trigger Minimee to create new cache files. For most setups this is unneccessary, however edge cases (such as when Minimee+LESS needs to be re-run due to a modified `@import`'ed file that Minimee can't detect) will find this setting useful.


# Upgrading from 1.x

## Filename Case Sensitivity

There is a file who's name has changed case, which may go unrecognised with versioning systems such as SVN/Git; this will cause EE to throw big nasty errors if 1.x is overwritten with 2.x and this case change is not maintained. The file is:

/system/expressionengine/third_party/minimee/libraries/JSMin.php

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

## Config via EE's `$config`

	$config['minimee'] = array(
		'base_path'			=> '/path/to/site.com',			// defaults to site's FCPATH
		'base_url'			=> 'http://site.com',			// defaults to $EE->config->item('base_url')
		'cachebust'			=> '',							// unique string to force a new cache
		'cache_path'		=> '/path/to/site.com/cache',	// defaults to site's FCPATH + '/cache'
		'cache_url'			=> 'http://site.com/cache',		// defaults to $EE->config->item('base_url') + '/cache'
		'combine'			=> 'yes',						// 'yes' or 'no'
		'combine_css'		=> 'yes',						// 'yes' or 'no'
		'combine_js'		=> 'yes',						// 'yes' or 'no'
		'css_prepend_mode'	=> 'yes',						// 'yes' or 'no'
		'css_prepend_url'	=> '/path/to/site.com',			// defaults to $EE->config->item('base_url')
		'minify'			=> 'yes',						// 'yes' or 'no'
		'minify_css'		=> 'yes',						// 'yes' or 'no'
		'minify_html'		=> 'no',						// 'yes' or 'no'
		'minify_js'			=> 'yes',						// 'yes' or 'no'
		'remote_mode'		=> 'auto'						// 'auto', 'fgc', or 'curl'
	);


# Special Notes / FAQs

## How Minimee creates cache filenames

## Cleaning your Cache folder

## Manual 'Cachebusting'

## The 'Croxton Queue' for EE2.4+

## SSL: mixing `http` & `https`

## Different settings for different files