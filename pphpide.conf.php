<?php
/*================================================================================
	pPHPide - configuration options
	----------------------------------------------------------------------------
	[ pPHPide configuration vars ]

	Some configuration options are for IDE protocol handling support for seamless
	debugging, @see http://pospi.spadgos.com/projects/pPHPide

	Version History:
		3.0 -	Beta phase test phase release
				Complete code rewrite
				Drastic performance improvements, precomputed string substitution etc
				OOP design methodology
				Added protocol handling support for IDE integration
				Created shared behaviour class, for path portability
				Moved to external config file and more stable codebase
				Simplified integration - single include & abstracted configuration
				Debugger:
				- Theme support!
				- Unified variable type checking / hilighting
				- Added wrapper to set debugger output mode
				- Drastically reduced function recursion
				- Added array key variable type & string length outputs
				- Further abstracted debugger output of resource types into their own external function includes (to reduce initial load overhead)
				- Improved string debugging:
				- Added ability to toggle visibility of entire debug blocks
				- Nodes now blink on toggling and scroll to be onscreen, to make them easier to keep track of
				-- Truncation in function parameters
				-- Multiline strings now displayed with line numbers
				-- Scrolling ouput panel for long lines
				-- Line ending match regular expression now as a config var
				-- Now display as collapsible nodes similar to Arrays, Objects etc
				Benchmarker:
				- Benchmark data can now be used externally
				- Compressed variable output / dumping as extra parameters to bench()
				- Benchmarking accuracy improvements & offsets for internal operations
				- Color shading for memory & time usage
				Stack Trace:
				- Added 'compressed mode' variable view for stack trace function parameters
				- Fixes to stack trace, so that it works with PHP5 pseudo-functions
				Error Handler:
				- Initial implementation
				- Added option to log notices / strict warnings
				Added option to output pPHPide initialisation statistics

		2.0 -	@thanks Jason Friedland <jfriedland@vision6.com.au>
				@thanks Jamie Curnow	<jc@jc21.com>
				@thanks Ben Kuskopf		<benkuskopf@hotmail.com>
				 upgraded for PHP5
				 proper object iteration

		1.0 -	@thanks Nick Fisher 	<fisher@spadgos.com>
				 basic functionality
				 string-based print_r() behaviour
	----------------------------------------------------------------------------
	Copyright (c) 2008 Sam Pospischil <pospi@spadgos.com>
  ===============================================================================*/

			// :TODO:
//1
			// variable type tooltips / hilighting for mysql results
			// strings: view as HTML / plain
			// string option: highlight PHP code in output
			// string option: highlight JS / CSS blocks in output
			// add indentation
			// plaintext output mode theme
//2
			// error handler: variable name detection (if possible?)
			// toggleable object nodes in stack
//3
			// (further!) file linkage based on strings (exact matches only, option)? (output file contents? first # lines?)
//4
			// array recursive debug output (as opposed to wildcards)!
			// consistent column widths for array keys / object members
			// go back and link to recursed member with arrays / objects (using reference IDs in plaintext)
//5
			// json theme (& handling) for ajax requests
			// JSON-type HTML theme that works collaboratively with AJAX rendering mode
