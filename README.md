TODO:

- for 2.4+, queue operates at template_post_parse hook
- test combine on/off for other tag methods (embed, link, url, etc)

#Minimee

* Author: [John D Wells](http://johndwells.com)

## Version 2.x

### Requirements:

* PHP5
* ExpressionEngine 2.1 or later
* For HTML Minification, EE2.4 or later is required

## Description

Minimize, combine & cache your CSS and JS files. Minify your HTML. Because size DOES matter.

## Upgrading from 1.x

If you have configured Minimee via extension, there is nothing you need to do differently from any other add-on upgrade.

However for those configuring Minimee via config or global variables, you will have to alter your configuration:

- Global variables are no longer supported
- Rather than each configuration item requiring its own key/value pair in the config array,
  simply provide one array with all settings within.

## Features (shortlist only)

* for EE2.4 and above, can minify your HTML
* works with the global variables, even {stylesheet}
* works with external files, over cURL or file_get_contents()
* embed combined & minified content directly inline to your template
* queue files for later
* compatible with server-side compression & caching

Minimee is inspired and influenced by [SL Combinator](http://experienceinternet.co.uk/software/sl-combinator/) from Experience Internet, and [Carabiner Asset Management Library](http://codeigniter.com/wiki/Carabiner/) from Tony Dewan. It is released under a BSD License.

Complete and up-to-date documentation can be found on [Minimee's homepage](http://johndwells.com/software/minimee).
