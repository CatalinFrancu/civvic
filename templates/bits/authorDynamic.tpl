{* Parameters: $authorName $signatureTypeName $noteName $authors $signatureTypes *}
{foreach from=$authors item=a key=i}
  <div class="authorWrapper">
    {include file=bits/signatureTypeDropdown.tpl name="`$signatureTypeName`[]" signatureTypes=$signatureTypes
      selected=$actAuthors.$i->signatureType emptyOption=false}
    <input type="text" name="{$authorName}[]" class="authorName" value="{$a->getDisplayName()}" size="80"/>
    <br/>
    <input type="text" name="{$noteName}[]" value="{$actAuthors.$i->note}" size="90" placeholder="opțional: în temeiul legii... etc."/>
    <input type="button" class="delAuthor" value="șterge" {if count($authors) == 1}disabled="disabled"{/if}/>
  </div>
{/foreach}

{* There has to be at least one set of inputs for cloning to work. *}
{if !count($authors)}
  <div class="authorWrapper">
    {include file=bits/signatureTypeDropdown.tpl name="`$signatureTypeName`[]" signatureTypes=$signatureTypes selected=null emptyOption=false}
    <input type="text" name="{$authorName}[]" class="authorName" value="" size="80"/>
    <br/>
    <input type="text" name="{$noteName}[]" value="" size="90" placeholder="opțional: în temeiul legii... etc."/>
    <input type="button" class="delAuthor" value="șterge"/>
  </div>
{/if}

<input type="button" id="addAuthor" value="adaugă"/>

<script>
  {literal}

  function delButtonClick() {
    var numDivs = $(".authorWrapper").length;
    if (numDivs == 1) {
      return; // Don't delete the last div
    }
    $(this).parent(".authorWrapper").remove();
    if (numDivs == 2) {
      $(".delAuthor").attr("disabled", "disabled");
    }
  }

  autocompleteParams = {
    source: function(request, response) {
      $.getJSON("{/literal}{$wwwRoot}{literal}ajax/authorAutocomplete.php", { term: request.term }, response);
    },
    minLength: 3,
  };

  $(function() {
    $(".authorName").autocomplete(autocompleteParams);

    $("#addAuthor").click(function() {
      var newDiv = $(".authorWrapper:first").clone();
      newDiv.find("input[type=text]").val("");
      newDiv.find("select").val("1");
      newDiv.find(".delAuthor").click(delButtonClick);
      newDiv.find(".authorName").autocomplete(autocompleteParams);
      $("#addAuthor").before(newDiv);
      $(".delAuthor").removeAttr("disabled");
    });

    $(".delAuthor").click(delButtonClick);
  });
  {/literal}
</script>
