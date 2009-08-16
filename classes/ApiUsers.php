<?php
/******************************************************************************
 * ApiUsers class  --  Darkcanuck's Roborumble Server
 *
 * $HeadURL$
 * $Date$
 * $Revision$
 * $Author$
 *
 * Copyright 2008-2009 Jerome Lavigne (jerome@darkcanuck.net)
 * Released under GPL version 3.0 http://www.gnu.org/licenses/gpl-3.0.html
 *****************************************************************************/

class ApiUsers {
	
	private $db = null;
	
	private $table = 'api_stats';
	private $fields = array('apikey', 'apiuser', 'hour', 'minute', 'created', 'updated', 'hreq', 'mreq', 'requests');
	
	private $statstable = 'upload_stats';
	
	private $alist = null;
	private $order = '';
		
	function __construct($db, $order='') {
		$this->db = $db;
		$this->order = $order;
	}
	
	function queryList() {
		$qry = "SELECT " . implode(', ', $this->fields) . " FROM {$this->table}";
		if ($this->order!='')
			$qry .= " ORDER BY `" . mysql_escape_string($this->order) . "` DESC";
		$this->db->query($qry);
		foreach($this->db->all() as $rs) {
			$this->alist[ $rs['apiuser'] ][ $rs['apikey'] ] = $rs;
		}
	}
	
	function queryUser($name, $key) {	    
    	$qry = "SELECT " . implode(', ', $this->fields) . " FROM {$this->table} " .
    	        "WHERE apikey='"  . mysql_escape_string($key)  . "' " .
    	        "  AND apiuser='" . mysql_escape_string($name) . "' ";
		if ($this->db->query($qry) > 0)
		    return $this->db->next();
	    else
		    return null;
	}
	
	function getList() {
		if ($this->alist==null)
			$this->queryList();
		return $this->alist;
	}
		
	function getUser($name, $key) {
		if ($this->alist==null)
			$this->queryList();
		if (!isset($this->alist[ $name ][ $key ]))
			trigger_error('Invalid API user key "' . substr($name, 0, 50) . '-' . substr($key, 0, 50) . '"', E_USER_ERROR);
		return $this->alist[ $name ][ $key ];
	}
	
	function createUser($name, $key) {
		$qry = sprintf("INSERT INTO {$this->table} SET apikey = '%s', apiuser = '%s', created=NOW() ",
							mysql_escape_string((int)$key), mysql_escape_string($name));
		if ($this->db->query($qry) > 0) {
			// update local copy
			if ($this->alist!=null)
			    $this->queryList();
			return true;
		}
		// failed
		trigger_error('Could not create new API user!', E_USER_ERROR);
		return false;
	}
	
	function updateUser($name, $key, $reset=false) {
	    $user = $this->getUser($name, $key);
		$time = getdate();
	    
	    // increment request numbers if still within window
	    $hreq = 1;
	    $mreq = 1;
	    if (!$reset) {
	        $hreq = ($user['hour']==$time['hours']) ? $user['hreq']+1 : 1;
	        $mreq = ($user['minute']==$time['minutes']) ? $user['mreq']+1 : 1;
	    }
	    
	    // update database
		$qry = "UPDATE {$this->table} " .
		        "SET requests=requests+1, hreq='" . (int)$hreq . "', mreq='" . (int)$mreq . "', " .
		            "hour='" . (int)$time['hours'] . "', minute='" . (int)$time['minutes'] . "' " .
                "WHERE apikey='"  . mysql_escape_string($key)  . "' " .
    	        "  AND apiuser='" . mysql_escape_string($name) . "' ";
		$ok = ($this->db->query($qry) > 0);
		
		// update local copy
	    if ($this->alist!=null) {
	        $this->alist[$name][$key]['hrequests'] = $hreq;
	        $this->alist[$name][$key]['mrequests'] = $mreq;
			$this->alist[$name][$key]['hour']   = (int)$time['hours'];
			$this->alist[$name][$key]['minute'] = (int)$time['minutes'];
	    }
        return $ok;
	}
	
}

?>