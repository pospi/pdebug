<?php
//
//			USAGE:	dump(							 				$var1 [, $var2, $var3, ...]	);
//					deferDump(		$times_to_defer, 				$var1 [, $var2, $var3, ...]	);
//					deferDump(		array($defer1, $defer2, ...), 	$var1 [, $var2, $var3, ...]	);
//					conditionalDump($condition, 					$var1 [, $var2, $var3, ...]	);
//					trace();
//					bench(); 				{...some code happens...}	bench();
//					bench($benchmark_tag);	{...some code happens...}	bench($benchmark_tag_2);
//					bench([$benchmark_tag, true]);	{...some code happens...}	bench([$benchmark_tag, true]);		<-- store this benchmark for use as a separate benching thread later
//					bench([$benchmark_tag, $since]);	{...some code happens...}	bench([$benchmark_tag, $since]);		<-- compute/update time since this previously stored benchmark
//					bench(); 				{...some code happens...}	bench();
//
/*================================================================================
	pdebug - debugger class
	----------------------------------------------------------------------------
	PDebug static Debug class definition, and wrapper methods for easy
	access - @see pdebug.conf.php for configuration options

	Supports IDE protocol handling for seamless debugging, @see
	 http://pospi.spadgos.com/projects/pdebug

	:NOTE: There should be *absolutely* no markup or even TEXT in this class! ALL markup /
			formatting to be generated through string substitution via configuration
			file, for easy configurability.
	----------------------------------------------------------------------------
	Copyright (c) 2008 Sam Pospischil <pospi@spadgos.com>
  ===============================================================================*/

