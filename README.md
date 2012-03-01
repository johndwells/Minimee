#Minimee

* Author: [John D Wells](http://johndwells.com)

## Version 2.x

### Requirements:

* PHP5
* ExpressionEngine 2.1 or later
* For HTML Minification, EE2.4 or later is required
* For the "Croxton Queue" (see below), EE2.4 or later is required


## Description

Minimize, combine & cache your CSS and JS files. Minify your HTML. Because size DOES matter.

Version 2 has involved a substantial re-write, ushering in a host of changes big and small. Depending on your situation and setup, you may need to make some adjustments before using Minimee.

Minimee is inspired and influenced by [SL Combinator](http://experienceinternet.co.uk/software/sl-combinator/) from Experience Internet, and [Carabiner Asset Management Library](http://codeigniter.com/wiki/Carabiner/) from Tony Dewan. It is released under a BSD License.

Complete and up-to-date documentation can be found on [Minimee's homepage](http://johndwells.com/software/minimee).


## Key Features

* for EE2.4 and above, can minify your HTML
* new exp:minimee:link tag returns just the URL to your minimee'd asset
* hooks for 3rd party integration (LESS, CE Cache, MSM, etc)
* ALL settings can be specified via config or extension, and then overridden at the tag level
* for EE2.4 and above, the "Croxton Queue" allows queueing of assets with no regard for EE's parse order
* Verbose template debugging messages to help easily track down problems
* individually turn off and on minification & combining for all assets (CSS, JS and HTML)
* works with the global variables, even {stylesheet}
* works with external files, over cURL or file_get_contents()
* embed combined & minified content directly inline to your template
* compatible with server-side compression & caching


## Changes in 2.x

The 'debug' setting has been removed. Now, simply turn on EE's "Template Debugging", visit your front end and search the page for "Minimee [" - all Notice, Warning and Error messages will be reported.

In Minimee 1.x if you were to set both combine="no" and minify="no", Minimee would disable itself and not run at all.  However now, Minimee will still create cached files of what assets it parses, yet they will simply not be combined into a single file, and not be minified.





## Upgrading from 1.x

If you have Minimee 1x installed and are using the Extension, there is nothing you will need to do prior to overwriting
system/expressionengine/third_party/minimee.

However IF YOU HAVE CONFIGURED MINIMEE VIA CONFIG OR GLOBAL VARIABLES, note:

* Configuring via global variables are no longer supported
* Specifying config options has changed to be a single array, e.g.

	// For a complete list of new configuration settings, see below.
	$config['minimee'] = array(
		'cache_path' => '/path/to/domain.com/cache',
		'cache_url' => 'http://domain.com/cache',
		...
	);

