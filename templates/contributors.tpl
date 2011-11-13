{include file="header.tpl" title="darkcanuck.net - RoboRumble Contributors" game="" robots="INDEX, NOFOLLOW"}


<h1>RoboRumble Contributors</h1>

<h2 class="clear">{$dateinfo.month} {$dateinfo.year}</h2>
{foreach from=$monthly key=id item=game}
<div class="inline">
    <h3>{$game.title}</h3>
    <table id="monthly{$id}" class="rankings">
      <thead><tr>
      	<th>User</th><th># Battles</th><th>Last Battle</th>
      </tr></thead>
      <tbody>
      {foreach from=$game.data key=id item=user}
      <tr>
        <td>{$user.username} ({$user.version})</td>
      	<td>{$user.battles}</td>
      	<td>{$user.updated}</td>
      </tr>
      {/foreach}
      </tbody>
    </table>
</div>
{/foreach}

<h2 class="clear">Last 30 Days</h2>
{foreach from=$last30 key=id item=game}
<div class="inline">
    <h3>{$game.title}</h3>
    <table id="last30{$id}" class="rankings">
      <thead><tr>
      	<th>User</th><th># Battles</th><th>Last Battle</th>
      </tr></thead>
      <tbody>
      {foreach from=$game.data key=id item=user}
      <tr>
        <td>{$user.username} ({$user.version})</td>
      	<td>{$user.battles}</td>
      	<td>{$user.updated}</td>
      </tr>
      {/foreach}
      </tbody>
    </table>
</div>
{/foreach}


<h2 class="clear">All Uploads</h2>
<p>(since September 2008)</p>
<table id="userdata" class="rankings">
  <thead><tr>
  	<th>User</th>
  	<th># Uploads</th>
  	<th>Last Upload</th>
  </tr></thead>
  
  <tbody>
  {foreach from=$userdata key=id item=user}
  <tr>
    <td>{$user.username}</td>
  	<td>{$user.battles}</td>
  	<td>{$user.updated}</td>
  </tr>
  {/foreach}
  </tbody>
</table>

<p><b>Total uploads = {$totalbattles}</b></p>

{include file="footer.tpl"}