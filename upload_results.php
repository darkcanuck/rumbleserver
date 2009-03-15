<?php
/******************************************************************************
 * Upload Results client request  --  Darkcanuck's Roborumble Server
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
require_once 'classes/PostRequest.php';

$err->setClient(true);
ignore_user_abort(true);	// don't stop if client disconnects!

$debug_user = false;
if (isset($_POST['user'])) {
    $debug_user = (strpos($_POST['user'], '-debug')!==false);
    $_POST['user'] = str_replace('-debug', '', $_POST['user']);
}

if (!$debug_user && $properties->get('disable_upload'))
    trigger_error('Function temporarily disabled.  Please try again later.', E_USER_ERROR);

/* other servers to relay results to */
//$rumbleURLS = array('http://abchome.aclsi.pt:8080/rumble/UploadedResults');
//$relayGames = array('R', 'X', 'Y', 'Z');


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
			
			// filter out bad data
			$bad_bots = array('jk.mega.DrussGT 1.2.7',
			                  'nat.nano.OcnirpSNG 1.1 4', 'nat.nano.OcnirpSNG_1.1 4', 'nat.nano.OcnirpSNG 1.1_4',
			                  'nat.nano.OcnirpSNG 1.1 3', 'nat.nano.OcnirpSNG_1.1 3', 'nat.nano.OcnirpSNG 1.1_3',
			                  'nat.nano.OcnirpSNG 1.1 2', 'nat.nano.OcnirpSNG_1.1 2', 'nat.nano.OcnirpSNG 1.1_2',
			                  'nat.Carola 0.3',
			                  'nat.kitty.NanoKitty 0.5',
			                  'elvbot.ElverionBot 0.2'
			                  );
			foreach($bad_bots as $bb) {
			  if (($params['bot1']==$bb) || ($params['bot2']==$bb))
			    trigger_error('Stop uploading results for ' . $bb . '!  Check your client configuration. (Duplicate)', E_USER_ERROR);
			}
			$gametype->checkScores($params);
			break;
			
		default:
			// unsupported client
			trigger_error('Client version ' . substr($version, 0, 10) . ' is not supported by this server!', E_USER_ERROR);
			break;
	}
	
	// save results to database
	//set_time_limit(600);
	$results = new BattleResults($db);
	$botdata = $results->saveBattle($params);
	
	// return "Ok" so client can move on to next result
	echo('OK.  ' . $params['bot1'] . ' vs. ' . $params['bot2'] . ' received');
	//echo("\n" . print_r($db->queries, true));
	
	if(isset($_POST['import']) && ($_POST['import'] > 0)) {
    	exit(0);
    }
	
	// return list of missing pairings
	$countmissing = 0;
	if (isset($botdata['missing'])) {
		foreach($botdata['missing'] as $botpair) {
			echo("\n[" . str_replace(' ', '_', $botpair[0]) . "," . str_replace(' ', '_', $botpair[1]) . "]");
			$countmissing++;
			if ($countmissing > 60)
				break;
		}
	}
	sleep(1);
	// return number of battles
	if (isset($botdata['battles']))
		echo("\n<{$botdata['battles'][0]} {$botdata['battles'][1]}>");
	
	// relay to other rumble hosts
	//if (in_array($params['gametype'], $relayGames) && ($_SERVER['REMOTE_ADDR']!='127.0.0.1') && !isset($_POST['import'])) {
	//	foreach ($rumbleURLS as $url) {
	//		echo "\n    Relaying results to $url - ";
	//		list($header, $content) = PostRequest($url, 'http://darkcanuck.net/rumble', $_POST, true);
	//		echo ((substr($content, 0, 2)=='OK') ? 'OK.' : $content);
	//	}
	//}
	
	// debugging
	//if (($_SERVER['REMOTE_ADDR']=='127.0.0.1') || $debug_user)
	//	echo str_replace(array('[',']','<','>'), '|', $db->debug());
    
} else {
	//missing version parameter
	trigger_error('Missing client version number!', E_USER_ERROR);
}

?>