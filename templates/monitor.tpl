<div class="actTitle">Monitorul Oficial nr. {$monitor->number} / {$monitor->year}</div>
<div class="actDetails">
  publicat la {$monitor->issueDate|date_format:"%e %B %Y"}<br/>

  {if $user && $user->admin}
    <span class="actEditLinks">
      <a href="editare-monitor?id={$monitor->id}">editează</a>
    </span>

    {include file=bits/monitorPdfLink.tpl monitor=$monitor}
  {/if}
</div>

<ul class="actList">
  {foreach from=$acts item=a}
     <li>{include file=bits/actLink.tpl act=$a} {$a->name}</li>
  {/foreach}
</ul>

{if $republishedActs}
  <h3>Republicări</h3>

  <ul class="actList">
    {foreach from=$republishedActs item=a}
       <li>{include file=bits/actLink.tpl act=$a} {$a->name}</li>
    {/foreach}
  </ul>
{/if}