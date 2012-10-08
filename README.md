#Minimee

Minimize, combine & cache your CSS and JS files. Minify your HTML. Because size (still) DOES matter.

* [Full Documentation](http://johndwells.com/software/minimee)
* [On @devot-ee](http://devot-ee.com/add-ons/minimee)
* [Forum Support](http://devot-ee.com/add-ons/support/minimee/)


# Version 2.4.8

## Requirements:

* PHP5+
* ExpressionEngine 2.1+

## Optional Requirements:

* EE2.4+ is required for HTML minification
* EE2.4+ is required for the "Croxton Queue" (see below)
* cURL or file_get_contents() is required if using `{stylesheet=}` or external URLs
* If using `file_get_contents()`, PHP must be compiled with support for fopen wrappers (allow_url_fopen must be set to true in PHP.ini)
* If using `file_get_contents()` and combining/minifying files over `https`, PHP must be compiled with OpenSSL support


# Description

"Minimee combines and compresses JavaScript and CSS files, thereby reducing file sizes and HTTP requests, and turning your puddle of online molasses into a digital fire hose." _- Stephen Lewis, founder, [Experience Internet](http://experienceinternet.co.uk/)_

Minimee watches your filesystem for changes to your CSS & JS assets, and automatically combines, minifies & caches these assets whenever changes are detected. It can also detect changes to stylesheet templates (whether saved as files or not).

Version 2's substantial re-write has ushered in a host of changes big and small. It is the same Minimee you've come to rely on, with more power, intelligence, and fun-ness.

Minimee is inspired and influenced by [SL Combinator](http://experienceinternet.co.uk/software/sl-combinator/) from Experience Internet, and [Carabiner Asset Management Library](http://codeigniter.com/wiki/Carabiner/) from Tony Dewan.


## Companion Add-Ons

The architecture of Minimee2 has given me the opportunity to build other add-ons that extend Minimee's capabilities.  So if you're curious, have a look at:

* [MSMinimee](https://github.com/johndwells/MSMinimee) - a module that brings full MSM-compatibility to Minimee
* [Minimee+LESS](https://github.com/johndwells/Minimee-LESS) - adds LESS processing to Minimee


# Key Features

## New for 2.x:


* **ALL settings** can be specified via config or extension, *as well as* via tag parameters
* Ability to turn on and off minification and/or combining **per asset type**
* Better, more verbose debugging with EE's [Template Debugging](http://expressionengine.com/user_guide/cp/admin/output_and_debugging_preferences.html)
* **New API available for 3rd party add-ons**
* Path & URL settings can now be relative to site
* Out-of-the-box default configuration means near zero setup
* New shorthand `exp:minimee` tag allows for quick caching, plus access to a powerful API "interface"
* New ways to display your cache: in a tag, embedded inline, or just the URL
* Purpose-built compatibility with RequireJS, LABjs, etc
* Disable or override the URLs which are prepended to image & @import paths in CSS
* New `priority=""` parameter allows you to queue assets into a specific order
* For EE2.4 and above, assets are queue'd and sorted **after** all templates have been parsed (see the "Croxton Queue" below)
* New `cleanup=""` setting for automatically deleting expired caches
* Verbose template debugging messages to help easily track down errors
* Hooks for 3rd party integration (see [Minimee+LESS](https://github.com/johndwells/Minimee-LESS), [MSMinimee](https://github.com/johndwells/MSMinimee))
* **Plus bug fixes, more fine-grained control, and other improvements!**


## Since 1.x:

* For EE2.4 and above, can minify your HTML
* Works with EE global variables and `{stylesheet=""}`
* Works with external files, over cURL or file_get_contents()
* Embed combined & minified content directly inline to your template
* Compatible with server-side compression & caching


# Installing Minimee

1. Create a cache folder on your server, accessible by a URL, and ensure permissions allow for reading & writing.
2. Copy the minimee folder to your system/expressionengine/third_party folder
3. Follow **Configuration** steps below


# Upgrading from 1.x

Despite Minimee2's significant changes, it has been built to ensure full backwards-compatibility with Minimee 1.x.  So if you're a cow(boy|girl) and like to upgrade add-ons before reading upgrade notes, then we've got you covered.  But to get the most out of Minimee's new abilities and performance, be sure to make it past Step 1.

## Step 1: Update third_party/minimee folder

It's obvious, and it just works. Replace your old `system/ee/third_party/minimee` folder with the new, and you're in business.

## Step 2a: Visit the Extensions CP

If you have Minimee's Extension installed, you will want to visit the Extensions page so that Minimee can upgrade its settings the new architecture.

## Step 2b: Update Config.php

In lieu of Minimee's Extension, you may have Minimee configured via EE's config option, or via EE's Global Variables. If so, please note:

* **EE's Global Variables are deprecated. Please switch to using EE's config instead.**

Additionally, when configuring via EE's `$config`, __setting keys have changed to be a single array__. See the **Configuration** section below for more.

## Step 3: Filename Case Sensitivity

There is a file who's name has **_changed case_**, which may go unrecognised with versioning systems such as SVN/Git; while a check is in place to account for this, it is recommended that you double-check the filename's casing has been properly maintained (subsequent versions of Minimee may drop this check to save time). The file is:

    // correct:
    /system/expressionengine/third_party/minimee/libraries/JSMin.php
    
    // incorrect:
    /system/expressionengine/third_party/minimee/libraries/jsmin.php

Once you have upgraded Minimee on your server, either through deployment or FTP, you should make sure that this file has its proper case as above.


# Configuration

_Out-of-the-box and left un-configured, Minimee 2.x will look for a 'cache' folder at the root of your site, e.g. `http://yoursite.com/cache`._

To get the most out of Minimee's performance offering, configure via EE's `$config` variable; use the Extension for simplicity and convenience, but at the cost of an extra database call.

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
		 * BASIC PREFERENCES (REQUIRED)
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
		 * DISABLING MINIMEE
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

# Parameters

In addition to any configuration values mentioned above, the following parameters can also be passed at runtime:

### exp:minimee:css and exp:minimee:js

##### Optional

* `queue=` To "queue" assets for output; receives a "key" value (e.g. "head_css"), which is used later to lookup and retrieve the queue'd cache
* `priority=` For use with `queue` feature. Value specified is a number; lower numbers are placed earlier in queue order.
* `combine=` Shorthand, runtime override of the combine_(css|js) config option
* `minify=` Shorthand, runtime override of the minify_(css|js) config option
* `display=` Modify what the tag will return upon success; default is "tag"; also available values are "contents" (embedding cache contents inline), or "url" (returning just the cache URL).
* `delimiter=` When not combining, this is the string to place between cache output
* `attribute:name="value"` Override the tag output; useful if changing `display="contents"`, since you will need to specify tag output to avoid contents being returned without a containing tag. See **Advanced Usage** section below.

### exp:minimee:display

##### Required

* `css=` OR `js=` the "key" value of the queue, to fetch and return

##### Optional

* `combine=` Shorthand, runtime override of the combine_(css|js) config option
* `minify=` Shorthand, runtime override of the minify_(css|js) config option
* `display=` Modify what the tag will return upon success; default is "tag"; also available values are "contents" (embedding cache contents inline), or "url" (returning just the cache URL).
* `delimiter=` When not combining, this is the string to place between cache output
* `attribute:name="value"` Override the tag output; useful if changing `display="contents"`, since you will need to specify tag output to avoid contents being returned without a containing tag. See **Advanced Usage** section below.

### exp:minimee

##### Required

* `css=` OR `js=` filename(s) of assets to cache. Cannot specify both in same call.

##### Optional

* `delimiter=` if multiple filenames are supplied, the delimiter used to separate multiple files is by default a comma (`,`). Override with this parameter.
* `display=` specify type of output to display (url, contents or tag); default is `url`
* `display_delimiter=` When not combining cache assets, the results will be delimited by the `delimiter=` string by default; this can be overridden with this value. Defaults to `,`
* `attribute:name="value"` When `display="tag"`, this allows you to format the containing tag output. See **Advanced Usage** section below.
* `queue=` To "queue" assets for output; receives a value 
* `priority=` For use with `queue` feature. Value specified is a number; lower numbers are placed earlier in queue order.
* `combine=` Shorthand, runtime override of the combine_(css|js) config option
* `minify=` Shorthand, runtime override of the minify_(css|js) config option




# Basic Usage

Once configured, basic use of Minimee is as simple and beautiful as:

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


# Advanced Usage


## LABjs

If using the [LABjs](http://labjs.com/) script loading library, you can use the `exp:minimee` API tag to seamlessly integrate with your application:

	<script>
		$LAB
		.script("{exp:minimee js='framework.js'}").wait()
		.script("{exp:minimee js='plugin.framework.js'}")
		.script("{exp:minimee js='myplugin.framework.js'}").wait()
		.script("{exp:minimee js='init.js'}").wait();
	</script>

What's more, you can still combine & cache JS assets using a similar syntax. When doing so, consider setting it up so that should Minimee encounter an error - or be disabled manually - the resulting output can still work with LABjs.

Consider the follwing example from LABjs:

	<script>
		$LAB
		.script("script1.js", "script2.js", "script3.js")
		.wait(function(){
			 // wait for all scripts to execute first
		});
	</script>

By modifying the delimiter which Minimee uses to parse assets, we can do the following:

	<script>
		$LAB
		.script("{exp:minimee js='script1.js", "script2.js", "script3.js' delimiter='", "'}")
		.wait(function(){
			// wait for all scripts to execute first
		});
	</script>

If all goes well, $LAB.script() will receive a single cache file; if not - or if Minimee has been temporarily disabled - it receives the original example's equivalent.

# Special Notes / FAQs

## Minimee isn't working. Where do I start?

Start by turning on EE's template debugging, and visiting the front end of your site. Minieme's debugging messages are each prefixed with one of three labels:

* __Minimee [INFO]:__ Debugging messages at important stages in Minimee's processing
* __Minimee [DEBUG]:__ Indicates a potential issue to resolve
* __Minimee [ERROR]:__ Something has gone wrong, and Minimee has failed

For official support please head over to the @devot-ee forum. When posting, please specify what version of EE and Minimee you are running: [Minimee Support Forums on @devotee](devot-ee.com/add-ons/support/minimee).

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

## What does Minimee do when it encounters an error?

Sometimes Minimee runs into trouble; a file cannot be found, or perhaps the base path has been incorrectly set.  When this happens, Minimee does its best to return your template content ontouched. Keep in mind though that queue'ing may have unintended consequences when an error is encountered; original tagdata may be returned, but it will be returned at the point in which you have asked to `display` the queue'd contents. So code will still be re-ordered.

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

## Tag aliases, and why your Minimee 1.x syntax Just Works

The astute Minimee'er may have noticed that old Minimee 1.x tags such as `exp:minimee:embed` continue to work on 2.x. This is because in 2.x, all "display" methods run through our master exp:minimee:display tag. This ensures we can maintain compatibility with our term aliases ("embed" is synonymous with "contents"). What's more, there's some syntax sugary goodness tossed in there for fun.

### Term Synonyms

* `embed` == `contents`
* `url` == `link`
* `tag` == `display` (since the default `display` behaviour is to output tags)

### Alias tag pairs:

* `exp:minimee:embed` == `exp:minimee:display display="contents"`
* `exp:minimee:contents` == `exp:minimee:display display="contents"`
* `exp:minimee:url` == `exp:minimee:display display="url"`
* `exp:minimee:link` == `exp:minimee:display display="url"`
* `exp:minimee:tag` == `exp:minimee:display display="tag"` == `exp:minimee:display`

### Tag pair "modifiers":

If you append a 4th segment to your tag pair, you can *invoke the display mode automatically*. Check it out:

* `exp:minimee:display:embed` == `exp:minimee:display display="contents"`
* `exp:minimee:display:contents` == `exp:minimee:display display="contents"`
* `exp:minimee:display:url` == `exp:minimee:display display="url"`
* `exp:minimee:display:link` == `exp:minimee:display display="url"`
* `exp:minimee:display:tag` == `exp:minimee:display display="tag"` == `exp:minimee:display`

OK, so that might not be too exciting, but the tag pair modifer also works on our standard `exp:minimee:css` and `exp:minimee:js` tags:

* `exp:minimee:css:embed` == `exp:minimee:css display="contents"`
* `exp:minimee:css:contents` == `exp:minimee:css display="contents"`
* `exp:minimee:css:url` == `exp:minimee:css display="url"`
* `exp:minimee:css:link` == `exp:minimee:css display="url"`
* `exp:minimee:css:tag` == `exp:minimee:css display="tag"` == `exp:minimee:css`

and

* `exp:minimee:js:embed` == `exp:minimee:js display="contents"`
* `exp:minimee:js:contents` == `exp:minimee:js display="contents"`
* `exp:minimee:js:url` == `exp:minimee:js display="url"`
* `exp:minimee:js:link` == `exp:minimee:js display="url"`
* `exp:minimee:js:tag` == `exp:minimee:js display="tag"` == `exp:minimee:js`

And while that may be pretty cool, use these with informed caution - consider what might happen if Minimee encounters an error.


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

## Does Minimee process inline CSS and JS?

No. There are performance implications, error handling gotchas, and it over-complicates what should remain a simple utility.

If you need to "queue" your inline content to appear after other Minimee'd assets, I suggest you consider [Croxton's Stash](github.com/croxton/Stash), which is an incredibly powerful, fast, and *free* tool.


## Does Minimee minify & cache `@import`'ed CSS?

No, but the `@import` rule will remain in the cached file, and the path to the imported CSS file will be adjusted so that the file can still be included by the browser.


## MSM Compatibility

Minimee is already MSM-compatible as long as each Site's cache folder is located in the same relative location. For example:

* http://siteone.com/cache
* http://siteone.com/site2/cache
* http://sitethree.com/cache

To ensure compatibility in these situations, either configure Minimee via config.php using a bootstrap method (examples mentioned above), or config Minimee via the extension but use _relative path and url values_, e.g. `'cache'`, instead of `/cache`.

If your setup does not match the above, and you need per-site configuration, check out [MSMinimee](http://github.com/johndwells/MSMinimee).