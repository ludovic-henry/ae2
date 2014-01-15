<?php
/*
 * FORUM2
 *
 * Copyright 2007 - 2010
 * - Julien Etelain < julien dot etelain at gmail dot com >
 * - Pierre Mauduit <pierre POINT mauduit CHEZ utbm POINT fr>
 * - Benjamin Collet <bcollet at oxynux dot org>
 *
 * Ce fichier fait partie du site de l'Association des Étudiants de
 * l'UTBM, http://ae.utbm.fr/
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
require_once($topdir . "include/entities/asso.inc.php");
require_once($topdir . "include/entities/forum.inc.php");
require_once($topdir . "include/entities/sujet.inc.php");
require_once($topdir . "include/entities/message.inc.php");

require_once($topdir . "include/entities/news.inc.php");
require_once($topdir . "include/entities/sondage.inc.php");
require_once($topdir . "sas2/include/cat.inc.php");

require_once($topdir . "include/cts/forum.inc.php");

$site = new site ();

if (!$site->get_param ("forum_open", false)) {
  if (!$site->user->is_in_group ("moderateur_forum") &&
      !$site->user->is_in_group ("root")) {
    $site->start_page ("forum", "Forum");
    $cts = new contents ("Forum fermé",
        $site->get_param ("forum_message", "Maintenance."));
    $site->add_contents ($cts);
    $site->end_page();
    exit();
  } else {
    $cts = new contents ();
    $cts->add_paragraph ("<b>Attention, forum fermé aux non-modérateurs : ".
        $site->get_param ("forum_message", "Maintenance.")."</b>");
    $site->add_contents ($cts);
  }
}

$site->add_css("css/forum.css");
$site->add_css("css/doku.css");
$site->add_css("css/planning2.css");
$site->add_rss("Les 40 derniers messages du forum de l'AE",
         "rss.php");

if($site->user->is_in_group("ban_forum"))
{
  $site->add_contents(new error("Vous n'avez pas respecté la charte de publication, votre présence n'est désormais plus souhaitée.",false));
  $site->end_page();
  exit();
}


$forum = new forum($site->db,$site->dbrw);
$pforum = new forum($site->db);
$sujet = new sujet($site->db,$site->dbrw);
$message = new message($site->db,$site->dbrw);

// Chargement des objets
if ( isset($_REQUEST["id_message"]) )
{
  $message->load_by_id($_REQUEST["id_message"]);
  if ( $message->is_valid() )
  {
    $sujet->load_by_id($message->id_sujet);
    $forum->load_by_id($sujet->id_forum);
  }
}
elseif ( isset($_REQUEST["id_sujet"]) )
{
  $sujet->load_by_id($_REQUEST["id_sujet"]);
  if ( $sujet->is_valid() )
  {
    $forum->load_by_id($sujet->id_forum);
  }
}
elseif ( isset($_REQUEST["id_forum"]) )
{
  $forum->load_by_id($_REQUEST["id_forum"]);
}
elseif ( isset($_REQUEST["react"]) )
{

  $conds=array();

  if ( isset($_REQUEST["id_nouvelle"]) )
    $conds[]= "(`frm_sujet`.`id_nouvelle`='" . mysql_escape_string($_REQUEST["id_nouvelle"]) . "')";

  if ( isset($_REQUEST["id_catph"]) )
    $conds[]= "(`frm_sujet`.`id_catph`='" . mysql_escape_string($_REQUEST["id_catph"]) . "')";

  if ( isset($_REQUEST["id_sondage"]) )
    $conds[]= "(`frm_sujet`.`id_sondage`='" . mysql_escape_string($_REQUEST["id_sondage"]) . "')";

  if ( count($conds) > 0 )
  {
    $sqlconds = implode(" AND ",$conds);

    if ( $site->user->is_valid() )
    {
      $grps = $site->user->get_groups_csv();
      $req = new requete($site->db,"SELECT frm_sujet.* ".
        "FROM frm_sujet ".
        "INNER JOIN frm_forum USING(`id_forum`) ".
        "WHERE ((droits_acces_forum & 0x1) OR " .
        "((droits_acces_forum & 0x10) AND id_groupe IN ($grps)) OR " .
        "(id_groupe_admin IN ($grps)) OR " .
        "((droits_acces_forum & 0x100) AND id_utilisateur='".$site->user->id."')) ".
        "AND $sqlconds");
    }
    else
      $req = new requete($site->db,"SELECT frm_sujet.* ".
        "FROM frm_sujet ".
        "INNER JOIN frm_forum USING(`id_forum`) ".
        "WHERE (droits_acces_forum & 0x1) ".
        "AND $sqlconds");

    if ( $req->lines > 0 )
    {
      $sujet->_load($req->get_row());
      $forum->load_by_id($sujet->id_forum);
    }
    else
    {
      $forum->load_by_id(3);
      if ( isset($_REQUEST["id_asso"]) && !is_null($_REQUEST["id_asso"]) )
      {
        $req = new requete($site->db,"SELECT * FROM frm_forum WHERE id_asso='".mysql_escape_string($_REQUEST["id_asso"])."' AND categorie_forum=0");
        if ( $req->lines > 0 )
          $forum->_load($req->get_row());
      }
      $_REQUEST["page"]="post";
    }
  }
}

if ( isset($_REQUEST["setnosecret"]) )
  setcookie ("nosecret", $_REQUEST["setnosecret"], time() + 31536000, "/", $domain, 0);


if ( !$forum->is_valid() )
  $forum->load_by_id(1); // Le forum id=1 est la racine

if ( !$forum->is_right($site->user,DROIT_LECTURE) )
{
  $site->error_forbidden("forum");
}

if( isset($_REQUEST['get_preview']) )
{
  $message->titre = html_entity_decode($_REQUEST['title'], ENT_NOQUOTES, 'UTF-8');
  $message->contenu = html_entity_decode($_REQUEST['content'], ENT_NOQUOTES, 'UTF-8');
  $message->id_utilisateur = $_REQUEST['user'];
  $message->syntaxengine = $_REQUEST['syntaxengine'];
  $message->date = time();


  $preview = new simplemessageforum($message);
  header("Content-Type: text/javascript; charset=utf-8");
  echo "<h2>Prévisualisation</h2>";
  echo( $preview->html_render() );
  echo "<h2>Historique</h2>";

  exit();
}

if ( $_REQUEST["action"] == "setallread" )
{
  $site->allow_only_logged_users("forum");
  $site->user->set_all_read( );
  header("Location: ".$wwwtopdir."forum2/index.php");
  exit();
}


/* postage d'un nouveau sujet */
if ( $_REQUEST["action"] == "post" && !$forum->categorie )
{
  $site->allow_only_logged_users("forum");

  $_REQUEST["page"]="post";

  if ( !$_REQUEST["titre_sujet"] )
    $Erreur="Veuillez préciser un titre";

  elseif ( !$_REQUEST["subjtext"] )
    $Erreur="Veuillez saisir le texte du message";

  elseif ( $GLOBALS['svalid_call'] )
  {

    $type=SUJET_NORMAL;
    $date_fin_annonce=null;

    if ( $forum->is_admin($site->user) )
    {
      $type = $_REQUEST["subj_type"];
      if ( $type == SUJET_ANNONCESITE &&
        !$site->user->is_in_group("moderateur_forum") &&
        !$site->user->is_in_group("root") )
      {
        $type = SUJET_ANNONCE;
        $date_fin_annonce=$_REQUEST["date_fin_announce_site"];
      }
      elseif ( $type == SUJET_ANNONCE )
        $date_fin_annonce=$_REQUEST["date_fin_announce"];

      elseif ( $type == SUJET_ANNONCESITE )
        $date_fin_annonce=$_REQUEST["date_fin_announce_site"];
    }

    $news = new nouvelle($site->db);
    $catph = new catphoto($site->db);
    $sdn = new sondage($site->db);

    if ( isset($_REQUEST["id_nouvelle"]) )
      $news->load_by_id($_REQUEST["id_nouvelle"]);

    elseif ( isset($_REQUEST["id_catph"]) )
      $catph->load_by_id($_REQUEST["id_catph"]);

    elseif ( isset($_REQUEST["id_sondage"]) )
      $sdn->load_by_id($_REQUEST["id_sondage"]);

    $sujet->create ( $forum, $site->user->id, $_REQUEST["titre_sujet"], $_REQUEST["soustitre_sujet"],
        $type,null,$date_fin_annonce,
        $news->id,$catph->id,$sdn->id );

    $subjtext = $message->commit_replace($_REQUEST['subjtext'],$site->user);

    $message->create($forum,
            $sujet,
            $site->user->id,
            $_REQUEST['titre_sujet'],
            $subjtext,
            $_REQUEST['synengine']);

    if ( isset($_REQUEST['star']) )
      $sujet->set_user_star($site->user->id,true);

  }
}

