<?php
/*================================================================================
	pdebug - initialisation file
	----------------------------------------------------------------------------
	Include IDE package files. No namespace pollution whatsoever, aside from
	PDebug class and function helpers (if enabled).

	Version History:
		3.1 -	add ability to filter out errors based on path
				various bugfixes

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

	// store the start time / mem of this script without initialising any classes,
	// so that we can time the startup overhead of the package. Note that we require
	// a memory usage interrogation function for this to work at all.
	$pdebug_init_start_time = microtime(true);

	if (!function_exists('memory_get_usage')) {
		require(dirname(__FILE__) . '/classes/pdebug_memorycompat.inc.php');
	}
	$pdebug_init_start_mem  = memory_get_usage();

	// Load a config file from various places. This allows you to define your debugger configurations
	// on a per-vhost basis or override them from within specific directories during development.
	if (file_exists($_SERVER['DOCUMENT_ROOT'] . dirname($_SERVER['PHP_SELF']) . '/pdebug.conf.php')) {	// directory of requested file
		include($_SERVER['DOCUMENT_ROOT'] . dirname($_SERVER['PHP_SELF']) . '/pdebug.conf.php');
	} else if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/pdebug.conf.php')) {							// document root
		include($_SERVER['DOCUMENT_ROOT'] . '/pdebug.conf.php');
	} else {																							// default config
		require(dirname(__FILE__) . '/pdebug.conf.php');
	}

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

	// class files
	include('classes/pdebug_shared.class.php');
	if ($_PDEBUG_OPTIONS['use_debugger']) {
		include('classes/pdebug_debug.class.php');
	}

	// show startup stats, if necessary
	if (isset($_PDEBUG_OPTIONS['show_startup_stats']) && $_PDEBUG_OPTIONS['show_startup_stats']) {

		unset($_PDEBUG_OPTIONS);		// unset this separately so we don't have to make a temporary variable for $show_startup_stats

		// apply stat shading
		$time_stuff = PDebug::__shadedTime(microtime(true) - $pdebug_init_start_time);
		$mem_stuff = PDebug::__shadedMem(memory_get_usage() - $pdebug_init_start_mem);

		// initialisation statistics string precalculation
		PDebug::$INITIALISATION_LOG_STRING = str_replace(array('%p', '%dt', '%dm', '%cdt', '%cdm'),
												array(
													(isset($_SERVER['HTTP_HOST']) && isset($_SERVER['PHP_SELF']) ?  // woo! nested ternaries...
														'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF']	// ...I'm trying to avoid declaring unnecessary variables here :)
													:
														(isset($_SERVER['argv']) && isset($_SERVER['argv'][0]) ?
															($_SERVER['argv'][0]{0} == '/' ?	// absolute path
																$_SERVER['argv'][0]
															:
																getcwd() .'/'. $_SERVER['argv'][0]  // relative path, prefix with working directory
															)
														:
															__FILE__
														)
													)
													. '' .
													(isset($_SERVER['PHP_SELF']) ?
														''
													:
														'/' . __LINE__	  // meh stfu its not even clickable
													),
													$time_stuff[0],
													$mem_stuff[0],
													$time_stuff[1],
													$mem_stuff[1],
												),
												PDebug::$STARTUP_STATS_FORMAT);

		// can't forget to clean up these variables
		unset($time_stuff);
		unset($mem_stuff);
		unset($pdebug_init_start_time);
		unset($pdebug_init_start_mem);

		// we defer loading statistics to output at script termination
		if (PDebug::$STARTUP_STATS_FORMAT) {
			register_shutdown_function(array('PDebug', '__printInitStats'));
		}

	} else {
		unset($_PDEBUG_OPTIONS);		// unset this separately so we don't have to make a temporary variable for $show_startup_stats
	}
?>
