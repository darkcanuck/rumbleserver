<?php
/******************************************************************************
 * RankingsUpdate class  --  Darkcanuck's Roborumble Server
 *
 * TODO:  this class needs to be cleaned up!
 *          The updateScores method is obsolete
 *
 * $HeadURL$
 * $Date$
 * $Revision$
 * $Author$
 *
 * Copyright 2008-2009 Jerome Lavigne (jerome@darkcanuck.net)
 * Released under GPL version 3.0 http://www.gnu.org/licenses/gpl-3.0.html
 *****************************************************************************/

require_once 'EloRating.php';
require_once 'GlickoRating.php';
require_once 'Glicko2Rating.php';

class RankingsUpdate {
	
	private $db = null;
		
	function __construct($db) {
		$this->db = $db;
	}
		
/* unused function -- kept for reference only	
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
		$elo    = new EloRating();
		$glicko = new GlickoRating();
		$glicko2 = new Glicko2Rating();
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
								'elo'    => $elo->validRating($partylist[$vs]['rating_classic'], $battles, $partylist[$vs]['score_pct']),
								'glicko' => $glicko->validRating($partylist[$vs]['rating_glicko'], $partylist[$vs]['battles']),
								'RD'     => $glicko->validDeviation($partylist[$vs]['rd_glicko'], $partylist[$vs]['battles']),
								'glicko2' => $glicko2->validRating($partylist[$vs]['rating_glicko2'], $battles, $partylist[$vs]['rating_glicko']),
								'RD2'     => $glicko2->validDeviation($partylist[$vs]['rd_glicko2'], $battles),
								'score'  => $b['bot_score'] / ($b['bot_score'] + $b['vs_score'])
								);
						}
					}
				}
				// update classic Elo rating (scaling done in class)
				$score['rating_classic'] = $elo->calcRating($score['rating_classic'], $score['battles'], $score['score_pct'], $ratingdata);
				
				// update Glicko rating
				$newrating = $glicko->calcRating(
											$glicko->validRating($score['rating_glicko'], $score['battles']),
											$glicko->validDeviation($score['rd_glicko'], $score['battles']),
											$ratingdata
											);
				$score['rating_glicko'] = (int)($newrating['glicko'] * 1000.0);
				$score['rd_glicko'] = (int)($newrating['RD'] * 1000.0);
				
				// update Glicko-2 rating
				$newrating = $glicko2->calcRating($score['rating_glicko2'], $score['rd_glicko2'], $score['rd_glicko2'],
												$score['battles'], $score['rating_glicko'], $ratingdata);
				$score['rating_glicko2'] = $newrating['glicko2'];
				$score['rd_glicko2'] = $newrating['RD2'];
				$score['vol_glicko2'] = $newrating['vol2'];
				
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
*/

	function updatePair($gametype, $id1, $id2, $party=null, $allpairings=null) {
		// create new participants list if needed
		if ($party==null)
			$party = new Participants($this->db, $gametype);
		$partylist = $party->getList();
		
		// summarize pairings
		if (($allpairings==null) || (!is_array($allpairings))) {
			$gamepairings = new GamePairings($this->db, $game, $id1, $id2);
			$allpairings = $gamepairings->getAllPairings();
		}
		$allfields = array('score_pct', 'score_dmg', 'score_survival', 'battles', 'count_wins', 'pairings');
		$sumfields = array('score_pct', 'score_dmg', 'score_survival', 'battles', 'count_wins');
		$avgfields = array('score_pct', 'score_dmg', 'score_survival');
		$pairings = array($id1 => array(), $id2 => array());
		foreach($pairings as $id => $data) {
			foreach($allfields as $f)
				$pairings[$id][$f] = 0;
			$pairings[$id]['list'] = array();
		}
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
		$elo     = new EloRating();
		$glicko  = new GlickoRating();
		$glicko2 = new Glicko2Rating();
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
					$battles = $partylist[$vs]['battles'];
					$ratingdata[] = array(
							'elo'    => $elo->validRating($partylist[$vs]['rating_classic'], $battles, $partylist[$vs]['score_pct']),
							'glicko' => $glicko->validRating($partylist[$vs]['rating_glicko'], $battles),
							'RD'     => $glicko->validDeviation($partylist[$vs]['rd_glicko'], $battles),
							'glicko2' => $glicko2->validRating($partylist[$vs]['rating_glicko2'], $battles, $partylist[$vs]['rating_glicko']),
							'RD2'     => $glicko2->validDeviation($partylist[$vs]['rd_glicko2'], $battles),
							'score'  => $pair['score_pct'] / 1000.0 / 100.0
							);
				}
			}
			
			// update classic Elo rating (scaling done in class)
			$score['rating_classic'] = $elo->calcRating($score['rating_classic'], $score['battles'], $score['score_pct'], $ratingdata);
			
			// update Glicko rating
			$newrating = $glicko->calcRating($glicko->validRating($score['rating_glicko'], $score['battles']),
											$glicko->validDeviation($score['rd_glicko'], $score['battles']),
											$ratingdata);
			$score['rating_glicko'] = (int)($newrating['glicko'] * 1000.0);
			$score['rd_glicko'] = (int)($newrating['RD'] * 1000.0);
			
			// update Glicko-2 rating
			$newrating = $glicko2->calcRating($score['rating_glicko2'], $score['rd_glicko2'], $score['rd_glicko2'],
											$score['battles'], $score['rating_glicko'], $ratingdata);
			$score['rating_glicko2'] = $newrating['glicko2'];
			$score['rd_glicko2'] = $newrating['RD2'];
			$score['vol_glicko2'] = $newrating['vol2'];
		}
		return $scores;
	}
}

?>