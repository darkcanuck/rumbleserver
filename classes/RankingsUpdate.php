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
		$this->db->query('LOCK TABLES battle_results WRITE, upload_users READ,
									game_pairings READ, game_pairings AS g READ,
									participants WRITE, participants AS p WRITE,
									bot_data READ, bot_data AS b READ');
		
		// get new battle results
		$results = new BattleResults($this->db);
		$newbattles = $results->lockNewBattles($updatesize);
		if (($newbattles==null) || (count($newbattles)<1))
			return;		// nothing to update
		
		// organize update list by game / bot / vs
		$updatelist = array();
		foreach($newbattles as $battle) {
			$updatelist[ $battle['gametype'] ][ $battle['bot_id'] ][ $battle['vs_id'] ][] = $battle;
			$updatelist[ $battle['gametype'] ][ $battle['vs_id'] ][ $battle['bot_id'] ][] = $battle;
		}
		
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
	
	function updatePair($gametype, $newscores, $party=null, $allpairings=null) {
		// $newscores should be an assoc. array with winner & loser ids as array keys
		// data must contain score, bullet dmg. and survival for each bot
		if (!is_array($newscores) || (count($newscores)!=2))
			trigger_error("Invalid score data!", E_USER_ERROR);
		list($id1, $id2) = array_keys($newscores);
		
		// create new participants list if needed
		if ($party==null)
			$party = new Participants($this->db, $gametype);
		$partylist = $party->getList();
		
		// summarize pairings
		if (($allpairings==null) || (!is_array($allpairings))) {
			$gamepairings = new GamePairings($this->db, $game, $id1, $id2);
			$allpairings = $gamepairings->getAllPairings();
		}
		$pairings = array(
						$id1 => array('score_pct'=>0, 'score_dmg'=>0, 'score_survival'=>0,
								'pairings'=>0, 'battles'=>0, 'count_wins'=>0, 'list'=>array()),
						$id2 => array('score_pct'=>0, 'score_dmg'=>0, 'score_survival'=>0,
								'pairings'=>0, 'battles'=>0, 'count_wins'=>0, 'list'=>array())
						);
		$allfields = array('score_pct', 'score_dmg', 'score_survival', 'battles', 'count_wins', 'pairings');
		$sumfields = array('score_pct', 'score_dmg', 'score_survival', 'battles', 'count_wins');
		$avgfields = array('score_pct', 'score_dmg', 'score_survival');
		foreach($allpairings as $p) {
			foreach($sumfields as $f)
				$pairings[ $p['bot_id'] ][$f] += $p[$f];
			$pairings[ $p['bot_id'] ]['pairings']++;
			$pairings[ $p['bot_id'] ]['list'][] = $p;
		}
		foreach($avgfields as $f) {
			$pairings[$id1][$f] /= $pairings[$id1]['pairings'];
			$pairings[$id2][$f] /= $pairings[$id2]['pairings'];
		}
		
		// update scores
		$scores = array();
		$glicko = new GlickoRating();
		foreach($pairings as $id => $data) {
			// get bot's current scores
			$scores[$id] = $party->getBot($id);
			$score =& $scores[$id];
				
			// update pairing data
			
			foreach($allfields as $f)
				$score[$f] = $data[$f];
				
			// prepare for ratings update
			$ratingdata = array();
			foreach($data['list'] as $pair) {
				$vs = $pair['vs_id'];
				if (isset($partylist[$vs])) {
					$ratingdata[] = array(
							'rating' => $this->validRating($partylist[$vs]['score_elo'], $partylist[$vs]['battles']),
							'RD'     => $this->validDeviation($partylist[$vs]['deviation'], $partylist[$vs]['battles']),
							'score'  => $pair['score_pct'] / 1000.0 / 100.0
							);
				}
			}
			
			// update Glicko rating
			$newrating = $glicko->calcRating($this->validRating($score['score_elo'], $score['battles']),
											$this->validDeviation($score['deviation'], $score['battles']),
											$ratingdata);
			$score['score_elo'] = (int)($newrating['rating'] * 1000.0);
			$score['deviation'] = (int)($newrating['RD'] * 1000.0);
		}
		return $scores;
	}
}

?>