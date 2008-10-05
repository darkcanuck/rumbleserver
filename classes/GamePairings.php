<?php


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
		
		$gametype = mysql_escape_string($this->gametype);
		$id1 = mysql_escape_string($this->id1);
		$id2 = mysql_escape_string($this->id2);
		$qry = "SELECT gametype, bot_id, vs_id, battles, score_pct, score_dmg,
						score_survival, count_wins, timestamp
				FROM   game_pairings
				WHERE  gametype = '$gametype'
				  AND ((bot_id='$id1' AND vs_id='$id2') OR (bot_id='$id2' AND vs_id='$id1'))";
		
		if ($this->db->query($qry) > 0) {
			$this->newpair = false;
			foreach($this->db->all() as $rs)
				$this->pairing[ $rs['bot_id'] ] = $rs;
			return true;
		
		} else {
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
			return true;			
		}
	}
	
	function savePairing() {
		$rows = 0;
		foreach($this->pairing as $id => $pair) {
			$qry = '';
			if ($this->newpair) {
				$qry = "INSERT INTO game_pairings
							SET gametype = '" . mysql_escape_string($pair['gametype']) . "',
								bot_id        = '" . mysql_escape_string($pair['bot_id']) . "',
								vs_id         = '" . mysql_escape_string($pair['vs_id']) . "',
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
				$qry .= " WHERE gametype = '" . mysql_escape_string($pair['gametype']) . "'
							AND bot_id   = '" . mysql_escape_string($pair['bot_id']) . "'
							AND vs_id    = '" . mysql_escape_string($pair['vs_id']) . "'";
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
		return (int) ( (($score1 / ($score1+$score2) * 100 * 1000) + ($lastscore * $battles)) / ($battles+1) );
	}
	
	function getAllPairings($allbots=false) {
		$qry = "SELECT gametype, bot_id, vs_id, battles, score_pct, score_dmg,
						score_survival, count_wins, timestamp
				FROM  game_pairings
				WHERE gametype = '" . mysql_escape_string($this->gametype) . "' ";
		if(!$allbots)
			$qry .= " AND bot_id IN ('" . mysql_escape_string($this->id1) . "',
			 						 '" . mysql_escape_string($this->id2) . "')";
		if ($this->db->query($qry)>0)
			return $this->db->all();
		else
			return array();
	}
	
	function updateState($bot_id, $newstate, $oldstate='') {
		$id = mysql_escape_string($bot_id);
		$qry = "UPDATE game_pairings SET state='" . mysql_escape_string($newstate) . "'
				WHERE  gametype = '" . mysql_escape_string($this->gametype) . "'
				  AND  (bot_id='$id' OR vs_id='$id') ";
		if ($oldstate!='')
			$qry .= " AND state='" . mysql_escape_string($oldstate) . "'";
		return ($this->db->query($qry) > 0);
	}
		
	function getNewPairings($limit=50, $state=STATE_NEW) {
		$qry = "SELECT gametype, bot_id, vs_id, battles,
						score_pct, score_dmg, score_survival,
						count_wins, timestamp, state
				FROM   game_pairings
				WHERE  state='" . mysql_escape_string($state) . "'
				ORDER BY timestamp ASC ";
		if ($limit>0)
			$qry .= " LIMIT " . ((int)$limit);
		if ($this->db->query($qry)>0)
			return $this->db->all();
		else
			return null;
	}
	
	function lockNewPairings($limit=50) {
		$qry = "UPDATE game_pairings
				SET   state='" . STATE_LOCKED . "'
				WHERE state IN ('" . STATE_NEW . "', '" . STATE_LOCKED . "')
				ORDER BY timestamp ASC
				LIMIT " . ((int)$limit);
		if ($this->db->query($qry)<1)
			return null;
		else
			return $this->getNewPairings($limit, STATE_LOCKED);
	}
	
	function releasePairings($oldstate=STATE_LOCKED, $newstate=STATE_OK) {
		$qry = "UPDATE game_pairings
				SET   state='" . mysql_escape_string($newstate) . "'
				WHERE state='" . mysql_escape_string($oldstate) . "'";
		return ($this->db->query($qry)>0);
	}
	
	function getBotSummary($game='', $id='') {
		$gametype = mysql_escape_string( ($game!='') ? $game : $this->gametype);
		$id1 = mysql_escape_string( ($id!='') ? $id : $this->id1);
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
				  AND state IN ('" . STATE_NEW . "', '" . STATE_OK . "')
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
		$gametype = mysql_escape_string( ($game!='') ? $game : $this->gametype);
		$id1 = mysql_escape_string( ($id!='') ? $id : $this->id1);
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
				  AND g.state IN ('" . STATE_NEW . "', '" . STATE_OK . "')
				ORDER BY `" . mysql_escape_string($order) . "` ASC";
		if ($this->db->query($qry)>0)
			return $this->db->all();
		else
			return null;
	}
}

?>