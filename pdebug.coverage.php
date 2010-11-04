<?php
/*================================================================================
	pdebug - code coverage display
	----------------------------------------------------------------------------
	This file can be included at the start of any scripts you wish to test for
	code coverage. It will output a display at the end of the request which lets
	you know what's been parsed.

	REQUIRES xDebug TO BE INSTALLED!
	----------------------------------------------------------------------------
	Copyright (c) 2010 Sam Pospischil <pospi@spadgos.com>
  ===============================================================================*/

require_once('pdebug.php');

xdebug_start_code_coverage();

function __pdebug_show_coverage() {
	$out = array();
	$stats = xdebug_get_code_coverage();

	foreach ($stats as $file => $lines) {
		if (!preg_match('/^' . addcslashes(dirname(__FILE__), '/\\') . '/', $file) && strpos($file, 'eval()\'d code') === false) {
			$fileLines = file($file);
			$out[$file] = array();

			foreach ($fileLines as $num => $line) {
				$out[$file][] = "<li" . (isset($lines[$num+1]) ? ' class="covered"' : '') . ">" . htmlentities($line) .
								(isset($lines[$num+1]) ? "<span class=\"c\">(" . $lines[$num+1] . ")</span>" : "") .
								"</li>";
			}
		}
	}

	list($header_extra, $footer_extra) = PDebug::verifyHeaderIncludes(PDebug::$HEADER_BLOCK, PDebug::$FOOTER_BLOCK);

	echo "\n\n$header_extra<div class=\"coverage\"><li onclick=\"PDebug.c(this);\"><span class=\"resource\"><span><nobr>Code coverage:</nobr></span></span></li>\n";
	foreach ($out as $header => $data) {
		echo "<h4 onclick=\"PDebug.toggle(this.nextSibling);\">" . PProtocolHandler::String_translatePathsFor($header, 1) . " <span>(hit " . count($stats[$header]) . " lines " . array_sum($stats[$header]) . " times)</span></h4>";
		echo "<ol style=\"display: none;\">";
		echo implode("\n", $data);
		echo "</ol>";
	}
	echo "</div>$footer_extra";
}

register_shutdown_function('__pdebug_show_coverage');

?>
