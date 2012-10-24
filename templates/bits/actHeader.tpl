{* Parameters: $act $actType=null $monitor=null $authors=null $actAuthors=null $versions $shownAv $republicationMonitors $editLinks=false *}
{assign var="editLinks" value=$editLinks|default:false}
<div class="actTitle">{$act->name}</div>
<div class="actDetails">
  <ul class="attributes">
    {if $actType}<li>tipul: <b>{$actType->name}</b>{/if}
    {if $act->number}<li>numărul: <b>{$act->number} / {$act->year}</b></li>{/if}
    {if $act->issueDate}<li>data: <b>{$act->issueDate|date_format:"%e %B %Y"}</b></li>{/if}
    {if $monitor}<li>publicat în {include file=bits/monitorLink.tpl monitor=$monitor}</li>{/if}
  </ul>

  {if count($republicationMonitors)}
    <ul class="attributes">
      <li>
        republicat în 
        {strip}
          {foreach from=$republicationMonitors item=rep key=i}
            {if $i}, {/if}
            {include file=bits/monitorLink.tpl monitor=$rep}
          {/foreach}
        {/strip}
      </li>
    </ul>
  {/if}

  <ul class="authors">
    {if $act->note}
      notă: {$act->note}
    {/if}

    {foreach from=$authors item=author key=i}
      {assign var=aa value=$actAuthors.$i}
      <li>
        {$aa->getSignatureTypeName()}: <b>{$author->getDisplayName()}</b>
        {if $aa->note}
          <a href="#" onclick="$(this).next('.signatureNote').slideToggle(); return false">notă</a>
          <div class="signatureNote">{$aa->note}</div>
        {/if}
      </li>
    {/foreach}
  </ul>

  {if count($versions) > 1}
    <form action="act">
      <input type="hidden" name="id" value="{$act->id}"/>
      Versiunea:
      <select name="version">
        {foreach from=$versions item=av}
          <option value="{$av->versionNumber}" {if $av->versionNumber == $shownAv->versionNumber}selected="selected"{/if}>
            {$av->versionNumber}{if $av->current} (curentă){/if}
          </option>
        {/foreach}
      </select>
      <input type="submit" name="submitButton" value="Arată"/>
    </form>
  {/if}

  {if $editLinks && $user && $user->admin}
    <span class="actEditLinks">
      editează <a href="editare-act?id={$act->id}">actul</a> |
      <a href="editare-versiune-act?id={$shownAv->id}">această versiune</a>
    </span>

    {include file=bits/monitorPdfLink.tpl monitor=$monitor}
  {/if}
</div>
