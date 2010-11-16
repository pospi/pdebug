<?php
/*================================================================================
	pdebug - resource type include
	----------------------------------------------------------------------------
	Provides debug behaviour for whatever resource type this is....
	----------------------------------------------------------------------------
	Copyright (c) 2008 Sam Pospischil <pospi@spadgos.com>
  ===============================================================================*/

	$spin = 0;
	
	$options = stream_context_get_options($var);
	$params = stream_context_get_params($var);
	unset($params['options']);
	
	$optionText = PDebug::getDebugFor($options, false, $ref_chain);
	$paramText = PDebug::getDebugFor($params, false, $ref_chain);
	
	$optsW = PProtocolHandler::String_getDisplayWidth($optionText);
	$paramsW = PProtocolHandler::String_getDisplayWidth($paramText);
	
	$keyColumnW = 10;		// strlen('parameters')
	
	$column_lengths = array($keyColumnW, max($optsW, $paramsW));
	
	$leftInsert = str_repeat(' ', $keyColumnW) . PDebug::$GENERIC_CELL_JOINER;
	$resource_table_rows[] = array('options', 		PProtocolHandler::String_padForGenericLine($optionText, PDebug::$CURRENT_INDENT_STRING, 0, 0, $leftInsert));
	$resource_table_rows[] = array('parameters',	PProtocolHandler::String_padForGenericLine($paramText,  PDebug::$CURRENT_INDENT_STRING, 0, 0, $leftInsert));

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

		$divider_row = str_replace(array(PDebug::WC_INDENT, PDebug::WC_TYPE, PDebug::WC_INFO, PDebug::WC_SUBITEM), array(PDebug::$CURRENT_INDENT_STRING, '', '', str_repeat(PDebug::$GENERIC_BORDER_CHARACTER, $full_width)), PDebug::$GENERIC_LINE);

		$resource_footer_rows[] = $divider_row;
		array_unshift($resource_header_rows, $divider_row);
	}

?>
