<?php
/******************************************************************************
 * API Query URL  --  Darkcanuck's Roborumble Server
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
require_once 'classes/ApiUsers.php';

require_once 'classes/Glicko2Rating.php';
$glicko2 = new Glicko2Rating();         // needed for glicko-2 scaling

// send JSON headers and force error handler to use JSON output
header('Cache-Control: no-cache, must-revalidate');
header('Expires: ' . strtotime(DATE_RFC1123));
header('Content-type: application/json');
$err->setJsonOutput(true);

define('API_MAXREQ_HOUR', 60);
define('API_MAXREQ_MIN' , 10);

// check for valid API user
$api_user = trim(isset($_REQUEST['user']) ? $_REQUEST['user'] : '');
$api_key  = trim(isset($_REQUEST['apikey']) ? $_REQUEST['apikey'] : '');
$userlist = new ApiUsers($db);
$user = $userlist->getUser($api_user, $api_key);

// check that user is below query threshold
$lastupdate = strtotime($user['updated']);
$time = getdate();
if ($lastupdate+3600 < time()) {    // NOTE: this does not work on my test server since the db does not use UTC!
    // no query for at least 1hr, reset stats
    $userlist->updateUser($api_user, $api_key, true);
} else {
    // check hourly counter
    if ($user['hour']==$time['hours']) {
        if ($user['hreq']>=API_MAXREQ_HOUR)
            trigger_error('Too many requests per hour (' . $user['hreq'] .') - try again later', E_USER_ERROR);
        
        // check minute counter
        if (($user['minute']==$time['minutes']) && ($user['mreq']>=API_MAXREQ_MIN))
            trigger_error('Too many requests per minute (' . $user['mreq'] .') - try again later', E_USER_ERROR);
    }
    $userlist->updateUser($api_user, $api_key);
}

// determine query type
$api_query = trim(isset($_REQUEST['query']) ? $_REQUEST['query'] : 'test');

// get common query parameters (if set)
$version = trim(isset($_REQUEST['version']) ? $_REQUEST['version'] : '1');
$game    = trim(isset($_REQUEST['game']) ? $_REQUEST['game'] : '');
$results = array(
                'query' => $api_query,
                'params' => $_REQUEST,
                'version' => $version,
                'game'  => $game,
                'data'  => null,
                'message' => ''
                );

// determine game type (if parameter set)
$gamedef = ($game=='') ? null : new GameType($version, $game);

// fetch query data
switch (strtolower($api_query)) {
    case 'rankings':
        // requires params: 'game'
        if ($gamedef==null)
            trigger_error('Missing parameter "game"', E_USER_ERROR);
        
        // optional params: 'order'
        $order = strtolower(trim(isset($_REQUEST['order']) ? $_REQUEST['order'] : 'score_pct'));
        
        // get rankings for specified game
        $party = new Participants($db, $gamedef->getCode(), $order);
        $rankings = $party->getList();
        
        if ($rankings!=null) {
            // TODO: these fields should be fixed by the Participants class, not by consumers!
            $div_fields = array('score_pct', 'score_dmg', 'score_survival', 'rating_classic', 'rating_glicko', 'rd_glicko',
                                'rating_glicko2', 'rd_glicko2', 'vol_glicko2');
            $results['data'] = array();
            $rank = 1;
            foreach($rankings as $k=>$rs) {
                foreach($div_fields as $f)
                    $rankings[$k][$f] = (float)$rs[$f] / 1000.0;
                $rankings[$k]['rating_glicko2'] = $glicko2->eloScale((float)$rankings[$k]['rating_glicko2']);
                $rankings[$k]['rank'] = $rank;
                $rank++;
                // re-assign to new array since getList() uses bot_id's as keys
                $results['data'][] = $rankings[$k];
            }
        }
        break;
    
    
    case 'participant':
        // requires params 'game', 'name'
        if ($gamedef==null)
            trigger_error('Missing parameter "game"', E_USER_ERROR);
        $name = trim(isset($_REQUEST['name']) ? $_REQUEST['name'] : '');
        if ($name=='')
            trigger_error('Missing parameter "name"', E_USER_ERROR);
        
        // get bot ranking details
        $party = new Participants($db, $gamedef->getCode());
        $participant = $party->getByName($name, true);
        
        if ($participant!=null) {
            // TODO: these fields should be fixed by the Participants class, not by consumers!
            $div_fields = array('score_pct', 'score_dmg', 'score_survival', 'rating_classic', 'rating_glicko', 'rd_glicko',
                                'rating_glicko2', 'rd_glicko2', 'vol_glicko2');
            $results['data'] = array();
            foreach($div_fields as $f)
                $participant[$f] = (float)$participant[$f] / 1000.0;   
            $participant['rating_glicko2'] = $glicko2->eloScale((float)$participant['rating_glicko2']);
            $results['data'][] = $participant;
        }
        break;
    
    case 'details':
        // requires params 'game', 'name'
        if ($gamedef==null)
            trigger_error('Missing parameter "game"', E_USER_ERROR);
        $name = trim(isset($_REQUEST['name']) ? $_REQUEST['name'] : '');
        if ($name=='')
            trigger_error('Missing parameter "name"', E_USER_ERROR);
        
        // get pairings for bot
        $bot = new BotData($name);
		$id = $bot->getID($db, false);
        $pairings = new GamePairings($db, $gamedef->getCode());
        $details = $pairings->getBotPairings($gamedef->getCode(), $id, false, true);   // get all states
        
        if ($details!=null) {
            // TODO: these fields should be fixed by the GamePairings class, not by consumers!
            $div_fields = array('score_pct', 'score_dmg', 'score_survival');
            foreach($details as $k=>$rs) {
                foreach($div_fields as $f)
                    $details[$k][$f] = (float)$rs[$f] / 1000.0;
            }
            $results['data'] = $details;
        }
        break;

    case 'pairing':
        // requires params 'game', 'name', 'vs'
        if ($gamedef==null)
            trigger_error('Missing parameter "game"', E_USER_ERROR);
        $name = trim(isset($_REQUEST['name']) ? $_REQUEST['name'] : '');
        if ($name=='')
            trigger_error('Missing parameter "name"', E_USER_ERROR);
        $vs_name = trim(isset($_REQUEST['vs']) ? $_REQUEST['vs'] : '');
        if ($vs_name=='')
            trigger_error('Missing parameter "vs"', E_USER_ERROR);

        // get pairing data for bots
        $bot = new BotData($name);
		$id = $bot->getID($db, false);
		$vs_bot = new BotData($vs_name);
		$vs = $vs_bot->getID($db, false);
        $pairings = new GamePairings($db, $gamedef->getCode());
        $pair = $pairings->getSinglePairing($gamedef->getCode(), $id, $vs);
        
        if ($pair!=null)
            $results['data'][] = $pair;
        break;
        
    case 'battles':
        // requires params 'game', 'name', 'vs'
        if ($gamedef==null)
            trigger_error('Missing parameter "game"', E_USER_ERROR);
        $name = trim(isset($_REQUEST['name']) ? $_REQUEST['name'] : '');
        if ($name=='')
            trigger_error('Missing parameter "name"', E_USER_ERROR);
        $vs_name = trim(isset($_REQUEST['vs']) ? $_REQUEST['vs'] : '');
        if ($vs_name=='')
            trigger_error('Missing parameter "vs"', E_USER_ERROR);
        
        // get battle data for bots
        $bot = new BotData($name);
		$id = $bot->getID($db, false);
		$vs_bot = new BotData($vs_name);
		$vs = $vs_bot->getID($db, false);
        $battles = new BattleResults($db);
        $results['data'] = $battles->getBattleDetails($gamedef->getCode(), $id, $vs);
        break;
    
    default:
        $results['data']    = array(1,2,3);
        $results['message'] = 'Results for API query "test"';
        $results['hint']    = 'Not what you expected? You need to send a valid "query" type';
        break;
}

// default message
if ($results['message']=='')
    $results['message'] = $api_query . ' query ok';

if (isset($_REQUEST['debug']))
    $results['debug-db'] = $db->debug();

// send json output
echo json_encode($results);

?>