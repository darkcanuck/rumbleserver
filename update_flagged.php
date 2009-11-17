<?php
/******************************************************************************
 * Update flagged battle --  Darkcanuck's Roborumble Server
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

// determine game type
$version = 1;
$game    = trim(isset($_REQUEST['game']) ? $_REQUEST['game'] : '');
$gametype = new GameType($version, $game);

// check bot name
$party = new Participants($db, $gametype->getCode());
$name = trim(isset($_REQUEST['name']) ? $_REQUEST['name'] : '');
$bot = $party->getByName($name, true);
$retired = $party->isRetired();

// check vs name
$vs_name = trim(isset($_REQUEST['vs']) ? $_REQUEST['vs'] : '');
$vs  = $party->getByName($vs_name, true);
$vs_retired = $party->isRetired();

// update pairing scores
$db->query('START TRANSACTION');
$pairings = new GamePairings($db, $gametype->getCode());
if ($pairings->recalcScores($bot['bot_id'], $vs['bot_id'])) {
    
    // remove battle from active listings
    $timestamp = trim(isset($_REQUEST['timestamp']) ? $_REQUEST['timestamp'] : '');
    $millisecs = trim(isset($_REQUEST['millisecs']) ? $_REQUEST['millisecs'] : '');
    $results = new BattleResults($db);
    if ($results->updateBattle($gametype->getCode(), $bot['bot_id'], $vs['bot_id'], $timestamp, $millisecs, STATE_REMOVED))
        $msg = "Pairing $name vs $vs_name recalculated; flagged battle removed";
    else
        $msg = "Pairing $name vs $vs_name recalculated; Error removing flagged battle!";    
    //print_r($db->debug());
} else {
    $msg = "Error updating pairing $name vs $vs_name !";
}
$db->query('COMMIT');


// send redirection header
header("Location: ViewFlagged?message=$msg");

?>