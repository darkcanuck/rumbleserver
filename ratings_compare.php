<?php
/******************************************************************************
 * Compare Bot Details view  --  Darkcanuck's Roborumble Server
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
require_once 'classes/EloRating.php';
require_once 'classes/GlickoRating.php';
require_once 'classes/Glicko2Rating.php';

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
$bot['state'] = ($party->isRetired()) ? '(RETIRED)' : ''; 
$chunks = explode('.', $name);
$bot['package'] = $chunks[0];

// check vs name
$vs_name = trim(isset($_GET['vs']) ? $_GET['vs'] : '');
$vs  = $party->getByName($vs_name, true);
$vs['state'] = ($party->isRetired()) ? '(RETIRED)' : ''; 
$chunks = explode('.', $vs_name);
$vs['package'] = $chunks[0];

// get pairings for both
$pairings = new GamePairings($db, $gametype->getCode());
$bot_pairs = $pairings->getBotPairings($gametype->getCode(), $bot['bot_id'], $bot['state']!='');
$vs_pairs = $pairings->getBotPairings($gametype->getCode(), $vs['bot_id'], $vs['state']!='');

// ratings calc
$elo     = new EloRating();
$glicko  = new GlickoRating();
$glicko2 = new Glicko2Rating();

// bot summaries
$details = array($bot, $vs);
foreach ($details as $k=>$v) {
    foreach (array('score_pct', 'score_dmg', 'score_survival', 'rating_classic', 'rating_glicko', 'rd_glicko',
                'rating_glicko2', 'rd_glicko2', 'vol_glicko2') as $f)
        $details[$k][$f] /= 1000.0;
    $details[$k]['rating_glicko2'] = $glicko2->eloScale($details[$k]['rating_glicko2']);
    $details[$k]['vol_glicko2'] /= 1000.0;
    $details[$k]['percent_wins'] = $details[$k]['count_wins']/($details[$k]['pairings']>0 ? $details[$k]['pairings'] : 1) * 100.0;
    $details[$k]['percent_score'] = $details[$k]['score_pct']/100.0;
    $details[$k]['unixtimestamp'] = strtotime($details[$k]['timestamp'])*1000;
    $details[$k]['avg_score'] = 0.0;
    $details[$k]['avg_survival'] = 0.0;
}

// create superset of pairings
$allpairs = array();
foreach($bot_pairs as $k=>$v)
    $allpairs[ $v['vs_name'] ]['bot_data'] = $v;
foreach($vs_pairs as $k=>$v)
    $allpairs[ $v['vs_name'] ]['vs_data'] = $v;
ksort($allpairs, SORT_STRING);

// calculate and sort
$common_pairs = 0;
$fields = null;
$sort = trim(isset($_GET['sort']) ? $_GET['sort'] : '');
$sortcol = array();
$sortname = array();
foreach ($allpairs as $k=>$v) {
    $allpairs[$k]['vs_name'] = $k;
	$chunks = explode('.', $k);
	$allpairs[$k]['package'] = $chunks[0];
    
    $datafields = array('score_pct', 'score_dmg', 'score_survival',
                        'vs_pct', 'vs_dmg', 'vs_survival',
                        'diff_pct', 'diff_dmg', 'diff_survival');
    foreach ($datafields as $f)
        $allpairs[$k][$f] = '--';
    
    if (isset($v['bot_data'])) {
        $allpairs[$k]['score_pct'] = $v['bot_data']['score_pct'] / 1000.0;
	    $allpairs[$k]['score_dmg'] = $v['bot_data']['score_dmg'] / 1000.0;
	    $allpairs[$k]['score_survival'] = $v['bot_data']['score_survival'] / 1000.0;
    }
    if (isset($v['vs_data'])) {
        $allpairs[$k]['vs_pct'] = $v['vs_data']['score_pct'] / 1000.0;
	    $allpairs[$k]['vs_dmg'] = $v['vs_data']['score_dmg'] / 1000.0;
	    $allpairs[$k]['vs_survival'] = $v['vs_data']['score_survival'] / 1000.0;
    }
    
    if (isset($v['bot_data']) && isset($v['vs_data'])) {
        $allpairs[$k]['diff_pct'] = $allpairs[$k]['score_pct'] - $allpairs[$k]['vs_pct'];
	    $allpairs[$k]['diff_dmg'] = $allpairs[$k]['score_dmg'] - $allpairs[$k]['vs_dmg'];
	    $allpairs[$k]['diff_survival'] = $allpairs[$k]['score_survival'] - $allpairs[$k]['vs_survival'];
	    
	    $details[0]['avg_score'] += $allpairs[$k]['score_pct'];
	    $details[0]['avg_survival'] += $allpairs[$k]['score_survival'];
	    $details[1]['avg_score'] += $allpairs[$k]['vs_pct'];
	    $details[1]['avg_survival'] += $allpairs[$k]['vs_survival'];	    
	    $common_pairs++;
    }
	
	if ($fields==null) {
		// initialize sorting
		$fields = array_keys($allpairs[$k]);
		if (($sort=='') || !in_array($sort, $fields))
			$sort = null;
	}
	if ($sort!=null) {
		$sortcol[$k] = $allpairs[$k][ $sort ];
		$sortname[$k] = $allpairs[$k]['vs_name'];
	}
}
foreach ($details as $k=>$v) {
    $details[$k]['avg_score'] /= (float)$common_pairs;
    $details[$k]['avg_survival'] /= (float)$common_pairs;
}
if ($sort!=null)
	array_multisort($sortcol, ($sort=='vs_name') ? SORT_ASC : SORT_DESC, $sortname, SORT_ASC, $allpairs);


// assign data to template & display results
$template->assign('gentime', strftime('%Y-%m-%d %T %z'));
$template->assign('version', htmlspecialchars($version));
$template->assign('game', htmlspecialchars($game));
$template->assign('gametype', $gametype);
$template->assign('name', htmlspecialchars($name));
$template->assign('vs_name', htmlspecialchars($vs_name));
$template->assign('details', $details[0]);
$template->assign('vs_details', $details[1]);
$template->assign('pairings', $allpairs);

$template->display('ratings_compare.tpl');

?>