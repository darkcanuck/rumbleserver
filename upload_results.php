<?php

require_once 'classes/common.php';
require_once 'classes/PostRequest.php';

$err->setClient(true);
ignore_user_abort(true);	// don't stop if client disconnects!


/* other servers to relay results to */
$rumbleURLS = array('http://abchome.aclsi.pt:8080/rumble/UploadedResults');
$relayGames = array('R', 'X', 'Y', 'Z');


/* check RoboRumble client version */
$params = array();
if (isset($_POST['version'])) {
	$params['version'] = trim($_POST['version']);

	switch ($params['version']) {
		
		case "1":
			/* "classic" client, can't determine exact version
			 *
			 *  Supplies the following values:
			 *  	version, game, rounds, field, user time, fname, fscore, fbulletd, fsurvival, sname, sscore, sbulletd, ssurvival
			 */
			
			// determine game type
			$gametype = new GameType($params['version'], $_POST['game'], $_POST['field'], $_POST['rounds']);
			$gametype->isValid();
			
			// set results data
			$params['user']       = $_POST['user'];
			$params['ip_addr']    = (isset($_POST['import']) ? 'import' : $_SERVER['REMOTE_ADDR']);
			$params['timestamp']  = $_POST['time'];
			$params['gametype']   = $gametype->getCode();
			$params['bot1']       = $_POST['fname'];
			$params['score1']     = $_POST['fscore'];
			$params['bulletdmg1'] = $_POST['fbulletd'];
			$params['survival1']  = $_POST['fsurvival'];
			$params['bot2']       = $_POST['sname'];
			$params['score2']     = $_POST['sscore'];
			$params['bulletdmg2'] = $_POST['sbulletd'];
			$params['survival2']  = $_POST['ssurvival'];
			
			$gametype->checkScores($params);
			break;
			
		default:
			// unsupported client
			trigger_error('Client version ' . substr($version, 0, 10) . ' is not supported by this server!', E_USER_ERROR);
			break;
	}
	
	// save results to database
	$results = new BattleResults($db);
	$botdata = $results->saveBattle($params);
	
	// return "Ok" so client can move on to next result
	echo('OK.  ' . $params['bot1'] . ' vs. ' . $params['bot2'] . ' received');
	//echo("\n" . print_r($db->queries, true));
	
	// return list of missing bots
	$countmissing = 0;
	if (isset($botdata['missing'])) {
		foreach($botdata['missing'] as $botpair) {
			echo("\n[" . str_replace(' ', '_', $botpair[0]) . "," . str_replace(' ', '_', $botpair[1]) . "]");
			$countmissing++;
			if ($countmissing > 60)
				break;
		}
	}
	
	// return number of battles
	if (isset($botdata['battles']))
		echo("\n<{$botdata['battles'][0]} {$botdata['battles'][1]}>");
	
	// relay to other rumble hosts
	if (in_array($params['gametype'], $relayGames) && ($_SERVER['REMOTE_ADDR']!='127.0.0.1') && !isset($_POST['import'])) {
		foreach ($rumbleURLS as $url) {
			echo "\n    Relaying results to $url - ";
			list($header, $content) = PostRequest($url, 'http://darkcanuck.net/rumble', $_POST, true);
			echo ((substr($content, 0, 2)=='OK') ? 'OK.' : $content);
		}
	}
} else {
	//missing version parameter
	trigger_error('Missing client version number!', E_USER_ERROR);
}

?>