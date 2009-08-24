{* $Id$ *}
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
	<title>{$title|default:"darkcanuck.net - RoboRumble"}</title>
	
	<meta name="ROBOTS" content="{$robots|default:"NOINDEX, NOFOLLOW, NOARCHIVE"}" />
	
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<meta http-equiv="Content-Style-Type" content="text/css" />
	<link href="css/styles.css" rel="stylesheet" type="text/css" media="all" />
	
    {if isset($jsdata)}<script language="javascript">
        var data = new Array();
        data.name = "{$name}";
        data.game = "{$game}";
        data.rating = {$details.rating_classic};
        data.numBattles = {$details.battles};
        data.lastBattle = {$details.unixtimestamp};
        data.specializationIndex = {$details.special};
        data.momentum = {$details.momentum};
        data.APS = {$details.percent_score};
        data.pairings = new Array(
            {foreach from=$pairings key=id item=bot name=botjs}
            {ldelim} "name": "{$bot.vs_name}", "ranking": {$bot.rating_classic}, "score": {$bot.score_pct}, "numBattles": {$bot.battles}, "lastBattle": {$bot.unixtimestamp}, "expectedScore": {$bot.expected}, "PBI": {$bot.pbindex} {rdelim}{if !$smarty.foreach.botjs.last},{/if}
            
            {/foreach});
	</script>{/if}
	
	<script language="javascript" type="text/javascript" src="js/jquery.js"></script>
	<script language="javascript" type="text/javascript" src="js/jquery.tablesorter.min.js"></script>
	<script language="javascript" type="text/javascript" src="js/jquery.flot.js"></script>
	<script language="javascript" type="text/javascript" src="js/rankings.js"></script>
	{if isset($jscript)}
	<script language="javascript" type="text/javascript" src="{$jscript}"></script>
	{/if}
	
</head>

<body>

<div id="header">
  <ul id="navigation">
    <li><a href="index.html" title="Home">Home</a></li>
    {if $game=="roborumble" || $game=="minirumble" || $game=='microrumble' || $game=='nanorumble' }
    <li><span class="subnav">1v1:</span>
      <a class="subnav" href="Rankings?game=roborumble" title="Rankings - 1v1 (General)">General</a>
      <a class="subnav" href="Rankings?game=minirumble" title="Rankings - 1v1 (Mini)">Mini</a>
      <a class="subnav" href="Rankings?game=microrumble" title="Rankings - 1v1 (Micro)">Micro</a>
      <a class="subnav" href="Rankings?game=nanorumble" title="Rankings - 1v1 (Nano)">Nano</a>
    </li>
    {else}
    <li><a href="Rankings?game=roborumble" title="Rankings - 1v1 (General)">1v1 Rankings</a></li>
    {/if}
    {if $game=="meleerumble" || $game=="minimeleerumble" || $game=='micromeleerumble' || $game=='nanomeleerumble' }
    <li><span class="subnav">Melee:</span>
      <a class="subnav" href="Rankings?game=meleerumble" title="Rankings - Melee (General)">General</a>
      <a class="subnav" href="Rankings?game=minimeleerumble" title="Rankings - Melee (Mini)">Mini</a>
      <a class="subnav" href="Rankings?game=micromeleerumble" title="Rankings - Melee (Micro)">Micro</a>
      <a class="subnav" href="Rankings?game=nanomeleerumble" title="Rankings - Melee (Nano)">Nano</a>
    </li>
    {else}
    <li><a href="Rankings?game=meleerumble" title="Rankings - Melee (General)">Melee Rankings</a></li>
    {/if}
    <li><a href="Rankings?game=teamrumble" title="Rankings - Teams (General)">Team Rankings</a></li>
    <li><a href="Rankings?game=twinduel" title="Rankings - Twin Duel">Twin Duel</a></li>
    <li><a href="Contributors" title="Contributors">Contributors</a></li>
  </ul>
</div>

<div id="content">
    