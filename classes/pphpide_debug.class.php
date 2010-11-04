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

	:NOTE: There should be *absolutely* no markup or even TEXT in this class! ALL markup /
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

		static $SINGLELINE_STRING_FORMAT;
		static $MULTILINE_STRING_FORMAT;
			static $MULTILINE_STRING_LINE;
			static $MULTILINE_STRING_JOINER;

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

		static $GENERIC_FORMAT;				// this is used for generic resources
			static $GENERIC_HEADER;
			static $GENERIC_BODY;
			static $GENERIC_FOOTER;
			static $GENERIC_LINE;
			static $GENERIC_CELL;
			static $GENERIC_TITLED_CELL;

			static $GENERIC_LINE_JOINER;
			static $GENERIC_CELL_JOINER;
			static $GENERIC_HEADER_CHARACTER;
			static $GENERIC_BORDER_CHARACTER;

		static $VARIABLES_FOOTER;		//	</ol></li>					:NOTE: this header & footer are only used when dump()ing multiple variables

		static $FOOTER_BLOCK;			// </ul>

		static $INDENT_STRING;			// don't want it indented? turn it off. bitch.

		static $VARIABLE_OUTPUT_FORMAT;	// <li></li>					:NOTE: common format used in dumping all simple datatypes

		static $STARTUP_STATS_FORMAT;	   // format to output the startup statistics for pPHPide in. Disable by setting
		static $INTERNAL_CALL_LOG_FORMAT;

		static $COLLAPSED_STRING;

		//===============================

		static $DEBUGGER_STYLES 	= array(PProtocolHandler::MODE_TEXT => array(), PProtocolHandler::MODE_HTML => array(), PProtocolHandler::MODE_JSON => array());

		static $INITIALISATION_LOG_STRING;  // holds initialisation log string accoring to the desired output format, for outputting later

		static $CURRENT_INDENT_STRING = '';		// current indent level string to prepend to lines

		//===============================

		// Global wildcards:
		const WC_SUBITEM		= '%s';
		const WC_PATH			= '%p';
		const WC_COLLAPSED_STR	= '%c';
		const WC_COUNTER		= '%n';
		const WC_INDENT			= '%-';

		// Stack wildcards:
		const WC_CLASS			= '%c';
		const WC_CALL_TYPE		= '%t';
		const WC_FUNC_NAME		= '%f';
		const WC_STACK_ROW_COLOR = '%cs';

		// Benchmarker wildcards:
		const WC_BENCH_TIME 	= '%t';
		const WC_BENCH_MEM	 	= '%m';
		const WC_BENCH_TIMEDIFF	= '%dt';
		const WC_BENCH_MEMDIFF 	= '%dm';
		const WC_BENCH_TIME_C 		= '%ct';
		const WC_BENCH_MEM_C	 	= '%cm';
		const WC_BENCH_TIMEDIFF_C	= '%cdt';
		const WC_BENCH_MEMDIFF_C 	= '%cdm';

		// Debugger wildcards:
		const WC_TYPE 			= '%t';
		const WC_VAR 			= '%v';
		const WC_KEY 			= '%k';
		const WC_INFO 			= '%i';
		const WC_STRING_LINES	= '%l';

		// Error handler wildcards:
		const WC_ERROR 			= '%e';
		const WC_ERROR_MESSAGE	= '%m';

		//===============================

		static $USE_STACK_TRACE = true;

		static $STRICT_ERROR_HANDLER = false;

		static $ADJUST_BENCHMARKER_FOR_DEBUGGER = true;

		static $SHOW_INTERNAL_STATISTICS = true;

		static $IGNORE_FUNCTIONS = array();		// internal functions to exclude from a stack trace (not including class functions)

		static $START_COLLAPSED = false;

		static $BENCHMARKER_TIME_VALUE_HIGH = 1;
		static $BENCHMARKER_TIME_VALUE_LOW	= 0.0001;
		static $BENCHMARKER_MEM_VALUE_HIGH 	= 1048576;
		static $BENCHMARKER_MEM_VALUE_LOW	= 512;
		static $BENCHMARKER_COLOR_HIGH		= 0xFF0000;
		static $BENCHMARKER_COLOR_LOW		= 0x00AA00;

		static $STACK_COLOR_NEWEST  = 0xFFFF00;
		static $STACK_COLOR_OLDEST  = 0x990000;

		//================================
		// state vars

		static $PDEBUG_BENCH_START = 0;
		static $PDEBUG_LOOP_COUNT = 0;
		static $PDEBUG_PREV_BENCH = 0;
		static $PDEBUG_PREV_MEM = 0;

		static $DEFER_COUNT = 0;
		static $ERROR_COUNT = 0;
		static $BENCH_COUNT = 0;

		static $LAST_CALL_TIME = 0;
		static $LAST_MEM_USAGE = 0;

		static $HAS_OUTPUT_HEADER 	= false;	// include common CSS / JS header for debugging HTML output on first call

		//============================================================================================

		/**
		 *	Dumps a variable(s) information recursively
		 *
		 *  @param  bool	$force_show_trace	Toggle stack trace display
		 *  @param  bool	$force_collapsed 	Force starting node status
		 *  @param  bool	$short_format	   Force short variable dump format (use for inline variable debug output etc)
		 *  @param  bool	$skip_headers	   If true, don't output debugger header / footer
		 *
		 *  @return string
		 */
		public static function dump($vars, $force_show_trace = null, $force_collapsed = null, $short_format = false, $skip_headers = false) {
			PDebug::goInternal();

			$out = '';

			//show the call stack
			if ($force_show_trace || ($force_show_trace === null && PDebug::$USE_STACK_TRACE)) {
				$out .= PDebug::trace(true);
			}

			$start_depth = 0;
			$do_numbering = false;
			if (count($vars) > 1) {
				$out .= str_replace(array(PDebug::WC_INDENT, PDebug::WC_INFO), array(PDebug::$CURRENT_INDENT_STRING, count($vars)), PDebug::$VARIABLES_HEADER);
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
					$out .= str_replace(array(PDebug::WC_INDENT, PDebug::WC_INFO), array(PDebug::$CURRENT_INDENT_STRING, ++$i), PDebug::$VARIABLES_JOINER);
				}
				$out .= PDebug::getDebugFor($var, $short_format);
			}

			if ($do_numbering) {
				$out .= str_replace(array(PDebug::WC_INDENT, PDebug::WC_INFO), array(PDebug::$CURRENT_INDENT_STRING, count($vars)), PDebug::$VARIABLES_FOOTER);
			}

			PDebug::$START_COLLAPSED = $debug_last_collapsed;

			$header_extra = $footer_extra = '';
			if (!$skip_headers) {
				list($header_extra, $footer_extra) = PDebug::verifyHeaderIncludes(PDebug::$HEADER_BLOCK, PDebug::$FOOTER_BLOCK);
			}

			PDebug::goExternal($out, 'dump');

			return $header_extra . $out . $footer_extra;
		}

		/**
		 * Generates a backtrace so that you can easily view the callstack.
		 *
		 * @return string
		 */
		public static function trace($internal_call = false) {
			if (!$internal_call) {
				PDebug::goInternal();
			}

			$stack = debug_backtrace();

			$out = PDebug::readableBacktrace($stack, true);

			// print PDebug headers / footers if this is not an external (direct) function call
		   	$header_extra = $footer_extra = '';
		   	if (!$internal_call) {
				list($header_extra, $footer_extra) = PDebug::verifyHeaderIncludes(PDebug::$HEADER_BLOCK, PDebug::$FOOTER_BLOCK);
				PDebug::goExternal($out, 'trace');
			}

		   	return $header_extra . $out . $footer_extra;
		}

		/**
		 * Benchmarking functions
		 */
		public static function bench($tag = '', $vars = null, $internal_call = false) {
			if (!$internal_call) {
				PDebug::goInternal();
			}

			$out = PDebug::formatBench($tag, PDebug::getBench(true), true, $vars);

		   	// print PDebug headers / footers if this is not an external (direct) function call
		   	$header_extra = $footer_extra = '';
		   	if (!$internal_call) {
				list($header_extra, $footer_extra) = PDebug::verifyHeaderIncludes(PDebug::$HEADER_BLOCK, PDebug::$FOOTER_BLOCK);
				PDebug::goExternal($out, 'bench');
			}

		   	return $header_extra . $out . $footer_extra;
		}

		/**
		 * @return:  [
		 *			current memory usage (bytes)
		 *			current execution time since script start (s)
		 *			memory difference between this call and previous call (bytes)
		 *			time difference between this call and previous call (s)
		 *		]
		 */
		public static function getBench($internal_call = false) {
			if (!$internal_call) {
				PDebug::goInternal();
			}

			if (!PDebug::$PDEBUG_PREV_BENCH) {
				PDebug::$PDEBUG_BENCH_START = $_SERVER['REQUEST_TIME'];
				PDebug::$PDEBUG_PREV_BENCH = microtime(true);
				$time_diff = 0;  // otherwise we'll get some meaningless small offset...
			}

			$mem_usage = memory_get_usage();
			$this_call = microtime(true);
			if (!isset($time_diff)) {
				$time_diff = round($this_call - PDebug::$PDEBUG_PREV_BENCH, 5);
			}
			$mem_diff  = $mem_usage - PDebug::$PDEBUG_PREV_MEM;

			PDebug::$PDEBUG_PREV_BENCH = $this_call;
			PDebug::$PDEBUG_PREV_MEM = $mem_usage;

			if (!$internal_call) {
				PDebug::goExternal();
			}

			return array('m' => $mem_usage, 't' => $this_call, 'dm' => $mem_diff, 'dt' => $time_diff);
		}

		public static function outputAs($mode) {
			PDebug::goInternal();
			PProtocolHandler::outputAs($mode);
			PDebug::refreshThemes();
			PDebug::goExternal();
		}

		//====================================================================================================================================
		//============================================= UTILITY METHODS ======================================================================
		//====================================================================================================================================

		/**
		 * Convert a backtrace array into a nice readable format
		 *
		 * @param  array	stack	backtrace array
		 * @return string
		 */
		public static function readableBacktrace($stack, $internal_call = false) {
			if (!$internal_call) {
				PDebug::goInternal();
			}

			$out = '';

			$i = 0;
			$num_funcs = sizeof($stack);		// for row shading / colouring

			foreach ($stack as $hist => $data) {
				if (!empty($data['class']) && $data['class'] == 'PDebug') {
					$num_funcs--;			   // discount this one from the total number of function calls
					continue;
				}

				// :IMPORTANT: modifying $data['args'] seems to break sometimes when executing from within a class method
				$func_arguments = array();
				if (!in_array($data['function'], array_keys(PDebug::$IGNORE_FUNCTIONS))) {
					if (isset($data['args'])) {
						foreach ($data['args'] as $k => $arg) {
							$func_arguments[$k] = PDebug::getDebugFor($arg, true);
						}
					}
				}

				$color = PProtocolHandler::getColorBetween(array(0, PDebug::$STACK_COLOR_NEWEST), array($num_funcs, PDebug::$STACK_COLOR_OLDEST), $i++);

				$out .= str_replace(
					array(PDebug::WC_INDENT, PDebug::WC_INFO, PDebug::WC_STACK_ROW_COLOR,
						  PDebug::WC_PATH, PDebug::WC_CLASS, PDebug::WC_CALL_TYPE, PDebug::WC_FUNC_NAME, PDebug::WC_SUBITEM),
					array(
						PDebug::$CURRENT_INDENT_STRING, $i, $color,
						(isset($data['file']) ? PProtocolHandler::translatePathsIn($data['file'], $data['line']) : ''),
						(isset($data['class'])	? $data['class'] : ''),
						(isset($data['type'])	? $data['type']	 : ''),
						$data['function'],
						implode(PDebug::$STACK_JOINER, $func_arguments),
					),
					PDebug::$STACK_LINE);
			}

			$header_extra = $footer_extra = '';
			$stack_bits = explode(PDebug::WC_SUBITEM, PDebug::$STACK_FORMAT);
			if (sizeof($stack_bits) == 2) {
				$header_extra = $stack_bits[0];
				$footer_extra = $stack_bits[1];
			}

		   	// print PDebug headers / footers if this is not an external (direct) function call
		   	if (!$internal_call) {
		   		$header_extra = PDebug::$HEADER_BLOCK . $header_extra;
		   		$footer_extra .= PDebug::$FOOTER_BLOCK;
				list($header_extra, $footer_extra) = PDebug::verifyHeaderIncludes($header_extra, $footer_extra);
				PDebug::goExternal($out, 'trace');
			}

		   	return $header_extra . $out . $footer_extra;
		}

		/**
		 * Convert benchmarking data into a nice readable format
		 *
		 * @return string
		 */
		public static function formatBench($tag = '', $bench_stats, $internal_call = false, $dump_vars = null, $diffs_only = false) {
			if (!$internal_call) {
				PDebug::goInternal();
			}

			$trace = debug_backtrace();

			$mem_usage = PDebug::__shadedMem($bench_stats['m']);
			$this_call = PDebug::__shadedTime($bench_stats['t'] - PDebug::$PDEBUG_BENCH_START);
			$mem_diff  = PDebug::__shadedMem($bench_stats['dm']);
			$time_diff = PDebug::__shadedTime($bench_stats['dt']);
			$mem_usage_c = $mem_usage[1];
			$this_call_c = $this_call[1];
			$mem_diff_c  = $mem_diff[1];
			$time_diff_c = $time_diff[1];
			$mem_usage = $mem_usage[0];
			$this_call = $this_call[0];
			$mem_diff  = $mem_diff[0];
			$time_diff = $time_diff[0];

			// dump variables along with the benchmarker if we have opted to
			$var_extra = array();
			if (is_array($dump_vars)) {
				foreach ($dump_vars as $var) {
					$var_extra[] = PDebug::getDebugFor($var, true);
				}
			}
			$var_extra = implode(PDebug::$STACK_JOINER, $var_extra);

			// few levels up the stack to get the the first external call
			$trace_index = $internal_call ? 2 : 1;
			$give_up_after = 10;
			while (!isset($trace[$trace_index]['file']) && --$give_up_after > 0) {
				$trace_index++;
			}

			$out = str_replace(
					array(PDebug::WC_INDENT, PDebug::WC_INFO, PDebug::WC_PATH,
						PDebug::WC_BENCH_TIME, PDebug::WC_BENCH_MEM, PDebug::WC_BENCH_TIMEDIFF, PDebug::WC_BENCH_MEMDIFF,
						PDebug::WC_BENCH_TIME_C, PDebug::WC_BENCH_MEM_C, PDebug::WC_BENCH_TIMEDIFF_C, PDebug::WC_BENCH_MEMDIFF_C,
						PDebug::WC_SUBITEM, PDebug::WC_COUNTER),
					array(
						PDebug::$CURRENT_INDENT_STRING,
						$tag,
						PProtocolHandler::translatePathsIn($trace[$trace_index]['file'], $trace[$trace_index]['line']),
						$this_call, $mem_usage, $time_diff, $mem_diff,
						$this_call_c, $mem_usage_c, $time_diff_c, $mem_diff_c,
						$var_extra,
						(!$diffs_only ? PDebug::$BENCH_COUNT++ : ''),
					),
					($diffs_only ? PDebug::$INTERNAL_CALL_LOG_FORMAT : PDebug::$BENCH_FORMAT));

		   	// print PDebug headers / footers if this is not an external (direct) function call
		   	$header_extra = $footer_extra = '';
		   	if (!$internal_call) {
				list($header_extra, $footer_extra) = PDebug::verifyHeaderIncludes(PDebug::$HEADER_BLOCK, PDebug::$FOOTER_BLOCK);
				PDebug::goExternal($out, 'bench');
			}

		   	return $header_extra . $out . $footer_extra;
		}

		//====================================================================================================================================

		public static function __shadedMem($usage) {
			$color = PProtocolHandler::getColorBetween(array(PDebug::$BENCHMARKER_MEM_VALUE_LOW, PDebug::$BENCHMARKER_COLOR_LOW), array(PDebug::$BENCHMARKER_MEM_VALUE_HIGH, PDebug::$BENCHMARKER_COLOR_HIGH), $usage);

			$usage = number_format($usage / 1024, 3, '.', '');
			if ($usage > 0) {
				$usage = '+' . $usage;
			}

			return array($usage, $color);
		}

		public static function __shadedTime($time) {
			$color = PProtocolHandler::getColorBetween(array(PDebug::$BENCHMARKER_TIME_VALUE_LOW, PDebug::$BENCHMARKER_COLOR_LOW), array(PDebug::$BENCHMARKER_TIME_VALUE_HIGH, PDebug::$BENCHMARKER_COLOR_HIGH), $time);

			$time = number_format($time, 6, '.', '');
			if ($time > 0) {
				$time = '+' . $time;
			}

			return array($time, $color);
		}

		//====================================================================================================================================

		/**
		 * Call this to return wrapper text for an external function call's output,
		 * in the current output mode
		 */
		private static function verifyHeaderIncludes($header_extra = null, $footer_extra = null) {
			$header = $footer = '';

			if (!PDebug::$HAS_OUTPUT_HEADER && PProtocolHandler::isOutputtingHtml()) {
				PDebug::$HAS_OUTPUT_HEADER = true;
				$header = PDebug::$COMMON_HEADER . $header;
			}

			$header .= $header_extra;		// no checks: these should never contain HTML in plaintext mode anyway...
			$footer .= $footer_extra;

			return array($header, $footer);
		}

		private static function goInternal() {
			if (PDebug::$SHOW_INTERNAL_STATISTICS) {
				PDebug::$LAST_CALL_TIME = microtime(true);
				PDebug::$LAST_MEM_USAGE	= memory_get_usage();
			}
		}

		private static function goExternal(&$out = null, $tag = '') {
			$dt = microtime(true) - PDebug::$LAST_CALL_TIME;
			$dm = memory_get_usage() - PDebug::$LAST_MEM_USAGE;

			if (PDebug::$SHOW_INTERNAL_STATISTICS && $out !== null) {
				$dump_stats = array(
					't'	=> microtime(true),
					'm'	=> memory_get_usage(),
					'dt'=> $dt,
					'dm'=> $dm,
				);
				$out = PDebug::formatBench($tag, $dump_stats, true, null, true) . $out;
			}

			// recalculate this to get the revised estimate at the *absolute* end
			$dt = microtime(true) - PDebug::$LAST_CALL_TIME;
			$dm = memory_get_usage() - PDebug::$LAST_MEM_USAGE;

			if (PDebug::$ADJUST_BENCHMARKER_FOR_DEBUGGER && PDebug::$PDEBUG_PREV_BENCH) {
				PDebug::$PDEBUG_PREV_BENCH	-= $dt;
				PDebug::$PDEBUG_PREV_MEM	-= $dm;
			}
		}

		private static function increaseIndent() {
			PDebug::$CURRENT_INDENT_STRING .= PDebug::$INDENT_STRING;
		}

		private static function decreaseIndent() {
			PDebug::$CURRENT_INDENT_STRING = substr(PDebug::$CURRENT_INDENT_STRING, 0, strlen(PDebug::$INDENT_STRING) * -1);
		}

		private static function refreshThemes() {
			foreach (PDebug::$DEBUGGER_STYLES[PProtocolHandler::$CURRENT_OUTPUT_MODE] as $var => $value) {
				PDebug::$$var = $value;
			}
		}

		// dont explode server into infinite looptown™ plz kthx
		private static function sanityCheck() {
			if (++PDebug::$PDEBUG_LOOP_COUNT > 500000) {
				die(PDebug::isOutputtingHtml() ? '<h3>Circular reference detected - aborting!</h3>' : 'Circular reference detected - aborting!');
			}
		}

		//====================================================================================================================================
		//====================================================================================================================================
		//====================================================================================================================================

		/**
		 * This confines all our type-checking into one function, rather than checking for each type
		 * separately in each debug_* function. Plus, it cuts down on needless type checks by ensuring
		 * we only do it once for each variable.
		 *
		 *  @param	  array		ref_chain   array of all previously dumped vars (avoids recursion)
		 */
		private static function getDebugFor($var, $short_format = false, &$ref_chain = null) {
			PDebug::sanityCheck();

			$out = '';

			if (is_object($var)) {
				$out = PDebug::debug_object($var, $short_format, $ref_chain);
			} else if (is_array($var)) {
				$out = PDebug::debug_array($var, $short_format, $ref_chain);
			} else if (is_resource($var)) {
				$out = PDebug::debug_resource($var, $short_format);
			} else {
				$out = PDebug::debug_var($var, $short_format);
			}

			return $out;
		}

		//======================================================================================
		//======================================================================================
		//======================================================================================

		/**
		 *  Debugging for resource datatypes, since PHP performs no debugging for these types itself.
		 *  Implementation for each custom resource type to be added as deemed necessary.
		 *
		 *  @param	  resource		var	 	the resource to debug
		 */
		private static function debug_resource($var, $short_format = false) {

			PDebug::increaseIndent();

			$resource_type = get_resource_type($var);

			// fill with resource content blocks, to implode() later...
			$resource_header_rows = $resource_table_rows = $resource_footer_rows = array();

			// content string to substitute into at the end
			$resource_output_format_str = PDebug::$GENERIC_FORMAT;

			$resource_type_inc = dirname(__FILE__) . '/resource/' . str_replace(' ', '_', $resource_type) . '.inc.php';

			// this will be the length of extra characters in the line after substitutions, to use in
			// includes to calculate the max display length for columns / headers etc
			$line_length_offset = strlen(str_replace(array(PDebug::WC_INDENT, PDebug::WC_TYPE, PDebug::WC_INFO, PDebug::WC_SUBITEM), array('', '', '', ''), PDebug::$GENERIC_LINE));

			if (file_exists($resource_type_inc)) {
				include($resource_type_inc);
			} else {
				$resource_table_rows[] = str_replace(array(PDebug::WC_INDENT, PDebug::WC_TYPE, PDebug::WC_VAR, PDebug::WC_INFO), array(PDebug::$CURRENT_INDENT_STRING, 'unknown', 'UNSUPPORTED RESOURCE TYPE', ''), PDebug::$VARIABLE_OUTPUT_FORMAT);
			}

			$table_contents = array();
			$table_contents[] = str_replace(array(PDebug::WC_INDENT, PDebug::WC_TYPE, PDebug::WC_SUBITEM), array(PDebug::$CURRENT_INDENT_STRING, 'resource', implode(PDebug::$GENERIC_LINE_JOINER, $resource_header_rows)), PDebug::$GENERIC_HEADER)	. (sizeof($resource_table_rows) || sizeof($resource_footer_rows) ? PDebug::$GENERIC_LINE_JOINER : '');
			$table_contents[] = str_replace(array(PDebug::WC_INDENT, PDebug::WC_TYPE, PDebug::WC_SUBITEM), array(PDebug::$CURRENT_INDENT_STRING, 'resource', implode(PDebug::$GENERIC_LINE_JOINER, $resource_table_rows)), 	PDebug::$GENERIC_BODY)		. (sizeof($resource_footer_rows) ? PDebug::$GENERIC_LINE_JOINER : '');
			$table_contents[] = str_replace(array(PDebug::WC_INDENT, PDebug::WC_TYPE, PDebug::WC_SUBITEM), array(PDebug::$CURRENT_INDENT_STRING, 'resource', implode(PDebug::$GENERIC_LINE_JOINER, $resource_footer_rows)), PDebug::$GENERIC_FOOTER);

			$compress = PDebug::$START_COLLAPSED || $short_format;

			PDebug::decreaseIndent();

			return str_replace(array(PDebug::WC_INDENT, PDebug::WC_TYPE, PDebug::WC_INFO, PDebug::WC_SUBITEM, PDebug::WC_COLLAPSED_STR), array(PDebug::$CURRENT_INDENT_STRING, $resource_type, print_r($var, true), (!$compress || PProtocolHandler::isOutputtingHtml() ? PDebug::$GENERIC_LINE_JOINER . implode('', $table_contents) . PDebug::$GENERIC_LINE_JOINER : ''), ($compress ? PDebug::$COLLAPSED_STRING : '') ), $resource_output_format_str);
		}

		/**
		 *  Special debug case for objects - output all relevant data.
		 *
		 *  @param	  object	var		 	the object to debug
		 *  @param	  array		ref_chain   array of all previously dumped vars (avoids recursion)
		 */
		private static function debug_object($var, $short_format = false, &$ref_chain = null) {

			if ($ref_chain === null) {
				$ref_chain = array();
			}

			foreach ($ref_chain as $ref_val) {
				// :TODO: linkage! :D
				if ($ref_val === $var) {
					return str_replace(array(PDebug::WC_INDENT, PDebug::WC_TYPE, PDebug::WC_VAR, PDebug::WC_INFO), array(PDebug::$CURRENT_INDENT_STRING, 'unknown', '* RECURSION *', ''), PDebug::$VARIABLE_OUTPUT_FORMAT);
				}
			}

			PDebug::increaseIndent();

			// push this object into the active references list, to prevent recursive references
			array_push($ref_chain, $var);

			// cast to array to iterate over private properties
			$avar = (array)$var;

			$obj_contents = array();
			foreach ($avar as $key => $val) {

				$key_type = 'public';
				if ($key{0} == "\0") {					// private or protected var
					$key_parts = explode("\0", $key);
					$key = $key_parts[2];
					$key_type = ($key_parts[1] == '*') ? 'protected' : 'private';
				}

				$value_dbg  = PDebug::getDebugFor($val, $short_format, $ref_chain);

				$obj_contents[] = str_replace(array(PDebug::WC_INDENT, PDebug::WC_KEY, PDebug::WC_INFO, PDebug::WC_VAR), array(PDebug::$CURRENT_INDENT_STRING, $key, $key_type, $value_dbg), PDebug::$OBJECT_MEMBER);
			}

			array_pop($ref_chain);

			$compress = PDebug::$START_COLLAPSED || $short_format || sizeof($obj_contents) == 0;

			PDebug::decreaseIndent();

			return str_replace(array(PDebug::WC_INDENT, PDebug::WC_INFO, PDebug::WC_SUBITEM, PDebug::WC_COLLAPSED_STR), array(PDebug::$CURRENT_INDENT_STRING, get_class($var), (!$compress || PProtocolHandler::isOutputtingHtml() ? PDebug::$OBJECT_JOINER . implode(PDebug::$OBJECT_JOINER, $obj_contents) . PDebug::$OBJECT_JOINER : ''), ($compress ? PDebug::$COLLAPSED_STRING : '') ), PDebug::$OBJECT_FORMAT);
		}

		/**
		 *  Special debug case for arrays - recursively output all relevant data.
		 *
		 *  @param	  array	   	var			the array to recursively debug
		 *  @param	  array		ref_chain	array of all previously dumped vars (avoids recursion)
		 */
		private static function debug_array($var, $short_format = false, &$ref_chain = null) {

			// initialise reference chain if not set (initial call)
			// this prevents recursive object / array loops from making the debugger explode
			if ($ref_chain === null) {
				$ref_chain = array();
			}

			PDebug::increaseIndent();

			if (PProtocolHandler::isOutputtingHtml()) {
				$var_type = gettype($var);
			} else {
				// pad to 7 chars cos 'boolean' is the longest
				$var_type = str_pad(strtoupper('Array'), 7, ' ', STR_PAD_RIGHT);
			}

			$arr_contents = array();
			foreach ($var as $k => $v) {
				$key_dbg	= PDebug::getDebugFor($k, $short_format, $ref_chain);
				$value_dbg  = PDebug::getDebugFor($v, $short_format, $ref_chain);

				$arr_contents[] = str_replace(array(PDebug::WC_INDENT, PDebug::WC_KEY, PDebug::WC_VAR), array(PDebug::$CURRENT_INDENT_STRING, $key_dbg, $value_dbg), PDebug::$ARRAY_PAIR);
			}

			$arr_size = sizeof($var);

			$compress = PDebug::$START_COLLAPSED || $short_format || $arr_size == 0;
			$draw_contents = !$compress || (PProtocolHandler::isOutputtingHtml() && !PProtocolHandler::$OUTPUT_HTML_AS_PLAIN);

			PDebug::decreaseIndent();

			return str_replace(array(PDebug::WC_INDENT, PDebug::WC_INFO, PDebug::WC_SUBITEM, PDebug::WC_COLLAPSED_STR), array(($arr_size > 0 ? PDebug::$CURRENT_INDENT_STRING : ''), $arr_size, ($draw_contents ? PDebug::$ARRAY_JOINER . implode(PDebug::$ARRAY_JOINER, $arr_contents) . PDebug::$ARRAY_JOINER : ''), ($compress ? PDebug::$COLLAPSED_STRING : '') ), PDebug::$ARRAY_FORMAT);
		}

		/**
		 *  Debug a scalar datatype
		 *  I've chosen to output warnings if anything else gets in here, because getDebugFor() should
		 *  handle switching for all other cases.
		 *
		 *  @param	  mixed	   	 var			the variable to debug
		 *  @param	bool	   short_format   whether or not to output in brief format (as in stack)
		 */
		private static function debug_var($var, $short_format = false) {

			$modified_var	= $var;

			// only used for strings
			$string_length	= 0;
			$line_count 	= 0;

			if ($var === null) {
				$modified_var = $var_type = 'null';
			} else if (is_bool($var)) {
				$var_type = 'boolean';
				$modified_var = $var ? 'true' : 'false';
			} else if (is_int($var)) {
				$var_type = 'integer';
			} else if (is_float($var)) {
				$var_type = 'float';
			} else if (is_string($var)) {
				// escape entities for strings
				$string_length = strlen($var);

				// get line count
				preg_match_all(PProtocolHandler::$LINE_ENDING_REGEX, $var, $line_count, PREG_PATTERN_ORDER);
				$line_count = sizeof($line_count[0]) + 1;		// total matches of whole pattern

				$modified_var = PProtocolHandler::htmlSafeString($var);
				if ($short_format) {
					$modified_var = strlen($modified_var) > 20 ? substr($modified_var, 0, 20) . '...' : $modified_var;
					$modified_var = preg_replace(PProtocolHandler::$LINE_ENDING_REGEX, '', $modified_var);
				}

				if (PProtocolHandler::$TRANSLATE_STRING_PATHS_IN_HTML) {
					$modified_var = PProtocolHandler::translatePathsInString($modified_var);
				}
				$var_type = 'string';
			} else {
				// this should never happen, in an ideal world!
				$modified_var = print_r($var, true);
				$var_type = 'unknown';
			}

			if (!PProtocolHandler::isOutputtingHtml()) {
				// pad to 7 chars cos 'boolean' is the longest
				$var_type = str_pad(strtoupper($var_type), 7, ' ', STR_PAD_RIGHT);
			}

			if (!is_string($var)) {
				return str_replace(array(PDebug::WC_INDENT, PDebug::WC_TYPE, PDebug::WC_VAR), array(PDebug::$CURRENT_INDENT_STRING, $var_type, $modified_var), PDebug::$VARIABLE_OUTPUT_FORMAT);
			} else {
				if ($short_format || $line_count == 1) {
					return str_replace(array(PDebug::WC_INDENT, PDebug::WC_INFO,	PDebug::WC_VAR), array(PDebug::$CURRENT_INDENT_STRING, $string_length, $modified_var), PDebug::$SINGLELINE_STRING_FORMAT);
				} else {
					PDebug::increaseIndent();

					$modified_var = PProtocolHandler::getStringLines($modified_var);
					$string_lines = array();
					foreach ($modified_var as $line => $text) {
						$string_lines[$line] = str_replace(array(PDebug::WC_INDENT, PDebug::WC_COUNTER, PDebug::WC_VAR), array(PDebug::$CURRENT_INDENT_STRING, $line, $text), PDebug::$MULTILINE_STRING_LINE);
					}

					PDebug::decreaseIndent();
				}
				return str_replace(array(PDebug::WC_INDENT, PDebug::WC_INFO, PDebug::WC_SUBITEM, PDebug::WC_STRING_LINES), array(PDebug::$CURRENT_INDENT_STRING, $string_length, PDebug::$MULTILINE_STRING_JOINER . implode(PDebug::$MULTILINE_STRING_JOINER, $string_lines) . PDebug::$MULTILINE_STRING_JOINER, $line_count), PDebug::$MULTILINE_STRING_FORMAT);
			}
		}

		//======================================================================================
		//======================================================================================
		//======================================================================================

		public static function __error() {
			PDebug::goInternal();

			// continue execution for errors supressed with '@'
			if (error_reporting() == 0) {
				return;
			}

			$arg_list	  = func_get_args();

			$type			= $arg_list[0];
			$error_message	= $arg_list[1];
			$file_name		= $arg_list[2];
			$line_number	= $arg_list[3];
			$data			= $arg_list[4];

			if (!PDebug::$STRICT_ERROR_HANDLER && ($type == E_NOTICE || $type == E_STRICT)) {
				return;
			}

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
					   E_NOTICE				=> 'NOTICE',
					   E_STRICT				=> 'STRICT NOTICE',

					   E_PARSE			 	=> 'PARSING ERROR',
					   E_CORE_ERROR			=> 'CORE ERROR',
					   E_CORE_WARNING		=> 'CORE WARNING',
					   E_COMPILE_ERROR		=> 'COMPILE ERROR',
					   E_COMPILE_WARNING	=> 'COMPILE WARNING',
					   E_USER_ERROR			=> 'USER ERROR',
					   E_USER_WARNING		=> 'USER WARNING',
					   E_USER_NOTICE		=> 'USER NOTICE',
					   E_RECOVERABLE_ERROR  => 'RECOVERABLE ERROR',
				  );

			// create error message
			if (array_key_exists($type, $error_types)) {
				$err = $error_types[$type];
			} else {
				$err = 'CAUGHT EXCEPTION';
			}

			$err_msg = str_replace(array(PDebug::WC_INDENT, PDebug::WC_ERROR, PDebug::WC_PATH, PDebug::WC_ERROR_MESSAGE, PDebug::WC_COUNTER), array(PDebug::$CURRENT_INDENT_STRING, $err, PProtocolHandler::translatePathsIn($file_name, $line_number), $error_message, PDebug::$ERROR_COUNT++), PDebug::$ERROR_FORMAT);

			// pretend we're from a backtrace call so we don't get headers output
			$trace = PDebug::$USE_STACK_TRACE ? PDebug::trace(true) : '';

			list($header_extra, $footer_extra) = PDebug::verifyHeaderIncludes(PDebug::$HEADER_BLOCK, PDebug::$FOOTER_BLOCK);

			// what to do
			switch ($type) {
				case E_NOTICE:
				case E_USER_NOTICE:
				case E_STRICT:
				case E_WARNING:
				case E_USER_WARNING:
					PDebug::goExternal($err_msg, $err);
					PDebug::__errorOutput($header_extra . $err_msg . $trace . $footer_extra);
					return;
				default:
					PDebug::goExternal($err_msg, $err);
					exit(PDebug::__errorOutput($header_extra . $err_msg . $trace . $footer_extra));
			}

		}

		private static function __errorOutput($message) {
			print $message;
		}


		// method to output pPHPide startup statistical string, stored upon loading the library
		public static function __printInitStats() {
			PDebug::goInternal();
			if (PDebug::$INITIALISATION_LOG_STRING) {
				list($header_extra, $footer_extra) = PDebug::verifyHeaderIncludes(PDebug::$HEADER_BLOCK, PDebug::$FOOTER_BLOCK);
				print $header_extra . PDebug::$INITIALISATION_LOG_STRING . $footer_extra;
			}
			PDebug::goExternal();
		}

		public static function ignoreInitStats() {
			PDebug::goInternal();
			PDebug::$INITIALISATION_LOG_STRING = '';
			PDebug::goExternal();
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

		// we probably *DO* want to see the place the function was called - but not the parameters...
		PDebug::$IGNORE_FUNCTIONS = array(
			'dump' 				=> true,
			'deferdump'			=> true,
			'conditionaldump'	=> true,
			'dumpsmall'			=> true,
			'dumpbig'			=> true,
			'trace'				=> true,
			'bench'				=> true,
		);

		function dump() {
			print PDebug::dump(func_get_args());
		}

		function dumpsmall() {
			print PDebug::dump(func_get_args(), false, true);
		}
		function dumpbig() {
			print PDebug::dump(func_get_args(), null, false);
		}

		function trace() {
			print PDebug::trace();
		}
		function bench() {
			$vars = func_get_args();
			$tag  = array_shift($vars);
			print PDebug::bench($tag, $vars);
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

	// store all rendering data for the currently selected theme in the class, so the output mode can be changed mid-page
	$html_theme = isset($_PDEBUG_OPTIONS['html_theme']) ? $_PDEBUG_OPTIONS['html_theme'] : 'pPHPide';
	$text_theme = isset($_PDEBUG_OPTIONS['plaintext_theme']) ? $_PDEBUG_OPTIONS['plaintext_theme'] : 'pPHPide';
	$json_theme = isset($_PDEBUG_OPTIONS['json_theme']) ? $_PDEBUG_OPTIONS['json_theme'] : 'pPHPide';

	if ($html_theme == 'plaintext') {
		foreach ($_PDEBUG_OPTIONS['DEBUGGER_THEMES'][PProtocolHandler::MODE_TEXT][$text_theme] as $layout_property => $value) {
			PDebug::$DEBUGGER_STYLES[PProtocolHandler::MODE_HTML][$layout_property] = $value;
		}
		PDebug::$DEBUGGER_STYLES[PProtocolHandler::MODE_HTML]['HEADER_BLOCK'] = '<pre>' . PDebug::$DEBUGGER_STYLES[PProtocolHandler::MODE_HTML]['HEADER_BLOCK'];
		PDebug::$DEBUGGER_STYLES[PProtocolHandler::MODE_HTML]['FOOTER_BLOCK'] .= '</pre>';
		PProtocolHandler::$OUTPUT_HTML_AS_PLAIN = true;
	} else {
		foreach ($_PDEBUG_OPTIONS['DEBUGGER_THEMES'][PProtocolHandler::MODE_HTML][$html_theme] as $layout_property => $value) {
			PDebug::$DEBUGGER_STYLES[PProtocolHandler::MODE_HTML][$layout_property] = $value;
		}
	}
	foreach ($_PDEBUG_OPTIONS['DEBUGGER_THEMES'][PProtocolHandler::MODE_TEXT][$text_theme] as $layout_property => $value) {
		PDebug::$DEBUGGER_STYLES[PProtocolHandler::MODE_TEXT][$layout_property] = $value;
	}
	foreach ($_PDEBUG_OPTIONS['DEBUGGER_THEMES'][PProtocolHandler::MODE_JSON][$json_theme] as $layout_property => $value) {
		PDebug::$DEBUGGER_STYLES[PProtocolHandler::MODE_JSON][$layout_property] = $value;
	}
	unset($html_theme);
	unset($text_theme);
	unset($json_theme);


	if (isset($_PDEBUG_OPTIONS['strict_error_handler'])) {
		PDebug::$STRICT_ERROR_HANDLER = (bool)$_PDEBUG_OPTIONS['strict_error_handler'];
	}
	if (isset($_PDEBUG_OPTIONS['auto_stack_trace'])) {
		PDebug::$USE_STACK_TRACE = (bool)$_PDEBUG_OPTIONS['auto_stack_trace'];
	}
	if (isset($_PDEBUG_OPTIONS['adjust_benchmarker_for_debugger'])) {
		PDebug::$ADJUST_BENCHMARKER_FOR_DEBUGGER = (bool)$_PDEBUG_OPTIONS['adjust_benchmarker_for_debugger'];
	}
	if (isset($_PDEBUG_OPTIONS['show_internal_statistics'])) {
		PDebug::$SHOW_INTERNAL_STATISTICS = (bool)$_PDEBUG_OPTIONS['show_internal_statistics'];
	}

	if (isset($_PDEBUG_OPTIONS['benchmarker_time_value_high'])) {
		PDebug::$BENCHMARKER_TIME_VALUE_HIGH = (float)$_PDEBUG_OPTIONS['benchmarker_time_value_high'];
	}
	if (isset($_PDEBUG_OPTIONS['benchmarker_time_value_low'])) {
		PDebug::$BENCHMARKER_TIME_VALUE_LOW = (float)$_PDEBUG_OPTIONS['benchmarker_time_value_low'];
	}
	if (isset($_PDEBUG_OPTIONS['benchmarker_mem_value_high'])) {
		PDebug::$BENCHMARKER_MEM_VALUE_HIGH = (int)$_PDEBUG_OPTIONS['benchmarker_mem_value_high'];
	}
	if (isset($_PDEBUG_OPTIONS['benchmarker_mem_value_low'])) {
		PDebug::$BENCHMARKER_MEM_VALUE_LOW = (int)$_PDEBUG_OPTIONS['benchmarker_mem_value_low'];
	}
	if (isset($_PDEBUG_OPTIONS['benchmarker_color_high'])) {
		PDebug::$BENCHMARKER_COLOR_HIGH = (int)$_PDEBUG_OPTIONS['benchmarker_color_high'];
	}
	if (isset($_PDEBUG_OPTIONS['benchmarker_color_low'])) {
		PDebug::$BENCHMARKER_COLOR_LOW = (int)$_PDEBUG_OPTIONS['benchmarker_color_low'];
	}
	if (isset($_PDEBUG_OPTIONS['stack_color_newest'])) {
		PDebug::$STACK_COLOR_NEWEST = (int)$_PDEBUG_OPTIONS['stack_color_newest'];
	}
	if (isset($_PDEBUG_OPTIONS['stack_color_oldest'])) {
		PDebug::$STACK_COLOR_OLDEST = (int)$_PDEBUG_OPTIONS['stack_color_oldest'];
	}

	// load theme vars into class, to reduce access overhead - speed is more important than this minor mem usage
	PDebug::outputAs(PProtocolHandler::$CURRENT_OUTPUT_MODE);

}
?>
