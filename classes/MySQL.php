<?php
/*******************************************************************************
 * LightPress - http://lightpress.org
 *
 * Copyright 2004-2005 Ludovico Magnocavallo.
 * Released under the GPL http://www.gnu.org/copyleft/gpl.html
 *
 * developers:
 *              Ludovico Magnocavallo <ludo@asiatica.org>
 *              Jerome Lavigne <jerome@vapourtrails.ca>
 *              Luca Lizzeri <luca.lizzeri@gmail.com>
 *
 * $Id: MySQL.php 60 2005-08-08 15:20:11Z ludo $
 ******************************************************************************/

class DBlite_MySQL {
    var $user;
    var $passwd;
    var $server;
    var $db;
    var $_connection;
    var $_result;
    var $count;
    
    var $queries = array();
	var $qrytime = array();
    
    function DBlite_MySQL($params = array()) {
        foreach ($params as $k => $v)
            $this->$k = $v;
        
    }
    
    function &_connect() {
        
        if (!is_null($this->_connection))
            return $this->_connection;
        
        $connection = mysql_connect($this->server, $this->user, $this->passwd);
        if (!$connection) {
            $msg = sprintf('connection failed to server %s user %s (mysql error: %s)', $this->server, $this->user, mysql_error());
            trigger_error($msg, E_USER_ERROR);
        } elseif (!mysql_select_db($this->db, $connection)) {
            $msg = sprintf('Cannot select db %s (MySQL error: %s)', $this->db, mysql_error());
            trigger_error($msg, E_USER_ERROR);
        }
        $this->_connection =& $connection;
        return $connection;
    }
    
    function _free() {
        if (is_resource($this->_result))
            mysql_free_result($this->_result);
        $this->_result = null;
        $this->count;
    }
            
    function query($statement) {
        
        $connection =& $this->_connect();
        
        if (is_null($connection))
            return trigger_error("No connection, cannot execute query '$statement'", E_USER_ERROR);
        
        $this->_free();
        $this->queries[] = $statement;
		$start = microtime(true);
        $result = mysql_query($statement, $this->_connection);
        $this->qrytime[] = microtime(true) - $start;

		if (!$result) {
            $msg = sprintf('Cannot execute query %s (MySQL error: %s)', $statement, mysql_error());
            return trigger_error($msg, E_USER_ERROR);
        }
        $this->_result = $result;
        if (is_resource($this->_result))
            $this->count = mysql_num_rows($this->_result);
        else if (!$this->_result)
            return trigger_error("No result returned from query $statement", E_USER_ERROR);
        else
            $this->count = mysql_affected_rows($this->_connection);
        return $this->count;
        
    }
    
    function fields() {
        if (empty($this->_result))
            return false;
        if (!$this->_connection)
            $this->_connect();
        if ($this->isError($this->_connection))
            return $this->_connection;
        if ($this->isError($this->_result))
            return $this->_result;
        $fields = array();
        $num_fields = mysql_num_fields($this->_result);
        $i = 0;
        while ($i < $num_fields) {
            $field = mysql_fetch_field($this->_result, $i);
            $fields[$field->name] = $field;
            $i++;
        }
        return $fields;
    }
    
    function next() {
        if (is_null($this->_result))
            return $this->_result;
        return mysql_fetch_assoc($this->_result);
    }
    
    function get($field) {
        $row = $this->next();
        return $row[$field];
    }
    
	function getID() {
		return mysql_insert_id($this->_connection);
	}

    function &all() {
        if (is_null($this->_result))
            return $this->_result;
        $records = array();
        while ($row = mysql_fetch_assoc($this->_result))
            $records[] = $row;
        return $records;
    }

	function debug() {
		$out = '';
		foreach($this->queries as $i => $qry) {
			$out .= '[' . $i . ']  ' . number_format($this->qrytime[$i], 3) . 's  ';
			$out .= str_replace(array("\n", "\t"), ' ', $qry) . "\n";
		}
		return $out;
	}
}

?>