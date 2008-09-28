<?php

class BattleResults {
	
	private $db = null;
	
	// battle data
	private $version    = '';
	private $user       = '';
	private $timestamp  = '';
	private $gametype   = '';
	private $bot1       = '';
	private $score1     = '';
	private $bulletdmg1 = '';
	private $survival1  = '';
	private $bot2       = '';
	private $score2     = '';
	private $bulletdmg2 = '';
	private $survival2  = '';
	
	
	function __construct($db) {
		$this->db = $db;
	}
		
	function saveBattle($data) {	
		foreach ($data as $k => $v)
			$this->$k = trim($v);
		
		// check battle data
		$ok = true;	
		$ok &= !empty($this->version);
		$ok &= !empty($this->user);
		$ok &= !empty($this->timestamp);		// TODO: sanity check!
		$ok &= !empty($this->gametype) && (strlen($this->gametype)==1);
		$ok &= !empty($this->bot1);
		$ok &= !empty($this->bot2);
		$ok &= is_numeric($this->score1)     && (((int)$this->score1) >= 0);
		$ok &= is_numeric($this->bulletdmg1) && (((int)$this->bulletdmg1) >= 0);
		$ok &= is_numeric($this->survival1)  && (((int)$this->survival1) >= 0);
		$ok &= is_numeric($this->score2)     && (((int)$this->score2) >= 0);
		$ok &= is_numeric($this->bulletdmg2) && (((int)$this->bulletdmg2) >= 0);
		$ok &= is_numeric($this->survival2)  && (((int)$this->survival2) >= 0);
		
		if ($ok) {
			// convert numeric values
			$this->score1     = (int)$this->score1;
			$this->bulletdmg1 = (int)$this->bulletdmg1;
			$this->survival1  = (int)$this->survival1;
			$this->score2     = (int)$this->score2;
			$this->bulletdmg2 = (int)$this->bulletdmg2;
			$this->survival2  = (int)$this->survival2;
		} else {
			trigger_error('Invalid data received: ' . print_r($this, true), E_USER_ERROR);
		}
		
		/* update participants list & get bot id's*/
		$party = new Participants($this->db, $this->gametype);
		$ids = $party->checkNames(array($this->bot1, $this->bot2));
		$this->id1 = $ids[ $this->bot1 ];
		$this->id2 = $ids[ $this->bot2 ];
		
		/* lock tables for faster inserts */
		$this->db->query('LOCK TABLES battle_results WRITE,
									game_pairings WRITE');
									
		/* save battle data */
		if (!$this->insertBattle($db, true) || !$this->insertBattle($db, false))
			trigger_error('Failed to add battle result to database.', E_USER_ERROR);
		
		/* update pairings */
		$pairing = new GamePairings($this->db, $this->gametype, $this->id1, $this->id2);
		$pairing->updateScores(array(
								$this->id1 => array('score' => $this->score1,
								 					'bulletdmg' => $this->bulletdmg1,
													'survival' => $this->survival1),
								$this->id2 => array('score' => $this->score2,
								 					'bulletdmg' => $this->bulletdmg2,
													'survival' => $this->survival2)
								));
		
		/* determine missing pairings */
		$complete = array($this->id1 => array($this->id1 => 1), $this->id2 => array($this->id2 => 1));
		foreach($pairing->getAllPairings() as $pair)
			$complete[ $pair['bot_id'] ][ $pair['vs_id'] ] = 1;
		$missing = array();
		$botlist = $party->getList();
		foreach($botlist as $id => $bot) {
			if (!isset($complete[$this->id1][$id]))
				$missing[] = array($botlist[$this->id1]['name'], $botlist[$id]['name']);
			if (!isset($complete[$this->id2][$id]))
				$missing[] = array($botlist[$this->id2]['name'], $botlist[$id]['name']);
		}
		
		/* total battles fought to date */
		$battles = array($botlist[$this->id1]['battles'], $botlist[$this->id2]['battles']);
		
		$this->db->query('UNLOCK TABLES');
		return array('missing' => $missing, 'battles' => $battles);
	}
		
	function insertBattle($db, $winner) {
		$qry = 	"INSERT INTO battle_results
					SET version   = '" . mysql_escape_string($this->version) . "',
					user          = '" . mysql_escape_string($this->user) . "',
					ip_addr       = '" . mysql_escape_string($_SERVER['REMOTE_ADDR']) . "',
					timestamp     = FROM_UNIXTIME('" . mysql_escape_string(substr($this->timestamp, 0, -3)) . "'),
					millisecs     = '" . mysql_escape_string(substr($this->timestamp, -3)) . "',
					gametype      = '" . mysql_escape_string($this->gametype) . "',
					bot_id        = '" . mysql_escape_string(($winner) ? $this->id1 : $this->id2) . "',
					bot_score     = '" . mysql_escape_string(($winner) ? $this->score1 : $this->score2) . "',
					bot_bulletdmg = '" . mysql_escape_string(($winner) ? $this->bulletdmg1 : $this->bulletdmg2) . "',
					bot_survival  = '" . mysql_escape_string(($winner) ? $this->survival1 : $this->survival2) . "',
					vs_id         = '" . mysql_escape_string(($winner) ? $this->id2 : $this->id1) . "',
					vs_score      = '" . mysql_escape_string(($winner) ? $this->score2 : $this->score1) . "',
					vs_bulletdmg  = '" . mysql_escape_string(($winner) ? $this->bulletdmg2 : $this->bulletdmg1) . "',
					vs_survival   = '" . mysql_escape_string(($winner) ? $this->survival2 : $this->survival1) . "',
					state = '" . STATE_OK . "' ";
		return ($this->db->query($qry) > 0);
	}
	
	function updateState($gametype, $bot_id, $newstate, $oldstate='') {
		$id = mysql_escape_string($bot_id);
		$qry = "UPDATE battle_results SET state='" . mysql_escape_string($newstate) . "'
				WHERE  gametype = '" . mysql_escape_string($gametype) . "'
				  AND  (bot_id='$id' OR vs_id='$id') ";
		if ($oldstate!='')
			$qry .= " AND state='" . mysql_escape_string($oldstate) . "'";
		return ($this->db->query($qry) > 0);
	}
	
	function getNewBattles($limit=100, $state=STATE_OK) {
		$qry = "SELECT version, user, ip_addr, timestamp, millisecs, gametype, state,
					   bot_id, bot_score, bot_bulletdmg, bot_survival,
					   vs_id,  vs_score,  vs_bulletdmg,  vs_survival
				FROM   battle_results
				WHERE  state = '" . mysql_escape_string($state) . "'
				ORDER BY timestamp, millisecs ASC
				LIMIT " . ((int)$limit);
		if ($this->db->query($qry)>0)
			return $this->db->all();
		else
			return null;
	}
	
	function lockNewBattles($limit=100) {
		$qry = "UPDATE battle_results
				SET   state='" . STATE_LOCKED . "'
				WHERE state IN ('" . STATE_OK . "', '" . STATE_LOCKED . "')
				ORDER BY timestamp, millisecs ASC
				LIMIT " . ((int)$limit);
		$this->db->query($qry);
		return $this->getNewBattles($limit*10, STATE_LOCKED);
	}
	
	function releaseBattles($newstate=STATE_RATED) {
		$qry = "UPDATE battle_results
				SET   state='" . mysql_escape_string($newstate) . "'
				WHERE state='" . STATE_LOCKED . "'";
		return ($this->db->query($qry)>0);
	}
	
	
	function getPairingSummary($gametype, $id, $vs) {
		$qry = "SELECT bot_id, vs_id,
					AVG(bot_score / (bot_score+vs_score)),
					AVG(bot_bulletdmg / (bot_bulletdmg+vs_bulletdmg)),
					AVG(bot_survival / (bot_survival+vs_survival)),
					MAX(timestamp) AS last
			FROM battle_results
			WHERE gametype = '" . mysql_escape_string($gametype) . "'
			  AND bot_id = '" . mysql_escape_string($id) . "'
			  AND state IN ('" . STATE_NEW . "', '" . STATE_OK . "', '" . STATE_RATED . "')
			  AND vs_id = '" . mysql_escape_string($vs) . "'
			GROUP BY vs_id
			ORDER BY NULL";		// optimization!
		if ($this->db->query($qry)>0)
			return $this->db->all();
		else
			return null;
	}
	
	function getPairingDetails($gametype, $id, $vs) {
		$qry = "SELECT version, user, ip_addr, timestamp, millisecs, gametype, state,
					   bot_id, bot_score, bot_bulletdmg, bot_survival,
					   vs_id,  vs_score,  vs_bulletdmg,  vs_survival
				FROM   battle_results
				WHERE gametype = '" . mysql_escape_string($gametype) . "'
				  AND bot_id = '" . mysql_escape_string($id) . "'
				  AND vs_id  = '" . mysql_escape_string($vs) . "'				
				  AND state IN ('" . STATE_NEW . "', '" . STATE_OK . "', '" . STATE_RATED . "')
				ORDER BY timestamp, millisecs DESC";
		if ($this->db->query($qry)>0)
			return $this->db->all();
		else
			return null;
	}
	
}

?>