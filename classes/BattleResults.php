<?php
/******************************************************************************
 * BattleResults class  --  Darkcanuck's Roborumble Server
 *
 * $HeadURL$
 * $Date$
 * $Revision$
 * $Author$
 *
 * Copyright 2008-2011 Jerome Lavigne (jerome@darkcanuck.net)
 * Released under GPL version 3.0 http://www.gnu.org/licenses/gpl-3.0.html
 *****************************************************************************/

class BattleResults {
	
	private $db = null;
	
	// battle data
	private $version    = '';
	private $client     = '';
	private $user       = '';
	private $ip_addr    = '';
	private $userid     = '';
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
		$ok &= !empty($this->client);
		$ok &= !empty($this->user);
		$ok &= !empty($this->ip_addr);
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
		if ($this->bot1 == $this->bot2) {
		    trigger_error('Bot versus itself received: ' . print_r($this, true), E_USER_ERROR);
		}
		
		/* start database transaction */
        set_time_limit(600);    // allow time to finish once we have locks
        $this->db->query('START TRANSACTION');
        
		/* update participants list & get bot id's*/
		$party = new Participants($this->db, $this->gametype);
		$ids = $party->checkNames(array($this->bot1, $this->bot2));
		$this->id1 = $ids[ $this->bot1 ];
		$this->id2 = $ids[ $this->bot2 ];		
		
		/* get user upload id */
		$users = new UploadUsers($this->db);
		$this->userid = $users->getID($this->user, $this->ip_addr, $this->client);
		
		/* save battle data */
		$this->insertBattle();
		
		/* update upload stats - +1 new battle*/
		$users->updateUser($this->userid, 1);
		$users->updateStats($this->userid, $this->gametype, 1);
		
		/* update pairings */
		$pairing = new GamePairings($this->db, $this->gametype, $this->id1, $this->id2);
		$scores = array(
						$this->id1 => array('score' => $this->score1,
						 					'bulletdmg' => $this->bulletdmg1,
											'survival' => $this->survival1),
						$this->id2 => array('score' => $this->score2,
						 					'bulletdmg' => $this->bulletdmg2,
											'survival' => $this->survival2)
						);
		$pairing->updateScores($scores);
		
		$this->db->query('COMMIT');
		
		$allpairings = $pairing->getAllPairings();
		
		/* update ratings */
		$rankings = new RankingsUpdate($this->db);
		$updates = $rankings->updatePair($this->gametype, $this->id1, $this->id2, $party, $allpairings);
		$party->updateScores($this->id1, $updates[$this->id1]);
		$party->updateScores($this->id2, $updates[$this->id2]);
		
		/* total battles fought to date */
		$botlist = $party->getList();
		$battles = array($botlist[$this->id1]['battles'], $botlist[$this->id2]['battles']);
		
		return array('battles' => $battles, 'ids' => array($this->id1, $this->id2),
		             'pairings' => $allpairings, 'participants' => $botlist);
	}
		
	function insertBattle($winner=true) {
		$qry = 	"INSERT INTO battle_results
					SET user_id   = '" . mysql_escape_string($this->userid) . "',
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
					state = '" . STATE_RATED . "' ";
		if ($this->db->query($qry) < 1)
			trigger_error('Failed to add battle result to database.', E_USER_ERROR);
		return true;
	}
	
	function updateState($gametype, $bot_id, $newstate, $oldstate='') {
		$id = mysql_escape_string($bot_id);
		$qry = "UPDATE battle_results SET state='" . mysql_escape_string($newstate) . "'
				WHERE  gametype = '" . mysql_escape_string($gametype) . "'
				  AND  %s='$id' ";
		if ($oldstate!='')
			$qry .= " AND state='" . mysql_escape_string($oldstate) . "'";
		$qry1 = sprintf($qry, 'bot_id');
		$qry2 = sprintf($qry, 'vs_id');
		return ( ($this->db->query($qry1) + $this->db->query($qry2)) > 0);
	}
	
	function updateBattle($gametype, $bot_id, $vs_id, $timestamp, $millisecs, $oldstate=STATE_FLAGGED, $newstate=STATE_RATED) {
		$qry = "UPDATE battle_results SET state='" . mysql_escape_string($newstate) . "'
				WHERE gametype = '" . mysql_escape_string($gametype) . "'
				  AND bot_id = '" . mysql_escape_string($bot_id) . "'
				  AND vs_id  = '" . mysql_escape_string($vs_id) . "'
                  AND timestamp = '" . mysql_escape_string($timestamp) . "'
				  AND millisecs = '" . mysql_escape_string($millisecs) . "'
				  AND state = '" . mysql_escape_string($oldstate) . "'";
		return ($this->db->query($qry) > 0);
	}
    
	function getBattleDetails($gametype, $id, $vs, $retired=false) {
		$botcombo = "'" .  mysql_escape_string($id) . "', '" . mysql_escape_string($vs) . "'";
		$qry = "SELECT u.username AS user, u.ip_addr, u.version,
				       r.timestamp, r.millisecs, r.gametype, r.state, r.created,
					   r.bot_id, r.bot_score, r.bot_bulletdmg, r.bot_survival,
					   r.vs_id,  r.vs_score,  r.vs_bulletdmg,  r.vs_survival
				FROM   battle_results AS r INNER JOIN upload_users AS u ON r.user_id = u.user_id
				WHERE gametype = '" . mysql_escape_string($gametype) . "'
				  AND bot_id IN ($botcombo)
				  AND vs_id IN ($botcombo)
				  AND state IN ('" . STATE_NEW . "', '" . STATE_OK . "', '" . STATE_RATED . "', '"
				                . STATE_RETIRED . "', '" . STATE_RETIRED2 . "')
				ORDER BY created DESC";
		if ($this->db->query($qry)>0) {
			$results = array();
			$fields  = array('id', 'score', 'bulletdmg', 'survival');
			foreach($this->db->all() as $rs) {
				$row = $rs;
				if($row['vs_id']==$id) {	// flip results if necessary
					foreach($fields as $f) {
						$row['bot_' . $f] = $rs['vs_' . $f];
						$row['vs_' . $f] = $rs['bot_' . $f];
					}
					$row['bot_id'] = $rs['vs_id'];
				}	
				$results[] = $row;
			}
			return $results;
		}
		return null;
	}
	
	function getBattlesByState($state=STATE_RATED, $gametype="", $limit=100) {
		$qry = "SELECT u.username AS user, u.ip_addr, u.version,
		               b.full_name AS bot_name, r.bot_id,
		               v.full_name AS vs_name, r.vs_id,
				       r.timestamp, r.millisecs, r.gametype, r.state, r.created,
					   r.bot_score, r.bot_bulletdmg, r.bot_survival,
					   r.vs_score,  r.vs_bulletdmg,  r.vs_survival
				FROM   battle_results AS r
				INNER JOIN upload_users AS u ON r.user_id = u.user_id
				INNER JOIN bot_data AS b ON r.bot_id = b.bot_id
				INNER JOIN bot_data AS v ON r.vs_id = v.bot_id
				WHERE state = '" . mysql_escape_string($state) . "' " .
				(($gametype!="") ? " AND gametype = '" . mysql_escape_string($gametype) . "' " : "") . "
				ORDER BY created DESC
				LIMIT " . (int)$limit;
		if ($this->db->query($qry)>0) {
			return $this->db->all();
		}
		return null;
	}
	
}

?>