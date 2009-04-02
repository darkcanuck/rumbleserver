<?php
/******************************************************************************
 * GamePairings class  --  Darkcanuck's Roborumble Server
 *
 * $HeadURL$
 * $Date$
 * $Revision$
 * $Author$
 *
 * Copyright 2008-2009 Jerome Lavigne (jerome@darkcanuck.net)
 * Released under GPL version 3.0 http://www.gnu.org/licenses/gpl-3.0.html
 *****************************************************************************/

class GamePairings {
	
	private $db = null;
	
	private $pairing = null;
	private $newpair = false;
	
	private $gametype  = '';
	private $id1       = '';
	private $id2       = '';
	
	
	function __construct($db, $gametype, $id1=1, $id2=1) {
		$this->db = $db;
		$this->gametype = $gametype;

		if (($id1 < 1) || ($id2 < 1))
			trigger_error("Invalid bot pairing data!", E_USER_ERROR);
		$this->id1 = $id1;
		$this->id2 = $id2;
	}
	
	function getPairing() {
		$this->pairing = null;
		
		$qrystring = "SELECT gametype, bot_id, vs_id, battles, score_pct, score_dmg, " .
						" score_survival, count_wins, timestamp " .
				        " FROM   game_pairings " .
				        " WHERE  gametype = '%s' AND bot_id=%u AND vs_id=%u " .
				        " FOR UPDATE ";     // write locks rows if inside transaction
		$qry1 = sprintf($qrystring, $this->gametype[0], (int)$this->id1, (int)$this->id2);
		$qry2 = sprintf($qrystring, $this->gametype[0], (int)$this->id2, (int)$this->id1);
		
		if ($this->db->query($qry1) > 0)
		    $this->pairing[ $this->id1 ] = $this->db->next();
        if ($this->db->query($qry2) > 0)
		    $this->pairing[ $this->id2 ] = $this->db->next();
		
		if ( (count($this->pairing) != 0) && (count($this->pairing) != 2) )
		    trigger_error("Corrupted pairing data!!!", E_USER_ERROR);
		
		if (($this->pairing==null) || (count($this->pairing)<2)) {
			$this->newpair = true;
			foreach(array($this->id1, $this->id2) as $id)
				$this->pairing[ $id ] = array(
											'gametype' => $this->gametype,
											'bot_id' => $id,
											'vs_id' => ($id==$this->id1) ? $this->id2 : $this->id1,
											'battles' => 0,
											'score_pct' => 0,
											'score_dmg' => 0,
											'score_survival' => 0,
											'count_wins' => 0,
											'timestamp' => strftime('%Y-%m-%d %T')
											);
		}
		return true;
	}
	
	function savePairing() {
		$rows = 0;
		foreach($this->pairing as $id => $pair) {
			$qry = '';
			if ($this->newpair) {
				$qry = "INSERT INTO game_pairings
							SET gametype = '" . $pair['gametype'][0] . "',
								bot_id   = " . ((int)$pair['bot_id']) . ",
								vs_id    = " . ((int)$pair['vs_id']) . ",
								";
			} else {
				$qry = "UPDATE game_pairings
							SET ";
			}
			$qry .=			   "battles   = '" . mysql_escape_string($pair['battles']) . "',
								score_pct = '" . mysql_escape_string($pair['score_pct']) . "',
								score_dmg = '" . mysql_escape_string($pair['score_dmg']) . "',
								score_survival = '" . mysql_escape_string($pair['score_survival']) . "',
								count_wins     = '" . mysql_escape_string($pair['count_wins']) . "',
								timestamp = NOW(),
								state = '" . STATE_OK . "' ";
			if (!$this->newpair) {
				$qry .= " WHERE gametype = '" . $pair['gametype'][0] . "'
							AND bot_id   = " . ((int)$pair['bot_id']) . "
							AND vs_id    = " . ((int)$pair['vs_id']);
			}
			$rows += $this->db->query($qry);
		}
		return ($rows > 1);
	}
	
	function updateScores($scores) {
		// $scores should be an assoc. array with winner & loser ids as array keys
		// data must contain score, bullet dmg. and survival for each bot
		if (!is_array($scores) || (count($scores)<2))
			trigger_error("Invalid score data!", E_USER_ERROR);

		list($this->id1, $this->id2) = array_keys($scores);
		
		if (!$this->newpair && ($this->pairing==null))
			$this->getPairing();
		
		foreach($this->pairing as $id => $p) {
			$vs = ($this->id1==$id) ? $this->id2 : $this->id1;
			$pair =& $this->pairing[$id];
			$bot1 =& $scores[$id];
			$bot2 =& $scores[$vs];
			$pair['score_pct'] = $this->calcScorePercent($bot1['score'], $bot2['score'], $pair['score_pct'], $pair['battles']);
			$pair['score_dmg'] = $this->calcScorePercent($bot1['bulletdmg'], $bot2['bulletdmg'], $pair['score_dmg'], $pair['battles']);
			$pair['score_survival'] = $this->calcScorePercent($bot1['survival'], $bot2['survival'], $pair['score_survival'], $pair['battles']);
			$pair['count_wins'] = ($pair['score_pct'] > 50000) ? 1 : 0;
			$pair['battles'] += 1;
		}
		return $this->savePairing();
	}
	
