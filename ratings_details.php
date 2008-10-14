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

// calculate & sort
$standarddev = 0.0;
$momentum = 0.0;
$specialization = 0.0;
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
	$standarddev += pow(($allrows[$k]['score_pct'] - $bot['score_pct'])/100000.0, 2.0);
	$momentum += 3.0 * $allrows[$k]['pbindex'];
	$specialization += pow($allrows[$k]['pbindex']/100.0, 2.0);
	
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
$standarddev = sqrt($standarddev / $bot['pairings']) * 100.0;
if ($sort!=null)
	array_multisort($sortcol, ($sort=='vs_name') ? SORT_ASC : SORT_DESC, $sortname, SORT_ASC, $allrows);

// JSON output for LRP graphs
if (isset($_REQUEST['json']) && (isset($_REQUEST['json'])>0)) {
    $json_rating = number_format($bot['rating_glicko']/1000.0, 1, '.', '');
    $json_battles = $bot['battles'];
    $json_lastbattle = strtotime($bot['timestamp'])*1000;
    echo <<<EOT
{
"name": "$name",
"game": "$game",
"rating": $json_rating,
"numBattles": $json_battles,
"lastBattle" : $json_lastbattle,
"pairings": [    
EOT;
    foreach ($allrows as $rs) {
    	echo '{"name": "' . $rs['vs_name'] . '", ';
    	echo '"ranking": ' . $rs['rating_glicko']/1000.0 . ', ';
    	echo '"score": ' . number_format($rs['score_pct']/1000, 3) . ', ';
    	echo '"numBattles": ' . $rs['battles'] . ', ';
    	echo '"lastBattle": ' . strtotime($rs['timestamp'])*1000 . ', ';
    	echo '"expectedScore": ' . number_format($rs['expected'], 1) . ', ';
    	echo '"PBI": ' . number_format($rs['pbindex'], 1) . "},\n";
    }
    $json_specialization = number_format($specialization*100.0, 3);
    $json_momentum = number_format($momentum, 3);
    $json_aps = number_format($bot['score_pct']/1000/100, 6);
    echo <<<EOT
], 
"specializationIndex": $json_specialization,
"momentum": $json_momentum,
"APS": $json_aps
}    
EOT;
    exit(0);
}


//output header
echo "<h2>RATING DETAILS FOR $name IN GAME $game</h2>
<table border='1'>
	<tr><th colspan='2'>Stats for $name (<a href='RatingsLRP?game=$game&name=$name' title='LRP Graph'>LRP</a>)</th></tr>
	<tr><td>\"Classic\" Elo Rating</td><td>" . number_format($bot['rating_classic']/1000, 1, '.', '') . "</td></tr>
	<tr><td>Glicko Rating (RD)</td><td>" . number_format($bot['rating_glicko']/1000, 1, '.', '') .
	 									" (" . number_format($bot['rd_glicko']/1000, 0)  . ")</td></tr>
	<tr><td>Glicko-2 (RD, volatility)</td><td>" . number_format($bot['rating_classic']/1000, 1, '.', '') 									.
										 " (" . number_format($bot['rd_glicko2']/1000, 0)  . 
										 ", " . number_format($bot['vol_glicko2']/1000000, 3)  . ")</td></tr>
	<tr><td>Rating Momentum</td><td>" . number_format($momentum, 3) . "</td></tr>
	<tr><td>Specialization</td><td>" . number_format($specialization*100.0, 3) . "</td></tr>
	<tr><td>Average % Score (APS)</td><td>" . number_format($bot['score_pct']/1000, 3) . " %</td></tr>
	<tr><td>Standard Deviation</td><td>" . number_format($standarddev, 3) . "</td></tr>
	<tr><td>Average % Survival</td><td>" . number_format($bot['score_survival']/1000, 3) . " %</td></tr>
	<tr><td># Battles</td><td>{$bot['battles']}</td></tr>
	<tr><td># Pairings</td><td>{$bot['pairings']}</td></tr>
	<tr><td># Pairs Won</td><td>{$bot['count_wins']} (" . number_format($bot['count_wins']/$bot['pairings'] * 100.0, 1) . "%)</td></tr>
	<tr><td>Added to Rumble</td><td>{$bot['created']}</td></tr>
	<tr><td>Last Battle</td><td>{$bot['timestamp']}</td></tr>
</table>

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


// output data
foreach ($allrows as $rs) {
	$cell_colour = ($rs['score_pct']>60000) ? ' bgcolor="#99CC00"' : 
				 ( ($rs['score_pct']<40000) ? ' bgcolor="#FF6600"' :  '');
	$pbi_colour = ($rs['pbindex']>=10.0) ? ' bgcolor="#99CC00"' : 
				 ( ($rs['pbindex']<=-10.0) ? ' bgcolor="#FF6600"' :  '');
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
	echo "<td>" . number_format($rs['expected'], 1) . "</td>";
	echo "<td" . $pbi_colour . ">" . number_format($rs['pbindex'], 1) . "</td>";
	echo "</tr>\n";
}

//output footer
echo "</table>\n";
?>