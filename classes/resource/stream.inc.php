<?php
/*================================================================================
	pPHPide - resource type include
	----------------------------------------------------------------------------
	Provides debug behaviour for whatever resource type this is....
	----------------------------------------------------------------------------
	Copyright (c) 2008 Sam Pospischil <pospi@spadgos.com>
  ===============================================================================*/

	$spin = 0;
	$column_lengths = array();

	// retrieve stream metadata first
	$stream_meta = stream_get_meta_data($var);
	foreach ($stream_meta as $key => $value) {

		// store max column lengths
		if (!isset($column_lengths[0]) || $column_lengths[0] < strlen($key)) {
			$column_lengths[0] = strlen($key);
		}
		// check based on length of final debugged var output
		$meta_debug = PDebug::getDebugFor($value);
		$longest_debug_line = 0;
		$meta_debug_lines = PProtocolHandler::getStringLines($meta_debug);
		foreach ($meta_debug_lines as $line) {
			if (PProtocolHandler::$OUTPUT_HTML_AS_PLAIN) {
				// if outputting plaintext into HTML, file links will still work so we need to get the display width
				$line = strip_tags($line);
			}
			if ($longest_debug_line < strlen($line)) {
				$longest_debug_line = strlen($line);
			}
		}

		if (!isset($column_lengths[1]) || $column_lengths[1] < $longest_debug_line) {
			$column_lengths[1] = $longest_debug_line;
		}

		$row = array($key, $meta_debug);
		$resource_table_rows[] = $row;
	}

	// other stream info to attempt getting
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

		// store max column lengths
		if (!isset($column_lengths[0]) || $column_lengths[0] < strlen($key)) {
			$column_lengths[0] = strlen($key);
		}
		if (!isset($column_lengths[1]) || $column_lengths[1] < strlen($value)) {
			$column_lengths[1] = strlen($resource_info);
		}

		$row = array($key, $resource_info);
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
