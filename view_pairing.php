<?php
/******************************************************************************
 * Pairing Details view  --  Darkcanuck's Roborumble Server
 *
 * Copyright 2008-2011 Jerome Lavigne (jerome@darkcanuck.net)
 * Released under GPL version 3.0 http://www.gnu.org/licenses/gpl-3.0.html
 *****************************************************************************/

require_once 'classes/common.php';

require_once 'Smarty/Smarty.class.php';
$template = new Smarty();

// determine game type
$version = 1;
$game    = trim(isset($_GET['game']) ? $_GET['game'] : '');
$gametype = new GameType($version, $game);

// check bot name
$party = new Participants($db, $gametype->getCode());
$name = trim(isset($_GET['name']) ? $_GET['name'] : '');
$bot = $party->getByName($name, true);
$retired = $party->isRetired();
$chunks = explode('.', $name);
$package = $chunks[0];

// check vs name
$vs_name = trim(isset($_GET['vs']) ? $_GET['vs'] : '');
$vs  = $party->getByName($vs_name);
$vs_retired = $party->isRetired();
$chunks = explode('.', $vs_name);
$vs_package = $chunks[0];

// get battle results for pairing
$battles = new BattleResults($db);
$allrows = $battles->getBattleDetails($gametype->getCode(), $bot['bot_id'], $vs['bot_id'], ($retired || $vs_retired));

// get pairing summary
$pairings = new GamePairings($db, $gametype->getCode());
$pairdata = $pairings->getSinglePairing($gametype->getCode(), $bot['bot_id'], $vs['bot_id']);

// calculate
foreach ($allrows as $k=>$rs) {
	$allrows[$k]['score_pct'] = $rs['bot_score'] / ($rs['bot_score'] + $rs['vs_score']) * 100;
	$allrows[$k]['score_survival'] = ($rs['bot_survival']+$rs['vs_survival'] > 0) ?
                                    $rs['bot_survival'] / ($rs['bot_survival'] + $rs['vs_survival']) * 100 : 50.0;
}

// assign data to template & display results
$template->assign('gentime', strftime('%Y-%m-%d %T %z'));
$template->assign('version', htmlspecialchars($version));
$template->assign('game', htmlspecialchars($game));
$template->assign('gametype', $gametype);
$template->assign('name', htmlspecialchars($name));
$template->assign('vs_name', htmlspecialchars($vs_name));
$template->assign('package', htmlspecialchars($package));
$template->assign('vs_package', htmlspecialchars($vs_package));
$template->assign('battles', $allrows);
$template->assign('pairing', $pairdata);


$template->display('view_pairing.tpl');

?>