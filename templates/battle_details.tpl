{* Smarty Template *}
{include file="header.tpl" title="darkcanuck.net - RoboRumble Rankings"}


<h1>BATTLE DETAILS FOR "{$name}" VS "{$vs_name}" IN GAME "{$game}"</h1>

<table id="battledata" class="rankings">
  <thead>
    <tr>
      <th rowspan="2">% Score</th>
      <th rowspan="2">% Survival</th>
      <th>score</th>
      <th>bullet dmg.</th>
      <th>survival</th>
      <th>score</th>
      <th>bullet dmg.</th>
      <th>survival</th>
      <th rowspan="2">Battle Time</th>
      <th rowspan="2">Submitted by</th>
    </tr>
    <tr>
      <th colspan='3'><img src="flags/{$package}.gif" alt="Flag for {$package}" /> {$name}</th>
      <th colspan='3'><img src="flags/{$vs_package}.gif" alt="Flag for {$vs_package}" /> {$vs_name}</th>
    </tr>
  </thead>
  <tbody>
  {foreach from=$battles key=id item=b}
  <tr>
    <td{if $b.score_pct gt 60} class="highScore"{elseif $b.score_pct lt 40} class="lowScore"{/if}>{$b.score_pct|string_format:"%.3f"}</td>
    <td{if $b.score_survival gt 60} class="highScore"{elseif $b.score_survival lt 40} class="lowScore"{/if}>{$b.score_survival|string_format:"%.3f"}</td>
    <td>{$b.bot_score}</td>
  	<td>{$b.bot_bulletdmg}</td>
  	<td>{$b.bot_survival}</td>
  	<td>{$b.vs_score}</td>
  	<td>{$b.vs_bulletdmg}</td>
  	<td>{$b.vs_survival}</td>
  	<td>{$b.timestamp}:{$b.millisecs}</td>
  	<td>{$b.user}@{$b.ip_addr} ver.{$b.version}</td>
  </tr>
  {/foreach}
  </tbody>
</table>

{include file="footer.tpl"}