	function calcScorePercent($score1, $score2, $lastscore, $battles) {
	    $pctscore = (($score1+$score2)>0) ? $score1 / ($score1+$score2) : 0.0 ;
		return (int) ( (($pctscore * 100 * 1000) + ($lastscore * $battles)) / ($battles+1) );
	}
	
	function getAllPairings() {
	    $qrystring = "SELECT bot_id, vs_id, battles, score_pct, score_dmg, " .
						" score_survival, count_wins " .
				        " FROM   game_pairings " .
				        " WHERE  gametype = '%s' AND bot_id=%u " .
				        " AND state = '" . STATE_OK . "' ";
		$qry1 = sprintf($qrystring, $this->gametype[0], (int)$this->id1);
		$qry2 = sprintf($qrystring, $this->gametype[0], (int)$this->id2);
		
		$results = array();
		if ($this->db->query($qry1) > 0)
		    $results = $this->db->all();
        if ($this->db->query($qry2) > 0) {
            foreach ($this->db->all() as $rs)
		        $results[] = $rs;
        }
	    return $results;
	}
	
	function updateState($bot_id, $newstate, $oldstate='') {
		$id = (int)$bot_id;
		$qry = "UPDATE game_pairings SET state='" . mysql_escape_string($newstate) . "'
				WHERE  gametype = '" . $this->gametype[0] . "'
				  AND  (bot_id=$id OR vs_id=$id) ";
		if ($oldstate!='')
			$qry .= " AND state='" . mysql_escape_string($oldstate) . "'";
		return ($this->db->query($qry) > 0);
	}
	
	function getBotSummary($game='', $id='') {
		$gametype = ($game!='') ? $game[0] : $this->gametype[0];
		$id1 = (int)(($id!='') ? $id : $this->id1);
		$qry = "SELECT gametype, bot_id,
						COUNT(bot_id) AS pairings,
						SUM(battles) AS battles,
						AVG(score_pct) AS score_pct,
						AVG(score_dmg) AS score_dmg,
						AVG(score_survival) AS score_survival,
						SUM(count_wins) AS count_wins,
						MAX(timestamp) AS last
				FROM  game_pairings
				WHERE gametype = '$gametype'
				  AND bot_id='$id1'
				  AND state='" . STATE_OK . "'
				GROUP BY bot_id
				ORDER BY NULL";		// optimization!
		if ($this->db->query($qry)>0)
			return $this->db->next();
		else
			return null;
		/*
		$summary = array('gametype' => $gametype, 'bot_id' => $id);
		$sumfields = array('battles', 'count_wins');
		$avgfields = array('score_pct', 'score_dmg', 'score_survival');
		$lasttime = 0;
		
		$qry = "SELECT gametype, bot_id, vs_id, battles,
						score_pct, score_dmg, score_survival,
						count_wins, timestamp, state
				FROM  game_pairings
				WHERE gametype = '$gametype'
				  AND bot_id='$id1'
				  AND state IN ('" . STATE_NEW . "', '" . STATE_OK . "')";
		$summary['pairings'] = $this->db->query($qry);
		if ($summary['pairings']<1)
			return null;
		
		foreach($this->db->all() as $rs) {
			foreach($sumfields as $f)
				$summary[$f] += $rs[$f];
			foreach($avgfields as $f)
				$summary[$f] += $rs[$f];
			$timeval = strtotime($rs['timestamp']);
			if ($timeval > $lasttime) {
				$summary['timestamp'] = $rs['timestamp'];
				$lasttime = $timeval;
			}
		}
		foreach($avgfields as $f)
			$summary[$f] /= $summary['pairings'];
		return $summary;
		*/
	}
	
	function getBotPairings($game='', $id='', $order='vs_name') {
		$gametype = ($game!='') ? $game[0] : $this->gametype[0];
		$id1 = (int)(($id!='') ? $id : $this->id1);
        $qry = "SELECT g.gametype AS gametype, g.bot_id AS bot_id,
						g.vs_id AS vs_id, b.full_name AS vs_name,
						g.battles AS battles, g.score_pct AS score_pct,
						g.score_dmg AS score_dmg, g.score_survival AS score_survival,
						g.count_wins AS count_wins, g.timestamp AS timestamp,
						g.state AS state
				FROM game_pairings AS g
				INNER JOIN bot_data AS b ON g.vs_id = b.bot_id
				WHERE g.gametype = '$gametype'
				  AND g.bot_id = '$id1'
				  AND g.state = '" . STATE_OK . "'
				ORDER BY `" . mysql_escape_string($order) . "` ASC";
		if ($this->db->query($qry)>0)
			return $this->db->all();
		else
			return null;
	}
}

?>