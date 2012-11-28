<?php
/*================================================================================
	pdebug - memory_get_usage() compatibility layer
	----------------------------------------------------------------------------
	Attempts to retrieve PHP's memory usage in nonstandard ways.
	Requires some configuration.
	----------------------------------------------------------------------------
	Copyright (c) 2008 Sam Pospischil <pospi@spadgos.com>
  ===============================================================================*/

function memory_get_usage() {

	// YOU MUST REMOVE THIS LINE TO USE MEMORY LOGGING ON WINDOWS HOSTS. IT
	// IS DISABLED BY DEFAULT DUE TO UNRELIABILITY OF THE exec() COMMAND ON
	// WINDOWS SERVERS. BASICALLY - IF YOU WANT THIS, IT WILL REQUIRE CONFIGURATION
	return 0;

	// Win XP Pro SP2, Win 2003 Server
	// Will work for Win2000 with pslist.exe - see http://php.net/manual/en/function.memory-get-usage.php#54642 and http://technet.microsoft.com/en-us/sysinternals/bb896682
	if (substr(PHP_OS, 0, 3) == 'WIN') {
		$output = array();

		$pslist = dirname(__FILE__) . '\resources\PsList.exe';
		if (file_exists($pslist)) {
			if (version_compare(PHP_VERSION, '5.3.0') == -1) {
				exec('"' . $pslist . ' ' . getmypid() . '"', $output);
			} else {
				exec($pslist . ' ' . getmypid() , $output);
			}
			return trim(substr($output[8],38,10));
		} else {
			exec('tasklist /FI "PID eq ' . getmypid() . '" /FO LIST', $output);
			return preg_replace('/[\D]/', '', $output[5]) * 1024;
		}
	} else {
	// UNIX / OSX
		$pid = getmypid();
		exec("ps -eo%mem,rss,pid | grep $pid", $output);
		$output = explode("  ", $output[0]);

		return $output[1] * 1024;
	}
}
