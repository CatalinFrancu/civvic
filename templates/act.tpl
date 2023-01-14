{if $act}
  {include "bits/actHeader.tpl" act=$act actType=$actType monitor=$monitor authors=$authors actAuthors=$actAuthors
    versions=$versions shownAv=$shownAv republicationMonitors=$republicationMonitors editLinks=true}
{/if}

{if $shownAv->status == $smarty.const.ACT_STATUS_REPEALED}
  <div class="repealedMention">
    Acest act a fost abrogat de {include "bits/actLink.tpl" act=$modifyingAct}.
  </div>
{/if}

{if $referringActs || $collidingActs}
  <div class="relatedActLinks">
    {if $referringActs}
      <a class="referringActLink" href="#" onclick="$('#referringActList').toggle(); return false">acte care menționează acest act</a>
    {/if}
    {if $collidingActs}
      <a class="collidingActLink" href="#" onclick="$('#collidingActList').toggle(); return false">acte cu același nume</a>
    {/if}
  </div>
{/if}


{if $referringActs}
  <div id="referringActList" class="relatedActList">
    <p>Acte care menționează acest act:</p>
    <ul>
      {foreach from=$referringActs item=a}
        <li>{include "bits/actLink.tpl" act=$a}</li>
      {/foreach}
    </ul>
  </div>
{/if}

{if $collidingActs}
  <div id="collidingActList" class="relatedActList">
    <p>Acte cu același nume:</p>
    <ul>
      {foreach from=$collidingActs item=a}
        <li>{include "bits/actLink.tpl" act=$a}</li>
      {/foreach}
    </ul>
  </div>
{/if}

<div id="actBody">{$shownAv->htmlContents}</div>
