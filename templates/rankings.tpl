{* $Id$ *}
{include file="header.tpl" title="darkcanuck.net - RoboRumble Rankings"}


<h1>Current rankings for game "{$game}"</h1>

<p>{$pagemessage}</p>
<p>Generation time: {$gentime}</p>

<table id="rankingdata" class="rankings">
  <thead><tr>
    <th>Rank</th>
    <th>Competitor</th>
    {if $survival}
    <th>Survival</th>
    <th>APS</th>
    {else}
    <th>APS</th>
    <th>Survival</th>
    {/if}
    <th>ELO Rating</th>
    <th>Glicko-2 (RD)</th>
    <!-- <th>Details</th> -->
    <th>Battles</th>
    <th>Pairings</th>
    <th>PL Score</th>
    <th>Last Update</th>
  </tr></thead>
  
  <tbody>
  {foreach from=$rankings key=id item=bot}
  <tr>
    <td>{$bot.rank}</td>
    <td><img src="flags/{$bot.package}.gif" alt="Flag for {$bot.package}" />
        <a href="RatingsDetails?game={$game}&amp;name={$bot.name|escape}" title="Details for {$bot.name}">{$bot.name}</a></td>
    <td>{$bot.score_pct|string_format:"%.2f"}</td>
    <td>{$bot.score_survival|string_format:"%.2f"}</td>
    <td>{$bot.rating_classic|string_format:"%.1f"}</td>
    <td>{$bot.rating_glicko2|string_format:"%.1f"} ({$bot.rd_glicko2|string_format:"%.0f"})</td>
    <!-- <td><a href="RatingsDetails?game={$game}&amp;name={$bot.name|escape}">details</a> / 
        <a href="RatingsLRP?game={$game}&amp;name={$bot.name|escape}">LRP</a></td> -->
    <td>{$bot.battles}</td>
    <td>{$bot.pairings}</td>
    <td>{$bot.score_pl}</td>
    <td>{$bot.timestamp}</td>
  </tr>
  {/foreach}
  </tbody>
</table>

<p><b>Total battles = {$totalbattles}</b></p>

{include file="footer.tpl"}