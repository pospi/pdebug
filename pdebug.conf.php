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
			'shallow_stack_trace'				=> true,	// when true, only prints object names in stack trace. Otherwise, full debug is output (this only ever happens in HTML mode)
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

			// output flags. These control how the debugger sends its output.
			'print_output'						=> true,			// outputs within the current response
			'email_output'						=> false,			// emails all output at the end of the script
			'error_output_callback'				=> null,			// if print_output is FALSE, this can be used to set a function for outputting 'safe' error messages to the end-user. It is passed the error code and message.

			// email error reporting options.
			// These are sent using the PHP's mail() function when the script terminates.
			// Feel free to edit PProtocolHandler::sendMail() to use another delivery system.
			'email_errors_to'					=> '',				// this is a comma-separated list of email addresses to send to (as in RFC 2822)
			'email_from_address'				=> 'debugger@example.com',
			'email_envelope'					=> '',				// this will set your email envelope sender for servers which require a particular value for this. When blank, none is set.

			// controls which error levels are interpreted by the debugger as non-critical
			'warning_types'						=> E_USER_WARNING | E_USER_NOTICE | E_WARNING | E_NOTICE | E_STRICT | E_CORE_WARNING | E_COMPILE_WARNING | 8192 | 16384,
			// sets which error levels should be emailed when email_output is set
			'email_types'						=> E_ALL | E_STRICT,
			// sets which error levels should be completely ignored by the debugger.
			'ignore_types'						=> E_NOTICE | E_STRICT,

			// an array of filesystem folder names to ignore any error output from. Use to suppress log spam from older code libraries.
			'ignore_paths'						=> array(),

			// Set email subjects. %h = hostname, %e = error count, %w = warning count
			'email_subject_output'				=> 'pDebug - %h',							// no errors or warnings present, manual debug output only
			'email_subject_warnings'			=> 'pDebug [%w warnings] - %h',				// warnings (but no errors) present
			'email_subject_errors'				=> 'pDebug [%e errors, %w warnings] - %h',	// errors present

			// only use this if you are having trouble with some wierd server configuration or AJAX api that doesn't autodetect through the built-in checks.
			//										null = auto, or any of 'html', 'text' or 'json'
			'force_output_mode' 				=> null,

			'debug_start_collapsed'				=> false,			// start debugger variable nodes collapsed in HTML view (useful to hide long datasets)
			'adjust_benchmarker_for_debugger'	=> true,			// if true, subtract the time taken to execute debug() calls from benchmarking statistics
			'show_internal_statistics'			=> true,			// if true, show time & memory usage for each debugger function call

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
			'flush_on_output'					=> true,				// by default, any output from PDebug will flush the output buffer. You can disable this here if you use some form of output buffering in your projects that conflicts.


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

/*			// Komodo Edit
			// :NOTE: because this format contains spaces, you will need to replace %20's in the URI using sed.
			// If on windows with your IDE in linux, put the following in a batch file and send your commands to it like: file.bat "%1" (note quotes)
			//		C:\path\to\plink.exe -load local -i C:\path\to\privkey.ppk -batch komodo `echo %1 ^| sed -e 's/%%20/ /g'`
			'output_path_format'				=> '<a href="pdebug:%p#%l" title="%p(%l)">%f(%l)</a>',
			'output_path_format_plaintext'		=> '%p%l',
			'output_line_format'				=> '%l',
			'output_line_format_plaintext'		=> '%l',
*/
			// Ultraedit 13-, UEStudio 6.0-, CEdit, ZendIDE etc
			'output_path_format'				=> '<a href="pdebug:%p%l" title="%p%l">%f%l</a>',
			'output_path_format_plaintext'		=> '%p%l',
			'output_line_format'				=> '/%l',					// substituted into output_path_format at %l
			'output_line_format_plaintext'		=> '/%l',					// substituted into output_path_format_plaintext at %l
/*
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

			// IDEs not supporting line delimiters (last resort)
			'output_path_format'				=> '<a href="pdebug:%p" title="%p%l">%f%l</a>',
			'output_path_format_plaintext'		=> '%p%l',
			'output_line_format'				=> ':%l',
			'output_line_format_plaintext'		=> ':%l',
*/
	//===================================================================================================================
	//===================================================================================================================

		'DEBUGGER_THEMES' => array()		// will contain 'html', 'text' and 'json'
	);

?>
