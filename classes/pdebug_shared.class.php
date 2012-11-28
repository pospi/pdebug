<?php
/*================================================================================
	pdebug - protocol handler class
	----------------------------------------------------------------------------
	pdebug protocol handler class for URI translation between different
	filesystems / network resources / server paths etc

	Adds support for IDE protocol handling for seamless debugging, @see
	 http://pospi.spadgos.com/projects/pdebug
	----------------------------------------------------------------------------
	Copyright (c) 2008 Sam Pospischil <pospi@spadgos.com>
  ===============================================================================*/

	abstract class PProtocolHandler {

		// output modes
		const MODE_TEXT = 'text';
		const MODE_HTML	= 'html';
		const MODE_JSON = 'json';

		static $CURRENT_OUTPUT_MODE = PProtocolHandler::MODE_HTML;

		static $APP_ROOT = '';

		static $SERVER_PATH = '';
		static $CLIENT_PATH = '';

		static $SERVER_UNIX = true;
		static $CLIENT_UNIX = false;

		// tokens allowable: %p, %f, %l
		static $OUTPUT_PATH_FORMAT  		 = '%p%l';			// defaults in case of missing config vars...
		static $OUTPUT_PATH_FORMAT_PLAINTEXT = '%p%l';
		static $OUTPUT_LINE_FORMAT			 = '/%l';
		static $OUTPUT_LINE_FORMAT_PLAINTEXT = '/%l';

		static $TRANSLATE_STRING_PATHS_IN_HTML = true;		// strings like "require('C:\web\myproject\file.inc.php');" gets paths replaced with links / tooltips automatically

		static $LINE_ENDING_REGEX = "/(\r\n|\r|\n)/";

		static $OUTPUT_HTML_AS_PLAIN = false;	// true if outputting plaintext data in HTML mode (config hack for end user simplicity)

		static $ECHO = true;					// output to the screen

		static $RECIPIENT_EMAIL = null;			// output to emails when this is not null
		static $SENDER_EMAIL = 'debugger@example.com';
		static $ENVELOPE_SENDER = null;
		static $EMAIL_SUBJECT_ERR = 'pDebug [%e errors, %w warnings] - %h';		// %h is hostname, %e = error count, %w = warning count
		static $EMAIL_SUBJECT_WARN = 'pDebug [%w warnings] - %h';
		static $EMAIL_SUBJECT_NORM = 'pDebug - %h';

	//=====================================================================================================================
	// String Functions
	//=====================================================================================================================

		/**
		 * Translates a server path to a client path, to link your live application to your local dev codebase via
		 * IDE protocol handling
		 */
		public static function String_translatePathsFor($server_path = '', $line = null) {
			if (!$server_path) {
				if (PProtocolHandler::$APP_ROOT) {
					$server_path = PProtocolHandler::$APP_ROOT;
				} else {
					return $server_path;		// cant really do anything if we don't know the server path...
				}
			}

			$translated_path = str_replace(PProtocolHandler::$SERVER_PATH, PProtocolHandler::$CLIENT_PATH, $server_path);
			if (PProtocolHandler::$SERVER_UNIX && !PProtocolHandler::$CLIENT_UNIX) {
				$translated_path = str_replace('/', '\\', $translated_path);
			} else if (PProtocolHandler::$CLIENT_UNIX && !PProtocolHandler::$SERVER_UNIX) {
				$translated_path = str_replace('\\', '/', $translated_path);
			}

			$line_str = $line ? str_replace('%l', $line, (PProtocolHandler::isOutputtingHtml() ? PProtocolHandler::$OUTPUT_LINE_FORMAT : PProtocolHandler::$OUTPUT_LINE_FORMAT_PLAINTEXT)) : '';
			$translated_path = str_replace(array('%p', '%f', '%l'), array($translated_path, basename($server_path), $line_str), (PPRotocolHandler::isOutputtingHtml() ? PProtocolHandler::$OUTPUT_PATH_FORMAT : PProtocolHandler::$OUTPUT_PATH_FORMAT_PLAINTEXT));

			return $translated_path;
		}


		/**
		 * Translates any paths relevant to this application within strings to clickable links which will open in your IDE
		 *
		 * :NOTE: separating this function removes the overhead for path detection in normal path situations
		 *		  where no parsing is required
		 */
		public static function String_translatePathsIn($string) {
			if (PProtocolHandler::isOutputtingHtml() && PProtocolHandler::$APP_ROOT) {   // cant really do anything if we don't know the app root path...
				$path = $line = null;

				$regex_special_chars = '\\/^.$|()[]*+?{}-';

				$line_regex = str_replace('%l', '\d+', addcslashes(PProtocolhandler::$OUTPUT_LINE_FORMAT_PLAINTEXT, $regex_special_chars));
				$path_regex = addcslashes(PProtocolHandler::$APP_ROOT, $regex_special_chars) . '([a-zA-Z0-9_\-\.~!\/\\\\])*';

				$path_detect_regex = '/((' . $path_regex . ')(' . $line_regex . ')?)/im';
				$line_detect_regex = '/^((' . $path_regex . ')(' . $line_regex . '))$/im';

				if ($string_pieces = preg_split($path_detect_regex, $string, -1, PREG_SPLIT_OFFSET_CAPTURE | PREG_SPLIT_DELIM_CAPTURE)) {

					$match_string = '';
					$last_match = array(0, 0);

					foreach ($string_pieces as $idx => $match_data) {
						$match = $match_data[0];
						$start_offset = $match_data[1];
						$end_offset = $start_offset + strlen($match);

						if ($start_offset >= $last_match[0] && $end_offset <= $last_match[1]) {
							continue;   // sub-match
						}

						$last_match = array($start_offset, $end_offset);

						if (!preg_match($path_detect_regex, $match)) {
							$match_string .= $match;
						} else {
							$path = $match;
							$line = null;

							if (preg_match($line_detect_regex, $match, $line_matches)) {
								$path = $line_matches[2];
								// this handles some wierdness (probably with my regex, meh) where the last
								// character of the path is sometimes detected as a separate subpattern
								$line = isset($line_matches[4]) ? $line_matches[4] : $line_matches[3];

								// we have no idea what people's line formats might be here, so umm....
								// just take out the first number - should be fine...?
								// we only want the first one in case of things like title attributes, etc
								if (preg_match('/\d+/i', $line, $line_number_match)) {
									$line = $line_number_match[0];
								}
							}

							$match_string .= PProtocolHandler::String_translatePathsFor($path, $line);
						}
					}
					return $match_string;
				}
			}
			return $string;
		}

		// format string, escaped for html... if required
		public static function String_htmlSafe($string, $allow_br = false) {
			if (PProtocolHandler::isOutputtingHtml()) {
				$string = htmlentities($string);
				if ($allow_br) {
					$string = nl2br($string);
				}
			}
			return $string;
		}

		// format string, escaped for insertion into regular expression
		public static function String_escapeForRegex($string) {
			return addcslashes($string, '\\/^.$|()[]*+?{}-');
		}

		// line breaking function using regexes to detect multiple line-ending types automatically
		public static function String_getLines($string) {

			$parts = preg_split(PProtocolHandler::$LINE_ENDING_REGEX, $string, -1, PREG_SPLIT_DELIM_CAPTURE);

			$string_lines = array();
			$line_num = 1;
			$last_was_newline = false;

			for ($i = 0; $i < sizeof($parts); ++$i) {
				$piece = $parts[$i];

				// if the previous line wasn't a newline
				if (!$last_was_newline) {
					// if this is a newline, add the newline to output...
					if ($piece == "\r\n" || $piece == "\r" || $piece == "\n") {
						++$line_num;

						// set the text for the next line to include the newline (so copy/pasting is ok in HTML mode)
						/*if (PProtocolHandler::isOutputtingHtml() && !PProtocolHandler::$OUTPUT_HTML_AS_PLAIN) {
							$line = $piece;
						} else {*/
							$line = '';
						//}

						$last_was_newline = true;	// flag that this was a newline in case the next one is too
						continue;
					} else {	// ...otherwise we start off blank for the next line
						$line = '';
					}
				}

				$line .= $piece;
				$last_was_newline = false;
				$string_lines[] = $line;
			}

			return $string_lines;
		}

		// takes a string or array of lines
		public static function String_getDisplayWidth($string) {
			if (is_string($string)) {
				$lines = PProtocolHandler::String_getLines($string);
			} else {
				$lines = $string;
			}

			$escape = PProtocolHandler::isOutputtingHtml();

			$maxw = 0;
			foreach ($lines as $line) {
				$len = $escape ? strlen(html_entity_decode(strip_tags($line))) : strlen($line);
				if ($len > $maxw) {
					$maxw = $len;
				}
			}

			return $maxw;
		}

		/**
		 * takes a multiline string with all different line lengths, and inserts padding around it for plaintext output, like so (dots are spaces):
		 * "test string								"test string............|
		 *  	zomg					-->		|........zomg...............|
		 *  no FREAKING WAY"					|....no FREAKING WAY........|"
		 */
		public static function String_padForGenericLine($string, $indent, $leftMargin, $rightMargin, $leftPrepend = '', $rightAppend = '', $doFirstLine = false, $doLastLine = false) {
			if (PProtocolHandler::isOutputtingHtml() && !PProtocolHandler::$OUTPUT_HTML_AS_PLAIN) {
				return $string;
			}

			$lines = PProtocolHandler::String_getLines($string);
			$maxw = PProtocolHandler::String_getDisplayWidth($lines);
			$first = array_shift($lines);

			$result = ($doFirstLine ? $indent . $leftPrepend . str_repeat(' ', $leftMargin) : '') . str_pad($first, $maxw) . str_repeat(' ', $rightMargin) . $rightAppend;
			foreach ($lines as $k => $line) {
				$line = $leftPrepend . str_repeat(' ', $leftMargin) . str_pad($line, $maxw) . str_repeat(' ', $rightMargin) . $rightAppend;
				$result .= "\n" . str_replace(array(PDebug::WC_INDENT, PDebug::WC_TYPE, PDebug::WC_INFO, PDebug::WC_SUBITEM), array($indent, '', '', $line), PDebug::$GENERIC_LINE);
			}

			return $result;
		}

	//=====================================================================================================================
	// Color Functions
	//=====================================================================================================================

		public static function Color_getHexString($hex) {
			$return = sprintf('%X', $hex);
			$return = '#' . str_pad($return, 6, '0', STR_PAD_LEFT);
			return $return;
		}

		/**
		 * Finds the colour linearly between two color values (as ints plz)
		 *
		 * @param array	$low 	= [(mixed)min_value, (int)min_color]
		 * @param array	$high	= [(mixed)max_value, (int)max_color]
		 * @param mixed	$value	the value between min_value and max_value to base the shade on
		 */
		public static function Color_getColorBetween($low, $high, $value) {

			$low_val = $low[0];
			$low = $low[1];
			$high_val = $high[0] > 0 ? $high[0] - 1 : $high[0];
			$high = $high[1];

			// clamp the value, first
			$value = max(min($value, $high_val), $low_val);

			if ($low_val == $high_val && $high_val == 0) {
				$ratio = 0.5;
			} else {
				$ratio = ($value - $low_val) / ($high_val - $low_val);
			}

			$components = array(
				'r' => array(($low & 0xFF0000) >> 16, ($high & 0xFF0000) >> 16),
				'g' => array(($low & 0x00FF00) >> 8,  ($high & 0x00FF00) >> 8),
				'b' => array(($low & 0x0000FF),	   ($high & 0x0000FF)),
			);

			foreach ($components as $primary => $color_parts) {
				$this_ratio = $ratio;
				$high_val = $color_parts[1];
				$low_val  = $color_parts[0];
				if ($high_val < $low_val) {
					$temp = abs($high_val);
					$high_val = abs($low_val);
					$low_val = $temp;
					$this_ratio = 1 - $this_ratio;
				}

				$components[$primary]['final'] = $low_val + (int)(($high_val - $low_val) * $this_ratio);
			}

			$hex = ($components['r']['final'] << 16) | ($components['g']['final'] << 8) | $components['b']['final'];

			return PProtocolHandler::Color_getHexString($hex);
		}

	//=====================================================================================================================
	// Mailing Functions
	//=====================================================================================================================

		public static function sendMail($subject, $text) {
			if (!PProtocolHandler::$RECIPIENT_EMAIL) {
				return false;
			}

			// add mail headers & formatting depending on the type of debug we've been collecting
			$h = 'From: ' . PProtocolHandler::$SENDER_EMAIL . "\r\n"
			   . 'Reply-To: ' . PProtocolHandler::$SENDER_EMAIL . "\r\n";

			if (PProtocolHandler::isOutputtingHtml()) {
				$h .= "MIME-Version: 1.0\r\n"
					. "Content-Type: text/html; charset=ISO-8859-1\r\n";

				$text = '<html><body>' . $text . '</body></html>';
			}

			return @mail(PProtocolHandler::$RECIPIENT_EMAIL, $subject, $text, $h, (isset(PProtocolHandler::$ENVELOPE_SENDER) ? '-f' . PProtocolHandler::$ENVELOPE_SENDER : ''));
		}

	//=====================================================================================================================
	//=====================================================================================================================

		public static function outputAs($mode) {
			switch (strval($mode)) {
				case 'text':
				case 'plaintext':
				case 'plain':
					PProtocolHandler::$CURRENT_OUTPUT_MODE = PProtocolHandler::MODE_TEXT;
					break;
				case 'html':
					PProtocolHandler::$CURRENT_OUTPUT_MODE = PProtocolHandler::MODE_HTML;
					break;
				case 'json':
				case 'ajax':
				case 'js':
				case 'javascript':
					PProtocolHandler::$CURRENT_OUTPUT_MODE = PProtocolHandler::MODE_JSON;
					break;

			}
		}

		public static function isOutputtingHtml() {
			return PProtocolHandler::$CURRENT_OUTPUT_MODE == PProtocolHandler::MODE_HTML;
		}

	}

