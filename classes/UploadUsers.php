<?php
/******************************************************************************
 * UploadUsers class  --  Darkcanuck's Roborumble Server
 *
 * $HeadURL$
 * $Date$
 * $Revision$
 * $Author$
 *
 * Copyright 2008-2009 Jerome Lavigne (jerome@darkcanuck.net)
 * Released under GPL version 3.0 http://www.gnu.org/licenses/gpl-3.0.html
 *****************************************************************************/

class UploadUsers {
	
	private $db = null;
	
	private $table = 'upload_users';
	private $fields = array('user_id', 'username', 'ip_addr', 'version', 'battles', 'created', 'updated');
	
	private $statstable = 'upload_stats';
	
	private $ulist = null;
	private $udata = null;
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
			$this->ulist[ $rs['user_id'] ] = $rs;
			$this->udata[ $rs['username'] ][ $rs['ip_addr'] ][ $rs['version'] ] = $rs;
		}
	}
	
	function queryUser($name, $ip, $version) {	    
    	$qry = "SELECT " . implode(', ', $this->fields) . " FROM {$this->table} " .
    	        "WHERE username='" . mysql_escape_string($name) . "' " .
    	        "  AND ip_addr='" . mysql_escape_string($ip) . "' " . 
    	        "  AND version='" . mysql_escape_string($version) . "' ";
		if ($this->db->query($qry) > 0)
		    return $this->db->next();
	    else
		    return null;
	}
	
	function getList() {
		if ($this->ulist==null)
			$this->queryList();
		return $this->ulist;
	}
		
	function getUser($id) {
		if ($this->ulist==null)
			$this->queryList();
		if (!isset($this->ulist[ $id ]))
			trigger_error('Invalid user id "' . ( (int) $id ) . '"', E_USER_ERROR);
		return $this->ulist[ $id ];
	}
	
	function getID($name, $ip, $version, $addifmissing=true) {
		if ( (empty($name) && $name[0]!='0') || (empty($ip) && $ip[0]!='0')
				|| (empty($version) && $version[0]!='0') )
			trigger_error('Invalid user data', E_USER_ERROR);
		if ($name=='Put_Your_Name_Here')
		    trigger_error('You must set your username in the config file!', E_USER_ERROR);
		if (strlen($name)>20)
			trigger_error('User name "' . substr($name, 0, 50) . '" is too long (max 20 chars)', E_USER_ERROR);
		$ip = substr($ip, 0, 15);
		$version = substr($version, 0, 20);
        
		$user = null;
		if ($this->udata==null)
		    $user = $this->queryUser($name, $ip, $version);
		else if (isset($this->udata[ $name ][ $ip ][ $version ]))
		    $user = $this->udata[ $name ][ $ip ][ $version ];

        if ($user==null) {
            if ($addifmissing)
                return $this->createUser($name, $ip, $version);
            else
                trigger_error('Invalid user name "' . substr($name, 0, 50) . '"', E_USER_ERROR);
        }
		return $user['user_id'];
	}
	
	function createUser($name, $ip, $version) {
		$qry = sprintf("INSERT INTO {$this->table} SET username = '%s', ip_addr = '%s',
							version = '%s', battles = '0', created=NOW() ",			// updated timestamp is automatic
							mysql_escape_string($name), mysql_escape_string($ip),
							mysql_escape_string($version));
		if ($this->db->query($qry) > 0) {
			$id = $this->db->getID();
			if ($id<1)
			    trigger_error('Error creating new user id!', E_USER_ERROR);
			
			// update local copy
			if ($this->ulist!=null)
			    $this->queryList();
			return $id;
		}
		// failed
		trigger_error('Could not create new user id!', E_USER_ERROR);
		return 0;
	}
	
	function updateUser($id, $newbattles) {
		$qry = "UPDATE {$this->table} SET battles=battles+" . ((int)$newbattles) . "
				WHERE user_id = '" . mysql_escape_string($id) . "'";
		$ok = ($this->db->query($qry) > 0);
		
		// update local copy
		if ($this->ulist!=null)
			$this->queryList();
        return $ok;
	}
	
	function getContributors() {
		$qry = "SELECT username, SUM(battles) AS battles,
						MIN(created) AS created, MAX(updated) AS updated
				FROM {$this->table}
				GROUP BY username
				ORDER BY battles DESC";
		if ($this->db->query($qry)>0)
			return $this->db->all();
		else
			return null;
	}
	
	
	/* UPLOAD STATISTICS */
	
	function updateStats($id, $gametype, $newbattles) {
	    $new = (int)$newbattles;
	    $game = $gametype[0];
	    $today = strftime('%Y-%m-%d');
	    $userid = mysql_escape_string($id);
		$qry = "UPDATE {$this->statstable} SET battles=battles+$new
				 WHERE gametype = '$game'
				   AND date = '$today'
				   AND user_id = '$userid'";
				   
		if ($this->db->query($qry) > 0) {
		    return true;
	    } else {
	        // insert new record
	        $qry = "INSERT INTO {$this->statstable}
	                SET gametype = '$game',
	                    date = '$today',
    				    user_id = '$userid',
    				    battles = $new";
		    return ($this->db->query($qry) > 0);
		}
	}
	
	function statsMonthly($gametype, $year, $month) {
	    $game = $gametype[0];
	    $yearnum  = sprintf('%04d', (int)$year);
	    $monthnum = sprintf('%02d', (int)$month);
	    $start = "$year-$month-01";
	    $end = "$year-$month-31";
		$qry = "SELECT u.username AS username,
		                SUM(s.battles) AS battles,
						MAX(s.date) AS updated,
						MAX(u.version) as version
				FROM {$this->statstable} AS s
				INNER JOIN {$this->table} AS u ON s.user_id = u.user_id
				WHERE s.gametype = '$game' AND s.date >= '$start' AND s.date <= '$end'
				GROUP BY username
				ORDER BY battles DESC";
		if ($this->db->query($qry)>0)
			return $this->db->all();
		else
			return null;
	}
	
	function statsLast30($gametype) {
	    $game = $gametype[0];
	    $start = strftime('%Y-%m-%d', time() - (30 * 24 * 60 * 60));
		$qry = "SELECT u.username AS username,
		                SUM(s.battles) AS battles,
						MAX(s.date) AS updated,
						MAX(u.version) as version
				FROM {$this->statstable} AS s
				INNER JOIN {$this->table} AS u ON s.user_id = u.user_id
				WHERE s.gametype = '$game' AND s.date >= '$start'
				GROUP BY username
				ORDER BY battles DESC";
		if ($this->db->query($qry)>0)
			return $this->db->all();
		else
			return null;

	}
}

?>