{* Parameters: $name $selected=null $autofocus=false $refFieldName=null $submitOnSelect=false *}
{assign var="selected" value=$selected|default:null}
{assign var="autofocus" value=$autofocus|default:false}
{assign var="refFieldName" value=$refFieldName|default:null}
{assign var="submitOnSelect" value=$submitOnSelect|default:false}
<input type="hidden" id="{$name}_hidden" name="{$name}" value="{$selected->id|default:""}"/>
{if $refFieldName}
  <input type="hidden" id="{$name}_ref" name="{$refFieldName}" value=""/>
{/if}
<input type="text" id="{$name}_visible" name="{$name}_visible" value="{if $selected}{$selected->getDisplayId()}{/if}"
  {if $autofocus}autofocus="autofocus"{/if} size="80" placeHolder="Caută un act..."/>

<script>
  {literal}
  $("#{/literal}{$name}{literal}_visible").autocomplete({
    source: function(request, response) {
      $.ajax({
        url: "{/literal}{$wwwRoot}{literal}ajax/actAutocomplete.php",
        dataType: 'json',
        data: { term: request.term, ref: {/literal}{if $refFieldName}1{else}0{/if}{literal} },
        success: function(data) { response(data); },
      })
    },
    select: function (event, ui) {
      $('#{/literal}{$name}{literal}_hidden').val(ui.item.id);
      $('#{/literal}{$name}{literal}_ref').val(ui.item.ref);
      {/literal}
      {if $submitOnSelect}
        $('#{$name}_hidden').closest('form').submit();
      {/if}
      {literal}
    },
    change: function (event, ui) {
      if (ui.item) {
        $('#{/literal}{$name}{literal}_hidden').val(ui.item.id ? ui.item.id : '');
        $('#{/literal}{$name}{literal}_ref').val(ui.item.ref);
      }
    },
    minLength: 2,
  });
  {/literal}
</script>
