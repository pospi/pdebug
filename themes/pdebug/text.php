<?php
$t = array(

	'COMMON_HEADER'	=> '',

	// Generic wildcards:
	// 	%s =	subitem - nests another group of items inside this one
	//			used for array pairs, object members, function arguments, table lines etc
	//  %- =	indentation string appropriate for this level of the output

	// Debugger wildcards:
	//	%t =	variable type
	// 	%v =	simple variable value (using VARIABLE_OUTPUT_FORMAT) / array value	/ object member
	// 	%k =	array key / object member name
	//	%p =	array key padding string
	//	%i =	"info"... object class / array count / string length / resource type / counter variable etc
	//  %c =	collapsed string, if debug_start_collapsed is set (see below:)

	'COLLAPSED_STRING'	  => '',

	'VARIABLE_OUTPUT_FORMAT' => "%v",

	'INDENT_STRING' 		=> "    ",
	'PADDING_CHARACTER'		=> "-",

	'HEADER_BLOCK' 			=> "\n",

	'VARIABLES_HEADER'		=>	" [INFORMATION FOR %i VARS]",		// :NOTE: this header & footer are only used when dump()ing multiple variables
	'VARIABLES_JOINER'		=>	"\n%- #%i : ",

	'SINGLELINE_STRING_FORMAT'	=>	'String (%i chars): "%v"',
	//	%l =	string line count
	'MULTILINE_STRING_FORMAT'	=>	"String (%i chars, %l lines): \"%s%-\"",
	//	%n =	string line number
	//	%v =	string line text
		'MULTILINE_STRING_LINE'	=>	"%-[%n] %v",
		'MULTILINE_STRING_JOINER' => "\n",

	'ARRAY_FORMAT'			=>	"Array (%i elements): [%s%-]",
		'ARRAY_KEY_NUMERIC'	=>		'%-[%p%k] ',
		'ARRAY_KEY_STRING'	=>		'%-["%k"%p] ',
		'ARRAY_VALUE'		=>		'=> %v',			// these two are separated so that padding can be calculated
		'ARRAY_JOINER'		=>		"\n",

	'OBJECT_FORMAT'			=>	"%i Object: {%s%-}",
		'OBJECT_INDEX'		=>		'%-[%i:%k] ',
		'OBJECT_MEMBER'		=>		':= %v',			// these two are separated so that padding can be calculated
		'OBJECT_JOINER'		=>		"\n",

	'GENERIC_FORMAT'		=>	"%t [%i]: (%s%-)",
		'GENERIC_HEADER'	=>		"%s",
		'GENERIC_BODY'		=>		"%s",
		'GENERIC_FOOTER'	=>		"%s",
		//	%i in this case is intended as a 'spin' variable for table row styling etc
		'GENERIC_LINE'		=>		'%-| %s |',
		'GENERIC_CELL'		=>		'%v',
		'GENERIC_TITLED_CELL'	=>	'%v',	// title isn't really available in plaintext, so this is just the same string as above
		'GENERIC_LINE_JOINER'	=>	"\n",
		'GENERIC_CELL_JOINER'	=>	" | ",
	'GENERIC_HEADER_CHARACTER' => '-',
	'GENERIC_BORDER_CHARACTER' => '=',

	'VARIABLES_FOOTER'		=>	"\n [%i VARS DEBUGGED]\n",			// :NOTE: this header & footer are only used when dump()ing multiple variables

	'FOOTER_BLOCK' 			=> "\n",

	// Benchmarker wildcards:
	//	%i	=	benchmark tag
	//  %n  =   benchmark call number
	// 	%p	=	file path
	//	%t	=	current execution time (s)		<-- these 4 also have %ct, %cm, %cdt & %cdm for HEX strings for shading in HTML mode
	//	%m	=	current mem usage (KB)
	//	%dt	=	time diff since last call (s)
	//	%dm	=	memory diff since last call (KB)
	//  %s  =   variables to dump
	'BENCH_FORMAT'			=>	" [BENCH %n] %i : %p @ %t sec [%dt sec] %m KB [%dm KB] \n%s",

	// This one is like a benchmark, except that it shows stats for pdebug startup overhead
	// Feel entirely free to disable this with the config var up top if it shits you!
	// :NOTE:
	//  - %t and %m are not used for this, only the overhead is shown.
	//  - %p shows $_SERVER['PHP_SELF'], or server path of the executing script if unavailable
	'STARTUP_STATS_FORMAT'		=>  "[[pdebug loaded]] for %p (in %dt sec / %dm KB, overhead %t sec)\n",
	//	- %p is additionally not used in this one...
	'INTERNAL_CALL_LOG_FORMAT'	=>  "[pdebug invoked: %i] utime %Cu / systime %Cs (%Cdu / %Cds) : (rendered in %dt sec / %dm KB)\n",

	//  %n =	error number (since script start)
	//	%e =	error type
	//	%m =	error message
	// 	%p =	file path
	'ERROR_FORMAT' 			=> " [ERROR %n] %e : %p \n  %m\n",

	//	%s =	combined stack lines
	'STACK_FORMAT'			=>	" STACK: \n%s",
	//	%c = 	class name
	//	%o =	compressed calling object debug
	//	%t = 	call type
	// 	%f =	function name
	// 	%s =	function arguments
	// 	%p =	file path
	//  %i =   function call number
		'STACK_LINE'		=> 		"  [%i] %p : %c%t%f(%s)\n",
		'STACK_JOINER'		=> 		", ",
);

?>
