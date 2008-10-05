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
$allrows = $battles->getBattleDetails($gametype->getCode(), $bot['bot_id'], $vs['bot_id']);


//output header
echo "<h2>BATTLE DETAILS FOR $name VS $vs_name IN GAME roborumble</h2>
<table border=1>
<tr>
	<td rowspan='2'><b><a href='BattleDetails?game=$game&name=$name&vs=$vs_name&sort=score_pct' title='Sort by % Score'>% Score</a></b></td>
	<td rowspan='2'><b><a href='BattleDetails?game=$game&name=$name&vs=$vs_name&sort=score_survival' title='Sort by % Survival'>% Survival</a></b></td>
	<td colspan='3'><b>$name</b></td>
	<td colspan='3'><b>$vs_name</b></td>
	<td rowspan='2'><b><a href='BattleDetails?game=$game&name=$name&vs=$vs_name&sort=timestamp' title='Sort by Time'>Battle Time</a></b></td>
	<td rowspan='2'><b><a href='BattleDetails?game=$game&name=$name&vs=$vs_name&sort=user' title='Sort by User'>Submitted by</a></b></td>
</tr>
<tr>
	<td><a href='BattleDetails?game=$game&name=$name&vs=$vs_name&sort=bot_score' title='Sort by Score'>score</a></td>
	<td><a href='BattleDetails?game=$game&name=$name&vs=$vs_name&sort=bot_bulletdmg' title='Sort by Bullet Damage'>bullet dmg.</a></td>
	<td><a href='BattleDetails?game=$game&name=$name&vs=$vs_name&sort=bot_survival' title='Sort by Survival'>survival</a></td>
	<td><a href='BattleDetails?game=$game&name=$name&vs=$vs_name&sort=vs_score' title='Sort by Score'>score</a></td>
	<td><a href='BattleDetails?game=$game&name=$name&vs=$vs_name&sort=vs_bulletdmg' title='Sort by Bullet Damage'>bullet dmg.</a></td>
	<td><a href='BattleDetails?game=$game&name=$name&vs=$vs_name&sort=vs_survival' title='Sort by Survival'>survival</a></td>
</tr>";

// calculate & sort
$fields = null;
$sort = trim(isset($_GET['sort']) ? $_GET['sort'] : '');
$sortcol = array();
$sorttime = array();
foreach ($allrows as $k=>$rs) {
	$allrows[$k]['score_pct'] = $rs['bot_score'] / ($rs['bot_score'] + $rs['vs_score']) * 100;
	$allrows[$k]['score_survival'] = $rs['bot_survival'] / ($rs['bot_survival'] + $rs['vs_survival']) * 100;
	
	if ($fields==null) {
		// initialize sorting
		$fields = array_keys($allrows[$k]);
		if (($sort=='') || !in_array($sort, $fields))
			$sort = null;
	}
	if ($sort!=null) {
		$sortcol[$k] = $allrows[$k][ $sort ];
		$sorttime[$k] = $allrows[$k]['timestamp'];
	}
}
if ($sort!=null)
	array_multisort($sortcol, SORT_DESC, $sorttime, SORT_DESC, $allrows);

// output data
foreach ($allrows as $rs) {
	$rs['score_pct'] = $rs['bot_score'] / ($rs['bot_score'] + $rs['vs_score']) * 100;
	$cell_colour = ($rs['score_pct']>60) ? ' bgcolor="#99CC00"' : 
				 ( ($rs['score_pct']<40) ? ' bgcolor="#FF6600"' :  '');
	$surv_colour = ($rs['score_survival']>60) ? ' bgcolor="#99CC00"' : 
				 ( ($rs['score_survival']<40) ? ' bgcolor="#FF6600"' :  '');
	echo "<tr>";
	echo "<td" . $cell_colour . ">" . number_format($rs['score_pct'], 3)  . "</td>";
	echo "<td" . $surv_colour . ">" . number_format($rs['score_survival'], 1)  . "</td>";
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