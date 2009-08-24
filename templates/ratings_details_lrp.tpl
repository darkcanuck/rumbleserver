{* $Id$ *}
{include file="header.tpl" title="darkcanuck.net - Rating Details for $name ($game)" jscript="js/lrp2.js" jsdata="1"}


<h1>RATING DETAILS FOR "{$name}" IN GAME "{$game}"</h1>

<h2><img src="flags/{$details.package}.gif" alt="Flag for {$details.package}" />
    {$name} {$details.state}</h2>
<table id="ratingdetails" class="rankings">
  <tbody>
	<tr><td>"Classic" Elo Rating</td>
	    <td>{$details.rating_classic|string_format:"%.1f"}</td></tr>
	<tr><td>Glicko-2 (RD, volatility)</td>
	    <td>{$details.rating_glicko2|string_format:"%.1f"}
		    ({$details.rd_glicko2|string_format:"%.0f"}, {$details.vol_glicko2|string_format:"%.3f"})</td></tr>
	<tr><td>Rating Momentum</td>
	    <td>{$details.momentum|string_format:"%.1f"}</td></tr>
	<tr><td>Specialization</td>
	    <td>{$details.special|string_format:"%.3f"}</td></tr>
	<tr><td>{if $survival}Average % Survival{else}Average % Score (APS){/if}</td>
	    <td>{$details.score_pct|string_format:"%.3f"} %</td></tr>
	<tr><td>Standard Deviation</td>
	    <td>{$details.stddev|string_format:"%.3f"}</td></tr>
	<tr><td>{if $survival}Average % Score{else}Average % Survival{/if}</td>
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

{if isset($versions)}
<table id="oldversions" class="rankings">
  <thead>
    <tr><th colspan="3">Other versions</th></tr>
  </thead>
  <tbody>
    {foreach from=$versions key=id item=bot}
    <tr><td>{$bot.timestamp}</td>
        <td><a href="RatingsDetails?game={$game}&amp;name={$bot.full_name|escape}" title="Details for {$bot.full_name}">{$bot.full_name}</a></td>
        <td><a href="RatingsCompare?game={$game}&amp;name={$name|escape}&amp;vs={$bot.full_name|escape}" title="Compare with {$bot.full_name}">compare</a></td>
    </tr>
    {/foreach}
  </tbody>
</table>
{/if}

<div id="lrp">
    <div id="info"></div>
    <div id="graph" style="width:90%;height:300px"></div>
    <div id="legend"></div>
    <div id="debug"></div>
</div>

<table id="pairingdata" class="rankings">
  <thead><tr>
    <th>Enemy</th>
    {if $survival}
    <th>Survival</th>
    <th>% Score</th>
    {else}
    <th>% Score</th>
    <th>Survival</th>
    {/if}
    <th title="ELO Rating">Rating</th>
    <th>Battles</th>
    <th>Last Battle</th>
    <th>Details</th>
    <th>Expected %</th>
    <th title="Problem Bot Index">PBI</th>
  </tr></thead>
  
  <tbody>
  {foreach from=$pairings key=id item=bot}
  <tr>
    <td><img src="flags/{$bot.package}.gif" alt="Flag for {$bot.package}" />
        <a href="RatingsDetails?game={$game}&amp;name={$bot.vs_name|escape}" title="Details for {$bot.vs_name}">{$bot.vs_name}</a></td>
    <td{if $bot.score_pct gt 60} class="highScore"{elseif $bot.score_pct lt 40} class="lowScore"{/if}>{$bot.score_pct|string_format:"%.2f"}</td>
    <td{if $bot.score_survival gt 60} class="highScore"{elseif $bot.score_survival lt 40} class="lowScore"{/if}>{$bot.score_survival|string_format:"%.2f"}</td>
    <td>{$bot.rating_classic|string_format:"%.1f"}</td>
    <td>{$bot.battles}</td>
    <td>{$bot.timestamp}</td>
    <td><a href="BattleDetails?game={$game}&amp;name={$name|escape}&amp;vs={$bot.vs_name|escape}" title="View battle details">battles</a> /
            <a href="RatingsCompare?game={$game}&amp;name={$name|escape}&amp;vs={$bot.vs_name|escape}" title="Compare with {$bot.vs_name}">compare</a></td>
    <td>{$bot.expected|string_format:"%.1f"}</td>
    <td{if $bot.pbindex gt 10} class="highPBI"{elseif $bot.pbindex lt -10} class="lowPBI"{/if}>{$bot.pbindex|string_format:"%.1f"}</td>
  </tr>
  {/foreach}
  </tbody>
</table>

{include file="footer.tpl"}