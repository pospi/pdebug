<?php
/*================================================================================
	pPHPide - configuration options
	----------------------------------------------------------------------------
	[ pPHPide configuration vars ]

	Some configuration options are for IDE protocol handling support for seamless
	debugging, @see http://pospi.spadgos.com/projects/pPHPide

	Version History:
		3.0 -	complete code rewrite
				drastic performance improvements, precomputed string substitution etc
				oop design methodology
				array based debug stack / minimal string-based operations
				added protocol handling support for IDE integration
				created shared behaviour class, for path portability
				created & integrated grep find class & example pages
				unified variable type checking / hilighting
				moved to external config file and more stable codebase
				simplified integration - single include & abstracted configuration
				drastically reduced function recursion
				benchmark data can now be used externally
				debugger theme support

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
			// stack colouring & nesting
			// stack indent mode
			// error handler: variable name detection?
			// verify headers for all publicly accessible functions
			// added array key type, string info,
			// benchmarking accuracy improvements
			// ****indentation****
			// go back and link to recursed member with arrays!
			// IE6 colouring css
			// correct tabstopping for resource debugging
			// possibly reset resource pointers before exiting mysql result dump
			// possibly skip multiple result iteration in mysql result resource dump

	$_PDEBUG_OPTIONS = array(

		// General configuration / features
			'application_root' 					=> '',		// override your web application's root path if necessary (defaults to $_SERVER['DOCUMENT_ROOT'])
			'use_debugger' 						=> true,	// enable debugger with variable folding, hilighting, stack trace and other fanciness
			'auto_stack_trace'					=> true,	// enable stack trace in debugger & error handler
			'use_error_handler' 				=> true,	// enable error handler with stack trace and other fanciness
			'debug_start_collapsed'				=> false,	// start debugger variable nodes collapsed in HTML view (useful to hide long datasets)
			'use_find'							=> false,	// enable find class usage

		// File linkage / IDE protocol handling support
			'server_path_search'				=> 'd:\\!work\\websites\\hive\\',	// replace this path from serverside search results...
			'server_path_replace'				=> 'd:\\!work\\websites\\hive\\',	// ...with this path for display / linkage
			'server_unix'						=> false,							// true if server paths are unix (with /'s)
			'client_unix'						=> false,							// true if client paths are unix (with /'s)
			'translate_string_paths_in_html'	=> true,							// strings like "require('C:\web\myproject\file.inc.php');" gets paths replaced with links / tooltips automatically

		// Debugger options
			'html_theme'						=> 'pPHPide',		// choose a theme! (see below, or make your own)
			'plaintext_theme'					=> 'pPHPide',
			'adjust_benchmarker_for_debugger'	=> true,			// if true, subtract the time taken to execute debug() calls from benchmarking statistics

		// Find class (and script) default options (should be made configurable via page inputs, of course!)


		// Security related / Find class readonly options
		//	'restrict_search_basepath'			=> true,		// restrict searches to only allow searching inside $_PDEBUG_OPTIONS['application_root']
		//	'restrict_search_to'				=> 'hive/',		// restrict search to subfolder of $_PDEBUG_OPTIONS['application_root'] (if restrict_search_basepath), or absolute filesystem path

		// Wierdness / troubleshooting
			'force_html_mode' 					=> null,	// only use this if you are having trouble with some wierd server configuration or AJAX api that doesn't autodetect through the built-in checks. null = auto, false = off, true = on
			'enable_debug_function_wrappers'	=> true,	// disable to only call debugger functions directly - PDebug::dump(array($var1, $var2, ...)); etc as opposed to dump(). Only use this if you have conflicting function names in the global namespace, and in that case you should probably just rename the pPHPide functions...


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
/*			'output_path_format'				=> '<a href="pphpide://%p%l" title="%p%l">%f%l</a>',
			'output_path_format_plaintext'		=> '%p%l',
			'output_line_format'				=> '/%l',					// substituted into output_path_format at %l
			'output_line_format_plaintext'		=> '/%l',					// substituted into output_path_format_plaintext at %l
*/
			// Ultraedit 14+, UEStudio 6.5+
			'output_path_format'				=> '<a href="pphpide://%p%l" title="%p%l">%f%l</a>',
			'output_path_format_plaintext'		=> '%p%l',
			'output_line_format'				=> '(%l)',					// substituted into output_path_format at %l
			'output_line_format_plaintext'		=> '(%l)',					// substituted into output_path_format_plaintext at %l
