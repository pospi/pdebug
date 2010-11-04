<?php
/*================================================================================
	pPHPide - protocol handler class
	----------------------------------------------------------------------------
	pPHPide protocol handler class for URI translation between different
	filesystems / network resources / server paths etc

	Adds support for IDE protocol handling for seamless debugging, @see
	 http://pospi.spadgos.com/projects/pPHPide
	----------------------------------------------------------------------------
	Copyright (c) 2008 Sam Pospischil <pospi@spadgos.com>
  ===============================================================================*/

	class PProtocolHandler {

		static $USE_HTML = true;
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

		/**
		 * Translates a server path to a client path, to link your live application to your local dev codebase via
		 * IDE protocol handling
		 */
		static function translatePathsIn($server_path = '', $line = null) {
			if (!$server_path) {
				$server_path = PProtocolHandler::$APP_ROOT;
			}

			$translated_path = str_replace(PProtocolHandler::$SERVER_PATH, PProtocolHandler::$CLIENT_PATH, $server_path);
			if (PProtocolHandler::$SERVER_UNIX && !PProtocolHandler::$CLIENT_UNIX) {
				$translated_path = str_replace('/', '\\', $translated_path);
			} else if (PProtocolHandler::$CLIENT_UNIX && !PProtocolHandler::$SERVER_UNIX) {
				$translated_path = str_replace('\\', '/', $translated_path);
			}

			$line_str = $line ? str_replace('%l', $line, (PPRotocolHandler::$USE_HTML ? PProtocolHandler::$OUTPUT_LINE_FORMAT : PProtocolHandler::$OUTPUT_LINE_FORMAT_PLAINTEXT)) : '';
			$translated_path = str_replace(array('%p', '%f', '%l'), array($server_path, basename($server_path), $line_str), (PPRotocolHandler::$USE_HTML ? PProtocolHandler::$OUTPUT_PATH_FORMAT : PProtocolHandler::$OUTPUT_PATH_FORMAT_PLAINTEXT));

			return $translated_path;
		}


		/**
		 * Translates any paths relevant to this application within strings to clickable links which will open in your IDE
		 *
		 * :NOTE: separating this function removes the overhead for path detection in normal path situations
		 *		  where no parsing is required
		 */
		static function translatePathsInString($string) {
			if (PProtocolHandler::$USE_HTML) {
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

							$match_string .= PProtocolHandler::translatePathsIn($path, $line);
						}
					}
					return $match_string;
				}
			}
			return $string;
		}

		//================================================================================================

		// format string, escaped for html... if required

		static function htmlSafeString($string, $allow_br = true) {
			if (PProtocolHandler::$USE_HTML) {
				$string = htmlentities($string);
				if ($allow_br) {
					$string = nl2br($string);
				}
			}
			return $string;
		}

		// format string, escaped for html attribute tag... if required

		static function encodeHtmlAttribute($string) {
			if (PProtocolHandler::$USE_HTML) {
				$replacements = array(
					'&'		=> '&amp;',		// lazy, but.. just do this one first, okay?
					'  ' 	=> ' &nbsp;',
					'<'		=> '&lt;',
					'>'		=> '&gt;',
					'"'		=> '&quot;',
				);
				$string = str_replace(array_keys($replacements), array_values($replacements), $string);
			}
			return $string;
		}

	}

//=====================================================================================================================================
//											IDE options reading for class variables
//=====================================================================================================================================

	// non-HTML mode checks
	if ( ($_PDEBUG_OPTIONS['force_html_mode'] !== null && !$_PDEBUG_OPTIONS['force_html_mode'])
	  || (!isset($_SERVER['SERVER_SOFTWARE'])
	  || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest')
	  || empty($_SERVER['HTTP_USER_AGENT']) ) ) {
		PProtocolHandler::$USE_HTML = false;
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

	if ($_PDEBUG_OPTIONS['output_path_format']) {
		PProtocolHandler::$OUTPUT_PATH_FORMAT = $_PDEBUG_OPTIONS['output_path_format'];
	}
	if ($_PDEBUG_OPTIONS['output_path_format_plaintext']) {
		PProtocolHandler::$OUTPUT_PATH_FORMAT_PLAINTEXT = $_PDEBUG_OPTIONS['output_path_format_plaintext'];
	}
	if ($_PDEBUG_OPTIONS['output_line_format']) {
		PProtocolHandler::$OUTPUT_LINE_FORMAT = $_PDEBUG_OPTIONS['output_line_format'];
	}
	if ($_PDEBUG_OPTIONS['output_line_format_plaintext']) {
		PProtocolHandler::$OUTPUT_LINE_FORMAT_PLAINTEXT = $_PDEBUG_OPTIONS['output_line_format_plaintext'];
	}
	if ($_PDEBUG_OPTIONS['translate_string_paths_in_html']) {
		PProtocolHandler::$TRANSLATE_STRING_PATHS_IN_HTML = $_PDEBUG_OPTIONS['translate_string_paths_in_html'];
	}

?>
