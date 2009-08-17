{* $Id$ *}
{include file="header.tpl" title="darkcanuck.net - Comparing $name vs $vs_name ($game)"}


<h1>COMPARING "{$name}" VS "{$vs_name}" IN GAME "{$game}"</h1>

<h2></h2>
<table id="comparedetails" class="rankings">
  <thead>
    <tr><th></th>
        <th><img src="flags/{$details.package}.gif" alt="Flag for {$details.package}" />{$name} {$details.state}</th>
        <th><img src="flags/{$vs_details.package}.gif" alt="Flag for {$vs_details.package}" />{$vs_name} {$vs_details.state}</th></tr>
  </thead>
  <tbody>
	<tr><td>"Classic" Elo Rating</td>
	    <td>{$details.rating_classic|string_format:"%.1f"}</td>
	    <td>{$vs_details.rating_classic|string_format:"%.1f"}</td></tr>
	<tr><td>Glicko-2 (RD, volatility)</td>
	    <td>{$details.rating_glicko2|string_format:"%.1f"}
		    ({$details.rd_glicko2|string_format:"%.0f"}, {$details.vol_glicko2|string_format:"%.3f"})</td>
		<td>{$vs_details.rating_glicko2|string_format:"%.1f"}
    		    ({$vs_details.rd_glicko2|string_format:"%.0f"}, {$vs_details.vol_glicko2|string_format:"%.3f"})</td></tr>
	<tr><td>{if $survival}Average % Survival{else}Average % Score (APS){/if}</td>
	    <td>{$details.score_pct|string_format:"%.3f"} %</td>
	    <td>{$vs_details.score_pct|string_format:"%.3f"} %</td></tr>
	<tr><td>{if $survival}Average % Score{else}Average % Survival{/if}</td>
	    <td>{$details.score_survival|string_format:"%.3f"} %</td>
	    <td>{$vs_details.score_survival|string_format:"%.3f"} %</td></tr>
	<tr><td># Battles</td>
	    <td>{$details.battles}</td>
	    <td>{$vs_details.battles}</td></tr>
	<tr><td># Pairings</td>
	    <td>{$details.pairings}</td>
	    <td>{$vs_details.pairings}</td></tr>
	<tr><td># Pairs Won</td>
	    <td>{$details.count_wins} ({$details.percent_wins|string_format:"%.1f"}%)</td>
	    <td>{$vs_details.count_wins} ({$vs_details.percent_wins|string_format:"%.1f"}%)</td></tr>
	<tr><td>Added to Rumble</td>
	    <td>{$details.created}</td>
	    <td>{$vs_details.created}</td></tr>
	<tr><td>Last Battle</td>
	    <td>{$details.timestamp}</td>
	    <td>{$vs_details.timestamp}</td></tr>
	<tr><td>{if $survival}Common % Survival{else}Common % Score (APS){/if}</td>
        <td>{$details.avg_score|string_format:"%.3f"} %</td>
        <td>{$vs_details.avg_score|string_format:"%.3f"} %</td></tr>
	<tr><td>{if $survival}Common % Score{else}Common % Survival{/if}</td>
	    <td>{$details.avg_survival|string_format:"%.3f"} %</td>
	    <td>{$vs_details.avg_survival|string_format:"%.3f"} %</td></tr>
  </tbody>
</table>

<table id="comparedata" class="rankings">
  <thead>
    <tr>
      <th rowspan="2">Enemy</th>
      {if $survival}<th>% Survival</th>{else}{/if}
      <th>% Score</th>
      <th>% Survival</th>
      <th>% Score</th>
      <th>% Survival</th>
      <th>% Score</th>
      {if $survival}{else}<th>% Survival</th>{/if}
    </tr>
    <tr>
      <th colspan='2'><img src="flags/{$details.package}.gif" alt="Flag for {$details.package}" /> {$name}</th>
      <th colspan='2'><img src="flags/{$vs_details.package}.gif" alt="Flag for {$vs_details.package}" /> {$vs_name}</th>
      <th colspan='2'>+/- Difference</th>
    </tr>
  </thead>
  <tbody>
  {foreach from=$pairings key=id item=bot}
  <tr>
    <td><img src="flags/{$bot.package}.gif" alt="Flag for {$bot.package}" />
        <a href="RatingsDetails?game={$game}&amp;name={$bot.vs_name|escape}" title="Details for {$bot.vs_name}">{$bot.vs_name}</a></td>
    <td{if $bot.score_pct gt 60} class="highScore"{elseif $bot.score_pct lt 40 and $bot.score_pct neq '--'} class="lowScore"{/if}>
        {if $bot.score_pct neq '--'}{$bot.score_pct|string_format:"%.2f"}{/if}</td>
    <td{if $bot.score_survival gt 60} class="highScore"{elseif $bot.score_survival lt 40 and $bot.score_survival neq '--'} class="lowScore"{/if}>
        {if $bot.score_survival neq '--'}{$bot.score_survival|string_format:"%.2f"}{/if}</td>
    <td{if $bot.vs_pct gt 60} class="highScore"{elseif $bot.vs_pct lt 40 and $bot.vs_pct neq '--'} class="lowScore"{/if}>
        {if $bot.vs_pct neq '--'}{$bot.vs_pct|string_format:"%.2f"}{/if}</td>
    <td{if $bot.vs_survival gt 60} class="highScore"{elseif $bot.vs_survival lt 40 and $bot.vs_survival neq '--'} class="lowScore"{/if}>
        {if $bot.vs_survival neq '--'}{$bot.vs_survival|string_format:"%.2f"}{/if}</td>
    <td{if $bot.diff_pct gt 0} class="highScore"{elseif $bot.diff_pct lt 0} class="lowScore"{/if}>
        {if is_numeric($bot.diff_pct)}{$bot.diff_pct|string_format:"%.2f"}{/if}</td>
    <td{if $bot.diff_survival gt 0} class="highScore"{elseif $bot.diff_survival lt 0} class="lowScore"{/if}>
        {if is_numeric($bot.diff_survival)}{$bot.diff_survival|string_format:"%.2f"}{/if}</td>
  </tr>
  {/foreach}
  </tbody>
</table>

{include file="footer.tpl"}