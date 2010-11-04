<?php
//
//			USAGE:	dump(							 				$var1 [, $var2, $var3, ...]	);
//					deferDump(		$times_to_defer, 				$var1 [, $var2, $var3, ...]	);
//					deferDump(		array($defer1, $defer2, ...), 	$var1 [, $var2, $var3, ...]	);
//					conditionalDump($condition, 					$var1 [, $var2, $var3, ...]	);
//					trace();
//					bench(); 				{...some code happens...}	bench();
//					bench($benchmark_tag);	{...some code happens...}	bench($benchmark_tag_2);
//
/*================================================================================
	pPHPide - debugger class
	----------------------------------------------------------------------------
	PDebug static Debug class definition, and wrapper methods for easy
	access - @see pphpide.conf.php for configuration options

	Supports IDE protocol handling for seamless debugging, @see
	 http://pospi.spadgos.com/projects/pPHPide

	:NOTE: There should be *absolutely* no markup in this class! ALL markup /
		   formatting to be generated through string substitution via configuration
		   file, for easy configurability.
	----------------------------------------------------------------------------
	Copyright (c) 2008 Sam Pospischil <pospi@spadgos.com>
  ===============================================================================*/

if ($_PDEBUG_OPTIONS['use_debugger']) {

	class PDebug {

		//===============================
		// layout vars

		// These comments are indented (4 space tabs!) to show node layout in the default HTML style
		// Using lists is very economical and modular here with regards to recursion. Observe:

		static $COMMON_HEADER;			// style block, common clientside code etc

		static $HEADER_BLOCK;			//	<ul>

		static $ERROR_FORMAT; 			//	<li>...</li>

		static $BENCH_FORMAT;			//	<li>...</li>

		static $STACK_FORMAT;			//	<li>Stack: <ul>
			static $STACK_LINE;			// 		{<li>func_call(<ul>
			static $STACK_JOINER;		// 			{
	             // variables...   		// 				{<li>...</li>}
										//			,}
		            					//		</ul>)</li>}
										// 	</ul></li>

		static $VARIABLES_HEADER;		//	<li>Info for # vars:<ol>	:NOTE: this header & footer are only used when dump()ing multiple variables
		static $VARIABLES_JOINER;		//								joins stuff. If you use <ol>'s, you can get away with a blank string in HTML mode and let it handle the numbering.

		static $OBJECT_FORMAT;			//	<li>Object (stdClass)<ul>
			static $OBJECT_JOINER;		//	{
			static $OBJECT_MEMBER;		//		{<li><ul>
					// variables...		//			<li>member:scope</li>
										// 			... you get the idea...
				//end OBJECT_MEMBER		//		</ul></li>}
				//end OBJECT_JOINER		//	,}
			//end OBJECT_FORMAT			//	</ul></li>



		static $ARRAY_FORMAT;			//	{<li>Array (# elements)<ul>
			static $ARRAY_JOINER;		//		{
			static $ARRAY_PAIR;			//		 {<li><ul>
										//			{<li>key</li>}
	                           			//			{<li>simple value</li>}
										//		</ul></li>}, }
										//		{<li><ul>			this isn't a real type, just to illustrate substitutions
										//			{<li>key</li>}
			                            //			{<li>complex value<ul>
										//					<li>...</li>
			                            //			</ul></li>}
				 						//		</ul></li>}
										//	</ul></li>}

		static $GENERIC_FORMAT;				// this is used for resources, too
			static $GENERIC_LINE;
			static $GENERIC_ITEM;
			static $GENERIC_LINE_JOINER;
			static $GENERIC_ITEM_JOINER;

		static $VARIABLES_FOOTER;		//	</ol></li>					:NOTE: this header & footer are only used when dump()ing multiple variables

		static $FOOTER_BLOCK;			// </ul>

		static $INDENT_STRING;			// don't want it indented? turn it off. bitch.

		static $VARIABLE_OUTPUT_FORMAT;	// <li></li>					:NOTE: common format used in dumping all simple datatypes

		//===============================

		// Global wildcards:
		const WC_SUBITEM		= '%s';
		const WC_PATH			= '%p';

		// Stack wildcards:
		const WC_CLASS_NAME		= '%c';
		const WC_FUNC_TYPE		= '%t';
		const WC_FUNC_NAME		= '%f';
		const WC_FUNC_ARGS 		= '%a';

		// Debugger wildcards:
		const WC_TYPE 			= '%t';
		const WC_VAR 			= '%v';
		const WC_KEY 			= '%k';
		const WC_INFO 			= '%i';

		// Error handler wildcards:
		const WC_ERROR 			= '%e';
		const WC_ERROR_DETAILS	= '%m';

		//===============================

		static $USE_STACK_TRACE = true;

		static $IGNORE_FUNCTIONS = array();		// internal functions to exclude from a stack trace (not including class functions)

		static $START_COLLAPSED = false;

		//================================
		// state vars

		static $PDEBUG_LOOP_COUNT = 0;
		static $PDEBUG_PREV_BENCH = 0;
		static $PDEBUG_PREV_MEM = 0;

		static $DEFER_COUNT = 0;

		static $HAS_OUTPUT_HEADER 	= false;	// include common CSS / JS header for debugging HTML output on first call

		//============================================================================================

		/**
		 *	Dumps a variable(s) information recursively
		 *
		 *  @param  bool	$force_show_trace	Toggle stack trace display
		 *  @param  bool	$force_collapsed 	Force starting node status
		 *
		 *  @return string
		 */
		public static function dump($vars, $force_show_trace = null, $force_collapsed = null) {
			$out = '';

			//show the call stack
			if ($force_show_trace || ($force_show_trace === null && PDebug::$USE_STACK_TRACE)) {
				$out .= PDebug::trace();
			}

			$start_depth = 0;
			$do_numbering = false;
			if (count($vars) > 1) {
				$out .= str_replace('%i', count($vars), PDebug::$VARIABLES_HEADER);
				$start_depth = 1;
				$do_numbering = true;
			}

			// force stack compression if desired
			$debug_last_collapsed = PDebug::$START_COLLAPSED;
			if ($force_collapsed !== null) {
				PDebug::$START_COLLAPSED = (bool)$force_collapsed;
			}

			$i = 0;
			foreach ($vars as $var) {
				if ($do_numbering) {
					$out .= str_replace('%i', ++$i, PDebug::$VARIABLES_JOINER);
				}
				$out .= PDebug::getDebugFor($var, $start_depth);
			}

			if ($do_numbering) {
				$out .= str_replace('%i', count($vars), PDebug::$VARIABLES_FOOTER);
			}

			PDebug::$START_COLLAPSED = $debug_last_collapsed;

			list($header_extra, $footer_extra) = PDebug::verifyHeaderIncludes();

			return $header_extra . $out . $footer_extra;
		}

		/**
		 * Generates a backtrace so that you can easily view the callstack.
		 *
		 * @return string
		 */
		public static function trace() {
			$stack = array_reverse(debug_backtrace());

			return PDebug::readableBacktrace($stack);
		}

		/** :TODO:
		 * Benchmarking functions
		 */
		public static function bench($tag = '') {
			return PDebug::formatBench($tag, PDebug::getBench());
		}

		// returns KB / seconds
		public static function getBench() {
		   if (!PDebug::$PDEBUG_PREV_BENCH) {
		       PDebug::$PDEBUG_BENCH_START = $_SERVER['REQUEST_TIME'];
		       PDebug::$PDEBUG_PREV_BENCH = $_SERVER['REQUEST_TIME'];
		   }

		   $mem_usage = memory_get_usage();
		   $this_call = microtime(true) - PDebug::$PDEBUG_BENCH_START;

		   $time_diff = round($this_call - PDebug::$PDEBUG_PREV_BENCH, 5);
		   $mem_diff  = round(($mem_usage - PDebug::$PDEBUG_PREV_MEM) / 1024, 3);

		   PDebug::$PDEBUG_PREV_BENCH = $this_call;
		   PDebug::$PDEBUG_PREV_MEM = $mem_usage;

		   return array(round($mem_usage / 1024, 3), $this_call, $mem_diff, $time_diff);
		}

		//====================================================================================================================================
		//====================================================================================================================================
		//====================================================================================================================================

		/**
		 * Convert a backtrace array into a nice readable format
		 *
		 * @param  array	stack	backtrace array
		 * @return string
		 */
		private static function readableBacktrace($stack) {
			$out = '';

			foreach ($stack as $hist => $data) {
				if ((!empty($data['class']) && $data['class'] == 'PDebug')
				  || (empty($data['class']) && in_array($data['function'], PDebug::$IGNORE_FUNCTIONS))) {
					continue;
				}

				// :IMPORTANT: modifying $data['args'] seems to break sometimes when executing from within a class method
				$func_arguments = array();
				foreach ($data['args'] as $k => $arg) {
					$func_arguments[$k] = PDebug::getDebugFor($arg);
				}

				$out .= str_replace(
					array('%p', '%c', '%t', '%f', '%s'),
					array(
						PProtocolHandler::translatePathsIn($data['file'], $data['line']),
						(isset($data['class'])	? $data['class'] : ''),
						(isset($data['type'])	? $data['type']	 : ''),
						$data['function'],
						implode(PDebug::$STACK_JOINER, $func_arguments),
					),
					PDebug::$STACK_LINE);
			}

			$stack_bits = explode(PDebug::WC_SUBITEM, PDebug::$STACK_FORMAT);
			list($header_extra, $footer_extra) = PDebug::verifyHeaderIncludes($stack_bits[0], $stack_bits[1]);

			return $header_extra . $out . $footer_extra;
		}

		/**
		 * Convert benchmarking data into a nice readable format
		 *
		 * @param  array	stack	backtrace array
		 * @return string
		 */
		private static function formatBench($tag = '', $bench_stats) {
			$out = '';
			$trace = debug_backtrace();

			list($mem_usage, $this_call, $mem_diff, $time_diff) = $bench_stats;

		   	if ($mem_diff > 0) {
		   		$mem_diff = '+' . $mem_diff;
		   	}
		   	if ($time_diff > 0) {
		   		$time_diff = '+' . $time_diff;
		   	}

		   	$out .= str_replace(
					array('%i', '%p', '%t', '%m', '%dt', '%dm'),
					array(
						$tag,
						PProtocolHandler::translatePathsIn($trace[0]['file'], $trace[0]['line']),
						$this_call,
						$mem_usage,
						$time_diff,
						$mem_diff,
					),
					PDebug::$BENCH_FORMAT);

			return $out;
		}

		/**
		 * Call this to return wrapper text for an external function call's output,
		 * in the current output mode
		 */
		private static function verifyHeaderIncludes($header_extra = null, $footer_extra = null) {
			$header = $footer = '';

			if (!PDebug::$HAS_OUTPUT_HEADER && PProtocolHandler::$USE_HTML) {
				PDebug::$HAS_OUTPUT_HEADER = true;
				$header = PDebug::$COMMON_HEADER . $header;
			}

			$header .= PDebug::$HEADER_BLOCK;
			$footer .= PDebug::$FOOTER_BLOCK;

			$header .= $header_extra;		// no checks: these should never contain HTML in plaintext mode anyway...
			$footer .= $footer_extra;

			return array($header, $footer);
		}

		// dont explode server plz kthx
		private static function sanityCheck() {
			if (++PDebug::$PDEBUG_LOOP_COUNT > 500000) {
				die(PDebug::$USE_HTML ? '<h3>Circular reference detected - aborting!</h3>' : 'Circular reference detected - aborting!');
			}
		}

		/**
		 * This confines all our type-checking into one function, rather than checking for each type
		 * separately in each debug_* function. Plus, it cuts down on needless type checks by ensuring
		 * we only do it once for each variable.
		 *
		 *  @param	  array		ref_chain   array of all previously dumped vars (avoids recursion)
		 */
		private static function getDebugFor($var, $indent = 0, &$ref_chain = null) {
			PDebug::sanityCheck();

			if (is_object($var)) {
				return PDebug::debug_object($var, $indent, $ref_chain);
			} else if (is_array($var)) {
				return PDebug::debug_array($var, $indent, $ref_chain);
			} else if (is_resource($var)) {
				return PDebug::debug_resource($var, $indent);
			} else {
				return PDebug::debug_var($var, $indent);
			}
		}

		//======================================================================================
		//======================================================================================
		//======================================================================================

		/** :TODO:
		 *  PDebugging for resource datatypes, since PHP performs no debugging for these types itself.
		 *  Implementation for each custom resource type to be added as deemed necessary.
		 *
		 *  @param	  resource		var	 	the resource to debug
		 *  @param	  int		 	indent  number of tab-stops to indent this dump by
		 */
		private static function debug_resource($var, $indent) {

			$thisIndent = str_repeat(PDebug::$INDENT_STRING, $indent);
			$thisResType = get_resource_type($var);
			$out = '<span style="color: '.PDebug::PDEBUG_COLOR_RESOURCE.'">'.print_r($var, true).' ('.$thisResType.')</span>'."\n".$thisIndent.PDebug::PDEBUG_ELEMENT_TOGGLE.'<span' . (PDebug::$START_COLLAPSED ? ' style="display: none"' : '') . '>'."\n";	 //this will only show the type of resource
			switch ($thisResType) {
				case 'mysql result' :
					$tabStops = 4;
					$numRows = mysql_num_rows($var);
					$numFields = mysql_num_fields($var);
					$out .= $thisIndent.PDebug::$INDENT_STRING.'Number of rows: '.$numRows."\n";
					$out .= $thisIndent.PDebug::$INDENT_STRING.'Number of fields: '.$numFields."\n"."\n".$thisIndent.PDebug::$INDENT_STRING;
					$colWidths = array();
					$cols = array();
					$rowHeight = array();
					$rowCount = 0;
					if ($numRows) {
						mysql_data_seek($var, 0);
						while ($row = mysql_fetch_assoc($var)) {
							$rowHeight[$rowCount] = 1;
							foreach ($row as $field => $value) {
								if (!isset($cols[$field])) $cols[$field] = array();
								if (!isset($colWidths[$field])) $colWidths[$field] = strlen($field);

								$lines = is_null($value) ? array(null) : explode(LF, $value);
								$rowHeight[$rowCount] = max($rowHeight[$rowCount], count($lines));
								// find the width the columns need to be.
								for ($l = 0; $l < count($lines); $l++) {	// go through each of the lines in this field
									$line = $lines[$l];
									if (is_null($line)) {
										$len = 4;	// length of "null"
									} else {
										$line = strval($line);
										if (strpos($line, TAB) !== false) {
											$out = '';
											for ($i = 0; $i < strlen($line); $i++) {
												if ($line[$i] == TAB) {
													$out .= str_repeat(' ', ($tabStops - (strlen($out) % $tabStops)));
												} else {
													$out .= $line[$i];
												}
											}
											$lines[$l] = $line = $out;
										}
										$len = strlen($line);
									}
									$colWidths[$field] = max($colWidths[$field], $len);
								}
								$cols[$field][] = $lines;
							}
							$rowCount++;
						}
						$fullWidth = array_sum($colWidths) + count($colWidths) * 3 + 1;
						mysql_data_seek($var, 0);
						$out .= '|' . str_repeat('=', $fullWidth - 2) . '|'."\n".$thisIndent.PDebug::$INDENT_STRING;
						foreach (array_keys($cols) as $field) {
							$out .= '| ' . str_pad($field, $colWidths[$field]) . ' ';
						}
						$out .= '|'.LF.$thisIndent.PDebug::$INDENT_STRING.'|' . str_repeat('-', $fullWidth - 2) . '|'."\n".$thisIndent.PDebug::$INDENT_STRING;
						for ($i = 0; $i < $numRows; $i++) {
							for ($height = 0; $height < $rowHeight[$i]; $height++) {
								$out .= '<span style="background: ' . ($i % 2 ? PDebug::PDEBUG_COLOR_TABLEBG1 : PDebug::PDEBUG_COLOR_TABLEBG2) . '">';
								foreach (array_keys($colWidths) as $field) {
									if (!isset($cols[$field][$i][$height])) {
										$val = '';
										$len = $colWidths[$field];
									} elseif (is_null($cols[$field][$i][$height])) {
										$val = '<i style="color: '.PDebug::PDEBUG_COLOR_RESOURCE_NULL.'">NULL</i>';
										$len = $colWidths[$field] + 27;	// (length of <i></i>);
									} else {
										$val = $cols[$field][$i][$height];
										$len = $colWidths[$field];
									}
									$out .= '| ' . str_pad($val, $len, ' ', is_numeric($cols[$field][$i]) ? STR_PAD_LEFT : STR_PAD_RIGHT) . ' ';
								}
								$out .= '|</span>'."\n".$thisIndent.PDebug::$INDENT_STRING;
							}
						}
						$out .= '|' . str_repeat('=', $fullWidth - 2) . '|'."\n";
					}
					break;
				case 'mysql link' :
					$out .= $thisIndent . PDebug::$INDENT_STRING . 'Charset: ' . @mysql_client_encoding($var) . "\n";
					$out .= $thisIndent . PDebug::$INDENT_STRING . 'Client: ' . @mysql_get_client_info() . "\n";
					$out .= $thisIndent . PDebug::$INDENT_STRING . 'Host: ' . @mysql_get_host_info($var) . "\n";
					$out .= $thisIndent . PDebug::$INDENT_STRING . 'Protocol ver: ' . @mysql_get_proto_info($var) . "\n";
					$out .= $thisIndent . PDebug::$INDENT_STRING . 'Server ver: ' . @mysql_get_server_info($var) . "\n";
					break;
				default:
					$out .= $thisIndent . PDebug::$INDENT_STRING . '<span style="color: '.PDebug::PDEBUG_COLOR_UNKNOWN.'; font-style: italic">*UNKNOWN RESOURCE TYPE*</span>' . "\n";
					break;
			}
			$out .= $thisIndent.'</span>)'."\n";

			return $out;
		}

		/** :TODO:
		 *  Special debug case for objects - output all relevant data.
		 *
		 *  @param	  object	var		 	the object to debug
		 *  @param	  int		indent	  	number of tab-stops to indent this dump by
		 *  @param	  array		ref_chain   array of all previously dumped vars (avoids recursion)
		 */
		private static function debug_object($var, $indent, &$ref_chain = null) {
			$base_indent = str_repeat(PDebug::$INDENT_STRING, $indent);

			if ($ref_chain === null) {
				$ref_chain = array();
			}

			foreach ($ref_chain as $ref_val) {
				if ($ref_val === $var) {
					return str_replace(array('%t', '%v', '%i', '%n'), array('unknown', '* RECURSION *', '', ''), PDebug::$VARIABLE_OUTPUT_FORMAT);
				}
			}

			$myString = $base_indent;

			array_push($ref_chain, $var);

			// replace variable info into variable output string, for array itself...
			$object_var_format = str_replace(array('%t', '%v', '%i', '%n'), array('Object', 'Object', ' (' . get_class($var) . ' Object)', ''), PDebug::$VARIABLE_OUTPUT_FORMAT);
			// ...now replace this variable into the opening element toggle
			$myString .= str_replace(array('%t', '%v'), array($object_var_format, 'object'), PDebug::$ELEMENT_TOGGLE_OPEN);

			$var = (array)$var;
			foreach ($var as $key => $val) {

				$key_type = 'public';
				if ($key{0} == "\0") {					// private or protected var
					$key_parts = explode("\0", $key);
					$key = $key_parts[2];
					$key_type = ($key_parts[1] == '*') ? 'protected' : 'private';
				}

				$val_var = PDebug::getDebugFor($val, $indent + 1, $ref_chain);

				$myString .= $base_indent . str_replace(array('%k', '%v', '%t'), array($key, $val_var, $key_type), PDebug::$ITERABLE_ITEM);
			}
			$myString .= $base_indent . PDebug::$ELEMENT_TOGGLE_CLOSE;

			array_pop($ref_chain);

			return $myString;
		}

		/** :TODO:
		 *  Special debug case for arrays - recursively output all relevant data.
		 *
		 *  @param	  string	  out		 a reference to the output string to modify
		 *  @param	  array	   var		 the array to recursively debug
		 *  @param	  int		 indent	  number of tab-stops to indent this dump by
		 *  @param	  array	   ref_chain   array of all previously dumped vars (avoids recursion)
		 */
		private static function debug_array($var, $indent, &$ref_chain = null) {
			$base_indent = str_repeat(PDebug::$INDENT_STRING, $indent);
			if ($ref_chain === null) {
				$ref_chain = array();
			}
			$myString = $base_indent;

			if (!PProtocolHandler::$USE_HTML) {
				// pad to 7 chars cos 'boolean' is the longest
				$var_type = str_pad(strtoupper(gettype($var)), 7, ' ', STR_PAD_RIGHT) . '} ';
			} else {
				$var_type = gettype($var);
			}

			// replace variable info into variable output string, for array itself...
			$array_var_format = str_replace(array('%t', '%v', '%i', '%n'), array($var_type, 'Array', ' (' . sizeof($var) . ' element' . (sizeof($var) == 1 ? '' : 's') . ')', ''), PDebug::$VARIABLE_OUTPUT_FORMAT);
			// ...now replace this variable into the opening element toggle, rest of the wildcards should be preprocessed (see code below class def'n)
			$myString .= str_replace(array('%t', '%v'), array($array_var_format, 'array'), PDebug::$ARRAY_TOGGLE_OPEN);

			foreach ($var as $k => $v) {
				$myString .= $base_indent.PDebug::$INDENT_STRING;

				$key_dbg	= PDebug::getDebugFor($k, $indent + 1, $ref_chain);
				$value_dbg  = PDebug::getDebugFor($v, $indent + 1, $ref_chain);

				$myString .= str_replace(array('%k', '%v'), array($key_dbg, $value_dbg), PDebug::$ARRAY_ITEM);
			}
			$myString .= $base_indent . PDebug::$ARRAY_TOGGLE_CLOSE;

			return $myString;
		}

		/** :TODO:
		 *  Debug an 'unknown variable' type or scalar datatype
		 *
		 *  @param	  string	  out		 a reference to the output string to modify
		 *  @param	  mixed	   	  var		 the variable to debug
		 *  @param	  int		  indent	 number of tab-stops to indent this dump by
		 */
		private static function debug_var($var, $indent) {
			if ($var === null) {
				$var = $var_type = 'null';
			} else if (is_bool($var)) {
				$var_type = 'boolean';
				$var = $var ? 'true' : 'false';
			} else if (is_int($var)) {
				$var_type = 'integer';
			} else if (is_float($var)) {
				$var_type = 'float';
			} else if (is_string($var)) {
				// throw some quotes in there & escape entities for strings
				$var = PProtocolHandler::htmlSafeString($var);
				if (PProtocolHandler::$TRANSLATE_STRING_PATHS_IN_HTML) {
					$var = PProtocolHandler::translatePathsInString($var);
				}
				$var = '"' . $var . '"';
				$var_type = 'string';
			} else {
				// this should never happen!
				print 'WARNING: debug_var called on unsupported datatype?!';
				return;
			}

			if (!PProtocolHandler::$USE_HTML) {
				// pad to 7 chars cos 'boolean' is the longest
				$var_type = str_pad(strtoupper($var_type), 7, ' ', STR_PAD_RIGHT);
			}

			return str_replace(array('%t', '%v', '%i', '%n'), array($var_type, $var, '', ''), PDebug::$VARIABLE_OUTPUT_FORMAT);
		}

		//======================================================================================
		//======================================================================================
		//======================================================================================

		public static function __error() {

			// continue execution for errors supressed with '@'
			if (error_reporting() == 0) {
				return;
			}

			$arg_list	  = func_get_args();

			$type		  = $arg_list[0];
			$error_message = $arg_list[1];
			$file_name	 = $arg_list[2];
			$line_number   = $arg_list[3];
			$data		  = $arg_list[4];

			/*else {
				// caught exception
				$exc = func_get_arg(0);
				$errno = $exc->getCode();
				$errstr = $exc->getMessage();
				$errfile = $exc->getFile();
				$errline = $exc->getLine();

				$backtrace = $exc->getTrace();
			}*/

			$error_types = array (
					   E_ERROR				=> 'ERROR',
					   E_WARNING			=> 'WARNING',
					   E_PARSE			 	=> 'PARSING ERROR',
					   E_NOTICE				=> 'NOTICE',
					   E_CORE_ERROR			=> 'CORE ERROR',
					   E_CORE_WARNING		=> 'CORE WARNING',
					   E_COMPILE_ERROR		=> 'COMPILE ERROR',
					   E_COMPILE_WARNING	=> 'COMPILE WARNING',
					   E_USER_ERROR			=> 'USER ERROR',
					   E_USER_WARNING		=> 'USER WARNING',
					   E_USER_NOTICE		=> 'USER NOTICE',
					   E_STRICT				=> 'STRICT NOTICE',
					   E_RECOVERABLE_ERROR  => 'RECOVERABLE ERROR'
				  );

			// create error message
			if (array_key_exists($type, $error_types)) {
				$err = $error_types[$type];
			} else {
				$err = 'CAUGHT EXCEPTION';
			}

			$err_msg = str_replace(array('%e', '%f', '%d'), array($err, PProtocolHandler::translatePathsIn($file_name, $line_number), $error_message), PDebug::$ERROR_FORMAT);

			$trace = PDebug::$USE_STACK_TRACE ? PDebug::trace() : '';

			list($header_extra, $footer_extra) = PDebug::verifyHeaderIncludes();

			// what to do
			switch ($type) {
				case E_NOTICE:
				case E_USER_NOTICE:
				case E_STRICT:
				case E_WARNING:
				case E_USER_WARNING:
					PDebug::__errorOutput($header_extra . $err_msg . $footer_extra . $trace);
					return;
				default:
					exit(PDebug::__errorOutput($header_extra . $err_msg . $footer_extra . $trace));
			}

		}

		private static function __errorOutput($message) {
			print $message;
		}

	}

//=====================================================================================================================================
//											IDE options reading for class variables
//=====================================================================================================================================

	// set error handler, if desired :NOTE: very experimental
	if ($_PDEBUG_OPTIONS['use_error_handler']) {
		set_error_handler('PDebug::__error');
	}

	// other options
	if (!$_PDEBUG_OPTIONS['debug_start_collapsed']) {
		PDebug::$START_COLLAPSED = false;
	} else {
		PDebug::$START_COLLAPSED = $_PDEBUG_OPTIONS['debug_start_collapsed'];
	}


	// function wrappers for main PDebug class functions to make typing nicer
	if ($_PDEBUG_OPTIONS['enable_debug_function_wrappers']) {

		PDebug::$IGNORE_FUNCTIONS = array(
			'dump',
			'deferdump', 'conditionaldump',
			'dumpminimised', 'dumpmaximised', 'dumptrace', 'dumpnotrace',
			'trace',
			'bench',
		);

		function dump() {
			print PDebug::dump(func_get_args());
		}

		function dumpminimised() {
			print PDebug::dump(func_get_args(), null, true);
		}
		function dumpmaximised() {
			print PDebug::dump(func_get_args(), null, false);
		}
		function dumptrace() {
			print PDebug::dump(func_get_args(), true);
		}
		function dumpnotrace() {
			print PDebug::dump(func_get_args(), false);
		}

		function trace() {
			print PDebug::trace();
		}
		function bench($tag = '') {
			print PDebug::bench($tag);
		}

		// intended to be used once at a time to debug things that occur after being called
		// a certain number of times. Handy for testing an iteration of a loop, for example.
		function deferdump() {
			$args = func_get_args();
			$times = array_shift($args);
			if (!is_array($times)) {
				$times = array($times);
			}
			foreach ($times as $time) {
				if ($time == PDebug::$DEFER_COUNT) {
					print PDebug::dump($args);
				}
			}
			PDebug::$DEFER_COUNT++;
		}
		// only debug arguments if the first argument evaluates to true
		function conditionaldump() {
			$args = func_get_args();
			if (array_shift($args)) {
				print PDebug::dump($args);
			}
		}
	}

	// fallback to default theme if incorrectly configured... hopefully nobody will rename the default...
	if (PProtocolHandler::$USE_HTML) {
		$theme_type = 'html';
		$active_theme = isset($_PDEBUG_OPTIONS['html_theme']) ? $_PDEBUG_OPTIONS['html_theme'] : 'pPHPide';
	} else {
		$theme_type = 'plain';
		$active_theme = isset($_PDEBUG_OPTIONS['plaintext_theme']) ? $_PDEBUG_OPTIONS['plaintext_theme'] : 'pPHPide';
	}

	// set theme properties
	foreach ($_PDEBUG_OPTIONS['DEBUGGER_THEMES'][$theme_type][$active_theme] as $layout_property => $value) {
		// store all configured vars into the class
		PDebug::$$layout_property = $value;
	}
	unset($theme_type);
	unset($active_theme);

	if (isset($_PDEBUG_OPTIONS['auto_stack_trace'])) {
		PDebug::$USE_STACK_TRACE = (bool)$_PDEBUG_OPTIONS['auto_stack_trace'];
	}

}
?>
