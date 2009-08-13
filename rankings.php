<?php
/******************************************************************************
 * Game Rankings view  --  Darkcanuck's Roborumble Server
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

require_once 'classes/Glicko2Rating.php';
$glicko2 = new Glicko2Rating();

// determine game type
$version = trim(isset($_GET['version']) ? $_GET['version'] : '1');
$game    = trim(isset($_GET['game']) ? $_GET['game'] : 'roborumble');
$gamedef = new GameType($version, $game);

// get game results
$party = new Participants($db, $gamedef->getCode(), 'score_pct');
$allrows = $party->getList();

// calculate & sort
$total = 0;
$fields = null;
$sort = trim(isset($_GET['sort']) ? $_GET['sort'] : '');
$sortcol = array();
$sortaps = array();
$rank = 1;
$maxpair = 0;
$minpair = 0;
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
    
    // check pairing min/max values
    if (($minpair==0) || ($rs['pairings'] < $minpair))
        $minpair = $rs['pairings'];
    if ($rs['pairings'] > $maxpair)
        $maxpair = $rs['pairings'];
    
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

// generate page message re pairings
$totalpair = count($allrows) - 1;
$message = "PAIRINGS COMPLETE";
if ($minpair < $totalpair)
    $message = "PAIRINGS INCOMPLETE -- results unstable until all competitors have reached $totalpair pairings.";
else if ($maxpair > $totalpair)
    $message = "RESULTS UNSTABLE -- one or more bots have recently been removed and all competitors need at least one battle before results can stabilize.";

// assign data to template & display results
$template->assign('gentime', strftime('%Y-%m-%d %T %z'));
$template->assign('version', htmlspecialchars($version));
$template->assign('game', htmlspecialchars($game));
$template->assign('survival', $gamedef->useSurvival());
$template->assign('totalbattles', $total);
$template->assign('pagemessage', $message);
$template->assign('rankings', $allrows);

$template->display('rankings.tpl');


if (isset($_GET['forceupdate'])) {
	echo '<pre>' . $db->debug() . '</pre>';
}

?>