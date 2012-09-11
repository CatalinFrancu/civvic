<h3>
  {if $actType->id}
    Editează tipul de act '{$actType->name}'
  {else}
    Creează un tip de act
  {/if}
</h3>
<form action="editare-tip-act" method="post">
  {if $actType->id}
    <input type="hidden" name="id" value="{$actType->id}"/>
  {/if}
  Nume: <input type="text" name="name" value="{$actType->name}"/><br/>
  Articulat: <input type="text" name="artName" value="{$actType->artName}"/><br/>
  Numerotat: <input type="checkbox" name="hasNumbers" value="1" {if $actType->hasNumbers}checked="checked"{/if}/>
  <span class="hint">(unele tipuri de acte nu au numere: Constituția, Codul civil etc.)</span><br/>
  Expresii regulate: <span class="hint">(pentru crearea automată de legături)</span><br/>
  <textarea name="regexps" rows="5">{$actType->regexps}</textarea><br/>
  Prefixe: <span class="hint">(din titlurile actelor la importarea de monitoare)</span><br/>
  <textarea name="prefixes" rows="5">{$actType->prefixes}</textarea><br/>
  Nume de secțiuni: <span class="hint">(cuprinse între == == la importarea de monitoare)</span><br/>
  <textarea name="sectionNames" rows="5">{$actType->sectionNames}</textarea><br/>
  <input type="submit" name="submitButton" value="Salvează"/>
</form>

<br/>

{if $actType->id}
  <a class="delete" href="editare-tip-act?deleteId={$actType->id}"
     onclick="return confirm('Confirmați ștergerea tipului de act \'{$actType->name}\'?');">șterge</a>
  <br/><br/>
{/if}

<a href="tipuri-acte">înapoi la lista de tipuri</a>
