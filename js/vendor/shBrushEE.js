/*
 GWcode SyntaxHighlighter
 http://gwcode.com/add-ons/gwcode-syntaxhighlighter
============================================================
 Author: Leon Dijk (Twitter: @GWcode)
 Copyright (c) 2011 Gryphon WebSolutions, Leon Dijk
 http://gwcode.com
============================================================
*/
SyntaxHighlighter.brushes.Ee = function()
{
	/* start: for EE */
	String.prototype.trim = function() {
		return (this.replace(/^[\s\xA0]+/, "").replace(/[\s\xA0]+$/, ""))
	}
	String.prototype.startsWith = function(str) {
		return (this.match("^"+str)==str)
	}
	String.prototype.endsWith = function(str) {
		return (this.match(str+"$")==str)
	}
	var ee_tags = ['channel', 'comment', 'rss', 'forum', 'email', 'emoticon', 'ip_to_nation', 'jquery', 'mailinglist', 'member', 'moblog', 'pages', 'query', 'referrer', 'search', 'simple_commerce', 'stats', 'updated_sites']; // standard EE exp:tags
	var std_ee_tag = true;
	var exp_part_arr = '';
	function isValueInArray(arr, val) {
		inArray = false;
		for (i = 0; i < arr.length; i++)
			if (val == arr[i])
				inArray = true;
		return inArray;
	}
	/* end: for EE */

	/* start: XML/XHTML */
	function process(match, regexInfo)
	{
		var constructor = SyntaxHighlighter.Match,
			code = match[0],
			tag = new XRegExp('(&lt;|<)[\\s\\/\\?]*(?<name>[:\\w-\\.]+)', 'xg').exec(code),
			result = []
			;
		
		if (match.attributes != null) 
		{
			var attributes,
				regex = new XRegExp('(?<name> [\\w:\\-\\.]+)' +
									'\\s*=\\s*' +
									'(?<value> ".*?"|\'.*?\'|\\w+)',
									'xg');

			while ((attributes = regex.exec(code)) != null) 
			{
				result.push(new constructor(attributes.name, match.index + attributes.index, 'color1'));
				/* only highlight with 'string' if the value isn't a EE variable, ie: <a href="{site_url}">..</a> */
				var gwValue = attributes.value.trim();
				if(gwValue.startsWith('"{') == false && gwValue.endsWith('}"') == false && gwValue.startsWith("'{") == false && gwValue.endsWith("}'") == false) {
					result.push(new constructor(attributes.value, match.index + attributes.index + attributes[0].indexOf(attributes.value), 'string'));
				}
				else if(gwValue.endsWith('="') == false) {
					result.push(new constructor(attributes.value, match.index + attributes.index + attributes[0].indexOf(attributes.value), 'coloree4'));
				}
				else {
					result.push(new constructor(attributes.value, match.index + attributes.index + attributes[0].indexOf(attributes.value), 'plain'));
				}
			}
		}

		if (tag != null)
			result.push(
				new constructor(tag.name, match.index + tag[0].indexOf(tag.name), 'keyword')
			);

		return result;
	}
	/* end: XML/XHTML */
	/* start: EE */
	function processEE2(match, regexInfo)
	{
		var constructor = SyntaxHighlighter.Match,
			code = match[0],
			tag = new XRegExp('({)[\\s\\/\\?]*(?<name>[:\\w-\\.]+)', 'xg').exec(code),
			result = []
			;
			
		if (tag != null) {
			// remove characters such as {, /, }
			tag.name = tag.name.replace(/[^a-zA-Z:_\-]+/g,'');
		}
		std_ee_tag = true;
		exp_part_arr = tag.name.split(':');
		if(exp_part_arr[0] == 'exp' && exp_part_arr[1] != '') {
			if(!isValueInArray(ee_tags, exp_part_arr[1])) {
				std_ee_tag = false;
			}
		}
		else {
			std_ee_tag = false;
		}
		if(std_ee_tag) {
			result.push(
				new constructor(tag.name, match.index + tag[0].indexOf(tag.name), 'coloree3') // standard EE exp:tag
			);
		}
		else {
			if(exp_part_arr[0] == 'exp') {
				result.push(
					new constructor(tag.name, match.index + tag[0].indexOf(tag.name), 'coloree5') // third-party EE exp:tag
				);
			}
		}

		return result;
	}

	function processEE(match, regexInfo)
	{
		var constructor = SyntaxHighlighter.Match,
			code = match[0],
			tag = new XRegExp('({)[\\s\\/\\?]*(?<name>[:\\w-\\.]+)', 'xg').exec(code),
			result = []
			;

		if (match.attributes != null) 
		{
			var attributes,
				regex = new XRegExp('(?<name> [\\w:\\-\\.]+)' +
									'\\s*=\\s*' +
									'(?<value> ".*?"|\'.*?\'|\\w+)',
									'xg');

			while ((attributes = regex.exec(code)) != null) 
			{
				result.push(new constructor(attributes.name, match.index + attributes.index, 'coloree1')); // exp:tag parameter
				result.push(new constructor(attributes.value, match.index + attributes.index + attributes[0].indexOf(attributes.value), 'coloree2')); // exp:tag parameter value
			}
		}

		if (tag != null) {
			std_ee_tag = true;
			exp_part_arr = tag.name.split(':');
			if(exp_part_arr[0] == 'exp' && exp_part_arr[1] != '') {
				if(!isValueInArray(ee_tags, exp_part_arr[1])) {
					std_ee_tag = false;
				}
			}
			else {
				std_ee_tag = false;
			}
			if(std_ee_tag) {
				result.push(
					new constructor(tag.name, match.index + tag[0].indexOf(tag.name), 'coloree3') // standard EE exp:tag
				);
			}
			else {
				if(exp_part_arr[0] == 'exp') {
					result.push(
						new constructor(tag.name, match.index + tag[0].indexOf(tag.name), 'coloree5') // third-party EE exp:tag
					);
				}
			}
		}

		return result;
	}
	/* end: EE */
	
	this.regexList = [
		/* start: XML/XHTML */
		{ regex: new XRegExp('(\\&lt;|<)\\!\\[[\\w\\s]*?\\[(.|\\s)*?\\]\\](\\&gt;|>)', 'gm'),				css: 'color2' },	// <![ ... [ ... ]]>
		{ regex: SyntaxHighlighter.regexLib.xmlComments,													css: 'comments' },	// <!-- ... -->
		{ regex: new XRegExp('(&lt;|<)[\\s\\/\\?]*(\\w+)(?<attributes>.*?)[\\s\\/\\?]*(&gt;|>)', 'sg'), 	func: process },
		/* end: XML/XHTML */
		/* start: EE */
		{ regex: /{(?!\/if)[/a-z|A-Z|0-9|\-|_]+}/g,															css: 'coloree4' }, // any EE variables that doesn't start with {/if
		{ regex: new XRegExp('{(?!if|/if)[\\s\\/\\?]*(\\w+)(?<attributes>.*?)[\\s\\/\\?].*}', 'sg'),	func: processEE }, // any opening EE tag with possible params and values that doesn't start with {if or {/if
		{ regex: new XRegExp('{(exp:|/exp:)(.*?)[\\s|}]{1}', 'sg'),											func: processEE2 } // any EE tags that haven't been processed with processEE --> {exp: followed by some characters and ending with exactly one space or closing curly brade (})
		/* end: EE */
	];
};

SyntaxHighlighter.brushes.Ee.prototype	= new SyntaxHighlighter.Highlighter();
SyntaxHighlighter.brushes.Ee.aliases	= ['ee'];