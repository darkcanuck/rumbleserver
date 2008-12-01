<?php

require_once 'classes/common.php';

require_once 'Smarty/Smarty.class.php';
$template = new Smarty();

require_once 'classes/Glicko2Rating.php';
$glicko2 = new Glicko2Rating();

// determine game type
$version = trim(isset($_GET['version']) ? $_GET['version'] : '');
$game    = trim(isset($_GET['game']) ? $_GET['game'] : '');
$gametype = new GameType($version, $game, '', '');

// get game results
$party = new Participants($db, $gametype->getCode(), 'score_pct');
$allrows = $party->getList();

// calculate & sort
$total = 0;
$fields = null;
$sort = trim(isset($_GET['sort']) ? $_GET['sort'] : '');
$sortcol = array();
$sortaps = array();
$rank = 1;
foreach ($allrows as $k=>$rs) {
	$chunks = explode('.', $rs['name']);
	$allrows[$k]['package'] = $chunks[0];
	
	$allrows[$k]['score_pct'] /= 1000;
	$allrows[$k]['score_dmg'] /= 1000;
	$allrows[$k]['score_survival'] /= 1000;
	
	$allrows[$k]['rating_classic'] /= 1000;
	$allrows[$k]['rating_glicko'] /= 1000;
	$allrows[$k]['rd_glicko'] /= 1000;
	//$allrows[$k]['rating_glicko2'] /= 1000;
	$allrows[$k]['rating_glicko2'] = $glicko2->eloScale((float)$allrows[$k]['rating_glicko2']/1000.0);
	$allrows[$k]['rd_glicko2'] /= 1000;
	$allrows[$k]['vol_glicko2'] /= 1000000;
	
	$allrows[$k]['score_pl'] = $rs['count_wins'] * 2;
	$allrows[$k]['rank'] = $rank;
	$rank++;
    
	$total += $rs['battles'];
    
	if ($fields==null) {
		// initialize sorting
		$fields = array_keys($allrows[$k]);
		if (($sort=='') || !in_array($sort, $fields))
			$sort = null;
	}
	if ($sort!=null) {
		$sortcol[$k] = $allrows[$k][ $sort ];
		$sortaps[$k] = $allrows[$k]['score_pct'];
	}
}
$total /= 2;	// because each bot counted twice
if ($sort!=null)
	array_multisort($sortcol, SORT_DESC, $sortaps, SORT_DESC, $allrows);


// assign data to template & display results
$template->assign('gentime', strftime('%Y-%m-%d %T %z'));
$template->assign('version', htmlspecialchars($version));
$template->assign('game', htmlspecialchars($game));
$template->assign('gametype', $gametype);
$template->assign('totalbattles', $total);
$template->assign('rankings', $allrows);

$template->display('rankings.tpl');


if (isset($_GET['forceupdate'])) {
	echo '<pre>' . $db->debug() . '</pre>';
}

?>