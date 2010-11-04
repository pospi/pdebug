<?php
/*================================================================================
	pdebug - configuration options
	----------------------------------------------------------------------------
	[ pdebug configuration vars ]

	Version History:
		3.0 (Final) - First public release

		3.0.05a - Internal alpha test phase
			:--FLUXATION HAPPENS--:

		2.0 -	upgraded for PHP5
				proper object iteration
				With thanks / help from:
					Jason Friedland <jfriedland@vision6.com.au>
					Jamie Curnow	<jc@jc21.com>
					Ben Kuskopf		<benkuskopf@hotmail.com>

		1.0 -	basic functionality
				string-based print_r() behaviour
				With thanks / help from:
					Nick Fisher 	<fisher@spadgos.com>

	----------------------------------------------------------------------------
	Copyright (c) 2008 Sam Pospischil <pospi@spadgos.com>
  ===============================================================================*/

	$_PDEBUG_OPTIONS = array(

		// General configuration / features
			'application_root' 					=> '/var/www/html/',		// override your web application's root path if necessary (defaults to $_SERVER['DOCUMENT_ROOT'])
			'use_debugger' 						=> true,	// enable debugger with variable folding, hilighting, stack trace and other fanciness
			'auto_stack_trace'					=> true,	// enable stack trace in debugger & error handler
			'use_error_handler' 				=> true,	// enable error handler with stack trace and other fanciness
			'show_startup_stats'				=> false,	// enable displaying the memory & procecessing overheads of initialising pdebug

		// File linkage / IDE protocol handling support
			'server_path_search'				=> '',	// replace this path from serverside search results...
			'server_path_replace'				=> '',					// ...with this path for display / linkage
			'server_unix'						=> true,							// true if server paths are unix (with /'s)
			'client_unix'						=> true,							// true if client paths are unix (with /'s)
			'translate_string_paths_in_html'	=> true,							// strings like "require('C:\web\myproject\file.inc.php');" gets paths replaced with links / tooltips automatically

		// Debugger options
			// choose a theme! (see below, or make your own)
			'html_theme'						=> 'pdebug',		// or set 'plaintext' to use plaintext style in a <pre> tag
			'plaintext_theme'					=> 'pdebug',
			'json_theme'						=> 'pdebug',

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
			'stack_color_newest'				=> 0x88FF88,		// most recent call in stack will be shaded this colour
			'stack_color_oldest'				=> 0xFF8888,		// most ancient call in stack will be shaded this colour

		// Wierdness / troubleshooting
			'line_ending_regex'					=> "/(\r\n|\r|\n)/",	// modify this if you have issues - it should catch windows, mac or unix text fine though.
			'enable_debug_function_wrappers'	=> true,				// disable to only call debugger functions directly - PDebug::dump(array($var1, $var2, ...)); etc as opposed to dump(). Only use this if you have conflicting function names in the global namespace, and in that case you should probably just rename the pdebug functions...


	//===================================================================================================================
	//===================================================================================================================


		//
		// Presets for output format for IDE linkage / path output
		// link with pdebug protocol setup for your IDE, @see http://pospi.spadgos.com/projects/pdebug/installation.php
		// %p = full path, %f = filename, %l = line number
		//
		// :NOTE: output_line_format_plaintext is also used in substring path highlighting, for matching line numbers. So
		//		  you should keep it consistent with the HTML equivalent for encapsulated string path detection to work in HTML
		//

			// Ultraedit 13-, UEStudio 6.0-, CEdit, ZendIDE etc
/*			'output_path_format'				=> '<a href="pdebug:%p%l" title="%p%l">%f%l</a>',
			'output_path_format_plaintext'		=> '%p%l',
			'output_line_format'				=> '/%l',					// substituted into output_path_format at %l
			'output_line_format_plaintext'		=> '/%l',					// substituted into output_path_format_plaintext at %l

			// Ultraedit 14+, UEStudio 6.5+
			'output_path_format'				=> '<a href="pdebug:%p%l" title="%p%l">%f%l</a>',
			'output_path_format_plaintext'		=> '%p%l',
			'output_line_format'				=> '(%l)',					// substituted into output_path_format at %l
			'output_line_format_plaintext'		=> '(%l)',					// substituted into output_path_format_plaintext at %l

			// Ultraedit 14+, UEStudio 6.5+ -- Long path display
			'output_path_format'				=> '<a href="pdebug:%p%l">%p%l</a>',
			'output_path_format_plaintext'		=> '%p%l',
			'output_line_format'				=> '(%l)',					// substituted into output_path_format at %l
			'output_line_format_plaintext'		=> '(%l)',					// substituted into output_path_format_plaintext at %l
*/
			// IDEs not supporting line delimiters (last resort)
			'output_path_format'				=> '<a href="pdebug:%p">%p%l</a>',
			'output_path_format_plaintext'		=> '%p',
			'output_line_format'				=> '(%l)',
			'output_line_format_plaintext'		=> '(%l)',

	//===================================================================================================================
	//===================================================================================================================
	
		'DEBUGGER_THEMES' => array()		// will contain 'html', 'text' and 'json'
	);
	
	// load theme files
	$themePath = dirname(__FILE__) . '/themes/';
	$toLoad = array(
		'html' => $themePath . $_PDEBUG_OPTIONS['html_theme'] . '/html.php',
		'text' => $themePath . $_PDEBUG_OPTIONS['plaintext_theme'] . '/text.php',
		'json' => $themePath . $_PDEBUG_OPTIONS['json_theme'] . '/json.php'
	);
	
	foreach ($toLoad as $type => $path) {
		if (!file_exists($path)) {
			die("PDebug error: could not locate specified theme file ($path)");
		}
		include($path);
		// each file contains a variable called $t which is the theme array
		$_PDEBUG_OPTIONS['DEBUGGER_THEMES'][$type] = $t;
		unset($t);
	}
	unset($themePath, $toLoad, $type, $path);

?>
