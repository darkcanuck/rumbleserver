{* Smarty Template *}
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
	<title>darkcanuck.net - RoboRumble Rankings</title>
	
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	
	<meta http-equiv="Content-Style-Type" content="text/css" />
	<link href="css/styles.css" rel="stylesheet" type="text/css" media="all" />
	
	<script language="javascript" type="text/javascript" src="js/jquery.js"></script>
	<script language="javascript" type="text/javascript" src="js/jquery.tablesorter.min.js"></script>
    <script language="javascript" type="text/javascript" src="js/jquery.flot.js"></script>
    <script language="javascript" type="text/javascript" src="js/lrp2.js"></script>
	<script language="javascript" type="text/javascript" src="js/rankings.js"></script>
</head>

<body>


<h1>RATING DETAILS FOR "{$name}" IN GAME "{$game}"</h1>

<h2><img src="flags/{$details.package}.gif" alt="Flag for {$details.package}" />
    {$name} (<a href="RatingsLRP?game={$game}&amp;name={$name}" title="LRP Graph">LRP</a>)</h2>
<table id="ratingdetails" class="rankings">
  <tbody>
	<tr><td>"Classic" Elo Rating</td>
	    <td>{$details.rating_classic|string_format:"%.1f"}</td></tr>
	<tr><td>Glicko Rating (RD)</td>
	    <td>{$details.rating_glicko|string_format:"%.1f"} ({$details.rd_glicko|string_format:"%.0f"})</td></tr>
	<tr><td>Glicko-2 (RD, volatility)</td>
	    <td>{$details.rating_glicko2|string_format:"%.1f"}
		    ({$details.rd_glicko2|string_format:"%.0f"}, {$details.vol_glicko2|string_format:"%.3f"})</td></tr>
	<tr><td>Rating Momentum</td>
	    <td>{$details.momentum|string_format:"%.1f"}</td></tr>
	<tr><td>Specialization</td>
	    <td>{$details.special|string_format:"%.3f"}</td></tr>
	<tr><td>Average % Score (APS)</td>
	    <td>{$details.score_pct|string_format:"%.3f"} %</td></tr>
	<tr><td>Standard Deviation</td>
	    <td>{$details.stddev|string_format:"%.3f"}</td></tr>
	<tr><td>Average % Survival</td>
	    <td>{$details.score_survival|string_format:"%.3f"} %</td></tr>
	<tr><td># Battles</td>
	    <td>{$details.battles}</td></tr>
	<tr><td># Pairings</td>
	    <td>{$details.pairings}</td></tr>
	<tr><td># Pairs Won</td>
	    <td>{$details.count_wins} ({$details.percent_wins|string_format:"%.1f"}%)</td></tr>
	<tr><td>Added to Rumble</td>
	    <td>{$details.created}</td></tr>
	<tr><td>Last Battle</td>
	    <td>{$details.timestamp}</td></tr>
  </tbody>
</table>

<div id="lrp">
    <div id="info"></div>
    <div id="graph" style="width:90%;height:300px"></div>
    <div id="legend"></div>
    <div id="debug"></div>
</div>

<table id="pairingdata" class="rankings">
  <thead><tr>
    <th>Enemy</th>
    <th>% Score</th>
    <th>Elo Rating</th>
    <th>Battles</th>
    <th>Last Battle</th>
    <th>Details</th>
    <th>Expected %</th>
    <th>ProblemBot Index</th>
  </tr></thead>
  
  <tbody>
  {foreach from=$pairings key=id item=bot}
  <tr>
    <td><img src="flags/{$bot.package}.gif" alt="Flag for {$bot.package}" />
        {$bot.vs_name}</td>
    <td{if $bot.score_pct gt 60} class="highScore"{elseif $bot.score_pct lt 40} class="lowScore"{/if}>{$bot.score_pct|string_format:"%.2f"}</td>
    <td>{$bot.rating_classic|string_format:"%.1f"}</td>
    <td>{$bot.battles}</td>
    <td>{$bot.timestamp}</td>
    <td><a href="BattleDetails?game={$game}&amp;name={$name|escape}&amp;vs={$bot.vs_name|escape}">battles</a></td>
    <td>{$bot.expected|string_format:"%.1f"}</td>
    <td{if $bot.pbindex gt 10} class="highPBI"{elseif $bot.pbindex lt -10} class="lowPBI"{/if}>{$bot.pbindex|string_format:"%.1f"}</td>
  </tr>
  {/foreach}
  </tbody>
</table>

{include file="footer.tpl"}