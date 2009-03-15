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
	
	private $code  = '';
	private $valid = false;
	
	function __construct($version, $game, $field, $rounds) {
		
		switch ($version) {
		
			case '1':
				switch($game) {
					case 'roborumble':
						$this->code  = 'R';
						$this->valid = ($field=='800x600') && ($rounds=='35');
						break;
					case 'minirumble':
						$this->code  = 'X';
						$this->valid = ($field=='800x600') && ($rounds=='35');
						break;
					case 'microrumble':
						$this->code  = 'Y';
						$this->valid = ($field=='800x600') && ($rounds=='35');
						break;
					case 'nanorumble':
						$this->code  = 'Z';
						$this->valid = ($field=='800x600') && ($rounds=='35');
						break;
					
					case 'meleerumble':
						$this->code  = 'M';
						$this->valid = ($field=='1000x1000') && ($rounds=='35');
						break;
					case 'minimeleerumble':
						$this->code  = 'N';
						$this->valid = ($field=='1000x1000') && ($rounds=='35');
						break;
					case 'micromeleerumble':
						$this->code  = 'O';
						$this->valid = ($field=='1000x1000') && ($rounds=='35');
						break;
					case 'nanomeleerumble':
						$this->code  = 'P';
						$this->valid = ($field=='1000x1000') && ($rounds=='35');
						break;
						
					case 'teamrumble':
						$this->code  = 'T';
						$this->valid = ($field=='1200x1200') && ($rounds=='10');
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
		
}

?>