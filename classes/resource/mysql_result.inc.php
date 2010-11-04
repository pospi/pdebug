<?php
/*================================================================================
	pPHPide - resource type include
	----------------------------------------------------------------------------
	Provides debug behaviour for whatever resource type this is....
	----------------------------------------------------------------------------
	Copyright (c) 2008 Sam Pospischil <pospi@spadgos.com>
  ===============================================================================*/

	$num_rows = mysql_num_rows($var);
	$num_fields = mysql_num_fields($var);

	// initialise vars
	$columns 	 = array();
	$col_widths  = array();
	$row_heights = array();		// these 3 arent really needed in HTML mode but not much more overhead than IFs everywhere
	$row_count   = 0;
	$tab_spacing = strlen(PDebug::$INDENT_STRING);

	// parse the resultset to determine plaintext formatting, required padding / spacing for display, etc
	if ($num_rows) {

		mysql_data_seek($var, 0);							// reset resource pointer if set

		while ($row = mysql_fetch_assoc($var)) {
			$row_heights[$row_count] = 1;					// set default row display height

			foreach ($row as $field => $value) {

				if (!isset($columns[$field])) {
					$columns[$field] = array();
				}
				if (!isset($col_widths[$field])) {
					$col_widths[$field] = strlen($field);
				}

				// determine number of lines in this row's value
				$lines = is_null($value) ? array(null) : explode("\n", $value);

				// find min line height for this row. this is necessary for pleasant plaintext display.
				$row_heights[$row_count] = max($row_heights[$row_count], count($lines));

				// find the min width the columns need to be
				for ($l = 0; $l < count($lines); $l++) {	// go through each of the lines in this field
					$line = $lines[$l];
					if (is_null($line)) {
						$len = 4;							// length of "null"
					} else {
						$line = strval($line);
						if (strpos($line, TAB) !== false) {
							$out = '';
							for ($i = 0; $i < strlen($line); $i++) {
								if ($line[$i] == TAB) {
									$out .= str_repeat(' ', ($tab_spacing - (strlen($out) % $tab_spacing)));
								} else {
									$out .= $line[$i];
								}
							}
							$lines[$l] = $line = $out;
						}
						$len = strlen($line);
					}
					$col_widths[$field] = max($col_widths[$field], $len);
				}
				$columns[$field][] = $lines;
			}
			$row_count++;
		}

		// adjust full width for column padding and output length etc
		$full_width = array_sum($col_widths) - $line_length_offset + (count($col_widths) * strlen(PDebug::$GENERIC_CELL_JOINER) + 1);

		mysql_data_seek($var, 0);												// reset resource pointer again

		// resource table: info header rows
		$cells = array();
		$cells[] = str_replace(array(PDebug::WC_INDENT, PDebug::WC_TYPE, PDebug::WC_VAR),	array(PDebug::$CURRENT_INDENT_STRING, '', str_pad('Number of rows:   ' . $num_rows, $full_width)), 	PDebug::$GENERIC_CELL);
		if (PProtocolHandler::isOutputtingHtml() && !PProtocolHandler::$OUTPUT_HTML_AS_PLAIN) {
			// add empty table cells for HTML output mode
			for ($i = 0; $i < $num_fields - 1; ++$i) {
				$cells[] = str_replace(array(PDebug::WC_INDENT, PDebug::WC_TYPE, PDebug::WC_VAR),	array(PDebug::$CURRENT_INDENT_STRING, '', ''),	PDebug::$GENERIC_CELL);
			}
		}
		$resource_footer_rows[] = str_replace(array(PDebug::WC_INDENT, PDebug::WC_TYPE, PDebug::WC_INFO, PDebug::WC_SUBITEM), array(PDebug::$CURRENT_INDENT_STRING, '', '', implode(PDebug::$GENERIC_CELL_JOINER, $cells)), PDebug::$GENERIC_LINE);

		$cells = array();
		$cells[] = str_replace(array(PDebug::WC_INDENT, PDebug::WC_TYPE, PDebug::WC_VAR),	array(PDebug::$CURRENT_INDENT_STRING, '', str_pad('Number of fields: ' . $num_fields, $full_width)),	PDebug::$GENERIC_CELL);
		if (PProtocolHandler::isOutputtingHtml() && !PProtocolHandler::$OUTPUT_HTML_AS_PLAIN) {
			// add empty table cells for HTML output mode
			for ($i = 0; $i < $num_fields - 1; ++$i) {
				$cells[] = str_replace(array(PDebug::WC_INDENT, PDebug::WC_TYPE, PDebug::WC_VAR),	array(PDebug::$CURRENT_INDENT_STRING, '', ''),	PDebug::$GENERIC_CELL);
			}
		}
		$resource_footer_rows[] = str_replace(array(PDebug::WC_INDENT, PDebug::WC_TYPE, PDebug::WC_INFO, PDebug::WC_SUBITEM), array(PDebug::$CURRENT_INDENT_STRING, '', '', implode(PDebug::$GENERIC_CELL_JOINER, $cells)), PDebug::$GENERIC_LINE);

		// resource table: plaintext formatting table separators
		if (PDebug::$GENERIC_BORDER_CHARACTER) {
			$divider_row = str_replace(array(PDebug::WC_INDENT, PDebug::WC_TYPE, PDebug::WC_INFO, PDebug::WC_SUBITEM), array(PDebug::$CURRENT_INDENT_STRING, '', '', str_repeat(PDebug::$GENERIC_BORDER_CHARACTER, $full_width)), PDebug::$GENERIC_LINE);

			$resource_footer_rows[] = $divider_row;
			array_unshift($resource_header_rows, $divider_row);
		}

		// resource table: column headers
		$cells = array();
		foreach (array_keys($columns) as $field) {
			$cells[] = str_replace(array(PDebug::WC_INDENT, PDebug::WC_TYPE, PDebug::WC_VAR), array(PDebug::$CURRENT_INDENT_STRING, '', str_pad($field, $col_widths[$field])), PDebug::$GENERIC_CELL);
		}
		$resource_header_rows[] = str_replace(array(PDebug::WC_INDENT, PDebug::WC_TYPE, PDebug::WC_INFO, PDebug::WC_SUBITEM), array(PDebug::$CURRENT_INDENT_STRING, '', '', implode(PDebug::$GENERIC_CELL_JOINER, $cells)), PDebug::$GENERIC_LINE);

		if (PDebug::$GENERIC_BORDER_CHARACTER) {
			$resource_header_rows[] = $divider_row;
		}

		// display rows using precomputed column widths / heights, for plaintext compatibility
		for ($i = 0; $i < $num_rows; $i++) {
			for ($height = 0; $height < $row_heights[$i]; $height++) {

				$cells = array();

				foreach (array_keys($col_widths) as $field) {
					if (!isset($columns[$field][$i][$height])) {
						$val = '';
						$len = $col_widths[$field];
					} elseif (is_null($columns[$field][$i][$height])) {
						$val = 'null';
						$len = $col_widths[$field] + 4;			// (length of 'null')
					} else {
						$val = $columns[$field][$i][$height];
						$len = $col_widths[$field];
					}

					$value = str_pad($val, $len, ' ', is_numeric($val) ? STR_PAD_LEFT : STR_PAD_RIGHT);
					$cells[] = str_replace(array(PDebug::WC_INDENT, PDebug::WC_VAR), array(PDebug::$CURRENT_INDENT_STRING, $value), PDebug::$GENERIC_CELL);
				}

				// combine cells to row
				$resource_table_rows[] = str_replace(array(PDebug::WC_INDENT, PDebug::WC_TYPE, PDebug::WC_INFO, PDebug::WC_SUBITEM), array(PDebug::$CURRENT_INDENT_STRING, '', ($i % 2 ? '' : ' alt'), implode(PDebug::$GENERIC_CELL_JOINER, $cells)), PDebug::$GENERIC_LINE);
			}
		}

		// add footer row
		if (PDebug::$GENERIC_BORDER_CHARACTER) {
			array_unshift($resource_footer_rows, $divider_row);
		}
	}

?>
