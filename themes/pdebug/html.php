<?php
$t = array(
	
	'COMMON_HEADER'	=> <<<EOT
<style type="text/css">
/* general debugger layout */
	.PDebug { white-space: normal; font-family: "trebuchet ms",helvetica,tahoma,sans-serif; font-size: 10px; line-height: 11px; background: #F0F6FF; margin: 0; padding: 3px; border-radius: 5px; -moz-border-radius: 5px; -webkit-border-radius: 5px; border: 1px solid #E18E03; margin: 0px; display: block; }
	.PDebug li { list-style: none; line-height: 18px; }
	.PDebug ul { white-space: normal; padding: 0; margin: 0 2em; min-width: 200px; }
	.PDebug .info { font-weight: bold; color: #E18E03; }
	.PDebug .info * { font-weight: normal; }
	.PDebug .info ol.vars li { color: #000; list-style: decimal-leading-zero; }	/* these are only used for variable counter display */
	.PDebug .info ol.vars li li { list-style: none; }
	.PDebug .alt { background: #F0F0FF; }										/* alternate table row colouring, etc */
/* stack trace styles */
	.PDebug .stack ul li ul, .PDebug .stack ul li ul li { margin: 0; padding: 0; display: inline; }
	.PDebug .err ul li ul, .PDebug .err ul li ul li { margin: 0; padding: 0; display: inline; }
	.PDebug .bench>ul, .PDebug .bench>ul>li { margin: 0; display: inline; }
	.PDebug .stack ul li ul li ul { display: block; }
	.PDebug .stack ul li { padding: 1px; margin-bottom: 1px; }
	.PDebug .err ul li ul li ul { display: block; }
	.PDebug .bench ul li ul { display: block; }
/* file linkage */
	.PDebug a { text-decoration: none; border: 0; color: #0086CE; }
	.PDebug a:active, .PDebug a:hover { border-style: solid; border-width: 0 0 1px 0; border-color: #0086CE; }
/* type-specific styles */
	.PDebug .array>span>nobr, .PDebug .object>span>nobr, .PDebug .mstring>span>nobr, .PDebug .resource>span>nobr { cursor: pointer; padding: 1px; border: 1px solid; position: relative; z-index: 2; top: 0px; border-radius: 3px; -moz-border-radius: 3px; -webkit-border-radius: 3px; }
	.PDebug .array>span>nobr { 	color: #363; border-color: #7D9E69; background: #F0FFF6; }
	.PDebug .object>span>nobr { color: #33D; border-color: #697D9E; background: #F4F6FF; }
	.PDebug .mstring>span>nobr {	color: #555; border-color: #888; background: #EEE; }
	.PDebug .resource>span>nobr {	color: #FF7F00; border-color: #FF7F00; background: #FFE9C3; }
	.PDebug .resource>span>nobr td.resource { color: #AF3F00; border-color: #FF7F00; }
	.PDebug .array>span>ul, .PDebug .object>span>ul, .PDebug .mstring>span>div {	cursor: pointer; padding: 1px; border: 1px solid; position: relative; z-index: 1; border-radius: 3px; -moz-border-radius: 3px; -webkit-border-radius: 3px; }
	.PDebug .array>span>ul {	color: #363; border-color: #7D9E69; background: #E2F5E2; }
	.PDebug .object>span>ul {	color: #33D; border-color: #697D9E; background: #DBEDFB; }
	.PDebug .mstring>span>div { border-color: #999; background: #E8E8E8; }
	.PDebug .object>span>ul li.joiner, .PDebug .array>span>ul li.joiner { margin: 0 1em; }
	.PDebug .array>span>nobr:hover { background: #D0DFD6; }
	.PDebug .object>span>nobr:hover{ background: #D4D6DF; }
	.PDebug .resource>span>nobr:hover{ background: #FFEFDF; }
	.PDebug .object, .PDebug .array { display: list-item-inline-block; }
	.PDebug .object>span>ul>li:hover {	background: #9BDCFF; }
	.PDebug .array>span>ul>li:hover {	background: #BAEEB3; }
	.PDebug .mstring>span>div>ol>li:hover {	background: #FFF; }
	/* remove contents display for 0 length arrays */
	.PDebug .array.c0>span>ul { display: none; }
	.PDebug .array.c0>span>nobr { cursor: default; }
	/* multiline strings display block-level and are scrollable */
	.PDebug .mstring, .PDebug .string	{ color: #000 }
	.PDebug .mstring ol, .PDebug .string ol {	margin: 0 0 0 2em; display: block; border-color: #888; overflow: visible; padding: 0; margin: 0; width: 100%; } /* border: 1px solid #999; border-radius: 3px; -moz-border-radius: 3px; -webkit-border-radius: 3px; } */
	.PDebug .mstring ol li span, .PDebug .string ol li span, .PDebug .mstring pre { cursor: text; color: #888; white-space: pre; white-space: pre-wrap; word-wrap: break-word; margin: 0; }
	.PDebug .mstring ol li, .PDebug .string ol li { margin: 0 0 0 3.5em; white-space: nowrap; font-family: monospace; list-style: decimal-leading-zero outside !important; }
	.PDebug .mstring div, .PDebug .string div { overflow: auto; }
	.PDebug .mstring nobr span.l { color: #0086CE; text-decoration: underline; cursor: pointer; }
	/* single line strings display inline */
	.PDebug .string ol, .PDebug .string ol li { white-space: normal; border: 0; display: inline; margin: 0; }
	.PDebug .string div { display: inline; }
	.PDebug .boolean span { 	border-color: #00F; color: #00F; }
	.PDebug .null span {		border-color: #555; color: #555; }
	.PDebug .integer span {	border-color: #F00; color: #F00; }
	.PDebug .float span {		border-color: #900; color: #900; }
	.PDebug .unknown span {	border-color: #999; color: #999; }
	.PDebug .array ul li, .PDebug .object ul li	   { list-style: none; }
	.PDebug .array ul>li>li, .PDebug .object ul>li>li { display:inline; position: relative; left: -2em; }
	.PDebug .array ul>li>li>span, .PDebug .object ul>li>li>span { position: relative; left: 1em; text-indent: -3em !important; }
	.PDebug .array ul>li>ul, .PDebug .object ul li>ul { margin-left: 1em; }
	.PDebug .array ul>li>ul>li, .PDebug .object ul>li>ul>li { display: inline; }
	.PDebug .object ul li ul li.member 		{ color: #344F58; font-family: monospace; white-space: pre; }
	.PDebug .array ul>li>ul>li:first-child>span, .PDebug .array ul>li>ul>li:first-child li>span	{ font-family: monospace; white-space: pre; }
	.PDebug .object ul li ul span.private		{ color: #E00; }
	.PDebug .object ul li ul span.public		{ color: #090; }
	.PDebug .object ul li ul span.protected	{ color: #909; }
	.PDebug .resource tr { color: #447; padding: 0; }
	.PDebug .resource td { background: #EBC072; padding: 1px 3px; }
	.PDebug .resource thead td { background: #A88882; color: #FFF; }
	.PDebug .resource tfoot td { background: #A88882; color: #FFF; }
	.PDebug .resource table { margin: 0pt 1em; border: 1px solid #EBC072; border-radius: 3px; -moz-border-radius: 3px; -webkit-border-radius: 3px; }
	.PDebug .resource tr:first-child td:first-child { border-radius-topleft: 3px; -moz-border-radius-topleft: 3px; -webkit-border-radius-topleft: 3px; }
	.PDebug .resource tr:first-child td:last-child  { border-radius-topright: 3px; -moz-border-radius-topright: 3px; -webkit-border-radius-topright: 3px; }
	.PDebug .resource tr:last-child td:first-child  { border-radius-bottomleft: 3px; -moz-border-radius-bottomleft: 3px; -webkit-border-radius-bottomleft: 3px; }
	.PDebug .resource tr:last-child td:last-child   { border-radius-bottomright: 3px; -moz-border-radius-bottomright: 3px; -webkit-border-radius-bottomright: 3px; }
	.PDebug .resource tr:hover td		{ background: #DBB062; }
	.PDebug .resource tr.alt td			{ background: #E7D8B4; }
	.PDebug .resource tr.alt:hover td	{ background: #D7C8A4; }
/* error string format */
	.PDebug .err { color: #F00; }
	.PDebug .errorText { color: #600; font-style: italic; }
	.PDebug .errorText li { display: inline; }
/* global styles applied to benchmarker for differentiating pdebug calls */
	.PDebug .bench				{ color: #711E2A; }
	.PDebug .dump				{ color: #A400C8; }
	.PDebug .trace				{ color: #1F721D; }
	.PDebug .ERROR				{ color: #F00; }
	.PDebug .WARNING			{ color: #F70; }
	.PDebug .NOTICE				{ color: #9F0; }
/* code coverage statistics styles */
	.PDebug .coverage h4		{ cursor: pointer; background: #D0D6DF; margin: 0; padding: 0.5em 0; }
	.PDebug .coverage h4 span	{ color: #F70; }
	.PDebug .coverage ol		{ font-family: monospace; }
	.PDebug .coverage li		{ list-style: decimal-leading-zero; white-space: pre; }
	.PDebug .coverage li.covered{ background: #BAEEB3; }
	.PDebug .coverage li span.c	{ float: right; color: #F70; font-weight: bold; position: relative; top: -1.5em; }
	/*.PDebug .PARSING:after		{ vertical-align:super; content: "parse"; }
	.PDebug .CORE:after			{ vertical-align:super; content: "core"; }
	.PDebug .COMPILE:after		{ vertical-align:super; content: "compile"; }
	.PDebug .USER:after			{ vertical-align:super; content: "user"; }
	.PDebug .RECOVERABLE:after	{ vertical-align:super; content: "recoverable"; }
	.PDebug .STRICT:after		{ vertical-align:super; content: "strict"; }'*/
</style>
<script type="text/javascript">
var PDebug = {
// function to scroll the window to move something important (error message etc) into view
// this method is much nicer than window.scrollIntoView(), which will cause a scroll to
// the element top / bottom even if onscreen
	f: function(el) {
		if (typeof(el) == "string") {
			el = document.getElementById(el);
		}
		var focus_input = document.createElement("input");
		focus_input.type = "text";
		el.parentNode.insertBefore(focus_input, el);
		focus_input.focus();
		el.parentNode.removeChild(focus_input);
	},
// toggle an HTML element's visibility
	toggle: function(el) {
		el.style.display = el.style.display == "none" ? "" : "none";
	},
// toggle an entire output block's visibility
	c: function(el) {
		el = el.parentNode;
		for (var i = 2, node; node = el.childNodes[i]; ++i) {
			if (node.nodeType == 1) {
				PDebug.toggle(node);
			}
		}
	},
// toggle multiline string line numbers
	s : function(e) {
		var div = e.currentTarget.parentNode.nextSibling, pre;
		if (div.childNodes.length != 2) {
			pre = div.appendChild(document.createElement("pre"));
			pre.innerHTML = div.childNodes[0].textContent || div.childNodes[0].innerText;
		} else {
			pre = div.childNodes[1];
		}
		if (div.childNodes[0].style.display == "none") {
			div.childNodes[0].style.display = "";
			div.childNodes[1].style.display = "none"
		} else {
			div.childNodes[0].style.display = "none";
			div.childNodes[1].style.display = ""
		}
		e.stopPropagation();		// prevent node from toggling as well
	},
	
// NODE TOGGLING

// array of nodes that had their onclick events triggered by a click
	clicked_nodes : [],
	toggle_defer_timer : null,
// push nodes to temporary list so childmost clicked one can be determined
	t: function(e) {
		PDebug.clicked_nodes.push(e.currentTarget);
		if (!PDebug.toggle_defer_timer) {
			PDebug.toggle_defer_timer = window.setTimeout(PDebug.doToggle, 0.01);
		}
	},
// find childmost node clicked, and toggle it!
	doToggle: function() {
		var nodes_in_order = new Array();
		var diff, node1pos;
		var childmost = PDebug.clicked_nodes[0];
		for (var i = 1, node1, node2; node1 = PDebug.clicked_nodes[i-1], node2 = PDebug.clicked_nodes[i]; ++i) {
			diff = PDebug.compareNodes(node1, node2);
			node1pos = 0;
			for (var j = 0; j < nodes_in_order.length; ++j) {
				if (nodes_in_order[j] == node1) {
					node1pos = j;
					break;
				}
			}
			nodes_in_order[node1pos] = node1;
			if (node1pos + diff > 0) {
				nodes_in_order[node1pos + diff] = node2;
			} else if (node1pos + diff < 0) {
				while (diff++ > 1) {
					nodes_in_order.unshift();
				}
				nodes_in_order.unshift(node2);
			} else {
				// these nodes werent in any kind of hierachy... leave them out? shouldnt happen anyway...
			}
		}
		// first node should now be childmost!
		if (nodes_in_order.length > 0) {
			childmost = nodes_in_order[0];
		}
		window.clearTimeout(PDebug.toggle_defer_timer);
		PDebug.toggle_defer_timer = null;
			// li->span->[text, *UL*, text]
		if (childmost.firstChild.childNodes[1].childNodes.length == 0 || (childmost.firstChild.childNodes[1].childNodes[0].nodeType == 3 && childmost.firstChild.childNodes[1].childNodes.length == 1)) {} else {
			PDebug.toggle(childmost.firstChild.childNodes[1]);
			PDebug.flashNode(childmost);
		}
		// finally, reset the clicked nodes array
		PDebug.clicked_nodes = new Array();
	 },
// flash a node to notify it in some way (like, it was just clicked - so you can keep track of it easily)
	flash_timer : null,
	last_flashing_el : null,		// last element that was flashing (so we can reset it if a new one is clicked before the animation completes)
	last_flashing_color : "",		// original color of last flashing element
	def_flash_count : 4,			// number of times to flash
	flash_color : "#FFA466",		// color to flash nodes
	flash_count : null,				// used in flashing logic

	flashNode: function(el) {
		// clear the old timer & reset old element if it's still flashing
		if (PDebug.flash_timer) {
			window.clearInterval(PDebug.flash_timer);													
			PDebug.last_flashing_el.style.backgroundColor = PDebug.last_flashing_color;
		}
		// if parent node is a UL, it's not a root dump so we flash its parent row
		if (el.parentNode.tagName == "UL") {
			el = el.parentNode;
		}
		PDebug.flash_count = PDebug.def_flash_count;
		PDebug.last_flashing_el = el;
		PDebug.last_flashing_color = el.style.backgroundColor;
		PDebug.flash_timer = window.setInterval(PDebug.doFlash, 100);
		PDebug.f(el);	// focus on toggled node
	},
	doFlash: function() {
		if (PDebug.flash_count-- <= 0) {
			window.clearInterval(PDebug.flash_timer);
			PDebug.flash_timer = null;
		} else {
			PDebug.last_flashing_el.style.backgroundColor = PDebug.last_flashing_el.style.backgroundColor == PDebug.last_flashing_color ? PDebug.flash_color : PDebug.last_flashing_color;
		}
	},
// returns the difference in DOM position between two nodes in the same hierachy
//  (or false, in the event that they aren't... shouldn't happen in this implementation
	 compareNodes: function(node1, node2, reversed) {
		var temp_node = node1.parentNode;
		var found = false;
		var i = 1;
		while (temp_node) {
			if (temp_node == node2) {
				return i;								   // node2 is above node1 by i steps
			}
			temp_node = temp_node.parentNode;
			++i;
		}
		if (typeof reversed != "undefined" && reversed) {
			return false;									// not in the same node hierachy at all! oh noes!
		}
		var step_diff = PDebug.compareNodes(node2, node1, true);   // compare in opposite direction...
		if (step_diff === false) {
			return false;
		}
		return step_diff * -1;								// ...and return the negative
	 }
};
</script>

EOT
,

	// Generic wildcards:
	// 	%s =	subitem - nests another group of items inside this one
	//			used for array pairs, object members, function arguments, table lines etc
	//  %- =	indentation string appropriate for this level of the output (you don't *really* need this for HTML, btw)


	// Debugger wildcards:
	//	%t =	variable type
	// 	%v =	simple variable value (using VARIABLE_OUTPUT_FORMAT) / array value	/ object member
	// 	%k =	array key / object member name
	//	%i =	"info"... object class / array count / string length / resource type / counter variable etc
	//  %c =	collapsed string, if debug_start_collapsed is set (see below:)

	'COLLAPSED_STRING'	  => ' style="display: none"',

	'VARIABLE_OUTPUT_FORMAT' => "\n" . '<li class="%t" title="%t"><span> %v</span></li>' . "\n",

	'INDENT_STRING' 		=> "\t",
	'PADDING_CHARACTER'		=> " ",		// recommend you use this along with a "white-space: pre; font-family: monospace;" style in HTML mode

	'HEADER_BLOCK' 			=> '<ul class="PDebug">' . "\n",

	'VARIABLES_HEADER'		=>	'<li class="info">Information for %i vars:<ol class="vars">' . "\n",	// :NOTE: this header & footer are only used when dump()ing multiple variables
	'VARIABLES_JOINER'		=>	'',									// don't need one in html, let the <ol> take care of it

	'SINGLELINE_STRING_FORMAT'	=>	'<li class="string" title="string (%i chars)">&quot;<div><ol><li><span>%v</span></li></ol></div>&quot;</li>',
	//	%l =	string line count
	'MULTILINE_STRING_FORMAT'	=>	'<li class="mstring" title="string (%i chars, %l lines)" onclick="PDebug.t(event);"><span><nobr>&nbsp;string (%i chars, %l <span class="l" onclick="PDebug.s(event);">lines</span>)<span class="inner">&nbsp;&quot;&nbsp;</span></nobr><div><ol>%s</ol></div><nobr><span class="inner">&nbsp;&quot;&nbsp;</span></nobr></span></li>',
	//	%n =	string line number
	//	%v =	string line text
		'MULTILINE_STRING_LINE'	=>	'<li><span>%v</span></li>',
		'MULTILINE_STRING_JOINER' => "\n",

	'ARRAY_FORMAT'			=>	'<li class="array c%i" title="array (%i elements)" onclick="PDebug.t(event);"><span><nobr>&nbsp;Array (%i elements)<span class="inner">: [&nbsp;</span></nobr><ul%c>%s</ul><nobr><span class="inner">&nbsp;]&nbsp;</span></nobr></span></li>' . "\n",
		'ARRAY_KEY_NUMERIC'	=>		'<li><ul><li class="integer"><span>%k</span></li><li class="joiner"> ',
		'ARRAY_KEY_STRING'	=>		'<li><ul><li class="string">&quot;<div><ol><li><span>%k</span></li></ol></div>&quot;</li><li class="joiner"> ',
		'ARRAY_VALUE'		=>		'&#061;&gt; </li>%v</ul></li>',
		'ARRAY_JOINER'		=>		"\n",

	'OBJECT_FORMAT'			=>	'<li class="object" title="%i object" onclick="PDebug.t(event);"><span><nobr>&nbsp;%i Object<span class="inner">: {&nbsp;</span></nobr><ul%c>%s</ul><nobr><span class="inner">&nbsp;}&nbsp;</span></nobr></span></li>' . "\n",
		'OBJECT_INDEX'		=>		'<li><ul><li class="member"><span class="%i">%i:</span>%k</li>',
		'OBJECT_MEMBER'		=>		'<li class="joiner">:&#061</li>%v</ul></li>',
		'OBJECT_JOINER'		=>		"\n",

	'GENERIC_FORMAT'		=>	'<li class="resource" title="%t resource" onclick="PDebug.t(event);"><span><nobr>&nbsp;%t [%i]<span class="inner">&nbsp;(&nbsp;</span></nobr><table cellpadding="0" cellspacing="1"%c>%s</table><nobr><span class="inner">&nbsp;)&nbsp;</span></nobr></span></li>' . "\n",
		'GENERIC_HEADER'	=>		'<thead class="%t">%s</thead>' . "\n",
		'GENERIC_BODY'		=>		'<tbody class="%t">%s</tbody>' . "\n",
		'GENERIC_FOOTER'	=>		'<tfoot class="%t">%s</tfoot>' . "\n",
		//	%i in this case is intended as a 'spin' variable for table row styling etc
		'GENERIC_LINE'		=>		'<tr class="%t %i"> %s</tr>' . "\n",
		'GENERIC_CELL'		=>		'<td class="%t">%v</td>' . "\n",
		'GENERIC_TITLED_CELL'	=>	'<td class="%t" title="%t">%v</td>',
		'GENERIC_LINE_JOINER'	=>	"",
		'GENERIC_CELL_JOINER'	=>	"",
	'GENERIC_HEADER_CHARACTER' => '',
	'GENERIC_BORDER_CHARACTER' => '',

	'VARIABLES_FOOTER'		=>	'</ol></li>' . "\n",													// :NOTE: this header & footer are only used when dump()ing multiple variables

	'FOOTER_BLOCK' 			=> '</ul>' . "\n",

	// Benchmarker wildcards:
	//	%i	=	benchmark tag
	//  %n  =   benchmark call number
	// 	%p	=	file path
	//	%t	=	current execution time (s)		<-- these 4 also have %ct, %cm, %cdt & %cdm for HEX strings for shading in HTML mode
	//	%m	=	current mem usage (KB)
	//	%dt	=	time diff since last call (s)
	//	%dm	=	memory diff since last call (KB)
	//  %s  =   variables to dump
	'BENCH_FORMAT'			=>	'<li class="bench" id="PDebug_bench%n"><div style="width:50%;float:left;text-align:right;">%i : %p&nbsp;</div><div style="width:50%;float:left;font-family:monospace;">&nbsp;@ <span style="color: %ct">%t</span>s [<span style="color: %cdt">%dt</span>s] <span style="color: %cm">%m</span>K [<span style="color: %cdm">%dm</span>K]</div><br />%-<ul>%s</ul></li>' . "\n",

	// This one is like a benchmark, except that it shows stats for pdebug startup overhead
	// Feel entirely free to disable this with the config var up top if it shits you!
	// :NOTE:
	//  - %dt and %dm are the startup overhead, whilst %t is the total debugger execution time. The total debugger mem usage doesn't make sense.
	//  - %p shows $_SERVER['PHP_SELF'], or server path of the executing script if unavailable
	'STARTUP_STATS_FORMAT'		=>  '<li><span class="resource"><span><nobr style="cursor: default;">:pdebug:</nobr></span></span> <span class="info">loaded</span> for <span style="color: #0086CE;">%p</span>: (started in <span style="color: %cdt">%dt</span>s / <span style="color: %cdm">%dm</span>KB, total overhead <span style="color: %ct">%t</span>s)</li>',
	//	- %p is additionally not used in this one...
	'INTERNAL_CALL_LOG_FORMAT'	=>  '<li onclick="PDebug.c(this);"><span class="resource"><span><nobr>:pdebug:</nobr></span></span><span class="info %i"> %i </span>: (executed in <span style="color: %cdt">%dt</span>s / <span style="color: %cdm">%dm</span>KB)</li>',

	//  %n =	error number (since script start)
	//	%e =	error type
	//	%m =	error message
	// 	%p =	file path
	'ERROR_FORMAT' 			=> '<li class="err" id="PDebug_error%n"><b>%e</b> : %p<ul class="errorText">%m</ul></li>' . "\n", //<script type="text/javascript">PDebug.f("PDebug_error%n");</script>' . "\n",

	//	%s =	combined stack lines
	'STACK_FORMAT'			=>	'<li class="stack"><span class="info">Stack:</span> <ul>%s</ul></li>' . "\n",
	//	%c = 	class name
	//	%t = 	call type
	//	%o =	compressed calling object debug
	// 	%f =	function name
	// 	%s =	function arguments
	// 	%p =	file path
	//  %cs =   line colour
	//  %i  =   function call number
		'STACK_LINE'		=> 		'<li style="background: %cs;">&nbsp;%p : <ul>%o</ul>%t%f( <ul>%s</ul> )</li>' . "\n",
		'STACK_JOINER'		=> 		', ',
);
	
?>
