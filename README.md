#Minimee

Minimize, combine & cache your CSS and JS files. Minify your HTML. Because size (still) DOES matter.

* [Full Documentation](http://johndwells.com/software/minimee)
* [On @devot-ee](http://devot-ee.com/add-ons/minimee)
* [Forum Support](http://devot-ee.com/add-ons/support/minimee/)


# Version 2.1.2

## Requirements:

* PHP5
* ExpressionEngine 2.1 or later
* For HTML Minification, EE2.4 or later is required
* For the "Croxton Queue" (see below), EE2.4 or later is required


## Optional Requirements:

* If using `{stylesheet=}` or external URLs, either cURL or file_get_contents() is required
* If using `file_get_contents()`, PHP must be compiled with support for fopen wrappers (allow_url_fopen must be set to true in PHP.ini)
* If using `file_get_contents()` and combining/minifying files over `https`, PHP must be compiled with OpenSSL support


# Description

"Minimee combines and compresses JavaScript and CSS files, thereby reducing file sizes and HTTP requests, and turning your puddle of online molasses into a digital fire hose." _- Stephen Lewis, founder, [Experience Internet](http://experienceinternet.co.uk/)_

Minimee watches your filesystem for changes to your CSS & JS assets, and automatically combines, minifies & caches these assets whenever changes are detected. It can also detect changes to stylesheet templates (whether saved as files or not).

Version 2's substantial re-write has ushered in a host of changes big and small. __Depending on your setup, you may need to make some adjustments prior to _upgrading_ from Minimee 1.x.__ See 'Upgrading from 1.x' below for more.

Minimee is inspired and influenced by [SL Combinator](http://experienceinternet.co.uk/software/sl-combinator/) from Experience Internet, and [Carabiner Asset Management Library](http://codeigniter.com/wiki/Carabiner/) from Tony Dewan. It is released under a BSD License.


## Companion Add-Ons

The architecture of Minimee2 has given me the opportunity to build other add-ons that extend Minimee's capabilities.  So if you're curious, have a look at:

* [MSMinimee](https://github.com/johndwells/MSMinimee) - a module that brings full MSM-compatibility to Minimee
* [Minimee+LESS](https://github.com/johndwells/Minimee-LESS) - adds LESS processing to Minimee


# Key Features

## New for 2.x:

* Hooks for 3rd party integration (see [Minimee+LESS](https://github.com/johndwells/Minimee-LESS), [MSMinimee](https://github.com/johndwells/MSMinimee))
* ALL settings can be specified via config or extension, and then overridden at the tag level
* Path & URL settings can now be relative to site
* New `exp:minimee:link` tag returns just the URL to your minimee'd asset
* Removal of `combine=` and `minify=`, in favor of settings per asset type
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
Configuring via Global Variables is no longer supported, and __configuring via EE's $config variable has changed__; consult the Upgrade notes (below) for more.

### Debug
The `debug="yes"` setting has been removed. Instead, simply turn on EE's [Template Debugging](http://expressionengine.com/user_guide/cp/admin/output_and_debugging_preferences.html), visit your front end and search the debugging output for messages prefixed with:

* __Minimee [INFO]:__ Debugging messages at important stages in Minimee's processing
* __Minimee [DEBUG]:__ Indicates a potential issue to resolve
* __Minimee [ERROR]:__ Something has gone wrong, and Minimee has failed

### Specify combine & minify rules per asset type

There are 5 new settings/parameters that allow fine-grained control of turning on & off combining and minification for each asset type:

* `combine_css`
* `combine_js`
* `minify_css`
* `minify_js`
* `minify_html`

Because of these new settings/parameters, `combine="no"` and `minify="no"` have been removed (as of 2.0.3).

It may also be worth noting that in Minimee 1.x, if you set both `combine="no"` and `minify="no"`, you would effectively disable Minimee.  This is no longer the case - even if every `combine_` and `minify_` is set to `no`, Minimee will continue to run and create cache files (albeit unminified and uncombined). The only way to truly disable Minimee is via `disable`.

### Cachebusting
A new "Cachebust" setting allows you to manually trigger Minimee to create new cache files. For most setups this is unneccessary, however edge cases will find this useful - such as when [Minimee+LESS](https://github.com/johndwells/Minimee-LESS) needs to be re-run due to a modified `@import` file,  which Minimee is unable to detect.


# Upgrading from 1.x

### Filename Case Sensitivity

There is a file who's name has **_changed case_**, which may go unrecognised with versioning systems such as SVN/Git; while a check is in place to account for this, it is recommended that you double-check the filename's casing has been properly maintained (subsequent versions of Minimee may drop this check to save time). The file is:

    // correct:
    /system/expressionengine/third_party/minimee/libraries/JSMin.php
    
    // incorrect:
    /system/expressionengine/third_party/minimee/libraries/jsmin.php

Once you have upgraded Minimee on your server, either through deployment or FTP, you should make sure that this file has its proper case as above.


### Configuration Changes

if you have configured Minimee via EE's `$config` or Global Variables, please note:

* Configuring via global variables is __no longer supported__
* When configuring via EE's `$config`, __setting keys have changed to be a single array__. See below for details.


### Setting/Parameter Changes

As mentioned above, `combine="y|n"` and `minify="y|n"` have been removed in favor of per-asset options.


# Installation

1. Copy the minimee folder to your system/expressionengine/third_party folder
2. Create a cache folder on your server, accessible by a URL, and ensure permissions allow for reading & writing
3. Follow "Configuration" steps below


# Configuration

_Out-of-the-box and left un-configured, Minimee 2.x will look for a 'cache' folder at the root of your site, e.g. `http://yoursite.com/cache`._

## Config via Extension

A visual guide of Minimee2's Extension will eventually be available. In the meantime please see below.

## Config via EE's `$config`

Configuring Minimee via EE's `$config` has the advantage of not requiring any DB calls to initialise or run, saving precious time on your page loads. Going this route requires editing your /system/expressionengine/config/config.php file; alternatively you are encouraged to adopt any of the community-developed "bootstrap" methods, such as:

* [NSM Config Boostrap](http://ee-garage.com/nsm-config-bootstrap) from [EE-Garage](http://ee-garage.com)
* [EE Master Config](https://github.com/focuslabllc/ee-master-config) from [Focus Lab LLC](http://focuslabllc.com)
* [This little-known gist](https://gist.github.com/1329538) from [@airways](https://twitter.com/airways)

To configure Minimee via EE's `$config` array, the following values are available:

	$config['minimee'] = array(

		/**
		 * ==============================================
		 * BASIC PREFERENCES
		 * ==============================================
		 */

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
		 * Turn on or off minifying of CSS assets. 'yes' or 'no'.
		 * Values: 'yes' or 'no'
		 * Default: yes
		 */
		'minify_css'		=> 'yes',

		/**
		 * Turn on or off minifying of the template HTML.
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
		 * ==============================================
		 * DISABLING PREFERENCES
		 * ==============================================
		 */

		/**
		 * Disable Minimee entirely; aborts all activity
		 * and returns all tags untouched.
		 * Values: 'yes' or 'no'
		 * Default: no
		 */
		'disable'			=> 'no',


		/**
		 * ==============================================
		 * ADVANCED PREFERENCES
		 * It is recommended to not specify these unless
		 * you are intending to override their default values.
		 * ==============================================
		 */

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
		 * An optional unique 'cachebusting' string to force
		 * Minimee to generate a new cache whenever updated.
		 */
		'cachebust'			=> '',
		
		/**
		 * When 'yes', Minimee will attempt to delete caches
		 * it has determined to have expired.
		 * Values: 'yes' or 'no'
		 * Default: no
		 */
		'cleanup'		=> 'no',

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
		 * Please note that JSMinPlus is VERY memory-intensive. Not recommended
		 * unless you really know what you're doing. Seriously.
		 *
		 * Values: 'jsmin' or 'jsminplus'
		 * Default: jsmin
		 */
		'js_library'	=> 'jsmin',

		/**
		 * Specify the method with which Minimee should fetch external & {stylesheet=} assets.
		 * Values: 'auto', 'fgc', or 'curl'
		 * Default: auto
		 */
		'remote_mode'		=> 'auto'
	);

# Usage

Every configuration setting mentioned above can also be passed as a tag parameter; __tag parameters will override settings__. Basic usage is as follows:

## CSS:

	{exp:minimee:css}
		<link rel="stylesheet" href="css/reset.css">
		<link rel="stylesheet" href="{stylesheet='css/webfonts'}">
		<link rel="stylesheet" href="http://site.com/css/global.css">
		<link rel="stylesheet" href="css/forms.css">
	{/exp:minimee:css}
	
	{!-- will render something like: --}
	<link rel="stylesheet" href="http://site.com/cache/b488f65d0085dcc6b8f536f533b5f2da.1345797433.css">

## JS:

	{exp:minimee:js}
		<script type="text/javascript" src="/js/mylibs/jquery.easing.js"></script>
		<script type="text/javascript" src="/js/mylibs/jquery.cycle.js"></script>
		<script type="text/javascript" src="/js/mylibs/jquery.forms.js"></script>
		<script type="text/javascript" src="/js/scripts.js"></script>
		<script type="text/javascript" src="/js/plugins.js"></script>
	{/exp:minimee:js}
	
	{!- will render something like: --}
	<script type="text/javascript" src="http://site.com/cache/16b6345ae6f4b24dd2b1cba102cbf2fa.js?m=1298784512"></script>


# Special Notes / FAQs

## Minimee isn't working. Where do I start?

Start by turning on EE's template debugging, and visiting the front end of your site. Search for Minimee's debugging messages (see above), which may help track down the root of trouble.

And unless you have specific reason to do otherwise, all "Advanced Settings" should be left to Minimee's defaults.

## When/how does Minimee know to create a new file?

As a general rule, Minimee creates a new cache if it can detect that an asset's modification date is later than the cache's creation date, __OR__ if the list of assets to cache has been changed in some way.

More specifically, note:

- If the asset is a local file (e.g. /home/your/site/css/styles.css), Minimee checks for the file's modification date
- If the asset is a `{stylesheet=}` template, Minimee first checks the database modification date (and if that template is saved as a file, also checks the file’s modification date)
- If the asset is an “external” file, or a `{path=}` template (see below for more), then __Minimee does not compare anything and only fetches the contents at the time of creating a cache__.

## How Minimee creates cache filenames

A cache filename begins with an md5() hash created from the list of assets being cached together. Minimee then appends the last modified timestamp, and if an optional cachebusting string has been specified, comes next. The result format is:

	// md5.timestamp.[cachebust].ext
	03b5614606d3e8d2f28d0b07f802fbbb.1332460675.v2.5.css

This approach means that:

* Any change to the list of files Minimee is to process will result in a new cache filename
* Changing Minimee's settings, _with the exception of the cachebust string_, will __NOT__ create a new cache filename.


## Keeping your cache folder tidy
The new "cleanup" setting will delete any cache files it has determined have expired. This is done by looking at the timestamp segment of the filename (see above).

Minimee will not attempt to clean up files that are simply older than some unspecified time. Nor will it know to delete caches that are now obsolete, e.g. were created out of a combination of files that is no longer used any more.


## How to use the 'cachebust'

When you specify a cachebust value, such as `v1.0`, this value is appended toward the end of the cache filename (see above). In most situations this is not necessary, since Minimee will continue to look at file timestamps and create new cache files as required.

However one case where it does come in handy is when you are using Minimee+LESS, and having LESS process `@import` files.  Since these files are processed outside of Minimee, it will be unaware of filesystem changes.

Another scenario is when you have a requirement to run your assets through EE's parsing engine and therefore must use the `{path=}` global variable. Since Minimee cannot check for updates on `{path=}` assets (see below), you might use `cachebust` to prompt Minimee to create a new cache.

You may also prefer to simply use the cachebust string as part of tracking your website "revisions". You can create a global variable `{gv_cachebust}`, and pass this as a parameter to each of minimee's opening tags like so:

    {exp:minimee:css cachebust="{gv_cachebust}"}
        ...
    {/exp:minimee:css}

## Does Minimee support `{path=}`?

Yes, and no - If you have CSS/JS in a template that must be run through EE's parsing engine, you may use the `{path=}` global and Minimee will fetch the file's contents, BUT __it will not attempt to detect changes to the template's contents__. This is because there's no reliable way for Minimee to "know" when a change has been made. So consider a `{path=}` asset as the same as any other external asset (e.g. http://static.jquery.com/ui/css/base2.css).


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

When you need Minimee to support a mix of `http` & `https`, you can specify a [Protocol Relative URL](https://www.google.co.uk/search?q=protocol+relative+url) in Minimee's Cache URL and Base URL:

    $config['minimee'] = array(

		...

		// Note the two leading slashes
		'cache_url'			=> '//site.com/cache',
		'base_url'			=> '//site.com',
		
		...
	);

Take note that if you are also changing the URL that is prepended to CSS assets (`css_prepend_url`), you will want to be sure this too is protocol 'agnostic'.

## Different settings for different assets

To specify unique settings for each asset, you must use the `queue` + `display` tags. Take for example you are using a theme from jQuery UI, and would like to cache & combine a copy of the theme CSS.  To do so, any `url()` properties must have a different URL prepended to them than your site.  Here's how you would accomplish this:

    // Our first tag will specify an external URL to prepend to all url() paths
    {exp:minimee:css
        queue="css_head"
        css_prepend_url="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8/themes/base/"
    }
        <link rel="stylesheet" href="http://static.jquery.com/ui/css/base2.css" />
    {/exp:minimee:css}

	// Our second tag does not specify a special prepend URL, so the default (base URL) will be used    
    {exp:minimee:css queue="css_head"}
        <link rel="stylesheet" href="/css/styles.css" />
    {/exp:minimee:css}
    
    // Finally we display our combined output
    {exp:minimee:display css="css_head"}

## Working with LABjs, Head JS, RequireJS or similar

The principle of working with any sort of js loading library is largely the same, which is to use Minimee's `exp:minimee:link` combined with the `queue=` parameter.

This was discussed on [this support issue](https://github.com/johndwells/Minimee/issues/6), and the example below is for how to work with LABjs:

    {exp:minimee:js queue="labjs"}
        <script type="text/javascript" src="/js/jquery.min.js"></script>
        <script type="text/javascript" src="/js/jquery.custom.js"></script>
    {/exp:minimee:js}
    
    ... and then, or sometime later ...
    <script type="text/javascript" src="/js/LABjs.min.js"></script>
    <script>
        $LAB.script("{exp:minimee:link js='labjs'}");
    </script>

This also means that you could use the wait() feature of LABjs. Let's look at LABjs' own example:

    {exp:minimee:js queue="framework"}
        <script type="text/javascript" src="/js/framework.js"></script>
    {/exp:minimee:js}

    {exp:minimee:js queue="plugin.framework"}
        <script type="text/javascript" src="/js/plugin.framework.js"></script>
    {/exp:minimee:js}

    {exp:minimee:js queue="myplugin.framework"}
        <script type="text/javascript" src="/js/myplugin.framework.js"></script>
    {/exp:minimee:js}

    {exp:minimee:js queue="init"}
        <script type="text/javascript" src="/js/init.js"></script>
    {/exp:minimee:js}

    ... and then, or sometime later ...
    <script type="text/javascript" src="/js/LABjs.min.js"></script>
        $LAB
            .script("{exp:minimee:link js='framework'}").wait()
            .script("{exp:minimee:link js='plugin.framework'}")
            .script("{exp:minimee:link js='myplugin.framework'}").wait()
            .script("{exp:minimee:link js='init'}").wait();
    </script>


## Specifying the output of Minimee's link & script tags

When Minimee runs, it looks at the first tag for that particular asset type as the template for a final cache output. If you would like more fine-grained control over the tag output, use the `exp:minimee:link` tag to output only the path to the cache:

    {exp:minimee:css queue="css_head"}
        <link rel="stylesheet" href="/css/styles.css" />
    {/exp:minimee:css}

	...
    
	<link rel="stylesheet" href="{exp:minimee:link css='css_head'}" />

## Does Minimee minify & cache `@import`'ed CSS?

No, but the `@import` rule will remain in the cached file, and the path to the imported CSS file will be adjusted so that the file can still be included by the browser.


## MSM Compatibility

Minimee is already MSM-compatible as long as each Site's cache folder is located in the same relative location. For example:

* http://siteone.com/cache
* http://siteone.com/site2/cache
* http://sitethree.com/cache

To ensure compatibility in these situations, either configure Minimee via config.php using a bootstrap method (examples mentioned above), or config Minimee via the extension but use _relative path and url values_, e.g. `'cache'`, instead of `/cache`.

If your setup does not match the above, and you need per-site configuration, check out [MSMinimee](http://github.com/johndwells/MSMinimee).