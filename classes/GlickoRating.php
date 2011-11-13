<?php
/******************************************************************************
 * GlickoRating class  --  Darkcanuck's Roborumble Server
 *
 * Copyright 2008-2011 Jerome Lavigne (jerome@darkcanuck.net)
 * Released under GPL version 3.0 http://www.gnu.org/licenses/gpl-3.0.html
 *
 * Credits:
 *  Based on the Glicko rating system created by Mark E. Glickman
 *      see http://math.bu.edu/people/mg/glicko/glicko.doc/glicko.html
 *****************************************************************************/

class GlickoRating {

	private $init_rating = 1500.0;
	private $init_rd     =  350.0;

	private $q      = 0.0057565;
	private $q2     = 0.0;
	private $q2plus = 0.0;
		
	function __construct() {
		$this->q = log(10.0) / 400.0;
		$this->q2 = $this->q*$this->q;
		$this->q2plus = 3.0 * $this->q2 / (pi()*pi());
	}
	
	function g($RD) {
		return (1.0 / sqrt(1.0 + ($this->q2plus * $RD*$RD)) );
	}
	
	function E($rating, $rj, $RDj) {
		return (1.0 / (1.0 + pow(10.0, -1.0 * $this->g($RDj) * ($rating-$rj) / 400.0)) );
	}
	
	function invE($E, $rj, $RD) {
		return ($rj - 400.0 / $this->g($RDj) * log10((1-$E)/$E));
	}

	function calcRating($rating, $RD, $updates) {
		$d2 = 0.0;
		$dev = 0.0;
		
		// do summations
		foreach($updates as $u) {
			$gRD = $this->g($u['RD']);
			$Ej  = $this->E($rating, $u['glicko'], $u['RD']);
			
			$d2  += $gRD*$gRD * $Ej * (1.0 - $Ej);
			$dev += $gRD * ($u['score'] - $Ej);
		}
		$d2 *= $this->q2;
		
		// update rating, RD
		$RD2inv = 1.0 / ($RD*$RD);
		$new = array('rating' => 0.0, 'RD' => 0.0);
		$new['glicko'] = $rating + ($this->q / ($RD2inv + $d2)) * $dev;
		$new['RD']     = sqrt(1.0 / ($RD2inv + $d2));
		
		return $new;
	}
	
	// wrappers to handle our integer *1000 values
	function validRating($rating, $battles) {
		return ($battles==0) ? $this->init_rating : (float)$rating / 1000.0;
	}
	
	function validDeviation($deviation, $battles) {
		return (($deviation==0) || ($battles==0)) ? $this->init_rd : (float)$deviation / 1000.0;
	}
	
	function calcExpected($rating, $rj, $RDj) {
		return $this->E( (float)($rating/1000.0), (float)($rj/1000.0), (float)($RDj/1000.0) ) * 100.0;
	}
	
	function calcIdealRating($expected, $avgrating, $avgRD) {
		return $this->invE( (float)($expected/100000.0), (float)($avgrating/1000.0), (float)($avgRD/1000.0) );
	}
}

?>