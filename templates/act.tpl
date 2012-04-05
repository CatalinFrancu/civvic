{include file=bits/actHeader.tpl act=$act actType=$actType monitor=$monitor authors=$authors actAuthors=$actAuthors
  versions=$versions shownAv=$shownAv editLinks=true}

{if $shownAv->status == $smarty.const.ACT_STATUS_REPEALED}
  <div class="repealedMention">
    Acest act a fost abrogat de {include file=bits/actLink.tpl act=$modifyingAct}.
  </div>
{/if}

{if $referringActs}
  <div class="referringActs">
    <a href="#" onclick="$(this).next('ul').toggle(); return false">acte care menționează acest act</a>
    <ul>
      {foreach from=$referringActs item=ra}
        <li>{include file=bits/actLink.tpl act=$ra}</li>
      {/foreach}
    </ul>
  </div>
{/if}
{$shownAv->htmlContents}
