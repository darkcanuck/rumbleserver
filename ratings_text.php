<?php

require_once 'classes/common.php';


$err->setClient(true);
$err->setNoOutput(true);

/* check RoboRumble client version */
if (isset($_GET['version'])) {
	$version = trim($_GET['version']);

	switch ($version) {
		
		case "1":
			/* "classic" client, can't determine exact version
			 *
			 *  Supplies the following values:
			 *  	version, game
			 */
			
			// determine game type
			$gametype = new GameType($version, trim(isset($_GET['game']) ? $_GET['game'] : ''), '', '');
			$party = new Participants($db, $gametype->getCode());
			foreach($party->getList() as $bot)
				echo str_replace(' ', '_', $bot['name']) . '=' . $bot['rating_glicko']/1000 . ',' . $bot['battles'] . ',' . $bot['timestamp'] . "\n";
			
			break;
			
		default:
			// unsupported client
			trigger_error('Client version ' . substr($params['version'], 0, 10) . ' is not supported by this server!', E_USER_ERROR);
			break;
	}
	
} else {
	//missing version parameter
	trigger_error('Missing client version number!', E_USER_ERROR);
}

?>