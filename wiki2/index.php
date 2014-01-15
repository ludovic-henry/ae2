<?php

/* Copyright 2007
 *
 * - Simon Lopez < simon DOT lopez AT ayolo DOT org >
 * - Julien Etelain < julien at pmad dot net >
 * Ce fichier fait partie du site de l'Association des étudiants
 * de l'UTBM, http://ae.utbm.fr.
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA
 * 02111-1307, USA.
 */

$topdir = "../";

require_once($topdir. "include/site.inc.php");
require_once($topdir. "include/entities/asso.inc.php");
require_once($topdir. "include/entities/wiki.inc.php");
require_once($topdir. "include/cts/wiki.php");
$conf['maxtoclevel']=4;
$conf['maxseclevel']=6;
$conf['bookmarks']=true;

$site = new site();
$site->set_side_boxes("left",array("calendrier","connexion"));

$wiki = new wiki($site->db,$site->dbrw);

$site->add_css("css/doku.css");
$site->add_css("css/wiki.css");

function build_htmlpath ( $fullpath )
{
  $buffer = "<a href=\"./\">Wiki</a>";

  if ( empty($fullpath) )
    return $buffer;

  $path=null;
  $tokens = explode(":",$fullpath);

  foreach ( $tokens as $token )
  {
    if ( is_null($path) )
      $path = $token;
    else
      $path .= ":".$token;
    $buffer .= " / <a href=\"./?name=".htmlentities($path,ENT_QUOTES,"UTF-8")."\">".
               htmlentities($token,ENT_NOQUOTES,"UTF-8")."</a>";
  }
  return $buffer;
}

function build_asso_htmlpath ( $fullpath )
{
  $tokens = explode(":",$fullpath);
  $pole = $tokens[0];
  unset($tokens[0]);
  $asso = $tokens[1];
  unset($tokens[1]);

  $buffer = "<a href=\"./?name=".$pole.":".$asso."\">Wiki</a>";
  $path = $pole.":".$asso;

  foreach ( $tokens as $token )
  {
    $path .= ":".$token;
    $buffer .= " / <a href=\"./?name=".htmlentities($path,ENT_QUOTES,"UTF-8")."\">".
               htmlentities($token,ENT_NOQUOTES,"UTF-8")."</a>";
  }
  return $buffer;
}

// Casse toi de là toi !
if (isset($_REQUEST["name"]) && (substr($_REQUEST["name"], 0, 9) == "articles:"))
  $site->redirect('/article.php?name='.substr($_REQUEST["name"], 9));


// Creation d'une page
if ( $site->user->is_valid() && $_REQUEST["action"] == "create" )
{
  $parent = new wiki($site->db,$site->dbrw);
  /*
  // Prepare les info
  $pagepath = $_REQUEST["name"];

  // Récupère les tokens et le nom de la page (dernier token du path)
  $tokens = explode(":",$pagepath);
  $pagename=array_pop($tokens);

  // Cherche le dernier parent, crée les parents manquant si nécessaire
  // Commençons par la racine
  $parent->load_by_id(1);
  $can_create = $parent->is_right($site->user,DROIT_AJOUTCAT);

  // Poursuivons par les eventuel parents
  $parentparent = clone $parent;
  foreach( $tokens as $token )
  {
    if ( $parent->load_by_name($parentparent,$token) )
      $can_create = $parent->is_right($site->user,DROIT_AJOUTCAT);

    elseif( $can_create ) // On a le droit de creer, on alors on crée le parent manquant
    {
      $parent->herit($parentparent);
      if ( $parent->is_admin($site->user) )
         $parent->set_rights($site->user,
           $_REQUEST['rights'],$_REQUEST['rights_id_group'],
           $_REQUEST['rights_id_group_admin']);
      else
        $parent->id_utilisateur=$site->user->id;
      $parent->create ( $parentparent, null, $token, 0, $token, "Créée pour [[:$pagepath]]", $_REQUEST["comment"] );
    }
    $parentparent = clone $parent;
  }

  if ( !preg_match("#^([a-z0-9\-_:]+)$#",$pagepath) )
    $can_create=false;

  if ( strlen($pagename) > 64 )
    $can_create = false;

  if ( strlen($pagepath) > 512 )
    $can_create = false;*/

  $pagename = $parent->load_or_create_parent($_REQUEST["name"], $site->user, $_REQUEST['rights'], $_REQUEST['rights_id_group'], $_REQUEST['rights_id_group_admin']);

  if ( !is_null($pagename) && $parent->is_valid() && !$wiki->load_by_name($parent,$pagename) )
  {
    $wiki->herit($parent);
    if ( $parent->is_admin($site->user) )
        $wiki->set_rights($site->user,
          $_REQUEST['rights'],$_REQUEST['rights_id_group'],
          $_REQUEST['rights_id_group_admin']);
    else
      $parent->id_utilisateur=$site->user->id;
    $wiki->create ( $parent, null, $pagename, 0, $_REQUEST["title"], $_REQUEST["contents"], $_REQUEST["comment"] );
  }
  else
  {
    $Erreur="Impossible de créer la page.";
    $_REQUEST["view"]="create";
    $wiki->id=null;
  }
}
elseif ( isset($_REQUEST["name"]) )
{
  if ( !preg_match("#(\.|/)#",$_REQUEST["name"]) )
  {
    $_REQUEST["name"] = preg_replace("#[^a-z0-9\-_:]#","_",strtolower(utf8_enleve_accents($_REQUEST["name"])));
    $valid_name=true;

    if ( !(isset($_REQUEST["rev"]) && $wiki->load_by_fullpath_and_rev($_REQUEST["name"],$_REQUEST["rev"])) )
      $wiki->load_by_fullpath($_REQUEST["name"]);
  }
  else
    $valid_name=false;
}
else
  $wiki->load_by_id(1);