//=====================================================================================================================================
//											IDE options reading for class variables
//=====================================================================================================================================

	// non-HTML mode checks
	if ($_PDEBUG_OPTIONS['force_output_mode'] !== null) {
		PProtocolHandler::outputAs($_PDEBUG_OPTIONS['force_output_mode']);
	} else if (
		// :TODO: detect other things for weird AJAX libraries
		(isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest')
	  ) {
		PProtocolHandler::outputAs(PProtocolHandler::MODE_JSON);
	} else if (PHP_SAPI == 'cli' || (substr(PHP_SAPI, 0, 3) == 'cgi' && empty($_SERVER['REQUEST_URI']))) {
		PProtocolHandler::outputAs(PProtocolHandler::MODE_TEXT);
	}

	// set application root (for backtrace & other file paths)
	if (!$_PDEBUG_OPTIONS['application_root']) {
		PProtocolHandler::$APP_ROOT = $_SERVER['DOCUMENT_ROOT'];
	} else {
		PProtocolHandler::$APP_ROOT = $_PDEBUG_OPTIONS['application_root'];
	}

	// IDE path translations
	PProtocolHandler::$SERVER_PATH = $_PDEBUG_OPTIONS['server_path_search'];
	PProtocolHandler::$CLIENT_PATH = $_PDEBUG_OPTIONS['server_path_replace'];
	PProtocolHandler::$SERVER_UNIX = $_PDEBUG_OPTIONS['server_unix'];
	PProtocolHandler::$CLIENT_UNIX = $_PDEBUG_OPTIONS['client_unix'];

	if (isset($_PDEBUG_OPTIONS['output_path_format'])) {
		PProtocolHandler::$OUTPUT_PATH_FORMAT = $_PDEBUG_OPTIONS['output_path_format'];
	}
	if (isset($_PDEBUG_OPTIONS['output_path_format_plaintext'])) {
		PProtocolHandler::$OUTPUT_PATH_FORMAT_PLAINTEXT = $_PDEBUG_OPTIONS['output_path_format_plaintext'];
	}
	if (isset($_PDEBUG_OPTIONS['output_line_format'])) {
		PProtocolHandler::$OUTPUT_LINE_FORMAT = $_PDEBUG_OPTIONS['output_line_format'];
	}
	if (isset($_PDEBUG_OPTIONS['output_line_format_plaintext'])) {
		PProtocolHandler::$OUTPUT_LINE_FORMAT_PLAINTEXT = $_PDEBUG_OPTIONS['output_line_format_plaintext'];
	}
	if (isset($_PDEBUG_OPTIONS['translate_string_paths_in_html'])) {
		PProtocolHandler::$TRANSLATE_STRING_PATHS_IN_HTML = $_PDEBUG_OPTIONS['translate_string_paths_in_html'];
	}
	if (isset($_PDEBUG_OPTIONS['line_ending_regex'])) {
		PProtocolHandler::$LINE_ENDING_REGEX = $_PDEBUG_OPTIONS['line_ending_regex'];
	}

	if (isset($_PDEBUG_OPTIONS['print_output'])) {
		PProtocolHandler::$ECHO = $_PDEBUG_OPTIONS['print_output'];
	}
	if (isset($_PDEBUG_OPTIONS['email_output']) && $_PDEBUG_OPTIONS['email_output'] && isset($_PDEBUG_OPTIONS['email_errors_to'])) {
		PProtocolHandler::$RECIPIENT_EMAIL = $_PDEBUG_OPTIONS['email_errors_to'];
	}
	if (isset($_PDEBUG_OPTIONS['email_from_address'])) {
		PProtocolHandler::$SENDER_EMAIL = $_PDEBUG_OPTIONS['email_from_address'];
	}
	if (!empty($_PDEBUG_OPTIONS['email_envelope'])) {
		PProtocolHandler::$ENVELOPE_SENDER = $_PDEBUG_OPTIONS['email_envelope'];
	}
	if (isset($_PDEBUG_OPTIONS['email_subject_errors'])) {
		PProtocolHandler::$EMAIL_SUBJECT_ERR = $_PDEBUG_OPTIONS['email_subject_errors'];
	}
	if (isset($_PDEBUG_OPTIONS['email_subject_warnings'])) {
		PProtocolHandler::$EMAIL_SUBJECT_WARN = $_PDEBUG_OPTIONS['email_subject_warnings'];
	}
	if (isset($_PDEBUG_OPTIONS['email_subject_output'])) {
		PProtocolHandler::$EMAIL_SUBJECT_NORM = $_PDEBUG_OPTIONS['email_subject_output'];
	}
