<?php
/******************************************************************************
 * Glicko2Rating class  --  Darkcanuck's Roborumble Server
 *
 * Copyright 2008-2011 Jerome Lavigne (jerome@darkcanuck.net)
 * Released under GPL version 3.0 http://www.gnu.org/licenses/gpl-3.0.html
 *
 * Credits:
 *  Based on the Glicko-2 rating system created by Mark E. Glickman
 *      see http://math.bu.edu/people/mg/glicko/glicko2.doc/example.html
 *****************************************************************************/

class Glicko2Rating {

	private $init_rating = 1500.0;
	private $init_rd     =  350.0;
	private $init_vol    =   0.06;
	
	private $convert = 173.7178;
	
	private $tau = 0.5;
	private $tau2 = 0.0;
	private $q = 0.0;
		
	function __construct() {
		$this->q = 3.0 / (pi()*pi());
		$this->tau2 = $this->tau*$this->tau;
	}
	
	function g($phi) {
		return (1.0 / sqrt(1.0 + ($this->q * $phi*$phi)) );
	}
	
	function E($mu, $mu_j, $phi_j) {
		return (1.0 / (1.0 + exp(-1.0 * $this->g($phi_j) * ($mu-$mu_j))) );
	}
	
	function invE($E, $rj, $RD) {
		return ($rj - 400.0 / $this->g($RDj) * log10((1-$E)/$E));
	}

	function rating($rating, $RD, $vol, $updates) {
		$v = 0.0;
		$dev = 0.0;
		$delta = 0.0;
		
		// convert from Glicko scale
		$mu  = ($rating - $this->init_rating) / $this->convert;
		$phi = $RD / $this->convert;
		
		// do summations
		foreach($updates as $u) {
			$mu_j  = ($u['glicko2'] - $this->init_rating) / $this->convert;
			$phi_j = $u['RD2'] / $this->convert;
			
			$gj = $this->g($phi_j);
			$Ej = $this->E($mu, $mu_j, $phi_j);
			
			$v += $gj*$gj * $Ej * (1.0 - $Ej);
			$dev += $gj * ($u['score'] - $Ej);
		}
		$v = 1.0 / $v;
		$delta = $v * $dev;
		
		// iteratively determine new volatility
		$a = log($vol*$vol);
		$x0 = $a; $x1 = 0.0;
		$phi2v = $phi*$phi + $v;
		$delta2 = $delta*$delta;
		$d = 0.0; $d2 = 0.0; $d3 = 0.0;
		$h1 = 0.0; $h2 = 0.0;
		for ($i=1; $i<=20; $i++) {
			$expx0 = exp($x0);
			$d = $phi2v + $expx0;
			$d2 = $d*$d;
			$d3 = $d2*$d;
			$h1 = (-1.0 * ($x0 - $a) / $this->tau2) - (0.5 * $expx0 / $d) + (0.5 * $expx0 * $delta2 / $d2);
			$h2 = (-1.0 / $this->tau2) - (0.5 * $expx0 * $phi2v / $d2) + (0.5 * $delta2 * $expx0 * ($phi2v - $expx0) / $d3);
			$x1 = $x0 - $h1/$h2;
			if (abs($x1-$x0)<0.000001)
				break;
			$x0 = $x1;
		}
		$newvol = exp($x1/2.0);
		
		// limit volatility (some weird results in database?)
		if ($newvol>1000.0)
			$newvol = 1000.0;
		else if ($newvol<0.0)
			$newvol = 0.0;
		
		// update rating, RD
		$newphi = 1.0 / sqrt(1.0/($phi*$phi + $newvol*$newvol) + 1.0/$v);
		$newmu  = $mu + ($newphi*$newphi) * $dev;
		
		// convert back to Glicko scale
		return array(
					'glicko2' => $newmu * $this->convert + $this->init_rating,
					'RD2'     => $newphi * $this->convert,
					'vol2'    => $newvol
					);
	}
	
	// wrappers to handle our integer *1000 values
	function validRating($rating, $battles) {
		return ($battles==0) ? $this->init_rating : (float)$rating / 1000.0;
	}
	
	function validDeviation($deviation, $battles) {
		return (($deviation==0) || ($battles==0)) ? $this->init_rd : (float)$deviation / 1000.0;
	}
	
	function validVolatility($volatility, $battles) {
		return (($volatility==0) || ($battles==0)) ? $this->init_vol : (float)$volatility / 1000000.0;
	}
	
	function calcExpected($rating, $rj, $RDj) {
	    $mu    = ( ((float)$rating/1000.0) - $this->init_rating) / $this->convert;
	    $mu_j  = ( ((float)$rj/1000.0) - $this->init_rating) / $this->convert;
		$phi_j = ((float)$RDj/1000.0) / $this->convert;
		return ($this->E($mu, $mu_j, $phi_j) * 100.0);
	}
	
	function calcRating($rating, $deviation, $volatility, $battles, $glicko, $updates) {
		$newrating = $this->rating($this->validRating($rating, $battles, $glicko), 
									$this->validDeviation($deviation, $battles), 
									$this->validVolatility($volatility, $battles), 
									$updates);
		$newrating['vol2'] *= 1000.0;	// this is a really small number!
		foreach($newrating as $k=>$v)
			$newrating[$k] = (int)($v*1000.0);
		return $newrating;
	}
	
	function eloScale($rating) {
	    return (($rating-1500.0) * 1.53 + 1600.0);
	}
}

?>