<?php
//
//            USAGE: dump($var1 [, $var2, $var3, ...]);
//                   trace();
//                   bench(); {some code happens} bench();
//
// +----------------------------------------------------------------------+
// | Vision 6 - Pretty debugging functions                                |
// +----------------------------------------------------------------------+
// | Much the same as var_dump, but nicer to use.                         |
// | Pretty heavy on the HTML output though...                            |
// | Features:                                                            |
// |            - Coloured output                                         |
// |            - Mouseover variables to see their type                   |
// |            - Variable folding                                        |
// |            - Resource information, where applicable                  |
// |            - Pass as many variables to the function as you like      |
// |            - Will automatically print out object toString() methods  |
// |                                                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2007 Vision 6 Pty Ltd                                  |
// |                                                                      |
// | Code originates from self-created code with a friend. Permission     |
// | given for vision6 to be using it.                                    |
// +----------------------------------------------------------------------+
// | Authors: Nick Fisher          <fisher@spadgos.com>                   |
// |          Sam Pospischil       <spospischil@vision6.com.au>           |
// +----------------------------------------------------------------------+
//
// $Id: func_debug.inc.php 13920 2007-07-02 13:15:17Z spospischil $
//

    if (!defined('CR')) {
        define('CR'             , "\r");
    }
    if (!defined('LF')) {
        define('LF'             , "\n");
    }
    if (!defined('TAB')) {
        define('TAB'            , "\t");
    }
    if (!defined('CRLF')) {
        define('CRLF'           , CR.LF);
    }

    // non-HTML mode checks
    if ( !isset($_SERVER['SERVER_SOFTWARE'])
      || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest')
      || empty($_SERVER['HTTP_USER_AGENT']) ) {
        Debug::$USE_HTML = false;
    }

    // set application root (for backtrace & other file paths)
    if (!Debug::$APP_ROOT) {
        Debug::$APP_ROOT = $_SERVER['DOCUMENT_ROOT'];
    }

    // set error handler, if desired :NOTE: very experimental
    //set_error_handler('Debug::__error');

    // other options
    Debug::$START_COLLAPSED = false;


    // function wrappers for main Debug class functions to make typing nicer
    function dump() {
        print Debug::dump(func_get_args());
    }
    function trace() {
        print Debug::trace();
    }
    function bench() {
        print Debug::bench();
    }


    class Debug {

        const PDEBUG_COLOR_BACKGROUND           = '#FFF';
        const PDEBUG_COLOR_ARRAY_KEYS_BRACKET   = '#088';
        const PDEBUG_COLOR_ARRAY_KEYS           = '#008';
        const PDEBUG_COLOR_ARROW                = '#008';
        const PDEBUG_COLOR_OBJECT_TYPE          = '#B00';
        const PDEBUG_COLOR_TYPE                 = '#080';
        const PDEBUG_COLOR_UNKNOWN              = '#888';

        const PDEBUG_COLOR_RESOURCE             = '#FF7F00';
        const PDEBUG_COLOR_RESOURCE_NULL        = '#999';
        const PDEBUG_COLOR_TABLEBG1             = '#FFF';
        const PDEBUG_COLOR_TABLEBG2             = '#F4F4F4';

        const PDEBUG_TABSTRING                  = '    ';
        const PDEBUG_ELEMENT_TOGGLE             = '<span style="cursor: pointer; border: 1px solid #EEE" onclick="this.nextSibling.style.display = (this.nextSibling.style.display == \'\' ? \'none\' : \'\');">+ (</span>';

        static $PDEBUG_LOOP_COUNT = 0;
        static $PDEBUG_PREV_BENCH = 0;
        static $PDEBUG_PREV_MEM = 0;

        static $USE_HTML = true;
        static $APP_ROOT = '';
        static $START_COLLAPSED = false;

        /**
         *  Basically an improved version of var_dump() that makes things easier to read.
         *  This is basically accomplished via heavy use of regexes to improve the output for HTML display.
         *  Accepts any number of parameters and will debug them all in turn.
         *
         *  @param  bool    $show_trace     Toggle stack trace display
         *  @param  bool    $force_html     If specified, force HTML output to a certain mode
         *  @param  bool    $force_collapsed Force starting node status
         *
         *  @version 1
         *  @access public
         *  @return string
         */
        static function dump($var_array, $show_trace = true, $force_html = null, $force_collapsed = null) {
            $out = '';
            $vars = $var_array;
            $out .= '<pre style="background: '.Debug::PDEBUG_COLOR_BACKGROUND.'">';

            //show the call stack
            $trace = ($show_trace ? Debug::trace() : '');

            if (strlen($trace)) {
                $out .= '<div style="background: '.Debug::PDEBUG_COLOR_TABLEBG2.'"><strong>STACK:</strong>'.CRLF;
                $out .= $trace;
                $out .= '</div>';
            }

            $start_depth = 0;
            $do_numbering = false;
            if (count($vars) > 1) {
                $out .= '<b>Information for ' . count($vars) . ' variables:</b>'.CRLF;
                $start_depth = 1;
                $do_numbering = true;
            }

            // force stack compression if desired
            $debug_last_collapsed = Debug::$START_COLLAPSED;
            if ($force_collapsed !== null) {
                Debug::$START_COLLAPSED = (bool)$force_collapsed;
            }

            $i = 0;
            foreach ($vars as $var) {
                if ($do_numbering) {
                    $out .= '<b>#' . ++$i . '</b> <span style="cursor: pointer; background: #EEE" onclick="this.nextSibling.style.display = (this.nextSibling.style.display == \'\' ? \'none\' : \'\');"> + </span><span> ';
                }
                $this_string = '';

                // If the variable is an array, print it as a hierachy.
                if (is_array($var)) {
                    Debug::debug_array($this_string, $var, $start_depth);
                }

                // If the variable is an object, print out all its information
                else if (is_object($var)) {
                    Debug::debug_object($this_string, $var, $start_depth);
                }

                // If a resource, print something useful based on the type of resource.
                else if (is_resource($var)) {
                    Debug::debug_resource($this_string, $var, $start_depth);
                }

                // Otherwise, just print the variable out normally, along with its type.
                else {
                    Debug::debug_var($this_string, $var, $start_depth);
                }

                $out .= rtrim($this_string);
                if ($do_numbering) {
                    $out .= '</span>' . CRLF;
                }
            }
            $out .= '</pre>';

            Debug::$START_COLLAPSED = $debug_last_collapsed;

            if ($force_html !== null) {
                return ((bool)$force_html ? $out : strip_tags($out));
            } else {
                return (Debug::$USE_HTML ? $out : strip_tags($out));
            }
        }

        /**
         * Generates a backtrace so that you can easily view the callstack. Wrap the output
         * in a <pre> tag for HTML debugging.
         *
         * @access public
         * @return string
         */
        static function trace() {
            $stack = array_reverse(debug_backtrace());
            return Debug::_readableBacktrace($stack);
        }

        /**
         * Convert a backtrace array into a nice readable format
         *
         * @access private
         * @param  array    stack    backtrace array
         * @return string
         */
        static function _readableBacktrace($stack) {
            $out = '';
            foreach ($stack as $hist => $data) {
                if (isset($data['class']) && $data['class'] == 'Debug' && ($data['function'] == 'trace' || $data['function'] == 'dump' || $data['function'] == '__error')) {
                    continue;
                }

                $out .= str_replace(Debug::$APP_ROOT, '', $data['file']).'/'.$data['line'];
                $out .= ' : ' . (isset($data['class']) ? $data['class'] : '')
                        . (isset($data['type']) ? $data['type'] : '')
                        . $data['function'].'(';
                foreach ($data['args'] as $k => $arg) {
                    $data['args'][$k] = gettype($arg);
                }
                $out .= implode(', ', $data['args']);
                $out .= ')';
                $out .= CRLF;
            }
            return (Debug::$USE_HTML ? $out : strip_tags($out));
        }

        /**
         * Benchmarking - output lines with execution time and time difference between last call
         *
         * @access public
         */
        static function bench() {
            $trace = debug_backtrace();

            if (!Debug::$PDEBUG_PREV_BENCH) {
                Debug::$PDEBUG_PREV_BENCH = $_SERVER['REQUEST_TIME'];
            }

            $mem_usage = memory_get_usage();
            $this_call = microtime(true);

            $time_diff = number_format($this_call - Debug::$PDEBUG_PREV_BENCH, 10);
            $mem_diff  = round(($mem_usage - Debug::$PDEBUG_PREV_MEM) / 1024, 3);

            $output = '<span style="font-size: 11px; font-family: courier new"><i>' . str_replace($_SERVER['DOCUMENT_ROOT'], '', $trace[0]['file']) . '</i>/<span style="color:#F00;font-weight:bold">' . $trace[0]['line'] . '</span> @ ' . $this_call . 's [<b>&Delta; ' . $time_diff . 's</b>] ' . round($mem_usage / 1024, 3) . 'K [<b>&Delta; ' . $mem_diff . 'K</b>]</span><br/>' . CRLF;

            Debug::$PDEBUG_PREV_BENCH = $this_call;
            Debug::$PDEBUG_PREV_MEM = $mem_usage;

            return (Debug::$USE_HTML ? $output : strip_tags($output));
        }

        //======================================================================================
        //======================================================================================
        //======================================================================================

        static function sanityCheck() {
            if (++Debug::$PDEBUG_LOOP_COUNT > 500000) {
                die('</pre><br/><h3>Circular reference detected - aborting!</h3>');
            }
        }

        //======================================================================================
        //======================================================================================
        //======================================================================================

        /**
         *  Debugging for resource datatypes, since PHP performs no debugging for these types itself.
         *  Implementation for each custom resource type should be added as necessary.
         *
         *  @version 1
         *  @access private
         *
         *  @param      string      out     a reference to the output string to modify
         *  @param      resource    var     the resource to debug
         *  @param      int         indent  number of tab-stops to indent this dump by
         */
        static function debug_resource(&$out, $var, $indent = 0) {
            Debug::sanityCheck();
            $thisIndent = str_repeat(Debug::PDEBUG_TABSTRING, $indent);
            $thisResType = get_resource_type($var);
            $out .= '<span style="color: '.Debug::PDEBUG_COLOR_RESOURCE.'">'.print_r($var, true).' ('.$thisResType.')</span>'.CRLF.$thisIndent.Debug::PDEBUG_ELEMENT_TOGGLE.'<span' . (Debug::$START_COLLAPSED ? ' style="display: none"' : '') . '>'.CRLF;     //this will only show the type of resource
            switch ($thisResType) {
                case 'mysql result' :
                    $tabStops = 4;
                    $numRows = mysql_num_rows($var);
                    $numFields = mysql_num_fields($var);
                    $out .= $thisIndent.Debug::PDEBUG_TABSTRING.'Number of rows: '.$numRows.CRLF;
                    $out .= $thisIndent.Debug::PDEBUG_TABSTRING.'Number of fields: '.$numFields.CRLF.CRLF.$thisIndent.Debug::PDEBUG_TABSTRING;
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
                                for ($l = 0; $l < count($lines); $l++) {    // go through each of the lines in this field
                                    $line = $lines[$l];
                                    if (is_null($line)) {
                                        $len = 4;    // length of "null"
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
                        $out .= '|' . str_repeat('=', $fullWidth - 2) . '|'.CRLF.$thisIndent.Debug::PDEBUG_TABSTRING;
                        foreach (array_keys($cols) as $field) {
                            $out .= '| ' . str_pad($field, $colWidths[$field]) . ' ';
                        }
                        $out .= '|'.LF.$thisIndent.Debug::PDEBUG_TABSTRING.'|' . str_repeat('-', $fullWidth - 2) . '|'.CRLF.$thisIndent.Debug::PDEBUG_TABSTRING;
                        for ($i = 0; $i < $numRows; $i++) {
                            for ($height = 0; $height < $rowHeight[$i]; $height++) {
                                $out .= '<span style="background: ' . ($i % 2 ? Debug::PDEBUG_COLOR_TABLEBG1 : Debug::PDEBUG_COLOR_TABLEBG2) . '">';
                                foreach (array_keys($colWidths) as $field) {
                                    if (!isset($cols[$field][$i][$height])) {
                                        $val = '';
                                        $len = $colWidths[$field];
                                    } elseif (is_null($cols[$field][$i][$height])) {
                                        $val = '<i style="color: '.Debug::PDEBUG_COLOR_RESOURCE_NULL.'">NULL</i>';
                                        $len = $colWidths[$field] + 27;    // (length of <i></i>);
                                    } else {
                                        $val = $cols[$field][$i][$height];
                                        $len = $colWidths[$field];
                                    }
                                    $out .= '| ' . str_pad($val, $len, ' ', is_numeric($cols[$field][$i]) ? STR_PAD_LEFT : STR_PAD_RIGHT) . ' ';
                                }
                                $out .= '|</span>'.CRLF.$thisIndent.Debug::PDEBUG_TABSTRING;
                            }
                        }
                        $out .= '|' . str_repeat('=', $fullWidth - 2) . '|'.CRLF;
                    }
                    break;
                case 'mysql link' :
                    $out .= $thisIndent . Debug::PDEBUG_TABSTRING . 'Charset: ' . @mysql_client_encoding($var) . CRLF;
                    $out .= $thisIndent . Debug::PDEBUG_TABSTRING . 'Client: ' . @mysql_get_client_info() . CRLF;
                    $out .= $thisIndent . Debug::PDEBUG_TABSTRING . 'Host: ' . @mysql_get_host_info($var) . CRLF;
                    $out .= $thisIndent . Debug::PDEBUG_TABSTRING . 'Protocol ver: ' . @mysql_get_proto_info($var) . CRLF;
                    $out .= $thisIndent . Debug::PDEBUG_TABSTRING . 'Server ver: ' . @mysql_get_server_info($var) . CRLF;
                    break;
                default:
                    $out .= $thisIndent . Debug::PDEBUG_TABSTRING . '<span style="color: '.Debug::PDEBUG_COLOR_UNKNOWN.'; font-style: italic">*UNKNOWN RESOURCE TYPE*</span>' . CRLF;
                    break;
            }
            $out .= $thisIndent.'</span>)'.CRLF;
        }

        /**
         *  Special debug case for objects - output all relevant data.
         *
         *  @version 2
         *  @access private
         *
         *  @param      string      out         a reference to the output string to modify
         *  @param      object      var         the object to debug
         *  @param      int         indent      number of tab-stops to indent this dump by
         *  @param      array       ignore      member variables to skip
         *  @param      array       ref_chain   array of all previously dumped vars (avoids recursion)
         */
        static function debug_object(&$out, $var, $indent = 0, &$ref_chain = null, $ignore = array()) {
            Debug::sanityCheck();
            $base_indent = str_repeat(Debug::PDEBUG_TABSTRING, $indent);
            if ($ref_chain === null) {
                $ref_chain = array();
            }
            if (is_object($var)) {
                $myString = '';
                foreach ($ref_chain as $ref_val)
                    if ($ref_val === $var) {
                        $out .= '<span style="color: '.Debug::PDEBUG_COLOR_UNKNOWN.'; font-style: italic">*RECURSION*</span>' . CRLF;
                        return;
                    }
                array_push($ref_chain, $var);
                $myString .= '<span style="color: '.Debug::PDEBUG_COLOR_OBJECT_TYPE.'">' . get_class($var)
                     . '</span><span style="color: '.Debug::PDEBUG_COLOR_TYPE.'"> Object</span>' . CRLF . $base_indent
                     . Debug::PDEBUG_ELEMENT_TOGGLE.'<span' . (Debug::$START_COLLAPSED ? ' style="display: none"' : '') . '>'.CRLF;
                $var = (array) $var;
                foreach ($var as $key => $val)
                    if (is_array($ignore) && !in_array($key, $ignore, 1)) {
                        $myString .= $base_indent . Debug::PDEBUG_TABSTRING . '<span style="color: '.Debug::PDEBUG_COLOR_ARRAY_KEYS_BRACKET.'">[</span><span style="color: '.Debug::PDEBUG_COLOR_ARRAY_KEYS.'">';
                        if ($key{0} == "\0") {
                            $key_parts = explode("\0", $key);
                            $myString .= $key_parts[2] . (($key_parts[1] == '*')  ? ':protected' : ':private');
                        } else
                            $myString .= $key;
                        $myString .= '</span><span style="color: '.Debug::PDEBUG_COLOR_ARRAY_KEYS_BRACKET.'">]</span> <span style="color: '.Debug::PDEBUG_COLOR_ARROW.'">=' . (Debug::$USE_HTML ? '&gt;' : '>') . '</span> ';
                        Debug::debug_object($myString, $val, $indent + 1, $ref_chain, $ignore);
                    }
                $myString .= $base_indent . '</span>)' . CRLF;
                $out .= rtrim($myString).CRLF;
                array_pop($ref_chain);
            } else if (is_array($var)) {
                Debug::debug_array($out, $var, $indent, $ref_chain);
            } else if (is_resource($var)) {
                Debug::debug_resource($out, $var, $indent);
            } else {
                Debug::debug_var($out, $var, $indent);
            }
        }

        /**
         *  Special debug case for arrays - recursively output all relevant data.
         *
         *  @version 1
         *  @access private
         *
         *  @param      string      out         a reference to the output string to modify
         *  @param      array       var         the array to recursively debug
         *  @param      int         indent      number of tab-stops to indent this dump by
         *  @param      array       ref_chain   array of all previously dumped vars (avoids recursion)
         */
        static function debug_array(&$out, $var, $indent = 0, &$ref_chain = null) {
            Debug::sanityCheck();
            $base_indent = str_repeat(Debug::PDEBUG_TABSTRING, $indent);
            if ($ref_chain === null) {
                $ref_chain = array();
            }
            $myString = '<span style="color: '.Debug::PDEBUG_COLOR_TYPE.'">Array (length: '.count($var).')</span>'.CRLF.$base_indent;
            $myString .= Debug::PDEBUG_ELEMENT_TOGGLE.'<span' . (Debug::$START_COLLAPSED ? ' style="display: none"' : '') . '>'.CRLF;
            foreach ($var as $k => $v) {
                $myString .= $base_indent.Debug::PDEBUG_TABSTRING;
                $myString .= '<span style="color: '.Debug::PDEBUG_COLOR_ARRAY_KEYS_BRACKET.'">[</span><span style="color: '.Debug::PDEBUG_COLOR_ARRAY_KEYS.'">'.Debug::__htmlentities($k).'</span><span style="color: '.Debug::PDEBUG_COLOR_ARRAY_KEYS_BRACKET.'">]</span> <span style="color: '.Debug::PDEBUG_COLOR_ARROW.'">=' . (Debug::$USE_HTML ? '&gt;' : '>') . ' </span>';

                if (is_array($v)) {
                    Debug::debug_array($myString, $v, $indent + 1, $ref_chain);
                } else if (is_resource($v)) {
                    Debug::debug_resource($myString, $v, $indent + 1);
                } else if (is_object($v)) {
                    Debug::debug_object($myString, $v, $indent + 1, $ref_chain);
                } else {
                    Debug::debug_var($myString, $v, $indent + 1);
                }
            }
            $myString .= $base_indent.'</span>';
            $myString .= ')';
            $out .= rtrim($myString).CRLF;
        }

        /**
         *  Debug an unknown variable type or simple datatype
         *
         *  @version 1
         *  @access private
         *
         *  @param      string      out         a reference to the output string to modify
         *  @param      mixed       var         the variable to debug
         *  @param      int         indent      number of tab-stops to indent this dump by
         */
        static function debug_var(&$out, $var, $indent = 0) {
            Debug::sanityCheck();

            if (!Debug::$USE_HTML) {
                $out .= '{' . strtoupper(gettype($var)) . '} ';
            }

            $out .= '<span title="'.gettype($var).'">';
            if ($var === false) {
                $out .= 'FALSE';
            } else if ($var === true) {
                $out .= 'TRUE';
            } else if ($var === null) {
                $out .= 'NULL';
            } else {
                $out .= Debug::__htmlentities(print_r($var, true));
            }
            $out .= '</span>'.CRLF;
        }

        //======================================================================================
        //======================================================================================
        //======================================================================================

        static function __error() {

            // continue execution for errors supressed with '@'
            if (error_reporting() == 0) {
                return;
            }

            $arg_list      = func_get_args();

            $type          = $arg_list[0];
            $error_message = $arg_list[1];
            $file_name     = $arg_list[2];
            $line_number   = $arg_list[3];
            $data          = $arg_list[4];

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
                       E_ERROR              => 'ERROR',
                       E_WARNING            => 'WARNING',
                       E_PARSE              => 'PARSING ERROR',
                       E_NOTICE             => 'NOTICE',
                       E_CORE_ERROR         => 'CORE ERROR',
                       E_CORE_WARNING       => 'CORE WARNING',
                       E_COMPILE_ERROR      => 'COMPILE ERROR',
                       E_COMPILE_WARNING    => 'COMPILE WARNING',
                       E_USER_ERROR         => 'USER ERROR',
                       E_USER_WARNING       => 'USER WARNING',
                       E_USER_NOTICE        => 'USER NOTICE',
                       E_STRICT             => 'STRICT NOTICE',
                       E_RECOVERABLE_ERROR  => 'RECOVERABLE ERROR'
                  );

            // create error message
            if (array_key_exists($type, $error_types)) {
                $err = $error_types[$type];
            } else {
                $err = 'CAUGHT EXCEPTION';
            }

            $err_msg = str_replace(Debug::$APP_ROOT, '', "$err - $file_name/$line_number : $error_message");

            $trace = Debug::_readableBacktrace(array_reverse(debug_backtrace()));

            // what to do
            switch ($type) {
                case E_NOTICE:
                case E_USER_NOTICE:
                case E_STRICT:
                case E_WARNING:
                case E_USER_WARNING:
                    Debug::__errorMessage($err_msg . CRLF . $trace);
                    return;
                default:
                    exit(Debug::__errorMessage($err_msg . CRLF . $trace));
            }

        }

        static function __errorMessage($message) {
            print $message;
        }

        static function __htmlentities($string) {
            if (Debug::$USE_HTML) {
                $string = htmlentities($string);
            }
            return $string;
        }

    }
?>