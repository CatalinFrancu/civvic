{include file=bits/actHeader.tpl act=$act actType=$actType monitor=$monitor authors=$authors actAuthors=$actAuthors
  versions=$versions shownAv=$shownAv editLinks=true}

{if $shownAv->status == $smarty.const.ACT_STATUS_REPEALED}
  <div class="repealedMention">
    Acest act a fost abrogat de {include file=bits/actLink.tpl act=$modifyingAct}.
  </div>
{/if}
{$shownAv->htmlContents}
