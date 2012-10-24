<h3>
  {if $act->id}
    Editează actul '{$act->name}'
  {else}
    Creează un act
  {/if}
</h3>

<form action="editare-act" method="post">
  {if $act->id}
    <input type="hidden" name="id" value="{$act->id}"/>
  {/if}
  <table class="editForm">
    <tr>
      <td>nume:</td>
      <td><input type="text" name="name" value="{$act->name}" size="80" autocomplete="off"/></td>
    </tr>
    <tr>
      <td>tip:</td>
      <td>{include file="bits/actTypeDropdown.tpl" name="actTypeId" actTypes=$actTypes selected=$act->actTypeId autofocus=true}</td>
    </tr>
    <tr>
      <td>număr/an:</td>
      <td>
        <input type="text" name="number" value="{$act->number}" size="4"/> /
        <input type="text" name="year" value="{$act->year}" size="4"/>
      </td>
    </tr>
    <tr>
      <td>data:</td>
      <td>{include file="bits/datePicker.tpl" id="issueDate" name="issueDate" value=$act->issueDate}</td>
    </tr>
    <tr>
      <td>notă:</td>
      <td>
        <input type="text" name="note" value="{$act->note}" size="80"
          placeholder="opțional; unele acte au o notă comună, separată de nota fiecărui semnatar"/>
      </td>
    </tr>
    <tr>
      <td>autor(i):</td>
      <td>
        {include file=bits/authorDynamic.tpl authorName="authors" signatureTypeName="signatureTypes" noteName="notes"
        authors=$authors signatureTypes=$signatureTypes}
      </td>
    </tr>
    <tr>
      <td>publicat în</td>
      <td>{include file=bits/monitorDropdown.tpl name="monitorId" monitors=$monitors selected=$act->monitorId}</td>
    </tr>
    <tr>
      <td>locul:</td>
      <td>{include file=bits/placeDropdown.tpl name="placeId" places=$places selected=$act->placeId}</td>
    </tr>
    <tr>
      <td>comentariu:</td>
      <td><textarea name="comment" rows="3">{$act->comment}</textarea></td>
    </tr>
    <tr>
      <td></td>
      <td><input type="submit" name="submitButton" value="Salvează"/></td>
    </tr>
  </table>
</form>

<br/>
{if $act->id}
  <a class="delete" href="editare-act?deleteId={$act->id}"
     onclick="return confirm('Confirmați ștergerea actului \'{$act->name}\'?');">șterge</a>
  <br/>

  <h3>Lista de versiuni</h3>

  <table class="actVersionTable">
    <tr>
      <th>nr.</th>
      <th>acțiuni</th>
    </tr>
    {foreach from=$actVersions item=av}
      <tr>
        <td>{$av->versionNumber}</td>
        <td><span class="actEditLinks"><a href="editare-versiune-act?id={$av->id}">editează</a></span></td>
      </tr>
    {/foreach}
  </table>

  <form action="editare-act" method="post">
    <input type="hidden" name="id" value="{$act->id}"/>
    Adaugă o versiune [
    <input type="radio" id="versionPlacementBefore" name="versionPlacement" value="before"/>
    <label for="versionPlacementBefore">înainte</label>
    <input type="radio" id="versionPlacementAfter" name="versionPlacement" value="after" checked="checked"/>
    <label for="versionPlacementAfter">după</label>
    ] versiunea
    <input type="text" name="otherVersionNumber" value="{$numVersions}"/>
    <input type="submit" name="addVersionButton" value="Adaugă"/>
  </form>
{/if}

<br/>
<a href="act?id={$act->id}">înapoi la act</a>
