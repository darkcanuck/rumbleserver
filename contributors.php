<?php

require_once 'classes/common.php';

require_once 'Smarty/Smarty.class.php';
$template = new Smarty();


$users = new UploadUsers($db);
$userlist = $users->getContributors();
$total = 0;
foreach ($userlist as $u)
    $total += $u['battles'];
    
$dateinfo = getdate();

$gameinfo = array(
                'rumble' => array('gametype' => 'R', 'title' => 'RoboRumble'),
                'melee' => array('gametype' => 'M', 'title' => 'MeleeRumble'),
                'team' => array('gametype' => 'T', 'title' => 'TeamRumble')
                );

$monthly = array();
foreach($gameinfo as $name => $game) {
    $monthly[$name]['title'] = $game['title'];
    $monthly[$name]['gametype'] = $game['gametype'];
    $monthly[$name]['data'] = $users->statsMonthly($game['gametype'], $dateinfo['year'], $dateinfo['mon']);
    if (($name=='melee') && ($monthly[$name]['data']!=null)) {
        foreach($monthly[$name]['data'] as $key => $data)
            $monthly[$name]['data'][$key]['battles'] = floor($data['battles'] / 45.0);
    }        
}

$last30 = $monthly;
foreach($gameinfo as $name => $game) {
    $last30[$name]['data'] = $users->statsLast30($game['gametype']);
    if (($name=='melee') && ($monthly[$name]['data']!=null)) {
        foreach($last30[$name]['data'] as $key => $data)
            $last30[$name]['data'][$key]['battles'] = floor($data['battles'] / 45.0);
    }
}

// assign data to template & display results
$template->assign('gentime', strftime('%Y-%m-%d %T %z'));
$template->assign('userdata', $userlist);
$template->assign('dateinfo', $dateinfo);
$template->assign('monthly', $monthly);
$template->assign('last30', $last30);
$template->assign('totalbattles', $total);

$template->display('contributors.tpl');

?>