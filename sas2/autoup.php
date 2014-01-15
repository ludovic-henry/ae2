<?php
$topdir="../";
require_once("include/sas.inc.php");
require_once($topdir. "include/entities/asso.inc.php");
$site = new sas();

if ( $_REQUEST["act"] == "Info" )
{
  echo "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n";
  echo "<!DOCTYPE UBPTExchange>\n";
  echo "<info>\n";
  echo "  <name>Stock à Souvenirs (v2)</name>\n";
  echo "  <url>http://ae.utbm.fr/sas2/</url>\n";
  echo "  <message>(none)</message>\n";
  echo "  <domains>\n";
  echo "    <domain>utbm.fr</domain>\n";
  echo "    <domain>assidu-utbm.fr</domain>\n";
  echo "    <domain>id</domain>\n";
  echo "    <domain>autre</domain>\n";
  echo "  </domains>\n";
  echo "</info>\n";
  exit();
} else if ( $_REQUEST["act"] == "Connect" ) {

  echo "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n";
  echo "<!DOCTYPE UBPTExchange>\n";
  echo "<session>\n";

  $CVersion = explode(".",$_REQUEST["ClientVersion"]);
  if ( ( $CVersion[1] < 6 ) )
    echo "  <update/>\n";
  else
  {
    switch ($_REQUEST["Domain"])
    {
      case 0 :
        $site->user->load_by_email($_REQUEST["UserName"]."@utbm.fr");
      break;
      case 1 :
        $site->user->load_by_email($_REQUEST["UserName"]."@assidu-utbm.fr");
      break;
      case 2 :
        $site->user->load_by_id($_REQUEST["UserName"]);
      break;
      case 3 :
        $site->user->load_by_email($_REQUEST["UserName"]);
      break;
      default :
        $site->user->load_by_email($_REQUEST["UserName"]."@utbm.fr");
      break;
    }

    if ( $site->user->id != -1 && $site->user->hash == "valid" && $site->user->is_password($_POST["PassWord"]) )
    {
      echo "  <sessionid>".$site->connect_user()."</sessionid>\n";
      echo "  <userid>".$site->user->id."</userid>\n";
    }
    else
      echo "  <error/>\n";
  }

  echo "</session>\n";

  exit();

}

$site->load_session($_REQUEST["SessionId"]);

if ( $_REQUEST["act"] != "DownloadPhoto" )
{
  echo "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n";
  echo "<!DOCTYPE UBPTExchange>\n";
}

