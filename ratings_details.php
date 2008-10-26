<?php

require_once 'classes/common.php';
require_once 'classes/EloRating.php';
require_once 'classes/GlickoRating.php';
require_once 'classes/Glicko2Rating.php';

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

// get pairings for bot
$pairings = new GamePairings($db, $gametype->getCode());
$allrows = $pairings->getBotPairings($gametype->getCode(), $bot['bot_id']);

// ratings calc
$partylist = $party->getList();
$elo     = new EloRating();
$glicko  = new GlickoRating();
$glicko2 = new Glicko2Rating();

// calculate & sort
$details = $bot;
$details['stddev'] = 0.0;
$details['momentum'] = 0.0;
$details['special'] = 0.0;
foreach (array('score_pct', 'score_dmg', 'score_survival', 'rating_classic', 'rating_glicko', 'rd_glicko',
                'rating_glicko2', 'rd_glicko2', 'vol_glicko2') as $f)
    $details[$f] /= 1000.0;
$details['vol_glicko2'] /= 1000.0;
$details['percent_wins'] = $details['count_wins']/($details['pairings']>0 ? $details['pairings'] : 1) * 100.0;
$details['percent_score'] = $details['score_pct']/100.0;
$details['unixtimestamp'] = strtotime($bot['timestamp'])*1000;
$chunks = explode('.', $name);
$details['package'] = $chunks[0];

$fields = null;
$sort = trim(isset($_GET['sort']) ? $_GET['sort'] : '');
$sortcol = array();
$sortname = array();
foreach ($allrows as $k=>$rs) {
	$chunks = explode('.', $rs['vs_name']);
	$allrows[$k]['package'] = $chunks[0];
    
    $allrows[$k]['score_pct'] /= 1000.0;
	$allrows[$k]['score_dmg'] /= 1000.0;
	$allrows[$k]['score_survival'] /= 1000.0;
    
    $allrows[$k]['rating_classic'] = $partylist[ $rs['vs_id'] ]['rating_classic'] / 1000.0;
    $allrows[$k]['rating_glicko'] = $partylist[ $rs['vs_id'] ]['rating_glicko'] / 1000.0;
	$allrows[$k]['rd_glicko'] = $partylist[ $rs['vs_id'] ]['rd_glicko'] / 1000.0;
	$allrows[$k]['rating_glicko2'] = $partylist[ $rs['vs_id'] ]['rating_glicko2'] / 1000.0;
	$allrows[$k]['rd_glicko2'] = $partylist[ $rs['vs_id'] ]['rd_glicko2'] / 1000.0;
	$allrows[$k]['vol_glicko2'] = $partylist[ $rs['vs_id'] ]['vol_glicko2'] / 1000000.0;
	
	$allrows[$k]['expected'] = $elo->calcExpected($bot['rating_classic'],
									$partylist[ $rs['vs_id'] ]['rating_classic'] );
	$allrows[$k]['expected_glicko'] = $glicko->calcExpected($bot['rating_glicko'],
									$partylist[ $rs['vs_id'] ]['rating_glicko'],
									$partylist[ $rs['vs_id'] ]['rd_glicko'] );
	$allrows[$k]['expected_glicko2'] = $elo->calcExpected($bot['rating_glicko2'],
									$partylist[ $rs['vs_id'] ]['rating_glicko2'],
									$partylist[ $rs['vs_id'] ]['rd_glicko2'] );
									
	$allrows[$k]['pbindex'] = $allrows[$k]['score_pct'] - $allrows[$k]['expected'];
	$allrows[$k]['unixtimestamp'] = strtotime($rs['timestamp'])*1000;
	
	$details['stddev'] += pow(($allrows[$k]['score_pct'] - $details['score_pct'])/100.0, 2.0);
	$details['momentum'] += 3.0 * $allrows[$k]['pbindex'];
	$details['special'] += pow($allrows[$k]['pbindex']/100.0, 2.0);
	
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
$details['stddev'] = sqrt($details['stddev'] / ($bot['pairings']>0 ? $bot['pairings'] : 1)) * 100.0;
$details['special'] *= 100.0;
if ($sort!=null)
	array_multisort($sortcol, ($sort=='vs_name') ? SORT_ASC : SORT_DESC, $sortname, SORT_ASC, $allrows);


// assign data to template & display results
$template->assign('gentime', strftime('%Y-%m-%d %T %z'));
$template->assign('version', htmlspecialchars($version));
$template->assign('game', htmlspecialchars($game));
$template->assign('gametype', $gametype);
$template->assign('name', htmlspecialchars($name));
$template->assign('details', $details);
$template->assign('pairings', $allrows);

if (isset($_REQUEST['json']) && (isset($_REQUEST['json'])>0)) {
    $template->left_delimiter = '{{';
    $template->right_delimiter = '}}';
    $template->display('ratings_details_json.tpl');
} else {
    $template->display('ratings_details.tpl');
}

?>