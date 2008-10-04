<?php

// error handling
error_reporting(E_ALL);		// applies for errors not caught by error handler
require_once 'classes/ErrorHandler.php';
$err = new ErrorHandler();


// config includes
require_once 'config/config.php';


// class includes
require_once 'classes/MySQL.php';
require_once 'classes/BattleResults.php';
require_once 'classes/BotData.php';
require_once 'classes/GamePairings.php';
require_once 'classes/GameType.php';
require_once 'classes/Participants.php';
require_once 'classes/RankingsUpdate.php';
require_once 'classes/ServerProperties.php';
require_once 'classes/UploadUsers.php';
//require_once 'classes/PostRequest.php';	// not needed by all scripts


// database 'state' field values
define('STATE_NEW',		'0');	// battle result needs to be processed
define('STATE_OK',      '1');	// done pairing processing
define('STATE_RATED',   '2');	// done ratings processing
define('STATE_LOCKED',  'L');	// locked for ratings processing
define('STATE_RETIRED', 'R');	// retired; in results table, requires rebuild if re-activated
define('STATE_REMOVED', 'X');	// possibly bad result, needs rebuild if re-activated


// connect to database
$db = new DBlite_MySQL($db_creds);


// check if server in maintenance mode
$properties = new ServerProperties($db);
if ($properties->get('maintenance'))
	trigger_error('Sorry, the server is down for maintenance. Please try again later.', E_USER_ERROR);

// check if scoring update needed
$update_interval = $properties->getInt('update_interval', 60);
$update_time = strtotime($properties->get('update_last')) + $update_interval;
if ( isset($_GET['forceupdate']) || (($update_interval>0) && ($update_time < time())) ) {
	$properties->set('update_last', strftime("%Y-%m-%d %T"));	// update time now so other processes don't try to update
	$rankings = new RankingsUpdate($db);
	$rankings->updateScores($properties->getInt('update_size', 100), $properties->getInt('update_pairingdelay', -1));
}

?>