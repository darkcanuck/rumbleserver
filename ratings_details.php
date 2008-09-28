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
$pairings = new GamePairings($db);
$allrows = $pairings->getBotPairings($gametype->getCode(), $bot['bot_id']);

// ratings calc
$partylist = $party->getList();
$glicko = new GlickoRating();


//output header
echo "<h2>RATING DETAILS FOR $name IN GAME roborumble</h2>
<h3>CURRENT SCORE = " . number_format($bot['score_pct']/1000, 3) . "</h3>
<p>Participated in {$bot['battles']} battles since {$bot['created']}  Last battle held {$bot['timestamp']}.</p>
<table border=1>
<tr>
	<td><b>Enemy</b></td>
	<td><b>% Score</b></td>
	<td><b>G-Rating</b></td>
	<td><b>Battles</b></td>
	<td><b>Last Battle</b></td>
	<td><b>Battles</b></td>
	<td><b>Expected %</b></td>
	<td><b>ProblemBot Index</b></td>	
</tr>";


// output data
foreach ($allrows as $rs) {
	$cell_colour = ($rs['score_pct']>60000) ? ' bgcolor="#99CC00"' : 
				 ( ($rs['score_pct']<40000) ? ' bgcolor="#FF6600"' :  '');
	$expected = $glicko->calcExpected($bot['score_elo'],
									$partylist[ $rs['vs_id'] ]['score_elo'],
									$partylist[ $rs['vs_id'] ]['deviation']
									);
	$pbindex = $rs['score_pct']/1000.0 - $expected;
	
	echo "<tr>";
	echo "<td>{$rs['vs_name']}</td>";
	echo "<td" . $cell_colour . ">" . number_format($rs['score_pct']/1000, 3)  . "</td>";
	echo "<td>" . number_format($partylist[ $rs['vs_id'] ]['score_elo']/1000, 1, '.', '')  . 
			" (" . number_format($partylist[ $rs['vs_id'] ]['deviation']/1000, 0)  . ")</td>";	//ELO rank
	echo "<td>{$rs['battles']}</td>";
	echo "<td>{$rs['timestamp']}</td>";
	echo "<td><a href='BattleDetails?game=" . htmlspecialchars($game) 
				. "&name=" . htmlspecialchars($name)
				. "&vs="   . htmlspecialchars($rs['vs_name']) . "'>battles</a></td>";		//details link
	echo "<td>" . number_format($expected, 3) . "</td>";	//expected score
	echo "<td>" . number_format($pbindex, 3) . "</td>";	//PBI
	echo "</tr>\n";
}

//output footer
echo "</table>";

?>