//6
			// create & integrate grep find class & example pages

	$_PDEBUG_OPTIONS = array(

		// General configuration / features
			'application_root' 					=> '',		// override your web application's root path if necessary (defaults to $_SERVER['DOCUMENT_ROOT'])
			'use_debugger' 						=> true,	// enable debugger with variable folding, hilighting, stack trace and other fanciness
			'auto_stack_trace'					=> true,	// enable stack trace in debugger & error handler
			'use_error_handler' 				=> true,	// enable error handler with stack trace and other fanciness
			'use_find'							=> false,	// enable find class usage
			'show_startup_stats'				=> true,	// enable displaying the memory & procecessing overheads of initialising pPHPide

		// File linkage / IDE protocol handling support
			'server_path_search'				=> '/home/spospischil/',	// replace this path from serverside search results...
			'server_path_replace'				=> 'X:\\',					// ...with this path for display / linkage
			'server_unix'						=> true,							// true if server paths are unix (with /'s)
			'client_unix'						=> false,							// true if client paths are unix (with /'s)
			'translate_string_paths_in_html'	=> true,							// strings like "require('C:\web\myproject\file.inc.php');" gets paths replaced with links / tooltips automatically

		// Debugger options
			'html_theme'						=> 'pPHPide',		// choose a theme! (see below, or make your own)
			'plaintext_theme'					=> 'pPHPide',
			'json_theme'						=> 'pPHPide',

			// only use this if you are having trouble with some wierd server configuration or AJAX api that doesn't autodetect through the built-in checks.
			//										null = auto, or any of 'html', 'text' or 'json'
			'force_output_mode' 				=> null,

			'debug_start_collapsed'				=> false,			// start debugger variable nodes collapsed in HTML view (useful to hide long datasets)
			'adjust_benchmarker_for_debugger'	=> true,			// if true, subtract the time taken to execute debug() calls from benchmarking statistics
			'show_internal_statistics'			=> true,			// if true, show time & memory usage for each debugger function call
			'strict_error_handler'				=> false,			// set to true if you wish to see E_NOTICE and E_STRICT warnings

			// time / memory shading configuration vars
			'benchmarker_time_value_high'		=> 1,				// executions taking longer than this many seconds are considered to be "slow"
			'benchmarker_time_value_low'		=> 0.0001,			// executions this short are considered to be "quick"
			'benchmarker_mem_value_high'		=> 1048576,			// memory usage exceeding this is considered to be "inefficient"
			'benchmarker_mem_value_low'			=> 512,				// memory usage under this is considered to be "efficient"
			'benchmarker_color_high'			=> 0xBB0000,		// display color for long executions
			'benchmarker_color_low'				=> 0x00BB00,		// display color for short executions
			'stack_color_newest'				=> 0xAAFFAA,		// most recent call in stack will be shaded this colour
			'stack_color_oldest'				=> 0xFF8888,		// most ancient call in stack will be shaded this colour

		// Find class (and script) default options (should be made configurable via page inputs, of course!)


		// Security related / Find class readonly options
			'restrict_search_basepath'			=> true,		// restrict searches to only allow searching inside $_PDEBUG_OPTIONS['application_root']
			'restrict_search_to'				=> '',			// restrict search to subfolder of $_PDEBUG_OPTIONS['application_root'] (if restrict_search_basepath), or absolute filesystem path

		// Wierdness / troubleshooting
			'line_ending_regex'					=> "/(\r\n|\r|\n)/",	// modify this if you have issues - it should catch windows, mac or unix text fine though.
			'enable_debug_function_wrappers'	=> true,				// disable to only call debugger functions directly - PDebug::dump(array($var1, $var2, ...)); etc as opposed to dump(). Only use this if you have conflicting function names in the global namespace, and in that case you should probably just rename the pPHPide functions...


	//===================================================================================================================
	//===================================================================================================================


		//
		// Presets for output format for IDE linkage / path output
		// link with pPHPide protocol setup for your IDE, @see http://pospi.spadgos.com/projects/pPHPide/installation.php
		// %p = full path, %f = filename, %l = line number
		//
		// :NOTE: output_line_format_plaintext is also used in substring path highlighting, for matching line numbers. So
		//		  you should keep it consistent with the HTML equivalent for encapsulated string path detection to work in HTML
		//

			// Ultraedit 13-, UEStudio 6.0-, CEdit, ZendIDE etc
			'output_path_format'				=> '<a href="pPHPide:%p%l" title="%p%l">%f%l</a>',
			'output_path_format_plaintext'		=> '%p%l',
			'output_line_format'				=> '/%l',					// substituted into output_path_format at %l
			'output_line_format_plaintext'		=> '/%l',					// substituted into output_path_format_plaintext at %l

/*			// Ultraedit 14+, UEStudio 6.5+
			'output_path_format'				=> '<a href="pPHPide:%p%l" title="%p%l">%f%l</a>',
			'output_path_format_plaintext'		=> '%p%l',
			'output_line_format'				=> '(%l)',					// substituted into output_path_format at %l
			'output_line_format_plaintext'		=> '(%l)',					// substituted into output_path_format_plaintext at %l

			// Ultraedit 14+, UEStudio 6.5+ -- Long path display
			'output_path_format'				=> '<a href="pPHPide:%p%l">%p%l</a>',
			'output_path_format_plaintext'		=> '%p%l',
			'output_line_format'				=> '(%l)',					// substituted into output_path_format at %l
			'output_line_format_plaintext'		=> '(%l)',					// substituted into output_path_format_plaintext at %l

			// IDEs not supporting line delimiters (last resort)
			'output_path_format'				=> '<a href="pPHPide:%p">%p</a>',
			'output_path_format_plaintext'		=> '%p',
			'output_line_format'				=> '',
			'output_line_format_plaintext'		=> '',
*/


	//===================================================================================================================
	//===================================================================================================================


		//
		// debugger output appearance (both HTML and plaintext) to modify as you wish
		// :NOTE: if you define your css / js inline, you avoid dependency troubles &
		//		minimise system impact upon integration.
		//

		'DEBUGGER_THEMES' => array(

			// <html themes>

			'html' => array(

				// <pPHPide default theme>

				'pPHPide' => array(
					'COMMON_HEADER'	=> '<style type="text/css">'

									// general debugger layout
										. '	.PDebug { 	font-family: "trebuchet ms",helvetica,tahoma,sans-serif; font-size: 11px; line-height: 16px; background: #F0F6FF; margin: 0; padding: 3px; border-radius: 5px; -moz-border-radius: 5px; -webkit-border-radius: 5px; border: 2px solid #E18E03; margin: 1px; display: block; }'
										. '	.PDebug li { list-style: none; line-height: 18px; }'
										. ' .PDebug ul { padding: 0; margin-left: 2em; }'
										. ' .PDebug .info { font-weight: bold; color: #E18E03; }'
										. ' .PDebug .info * { font-weight: normal; }'
										. ' .PDebug .info ol.vars li { color: #000; list-style: decimal-leading-zero; }'	// these are only used for variable counter display
										. ' .PDebug .info ol.vars li li { list-style: none; }'
										. ' .PDebug .alt { background: #F0F0FF; }'							// alternate table row colouring, etc

									// stack trace styles
										. ' .PDebug .stack ul li ul, .PDebug .stack ul li ul li { margin: 0; display: inline; }'
										. ' .PDebug .err ul li ul, .PDebug .err ul li ul li { margin: 0; display: inline; }'
										. ' .PDebug .bench>ul, .PDebug .bench>ul>li { margin: 0; display: inline; }'
										. ' .PDebug .stack ul li ul li ul { display: block; }'
										. ' .PDebug .stack ul li { padding: 1px; margin-bottom: 1px; }'
										. ' .PDebug .err ul li ul li ul { display: block; }'
										. ' .PDebug .bench ul li ul { display: block; }'

									// file linkage
										. ' .PDebug a { text-decoration: none; border: 0; color: #0086CE; }'
										. '	.PDebug a:active, .PDebug a:hover { border-style: solid; border-width: 0 0 1px 0; border-color: #0086CE; }'

									// type-specific styles
										. ' .PDebug .array>span>nobr, .PDebug .object>span>nobr, .PDebug .mstring>span>nobr, .PDebug .resource>span>nobr { cursor: pointer; padding: 1px; border: 1px solid; position: relative; z-index: 2; top: 0px; border-radius: 3px; -moz-border-radius: 3px; -webkit-border-radius: 3px; }'
										. ' .PDebug .array>span>nobr { 	color: #363; border-color: #7D9E69; background: #F0FFF6; }'
										. ' .PDebug .object>span>nobr { color: #33D; border-color: #697D9E; background: #F4F6FF; }'
										. ' .PDebug .mstring>span>nobr {	color: #555; border-color: #888; background: #EEE; }'
										. ' .PDebug .resource>span>nobr {	color: #FF7F00; border-color: #FF7F00; background: #FFE9C3; }'
										. ' .PDebug .resource>span>nobr td.resource { color: #AF3F00; border-color: #FF7F00; }'

										. ' .PDebug .array>span>ul, .PDebug .object>span>ul, .PDebug .mstring>span>div {	cursor: pointer; padding: 1px; border: 1px solid; position: relative; z-index: 1; border-radius: 3px; -moz-border-radius: 3px; -webkit-border-radius: 3px; }'
										. ' .PDebug .array>span>ul {	color: #363; border-color: #7D9E69; background: #E2F5E2; }'
										. ' .PDebug .object>span>ul {	color: #33D; border-color: #697D9E; background: #DBEDFB; }'
										. ' .PDebug .mstring>span>div { border-color: #999; background: #E8E8E8; }'
										. ' .PDebug .object>span>ul li.joiner, .PDebug .array>span>ul li.joiner { margin: 0 1em; }'

										. ' .PDebug .array>span>nobr:hover { background: #D0DFD6; }'
										. ' .PDebug .object>span>nobr:hover{ background: #D4D6DF; }'
										. ' .PDebug .resource>span>nobr:hover{ background: #FFEFDF; }'

										. ' .PDebug .object, .PDebug .array { display: list-item-inline-block; }'

										. ' .PDebug .object>span>ul>li:hover {	background: #9BDCFF; }'
										. ' .PDebug .array>span>ul>li:hover {	background: #BAEEB3; }'
										. ' .PDebug .mstring>span>div>ol>li:hover {	background: #FFF; }'

										// remove contents display for 0 length arrays
										. ' .PDebug .array.c0>span>ul { display: none; }'
										. '	.PDebug .array.c0>span>nobr { cursor: default; }'

										// multiline strings display block-level and are scrollable
										. ' .PDebug .mstring, .PDebug .string	{ color: #000 }'
										. ' .PDebug .mstring ol, .PDebug .string ol {	margin: 0 0 0 2em; display: block; border-color: #888; overflow: visible; padding: 0; margin: 0; width: 100%; }'//border: 1px solid #999; border-radius: 3px; -moz-border-radius: 3px; -webkit-border-radius: 3px; }'
										. ' .PDebug .mstring ol li span, .PDebug .string ol li span { cursor: text; color: #888; }'
										. ' .PDebug .mstring ol li, .PDebug .string ol li { margin: 0 0 0 1em; white-space: nowrap; font-family: monospace; list-style: decimal-leading-zero inside !important; }'
										. ' .PDebug .mstring div, .PDebug .string div { overflow: auto; }'
										// single line strings display inline
										. ' .PDebug .string ol, .PDebug .string ol li { white-space: normal; border: 0; display: inline; margin: 0; }'
										. ' .PDebug .string div { display: inline; }'

										. ' .PDebug .boolean span { 	border-color: #00F; color: #00F; }'
										. ' .PDebug .null span {		border-color: #555; color: #555; }'
										. ' .PDebug .integer span {	border-color: #F00; color: #F00; }'
										. ' .PDebug .float span {		border-color: #900; color: #900; }'
										. ' .PDebug .unknown span {	border-color: #999; color: #999; }'

										. ' .PDebug .array ul li, .PDebug .object ul li	   { list-style: none; }'
										. ' .PDebug .array ul>li>li, .PDebug .object ul>li>li { display:inline; position: relative; left: -2em; }'
										. ' .PDebug .array ul>li>li>span, .PDebug .object ul>li>li>span { position: relative; left: 1em; text-indent: -3em !important; }'
										. ' .PDebug .array ul>li>ul, .PDebug .object ul li>ul { margin-left: 1em; }'
										. ' .PDebug .array ul>li>ul>li, .PDebug .object ul>li>ul>li { display: inline; }'
										. ' .PDebug .object ul li ul li.member 		{ color: #344F58; }'
										. ' .PDebug .object ul li ul span.private		{ color: #E00; }'
										. ' .PDebug .object ul li ul span.public		{ color: #090; }'
										. ' .PDebug .object ul li ul span.protected	{ color: #909; }'
										. ' .PDebug .resource tr { color: #447; padding: 0; }'
										. ' .PDebug .resource td { background: #EBC072; padding: 1px 3px; }'
										. ' .PDebug .resource thead td { background: #A88882; color: #FFF; }'
										. ' .PDebug .resource tfoot td { background: #A88882; color: #FFF; }'
										. ' .PDebug .resource table { margin: 0pt 1em; border: 1px solid #EBC072; border-radius: 3px; -moz-border-radius: 3px; -webkit-border-radius: 3px; }'
										. ' .PDebug .resource tr:first-child td:first-child { border-radius-topleft: 3px; -moz-border-radius-topleft: 3px; -webkit-border-radius-topleft: 3px; }'
										. ' .PDebug .resource tr:first-child td:last-child  { border-radius-topright: 3px; -moz-border-radius-topright: 3px; -webkit-border-radius-topright: 3px; }'
										. ' .PDebug .resource tr:last-child td:first-child  { border-radius-bottomleft: 3px; -moz-border-radius-bottomleft: 3px; -webkit-border-radius-bottomleft: 3px; }'
										. ' .PDebug .resource tr:last-child td:last-child   { border-radius-bottomright: 3px; -moz-border-radius-bottomright: 3px; -webkit-border-radius-bottomright: 3px; }'
										. ' .PDebug .resource tr:hover td		{ background: #DBB062; }'
										. ' .PDebug .resource tr.alt td			{ background: #E7D8B4; }'
										. ' .PDebug .resource tr.alt:hover td	{ background: #D7C8A4; }'

									// error string format
										. '	.PDebug .err { color: #F00; }'
										. ' .PDebug .errorText { color: #600; font-style: italic; }'
										. ' .PDebug .errorText li { display: inline; }'

									// global styles applied to benchmarker for differentiating pPHPide calls
										. '	.PDebug .bench				{ color: #711E2A; }'
										. '	.PDebug .dump				{ color: #A400C8; }'
										. '	.PDebug .trace				{ color: #1F721D; }'

										. '	.PDebug .ERROR				{ color: #F00; }'
										. '	.PDebug .WARNING			{ color: #F70; }'
										. '	.PDebug .NOTICE				{ color: #9F0; }'

										/*. '	.PDebug .PARSING:after		{ vertical-align:super; content: "parse"; }'
										. '	.PDebug .CORE:after			{ vertical-align:super; content: "core"; }'
										. '	.PDebug .COMPILE:after		{ vertical-align:super; content: "compile"; }'
										. '	.PDebug .USER:after			{ vertical-align:super; content: "user"; }'
										. '	.PDebug .RECOVERABLE:after	{ vertical-align:super; content: "recoverable"; }'
										. ' .PDebug .STRICT:after		{ vertical-align:super; content: "strict"; }'*/

									// common debugger js
										. "</style>\n"
										. '<script type="text/javascript">'
										. '	var PDebug = {'

										// function to scroll the window to move something important (error message etc) into view
										. '	f: function(el) {'
										. '		if (typeof(el) == "string") {'
										. '			el = document.getElementById(el);'
										. '		}'
										. '		try { el.scrollIntoView(false); } catch(e) { } '  // no idea why this generates a blank erorr if you dont try / catch it! *shrug*
										. '	},'

										// toggle an entire output block's visibility
										. '	c: function(el) {'
										. '		el = el.parentNode;'
										. '		for (var i = 2, node; node = el.childNodes[i]; ++i) {'
										. '			if (node.nodeType == 1) {'
										. '				node.style.display = node.style.display ? "" : "none";'
										. '			}'
										. '		}'
										. '	},'

										// NODE TOGGLING

										// array of nodes that had their onclick events triggered by a click
										. '		clicked_nodes : [],'
										. '		toggle_defer_timer : null,'

										// push nodes to temporary list so childmost clicked one can be determined
										. '	t: function(e) {'
										. '		PDebug.clicked_nodes.push(e.currentTarget);'
										. '		if (!PDebug.toggle_defer_timer) {'
										. '			PDebug.toggle_defer_timer = window.setTimeout(PDebug.doToggle, 0.01);'
										. '		}'
										. '	},'

										// find childmost node clicked, and toggle it!
										. '	doToggle: function() {'
										. '	 	var nodes_in_order = new Array();'
										. '	 	var diff, node1pos;'
										. '	 	var childmost = PDebug.clicked_nodes[0];'

										. '	 	for (var i = 1, node1, node2; node1 = PDebug.clicked_nodes[i-1], node2 = PDebug.clicked_nodes[i]; ++i) {'
										. '	 		diff = PDebug.compareNodes(node1, node2);'
										. '	 		node1pos = 0;'
										. '	 		for (var j = 0; j < nodes_in_order.length; ++j) {'
										. '	 			if (nodes_in_order[j] == node1) {'
										. '	 				node1pos = j;'
										. '	 				break;'
										. '	 			}'
										. '	 		}'
										. '	 		nodes_in_order[node1pos] = node1;'
										. '	 		if (node1pos + diff > 0) {'
										. '	 			nodes_in_order[node1pos + diff] = node2;'
										. '	 		} else if (node1pos + diff < 0) {'
										. '	 			while (diff++ > 1) {'
										. '	 				nodes_in_order.unshift();'
										. '	 			}'
										. '	 			nodes_in_order.unshift(node2);'
										. '	 		} else {'
														// these nodes werent in any kind of hierachy... leave them out? shouldnt happen anyway...
										. '	 		}'
										. '	 	}'
													// first node should now be childmost!
										. '	 	if (nodes_in_order.length > 0) {'
										. '			 childmost = nodes_in_order[0];'
										. '		 }'
										. '	 	window.clearTimeout(PDebug.toggle_defer_timer);'
										. '	 	PDebug.toggle_defer_timer = null;'
													// li->span->[text, *UL*, text]
										. '	 	childmost.firstChild.childNodes[1].style.display = childmost.firstChild.childNodes[1].style.display ? "" : "none";'
										. '		PDebug.flashNode(childmost);'
													// finally, reset the clicked nodes array
										. '	 	PDebug.clicked_nodes = new Array();'
										. '	 },'

										// flash a node to notify it in some way (like, it was just clicked - so you can keep track of it easily)
										. '	flash_timer : null,'
										. '	last_flashing_el : null,'		// last element that was flashing (so we can reset it if a new one is clicked before the animation completes)
										. ' last_flashing_color : "",'		// original color of last flashing element
										. '	def_flash_count : 4,'			// number of times to flash
										. '	flash_color : "#FFA466",'			// color to flash nodes
										. '	flash_count : null,'			// used in flashing logic

										. '	flashNode: function(el) {'
												// clear the old timer & reset old element if it's still flashing
										. '		if (PDebug.flash_timer) {'
										. '			window.clearInterval(PDebug.flash_timer);'										. '			'
										. '			PDebug.last_flashing_el.style.backgroundColor = PDebug.last_flashing_color;'
										. '		}'
												// if parent node is a UL, it's not a root dump so we flash its parent row
										. '		if (el.parentNode.tagName == "UL") {'
										. '			el = el.parentNode;'
										. '		}'
										. '		PDebug.flash_count = PDebug.def_flash_count;'
										. '		PDebug.last_flashing_el = el;'
										. '		PDebug.last_flashing_color = el.style.backgroundColor;'
										. '		PDebug.flash_timer = window.setInterval(PDebug.doFlash, 100);'
										. '		PDebug.f(el);'	// focus on toggled node
										. '	},'

										. '	doFlash: function() {'
										. '		if (PDebug.flash_count-- <= 0) {'
										. '			window.clearInterval(PDebug.flash_timer);'
										. '			PDebug.flash_timer = null;'
										. '		} else {'
										. '			PDebug.last_flashing_el.style.backgroundColor = PDebug.last_flashing_el.style.backgroundColor == PDebug.last_flashing_color ? PDebug.flash_color : PDebug.last_flashing_color;'
										. '		}'
										. '	},'

										// returns the difference in DOM position between two nodes in the same hierachy
										//  (or false, in the event that they aren't... shouldn't happen in this implementation
										. '	 compareNodes: function(node1, node2, reversed) {'
										. '	 	var temp_node = node1.parentNode;'
										. '	 	var found = false;'
										. '	 	var i = 1;'
										. '	 	while (temp_node) {'
										. '	 		if (temp_node == node2) {'
										. '	 			return i;'								   // node2 is above node1 by i steps
										. '	 		}'
										. '	 		temp_node = temp_node.parentNode;'
										. '	 		++i;'
										. '	 	}'
										. '	 	if (typeof reversed != "undefined" && reversed) {'
										. '	 		return false;'									// not in the same node hierachy at all! oh noes!
										. '	 	}'
										. '	 	var step_diff = PDebug.compareNodes(node2, node1, true);'   // compare in opposite direction...
										. '	 	if (step_diff === false) {'
										. '	 		return false;'
										. '	 	}'
										. '	 	return step_diff * -1;'								// ...and return the negative
										. '	 }'
										. '};'
										. "</script>\n",

					// Generic wildcards:
					// 	%s =	subitem - nests another group of items inside this one
					//			used for array pairs, object members, function arguments, table lines etc


					// Debugger wildcards:
					//	%t =	variable type
					// 	%v =	simple variable value (using VARIABLE_OUTPUT_FORMAT) / array value	/ object member
					// 	%k =	array key / object member name
					//	%i =	"info"... object class / array count / string length / resource type / counter variable etc
					//  %c =	collapsed string, if debug_start_collapsed is set (see below:)

					'COLLAPSED_STRING'	  => ' style="display: none"',

					'VARIABLE_OUTPUT_FORMAT' => "\n" . '<li class="%t" title="%t"><span>%v</span></li>' . "\n",

					'INDENT_STRING' 		=> "\t",

					'HEADER_BLOCK' 			=> '<ul class="PDebug">' . "\n",

					'VARIABLES_HEADER'		=>	'<li class="info">Information for %i vars:<ol class="vars">' . "\n",	// :NOTE: this header & footer are only used when dump()ing multiple variables
					'VARIABLES_JOINER'		=>	'',									// don't need one in html, let the <ol> take care of it

					'SINGLELINE_STRING_FORMAT'	=>	'<li class="string" title="string (%i chars)">&quot;<div><ol><li><span>%v</span></li></ol></div>&quot;</li>',
					//	%l =	string line count
					'MULTILINE_STRING_FORMAT'	=>	'<li class="mstring" title="string (%i chars, %l lines)" onclick="PDebug.t(event);"><span><nobr>&nbsp;string (%i chars, %l lines)<span class="inner">&nbsp;&quot;&nbsp;</span></nobr><div><ol>%s</ol></div><nobr><span class="inner">&nbsp;&quot;&nbsp;</span></nobr></span></li>',
					//	%n =	string line number
					//	%v =	string line text
						'MULTILINE_STRING_LINE'	=>	'<li><span>%v</span></li>' . "\n",

					'ARRAY_FORMAT'			=>	'<li class="array c%i" title="array (%i elements)" onclick="PDebug.t(event);"><span><nobr>&nbsp;Array (%i elements)<span class="inner">: [&nbsp;</span></nobr><ul%c>%s</ul><nobr><span class="inner">&nbsp;]&nbsp;</span></nobr></span></li>' . "\n",
						'ARRAY_PAIR'		=>		'<li><ul>%k<li class="joiner"> &#061;&gt; </li>%v</ul></li>',
						'ARRAY_JOINER'		=>		"\n",

					'OBJECT_FORMAT'			=>	'<li class="object" title="%i object" onclick="PDebug.t(event);"><span><nobr>&nbsp;%i Object<span class="inner">: {&nbsp;</span></nobr><ul%c>%s</ul><nobr><span class="inner">&nbsp;}&nbsp;</span></nobr></span></li>' . "\n",
						'OBJECT_MEMBER'		=>		'<li><ul><li class="member">%k:<span class="%i">%i</span></li><li class="joiner">:&#061</li>%v</ul></li>',
						'OBJECT_JOINER'		=>		"\n",

					'GENERIC_FORMAT'		=>	'<li class="resource" title="%t resource" onclick="PDebug.t(event);"><span><nobr>&nbsp;%t [%i]<span class="inner">&nbsp;(&nbsp;</span></nobr><table cellpadding="0" cellspacing="1"%c>%s</table><nobr><span class="inner">&nbsp;)&nbsp;</span></nobr></span></li>' . "\n",
						'GENERIC_HEADER'	=>		'<thead class="%t">%s</thead>' . "\n",
						'GENERIC_BODY'		=>		'<tbody class="%t">%s</tbody>' . "\n",
						'GENERIC_FOOTER'	=>		'<tfoot class="%t">%s</tfoot>' . "\n",
						//	%i in this case is intended as a 'spin' variable for table row styling etc
						'GENERIC_LINE'		=>		'<tr class="%t %i"> %s</tr>' . "\n",
						'GENERIC_CELL'		=>		'<td class="%t">%v</td>' . "\n",							// this is used for generic resources that don't require special formatting
						'GENERIC_TITLED_CELL'	=>	'<td class="%t" title="%t">%v</td>',
						'GENERIC_LINE_JOINER'	=>	"",
						'GENERIC_CELL_JOINER'	=>	"",
					'GENERIC_HEADER_CHARACTER' => '',
					'GENERIC_BORDER_CHARACTER' => '',

					'VARIABLES_FOOTER'		=>	'</ol></li>' . "\n",													// :NOTE: this header & footer are only used when dump()ing multiple variables

					'FOOTER_BLOCK' 			=> '</ul>' . "\n",

					// Benchmarker wildcards:
					//	%i	=	benchmark tag
					//  %n  =   benchmark call number
					// 	%p	=	file path
					//	%t	=	current execution time (s)		<-- these 4 also have %ct, %cm, %cdt & %cdm for HEX strings for shading in HTML mode
					//	%m	=	current mem usage (KB)
					//	%dt	=	time diff since last call (s)
					//	%dm	=	memory diff since last call (KB)
					//  %s  =   variables to dump
					'BENCH_FORMAT'			=>	'<li class="bench" id="PDebug_bench%n">%i : %p @ <span style="color: %ct">%t</span>s [<span style="color: %cdt">%dt</span>s] <span style="color: %cm">%m</span>K [<span style="color: %cdm">%dm</span>K] <ul>%s</ul></li>' . "\n", //<script type="text/javascript">PDebug.f("PDebug_bench%n");</script>' . "\n",

					// This one is like a benchmark, except that it shows stats for pPHPide startup overhead
					// Feel entirely free to disable this with the config var up top if it shits you!
					// :NOTE:
					//  - %t and %m are not used for this, only the overhead is shown.
					//  - %p shows $_SERVER['PHP_SELF'], or server path of the executing script if unavailable
					'STARTUP_STATS_FORMAT'		=>  '<li><span class="resource"><span><nobr style="cursor: default;">:NOTE:</nobr></span></span><span class="info"> pPHPide loaded </span>for <span style="color: #0086CE;">%p</span>: <span style="color: %cdt">%dt</span>s / <span style="color: %cdm">%dm</span>KB</li>',
					//	- %p is additionally not usefd for this one...
					//	- %c however is added as parameter for the calling type
					'INTERNAL_CALL_LOG_FORMAT'	=>  '<li onclick="PDebug.c(this);"><span class="resource"><span><nobr>:NOTE:</nobr></span></span><span class="info %c"> %i </span>: <span style="color: %cdt">%dt</span>s / <span style="color: %cdm">%dm</span>KB</li>',

					// Error handler wildcards:
					//  %n =	error number (since script start)
					//	%e =	error type
					//	%m =	error message
					// 	%p =	file path
					'ERROR_FORMAT' 			=> '<li class="err" id="PDebug_error%n"><b>%e</b> : %p<ul class="errorText">%m</ul></li><script type="text/javascript">PDebug.f("PDebug_error%n");</script>' . "\n",

					// Stack wildcards:
					//	%c = 	class name
					//	%t = 	call type
					// 	%f =	function name
					// 	%s =	function arguments
					// 	%p =	file path
					'STACK_FORMAT'			=>	'<li class="stack"><span class="info">Stack:</span> <ul>%s</ul></li>' . "\n",
						'STACK_LINE'		=> 		'<li style="background: %cs;">&nbsp;%p : %c%t%f( <ul>%s</ul> )</li>' . "\n",
						'STACK_JOINER'		=> 		', ',
				),

				// </pPHPide default theme>
//================================================================================================================
				// <'terminal' theme>

				'terminal' => array(

				),

				// </'terminal' theme>
//================================================================================================================
				// <Yours here?...>

			),

			// </html themes>
//================================================================================================================
//================================================================================================================
			// <plaintext themes>

			'text' => array(

				// <pPHPide default theme>

				'pPHPide' => array(

				),

				// </pPHPide default theme>
				//================================================================================================================
				// <Yours here?...>

			),

			// </plaintext themes>
//================================================================================================================
//================================================================================================================
			// <JSON (javascript / AJAX) themes>

			'json' => array(

				// <pPHPide default theme>

				'pPHPide' => array(

				),

				// </pPHPide default theme>
				//================================================================================================================
				// <Yours here?...>

			),

			// </plaintext themes>

		),


	);

?>
