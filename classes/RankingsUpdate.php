<?php

require_once 'GlickoRating.php';

class RankingsUpdate {
	
	private $db = null;
		
	function __construct($db) {
		$this->db = $db;
	}
	
	function validRating($rating, $battles) {
		return (($rating==0) || ($battles==0)) ? 1500.0 : (float)$rating / 1000.0;
	}
	function validDeviation($deviation, $battles) {
		return (($deviation==0) || ($battles==0)) ? 350.0 : (float)$deviation / 1000.0;
	}
	
	
	function updateScores($updatesize=100, $pairingdelay=-1) {
		
		//echo "\nDOING UPDATE...\n";
		
		set_time_limit(5 * $updatesize);
		
		// keep other processes out!
		$this->db->query('LOCK TABLES battle_results WRITE,
									game_pairings READ,
									participants WRITE, participants AS p WRITE,
									bot_data READ, bot_data AS b READ');
		
		// get new battle results
		$results = new BattleResults($this->db);
		$newbattles = $results->lockNewBattles($updatesize);
		if (($newbattles==null) || (count($newbattles)<1))
			return;		// nothing to update
		
		// organize update list by game / bot / vs
		$updatelist = array();
		foreach($newbattles as $battle)
			$updatelist[ $battle['gametype'] ][ $battle['bot_id'] ][ $battle['vs_id'] ][] = $battle;
		
		// update scores
		$party = array();
		$scores = array();
		$glicko = new GlickoRating();
		
		foreach($updatelist as $game => $botlist) {
			$party[$game] = new Participants($this->db, $game);
			$partylist = $party[$game]->getList();
			
			$scores[$game] = array();
			
			$pairing = new GamePairings($this->db, $game);
			
			foreach($botlist as $id => $vslist) {
				// get bot's current scores
				$scores[$game][$id] = $party[$game]->getBot($id);
				$score =& $scores[$game][$id];
				
				$ratingdata = array();
				foreach($vslist as $vs => $battlelist) {
					if (isset($partylist[$vs])) {
						foreach($battlelist as $b) {
							$ratingdata[] = array(
								'rating' => $this->validRating($partylist[$vs]['score_elo'], $partylist[$vs]['battles']),
								'RD'     => $this->validDeviation($partylist[$vs]['deviation'], $partylist[$vs]['battles']),
								'score'  => $b['bot_score'] / ($b['bot_score'] + $b['vs_score'])
								);
						}
					}
				}
				
				// update rating
				$newrating = $glicko->calcRating(
											$this->validRating($score['score_elo'], $score['battles']),
											$this->validDeviation($score['deviation'], $score['battles']),
											$ratingdata
											);
				$score['score_elo'] = (int)($newrating['rating'] * 1000.0);
				$score['deviation'] = (int)($newrating['RD'] * 1000.0);
				
				// update pairing scores if needed
				$nextupdate = strtotime($score['timestamp']) + $pairingdelay;	//speeds up ranking rebuilding
				if ($nextupdate < time()) {
					$summary = $pairing->getBotSummary($game, $id);				
					if ($summary!=null && (count($summary)>0)) {
						$fields = array('battles', 'score_pct', 'score_dmg', 'score_survival', 'pairings', 'count_wins');
						foreach($fields as $f) {
							$score[$f] = $summary[$f];
						}
						//if (!$party[$game]->updateScores($id, $score))
						//	trigger_error('Failed to update scores for ' . $score['name'], E_USER_ERROR);
					}
				}
				
				// update bot's scores
				$party[$game]->updateScores($id, $score);
			}
		}
		
		// mark new battle results as "done"
		$results->releaseBattles();
		$this->db->query('UNLOCK TABLES');
		
		return true;
	}
	
}

?>