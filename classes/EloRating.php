<?php
/******************************************************************************
 * EloRating class  --  Darkcanuck's Roborumble Server
 *
 * Copyright 2008-2011 Jerome Lavigne (jerome@darkcanuck.net)
 * Released under GPL version 3.0 http://www.gnu.org/licenses/gpl-3.0.html
 *
 * Credits:
 *  Robowiki user "Albert" for creating this scoring system based on ELO
 *****************************************************************************/

/* Roborumble Elo Rating System */

class EloRating {

	private $k = 3.0;
	
	private $init_rating = 1600.0;
	
	function __construct() {
		// empty
	}
	
	function E($rating, $rj) {
		return (1.0 / (1.0 + pow(20.0, -1.0 * ($rating-$rj) / 800.0)) );
	}
	
	function invE($E, $rj) {
		return ($rj - 800.0 * log((1-$E)/$E, 20.0));
	}
	
	function rating($rating, $updates) {
		$delta = 0.0;
		foreach($updates as $u) {
			$Ej  = $this->E($rating, $u['elo']);
			$delta += $this->k * ($u['score'] - $Ej);
		}
		return ($rating + $delta);
	}
	
	// wrappers to handle our integer *1000 values
	function validRating($rating, $battles) {
	    return ($battles==0) ? $this->init_rating : (float)$rating / 1000.0;
	}
	
	function calcExpected($rating, $rj) {
		return $this->E( (float)($rating/1000.0), (float)($rj/1000.0) ) * 100.0;
	}
	
	function calcRating($rating, $battles, $avgscore, $updates) {
		return (int)($this->rating($this->validRating($rating, $battles, $avgscore), $updates) * 1000.0);
	}
	
}

?>