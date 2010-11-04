<?php
/*================================================================================
	pPHPide - resource type include
	----------------------------------------------------------------------------
	Provides debug behaviour for whatever resource type this is....
	----------------------------------------------------------------------------
	Copyright (c) 2008 Sam Pospischil <pospi@spadgos.com>
  ===============================================================================*/

	$database_info = array(
		'Host' =>			'mysql_get_host_info',
		'Charset' =>	 	'mysql_client_encoding',
		'Client' =>	 		'mysql_get_client_info',
		'Protocol ver' =>	'mysql_get_proto_info',
		'Server ver' =>		'mysql_get_server_info',
	);

	$spin = 0;
	foreach ($database_info as $key => $function) {
		$cells = array();
		if (!$db_info = @$function($var)) {
			 $db_info = 'unknown';
		}
		$cells[] = str_replace(array('%t', '%v'), array('resource', $key), PDebug::$GENERIC_CELL);
		$cells[] = str_replace(array('%t', '%v'), array('', $db_info), PDebug::$GENERIC_CELL);
		$resource_table_rows[] = str_replace(array('%t', '%i', '%s'), array((++$spin % 2 ? '' : ' alt'), '', implode(PDebug::$GENERIC_LINE_JOINER, $cells)), PDebug::$GENERIC_LINE);
	}

?>
