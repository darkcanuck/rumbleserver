<?php

require_once 'classes/common.php';

$err->setClient(true);
ignore_user_abort(true);	// don't stop if client disconnects!

if ($properties->get('disable_remove'))
    trigger_error('Function temporarily disabled.  Please try again later.', E_USER_ERROR);

//if (!isset($_POST['user']) || ($_POST['user']!='darkcanuck'))
//    trigger_error('Function temporarily disabled!  ' . substr($_POST['name'], 0, 70), E_USER_ERROR);
//$debug_user = true;


/* check RoboRumble client version */
if (isset($_POST['version'])) {
	$version = trim($_POST['version']);

	switch ($version) {
		
		case "1":
			/* "classic" client, can't determine exact version
			 *
			 *  Supplies the following values:
			 *  	version, game, name, dummy
			 */
			
			// determine game type
			$gametype = new GameType($version, trim($_POST['game']), '', '');
			
			// check bot name
			if (!isset($_POST['name']) || empty($_POST['name']))
				trigger_error('No robot specified for removal!', E_USER_ERROR);
			$name = trim(isset($_POST['name']) ? $_POST['name'] : '');
			
			// bot name is from ratings file -- space between name+version replaced by underscore
			$pos = strrpos($name, '_');
			if ($pos!==false)
				$name[$pos] = ' ';
			
			// remove specified bot
			$party = new Participants($db, $gametype->getCode());
			if ($party->retireParticipant($name))
				die('OK.  Removed bot ' . substr($name, 0, 70));
			else
				trigger_error('Failed to remove ' . substr($name, 0, 70), E_USER_ERROR);
			break;
			
		default:
			// unsupported client
			trigger_error('Client version ' . substr($version, 0, 10) . ' is not supported by this server!', E_USER_ERROR);
			break;
	}
	
	// debugging
	if (($_SERVER['REMOTE_ADDR']=='127.0.0.1') || $debug_user)
		echo str_replace(array('[',']','<','>'), '|', $db->debug());
	
} else {
	//missing version parameter
	trigger_error('Missing client version number!', E_USER_ERROR);
}

?>