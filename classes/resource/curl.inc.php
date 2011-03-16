<?php
/*================================================================================
	pdebug - resource type include
	----------------------------------------------------------------------------
	Provides debug behaviour for whatever resource type this is....
	----------------------------------------------------------------------------
	Copyright (c) 2008 Sam Pospischil <pospi@spadgos.com>
  ===============================================================================*/

	$spin = 0;
	$column_lengths = array();
	
	// grab all relevant cURL resource information and parse into table rows
	$info = curl_getinfo($var);
	$info['error'] = curl_error($var);
	$info['error_code'] = curl_errno($var);
	foreach ($info as $key => $value) {
		// check based on length of final debugged var output
		$info_debug = PDebug::getDebugFor($value, false, $ref_chain);

		$value_width = PProtocolHandler::String_getDisplayWidth($info_debug);
		$key_width = PProtocolHandler::String_getDisplayWidth($key);

		// store max column lengths
		if (!isset($column_lengths[0]) || $column_lengths[0] < $key_width) {
			$column_lengths[0] = $key_width;
		}
		if (!isset($column_lengths[1]) || $column_lengths[1] < $value_width) {
			$column_lengths[1] = $value_width;
		}

		$row = array($key, $info_debug);
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
