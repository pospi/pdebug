<?php
/*================================================================================
	pdebug - initialisation file
	----------------------------------------------------------------------------
	Include IDE package files. No namespace pollution whatsoever, aside from
	PDebug class and function helpers (if enabled).
	----------------------------------------------------------------------------
	Copyright (c) 2008 Sam Pospischil <pospi@spadgos.com>
  ===============================================================================*/

	// store the start time / mem of this script without initialising any classes,
	// so that we can time the startup overhead of the package.
	$pdebug_init_start_time = microtime(true);
	$pdebug_init_start_mem  = memory_get_usage();

	include('pdebug.conf.php');

	include('classes/pdebug_shared.class.php');
	if ($_PDEBUG_OPTIONS['use_debugger']) {
		include('classes/pdebug_debug.class.php');
	}

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
			register_shutdown_function(array(PDebug, '__printInitStats'));
		}

	} else {
		unset($_PDEBUG_OPTIONS);		// unset this separately so we don't have to make a temporary variable for $show_startup_stats
	}
?>