if ( !$wiki->is_valid() )
{
  $pagepath = $_REQUEST["name"];
  $can_create = false;
  $is_admin = false;
  if ( $site->user->is_valid() && $valid_name )
  {
    // Cherche le parent le plus haut pour savoir si la création de page est autorisée
    $parent = new wiki($site->db);
    $tokens = explode(":",$pagepath);
    $pagename = array_pop($tokens);

    // La racine
    $parent->load_by_id(1);
    $can_create = $parent->is_right($site->user,DROIT_AJOUTCAT);
    $is_admin = $parent->is_admin($site->user);
    $lastparent = clone $parent;
    // Les eventuels parents
    foreach( $tokens as $token )
    {
      if ( $parent->load_by_name($parent,$token) )
      {
        $can_create = $parent->is_right($site->user,DROIT_AJOUTCAT);
        $is_admin = $parent->is_admin($site->user);
      }
      else
        break;
      $lastparent = clone $parent;
    }

    if ( strlen($pagename) > 64 )
      $can_create = false;

    if ( strlen($pagepath) > 512 )
      $can_create = false;
  }

  $site->start_page ("wiki", "Page inexistante");

  /*$side = new contents("Wiki");
  $lst = new itemlist();
  $lst->add("<a href=\"".$wwwtopdir."wiki2/?name=".$pagepath."\">Voir la page</a>");
  if ( $can_create )
    $lst->add("<a href=\"".$wwwtopdir."wiki2/?name=".$pagepath."&view=create\">Créer</a>");
  $side->add($lst);*/


  $tools = array();
  $tools[$wwwtopdir."wiki2/?name=".$pagepath] = "Voir la page";

  if ( $can_create )
    $tools[$wwwtopdir."wiki2/?name=".$pagepath."&view=create"] = "Créer";

  $castor = explode(":",$pagepath);

  $req = new requete($site->db,"SELECT asso.id_asso FROM asso
                                LEFT JOIN asso AS asso_parent ON asso.id_asso_parent=asso_parent.id_asso
                                WHERE CONCAT(asso_parent.nom_unix_asso,':',asso.nom_unix_asso)='".$castor[0].":".$castor[1]."'
                                AND asso.id_asso_parent <> '1'");

  if ( $req->lines == 1 )
    list($asso_id) = $req->get_row();


  if ( !is_null($asso_id))
  {
    $asso = new asso($site->db);
    $asso->load_by_id($asso_id);

    $cts = new contents($asso->get_html_path());
    $site->start_page("presentation","Wiki");

    $cts->add(new tabshead($asso->get_tabs($site->user),"wiki2"));
    $path = build_asso_htmlpath($pagepath);

    $ctsttl = new contents();
    $ctsttl->set_toolbox(new toolbox($tools));
    $ctsttl->add_title(1,build_asso_htmlpath($pagepath),"wikipath");
    $cts->add($ctsttl);
  }
  else
  {
    $cts = new contents(build_htmlpath($pagepath));
    $cts->set_toolbox(new toolbox($tools));
  }

  $site->add_box("wiki",$side);

  if ( $can_create && $_REQUEST["view"] == "create" )
  {
    $frm = new form("newwiki","./?name=$pagepath",true,"POST");
    if ( isset($Erreur) )
      $frm->error($Erreur);
    $frm->add_hidden("action","create");
    $frm->add_text_field("title","Titre","",true);
    $frm->add_dokuwiki_toolbar("contents");
    $frm->add_text_area("contents","Contenu","",80,30,true,true);
    $frm->add_text_field("comment","Log","Créée");
    if ( $is_admin )
      $frm->add_rights_field($lastparent,true,true,"wiki");
    $frm->add_submit("save","Ajouter");
    $cts->add($frm);
  }
  else
  {
    if ( $can_create )
      $cts->add_paragraph("Cette page n'existe pas. <a href=\"?name=".$pagepath."&view=create\">La creer</a>","error");
    else
      $cts->add_paragraph("Cette page n'existe pas.","error");
  }
  $site->add_contents($cts);

  $site->end_page ();

  exit();
}

$pagepath = $wiki->fullpath;
$pagename = $pagepath ? $pagepath : "(racine)";
$can_edit = $site->user->is_valid() && $wiki->is_right($site->user,DROIT_ECRITURE);
$is_admin = $wiki->is_admin($site->user);

if ( !$wiki->is_right($site->user,DROIT_LECTURE) )
  $site->error_forbidden("wiki","group",$wiki->id_groupe);


if ( $_REQUEST["action"] == "lockrenew" && $can_edit )
{
  $wiki->lock_renew($site->user);
  echo "wiki_renewed();";
  exit();
}
else if ( $_REQUEST["action"] == "revision" && $can_edit )
{
  $wiki->unlock($site->user);
  if ( $_REQUEST['save']=='Enregistrer' && ($_REQUEST["title"] != $wiki->rev_title || $_REQUEST["contents"] != $wiki->rev_contents ) )
  {
    if ( $_REQUEST["id_rev_last"] != $wiki->id_rev_last ) // pas cool
    {
      $Erreur="La page a été modifiée par un autre utilisateur ente temps.";
      $_REQUEST["view"] = "edit";
    }
    else if ( $wiki->is_locked($site->user) ) // encore moins cool
    {
      $Erreur="La page est en cours d'édition par un autre utilisteur. Elle n'a pas eu être modifiée.";
      $_REQUEST["view"] = "edit";
    }
    else
      $wiki->revision ( $site->user->id, $_REQUEST["title"], $_REQUEST["contents"], $_REQUEST["comment"] );
  }
}
elseif ( $_REQUEST["action"] == "edit" && $is_admin )
{
  $wiki->set_rights($site->user,
          $_REQUEST['rights'],$_REQUEST['rights_id_group'],
          $_REQUEST['rights_id_group_admin']);
  $wiki->update();
}
$site->start_page ("wiki", $wiki->rev_title);

/*$side = new contents("Wiki");
$lst = new itemlist();
$lst->add("<a href=\"".$wwwtopdir."wiki2/?name=".$pagepath."\">Voir la page</a>");
if ( $is_admin )
  $lst->add("<a href=\"".$wwwtopdir."wiki2/?name=".$pagepath."&view=edit".(isset($_REQUEST["rev"])?"&rev=".$_REQUEST["rev"]:"")."\">Editer</a>");
if ( $can_edit )
  $lst->add("<a href=\"".$wwwtopdir."wiki2/?name=".$pagepath."&view=refs\">Références</a>");
else
  $lst->add("<a href=\"".$wwwtopdir."wiki2/?name=".$pagepath."&view=srcs\">Source</a>");
$lst->add("<a href=\"".$wwwtopdir."wiki2/?name=".$pagepath."&view=hist\">Historique</a>");
$lst->add("<a href=\"".$wwwtopdir."wiki2/?name=".$pagepath."&view=advc\">Propriétés</a>");
$side->add($lst);*/

$tools = array();
$tools[$wwwtopdir."wiki2/?name=".$pagepath]="Voir la page";
if ( $can_edit )
  $tools[$wwwtopdir."wiki2/?name=".$pagepath."&view=edit".(isset($_REQUEST["rev"])?"&rev=".$_REQUEST["rev"]:"")]="Editer";
else
  $tools[$wwwtopdir."wiki2/?name=".$pagepath."&view=srcs"]="Source";
$tools[$wwwtopdir."wiki2/?name=".$pagepath."&view=refs"]="Références";
$tools[$wwwtopdir."wiki2/?name=".$pagepath."&view=hist"]="Historique de la page";
if ( $is_admin )
  $tools[$wwwtopdir."wiki2/?name=".$pagepath."&view=advc"]="Propriétés";




$castor = explode(":",$pagepath);

$req = new requete($site->db,"SELECT asso.id_asso FROM asso
                              LEFT JOIN asso AS asso_parent ON asso.id_asso_parent=asso_parent.id_asso
                              WHERE CONCAT(asso_parent.nom_unix_asso,':',asso.nom_unix_asso)='".$castor[0].":".$castor[1]."'
                              AND asso.id_asso_parent <> '1'");

if ( $req->lines == 1 )
  list($asso_id) = $req->get_row();

if ( !is_null($asso_id))
{
  $asso = new asso($site->db);
  $asso->load_by_id($asso_id);

  $cts = new contents($asso->get_html_path());
  $site->start_page("presentation","Wiki");

  $cts->add(new tabshead($asso->get_tabs($site->user),"wiki2"));
  $path = build_asso_htmlpath($pagepath);

  $ctsttl = new contents();
  $ctsttl->set_toolbox(new toolbox($tools));
  $ctsttl->add_title(1,build_asso_htmlpath($pagepath),"wikipath");
  $cts->add($ctsttl);
}
else
{
  $cts = new contents(build_htmlpath($pagepath));
  $cts->set_toolbox(new toolbox($tools));
}

//$cts->add_paragraph($path,"wikipath");

//$cts->add_title(1,htmlentities($wiki->rev_title,ENT_NOQUOTES,"UTF-8"));
$cts->puts("<h1 class=\"wikititle\">".htmlentities($wiki->rev_title,ENT_NOQUOTES,"UTF-8")."</h1>\n");

//$site->add_box("wiki",$side);

if ( $is_admin && $_REQUEST["view"] == "advc" )
{
  $frm = new form("editwiki","./?name=$pagepath",true,"POST");
  $frm->add_hidden("action","edit");
  $frm->add_rights_field($wiki,true,true,"wiki");
  $frm->add_submit("edit","Enregistrer");
  $cts->add($frm);
}
elseif ( $can_edit && $_REQUEST["view"] == "edit" )
{
  if ( $wiki->is_locked($site->user) )
  {
    if ( isset($Erreur) )
    {
      $cts->add_paragraph($Erreur,"error");
      /* et oui, un autre a pu modifier et encore un autre peut editer la page
       * à ce moment là, là c'est vraiment pas de bol, ça peut arriver qu'a Ayolo
       * ce genre de situation, mais bon... :-P
       */
      $cts->add_paragraph("<a href=\"./?name=$pagepath\">Voir la version actuelle.</a>");
      $cts->add_paragraph("La page est en cours d'édition par un autre utilisteur. Il n'est pas possible de reprendre l'édition de la page.","error");
      $cts->add_paragraph("<b>Conseil</b>: Sauvegardez vos modification dans un éditeur de texte, et retentez de le modifier dans une dizaine de minutes.");
      $cts->add_paragraph("Voici ce que vous vouliez soumettre :");
      $cts->add_paragraph("Titre : ".htmlentities($_REQUEST["title"],ENT_NOQUOTES,"UTF-8"));
      $cts->add_paragraph("Contenu : ".nl2br(htmlentities($_REQUEST["contents"],ENT_NOQUOTES,"UTF-8")));
      $cts->add_paragraph("Log : ".htmlentities($_REQUEST["comment"],ENT_NOQUOTES,"UTF-8"));
    }
    else
      $cts->add_paragraph("Cette page est en cours d'édition par un autre utilisateur. Il n'est pas possible de l'éditer en même temps.","error");
  }
  else
  {
    $site->add_js("js/wiki.js");

    if ( isset($Erreur) )
    {
      // TODO: tenter un merge? (sur $_REQUEST["contents"] et $_REQUEST["title"])
      // TODO: ou montrer un diff ? ou les deux ?
      $cts->add_paragraph($Erreur,"error");
      $cts->add_paragraph("<a href=\"./?name=$pagepath\">Voir la version actuelle.</a>");
      $cts->add_paragraph("<b>Attention</b>: Le texte en cours d'édition corresponds à votre soumission, il ne tient pas compte des modifications apportés par l'autre utilisateur.");
      $cts->add_paragraph("<b>Conseil</b>: Sauvegardez vos modification dans un éditeur de texte, et repartez de la version actuelle.");
    }

    $wiki->lock($site->user);
    $frm = new form("revisewiki","./?name=$pagepath",true,"POST");
    // le true au desus, va dire à form reprendre le bordel soumis,
    // dans le cas où la page a été appelée par la validation du formulaire
    $frm->add_hidden("action","revision");
    $frm->add_hidden("id_rev_last",$wiki->id_rev_last);
    $frm->add_text_field("title","Titre",$wiki->rev_title,true);
    $frm->add_dokuwiki_toolbar("contents");
    $frm->add_text_area("contents","Contenu",$wiki->rev_contents,80,30,true,true);
    $frm->add_text_field("comment","Log","");
    $frm->add_submit("save","Enregistrer");
    $frm->add_submit("save","Annuler");
    $cts->add($frm);
    $cts->puts("<script>wiki_lock_maintain('".$topdir."',".WIKI_LOCKTIME.",'".$pagepath."');</script>");

  }
}
elseif ( $_REQUEST["view"] == "srcs" )
{
  if ( $wiki->rev_id != $wiki->id_rev_last )
    $cts->add_paragraph("Ceci est une version archivée. En date du ".date("d/m/Y H:i",$wiki->rev_date).". ".
    "<a href=\"./?name=$pagepath\">Version actuelle</a>","wikinotice");
  $cts->add_paragraph(nl2br(htmlentities($wiki->rev_contents,ENT_NOQUOTES,"UTF-8")));
}
elseif ( $_REQUEST["view"] == "refs" )
{
  $req = new requete($site->db,"SELECT fullpath_wiki, title_rev ".
    "FROM wiki_ref_wiki ".
    "INNER JOIN wiki ON ( wiki.id_wiki=wiki_ref_wiki.id_wiki_rel) ".
    "INNER JOIN `wiki_rev` ON (".
                      "`wiki`.`id_wiki`=`wiki_rev`.`id_wiki` ".
                      "AND `wiki`.`id_rev_last`=`wiki_rev`.`id_rev` ) ".
                "WHERE wiki_ref_wiki.id_wiki='".$wiki->id."' ".
                "ORDER BY fullpath_wiki");

  if ( $req->lines )
  {
    $list = new itemlist("Cette page fait référence aux pages","wikirefpages");
    while ( $row = $req->get_row() )
    {
      $list->add(
        "<a class=\"wpage\" href=\"?name=".$row['fullpath_wiki']."\">".
        ($row['fullpath_wiki']?$row['fullpath_wiki']:"(racine)")."</a> ".
        " : <span class=\"wtitle\">".htmlentities($row['title_rev'],ENT_NOQUOTES,"UTF-8")."</span> ");
    }
    $cts->add($list,true);
  }

  $req = new requete($site->db,"SELECT fullpath_wiki, title_rev ".
    "FROM wiki_ref_wiki ".
    "INNER JOIN wiki ON ( wiki.id_wiki=wiki_ref_wiki.id_wiki) ".
    "INNER JOIN `wiki_rev` ON (".
                      "`wiki`.`id_wiki`=`wiki_rev`.`id_wiki` ".
                       "AND `wiki`.`id_rev_last`=`wiki_rev`.`id_rev` ) ".
    "WHERE wiki_ref_wiki.id_wiki_rel='".$wiki->id."' ".
    "ORDER BY fullpath_wiki");

  if ( $req->lines )
  {
    $list = new itemlist("Les pages suivantes font référence à cette page","wikirefpages");
    while ( $row = $req->get_row() )
    {
      $list->add(
        "<a class=\"wpage\" href=\"?name=".$row['fullpath_wiki']."\">".
        ($row['fullpath_wiki']?$row['fullpath_wiki']:"(racine)")."</a> ".
        " : <span class=\"wtitle\">".htmlentities($row['title_rev'],ENT_NOQUOTES,"UTF-8")."</span> ");
    }
    $cts->add($list,true);
  }

  $req = new requete($site->db,"SELECT titre_file, nom_fichier_file, d_file.id_file ".
    "FROM wiki_ref_file ".
    "INNER JOIN d_file USING(id_file) ".
    "WHERE wiki_ref_file.id_wiki='".$wiki->id."' ".
    "ORDER BY titre_file");

  if ( $req->lines )
  {
    $list = new itemlist("Cette page fait référence ou utilise les fichiers suivants","wikirefpages");
    while ( $row = $req->get_row() )
    {
      $list->add(
        "<a class=\"wfile\" href=\"".$wwwtopdir."d.php?id_file=".$row['id_file']."\">".
        htmlentities($row['titre_file'],ENT_NOQUOTES,"UTF-8")."</a>  (".$row['nom_fichier_file'].") ");
    }
    $cts->add($list,true);
  }
}
elseif ( $_REQUEST["view"] == "hist" )
{
  $site->add_js("js/wiki.js");
  $req = new requete($site->db,"SELECT ".
    "id_rev, date_rev, comment_rev, id_utilisateur_rev ".
    "FROM wiki_rev ".
    "WHERE id_wiki='".$wiki->id."' ".
    "ORDER BY id_rev DESC");

  $user_hist = new utilisateur($site->db);

  $diff=array();
  while ( $row = $req->get_row() )
  {
    $user_hist->load_by_id($row['id_utilisateur_rev']);
    $diff[]=array('desc'=>"<span class=\"wdate\">".date("Y/m/d H:i",strtotime($row['date_rev']))."</span> ".
                          "<a class=\"wpage\" href=\"?name=$pagepath&amp;rev=".$row['id_rev']."\">$pagename</a> ".
                          "- <span class=\"wuser\">".$user_hist->get_html_link()."</a></span> ".
                          "<span class=\"wlog\">".htmlentities($row['comment_rev'],ENT_NOQUOTES,"UTF-8")."</span><br />",
                  'value'=>$row['id_rev']);
  }
  $frm = new diffwiki ( 'diff', "?name=".$pagepath."&view=diff", $diff, "post", "Historique des révisions");
  $cts->add($frm);
}
elseif ( $_REQUEST["view"] == "diff" )
{
  if(isset($_REQUEST["rev_orig"]) && isset($_REQUEST["rev_comp"]))
  {
    if($wiki->load_by_fullpath_and_rev($_REQUEST["name"],intval($_REQUEST["rev_orig"])))
    {
      $new=array('rev'=>intval($_REQUEST["rev_orig"]),'cts'=>$wiki->rev_contents);
      if($wiki->load_by_fullpath_and_rev($_REQUEST["name"],intval($_REQUEST["rev_comp"])))
      {
        $site->add_css("css/diff.css");
        $old=array('rev'=>intval($_REQUEST["rev_comp"]),'cts'=>$wiki->rev_contents);
        $cts->add(new diff ( $old, $new),true);
      }
      else
        $cts->add(new error('',"Révision non trouvée"));
    }
    else
      $cts->add(new error("Révision non trouvée"));

  }
  else
  {
    if ( $wiki->rev_id != $wiki->id_rev_last )
      $cts->add_paragraph("Ceci est une version archivée. En date du ".date("d/m/Y H:i",$wiki->rev_date).". ".
                          "<a href=\"./?name=$pagepath\">Version actuelle</a>","wikinotice");
    $cts->add($wiki->get_stdcontents());
  }
}
else
{

  if ( $wiki->rev_id != $wiki->id_rev_last )
    $cts->add_paragraph("Ceci est une version archivée. En date du ".date("d/m/Y H:i",$wiki->rev_date).". ".
    "<a href=\"./?name=$pagepath\">Version actuelle</a>","wikinotice");

  $cts->add($wiki->get_stdcontents());

}

$cts->divid = "wikicts";

$site->add_contents($cts);

$site->end_page ();

?>
