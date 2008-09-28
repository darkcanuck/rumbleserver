<?php

require_once 'classes/common.php';
require_once 'classes/GlickoRating.php';

$glicko = new GlickoRating();

// determine game type
$version = trim(isset($_GET['version']) ? $_GET['version'] : '');
$game    = trim(isset($_GET['game']) ? $_GET['game'] : '');
$gametype = new GameType($version, $game, '', '');

// get game results
$party = new Participants($db, $gametype->getCode(), 'score_pct');
$allrows = $party->getList();

//output header
$gentime = strftime('%Y-%m-%d %T %z');
echo "<h2>CURRENT RANKINGS FOR GAME $game</h2>
<p>Results from Darkcanuck's experimental new RoboRumble server.  Stable results can be found from the RoboCode wiki.</p>
<p>Generation time: $gentime</p>
<table border=1>
<tr>
	<td><b>Rank</b></td>
	<td><b>Flag</b></td>
	<td><b>Competitor</b></td>
	<td><b><a href='Rankings?version=$version&game=$game&sort=score_pct' title='Sort by APS'>APS</a></b></td>
	<td><b><a href='Rankings?version=$version&game=$game&sort=score_elo' title='Sort by Rating'>G-Rating (RD)</a></b></td>
	<td><b><a href='Rankings?version=$version&game=$game&sort=ideal' title='Sort by Ideal'>Ideal</a></b></td>
	<td><b>Details</b></td>
	<td><b><a href='Rankings?version=$version&game=$game&sort=battles' title='Sort by Battles'>Battles</a></b></td>
	<td><b><a href='Rankings?version=$version&game=$game&sort=pairings' title='Sort by Pairings'>Pairings</a></b></td>
	<td><b>Last Update</b></td>
</tr>";

// calculate & sort
$total = 0;
$fields = null;
$sort = trim(isset($_GET['sort']) ? $_GET['sort'] : '');
$sortcol = array();
$sortaps = array();
foreach ($allrows as $k=>$rs) {
	$chunks = explode('.', $rs['name']);
	$allrows[$k]['package'] = $chunks[0];
	$allrows[$k]['ideal'] = $glicko->calcIdealRating($rs['score_pct'], 1500.0 *1000, 350.0 * 1000);
	$total += $rs['battles'];

	if ($fields==null) {
		// initialize sorting
		$fields = array_keys($allrows[$k]);
		if (($sort=='') || !in_array($sort, $fields))
			$sort = null;
	}
	if ($sort!=null) {
		$sortcol[$k] = $rs[ $sort ];
		$sortaps[$k] = $rs['score_pct'];
	}
}
$total /= 2;	// because each bot counted twice
if ($sort!=null)
	array_multisort($sortcol, SORT_DESC, $sortaps, SORT_DESC, $allrows);

// output data
$rank = 1;
foreach ($allrows as $rs) {
	echo "<tr><td>$rank</td>";
	echo "<td><img src='flags/{$rs['package']}.gif' title='$pkg' /></td>";	// flag
	echo "<td>{$rs['name']}</td>";
	echo "<td>" . number_format($rs['score_pct']/1000, 2)  . "</td>";
	echo "<td>" . number_format($rs['score_elo']/1000, 1, '.', '')  . 
			" (" . number_format($rs['deviation']/1000, 0)  . ")</td>";
	echo "<td>" . number_format($rs['ideal'], 0, '.', '')  . "</td>";
	echo "<td><a href='RatingsDetails?game=" . htmlspecialchars($game) 
				. "&name=" . htmlspecialchars($rs['name']) . "'>details</a></td>";		//details link
	echo "<td>{$rs['battles']}</td>";
	echo "<td>{$rs['pairings']}</td>";
	echo "<td>{$rs['timestamp']}</td>";
	echo "</tr>\n";
	$rank++;
}

//output footer
echo "</table>
<p><b>Total battles = $total</b></p>
<p>Ratings are calculated using the the <a href='http://math.bu.edu/people/mg/glicko/glicko.doc/glicko.html'>Glicko rating system</a>
by Mark E. Glickman.  Default ratings for new competitors are set at 1500 with a ratings deviation (RD) of 350.  The 'ideal' rating
listed is based on the competitor's average percentage score (APS) and is an indication of the rating's accuracy once enough pairings
have been recorded to give a stable APS.</p>";

if (isset($_GET['forceupdate'])) {
	echo '<pre>' . $db->debug() . '</pre>';
}

?>