{* Parameters: $name $signatureTypes $selected $emptyOption=true *}
{assign var="emptyOption" value=$emptyOption|default:true}
<select name="{$name}">
  {if $emptyOption}
    <option value=""></option>
  {/if}
  {foreach from=$signatureTypes item=name key=i}
    <option value="{$i}" {if $i == $selected}selected="selected"{/if}>
      {$name}
    </option>
  {/foreach}
</select>