if ( $_REQUEST['page'] == 'delete' )
{
  $site->allow_only_logged_users("forum");
  

  if ( $message->is_valid() )
  {
    $user = new utilisateur($site->db);
    $user->load_by_id($message->id_utilisateur);

	if ( !isset($_POST["___i_am_really_sure"]) && !isset($_POST["___finally_i_want_to_cancel"]) )
	  {
	    $site->start_page($section,"Êtes vous sûr ?");

	    $cts = new contents("Confirmation");

	    $cts->add_paragraph("Suppression du message ".$message->id." de ".$user->prenom." ".$user->nom." du ".human_date($message->date).".");

	    $cts->add_paragraph("Êtes vous sûr ?");

	    $frm = new form("suppressmess","?");
	    $frm->allow_only_one_usage();
	     foreach ( $_POST as $key => $val )
	      if ( $key != "magicform" )
	      {
		if($key=="__script__")
		  $frm->add_hidden($key,htmlspecialchars($val));
		else if (is_array($val))
		{
		  foreach ( $val as $k => $v )
		    $frm->add_hidden($key.'['.$k.']',$v);
		}
		else
		  $frm->add_hidden($key,$val);
	      }
	    foreach ( $_GET as $key => $val )
	      if ( $key != "magicform" )
	      {
		if (is_array($val))
		{
		  foreach ( $val as $k => $v )
		    $frm->add_hidden($key.'['.$k.']',$v);
		}
		else
		  $frm->add_hidden($key,$val);
	      }

	    if($message->id_utilisateur != $site->user->id)
		    $frm->add_text_area("raison",
			"Raison de la modération (obligatoire)",
			"",40,4,true, true);

	    $frm->add_submit("___i_am_really_sure","Valider");
	    $frm->add_submit("___finally_i_want_to_cancel","Annuler");

	    $cts->add($frm);

	    $site->add_contents($cts);

	    $site->end_page();
	    exit();
	  }
	elseif ((($forum->is_admin($site->user)) || ($message->id_utilisateur == $site->user->id))
      && isset($_POST["___i_am_really_sure"]))
	{
	  $raison = trim($_REQUEST["raison"]); 
	  if( empty($raison) && ($message->id_utilisateur != $site->user->id))
	  {
                $cts = new contents("Raison manquante",
                        "La raison pour la suppression est obligatoire.");
		$site->add_contents($cts);
		$site->end_page();
		exit();
	  }
      $message_initial = new message($site->db);
      $message_initial->load_initial_of_sujet($sujet->id);

      if ( $message_initial->id == $message->id ) // La supression du message initial, entraine la supression du sujet
        $sujet->delete($forum);
      else
        $ret =$message->delete($forum, $sujet, $site->user->id);

      $utl_concerne = new utilisateur($site->db);
      $utl_concerne->load_by_id($message->id_utilisateur);
      if($utl_concerne->is_valid() && $message->id_utilisateur != $site->user->id)
	      $utl_concerne->send_email("Suppression d'un de vos message",
		      "Votre message\n\n\"$message->contenu\"\n\na été modéré par "
		      .$site->user->prenom." ".$site->user->nom." pour la raison".
		      " suivante:\n\n$raison");

      $cts = new contents("Suppression d'un message",
        "Message supprimé avec succès.");
    }
    else
      $cts = new contents("Suppression d'un message",
        "Vous n'avez pas les autorisations nécessaires pour supprimer ce message.");

    $site->add_contents($cts);
  }
  elseif ( $sujet->is_valid() )
  {
    $user = new utilisateur($site->db);
    $user->load_by_id($sujet->id_utilisateur);

    if ((($forum->is_admin($site->user))
        || ($sujet->id_utilisateur == $site->user->id))
        && $site->is_sure("", "Suppression du sujet ".$sujet->id." de ".$user->prenom." ".$user->nom." du ".human_date($sujet->date).". Ceci est irréversible.", 1))
    {
      $ret =$sujet->delete($forum, $site->user->id);
      $cts = new contents("Suppression d'un sujet",
        "Sujet supprimé avec succès.");
    }
    else
      $cts = new contents("Suppression d'un Sujet",
        "Vous n'avez pas les autorisations nécessaires pour supprimer ce sujet.");

    $site->add_contents($cts);
  }
}
if ( $_REQUEST['page'] == 'undelete' )
{
  $site->allow_only_logged_users("forum");
  if ( $message->is_valid() )
  {
    $user = new utilisateur($site->db);
    $user->load_by_id($message->id_utilisateur);

      if ($site->user->is_in_group("moderateur_forum")
      && $site->is_sure("", "Rétablir le message ".$message->id." de ".$user->prenom." ".$user->nom." du ".human_date($message->date).".", 1))
    {
      $ret =$message->undelete($forum, $sujet, $site->user->id);

      $cts = new contents("Suppression d'un message",
        "Message supprimé avec succès.");
    }
    else
      $cts = new contents("Suppression d'un message",
        "Vous n'avez pas les autorisations nécessaires pour rétablir ce message.");

    $site->add_contents($cts);
  }
}

