<?php
/******************************************************************************
 * GameType class  --  Darkcanuck's Roborumble Server
 *
 * $HeadURL$
 * $Date$
 * $Revision$
 * $Author$
 *
 * Copyright 2008-2009 Jerome Lavigne (jerome@darkcanuck.net)
 * Released under GPL version 3.0 http://www.gnu.org/licenses/gpl-3.0.html
 *****************************************************************************/

class GameType {
	
	private $codelist = array(
	                        'roborumble'    => 'R',
	                        'minirumble'    => 'X',
	                        'microrumble'   => 'Y',
	                        'nanorumble'    => 'Z',
	                        'meleerumble'       => 'M',
	                        'minimeleerumble'   => 'N',
	                        'micromeleerumble'  => 'O',
	                        'nanomeleerumble'   => 'P',
	                        'teamrumble'    => 'T',
	                        'twinduel'      => 'D'
	                        );
	
	private $code  = '';
	private $valid = false;
	private $survival_scoring = false;
	
	function __construct($data, $gamedata=null) {
		
		if (is_array($data)) {
		    $version = $data['version'];
		    $game    = $data['game'];
		    $field   = $data['field'];
		    $rounds  = $data['rounds'];
		    $melee   = ($data['melee'] != 'NOT');
		    $teams   = ($data['teams'] != 'NOT');
	    } else {
	        $version = $data;
	        $game    = $gamedata;
	        $field   = '';
	        $melee   = '';
	        $teams   = '';
	    }
		
		switch ($version) {
		
			case '1':
			    $this->code = $this->nameToCode($game);
				switch($this->code) {
					case 'R':   // 1v1 rumble
					case 'X':
					case 'Y':
					case 'Z':
						$this->valid = ($field=='800x600') && ($rounds=='35') && !$melee && !$teams;
						break;
					
					case 'M':   // melee rumble
					case 'N':
					case 'O':
					case 'P':
						$this->valid = ($field=='1000x1000') && ($rounds=='35') && $melee && !$teams;
						break;
						
					case 'T':   // team rumble
						$this->valid = ($field=='1200x1200') && ($rounds=='10') && !$melee && $teams;
						break;
						
					case 'D':   // twin duel
						$this->valid = ($field=='800x800') && ($rounds=='75') && !$melee && $teams;
						$this->survival_scoring = true;
						break;
				}
				break;
		}

		if ($this->code=='')
			trigger_error('The game type "' . substr($game, 0, 20) . '" is not supported by this server!', E_USER_ERROR);
	}
	
	function isValid() {
		if (!$this->valid)
			trigger_error('The game parameters given are not supported by this server!', E_USER_ERROR);
		return $this->valid;
	}
	
	function getCode() {
		return $this->code;
	}
	
	function nameToCode($name) {
	    if (isset($this->codelist[$name]))
	        return $this->codelist[$name];  // return code for this game type
        if (in_array($name, $this->codelist))
            return $name;                   // already a game code
        return '';
	}
	
	function checkScores($data) {
		// make sure scores are sensible for game type
		switch($this->code) {
			case 'R':
			case 'X':
			case 'Y':
			case 'Z':
				if (($data['survival1'] + $data['survival2']) < 34)
					trigger_error('Survival count should total at least 34 for roborumble -- check your client configuration!', E_USER_ERROR);
				if (($data['score1']>10000) || ($data['score2']>10000))
					trigger_error('Score are too high for roborumble -- check your client configuration!', E_USER_ERROR);
				break;
				
			default:
				break;
		}
		return true;
	}
	
	function useSurvival() {
	    return $this->survival_scoring;
	}	
}

?>