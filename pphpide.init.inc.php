<?php
/*================================================================================
	pPHPide - initialisation file
	----------------------------------------------------------------------------
	Include IDE package files. No namespace pollution whatsoever, aside from
	PDebug and PFind classes, and function helpers (if enabled).
	----------------------------------------------------------------------------
	Copyright (c) 2008 Sam Pospischil <pospi@spadgos.com>
  ===============================================================================*/

	include('pphpide.conf.php');

	include('classes/pphpide_shared.class.php');
	if ($_PDEBUG_OPTIONS['use_debugger']) {
		include('classes/pphpide_debug.class.php');
	}
	if ($_PDEBUG_OPTIONS['use_find']) {
		include('classes/pphpide_find.class.php');
	}
	unset($_PDEBUG_OPTIONS);
?>
