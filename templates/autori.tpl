<h3>Autori</h3>

<a href="editare-autor">adaugă un autor</a><br/><br/>

<table id="authors">
  <tr>
    <th>instituție</th>
    <th>funcție</th>
    <th>titlu</th>
    <th>persoană</th>
    <th>acțiuni</th>
  </tr>
  {foreach from=$authors item=a}
    <tr>
      <td>{$a->institution}</td>
      <td>{$a->position}</td>
      <td>{$a->title}</td>
      <td>{$a->name}</td>
      <td><a href="editare-autor?id={$a->id}">editează</a></td>
    </tr>
     
  {/foreach}
</table>

<script>
  {literal}
  $(function() {
    tableToGrid('#authors', {});
    $('#authors').setGridHeight('auto');
  });
  {/literal}
</script>