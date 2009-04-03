<?php
/******************************************************************************
 * ErrorHandler class  --  Darkcanuck's Roborumble Server
 *
 * 	Custom error handling to prevent bad string output from causing exceptions
 *  in the rumble client.  Cannot handle E_ERROR, E_PARSE, E_CORE_ERROR,
 *  E_CORE_WARNING, E_COMPILE_ERROR, E_COMPILE_WARNING
 *
 * $HeadURL$
 * $Date$
 * $Revision$
 * $Author$
 *
 * Copyright 2008-2009 Jerome Lavigne (jerome@darkcanuck.net)
 * Released under GPL version 3.0 http://www.gnu.org/licenses/gpl-3.0.html
 *****************************************************************************/

if (!defined('E_DEPRECATED'))
	define('E_DEPRECATED', 8192);
if (!defined('E_USER_DEPRECATED'))
	define('E_USER_DEPRECATED', 16384);	

class ErrorHandler {
	
	private $is_client = false;
	private $no_output = false;
	
	private $debugmode = false;
	private $dumpvars  = false;
	
	private $errorname = array (
	                E_WARNING            => 'WARNING',
	                E_NOTICE             => 'NOTICE',
	                E_USER_ERROR         => 'ERROR',
	                E_USER_WARNING       => 'WARNING',
	                E_USER_NOTICE        => 'NOTICE',
	                E_STRICT             => 'NOTICE',
	                E_RECOVERABLE_ERROR  => 'ERROR',
	                E_DEPRECATED         => 'DEPRECATED',
	                E_USER_DEPRECATED    => 'DEPRECATED',
	                );		// based on PHP.net example
	private $fatal_errors = array(E_USER_ERROR, E_RECOVERABLE_ERROR);
	private $trace_errors = array(E_USER_ERROR, E_USER_WARNING, E_USER_NOTICE);
	
	function __construct($isclient=false, $debugmode=false, $dumpvars=false) {
		$this->is_client = $isclient;
		$this->debugmode = $debugmode;
		$this->dumpvars  = $dumpvars;
		
		// replace PHP error handler with an instance of this class
		set_error_handler( array($this, 'raiseError') );
	}

	function setClient($flag) {
		$this->is_client = $flag;
	}
	function setNoOutput($flag) {
		$this->no_output = $flag;
	}
	function setDebugMode($flag) {
		$this->debugmode = $flag;
	}
	function setDumpVars($flag) {
		$this->dumpvars = $flag;
	}
	
	function raiseError($errno, $errstr, $errfile, $errline, $errcontext) {
		
		// rumble client doesn't like <> or [] brackets in output
		$errclean = ($this->is_client) ? str_replace(array('<', '>', '[', ']'), '|', $errstr) : $errstr;
		
		// create standard message
		$defaultmsg = $this->errorname[$errno] . ': ' . $errclean;
		if ($this->debugmode && isset($errfile))
			$defaultmsg .= ' in ' . $errfile;
		if ($this->debugmode && isset($errline))
			$defaultmsg .= ' @ ' . $errline;
		if ($this->dumpvars && isset($errcontext) && in_array($errno, $this->trace_errors))
		 	$defaultmsg .= ' context=' . print_r($err_context, true);
		
		// send to error log
		error_log($defaultmsg);
		
		// file requests should send no data
		if ($this->no_output)
			die('');
		
		if ($this->is_client && !$this->debugmode) {
			// handle duplicate inserts gracefully
			if (strpos($errclean, 'Duplicate')!==false)
				die("OK.  Duplicate result thrown away!");
			
			// special handling for clients - reduced error messages
			if (in_array($errno, $this->fatal_errors)) {
				// fail and exit
				die( ((substr($errclean,0,2)=='OK') ? '' : 'FAIL. ') . $errclean . "\n");
			}
			// ignore non-fatal errors
		
		} else {
			if (in_array($errno, $this->fatal_errors)) {
				// fail and exit
				die($defaultmsg);
			} else if ($this->debugmode) {
				echo($defaultmsg . ($this->is_client ? "\n" : '<br/>') );
			}
		}
		
		return;
	}
	
}

?>