/*
			// Ultraedit 14+, UEStudio 6.5+ -- Long path display
			'output_path_format'				=> '<a href="pphpide://%p%l">%p%l</a>',
			'output_path_format_plaintext'		=> '%p%l',
			'output_line_format'				=> '(%l)',					// substituted into output_path_format at %l
			'output_line_format_plaintext'		=> '(%l)',					// substituted into output_path_format_plaintext at %l

			// IDEs not supporting line delimiters (last resort)
			'output_path_format'				=> '<a href="pphpide://%p">%p</a>',
			'output_path_format_plaintext'		=> '%p',
			'output_line_format'				=> '',
			'output_line_format_plaintext'		=> '',
*/


	//===================================================================================================================
	//===================================================================================================================


		//
		// debugger output appearance (both HTML and plaintext) to modify as you wish
		// :NOTE: if you define your css / js inline, you avoid dependency troubles &
		//        minimise system impact upon integration.
		//

		'DEBUGGER_THEMES' => array(

			// <html themes>

			'html' => array(

				// <pPHPide default theme>

				'pPHPide' => array(
					'COMMON_HEADER'	=> '<style type="text/css">'

									// general debugger layout
										. '	.PDebug { font-family: monospace; background: #F0F6FF; margin: 0; padding: 3px; border-radius: 5px; -moz-border-radius: 5px; -webkit-border-radius: 5px; border: 2px solid #E18E03; margin: 1px; }'
										. '	.PDebug li { list-style: none; }'
										. ' .PDebug ul { padding: 0; margin-left: 2em; }'
										. ' .PDebug .info { font-weight: bold; color: #E18E03; }'
										. ' .PDebug .info ol.vars li { list-style: decimal-leading-zero; }'	// these are only used for variable counter display
										. ' .PDebug .info ol.vars li * { font-weight: normal; }'
										. ' .PDebug .info ol.vars li li { list-style: none; }'
										. ' .PDebug .alt { background: #F0F0FF; }'							// alternate table row colouring, etc

									// stack trace styles
										. ' .PDebug .stack li ul, .PDebug .stack li ul li { margin: 0; display: inline; }'

									// file linkage
										. ' .PDebug a { text-decoration: none; border: 0; color: #0086CE; }'
										. '	.PDebug a:active, .PDebug a:hover { border-style: solid; border-width: 0 0 1px 0; border-color: #0086CE; }'

									// I realise this next lot kinda messes up my clean CSS classing, but
									// it's kinda necessary for nested highlighting to work in the way I intended...

									// variable type colors, to distinguish node & panel
										. ' .PDebugarray>span { 	cursor: pointer; padding: 1px; color: #363; border: 1px solid #7D9E69; position: relative; z-index: 2; top: 0px; border-radius: 3px; -moz-border-radius: 3px; -webkit-border-radius: 3px; background: #F0FFF6; }'
										. ' .PDebugarray>span>ul {	padding: 1px; color: #363; border: 1px solid #7D9E69; position: relative; z-index: 1; top: -1px; border-radius: 3px; -moz-border-radius: 3px; -webkit-border-radius: 3px; background: #F0FFF6; }'
										. ' .PDebugobject>span {	cursor: pointer; padding: 1px; color: #33D; border: 1px solid #697D9E; position: relative; z-index: 2; top: 0px; border-radius: 3px; -moz-border-radius: 3px; -webkit-border-radius: 3px; background: #F4F6FF; }'
										. ' .PDebugarray>span:hover { background: #D0DFD6; }'
										. ' .PDebugobject>span:hover{ background: #D4D6DF; }'
										. ' .PDebugobject>span>ul{	padding: 1px; color: #33D; border: 1px solid #697D9E; position: relative; z-index: 1; top: -1px; border-radius: 3px; -moz-border-radius: 3px; -webkit-border-radius: 3px; background: #F4F6FF; }'
										. ' .PDebugobject>span>ul li.joiner { margin-left: 1em; }'
										. ' .PDebugobject, .PDebugarray { display: inline-block; margin: 0 3px 0 0; padding: 0 5px 1em 0; }'
										. ' .PDebugresource {		border-color: #FF7F00; color: #FF7F00; }'
										. ' .PDebugresource>td.resource span.resource { border-color: #FF7F00; color: #FF7F00; }'
										. ' .PDebugboolean span { 	border-color: #00F; color: #00F; }'
										. ' .PDebugnull span {		border-color: #555; color: #555; }'
										. ' .PDebugstring span {	border-color: #888; color: #888; }'
										. ' .PDebuginteger span {	border-color: #F00; color: #F00; }'
										. ' .PDebugfloat span {		border-color: #900; color: #900; }'
										. ' .PDebugunknown span {	border-color: #999; color: #999; }'

									// type-specific styles
										. ' .PDebugarray ul li, .PDebugobject ul li       { list-style: none; }'
										. ' .PDebugarray ul>li>li, .PDebugobject ul>li>li { display:inline; position: relative; left: -2em; }'							// reverse indent array elements
										. ' .PDebugarray ul>li>li>span, .PDebugobject ul>li>li>span { position: relative; left: 1em; text-indent: -3em !important; }'	// add indentation for multiline strings on wrap
										. ' .PDebugarray ul>li>ul, .PDebugobject ul li>ul { margin-left: 1em; }'														// increase indentation of joiner string as well
										. ' .PDebugarray ul>li>ul>li, .PDebugobject ul>li>ul>li { display: inline; }'													// move joiner arrows back into position
										. ' .PDebugobject ul li ul span.member 		{ color: #344F58; }'
										. ' .PDebugobject ul li ul span.private		{ color: #E00; }'
										. ' .PDebugobject ul li ul span.public		{ color: #090; }'
										. ' .PDebugobject ul li ul span.protected	{ color: #909; }'
										. ' .PDebugresource td { color: #447; background: #EBC072; padding: 3px; }'
										. ' .PDebugresource td.alt { background: #E7D8B4; }'
										. ' .PDebugresource table { margin: 0 5%; border: 1px solid #EBC072; border-radius: 3px; -moz-border-radius: 3px; -webkit-border-radius: 3px; }'

									// error string format
										. '	.PDebug .error { margin: 0 0 0 -1em; color: #F00; font-weight: bold; }'
										. ' .PDebug .errorText { color: #600; font-style: italic; display: block; }'

									// common debugger js
										. "</style>\n"
										. '<script type="text/javascript">'
										. '	var PDebug = {'
										. '		toggleNode: function(node) {'
										. '			node.nextSibling.style.display = (node.nextSibling.style.display == "" ? "none" : "");'
										. '		}'
										. '	};'
										. "</script>\n",

					// Generic wildcards:
					// 	%s =	subitem - nests another group of items inside this one
					//			used for array pairs, object members, function arguments, table lines etc

					// Debugger wildcards:
					//	%t =	variable type
					// 	%v =	simple variable value (using VARIABLE_OUTPUT_FORMAT) / array value	/ object member
					// 	%k =	array key / object member name
					//	%i =	"info"... object class / array count / resource type / counter etc

					'VARIABLE_OUTPUT_FORMAT' => "\n" . '<li class="PDebug%t" title="%t"><span>%v</span></li>' . "\n",

					'INDENT_STRING' 		=> "    ",							// :WARNING: this should usually be spaces, so that plaintext etc can format text more consistently

					'HEADER_BLOCK' 			=> '<ul class="PDebug">' . "\n",

					'VARIABLES_HEADER'		=>	'<li class="info">Information for %i vars:<ol class="vars">' . "\n",	// :NOTE: this header & footer are only used when dump()ing multiple variables
					'VARIABLES_JOINER'		=>	'',									// don't need one in html, let the <ol> take care of it

					'ARRAY_FORMAT'			=>	'<li class="PDebugarray" title="array"><span>Array (%i elements): [<ul>%s</ul>&nbsp;] &nbsp;</span></li><br clear="both" />' . "\n",
						'ARRAY_PAIR'		=>		'<li><ul>%k<li class="joiner"> &#061;&gt; </li>%v</ul></li>',
						'ARRAY_JOINER'		=>		"\n",

					'OBJECT_FORMAT'			=>	'<li class="PDebugobject" title="object"><span>%i Object: {<ul>%s</ul>&nbsp;} &nbsp;</span></li><br clear="both" />' . "\n",
						'OBJECT_MEMBER'		=>		'<li><ul><li class="member">%k:<span class="%i">%i</span></li><li class="joiner">:&#061</li>%v</ul></li>',
						'OBJECT_JOINER'		=>		"\n",

					'GENERIC_FORMAT'		=>	'<li class="PDebug%t" title="%t"> [%i] <table cellpadding="0" cellspacing="1">%s</table></li>' . "\n",
						'GENERIC_HEADER'	=>		'<thead class="%t">%s</thead>' . "\n",
						'GENERIC_BODY'		=>		'<tbody class="%t">%s</tbody>' . "\n",
						'GENERIC_FOOTER'	=>		'<tfoot class="%t">%s</tfoot>' . "\n",
						//	%i in this case is intended as a 'spin' variable for table row styling etc
						'GENERIC_LINE'		=>		'<tr class="%t">%i %s</tr>' . "\n",
						'GENERIC_CELL'		=>		'<td class="%t">%v</td>' . "\n",							// this is used for generic resources that don't require special formatting
						'GENERIC_LINE_JOINER'	=>	"",
						'GENERIC_CELL_JOINER'	=>	"",
					'GENERIC_HEADER_CHARACTER' => '',
					'GENERIC_BORDER_CHARACTER' => '',

					'MYSQL_DB_CELL'			=>		'<td class="PDebug%t">%i</td><td>%v</td>',	// mysql db info cell (has extra information to format)
						// extra parameter %y = field value datatype
					'MYSQL_RESULT_CELL'		=>		'<td class="PDebug%t">%i</td><td class="PDebug%y" title="%y">%v</td>',	// mysql res

					'VARIABLES_FOOTER'		=>	'</ol></li>' . "\n",													// :NOTE: this header & footer are only used when dump()ing multiple variables

					'FOOTER_BLOCK' 			=> '</ul>' . "\n",

					// Benchmarker wildcards:
					//	%i	=	benchmark tag
					// 	%p	=	file path
					//	%t	=	current execution time (s)
					//	%m	=	current mem usage (KB)
					//	%dt	=	time diff since last call (s)
					//	%dm	=	memory diff since last call (KB)
					'BENCH_FORMAT'			=>	'<li>%i : %p @ %ts [%dt] %mK [%dm]</li>' . "\n",

					// Error handler wildcards:
					//	%e =	error type
					//	%m =	error message
					// 	%p =	file path
					'ERROR_FORMAT' 			=> '<li class="error">%e %p : %m</li>' . "\n",

					// Stack wildcards:
					//	%c = 	class name
					//	%t = 	call type
					// 	%f =	function name
					// 	%a =	function argument
					// 	%p =	file path
					'STACK_FORMAT'			=>	'<li class="stack"><span class="info">Stack:</span> <ul>%s</ul></li>' . "\n",
						'STACK_LINE'		=> 		'<li>%p : %c%t%f(<ul>%s</ul>)</li>' . "\n",
						'STACK_JOINER'		=> 		',',
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

			'plain' => array(

				// <pPHPide default theme>

				'pPHPide' => array(
					'COMMON_HEADER'			=> "",

					'VARIABLE_OUTPUT_FORMAT' => "{%t} %v",

					'INDENT_STRING' 		=> "    ",		// :WARNING: this should usually be spaces, so that plaintext etc can format text more consistently

					'HEADER_BLOCK' 			=> "",

					'VARIABLES_HEADER'		=>	"Information for %i vars:\n",
					'VARIABLES_JOINER'		=>	"#%i\n",

					'ARRAY_FORMAT'			=>	"Array (%i elements) [\n%s\n]\n",
						'ARRAY_PAIR'		=>		"%k => %v",
						'ARRAY_JOINER'		=>		"\n",

					'OBJECT_FORMAT'			=>	"Object (%i) {\n%s\n}\n",
						'OBJECT_MEMBER'		=>		"%k := %v",
						'OBJECT_JOINER'		=>		"\n",

					'GENERIC_FORMAT'		=>	"%v\n%s\n",
						'GENERIC_HEADER'	=>		"%s\n\n",
						'GENERIC_BODY'		=>		"%s\n\n",
						'GENERIC_FOOTER'	=>		"%s\n",
						'GENERIC_LINE'		=>		"|%s|",
						'GENERIC_CELL'		=>		" %v ",
						'GENERIC_LINE_JOINER'	=>	"\n",
						'GENERIC_CELL_JOINER'	=>	"|",
					'GENERIC_HEADER_CHARACTER' => '-',
					'GENERIC_BORDER_CHARACTER' => '=',

					'MYSQL_DB_CELL'			=>	"%i %v\n",

					'VARIABLES_FOOTER'		=>	"",

					'FOOTER_BLOCK'			=> "\n",

					'BENCH_FORMAT'			=>	"%i : %p @ %ts [%dts] %mK [%dmK]\n",

					'ERROR_FORMAT' 			=> "%e %p : %m\n",

					'STACK_FORMAT'			=>	"STACK:\n%s\n",
						'STACK_LINE'		=> 		"%p : %f(%s)\n",
						'STACK_JOINER'		=> 		", ",
				),

				// </pPHPide default theme>
				//================================================================================================================
				// <Yours here?...>

			),

			// </plaintext themes>

		),


	);

?>
