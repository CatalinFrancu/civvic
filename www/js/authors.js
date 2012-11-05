function authorAfterSubmit(response, postData) {
  if (response.responseText) {
    return [false, response.responseText];
  } else {
    var d = $('#authorsSuccessMessage');
    d.html("Datele au fost salvate cu succes");
    d.show();
    d.delay(3000).fadeOut();
    return [true, null];
  }
}

$(function() {
  var editOptions = {
    width: 600,
    top: 125,
    left: 100,
    afterSubmit: authorAfterSubmit,
    closeAfterEdit: true,
    closeOnEscape: true,
    reloadAfterSubmit: true,
  };
 
  var addOptions = {
    width: 600,
    top: 125,
    left: 100,
    afterSubmit: authorAfterSubmit,
    closeAfterAdd: true,
    closeOnEscape: true,
    reloadAfterSubmit: true,
  };
 
  var deleteOptions = {
    width: 600,
    top: 125,
    left: 100,
    afterSubmit: authorAfterSubmit,
    closeOnEscape: true,
    reloadAfterSubmit: true,
  };
 
  $('#authors').jqGrid({
    url: 'ajax/authorList.php',
    editurl: 'ajax/authorSave.php',
    datatype: 'xml',
      colNames: ['nume', 'titlu', 'funcție', 'instituție', 'id'],
    colModel: [
     {name: 'name', editable: true, editoptions: { size: 40 }},
     {name: 'title', editable: true, editoptions: { size: 40 }},
     {name: 'position', editable: true, editoptions: { size: 40 }},
     {name: 'institution', editable: true, editoptions: { size: 40 }},
     {name: 'id', hidden: 'true'},
    ],
    rowNum: 20,
    autowidth: true,
    height: '100%',
    rowList: [20, 50, 100],
    pager: '#pagerAuthors',
    sortname: 'name',
    sortorder: 'asc',
    viewrecords: true,
    ondblClickRow: function(rowid) { $(this).jqGrid('editGridRow', rowid, editOptions); }
  });
   
  $('#authors').navGrid('#pagerAuthors',
    {
      addtext: 'adaugă',
      deltext: 'șterge',
      edittext: 'editează',
      refreshtext: 'reîncarcă',
      refreshtitle: 'Reîncarcă tabelul',
      search: false,
    }, editOptions, addOptions, deleteOptions
  );

  $('#authors').filterToolbar({});
});