if ($_PDEBUG_OPTIONS['use_debugger']) {

	abstract class PDebug {

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
				 // variables...		// 				{<li>...</li>}
										//			,}
										//		</ul>)</li>}
										// 	</ul></li>

		static $VARIABLES_HEADER;		//	<li>Info for # vars:<ol>	:NOTE: this header & footer are only used when dump()ing multiple variables
		static $VARIABLES_JOINER;		//								joins stuff. If you use <ol>'s, you can get away with a blank string in HTML mode and let it handle the numbering.

		static $OBJECT_FORMAT;			//	<li>Object (stdClass)<ul>
			static $OBJECT_JOINER;		//	{
			static $OBJECT_INDEX;		//
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
			static $ARRAY_KEY_NUMERIC;	//		 {<li><ul>
			static $ARRAY_KEY_STRING;	//
			static $ARRAY_VALUE;		//			{<li>key</li>}
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

		static $INDENT_STRING;
		static $PADDING_CHARACTER;

		static $VARIABLE_OUTPUT_FORMAT;	// <li></li>					:NOTE: common format used in dumping all simple datatypes

		static $STARTUP_STATS_FORMAT;		// format to output the startup statistics for pdebug in. Disable by setting
		static $INTERNAL_CALL_LOG_FORMAT;

		static $COLLAPSED_STRING;

		//===============================

		static $DEBUGGER_STYLES 	= array(PProtocolHandler::MODE_TEXT => array(), PProtocolHandler::MODE_HTML => array(), PProtocolHandler::MODE_JSON => array());

		static $INITIALISATION_LOG_STRING;	// holds initialisation log string accoring to the desired output format, for outputting later

		static $CURRENT_INDENT_STRING = '';		// current indent level string to prepend to lines

		//============================================================================================================================

		// Global wildcards:
		const WC_SUBITEM		= '%s';
		const WC_PATH			= '%p';
		const WC_COLLAPSED_STR	= '%c';
		const WC_COUNTER		= '%n';
		const WC_INDENT			= '%-';

		// Stack wildcards:
		const WC_CLASS			= '%c';
		const WC_OBJECT			= '%o';
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
		const WC_KEY_PADDING	= '%p';
		const WC_INFO 			= '%i';
		const WC_STRING_LINES	= '%l';

		// Error handler wildcards:
		const WC_ERROR 			= '%e';
		const WC_ERROR_MESSAGE	= '%m';

		// Email report wildcards:
		const WC_ERROR_COUNT	= '%e';
		const WC_WARNING_COUNT	= '%w';
		const WC_SERVERNAME		= '%h';

		//============================================================================================================================

		static $USE_STACK_TRACE = true;
		static $SHALLOW_STACK_TRACE = true;
		static $USE_ERROR_HANDLER = false;
		static $IGNORE_ERRLEVELS = 2050; // E_STRICT | E_NOTICE
		static $WARNING_ERRLEVELS = 3754; // E_WARNING | E_NOTICE | E_STRICT | E_CORE_WARNING | E_COMPILE_WARNING | E_USER_WARNING | E_USER_NOTICE;
		static $EMAIL_ERRLEVELS = 32759; // E_ALL ^ (E_NOTICE | E_STRICT)
		static $ERROR_CALLBACK = null;

		static $ADJUST_BENCHMARKER_FOR_DEBUGGER = true;

		static $SHOW_INTERNAL_STATISTICS = true;

		static $IGNORE_FUNCTIONS = array();		// internal functions to exclude from a stack trace (not including class functions)

		static $START_COLLAPSED = false;

		static $FLUSH_ON_OUTPUT = true;

		static $BENCHMARKER_TIME_VALUE_HIGH = 1;
		static $BENCHMARKER_TIME_VALUE_LOW	= 0.0001;
		static $BENCHMARKER_MEM_VALUE_HIGH 	= 1048576;
		static $BENCHMARKER_MEM_VALUE_LOW	= 512;
		static $BENCHMARKER_COLOR_HIGH		= 0xFF0000;
		static $BENCHMARKER_COLOR_LOW		= 0x00AA00;

		static $STACK_COLOR_NEWEST	= 0xFFFF00;
		static $STACK_COLOR_OLDEST	= 0x990000;

		//================================
		// state vars

		static $PDEBUG_BENCH_START = 0;
		static $PDEBUG_LOOP_COUNT = 0;

		static $PDEBUG_BENCH_TIMES = array();	// benchmarks can be flagged to store their values in here, and compared against later
		static $PDEBUG_PREV_BENCH = 0;
		static $PDEBUG_PREV_MEM = 0;
		static $LAST_CALL_TIME = 0;
		static $LAST_MEM_USAGE = 0;

		static $DEFER_COUNT = 0;
		static $ERROR_COUNT = 0;
		static $WARNING_COUNT = 0;
		static $BENCH_COUNT = 0;
		static $TOTAL_DEBUG_TIME = 0.0;

		static $HAS_OUTPUT_HEADER 	= false;	// include common CSS / JS header for debugging HTML output on first call

		static $CAUGHT_OUTPUT = "";				// when error emailing is used, all output is cached here to be sent when the script terminates

		//============================================================================================

		/**
		 *	Dumps a variable(s) information recursively
		 *
		 *	@param	bool	$force_show_trace	Toggle stack trace display
		 *	@param	bool	$force_collapsed 	Force starting node status
		 *  @param  bool	$short_format		Force short variable dump format (use for inline variable debug output etc)
		 *  @param  bool	$skip_headers		If true, don't output debugger header / footer
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

			$num_vars = count($vars);
			$line_pad_length = strlen($num_vars);
			$i = 0;
			foreach ($vars as $var) {
				if ($do_numbering) {
					$out .= str_replace(array(PDebug::WC_INDENT, PDebug::WC_INFO), array(PDebug::$CURRENT_INDENT_STRING, str_pad(++$i, $line_pad_length, ' ', STR_PAD_LEFT)), PDebug::$VARIABLES_JOINER);
				}
				$out .= PDebug::getDebugFor($var, $short_format);
			}

			if ($do_numbering) {
				$out .= str_replace(array(PDebug::WC_INDENT, PDebug::WC_INFO), array(PDebug::$CURRENT_INDENT_STRING, $num_vars), PDebug::$VARIABLES_FOOTER);
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

			$store_call = null;
			$compute_since = null;
			$time_since_output = null;		// for formatBench to display time since stamp
			if (is_array($tag)) {
				$timeSince = $tag[1];
				$tag = $tag[0];

				if ($timeSince === true) {
					// store this stamp into the tag for later when flagged
					$store_call = $tag;
				} else if (is_string($timeSince)) {
					// compute this benchmark difference against the current timestamp stored in this key
					$compute_since = PDebug::$PDEBUG_BENCH_TIMES[$timeSince];
					$time_since_output = $timeSince;
				}
			}

			$benchData = PDebug::getBench(true, $compute_since, $store_call);
			$benchData['since'] = $time_since_output;

			// when passing null this just resets the timer without outputting anything
			if ($tag === null) {
				PDebug::goExternal();
				return '';
			}

			$out = PDebug::formatBench($tag, $benchData, true, $vars);

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
		public static function getBench($internal_call = false, &$since = null, $store = null) {
			if (!$internal_call) {
				PDebug::goInternal();
			}

			if (!PDebug::$PDEBUG_PREV_BENCH) {
				PDebug::$PDEBUG_BENCH_START = $_SERVER['REQUEST_TIME'];
				PDebug::$PDEBUG_PREV_BENCH = microtime(true);
				$time_diff = 0;  // otherwise we'll get some meaningless small offset...
			}

			if (!$since) {
				$previousTime	= PDebug::$PDEBUG_PREV_BENCH;
				$previousMem	= PDebug::$PDEBUG_PREV_MEM;
			} else {
				$previousTime	= $since[0];	//time
				$previousMem	= $since[1];	//mem
			}

			$mem_usage = memory_get_usage();
			$this_call = microtime(true);
			if (!isset($time_diff)) {
				$time_diff = round($this_call - $previousTime, 5);
			}
			$mem_diff  = $mem_usage - $previousMem;

			if (!$since) {
				PDebug::$PDEBUG_PREV_BENCH = $this_call;
				PDebug::$PDEBUG_PREV_MEM = $mem_usage;
			} else {
				$since[0] = $this_call;	//time
				$since[1] = $mem_usage;	//mem
			}

			if (is_string($store)) {
				PDebug::$PDEBUG_BENCH_TIMES[$store] = array(
					$this_call,
					$mem_usage
				);
			}

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
			$line_pad_length = strlen($num_funcs);

			foreach ($stack as $hist => $data) {
				if (!empty($data['class']) && $data['class'] == 'PDebug') {
					$num_funcs--;				// discount this one from the total number of function calls
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
				} else {
					$func_arguments[] = '...';
				}

				$color = PProtocolHandler::Color_getColorBetween(array(0, PDebug::$STACK_COLOR_NEWEST), array($num_funcs, PDebug::$STACK_COLOR_OLDEST), $i++);

				$out .= str_replace(
					array(PDebug::WC_INDENT, PDebug::WC_INFO, PDebug::WC_STACK_ROW_COLOR, PDebug::WC_OBJECT,
						  PDebug::WC_PATH, PDebug::WC_CLASS, PDebug::WC_CALL_TYPE, PDebug::WC_FUNC_NAME, PDebug::WC_SUBITEM),
					array(
						PDebug::$CURRENT_INDENT_STRING,
						str_pad($i, $line_pad_length, ' ', STR_PAD_LEFT),
						$color,
						(isset($data['object'])	? PDebug::getDebugFor($data['object'], true) : ''),
						(isset($data['file']) ? PProtocolHandler::String_translatePathsFor($data['file'], $data['line']) : ''),
						(isset($data['class'])	? $data['class'] : ''),
						(isset($data['type'])	? $data['type']	 : ''),
						$data['function'],
						implode(PDebug::$STACK_JOINER, $func_arguments),
					),
					PDebug::$STACK_LINE);
			}

			$out = str_replace(PDebug::WC_SUBITEM, $out, PDebug::$STACK_FORMAT);

			// print PDebug headers / footers if this is not an external (direct) function call
			$header_extra = $footer_extra = '';
			if (!$internal_call) {
				list($header_extra, $footer_extra) = PDebug::verifyHeaderIncludes(PDebug::$HEADER_BLOCK, PDebug::$FOOTER_BLOCK);
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

			$time_relative = isset($bench_stats['since']) ? $bench_stats['since'] : null;

			// dump variables along with the benchmarker if we have opted to
			$var_extra = array();
			if (is_array($dump_vars)) {
				foreach ($dump_vars as $var) {
					$var_extra[] = PDebug::getDebugFor($var, PProtocolHandler::isOutputtingHtml());
				}
			}
			$var_extra = implode(PDebug::$STACK_JOINER, $var_extra);

			// go back up the stack to get the the first external call
			$trace_index = 0;
			while (isset($trace[$trace_index]) && ( (isset($trace[$trace_index]['class']) && $trace[$trace_index]['class'] == 'PDebug') || !isset($trace[$trace_index]['file']) )) {
				$trace_index++;
			}

			$trace_call_location_string = '';
			if (isset($trace[$trace_index]['file'])) {
			    $trace_call_location_string = PProtocolHandler::String_translatePathsFor($trace[$trace_index]['file'], $trace[$trace_index]['line']);
			}

			$out = str_replace(
					array(PDebug::WC_INDENT, PDebug::WC_INFO, PDebug::WC_PATH,
						PDebug::WC_BENCH_TIME, PDebug::WC_BENCH_MEM, PDebug::WC_BENCH_TIMEDIFF, PDebug::WC_BENCH_MEMDIFF,
						PDebug::WC_BENCH_TIME_C, PDebug::WC_BENCH_MEM_C, PDebug::WC_BENCH_TIMEDIFF_C, PDebug::WC_BENCH_MEMDIFF_C,
						PDebug::WC_SUBITEM, PDebug::WC_COUNTER),
					array(
						PDebug::$CURRENT_INDENT_STRING,
						($time_relative ? $time_relative : '*') . ' -> ' . $tag,
						$trace_call_location_string,
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
			$color = PProtocolHandler::Color_getColorBetween(array(PDebug::$BENCHMARKER_MEM_VALUE_LOW, PDebug::$BENCHMARKER_COLOR_LOW), array(PDebug::$BENCHMARKER_MEM_VALUE_HIGH, PDebug::$BENCHMARKER_COLOR_HIGH), $usage);

			$usage = number_format($usage / 1024, 3, '.', '');
			if ($usage > 0) {
				$usage = '+' . $usage;
			}

			return array($usage, $color);
		}

		public static function __shadedTime($time) {
			$color = PProtocolHandler::Color_getColorBetween(array(PDebug::$BENCHMARKER_TIME_VALUE_LOW, PDebug::$BENCHMARKER_COLOR_LOW), array(PDebug::$BENCHMARKER_TIME_VALUE_HIGH, PDebug::$BENCHMARKER_COLOR_HIGH), $time);

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
		public static function verifyHeaderIncludes($header_extra = null, $footer_extra = null) {
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
			PDebug::$PDEBUG_LOOP_COUNT = 0;
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

			// also increment the debugger's execution time
			PDebug::$TOTAL_DEBUG_TIME += $dt;
		}

		private static function increaseIndent($add = null) {
			if ($add === null) {
				$add = PDebug::$INDENT_STRING;
			}
			PDebug::$CURRENT_INDENT_STRING .= $add;
		}

		private static function decreaseIndent($remove = null) {
			if ($remove === null) {
				$remove = PDebug::$INDENT_STRING;
			}
			PDebug::$CURRENT_INDENT_STRING = substr(PDebug::$CURRENT_INDENT_STRING, 0, strlen($remove) * -1);
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
		 *  @param		array		ref_chain	array of all previously dumped vars (avoids recursion)
		 */
		public static function getDebugFor($var, $short_format = false, &$ref_chain = null, $recurse_depth = 0) {
			PDebug::sanityCheck();

			$out = '';

			if (is_object($var)) {
				$out = PDebug::debug_object($var, $short_format, $ref_chain, $recurse_depth);
			} else if (is_array($var)) {
				$out = PDebug::debug_array($var, $short_format, $ref_chain, $recurse_depth);
			} else if (is_resource($var)) {
				$out = PDebug::debug_resource($var, $short_format, $ref_chain);
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
		 *  @param		resource		var	 	the resource to debug
		 */
		private static function debug_resource($var, $short_format = false, &$ref_chain = null) {

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
		 *  @param		object	var		 	the object to debug
		 *  @param		array		ref_chain	array of all previously dumped vars (avoids recursion)
		 */
		private static function debug_object($var, $short_format = false, &$ref_chain = null, $recurse_depth = 0) {

			if ($ref_chain === null) {
				$ref_chain = array();
			}

			foreach ($ref_chain as $ref_val) {
				// :TODO: linkage! :D
				if ($ref_val === $var) {
					return str_replace(array(PDebug::WC_INDENT, PDebug::WC_TYPE, PDebug::WC_VAR, PDebug::WC_INFO), array(PDebug::$CURRENT_INDENT_STRING, 'unknown', '* RECURSION *', ''), PDebug::$VARIABLE_OUTPUT_FORMAT);
				}
			}

			$show_contents = !($short_format && PDebug::$SHALLOW_STACK_TRACE && $recurse_depth > 0);
			++$recurse_depth;

			PDebug::increaseIndent();

			// push this object into the active references list, to prevent recursive references
			array_push($ref_chain, $var);

			// cast to array to iterate over private properties
			$avar = (array)$var;

			// show line and file for PHP 5.3 closures
			if ($var instanceof Closure && class_exists('ReflectionFunction')) {
				$closure = new ReflectionFunction($var);
				$avar['__FILE__'] = $closure->getFileName();
				$avar['__LINE__'] = $closure->getStartLine();
			}

			// work out the longest object member string for plaintext padding
			$val_pad_length = 0;
			$type_pad_length = 0;

			$obj_contents = array();
			if ($show_contents) {
				foreach ($avar as $key => $val) {

					$key_type = 'public';
					if ($key{0} == "\0") {					// private or protected var
						$key_parts = explode("\0", $key);
						$key = $key_parts[2];
						$key_type = ($key_parts[1] == '*') ? 'protected' : 'private';
					}

					// Ignore PDebug internal object ID if found - @see PDebug::getObjectUID()
					if ($key == '__pdebugID__') {
						continue;
					}

					$key_display_length = strlen($key);
					$key_type_display_length = strlen($key_type);
					if ($val_pad_length < $key_display_length) {
						$val_pad_length = $key_display_length;
					}
					if ($type_pad_length < $key_type_display_length) {
						$type_pad_length = $key_type_display_length;
					}

					$obj_contents[] = array($key, $key_type, $val);
				}

				foreach ($obj_contents as $idx => $member_tuple) {

					if (PDebug::$PADDING_CHARACTER) {
						$member_tuple[0] = $member_tuple[0] . str_repeat(PDebug::$PADDING_CHARACTER, ($val_pad_length - strlen($member_tuple[0])));
						$member_tuple[1] = str_repeat(PDebug::$PADDING_CHARACTER, ($type_pad_length - strlen($member_tuple[1]))) . $member_tuple[1];
					}

					$obj_contents[$idx] = str_replace(array(PDebug::WC_INDENT, PDebug::WC_KEY, PDebug::WC_INFO), array(PDebug::$CURRENT_INDENT_STRING, $member_tuple[0], $member_tuple[1]), PDebug::$OBJECT_INDEX)
										. str_replace(array(PDebug::WC_INDENT, PDebug::WC_VAR), array(PDebug::$CURRENT_INDENT_STRING, PDebug::getDebugFor($member_tuple[2], $short_format, $ref_chain, $recurse_depth)), PDebug::$OBJECT_MEMBER);
				}
			}

			$compress = PDebug::$START_COLLAPSED || $short_format || sizeof($obj_contents) == 0;

			// this MUST be done *after* all previous recursed calls are complete or the server will explode when it encounters a recursive reference
			array_pop($ref_chain);

			PDebug::decreaseIndent();

			return str_replace(array(PDebug::WC_INDENT, PDebug::WC_INFO, PDebug::WC_SUBITEM, PDebug::WC_COLLAPSED_STR),
							   array(PDebug::$CURRENT_INDENT_STRING,
									 get_class($var),
									 ($show_contents && (!$compress || PProtocolHandler::isOutputtingHtml()) ? PDebug::$OBJECT_JOINER . implode(PDebug::$OBJECT_JOINER, $obj_contents) . PDebug::$OBJECT_JOINER : ''),
									 ($compress ? PDebug::$COLLAPSED_STRING : '')
								),
							   PDebug::$OBJECT_FORMAT);
		}

		/**
		 * Returns a unique identifier for an object instance.
		 * This is used in HTML output so that multiple dumps of the same object
		 * do not require any extra output to be sent - instead, javascript is used to load their contents for debugging
		 */
		private static function getObjectUID($object) {
			if (!isset($object->__pdebugID__)) {
				$object->__pdebugID__ = uniqid("pd");
			}
			return $object->__pdebugID__;
		}

		/**
		 *  Special debug case for arrays - recursively output all relevant data.
		 *
		 *  @param		array		var			the array to recursively debug
		 *  @param		array		ref_chain	array of all previously dumped vars (avoids recursion)
		 */
		private static function debug_array($var, $short_format = false, &$ref_chain = null, $recurse_depth = 0) {

			// initialise reference chain if not set (initial call)
			// this prevents recursive object / array loops from making the debugger explode
			if ($ref_chain === null) {
				$ref_chain = array();
			}

			$show_contents = !($short_format && PDebug::$SHALLOW_STACK_TRACE && $recurse_depth > 0);
			++$recurse_depth;

			PDebug::increaseIndent();

			if (PProtocolHandler::isOutputtingHtml()) {
				$var_type = gettype($var);
			} else {
				// pad to 7 chars cos 'boolean' is the longest
				$var_type = str_pad(strtoupper('Array'), 7, ' ', STR_PAD_RIGHT);
			}

			// length offsets for numeric / string keys, based on template extra
			$numeric_key_extra	= PProtocolHandler::String_getDisplayWidth(str_replace(array(PDebug::WC_INDENT, PDebug::WC_KEY), array('', ''), PDebug::$ARRAY_KEY_NUMERIC));
			$string_key_extra	= PProtocolHandler::String_getDisplayWidth(str_replace(array(PDebug::WC_INDENT, PDebug::WC_KEY), array('', ''), PDebug::$ARRAY_KEY_STRING));

			// work out the longest array key string for plaintext padding
			$max_string_length = 0;
			$max_numeric_length = 0;

			$arr_contents = array();
			$contains_string_keys = false;
			$contains_numeric_keys = false;

			if ($show_contents) {
				foreach ($var as $k => $v) {

					$line_length = strlen($k);
					$key_is_string = is_string($k);

					if ($key_is_string) {
						$contains_string_keys = true;
						if ($max_string_length < $line_length) {
							$max_string_length = $line_length;
						}
					} else {
						$contains_numeric_keys = true;
						if ($max_numeric_length < $line_length) {
							$max_numeric_length = $line_length;
						}
					}

					$arr_contents[] = array($k, $v, ($key_is_string ? PDebug::$ARRAY_KEY_STRING : PDebug::$ARRAY_KEY_NUMERIC));
				}
			}

			$line_pad_length	= max($max_string_length, $max_numeric_length);
			$strings_longer		= $max_string_length > $max_numeric_length;

			$numeric_array = !$contains_string_keys;

			$this_line_padding = "";
			if ($show_contents) {
				foreach ($arr_contents as $idx => $array_pair_info) {
					if (PDebug::$PADDING_CHARACTER) {
						$this_pad_length = $line_pad_length;
						if (is_string($array_pair_info[0])) {
							$this_pad_length += $contains_numeric_keys && !$strings_longer ? ($numeric_key_extra - $string_key_extra) : 0;
						} else {
							$this_pad_length += $contains_string_keys && $strings_longer ? ($string_key_extra - $numeric_key_extra) : 0;
						}
						$this_line_padding = str_repeat(($numeric_array ? ' ' : PDebug::$PADDING_CHARACTER), max(0, $this_pad_length - strlen($array_pair_info[0])));
					}

					$arr_contents[$idx] = str_replace(array(PDebug::WC_INDENT, PDebug::WC_KEY_PADDING, PDebug::WC_KEY), array(PDebug::$CURRENT_INDENT_STRING, $this_line_padding, $array_pair_info[0]), $array_pair_info[2])
										. str_replace(array(PDebug::WC_INDENT, PDebug::WC_VAR), array(PDebug::$CURRENT_INDENT_STRING, PDebug::getDebugFor($array_pair_info[1], $short_format, $ref_chain, $recurse_depth)), PDebug::$ARRAY_VALUE);
				}
			}

			$arr_size = sizeof($var);

			$compress = PDebug::$START_COLLAPSED || $short_format || $arr_size == 0;
			$draw_contents = $show_contents && (!$compress || (PProtocolHandler::isOutputtingHtml() && !PProtocolHandler::$OUTPUT_HTML_AS_PLAIN));

			PDebug::decreaseIndent();

			return str_replace(array(PDebug::WC_INDENT, PDebug::WC_INFO, PDebug::WC_SUBITEM, PDebug::WC_COLLAPSED_STR), array(($arr_size > 0 ? PDebug::$CURRENT_INDENT_STRING : ''), $arr_size, ($draw_contents ? PDebug::$ARRAY_JOINER . implode(PDebug::$ARRAY_JOINER, $arr_contents) . PDebug::$ARRAY_JOINER : ''), ($compress ? PDebug::$COLLAPSED_STRING : '') ), PDebug::$ARRAY_FORMAT);
		}

		/**
		 *  Debug a scalar datatype
		 *  I've chosen to output warnings if anything else gets in here, because getDebugFor() should
		 *  handle switching for all other cases.
		 *
		 *  @param		mixed		 var			the variable to debug
		 *  @param	bool		short_format	whether or not to output in brief format (as in stack)
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

				if ($short_format && $line_count > 1) {
					$var = $string_length > 20 ? substr($var, 0, 20) . '...' : $var;
					$var = preg_replace(PProtocolHandler::$LINE_ENDING_REGEX, '', $var);
				}
				$modified_var = PProtocolHandler::String_htmlSafe($var);

				if (PProtocolHandler::$TRANSLATE_STRING_PATHS_IN_HTML) {
					$modified_var = PProtocolHandler::String_translatePathsIn($modified_var);
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
					return str_replace(array(PDebug::WC_INDENT, PDebug::WC_INFO,	PDebug::WC_VAR), array(PDebug::$CURRENT_INDENT_STRING, $string_length, PProtocolHandler::String_htmlSafe($var)), PDebug::$SINGLELINE_STRING_FORMAT);
				} else {
					PDebug::increaseIndent();

					$line_pad_length = strlen($line_count);
					$modified_var = PProtocolHandler::String_getLines($modified_var);
					$string_lines = array();
					foreach ($modified_var as $line => $text) {
						$string_lines[$line] = str_replace(array(PDebug::WC_INDENT, PDebug::WC_COUNTER, PDebug::WC_VAR), array(PDebug::$CURRENT_INDENT_STRING, str_pad($line, $line_pad_length, ' ', STR_PAD_LEFT), $text), PDebug::$MULTILINE_STRING_LINE);
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

			$arg_list		= func_get_args();

			if (count($arg_list) != 1) {
				$type			= $arg_list[0];
				$error_message	= $arg_list[1];
				$file_name		= $arg_list[2];
				$line_number	= $arg_list[3];
				$data			= $arg_list[4];
			} else {
				list($type, $error_message, $file_name, $line_number, $trace) = PDebug::__exception($arg_list[0]);
			}

			if ($type & PDebug::$IGNORE_ERRLEVELS) {
				return;
			}

			$error_types = array (
				E_ERROR				=> 'ERROR',
				E_WARNING			=> 'WARNING',
				E_NOTICE			=> 'NOTICE',
				E_STRICT			=> 'STRICT NOTICE',

				E_PARSE			 	=> 'PARSING ERROR',
				E_CORE_ERROR		=> 'CORE ERROR',
				E_CORE_WARNING		=> 'CORE WARNING',
				E_COMPILE_ERROR		=> 'COMPILE ERROR',
				E_COMPILE_WARNING	=> 'COMPILE WARNING',
				E_USER_ERROR		=> 'USER ERROR',
				E_USER_WARNING		=> 'USER WARNING',
				E_USER_NOTICE		=> 'USER NOTICE',
				4096				=> 'RECOVERABLE ERROR',
				8192				=> 'DEPRECATED',
				16384				=> 'USER DEPRECATED',
			);

			// create error message
			if (array_key_exists($type, $error_types)) {
				$err = $error_types[$type];
				// pretend we're from a backtrace call so we don't get headers output
				$trace = PDebug::$USE_STACK_TRACE ? PDebug::trace(true) : '';
			} else {
				$err = 'CAUGHT EXCEPTION';
				$trace = PDebug::readableBacktrace($trace, true);
			}

			if (PDebug::__errorIsNonCritical($type)) {
				$error_index = PDebug::$WARNING_COUNT++;
			} else {
				$error_index = PDebug::$ERROR_COUNT++;
			}

			$err_msg = str_replace(array(PDebug::WC_INDENT, PDebug::WC_ERROR, PDebug::WC_PATH, PDebug::WC_ERROR_MESSAGE, PDebug::WC_COUNTER), array(PDebug::$CURRENT_INDENT_STRING, $err, PProtocolHandler::String_translatePathsFor($file_name, $line_number), $error_message, $error_index), PDebug::$ERROR_FORMAT);

			list($header_extra, $footer_extra) = PDebug::verifyHeaderIncludes(PDebug::$HEADER_BLOCK, PDebug::$FOOTER_BLOCK);

			PDebug::goExternal($err_msg, $err);
			PDebug::__errorOutput($header_extra . $err_msg . $trace . $footer_extra, $type);

			return PDebug::__errorIsNonCritical($type);
		}

		public static function __checkExitStatus() {
			if (PDebug::$USE_ERROR_HANDLER && function_exists('error_get_last')) {
				$error = error_get_last();
				if (in_array($error['type'], array(E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_STRICT))) {
					PDebug::__error($error['type'], $error['message'], $error['file'], $error['line'], array());
				}
			}
		}

		// handler for caught exceptions (differs slightly from old PHP errors)
		private static function __exception($exc) {
			return array($exc->getCode(), $exc->getMessage(), $exc->getFile(), $exc->getLine(), $exc->getTrace());
		}

		private static function __errorIsNonCritical($errorCode) {
			return ($errorCode & PDebug::$WARNING_ERRLEVELS) > 0;
		}

		private static function __shouldEmailError($errorCode) {
			return ($errorCode & PDebug::$EMAIL_ERRLEVELS) > 0;
		}

		private static function __errorOutput($message, $errorCode) {
			PDebug::doOutput($message, !PDebug::__shouldEmailError($errorCode));
			if (!PProtocolHandler::$ECHO && isset(PDebug::$ERROR_CALLBACK)) {
				return call_user_func_array(PDebug::$ERROR_CALLBACK, array($errorCode, $message));
			}
		}

		// takes the stored initialisation stats string and adds the debugger execution times into it before returning
		private static function __getInitStats() {
			if (PDebug::$INITIALISATION_LOG_STRING) {
				$times = PDebug::__shadedTime(PDebug::$TOTAL_DEBUG_TIME);
				list($header_extra, $footer_extra) = PDebug::verifyHeaderIncludes(PDebug::$HEADER_BLOCK, PDebug::$FOOTER_BLOCK);

				PDebug::$INITIALISATION_LOG_STRING = str_replace(array(PDebug::WC_BENCH_TIME, PDebug::WC_BENCH_TIME_C), array($times[0], $times[1]), PDebug::$INITIALISATION_LOG_STRING);

				return $header_extra . PDebug::$INITIALISATION_LOG_STRING . $footer_extra;
			}
			return '';
		}

		// method to output pdebug startup statistical string, stored upon loading the library
		public static function __printInitStats() {
			PDebug::goInternal();
			$statsStr = PDebug::__getInitStats();
			if ($statsStr) {
				PDebug::doOutput($statsStr);
			}
			PDebug::goExternal();
		}

		// method to email all output generated by the debugger
		public static function __emailAllOutput() {
			if (strlen(PDebug::$CAUGHT_OUTPUT) == 0) {
				return;
			}
			PDebug::goInternal();

			if (PProtocolHandler::$RECIPIENT_EMAIL) {
				if (PDebug::$ERROR_COUNT > 0) {
					$subject = PProtocolHandler::$EMAIL_SUBJECT_ERR;
				} else if (PDebug::$WARNING_COUNT > 0) {
					$subject = PProtocolHandler::$EMAIL_SUBJECT_WARN;
				} else {
					$subject = PProtocolHandler::$EMAIL_SUBJECT_NORM;
				}

				$subject = str_replace(
					array(Pdebug::WC_ERROR_COUNT, PDebug::WC_WARNING_COUNT, PDebug::WC_SERVERNAME),
					array(PDebug::$ERROR_COUNT, PDebug::$WARNING_COUNT, (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME']) . $_SERVER['PHP_SELF']),
					$subject
				);

				$statusLine = PDebug::__getInitStats();

				PProtocolHandler::sendMail($subject, $statusLine . PDebug::$CAUGHT_OUTPUT);
			}
			PDebug::goExternal();
		}

		public static function ignoreInitStats() {
			PDebug::goInternal();
			PDebug::$INITIALISATION_LOG_STRING = '';
			PDebug::goExternal();
		}

		// an output wrapper which sends any output to the configured place(s)
		public static function doOutput($string, $skipEmail = false) {
			if (PProtocolHandler::$ECHO) {
				print $string;
				PDebug::flush();
			}
			if (!$skipEmail && PProtocolHandler::$RECIPIENT_EMAIL) {
				PDebug::$CAUGHT_OUTPUT .= $string;
			}
		}

		public static function flush() {
			if (PDebug::$FLUSH_ON_OUTPUT) {
				flush();
			}
		}
	}

//=====================================================================================================================================
//											IDE options reading for class variables
//=====================================================================================================================================

	// set error handler, if desired
	if ($_PDEBUG_OPTIONS['use_error_handler']) {
		PDebug::$USE_ERROR_HANDLER = true;
		set_error_handler(array('PDebug', '__error'));
		set_exception_handler(array('PDebug', '__error'));
		register_shutdown_function(array('PDebug', '__checkExitStatus'));
	}

	// set emailer callback, if desired
	if (PProtocolHandler::$RECIPIENT_EMAIL) {
		register_shutdown_function(array('PDebug', '__emailAllOutput'));
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
			PDebug::doOutput(PDebug::dump(func_get_args()));
			PDebug::flush();
		}

		function dumpsmall() {
			PDebug::doOutput(PDebug::dump(func_get_args(), false, true));
			PDebug::flush();
		}
		function dumpbig() {
			PDebug::doOutput(PDebug::dump(func_get_args(), null, false));
			PDebug::flush();
		}

		function trace() {
			PDebug::doOutput(PDebug::trace());
			PDebug::flush();
		}
		function bench() {
			$vars = func_get_args();
			$tag  = array_shift($vars);
			PDebug::doOutput(PDebug::bench($tag, $vars));
			PDebug::flush();
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
					PDebug::doOutput(PDebug::dump($args));
					PDebug::flush();
				}
			}
			PDebug::$DEFER_COUNT++;
		}
		// only debug arguments if the first argument evaluates to true
		function conditionaldump() {
			$args = func_get_args();
			if (array_shift($args)) {
				PDebug::doOutput(PDebug::dump($args));
				PDebug::flush();
			}
		}
	}

	// store all rendering data for the currently selected theme in the class, so the output mode can be changed mid-page
	$html_theme = isset($_PDEBUG_OPTIONS['html_theme']) ? $_PDEBUG_OPTIONS['html_theme'] : 'pdebug';

	if ($html_theme == 'plaintext') {
		foreach ($_PDEBUG_OPTIONS['DEBUGGER_THEMES'][PProtocolHandler::MODE_TEXT] as $layout_property => $value) {
			PDebug::$DEBUGGER_STYLES[PProtocolHandler::MODE_HTML][$layout_property] = $value;
		}
		PDebug::$DEBUGGER_STYLES[PProtocolHandler::MODE_HTML]['HEADER_BLOCK'] = '<pre>' . PDebug::$DEBUGGER_STYLES[PProtocolHandler::MODE_HTML]['HEADER_BLOCK'];
		PDebug::$DEBUGGER_STYLES[PProtocolHandler::MODE_HTML]['FOOTER_BLOCK'] .= '</pre>';
		PProtocolHandler::$OUTPUT_HTML_AS_PLAIN = true;
	} else {
		foreach ($_PDEBUG_OPTIONS['DEBUGGER_THEMES'][PProtocolHandler::MODE_HTML] as $layout_property => $value) {
			PDebug::$DEBUGGER_STYLES[PProtocolHandler::MODE_HTML][$layout_property] = $value;
		}
	}
	foreach ($_PDEBUG_OPTIONS['DEBUGGER_THEMES'][PProtocolHandler::MODE_TEXT] as $layout_property => $value) {
		PDebug::$DEBUGGER_STYLES[PProtocolHandler::MODE_TEXT][$layout_property] = $value;
	}
	foreach ($_PDEBUG_OPTIONS['DEBUGGER_THEMES'][PProtocolHandler::MODE_JSON] as $layout_property => $value) {
		PDebug::$DEBUGGER_STYLES[PProtocolHandler::MODE_JSON][$layout_property] = $value;
	}
	unset($html_theme);

	if (isset($_PDEBUG_OPTIONS['ignore_types'])) {
		PDebug::$IGNORE_ERRLEVELS = intval($_PDEBUG_OPTIONS['ignore_types']);
	}
	if (isset($_PDEBUG_OPTIONS['warning_types'])) {
		PDebug::$WARNING_ERRLEVELS = intval($_PDEBUG_OPTIONS['warning_types']);
	}
	if (isset($_PDEBUG_OPTIONS['email_types'])) {
		PDebug::$EMAIL_ERRLEVELS = intval($_PDEBUG_OPTIONS['email_types']);
	}
	if (isset($_PDEBUG_OPTIONS['auto_stack_trace'])) {
		PDebug::$USE_STACK_TRACE = (bool)$_PDEBUG_OPTIONS['auto_stack_trace'];
	}
	if (isset($_PDEBUG_OPTIONS['shallow_stack_trace'])) {
		PDebug::$SHALLOW_STACK_TRACE = (bool)$_PDEBUG_OPTIONS['shallow_stack_trace'];
	}
	if (isset($_PDEBUG_OPTIONS['adjust_benchmarker_for_debugger'])) {
		PDebug::$ADJUST_BENCHMARKER_FOR_DEBUGGER = (bool)$_PDEBUG_OPTIONS['adjust_benchmarker_for_debugger'];
	}
	if (isset($_PDEBUG_OPTIONS['show_internal_statistics'])) {
		PDebug::$SHOW_INTERNAL_STATISTICS = (bool)$_PDEBUG_OPTIONS['show_internal_statistics'];
	}

	if (isset($_PDEBUG_OPTIONS['flush_on_output'])) {
		PDebug::$FLUSH_ON_OUTPUT = (bool)$_PDEBUG_OPTIONS['flush_on_output'];
	}
	if (isset($_PDEBUG_OPTIONS['error_output_callback'])) {
		PDebug::$ERROR_CALLBACK = $_PDEBUG_OPTIONS['error_output_callback'];
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
