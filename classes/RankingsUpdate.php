<?php
/******************************************************************************
 * RankingsUpdate class  --  Darkcanuck's Roborumble Server
 *
 * TODO:  this class needs to be cleaned up!
 *
 * Copyright 2008-2011 Jerome Lavigne (jerome@darkcanuck.net)
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
		$sumfields = array('score_pct', 'score_dmg', 'score_survival', 'battles');
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
			if ($p['score_pct'] > 50000)
			    $pairings[ $p['bot_id'] ]['count_wins']++;
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
							'elo'    => $elo->validRating($partylist[$vs]['rating_classic'], $battles),
							'glicko' => $glicko->validRating($partylist[$vs]['rating_glicko'], $battles),
							'RD'     => $glicko->validDeviation($partylist[$vs]['rd_glicko'], $battles),
							'glicko2' => $glicko2->validRating($partylist[$vs]['rating_glicko2'], $battles),
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