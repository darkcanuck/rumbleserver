<?php

/* Glicko Rating System */

class GlickoRating {

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
			$Ej  = $this->E($rating, $u['rating'], $u['RD']);
			
			$d2  += $gRD*$gRD * $Ej * (1.0 - $Ej);
			$dev += $gRD * ($u['score'] - $Ej);
		}
		$d2 *= $this->q2;
		
		// update rating, RD
		$RD2inv = 1.0 / ($RD*$RD);
		$new = array('rating' => 0.0, 'RD' => 0.0);
		$new['rating'] = $rating + ($this->q / ($RD2inv + $d2)) * $dev;
		$new['RD']     = sqrt(1.0 / ($RD2inv + $d2));
		
		return $new;
	}
	
	// wrappers to handle our integer *1000 values
	function calcExpected($rating, $rj, $RDj) {
		return $this->E( (float)($rating/1000.0), (float)($rj/1000.0), (float)($RDj/1000.0) ) * 100.0;
	}
	
	function calcIdealRating($expected, $avgrating, $avgRD) {
		return $this->invE( (float)($expected/100000.0), (float)($avgrating/1000.0), (float)($avgRD/1000.0) );
	}
}

?>