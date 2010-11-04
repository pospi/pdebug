<?php

define('ANON_PAGE', true);
require_once('v6_init.php');

require_once('func_debug.inc.php');

print '<p>This is a big junk array im using for testing. It has several levels and lots of primitive variables as well as objects and resources.</p>';
print '<p>You can hover over variables to see their type, and click the plus symbols to expand/contract branches. A bit rough around the edges, but it does the job.</p>';
print '<p>If interested, the file for this is on plucky in <b>/home/spospischil/web/4.6/vision6/pospi/</b></p>';
print '<hr>';

$entity = new Account_Entity();
$entity->loadFromAuth('spospischil', 'fucfed');

$test = mysql_query('SELECT * FROM v6_users LIMIT 10;');

$arrayTest = array (
	'LANG_NAME' => 'PHP',
	'COMMENT_SINGLE' => array(1 => '//', 2 => '#'),
	'COMMENT_MULTI' => array('/*' => '*/'),
	'CASE_KEYWORDS' => false,
	'QUOTEMARKS' => array("'", '"'),
	'ESCAPE_CHAR' => '\\',
	'KEYWORDS' => array(
	'mysqlTest' => $test,
	4 => $entity,
		1 => array(
			'include', 'require'
			),
		2 => array(
			'null', '__LINE__'
			),
		3 => array(
			'func_num_args', 'func_get_arg'
			)
		),

	'SYMBOLS' => array(
		),
	'CASE_SENSITIVE' => array(
		0 => false,
		1 => false,
		2 => false,
		3 => false
		),
	'STYLES' => array(
		'KEYWORDS' => array(
			1 => 'color: #b1b100;',
			2 => 'color: #000000; font-weight: bold;',
			3 => 'color: #000066;'
			),
		'COMMENTS' => array(
			1 => 'color: #808080; font-style: italic;',
			2 => 'color: #808080; font-style: italic;',
			'MULTI' => 'color: #808080; font-style: italic;'
			),
		'ESCAPE_CHAR' => array(
			0 => 'color: #000099; font-weight: bold;'
			),
		'BRACKETS' => array(
			0 => 'color: #66cc66;'
			),
		'STRINGS' => array(
			0 => 'color: #ff0000;'
			),
		'NUMBERS' => array(
			0 => 'color: #cc66cc;'
			),
		'METHODS' => array(
			1 => 'color: #006600;',
			2 => 'color: #006600;'
			),
		'SYMBOLS' => array(
			0 => 'color: #66cc66;'
			),
		'REGEXPS' => array(
			0 => 'color: #0000ff;'
			),
		'SCRIPT' => array(
			0 => '',
			1 => '',
			2 => '',
			3 => ''
			)
		),
	'URLS' => array(
		1 => '',
		2 => '',
		3 => 'http://www.php.net/{FNAME}',
		4 => ''
		),
	'OOLANG' => true,
	'OBJECT_SPLITTERS' => array(
		1 => '-&gt;',
		2 => '::'
		),
	'REGEXPS' => array(
		0 => "[\\$]{1,2}[a-zA-Z_][a-zA-Z0-9_]*"
		),
	'STRICT_MODE_APPLIES' => 0,
	'SCRIPT_DELIMITERS' => array(
		),
	'HIGHLIGHT_STRICT_BLOCK' => array(
		0 => true,
		1 => true,
		2 => true,
		3 => true
        ),
    'TAB_WIDTH' => 4
);

class testclass {
    function debugme() {
        global $arrayTest;
        dump($arrayTest);
    }
}
$tc = new testclass();
$tc->debugme();
?>