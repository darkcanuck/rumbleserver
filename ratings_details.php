<?php

require_once 'classes/common.php';
require_once 'classes/GlickoRating.php';

// determine game type
$version = 1;
$game    = trim(isset($_GET['game']) ? $_GET['game'] : '');
$gametype = new GameType($version, $game, '', '');

// check bot name
$party = new Participants($db, $gametype->getCode());
$name = trim(isset($_GET['name']) ? $_GET['name'] : '');
$bot = $party->getByName($name);

// get pairings for bot
$pairings = new GamePairings($db, $gametype->getCode());
$allrows = $pairings->getBotPairings($gametype->getCode(), $bot['bot_id']);

// ratings calc
$partylist = $party->getList();
$glicko = new GlickoRating();


//output header
echo "<h2>RATING DETAILS FOR $name IN GAME $game</h2>
<h3>CURRENT SCORE = " . number_format($bot['score_pct']/1000, 3) . "</h3>
<p>Participated in {$bot['battles']} battles since {$bot['created']}  Last battle held {$bot['timestamp']}.</p>
<table border=1>
<tr>
	<td><b><a href='RatingsDetails?game=$game&name=$name&sort=vs_name' title='Sort by Name'>Enemy</a></b></td>
	<td><b><a href='RatingsDetails?game=$game&name=$name&sort=score_pct' title='Sort by APS'>% Score</a></b></td>
	<td><b><a href='RatingsDetails?game=$game&name=$name&sort=rating_glicko' title='Sort by Rating'>G-Rating</a></b></td>
	<td><b><a href='RatingsDetails?game=$game&name=$name&sort=battles' title='Sort by Battles'>Battles</a></b></td>
	<td><b><a href='RatingsDetails?game=$game&name=$name&sort=timestamp' title='Sort by Time'>Last Battle</a></b></td>
	<td><b>Battles</b></td>
	<td><b><a href='RatingsDetails?game=$game&name=$name&sort=expected' title='Sort by Expected %'>Expected %</a></b></td>
	<td><b><a href='RatingsDetails?game=$game&name=$name&sort=pbindex' title='Sort by PBI'>ProblemBot Index</a></b></td>	
</tr>";

// calculate & sort
$momentum = 0;
$fields = null;
$sort = trim(isset($_GET['sort']) ? $_GET['sort'] : '');
$sortcol = array();
$sortname = array();
foreach ($allrows as $k=>$rs) {
	$allrows[$k]['rating_glicko'] = $partylist[ $rs['vs_id'] ]['rating_glicko'];
	$allrows[$k]['rd_glicko'] = $partylist[ $rs['vs_id'] ]['rd_glicko'];
	$allrows[$k]['expected'] = $glicko->calcExpected($bot['rating_glicko'],
									$partylist[ $rs['vs_id'] ]['rating_glicko'],
									$partylist[ $rs['vs_id'] ]['rd_glicko']
									);
	$allrows[$k]['pbindex'] = $rs['score_pct']/1000.0 - $allrows[$k]['expected'];
	$momentum += $allrows[$k]['pbindex'];
	
	if ($fields==null) {
		// initialize sorting
		$fields = array_keys($allrows[$k]);
		if (($sort=='') || !in_array($sort, $fields))
			$sort = null;
	}
	if ($sort!=null) {
		$sortcol[$k] = $allrows[$k][ $sort ];
		$sortname[$k] = $allrows[$k]['vs_name'];
	}
}
if ($sort!=null)
	array_multisort($sortcol, ($sort=='vs_name') ? SORT_ASC : SORT_DESC, $sortname, SORT_ASC, $allrows);

// output data
foreach ($allrows as $rs) {
	$cell_colour = ($rs['score_pct']>60000) ? ' bgcolor="#99CC00"' : 
				 ( ($rs['score_pct']<40000) ? ' bgcolor="#FF6600"' :  '');
	echo "<tr>";
	echo "<td>{$rs['vs_name']}</td>";
	echo "<td" . $cell_colour . ">" . number_format($rs['score_pct']/1000, 3)  . "</td>";
	echo "<td>" . number_format($rs['rating_glicko']/1000, 1, '.', '')  . 
			" (" . number_format($rs['rd_glicko']/1000, 0)  . ")</td>";
	echo "<td>{$rs['battles']}</td>";
	echo "<td>{$rs['timestamp']}</td>";
	echo "<td><a href='BattleDetails?game=" . htmlspecialchars($game) 
				. "&name=" . htmlspecialchars($name)
				. "&vs="   . htmlspecialchars($rs['vs_name']) . "'>battles</a></td>";
	echo "<td>" . number_format($rs['expected'], 3) . "</td>";
	echo "<td>" . number_format($rs['pbindex'], 3) . "</td>";
	echo "</tr>\n";
}

//output footer
echo "</table>\n";
echo "<p><b>Momentum = " . number_format($momentum, 3) . "</b></p>\n";
?>