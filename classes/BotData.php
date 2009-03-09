<?php


class BotData {
	
	private $fullname  = '';
	private $package   = '';
	private $botname   = '';
	private $version   = '';
	private $timestamp = '';
	
	private $id = -1;
	
	function __construct($fullname) {
		$this->fullname = trim($fullname);
		if (preg_match('/[^a-zA-Z0-9 \-]/', $fullname))
			trigger_error('Invalid characters in robot name "' . substr($fullname, 0, 50) . '"', E_USER_ERROR);
		
		$parts = explode(' ', $this->fullname, 2);
		$this->version = $parts[1];
		
		$chunk = explode('.', trim($parts[0]), 2);
		$this->package = $chunk[0];
		$this->botname = isset($chunk[1]) ? $chunk[1] : '';
		
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
			trigger_error('Could not find bot "' . substr($this->fullname, 0, 50) . ' in database!', E_USER_ERROR);
		
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
}

?>