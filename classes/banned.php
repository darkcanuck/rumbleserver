<?php

$check_user = (isset($_REQUEST['user'])) ? $_REQUEST['user'] : '';
$check_ip   = (isset($_SERVER['REMOTE_ADDR'])) ? $_SERVER['REMOTE_ADDR'] : '';

/*
if ( ($check_user=='Darkstorm') || ($check_ip=='94.36.199.75') )
    trigger_error("Account '$check_user' has been suspended!
    Please check your client configuration and the RoboWiki for current issues.
    Post a message on the wiki once you have fixed the problem.", E_USER_ERROR);
*/

?>