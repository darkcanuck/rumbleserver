<?php
/******************************************************************************
 * ServerProperties class  --  Darkcanuck's Roborumble Server
 *
 * $HeadURL$
 * $Date$
 * $Revision$
 * $Author$
 *
 * Copyright 2008-2009 Jerome Lavigne (jerome@darkcanuck.net)
 * Released under GPL version 3.0 http://www.gnu.org/licenses/gpl-3.0.html
 *****************************************************************************/

class ServerProperties {
	
	private $db = null;
	private $properties = null;
		
	function __construct($db) {
		$this->db = $db;
	}
	
	function queryData() {
		$qry = "SELECT name, value FROM properties";
		$this->db->query($qry);
		foreach($this->db->all() as $rs)
			$this->properties[ $rs['name'] ] = $rs['value'];
	}
	
	function get($name, $default='') {
		if ($this->properties==null)
			$this->queryData();
		if (!isset($this->properties[ $name ]))
			$this->set($name, $default);
		return $this->properties[ $name ];
	}
	
	function getInt($name, $default=0) {
		return ( (int)$this->get($name, $default) );
	}
	
	function set($name, $value) {
		if ($this->properties==null)
			$this->queryData();
		if (!isset($this->properties[ $name ]))
			$qry = "INSERT INTO properties
					SET name='" . mysql_escape_string($name) . "',
						value='" . mysql_escape_string($value) . "'";
		else
			$qry = "UPDATE properties
					SET    value='" . mysql_escape_string($value) . "'
					WHERE  name='" . mysql_escape_string($name) . "'";
		if($this->db->query($qry) > 0)
			$this->properties[ $name ] = $value;
	}
	
}

?>