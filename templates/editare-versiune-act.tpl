<h3>
  Editează versiunea {$av->versionNumber} pentru {$act->name}
</h3>

<form action="editare-versiune-act" method="post">
  <input type="hidden" name="id" value="{$av->id}"/>

  <table class="editForm">
    <tr>
      <td>cauzată de:</td>
      <td>{include file=bits/actAutocomplete.tpl name="modifyingActId" selected=$modifyingAct autofocus=true}</td>
    </tr>
    <tr>
      <td>starea:</td>
      <td>
        {include file=bits/actStatusDropdown.tpl name="status" actStatuses=$actStatuses selected=$av->status}
        <div id="repubDiv">
          în {include file=bits/monitorDropdown.tpl name="monitorId" monitors=$monitors selected=$av->monitorId}</td>
        </div>
      </td>
    </tr>
    <tr>
      <td>conținutul:</td>
      <td><a id="togglePreviewLink" href="#">{if $preview}ascunde HTML{else}arată HTML{/if}</a></td>
    </tr>
    <tr>
      <td colspan="2">
        <div id="wikiHtmlPreview" class="wikiHtmlPreview" {if !$preview}style="display: none"{/if}>{$av->htmlContents}</div>
        <textarea name="contents" rows="20">{$av->contents}</textarea><br/>
      </td>
    </tr>
    <tr>
      <td></td>
      <td>
        <input type="submit" name="previewButton" value="Previzualizează"/>
        <input type="submit" name="submitButton" value="Salvează"/>
      </td>
    </tr>
  </table>
</form>

<br/>
<a class="delete" href="editare-versiune-act?deleteId={$av->id}"
   onclick="return confirm('Confirmați ștergerea versiunii {$av->versionNumber}?');">șterge</a>

<br/>
<a href="editare-act?id={$av->actId}">înapoi la editare act</a> |
<a href="act?id={$av->actId}">înapoi la vizualizare act</a>

<script>
  {literal}
  $('#togglePreviewLink').click(function(ev) { 
    $('#wikiHtmlPreview').toggle('fast'); 
    $(this).text(($('#togglePreviewLink').text() == 'arată HTML') ? 'ascunde HTML' : 'arată HTML');
   });

  function updateDateDivVisibility() {
    if ($(this).val() == 3) {
      $('#repubDiv').css('display', 'inline');
    } else {
      $('#repubDiv').css('display', 'none');
      $('#repubDiv select').val('');
    }
  }

  $('select[name="status"]').change(updateDateDivVisibility);

  $(function() {
    $('select[name="status"]').change();
  });
  {/literal}
</script>