if ( $sujet->is_valid() )
  $path = $forum->get_html_link()." / ".$sujet->get_html_link();
else
  $path = $forum->get_html_link();

$pforum->load_by_id($forum->id_forum_parent);
while ( $pforum->is_valid() )
{
  $path = $pforum->get_html_link()." / ".$path;
  $pforum->load_by_id($pforum->id_forum_parent);
}

if ( $sujet->is_valid() )
{
  if ($_REQUEST['action'] == 'star')
  {
    $site->allow_only_logged_users("forum");
    $sujet->set_user_star($site->user->id,true);
  }
  elseif ($_REQUEST['action'] == 'unstar')
  {
    $site->allow_only_logged_users("forum");
    $sujet->set_user_star($site->user->id,false);
  }
  elseif ($_REQUEST['page'] == 'edit')
  {
    $site->allow_only_logged_users("forum");

    if ( $message->is_valid() ) // On edite un message
    {
      if ($message->id_utilisateur != $site->user->id && !$forum->is_admin($site->user))
        $site->error_forbidden("forum","group");

      $site->start_page("forum",$sujet->titre);

      $frm = new form("frmedit",
          "?page=commitedit&amp;id_sujet=".
          $sujet->id."&amp;".
          "id_message=".$message->id."#msg".$message->id,
          true);

      $frm->add_text_field("title", "Titre du message : ", $message->titre,false,80);
      $frm->add_select_field('synengine',
           'Moteur de rendu : ',
           array('bbcode' => 'bbcode (type phpBB)','doku' => 'Doku Wiki (recommandé)'),
           $message->syntaxengine);
      if ( $message->syntaxengine == "doku" )
        $frm->add_dokuwiki_toolbar('text',$forum->id_asso,null,true);
      $frm->add_text_area("text", "Texte du message : ",$message->contenu,80,20);
      $frm->add_submit("submit", "Modifier");
      $frm->puts("<div class=\"formrow\"><div class=\"formlabel\"></div><div class=\"formfield\"><input type=\"button\" id=\"preview\" name=\"preview\" value=\"Prévisualiser\" class=\"isubmit\" onClick=\"javascript:make_preview();\" /></div></div>\n");
      $frm->allow_only_one_usage();

      $cts = new contents($path." / Edition");

      $cts->add_paragraph("<script language=\"javascript\">
      function make_preview()
      {
        title = document.frmedit.title.value;
        content = document.frmedit.text.value;
        user = ".$site->user->id.";
        syntaxengine = document.frmedit.synengine.value;

        openInContents('msg_preview', './index.php', 'get_preview&title='+encodeURIComponent(title)+'&content='+encodeURIComponent(content)+'&user='+user+'&syntaxengine='+syntaxengine);
      }
      </script>\n");

      $cts->add($frm);

      $cts->puts("<div id=\"msg_preview\"></div>");

      $site->add_contents($cts);
      $site->end_page();
      exit();
    }

    // On edite le sujet

    if ($sujet->id_utilisateur != $site->user->id && !$forum->is_admin($site->user))
      $site->error_forbidden("forum","group");

    // Recupération du premier message du sujet
    $message->load_initial_of_sujet($sujet->id);

    $site->start_page("forum",$sujet->titre);
    $cts = new contents($path." / Edition");

    $frm = new form("frmedit",
        "?page=commitedit&amp;id_sujet=".$sujet->id,
        true);

    if ( $forum->is_admin($site->user) )
    {
      if ( !$sujet->type )
        $sujet->type = SUJET_NORMAL;

      $sfrm = new form("subj_type",null,null,null,"Sujet normal");
      $frm->add($sfrm,false,true, $sujet->type==SUJET_NORMAL ,SUJET_NORMAL ,false,true);

      $sfrm = new form("subj_type",null,null,null,"Sujet épinglé, il sera toujours affiché en haut");
      $frm->add($sfrm,false,true, $sujet->type==SUJET_STICK ,SUJET_STICK ,false,true);

      $sfrm = new form("subj_type",null,null,null,"Annonce, le message sera affiché en haut dans un cadre séparé");
      $sfrm->add_datetime_field('date_fin_announce',
             'Date de fin de l\'annonce',
             $sujet->date_fin_annonce);
      $frm->add($sfrm,false,true, $sujet->type==SUJET_ANNONCE ,SUJET_ANNONCE ,false,true);

      if ( $site->user->is_in_group("moderateur_forum") || $site->user->is_in_group("root") )
      {
        $sfrm = new form("subj_type",null,null,null,"Annonce du site, le message sera affiché en haut sur la première page du forum");
        $sfrm->add_datetime_field('date_fin_announce_site',
               'Date de fin de l\'annonce',
               $sujet->date_fin_annonce);
        $frm->add($sfrm,false,true, $sujet->type==SUJET_ANNONCESITE ,SUJET_ANNONCESITE ,false,true);
      }
    }

    /**
     * @todo : edition des metas données
     */
    $forum_cats = array();
    $sql = "SELECT id_forum, titre_forum FROM frm_forum ORDER BY titre_forum";
    $req = new requete($site->db, $sql);
    while( list($value,$name) = $req->get_row()){
      $forum_cats[$value] = $name;
    }

    $frm->add_select_field('id_dst_forum', 'Forum : ', $forum_cats, $sujet->id_forum);
    $frm->add_text_field("titre", "Titre : ", $sujet->titre,true,80);
    $frm->add_text_field("soustitre","Sous-titre du message (optionel) : ",$sujet->soustitre,false,80);

    $frm->add_select_field('synengine',
         'Moteur de rendu : ',
         array('bbcode' => 'bbcode (type phpBB)','doku' => 'Doku Wiki (recommandé)'),
         $message->syntaxengine);
    $frm->add_dokuwiki_toolbar('text',$forum->id_asso,null,true);
    $frm->add_text_area("text", "Texte du message : ",$message->contenu,80,20);
    $frm->add_submit("submit", "Modifier");
    $frm->puts("<div class=\"formrow\"><div class=\"formlabel\"></div><div class=\"formfield\"><input type=\"button\" id=\"preview\" name=\"preview\" value=\"Prévisualiser\" class=\"isubmit\" onClick=\"javascript:make_preview();\" /></div></div>\n");
    $frm->allow_only_one_usage();

    $cts->add_paragraph("<script language=\"javascript\">
      function make_preview()
      {
        title = document.frmedit.title.value;
        content = document.frmedit.text.value;
        user = ".$site->user->id.";
        syntaxengine = document.frmedit.synengine.value;

        openInContents('msg_preview', './index.php', 'get_preview&title='+encodeURIComponent(title)+'&content='+encodeURIComponent(content)+'&user='+user+'&syntaxengine='+syntaxengine);
      }
      </script>\n");

    $cts->add($frm);
    $cts->puts("<div id=\"msg_preview\"></div>");

    /**@todo*/
    $site->add_contents($cts);
    $site->end_page();

    exit();
  }

  if ($_REQUEST['page'] == 'commitedit')
  {
    $site->allow_only_logged_users("forum");

    //$site->start_page("forum",$sujet->titre);
    if ( $message->is_valid() )
    {
      if ((($message->id_utilisateur == $site->user->id)
        || ($forum->is_admin($site->user)))
        && ($GLOBALS['svalid_call'] == true))
      {
        $text = $message->commit_replace($_REQUEST['text'],$site->user);
        $ret = $message->update($forum,
              $sujet,
              $_REQUEST['title'],
              $text,
              $_REQUEST['synengine'], $site->user->id);
        $cts = new contents("Modification d'un message", "Message modifié");
      }
      else
        $cts = new contents("Modification d'un message",
          "Erreur lors de la modification du message. Assurez-vous d'avoir les privilèges suffisants.");

      $site->add_contents($cts);
    }
    elseif ($GLOBALS['svalid_call'] == true)
    {
      if ($sujet->id_utilisateur != $site->user->id && !$forum->is_admin($site->user))
        $site->error_forbidden("forum","group");

      $message->load_initial_of_sujet($sujet->id);

      $text = $message->commit_replace($_REQUEST['text'],$site->user);

      $message->update($forum,
              $sujet,
              $_REQUEST['titre'],
              $text,
              $_REQUEST['synengine'], $site->user->id);

      $type=SUJET_NORMAL;
      $date_fin_annonce=null;

      if ( $forum->is_admin($site->user) )
      {
        $type = $_REQUEST["subj_type"];
        if ( $type == SUJET_ANNONCESITE &&
          !$site->user->is_in_group("moderateur_forum") &&
          !$site->user->is_in_group("root") )
        {
          $type = SUJET_ANNONCE;
          $date_fin_annonce=$_REQUEST["date_fin_announce_site"];
        }
        elseif ( $type == SUJET_ANNONCE )
          $date_fin_annonce=$_REQUEST["date_fin_announce"];

        elseif ( $type == SUJET_ANNONCESITE )
          $date_fin_annonce=$_REQUEST["date_fin_announce_site"];

        if ($_REQUEST['id_dst_forum'] != $message->id_forum)
        {
          $dst_forum = new forum($site->db);
          $dst_forum->load_by_id($_REQUEST['id_dst_forum']);
          $sujet->move_to($forum, $dst_forum);
          $forum = $dst_forum;
        }
      }

      $sujet->update ($_REQUEST["titre"], $_REQUEST["soustitre"],
          $type,null,$date_fin_annonce,
          $sujet->id_nouvelle,$sujet->id_catph,$sujet->id_sondage );

    }
    //$site->end_page();
    //exit();
  }

  if ( $_REQUEST["page"] == "reply" )
  {
    $site->allow_only_logged_users("forum");

    $site->start_page("forum",$sujet->titre);

    $cts = new contents($path." / <a href=\"?id_sujet=".$sujet->id."&amp;page=reply\">Répondre</a>");

    $cts->add_paragraph("<script language=\"javascript\">
      function make_preview()
      {
        title = document.frmreply.rpltitle.value;
        content = document.frmreply.rpltext.value;
        user = ".$site->user->id.";
        syntaxengine = document.frmreply.synengine.value;

        openInContents('msg_preview', './index.php', 'get_preview&title='+encodeURIComponent(title)+'&content='+encodeURIComponent(content)+'&user='+user+'&syntaxengine='+syntaxengine);
      }
      </script>\n");

    /* formulaire d'invite à postage de réponse */
    $frm = new form("frmreply", "?page=commit&amp;id_sujet=".$sujet->id."#lastmessage", true);

    if (intval($_REQUEST['quote']) == 1)
    {
      $_auteur="";
      /* l'objet message doit alors etre chargé */
      if($message->id_utilisateur>0)
      {
        $_auteur=new utilisateur($site->db,$site->dbrw);
        $_auteur->load_by_id($message->id_utilisateur);
        if(!is_null($_auteur->id)){
          $req = new requete($site->db, "SELECT * FROM `utl_etu_utbm` WHERE `id_utilisateur` =".$_auteur->id." ;");
          $_auteur->_load_extras($req->get_row());
          $_auteur="=".($_auteur->surnom!=null ? $_auteur->surnom : $_auteur->alias);
        }
      }

      $rpltext = "[quote".$_auteur."]".$message->contenu . "[/quote]";
      $rpltitle = "Re : " . $message->titre;
    }
    else
    {
      $rpltext = '';
      $rpltitle = '';
    }

    $frm->add_text_field("rpltitle", "Titre du message : ", $rpltitle,false,80);
    $frm->add_select_field('synengine',
         'Moteur de rendu : ',
         array('bbcode' => 'bbcode (type phpBB)','doku' => 'Doku Wiki (recommandé)'),'doku');
    $frm->add_dokuwiki_toolbar('rpltext',$forum->id_asso,null,true);
    $frm->add_text_area("rpltext", "Texte du message : ",$rpltext,80,20);
    $frm->add_checkbox ( "star", "Ajouter à mes sujets favoris.", true );
    $frm->add_submit("rplsubmit", "Poster");
    $frm->puts("<div class=\"formrow\"><div class=\"formlabel\"></div><div class=\"formfield\"><input type=\"button\" id=\"preview\" name=\"preview\" value=\"Prévisualiser\" class=\"isubmit\" onClick=\"javascript:make_preview();\" /></div></div>\n");

    $frm->allow_only_one_usage();
    $cts->add($frm);

    $cts->puts("<div id=\"msg_preview\"></div>");

    $npp=40;
    $nbpages = ceil($sujet->nb_messages / $npp);
    $start = ($nbpages - 1) * $npp;

    $cts->add(new sujetforum ($forum,
          $sujet,
          $site->user,
          "./",
          0,
          40,
          "DESC" ));


    $site->add_contents($cts);
    $site->end_page();
    exit();
  }


  /* réponse postée */
  if ($_REQUEST['page'] == 'commit')
  {
    $site->allow_only_logged_users("forum");

    if ( isset($_REQUEST['star']) )
      $sujet->set_user_star($site->user->id,true);

    $site->start_page("forum",$sujet->titre);

    $cts = new contents($path.
      " / <a href=\"?id_sujet=".
      $sujet->id.
      "&amp;page=reply\">Répondre</a>");

    /*  sujet */

    /* nombre de posts par page */
    $npp=40;

    $rpltext = $message->commit_replace($_REQUEST['rpltext'],$site->user);

    if (($GLOBALS['svalid_call'] == true) && ($_REQUEST['rpltext'] != ''))
      $retpost = $message->create($forum,
            $sujet,
            $site->user->id,
            $_REQUEST['rpltitle'],
            $rpltext,
            $_REQUEST['synengine']);
    else
      $retpost = false;

    /* nombre de pages */
    $nbpages = ceil($sujet->nb_messages / $npp);
    /* on va à la derniere */
    $start = ($nbpages - 1) * $npp;

    $cts->add(new sujetforum ($forum,
      $sujet,
      $site->user,
      "./",
      $start,
      $npp));

    if ($retpost == true)
      $answ = new contents("Poster une réponse",
           "<b>Réponse postée avec succès.</b>");
    else
      $answ = new contents("Poster une réponse",
           "<b>Echec lors de la tentative de postage de la réponse.</b>");

    if ($GLOBALS['svalid_call'] == false)
      $answ->add_paragraph('Votre réponse a déjà été postée.');

    $site->add_contents($answ);

    for($n=0 ; $n<$nbpages ; $n++)
      $entries[]=array($n,"forum2/?id_sujet=".$sujet->id."&spage=".$n,$n+1);

    $cts->add(new tabshead($entries, floor($start/$npp), "_bottom"));

    $cts->add_paragraph("<a href=\"?id_sujet=".$sujet->id."&amp;page=reply\"><img src=\"".$wwwtopdir."images/icons/16/message.png\" class=\"icon\" alt=\"\" />Répondre</a>","frmtools");
    $cts->add_paragraph($path);

    $site->add_contents($cts);

    $site->end_page();
    exit();
  }


  $site->start_page("forum",$sujet->titre);

  $cts = new contents($path);

  $npp=40;
  $start=0;
  $delta=0;
  $nbpages = ceil($sujet->nb_messages/$npp);

  if ( isset($_REQUEST["spage"]) && $_REQUEST["spage"] == "firstunread" && $site->user->is_valid() )
  {
    $last_read = $sujet->get_last_read_message ( $site->user->id );
    if ( !is_null($last_read) )
    {
      $message->load_by_id($last_read);
      $delta=1;
    }
    elseif( !is_null($site->user->tout_lu_avant) )
    {
      $req = new requete($site->db,"SELECT id_message FROM frm_message ".
        "WHERE id_sujet='".mysql_real_escape_string($sujet->id)."' ".
        "AND date_message > '".date("Y-m-d H:i:s",$site->user->tout_lu_avant)."' ".
        "ORDER BY date_message LIMIT 1");
      if ( $req->lines == 1 )
      {
        list($last_read) = $req->get_row();
        $message->load_by_id($last_read);
        $delta=0;
      }
    }
    unset($_REQUEST["spage"]);
  }

  if ( $message->is_valid() )
  {
    $req = new requete($site->db,"SELECT id_message FROM frm_message WHERE id_sujet='"
	.mysql_real_escape_string($sujet->id).
	"' AND msg_supprime='0' ORDER BY date_message");

    $ids = array();
    while ( list($id) = $req->get_row() )
      $ids[] = $id;

    list($start) = array_keys($ids, $message->id);
    $start += $delta;
    $start -= $start%$npp;
  }
  elseif ( isset($_REQUEST["spage"]) )
  {
    $start = intval($_REQUEST["spage"])*$npp;
    if ( $start > $sujet->nb_messages )
    {
      $start = $sujet->nb_messages;
      $start -= $start%$npp;
    }
  }

  /**@todo:bouttons+infos*/

  $buttons= "<a href=\"?id_sujet=".$sujet->id."&amp;page=reply\"><img src=\"".$wwwtopdir."images/icons/16/message.png\" class=\"icon\" alt=\"\" />Répondre</a>";

  if ( $site->user->is_valid() )
  {
    $row = $sujet->get_user_infos($site->user->id);
    if ( is_null($row) || !$row['etoile_sujet'] )
      $buttons .= " <a href=\"?id_sujet=".$sujet->id."&amp;action=star\"><img src=\"".$wwwtopdir."images/icons/16/star.png\" class=\"icon\" alt=\"\" />Ajouter aux sujets favoris</a>";
    else
      $buttons .= " <a href=\"?id_sujet=".$sujet->id."&amp;action=unstar\"><img src=\"".$wwwtopdir."images/icons/16/unstar.png\" class=\"icon\" alt=\"\" />Enlever des sujets favoris</a>";
  }

  $cts->add_paragraph($buttons,"frmtools");

  if ( $start == 0 )
  {
    if ( !is_null($sujet->id_sondage) )
    {
      $sdn = new sondage($site->db);
      $sdn->load_by_id($sujet->id_sondage);

      $cts->puts("<div class=\"sujetcontext\">");

      $cts->add_title(2,"Sondage : resultats");

      $cts->add_paragraph($sdn->question);

      $cts->puts("<p>");

      $res = $sdn->get_results();

      foreach ( $res as $re )
      {
        $cumul+=$re[1];
        $pc = $re[1]*100/$sdn->total;

        $cts->puts($re[0]."<br/>");

        $wpx = floor($pc);
        if ( $wpx != 0 )
          $cts->puts("<div class=\"activebar\" style=\"width: ".$wpx."px\"></div>");
        if ( $wpx != 100 )
          $cts->puts("<div class=\"inactivebar\" style=\"width: ".(100-$wpx)."px\"></div>");

        $cts->puts("<div class=\"percentbar\">".round($pc,1)."%</div>");
        $cts->puts("<div class=\"clearboth\"></div>\n");

      }

      if ( $cumul < $sdn->total )
      {
        $pc = ( $sdn->total-$cumul)*100/$sdn->total;
        $cts->puts("<br/>Blanc ou nul : ".round($pc,1)."%");
      }
      $cts->puts("</p>");
      $cts->puts("</div>");
    }
    if ( !is_null($sujet->id_nouvelle) )
    {
      $news = new nouvelle ($site->db);
      $news->load_by_id($sujet->id_nouvelle);
      $cts->add($news->get_contents(false),true,true,"newsboxed","sujetcontext");
    }
    if ( !is_null($sujet->id_catph) )
    {
      $cat = new catphoto($site->db);
      $catpr = new catphoto($site->db);
      $cat->load_by_id($sujet->id_catph);

      $path = $cat->get_html_link();
      $catpr->load_by_id($cat->id_catph_parent);
      while ( $catpr->is_valid() )
      {
        $path = $catpr->get_html_link()." / ".$path;
        $catpr->load_by_id($catpr->id_catph_parent);
      }

      if ( !$cat->is_right($site->user,DROIT_LECTURE) )
      {
        $cts->add(new contents($path),true,true,"sasboxed","sujetcontext");
      }
      else
      {
        require_once($topdir."include/cts/gallery.inc.php");
        $site->add_css("css/sas.css");

        $sqlph = $cat->get_photos ( $cat->id, $site->user, $site->user->get_groups_csv(), "sas_photos.*", " LIMIT 5");

        $gal = new gallery($path,"photos","phlist","../sas2/?id_catph=".$cat->id,"id_photo",array());
        while ( $row = $sqlph->get_row() )
        {
          $img = "../sas2/images.php?/".$row['id_photo'].".vignette.jpg";
          if ( $row['type_media_ph'] == 1 )
            $gal->add_item("<a href=\"../sas2/?id_photo=".$row['id_photo']."\"><img src=\"$img\" alt=\"Photo\">".
                "<img src=\"".$wwwtopdir."images/icons/32/multimedia.png\" alt=\"Video\" class=\"ovideo\" /></a>","");
          else
            $gal->add_item("<a href=\"../sas2/?id_photo=".$row['id_photo']."\"><img src=\"$img\" alt=\"Photo\"></a>","");
        }

        $img = $topdir."images/misc/sas-default.png";

        if ( $cat->id_photo )
          $img = "../sas2/images.php?/".$cat->id_photo.".vignette.jpg";

        $gal->add_item("<a href=\"../sas2/?id_catph=".$cat->id."\"><img src=\"$img\" alt=\"Photo\"></a>",$cat->nom." : suite..." );

        $cts->add($gal,true,true,"sasboxed","sujetcontext");
      }
    }
  }


  $entries=array();

  for( $n=0;$n<$nbpages;$n++)
    $entries[]=array($n,"forum2/?id_sujet=".$sujet->id."&spage=".$n,$n+1);

  $cts->add(new tabshead($entries, floor($start/$npp), "_top"));

  $cts->add(new sujetforum ($forum,
          $sujet,
          $site->user,
          "./",
          $start,
          $npp ));

  $cts->add(new tabshead($entries, floor($start/$npp), "_bottom"));

  $cts->add_paragraph($buttons,"frmtools");

  $cts->add_paragraph($path);


  if ( $site->user->is_valid() )
  {
    $num = $start+$npp-1;
    if ( $num >= $sujet->nb_messages )
      $max_id_message = null;
    else
    {
      $req = new requete($site->db,"SELECT id_message FROM frm_message WHERE id_sujet='".mysql_real_escape_string($sujet->id)."' AND msg_supprime='0' ORDER BY date_message LIMIT $num,1");
      list($max_id_message) = $req->get_row();
    }

    $sujet->set_user_read ( $site->user->id, $max_id_message );
  }

  $site->add_contents($cts);

  $site->end_page();
  exit();
}

if ( $_REQUEST["page"] == "post" && !$forum->categorie )
{
  $site->allow_only_logged_users("forum");

  $site->start_page("forum", $forum->titre);

  $cts = new contents($path." / Nouveau sujet");

  /* formulaire d'invite à postage de nouveau sujet */
  $frm = new form("newsbj","?id_forum=".$forum->id,
      true);

  $frm->add_hidden("action","post");

  $frm->allow_only_one_usage();

  if ( isset($Erreur) )
    $frm->error($Erreur);

  if ( $forum->is_admin($site->user) )
  {
    $type=SUJET_NORMAL;

    $sfrm = new form("subj_type",null,null,null,"Sujet normal");
    $frm->add($sfrm,false,true, $type==SUJET_NORMAL ,SUJET_NORMAL ,false,true);

    $sfrm = new form("subj_type",null,null,null,"Sujet épinglé, il sera toujours affiché en haut");
    $frm->add($sfrm,false,true, $type==SUJET_STICK ,SUJET_STICK ,false,true);

    $sfrm = new form("subj_type",null,null,null,"Annonce, le message sera affiché en haut dans un cadre séparé");
    $sfrm->add_datetime_field('date_fin_announce',
           'Date de fin de l\'annonce',
           time()+(7*24*60*60));
    $frm->add($sfrm,false,true, $type==SUJET_ANNONCE ,SUJET_ANNONCE ,false,true);

    if ( $site->user->is_in_group("moderateur_forum") || $site->user->is_in_group("root") )
    {
      $sfrm = new form("subj_type",null,null,null,"Annonce du site, le message sera affiché en haut sur la première page du forum");
      $sfrm->add_datetime_field('date_fin_announce_site',
             'Date de fin de l\'annonce',
             time()+(7*24*60*60));
      $frm->add($sfrm,false,true, $type==SUJET_ANNONCESITE ,SUJET_ANNONCESITE ,false,true);
    }
  }

  /* on part du principe qu'un sujet est nécessairement initié par
   * un message */

  /* choix d'une icone ? */
  /* TODO : à définir */

  /* choix d'une news de référence
   * id sondage concerné
   * => A ne supporter que si les IDs passés en paramètre
   */

  if ( isset($_REQUEST["id_nouvelle"]) )
  {
    $news = new nouvelle($site->db);
    $news->load_by_id($_REQUEST["id_nouvelle"]);
    if ( $news->is_valid() )
    {
      $frm->add_hidden("id_nouvelle",$news->id);
      $frm->add_info("<b>En reaction de la nouvelle</b> : ".$news->get_html_link());
    }
  }
  elseif ( isset($_REQUEST["id_catph"]) )
  {
    $catph = new catphoto($site->db);
    $catph->load_by_id($_REQUEST["id_catph"]);
    if ( $catph->is_valid() )
    {
      $frm->add_hidden("id_catph",$catph->id);
      $frm->add_info("<b>En reaction de la catégorie du SAS</b> : ".$catph->get_html_link());
    }
  }
  elseif ( isset($_REQUEST["id_sondage"]) )
  {
    $sdn = new sondage($site->db);
    $sdn->load_by_id($_REQUEST["id_sondage"]);
    if ( $sdn->is_valid() )
    {
      $frm->add_hidden("id_sondage",$sdn->id);
      $frm->add_info("<b>En reaction du sondage</b> : ".$sdn->get_html_link());
    }
  }

  /* titre du sujet */
  $frm->add_text_field("titre_sujet",
           "Titre du message : ",$_REQUEST["titre_sujet"],true,80);
  /* sous-titre du sujet */
  $frm->add_text_field("soustitre_sujet",
           "Sous-titre du message (optionel) : ","",false,80);
  /* moteur de rendu */
  $frm->add_select_field('synengine',
       'Moteur de rendu : ',
       array('bbcode' => 'bbcode (type phpBB)',
             'doku' => 'Doku Wiki (recommandé)'),'doku');

  /* texte du message initiateur */
  $frm->add_dokuwiki_toolbar('subjtext',$forum->id_asso,null,true);
  $frm->add_text_area("subjtext", "Texte du message : ","",80,20);
  /* et hop ! */

  $frm->add_checkbox ( "star", "Ajouter à mes sujets favoris.", true );


  $frm->add_submit("subjsubmit", "Poster");
  $frm->puts("<div class=\"formrow\"><div class=\"formlabel\"></div><div class=\"formfield\"><input type=\"button\" id=\"preview\" name=\"preview\" value=\"Prévisualiser\" class=\"isubmit\" onClick=\"javascript:make_preview();\" /></div></div>\n");
  $cts->add_paragraph("<script language=\"javascript\">
      function make_preview()
      {
        title = document.newsbj.titre_sujet.value;
        content = document.newsbj.subjtext.value;
        user = ".$site->user->id.";
        syntaxengine = document.newsbj.synengine.value;

        openInContents('msg_preview', './index.php', 'get_preview&title='+encodeURIComponent(title)+'&content='+encodeURIComponent(content)+'&user='+user+'&syntaxengine='+syntaxengine);
      }
      </script>\n");
  $cts->add($frm);
  $cts->puts("<div id=\"msg_preview\"></div>");
  $site->add_contents($cts);

  $site->end_page();

  exit();
}
elseif ( $_REQUEST["page"] == "edit" && $forum->is_admin($site->user) )
{
  $asso = new asso($site->db);
  $asso->load_by_id($forum->id_asso);

  $site->start_page("forum", $forum->titre);
  $cts = new contents($path." / Editer");

  $frm = new form("editfrm","?id_forum=".$forum->id);
  $frm->add_hidden("action","edit");
  $frm->add_text_field("titre","Titre",$forum->titre);
  $frm->add_text_field("ordre","Numéro d'ordre",$forum->ordre);
  $frm->add_entity_smartselect ("id_asso", "Association/Club lié",$asso, true);
  $frm->add_checkbox ( "categorie", "Catégorie", $forum->categorie );
  $frm->add_text_area("description","Description",$forum->description);
  $frm->add_rights_field($forum,false,$forum->is_admin($site->user));
  $frm->add_submit("rec","Enregistrer");
  $cts->add($frm);

  $site->add_contents($cts);
  $site->end_page();
  exit();
}
elseif ( $_REQUEST["action"] == "edit" && $forum->is_admin($site->user) )
{
  $asso = new asso($site->db);
  $asso->load_by_id($_REQUEST["id_asso"]);
  $forum->set_rights($site->user,$_REQUEST['rights'],$_REQUEST['rights_id_group'],$_REQUEST['rights_id_group_admin'],false);
  $forum->update ( $_REQUEST["titre"], $_REQUEST["description"], $_REQUEST["categorie"], $forum->id_forum_parent, $asso->id, $_REQUEST["ordre"] );
}

$site->start_page("forum",$forum->titre);

$cts = new contents($path);

if ( $forum->is_admin($site->user) )
  $cts->set_toolbox(new toolbox(array("?page=edit&id_forum=".$forum->id=>"Editer")));

if ( $forum->categorie )
{
// Liste des sous-forums

  if ( $forum->id == 1 && $site->user->is_valid() )
  {
   /*$cts->add_paragraph("<a href=\"./search.php?page=unread\">Voir tous les messages non lu</a>","frmgeneral");
   $cts->add_paragraph("<a href=\"./?action=setallread\">Marquer tous les messages comme lu</a>","frmgeneral");*/



    $query = "SELECT COUNT(*) " .
        "FROM frm_sujet " .
        "INNER JOIN frm_forum USING(id_forum) ".
        "LEFT JOIN frm_message ON ( frm_message.id_message = frm_sujet.id_message_dernier ) " .
        "LEFT JOIN frm_sujet_utilisateur ".
          "ON ( frm_sujet_utilisateur.id_sujet=frm_sujet.id_sujet ".
          "AND frm_sujet_utilisateur.id_utilisateur='".$site->user->id."' ) ".
        "WHERE ";

    if( is_null($site->user->tout_lu_avant))
      $query .= "(frm_sujet_utilisateur.id_message_dernier_lu<frm_sujet.id_message_dernier ".
                "OR frm_sujet_utilisateur.id_message_dernier_lu IS NULL) ";
    else
      $query .= "((frm_sujet_utilisateur.id_message_dernier_lu<frm_sujet.id_message_dernier ".
                "OR frm_sujet_utilisateur.id_message_dernier_lu IS NULL) ".
                "AND frm_message.date_message > '".date("Y-m-d H:i:s",$site->user->tout_lu_avant)."') ";

    if ( !$forum->is_admin( $site->user ) )
    {
      $grps = $site->user->get_groups_csv();
      $query .= "AND ((droits_acces_forum & 0x1) OR " .
        "((droits_acces_forum & 0x10) AND id_groupe IN ($grps)) OR " .
        "(id_groupe_admin IN ($grps)) OR " .
        "((droits_acces_forum & 0x100) AND frm_forum.id_utilisateur='".$site->user->id."')) ";
    }

    $req = new requete($site->db,$query);

    list($nb)=$req->get_row();

    if ( $nb > 0 )
      $cts->add_paragraph(
      "<a href=\"search.php?page=unread\">".
        "<img src=\"".$wwwtopdir."images/icons/16/unread.png\" class=\"icon\" alt=\"\" />Messages non lu : <b>$nb sujet(s)</b>".
      "</a> ".
      "<a href=\"./?action=setallread\">".
        "<img src=\"".$wwwtopdir."images/icons/16/valid.png\" class=\"icon\" alt=\"\" />Marquer tout comme lu".
      "</a> ".
      "<a href=\"search.php\">".
        "<img src=\"".$wwwtopdir."images/icons/16/search.png\" class=\"icon\" alt=\"\" />Rechercher".
      "</a> ".
      "<a href=\"search.php?page=starred\">".
        "<img src=\"".$wwwtopdir."images/icons/16/star.png\" class=\"icon\" alt=\"\" />Favoris".
      "</a>"
      ,"frmtools");
    else
      $cts->add_paragraph("<a href=\"search.php\"><img src=\"".$wwwtopdir."images/icons/16/search.png\" class=\"icon\" alt=\"\" />Rechercher</a>","frmtools");

  }
  else
    $cts->add_paragraph("<a href=\"search.php\"><img src=\"".$wwwtopdir."images/icons/16/search.png\" class=\"icon\" alt=\"\" />Rechercher</a>","frmtools");

  $cts->add(new forumslist($forum, $site->user, "./"));

}
else
{
// Liste des sujets
  $npp=40;
  $start=0;
  $nbpages = ceil($forum->nb_sujets/$npp);

  if ( isset($_REQUEST["fpage"]) )
  {
    $start = intval($_REQUEST["fpage"])*$npp;
    if ( $start > $forum->nb_sujets )
    {
      $start = $forum->nb_sujets;
      $start -= $start%$npp;
    }
  }

  /**@todo:bouttons+infos*/

  $cts->add_paragraph("<a href=\"search.php\"><img src=\"".$wwwtopdir."images/icons/16/search.png\" class=\"icon\" alt=\"\" />Rechercher</a> <a href=\"?id_forum=".$forum->id."&amp;page=post\"><img src=\"".$wwwtopdir."images/icons/16/sujet.png\" class=\"icon\" alt=\"\" />Nouveau sujet</a>","frmtools");

  $cts->add(new forumslist($forum, $site->user, "./"));
  $cts->add(new sujetslist($forum, $site->user, "./", $start, $npp));

  $entries=array();

  for( $n=0;$n<$nbpages;$n++)
    $entries[]=array($n,"forum2/?id_forum=".$forum->id."&fpage=".$n,$n+1);

  $cts->add(new tabshead($entries, floor($start/$npp), "_bottom"));

  $cts->add_paragraph("<a href=\"search.php\"><img src=\"".$wwwtopdir."images/icons/16/search.png\" class=\"icon\" alt=\"\" />Rechercher</a> <a href=\"?id_forum=".$forum->id."&amp;page=post\"><img src=\"".$wwwtopdir."images/icons/16/sujet.png\" class=\"icon\" alt=\"\" />Nouveau sujet</a>","frmtools");

  /**@todo:bouttons+infos*/
}

$site->add_contents($cts);

$site->end_page();

exit();
?>
