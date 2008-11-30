<?php

require_once 'classes/common.php';

require_once 'Smarty/Smarty.class.php';
$template = new Smarty();


$users = new UploadUsers($db);
$userlist = $users->getContributors();
$total = 0;
foreach ($userlist as $user)
    $total += $user['battles'];
    

// assign data to template & display results
$template->assign('gentime', strftime('%Y-%m-%d %T %z'));
$template->assign('userdata', $userlist);
$template->assign('userdata', $userlist);
$template->assign('totalbattles', $total);

$template->display('contributors.tpl');

?>