if ( !$site->user->is_valid() )
{
  echo "<error>SESSION_NECESSAIRE</error>\n";
  exit();
}
elseif ( $_REQUEST["act"] == "FetchContexts" )
{
  function get_short_semestre ($t)
  {
    $y = date("Y",$t);
    $m = date("m",$t);

    if ( $m >= 2 && $m < 9)
      return "P".$y;
    else if ( $m >= 9 )
      return "A".$y;
    else
      return "A".($y-1);
  }
  //
  $grps = $site->user->get_groups_csv();

  $req = new requete($site->db,"SELECT * " .
        "FROM sas_cat_photos " .
        "WHERE " .
        "((droits_acces_catph & 0x1) OR " .
        "((droits_acces_catph & 0x10) AND id_groupe IN ($grps)) OR " .
        "(id_groupe_admin IN ($grps)) OR " .
        "((droits_acces_catph & 0x100) AND id_utilisateur='".$site->user->id."')) " .
        "ORDER BY `id_catph_parent`,`date_debut_catph` DESC,`nom_catph`");


  echo "<contexts>\n";

  require_once($topdir."include/entities/group.inc.php");
  $groups=enumerates_groups($site->db);

  while ( $row = $req->get_row() )
  {
    if ( $row['date_debut_catph'] )
      $row["nom_catph"] .= " (".get_short_semestre(strtotime($row["date_debut_catph"])).")";

    echo "  <context>";
    echo "    <name>".htmlspecialchars($row["nom_catph"])."</name>\n";
    echo "    <id>".$row["id_catph"]."</id>\n";
    echo "    <idparent>".(!$row["id_catph_parent"]?0:$row["id_catph_parent"])."</idparent>\n";
    echo "    <group gid=\"".$row["id_groupe"]."\">".$groups[$row["id_groupe"]]."</group>\n";
    echo "    <rights>".$row["droits_acces_catph"]."</rights>\n";
    echo "  </context>";
  }

  echo "</contexts>\n";

  exit();
}
elseif ( $_REQUEST["act"] == "FetchUserGroups" )
{
  echo "<groups>\n";

  $groups=enumerates_groups($site->db);

  foreach ( $groups as $gid => $Group )
    echo "  <group gid=\"$gid\">$Group</group>";

  echo "</groups>\n";
  exit();
}
elseif ( $_REQUEST["act"] == "FetchClubs" )
{
  $asso = new asso($site->db);
  echo "<clubs>\n";

  $clubs=$asso->enumerate();

  foreach ( $clubs as $gid => $club )
    echo "  <club gid=\"$gid\">$club</club>";

  echo "</clubs>\n";
  exit();
}
elseif ( $_REQUEST["act"] == "FetchLicences" )
{
  $licence = new licence($site->db);
  echo "<licences>\n";

  $licences=$licence->enumerate();

  foreach ( $licences as $gid => $licence )
    echo "  <licence gid=\"$gid\">$licence</licence>";

  echo "</licences>\n";
  exit();
}
elseif ( $_REQUEST["act"] == "UploadImage" )
{
  require_once($topdir."include/entities/group.inc.php");
  $groups=enumerates_groups($site->db);


  $photo = new photo($site->db,$site->dbrw);
  $cat = new catphoto($site->db,$site->dbrw);

  $cat->load_by_id($_REQUEST["ContextId"]);
  if ( $cat->id < 1 ) {
    echo "<error>8</error>\n";
    exit();
  }

  if ( !$cat->is_right($site->user,DROIT_AJOUTITEM) ) {
    echo "<error>9</error>\n";
    exit();
  }

  if ( is_uploaded_file($_FILES['imageFile']['tmp_name']) && ($_FILES['imageFile']['error'] == UPLOAD_ERR_OK) )
  {
    $photo->herit($cat,false);

    $gids = array_keys($groups, $_REQUEST["Group"]);
    $id_group=$gids[0];

    $photo->set_rights($site->user,$_REQUEST["Rights"] & 0x333,$id_group,$cat->id_groupe_admin,false);

    if(isset($_REQUEST['id_licence']))
    {
      require_once('include/licence.inc.php');
      $licence=new licence($site->db);
      if($licence->load_by_id($_REQUEST['id_licence']))
        $licence=$licence->id;
      else
        $licence=$site->user->id_licence_default_sas;
    }
    else
      $licence=$site->user->id_licence_default_sas;

    $photographer = new utilisateur($site->db);
    if (isset($_REQUEST['PhotographerUserId']))
      $photographer->load_by_id($_REQUEST['PhotographerUserId']);

    $photographer_asso = new asso($site->db);
    if (isset($_REQUEST['AssoId']))
      $photographer_asso->load_by_id($_REQUEST['AssoId']);

    $photo->add_photo ( $_FILES['imageFile']['tmp_name'],
                        $cat->id,
                        isset($_REQUEST['Comment']) ? $_REQUEST['Comment'] : "",
                        $photographer->is_valid() ? $photographer->id : NULL,
                        false,
                        $cat->meta_id_asso,
                        NULL,
                        $photographer_asso->is_valid() ? $photographer_asso->id : NULL,
                        $licence);

    echo "<error>0</error>\n";

  }
  else
    echo "<error>1</error>\n";
  exit();

}
elseif ( $_REQUEST["act"] == "UploadVideoFLV" )
{
/*
 * La conversion de la video se fera coté client (par ubpttr v2.2)
 */
  require_once($topdir."include/entities/group.inc.php");
  $groups=enumerates_groups($site->db);


  $photo = new photo($site->db,$site->dbrw);
  $cat = new catphoto($site->db,$site->dbrw);

  $cat->load_by_id($_REQUEST["ContextId"]);
  if ( $cat->id < 1 ) {
    echo "<error>8</error>\n";
    exit();
  }

  if ( !$cat->is_right($site->user,DROIT_AJOUTITEM) ) {
    echo "<error>9</error>\n";
    exit();
  }

  if ( is_uploaded_file($_FILES['imageFile']['tmp_name']) && ($_FILES['imageFile']['error'] == UPLOAD_ERR_OK) &&
      is_uploaded_file($_FILES['flvFile']['tmp_name']) && ($_FILES['flvFile']['error'] == UPLOAD_ERR_OK) )
  {
    $photo->herit($cat,false);

    $gids = array_keys($groups, $_REQUEST["Group"]);
    $id_group=$gids[0];

    $photo->set_rights($site->user,$_REQUEST["Rights"] & 0x333,$id_group,$cat->id_groupe_admin,false);

    if(isset($_REQUEST['id_licence']))
    {
      require_once('include/licence.inc.php');
      $licence=new licence($site->db);
      if($licence->load_by_id($_REQUEST['id_licence']))
        $licence=$licence->id;
      else
        $licence=$site->user->id_licence_default_sas;
    }
    else
      $licence=$site->user->id_licence_default_sas;

    $photographer = new utilisateur($site->db);
    if (isset($_REQUEST['PhotographerUserId']))
      $photographer->load_by_id($_REQUEST['PhotographerUserId']);

    $photographer_asso = new asso($site->db);
    if (isset($_REQUEST['AssoId']))
      $photographer_asso->load_by_id($_REQUEST['AssoId']);

    $photo->add_videoflv ( $_FILES['imageFile']['tmp_name'],
                            $_FILES['flvFile']['tmp_name'],
                            $cat->id,
                            isset($_REQUEST['Comment']) ? $_REQUEST['Comment'] : "",
                            $photographer->is_valid() ? $photographer->id : NULL,
                            false,
                            $cat->meta_id_asso,
                            NULL,
                            $photographer_asso->is_valid() ? $photographer_asso->id : NULL,
                            $licence);

    echo "<error>0</error>\n";

  }
  else
    echo "<error>1</error>\n";
  exit();
}
elseif ( $_REQUEST["act"] == "Fetch" )
{ // L'objectif est de tout lister, mais en reduisant le plus possible la taille du xml produit

  $grps = $site->user->get_groups_csv();

  session_write_close();

  function reformat_date($date)
  {
    if(is_null($date))return"";
    if ($date=="1969-12-31 23:59:59") return"";
    if ($date=="1970-01-01 00:59:59") return"";
    if ($date=="1970-01-01 00:59:59") return"";
    return date("YmdHi",strtotime($date));

  }

  function fetch ( $cat )
  {
    global $site,$grps;
    $req = $cat->get_categories ( $cat->id, $site->user, $grps);
    $scat = new catphoto($site->db);
    while ( $row = $req->get_row() )
    {
      $scat->_load($row);
      echo "<c i=\"".$row["id_catph"]."\" n=\"".str_replace('"',"",$row["nom_catph"])."\" d=\"".reformat_date($row["date_debut_catph"])."\" f=\"".reformat_date($row["date_fin_catph"])."\">\n";
      fetch($scat);
      echo "</c>\n";
    }
    $req = $cat->get_photos ( $cat->id, $site->user, $grps);
    while ( $row = $req->get_row() )
    {
      echo "<i i=\"".$row["id_photo"]."\" d=\"".reformat_date($row["date_prise_vue"])."\" />\n";
    }
  }
  $cat = new catphoto($site->db);
  echo "<c>\n";
  $cat->load_by_id(1);
  fetch($cat);
  echo "</c>\n";
  exit();
}
elseif ( $_REQUEST["act"] == "DownloadPhoto" )
{
  session_write_close();

  $photo = new photo($site->db);
  $photo->load_by_id($_REQUEST["id_photo"]);

  if ( $photo->id < 1 || !$photo->is_right($site->user,DROIT_LECTURE) )
    exit();


  $abs_file = $photo->get_abs_path().$photo->id;

  if ( $_REQUEST["mode"] == "diapo" )
    $abs_file.=".diapo.jpg";
  else
    $abs_file.=".jpg";

  header("Content-type: image/jpeg");
  header("Content-Length: ".filesize($abs_file));
  readfile($abs_file);

  exit();
}
elseif ( $_REQUEST["act"] == "CheckSession" )
{
  echo "<session>\n";
  $CVersion = explode(".",$_REQUEST["ClientVersion"]);
  if ( ( $CVersion[1] < 6 ) )
    echo "  <update/>\n";
  else
    echo "  <sessionid>".$_REQUEST["SessionId"]."</sessionid>\n";
  echo "</session>\n";
  exit();
}
echo "<error>NON_DEFINI</error>\n";


?>
