{include file="header.tpl" title="darkcanuck.net - Flagged Battles"}

{if isset($message)}
<p>{$message}</p>
{/if}

<h1>FLAGGED BATTLES</h1>

<table id="flagged" class="rankings">
  <thead>
    <tr>
      <th>Game</th>
      <th>1st Bot</th>
      <th>2nd Bot</th>
      <th>% Score</th>
      <th>% Survival</th>
      <!-- ><th>Scores</th>
      <th>Bullet Dmg.</th>
      <th>Survival</th> -->
      <th>Battle Time</th>
      <th>Submitted by</th>
      <th>Actions</th>
    </tr>
  </thead>
  <tbody>
  {foreach from=$battles key=id item=b}
  <tr>
    <td>{$b.gametype}</td>
    <td><a href="ViewPairing?game={$b.gametype}&amp;name={$b.bot_name}&amp;vs={$b.vs_name}" title="View this pairing">{$b.bot_name}</a></td>
    <td><a href="ViewPairing?game={$b.gametype}&amp;name={$b.vs_name}&amp;vs={$b.bot_name}" title="View this pairing">{$b.vs_name}</a></td>
    <td{if $b.score_pct gt 60} class="highScore"{elseif $b.score_pct lt 40} class="lowScore"{/if}>{$b.score_pct|string_format:"%.1f"}</td>
    <td{if $b.score_survival gt 60} class="highScore"{elseif $b.score_survival lt 40} class="lowScore"{/if}>{$b.score_survival|string_format:"%.1f"}</td>
    <!-- ><td>{$b.bot_score} / {$b.vs_score}</td>
  	<td>{$b.bot_bulletdmg} / {$b.vs_bulletdmg}</td>
  	<td>{$b.bot_survival} / {$b.vs_survival}</td> -->
  	<td>{$b.timestamp}:{$b.millisecs}</td>
  	<td>{$b.user}@{$b.ip_addr} ver.{$b.version}</td>
  	<td><a href="RemoveFlagged?game={$b.gametype}&amp;name={$b.bot_name}&amp;vs={$b.vs_name}&amp;timestamp={$b.timestamp}&amp;millisecs={$b.millisecs}" title="Remove this battle">REMOVE</a></td>
  </tr>
  {/foreach}
  </tbody>
</table>

{include file="footer.tpl"}