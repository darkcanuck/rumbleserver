<?php
/******************************************************************************
 * BotData class  --  Darkcanuck's Roborumble Server
 *
 * Copyright 2008-2011 Jerome Lavigne (jerome@darkcanuck.net)
 * Released under GPL version 3.0 http://www.gnu.org/licenses/gpl-3.0.html
 *****************************************************************************/

class BotData {
	
	private $fullname  = '';
	private $package   = '';
	private $botname   = '';
	private $version   = '';
	private $timestamp = '';
	
	private $id = -1;
	
	function __construct($fullname) {
		$this->fullname = trim($fullname);
		if (strlen($this->fullname)>70)
			trigger_error('Robot name "' . substr($fullname, 0, 70) . '..." is too long (max 70 chars)', E_USER_ERROR);
        
		if (preg_match('/[^a-zA-Z0-9 \.\-\_]/', $this->fullname))
			trigger_error('Invalid characters in robot name "' . substr($fullname, 0, 50) . '"', E_USER_ERROR);
		
		$parts = explode(' ', $this->fullname, 2);
		$this->version = $parts[1];
		if (strpos($this->version, '_') !== false)
			trigger_error('Underscores cannot be used in robot version "' . substr($fullname, 0, 50) . '"', E_USER_ERROR);
		
		$chunk = explode('.', trim($parts[0]), 2);
		$this->package = $chunk[0];
		$this->botname = isset($chunk[1]) ? $chunk[1] : '';
		
		if (strlen($this->package)>20)
			trigger_error('Robot package "' . substr($this->package, 0, 50) . '..." is too long (max 20 chars)', E_USER_ERROR);
        if (strlen($this->version)>20)
			trigger_error('Robot version "' . substr($this->version, 0, 50) . '..." is too long (max 20 chars)', E_USER_ERROR);
        
		$valid  =  (!empty($this->package) || $this->package[0]=='0')
				&& (!empty($this->botname) || $this->botname[0]=='0')
				&& (!empty($this->version) || $this->version[0]=='0')
				&& (strpos($this->version, ' ')===false);			// explode assumed only one space in full name
		if (!$valid)
			trigger_error('Invalid robot name "' . substr($fullname, 0, 50) . '"', E_USER_ERROR);
	}
	
	// find in database
	function getID($db, $addifmissing=true) {
		
		$qry = sprintf("SELECT bot_id, timestamp " .
					   	" FROM bot_data " .
			 			"WHERE full_name = '%s'", 
		 				mysql_escape_string($this->fullname));
        if ($db->query($qry) > 0) {
			$rs = $db->next();
			$this->id = $rs['bot_id'];
			$this->timestamp = $rs['timestamp'];
			return $this->id;
		}
		
		if (!$addifmissing)
			trigger_error('Could not find bot "' . substr($this->fullname, 0, 50) . '" in database!', E_USER_ERROR);
		
		// bot missing from database, create record
		$qry = 	sprintf("INSERT INTO bot_data SET full_name = '%s', package_name = '%s',
							bot_name = '%s', bot_version = '%s', timestamp = NOW()",
							mysql_escape_string($this->fullname),
							mysql_escape_string($this->package),
							mysql_escape_string($this->botname),
							mysql_escape_string($this->version) );
		if ($db->query($qry) > 0) {
			$this->id = $db->getID();
			$this->timestamp = strftime('%Y-%m-%d %T');
			return $this->id;
		}
		
		// failed
		trigger_error('Could not find bot "' . substr($this->fullname, 0, 50) . ' in database!', E_USER_ERROR);
		return 0;
	}
	
	function getTimestamp() {
		return $this->timestamp;
	}
	
	function getVersions($db, $limit=-1) {
	    $qry = sprintf("SELECT bot_id, full_name, package_name, bot_name, bot_version, timestamp " .
					   	" FROM bot_data " .
			 			"WHERE package_name = '%s' AND bot_name = '%s' AND full_name <> '%s'" .
			 			" ORDER BY timestamp DESC ",
		 				mysql_escape_string($this->package),
		 				mysql_escape_string($this->botname),
		 				mysql_escape_string($this->fullname) );
        if ($limit > 0)
            $qry .= " LIMIT " . (int)$limit;
        if ($db->query($qry) > 0)
			return $db->all();
		else
	        return null;
	}
}

?>