{* $Id$ *}
{if isset($message)}{$message}</p>{/if}
{foreach from=$battles key=id item=b}
game={$b.gametype}&name={$b.bot_name}&vs={$b.vs_name}&timestamp={$b.timestamp}&millisecs={$b.millisecs}
{/foreach}