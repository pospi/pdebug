<?php
/*================================================================================
	pdebug - resource type include
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
	$column_lengths = array();
	foreach ($database_info as $key => $function) {
		if (!$db_info = @$function($var)) {
			 $db_info = 'unknown';
		}

		// store max column lengths
		if (!isset($column_lengths[0]) || $column_lengths[0] < strlen($key)) {
			$column_lengths[0] = strlen($key);
		}
		if (!isset($column_lengths[1]) || $column_lengths[1] < strlen($db_info)) {
			$column_lengths[1] = strlen($db_info);
		}

		$row = array($key, $db_info);
		$resource_table_rows[] = $row;
	}

	// perform replacements last so that we have precomputed field lengths
	foreach ($resource_table_rows as $idx => $row) {
		foreach ($row as $ridx => $cell) {
			$row[$ridx] = str_replace(array(PDebug::WC_INDENT, PDebug::WC_TYPE, PDebug::WC_VAR), array(PDebug::$CURRENT_INDENT_STRING, 'resource',	str_pad($cell, $column_lengths[$ridx])), 	PDebug::$GENERIC_CELL);
		}
		$resource_table_rows[$idx] = str_replace(array(PDebug::WC_INDENT, PDebug::WC_TYPE, PDebug::WC_INFO, PDebug::WC_SUBITEM), array(PDebug::$CURRENT_INDENT_STRING, (PProtocolHandler::isOutputtingHtml() && ++$spin % 2 ? ' alt' : ''), '', implode(PDebug::$GENERIC_CELL_JOINER, $row)), PDebug::$GENERIC_LINE);
	}

	// resource table: plaintext formatting table separators
	if (PDebug::$GENERIC_BORDER_CHARACTER) {
		// adjust full width for column padding and output length etc
		$full_width = array_sum($column_lengths) - $line_length_offset + 1 + ( count($column_lengths) * strlen(PDebug::$GENERIC_CELL_JOINER) );

		$divider_row = str_replace(array(PDebug::WC_INDENT, PDebug::WC_TYPE, PDebug::WC_INFO, PDebug::WC_SUBITEM), array(PDebug::$CURRENT_INDENT_STRING, '', '', str_repeat(PDebug::$GENERIC_BORDER_CHARACTER, $full_width + $total_line_padding)), PDebug::$GENERIC_LINE);

		$resource_footer_rows[] = $divider_row;
		array_unshift($resource_header_rows, $divider_row);
	}
?>
