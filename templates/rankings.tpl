{* Smarty Template *}
{include file="header.tpl" title="darkcanuck.net - RoboRumble Rankings"}


<h1>Current rankings for game "{$game}"</h1>
<p>Results from Darkcanuck's experimental new RoboRumble server.  Stable results can be found from the RoboCode wiki.</p>
<p>Generation time: {$gentime}</p>

<table id="rankingdata" class="rankings">
  <thead><tr>
    <th>Rank</th>
    <th>Competitor</th>
    <th>APS</th>
    <th>ELO Rating</th>
    <th>G-Rating (RD)</th>
    <th>Glicko-2 (RD)</th>
    <th>Details</th>
    <th>Battles</th>
    <th>Pairings</th>
    <th>PL Score</th>
    <th>Last Update</th>
  </tr></thead>
  
  <tbody>
  {foreach from=$rankings key=id item=bot}
  <tr>
    <td>{$bot.rank}</td>
    <td><img src="flags/{$bot.package}.gif" title="Flag for {$bot.package}" />
        {$bot.name}</td>
    <td>{$bot.score_pct|string_format:"%.2f"}</td>
    <td>{$bot.rating_classic|string_format:"%.1f"}</td>
    <td>{$bot.rating_glicko|string_format:"%.1f"} ({$bot.rd_glicko|string_format:"%.0f"})</td>
    <td>{$bot.rating_glicko2|string_format:"%.1f"} ({$bot.rd_glicko2|string_format:"%.0f"})</td>
    <td><a href="RatingsDetails?game={$game}&name={$bot.name|escape}">details</a> / 
        <a href="RatingsLRP?game={$game}&name={$bot.name|escape}">LRP</a></td>
    <td>{$bot.battles}</td>
    <td>{$bot.pairings}</td>
    <td>{$bot.score_pl}</td>
    <td>{$bot.timestamp}</td>
  </tr>
  {/foreach}
  </tbody>
</table>

<p><b>Total battles = {$totalbattles}</b></p>
<p><em>G-Ratings</em> are calculated using the the <a href="http://math.bu.edu/people/mg/glicko/glicko.doc/glicko.html">Glicko rating system</a>
by Mark E. Glickman.  Default ratings for new competitors are set at 1500 with a ratings deviation (RD) of 350.</p>

{include file="footer.tpl"}