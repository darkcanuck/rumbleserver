<?php
/******************************************************************************
 * PriorityBattles class  --  Darkcanuck's Roborumble Server
 *
 * Copyright 2008-2011 Jerome Lavigne (jerome@darkcanuck.net)
 * Released under GPL version 3.0 http://www.gnu.org/licenses/gpl-3.0.html
 *****************************************************************************/

class PriorityBattles {
	
	private $prop = null;
	private $ignoreData = array();
	private $ignoreNames = array();
	
	const PROPERTY  = 'priority_ignored';
	const DELIM1    = ';';
	const DELIM2    = ':';
	
	const RAND_VALUE = 50;
	
	function __construct($prop) {
		$this->prop = $prop;
		$this->loadIgnored();
	}
	
	function loadIgnored() {
	    $do_update = false;
        $current_ts = time();
        $setting = $this->prop->get(self::PROPERTY);
        if ($setting == '')
            return;
        
        $entries = explode(self::DELIM1, $setting);
        foreach ($entries as $e) {
            $parts = explode(self::DELIM2, $e);
            $name = $parts[0];
            $expiry = (count($parts) > 1) ? (int)$parts[1] : 0;
            if (($name == '') || ($expiry <= $current_ts)) {
                $do_update = true;
            } else {
                $this->ignoreData[]  = array('name' => $name, 'expiry' => $expiry);
                $this->ignoreNames[] = $name;
            }
        }
        if ($do_update)
            $this->saveIgnored();
	}
	
	function saveIgnored() {
	    $ignored = array();
	    foreach ($this->ignoreData as $i)
	        $ignored[] = sprintf('%s' . self::DELIM2 . '%d', $i['name'], $i['expiry']);
	    $this->prop->set(self::PROPERTY, implode(self::DELIM1, $ignored));
	}
	
	function addIgnored($name) {
	    if (in_array($name, $this->ignoreNames))
	        return;
	    $this->ignoreData[] = array('name' => $name, 'expiry' => time() + 1200);
	    $this->ignoreNames[] = $name;
	    $this->saveIgnored();
	}
	
	function nextPairing($id, $pairings, $botlist) {
	    if (!is_array($id) || (count($id) < 2) || !is_array($pairings) || !is_array($botlist))
	        return array();
        
        $ignore = array();
	    $complete = array();
	    foreach($id as $k => $v) {
	        if (!isset($botlist[$v]) || in_array($botlist[$v]['name'], $this->ignoreNames))
	            $ignore[] = $v;
	    	$complete[$v] = array($v => 1);
		}
		
		$missing = array();
		$incomplete = array();
		$needmore = array();
		
		$min_battles = 2000;
		$paircount = count($botlist) - 1;
		
		foreach($pairings as $pair) {
		    if (in_array($pair['bot_id'], $ignore) || in_array($pair['vs_id'], $ignore))
			    continue;
		    
		    $complete[ $pair['bot_id'] ][ $pair['vs_id'] ] = 1;
			
		    if ($pair['battles'] < $min_battles) {
			    $min_battles = $pair['battles'];
			    $needmore = array();
			}
			if ($pair['battles'] == $min_battles) {
			    $needmore[] = array($botlist[ $pair['bot_id'] ]['name'], $botlist[ $pair['vs_id'] ]['name']);
			}
		}
		
		foreach($id as $k => $v) {
		    if (in_array($v, $ignore))
		        continue;
		    
    		foreach($botlist as $i => $bot) {
    		    if (in_array($i, $ignore))
		            continue;
        		
        		if (!isset($complete[$v][$i]))
    				$missing[] = array($botlist[$v]['name'], $botlist[$i]['name']);
    			if ($bot['pairings'] != $paircount)
    			    $incomplete[] = $bot['name'];
    		}
		}
		
		// return priority pairings in the following order:
		//  1. missing pairings
		//  2. single battle pairings
		//  3. bots needing a battle after a bot removal
		//  4. lowest battle pairing or no priority pairings (selected randomly)
		if (count($missing) > 0) {
		    return $this->selectRandom($missing);
		
		} else if (($min_battles == 1) && (count($needmore) > 0)) {
		    return $this->selectRandom($needmore);
		
		} else if ((count($incomplete) > 0) && (count($botlist) > 10)) {
		    $select = $this->selectRandom($incomplete);
		    $bot1 = $select[0];
		    $bot2 = '';
		    $botids = array_keys($botlist);
		    $tries = 0;
		    while (($bot2 == '') && ($tries < 10)) {
		        $r = $botids[ rand(0, count($botids)-1) ];
		        if (isset($botlist[$r]) && !in_array($r, $ignore) && ($botlist[$r]['name'] != $bot1))
		            $bot2 = $botlist[$r]['name'];
		        $tries++;
		    }
		    return ($bot2 != '') ? array( array($bot1, $bot2) ) : array();
		
		} else if ((count($needmore) > 0) && (rand(1,100) > self::RAND_VALUE)) {
		    return $this->selectRandom($needmore);
		
		} else {
		    return array();
		}
	}
	
	function selectRandom($data, $count=1) {
	    // TODO: allow count > 1
	    return array( $data[ rand(0, count($data)-1) ] );
	}
}

?>