<?php
/******************************************************************************
 * Flagged Battles View --  Darkcanuck's Roborumble Server
 *
 * Copyright 2008-2011 Jerome Lavigne (jerome@darkcanuck.net)
 * Released under GPL version 3.0 http://www.gnu.org/licenses/gpl-3.0.html
 *****************************************************************************/

require_once 'classes/common.php';

require_once 'Smarty/Smarty.class.php';
$template = new Smarty();

// get battle results for pairing
$battles = new BattleResults($db);
$allrows = $battles->getBattlesByState(STATE_FLAGGED);

// calculate scores
foreach ($allrows as $k=>$rs) {
	$allrows[$k]['score_pct'] = $rs['bot_score'] / ($rs['bot_score'] + $rs['vs_score']) * 100;
	$allrows[$k]['score_survival'] = ($rs['bot_survival']+$rs['vs_survival'] > 0) ?
                                    $rs['bot_survival'] / ($rs['bot_survival'] + $rs['vs_survival']) * 100 : 50.0;
}

// assign data to template & display results
$template->assign('gentime', strftime('%Y-%m-%d %T %z'));
$template->assign('battles', $allrows);
if (isset($_REQUEST['message']))
    $template->assign('message', $_REQUEST['message']);

if (isset($_REQUEST['textonly']))
    $template->display('text_flagged.tpl');
else
    $template->display('view_flagged.tpl');

?>