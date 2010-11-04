<?php
/*================================================================================
	pPHPide - resource type include
	----------------------------------------------------------------------------
	Provides debug behaviour for whatever resource type this is....
	----------------------------------------------------------------------------
	Copyright (c) 2008 Sam Pospischil <pospi@spadgos.com>
  ===============================================================================*/

	$spin = 0;

	$stream_meta = stream_get_meta_data($var);
	foreach ($stream_meta as $key => $value) {
		$cells = array();
		$cells[] = str_replace(array('%t', '%v'), array('resource', $key), PDebug::$GENERIC_CELL);
		$cells[] = str_replace(array('%t', '%v'), array('', PDebug::getDebugFor($value)), PDebug::$GENERIC_CELL);
		$resource_table_rows[] = str_replace(array('%t', '%i', '%s'), array((++$spin % 2 ? '' : ' alt'), '', implode(PDebug::$GENERIC_LINE_JOINER, $cells)), PDebug::$GENERIC_LINE);
	}

	$stream_info = array(
		'Options' =>		'stream_context_get_options',
		'Local socket name' =>	'stream_socket_get_name',
		'Remote socket name' =>	'stream_socket_get_name',
	);
	foreach ($stream_info as $key => $function) {
		$cells = array();
		$resource_info = @$function($var);
		if ($resource_info === false) {
			$resource_info = 'unknown';
		} else if (!$resource_info && is_array($resource_info)) {
			$resource_info = 'empty';
		}
		$cells[] = str_replace(array('%t', '%v'), array('resource', $key), PDebug::$GENERIC_CELL);
		$cells[] = str_replace(array('%t', '%v'), array('', $resource_info), PDebug::$GENERIC_CELL);
		$resource_table_rows[] = str_replace(array('%t', '%i', '%s'), array((++$spin % 2 ? '' : ' alt'), '', implode(PDebug::$GENERIC_LINE_JOINER, $cells)), PDebug::$GENERIC_LINE);
	}

?>
