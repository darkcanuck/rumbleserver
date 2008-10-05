<?php


class UploadUsers {
	
	private $db = null;
	
	private $table = 'upload_users';
	private $fields = array('user_id', 'username', 'ip_addr', 'version', 'battles', 'created', 'updated');
	
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
	
	function getID($name, $ip, $version) {
		if ( (empty($name) && $name[0]!='0') || (empty($ip) && $ip[0]!='0')
				|| (empty($version) && $version[0]!='0') )
			trigger_error('Invalid user data', E_USER_ERROR);
		
		if ($this->udata==null)
			$this->queryList();
		if (!isset($this->udata[ $name ][ $ip ][ $version ]))
			trigger_error('Invalid user name "' . substr($name, 0, 50) . '"', E_USER_ERROR);
		return $this->udata[ $name ][ $ip ][ $version ]['user_id'];
	}
	
	function createUser($name, $ip, $version) {
		$qry = sprintf("INSERT INTO {$this->table} SET username = '%s', ip_addr = '%s',
							version = '%s', battles = '0', created=NOW() ",			// updated timestamp is automatic
							mysql_escape_string($name), mysql_escape_string($ip),
							mysql_escape_string($version));
		if ($this->db->query($qry) > 0) {
			$id = $db->getID();
			// update local copy
			$rs = array('user_id' => $id, 'username' => $name, 'ip_addr' => $ip_addr,
										'version' => $version, 'battles' => 0,
										'created' => strftime('%Y-%m-%d %T'),
										'updated' => strftime('%Y-%m-%d %T')
										);
			$this->ulist[ $id ] = $rs;
			$this->udata[ $rs['username'] ][ $rs['ip_addr'] ][ $rs['version'] ] = $rs;
			return $id;
		}
		// failed
		trigger_error('Could not create new user id!', E_USER_ERROR);
		return 0;
	}
	
	function updateUser($id, $newbattles) {
		// update local copy
		if ($this->ulist==null)
			$this->queryList();
		if (!isset($this->ulist[$id]))
			trigger_error('Could not find user ' . ((int)$id) . '!' . print_r($this->ulist[$id], true), E_USER_ERROR);
		
		$user =& $this->ulist[$id];
		$user['battles'] += $newbattles;
		$qry = "UPDATE {$this->table} SET battles='" . mysql_escape_string($user['battles']) . "'
				WHERE user_id = '" . mysql_escape_string($id) . "'";
		return($this->db->query($qry) > 0);
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
	
}

?>