<?php
/******************************************************************************
 * Battle Details view  --  Darkcanuck's Roborumble Server
 *
 * $HeadURL$
 * $Date$
 * $Revision$
 * $Author$
 *
 * Copyright 2008-2009 Jerome Lavigne (jerome@darkcanuck.net)
 * Released under GPL version 3.0 http://www.gnu.org/licenses/gpl-3.0.html
 *****************************************************************************/

require_once 'classes/common.php';

require_once 'Smarty/Smarty.class.php';
$template = new Smarty();

// determine game type
$version = 1;
$game    = trim(isset($_GET['game']) ? $_GET['game'] : '');
$gametype = new GameType($version, $game, '', '');

// check bot name
$party = new Participants($db, $gametype->getCode());
$name = trim(isset($_GET['name']) ? $_GET['name'] : '');
$bot = $party->getByName($name);
$chunks = explode('.', $name);
$package = $chunks[0];

// check vs name
$vs_name = trim(isset($_GET['vs']) ? $_GET['vs'] : '');
$vs  = $party->getByName($vs_name);
$chunks = explode('.', $vs_name);
$vs_package = $chunks[0];

// get battle results for pairing
$battles = new BattleResults($db);
$allrows = $battles->getBattleDetails($gametype->getCode(), $bot['bot_id'], $vs['bot_id']);

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

$template->display('battle_details.tpl');

?>