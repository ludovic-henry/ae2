function explore ( id )
{
  var obj = document.getElementById("folder_"+id);
  
  if ( obj.innerHTML != "" )
    obj.innerHTML = "";
  else
    openInContents("folder_"+id, "explorer.php", "get=folderchilds&id_folder="+id);
    
  openInContents("foldercontents", "explorer.php", "get=foldercontents&id_folder="+id);
}

function select_file ( id, titre )
{
  window.opener.onSelectedFile(id, titre);
  window.close();
}
