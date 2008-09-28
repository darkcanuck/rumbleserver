<?php

require_once 'classes/common.php';

// determine game type
$version = 1;
$game    = trim(isset($_GET['game']) ? $_GET['game'] : '');
$gametype = new GameType($version, $game, '', '');

// check bot name
$party = new Participants($db, $gametype->getCode());
$name = trim(isset($_GET['name']) ? $_GET['name'] : '');
$bot = $party->getByName($name);

// check vs name
$vs_name = trim(isset($_GET['vs']) ? $_GET['vs'] : '');
$vs  = $party->getByName($vs_name);

// get battle results for pairing
$battles = new BattleResults($db);
$allrows = $battles->getPairingDetails($gametype->getCode(), $bot['bot_id'], $vs['bot_id']);


//output header
echo "<h2>BATTLE DETAILS FOR $name VS $vs_name IN GAME roborumble</h2>
<table border=1>
<tr>
	<td rowspan='2'><b>% Score</b></td>
	<td rowspan='2'><b>% Survival</b></td>
	<td colspan='3'><b>$name</b></td>
	<td colspan='3'><b>$vs_name</b></td>
	<td rowspan='2'><b>Battle Time</b></td>
	<td rowspan='2'><b>Submitted by</b></td>
</tr>
<tr>
	<td>score</td><td>bullet dmg.</td><td>survival</td>
	<td>score</td><td>bullet dmg.</td><td>survival</td>
</tr>";

// output data
foreach ($allrows as $rs) {
	$score_pct = $rs['bot_score'] / ($rs['bot_score'] + $rs['vs_score']) * 100;
	$cell_colour = ($score_pct>60) ? ' bgcolor="#99CC00"' : 
				 ( ($score_pct<40) ? ' bgcolor="#FF6600"' :  '');
	$survival = $rs['bot_survival'] / ($rs['bot_survival'] + $rs['vs_survival']) * 100;
	$surv_colour = ($survival>60) ? ' bgcolor="#99CC00"' : 
				 ( ($survival<40) ? ' bgcolor="#FF6600"' :  '');
	echo "<tr>";
	echo "<td" . $cell_colour . ">" . number_format($score_pct, 3)  . "</td>";
	echo "<td" . $surv_colour . ">" . number_format($survival, 1)  . "</td>";
	echo "<td>{$rs['bot_score']}</td>";
	echo "<td>{$rs['bot_bulletdmg']}</td>";
	echo "<td>{$rs['bot_survival']}</td>";
	echo "<td>{$rs['vs_score']}</td>";
	echo "<td>{$rs['vs_bulletdmg']}</td>";
	echo "<td>{$rs['vs_survival']}</td>";
	echo "<td>{$rs['timestamp']}:{$rs['millisecs']}</td>";
	echo "<td>{$rs['user']}@{$rs['ip_addr']} ver.{$rs['version']}</td>";
	echo "</tr>\n";
}

//output footer
echo "</table>";

?>