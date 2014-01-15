<?php
/* Copyright 2006
 *
 * - Maxime Petazzoni < sam at bulix dot org >
 * - Pierre Mauduit < pierre dot mauduit at utbm dot fr >
 * - Benjamin Collet < bcollet at oxynux dot org >
 *
 * Ce fichier fait partie du site de l'Association des Ãtudiants de
 * l'UTBM, http://ae.utbm.fr.
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
require_once($topdir . "include/cts/sqltable.inc.php");
require_once($topdir . "include/entities/news.inc.php");
require_once($topdir . "include/entities/asso.inc.php");
require_once($topdir."include/entities/files.inc.php");
require_once($topdir . "include/entities/lieu.inc.php");

$site = new site ();

if (!$site->user->is_in_group ("moderateur_site"))
  $site->error_forbidden("accueil");

$site->start_page ("accueil", "Modération des nouvelles");

/*suppression via la sqltable */
if ((isset($_REQUEST['id_nouvelle']))
    && ($_REQUEST['action'] == "delete"))
{
  $news = new nouvelle ($site->db, $site->dbrw);
  $id = intval($_REQUEST['id_nouvelle']);
  $news->load_by_id ($id);
  $news->delete ();

  $site->add_contents (new contents("Suppression",
            "<p>Suppression eff&eacute;ctu&eacute;e avec succ&egrave;s</p>"));
}

/* moderation */
if ((isset($_REQUEST['id_nws']))
    && ($_REQUEST['action'] == "mod"))
{
  $news = new nouvelle ($site->db, $site->dbrw);

  $id = intval($_REQUEST['id_nws']);

  /* suppression */
  if (isset($_REQUEST['delete']))
  {
    $news->load_by_id ($id);
    $news->delete ();
    $site->add_contents (new contents("Suppression",
          "<p>Suppression eff&eacute;ctu&eacute;e avec succ&egrave;s</p>"));
  }
  /* accepte en moderation */
  if (isset($_REQUEST['accept']))
  {
    $lieu = new lieu($site->db);
    $lieu->load_by_id($_REQUEST["id_lieu"]);

    $news->load_by_id($id);

    $news->save_news(
           $_REQUEST['id_asso'],
           $_REQUEST['nws_title'],
           $_REQUEST['nws_sum'],
           $_REQUEST['nws_cts'],
           true,$site->user->id,$_REQUEST['type'],$lieu->id,$news->id_canal);

            $site->add_contents (new contents("Mod&eacute;ration",
          "<p>Mod&eacute;ration eff&eacute;ctu&eacute;e avec succ&egrave;s</p>"));

    nouvelle::expire_cache_content ();

    if ( isset($_REQUEST["dfile"]))
    {
      $fl = new dfile($site->db,$site->dbrw);
      foreach($_REQUEST["dfile"]as $id=>$chk)
      {
        $fl->load_by_id($id);
        $fl->set_modere();
      }

    }
  }
}



/* edition d'une nouvelle */
if (isset($_REQUEST['id_nouvelle']) &&
    ($_REQUEST['action'] == "edit"))
{
  /* un objet news */
  /* note : a ce stade, un read only est suffisant */
  $news = new nouvelle ($site->db);
  $news->load_by_id ($_REQUEST['id_nouvelle']);

  $site->add_contents(new contents ("Aper&ccedil;u de la nouvelle :",
         "<p>Dans le cadre ci-dessous, vous allez avoir un ".
         "aper&ccedil;u de la nouvelle</p>"));

  $site->add_contents ($news->get_contents ());

  /* on affiche un formulaire d'edition */
  $form = new form ("edit_nws",
        "moderenews.php?id_nws=".$_REQUEST['id_nouvelle'].
        "&action=mod",
        true,
        "post",
        "Edition de \"".$news->titre."\"");

  $form->add_select_field ("type",
         "Type de nouvelle", array( 3 => "Appel/concours",
                                          1 => "Évenement ponctuel",
                                          2 => "Séance hebdomadaire",
                                          0 => "Info/résultat")
         ,$news->type);

  $form->add_entity_select("id_asso", "Association concern&eacute;e", $site->db, "asso",$news->id_asso,true);
  $form->add_entity_select("id_lieu", "Lieu", $site->db, "lieu",$news->id_lieu,true);

  /* titre */
  $form->add_text_field ("nws_title", "Titre de la nouvelle :",$news->titre, true,"80");

  /* resume */
  $form->add_text_area ("nws_sum","Resume :",$news->resume,80,2);

  /* contenu */
  $form->add_text_area ("nws_cts","Contenu :",$news->contenu,80,10,true);

  $matches=null;
  preg_match_all("`\(\(dfile:\/\/([0-9]+)(\/thumb|\/preview)?\|(.*)\)\)`U",$news->contenu,$matches);

  if ( count($matches[1]) )
  {
    $fl = new dfile($site->db);
    foreach($matches[1] as $id)
    {
      $fl->load_by_id($id);
      if ( !$fl->modere )
      {
        $form->add_checkbox("dfile|".$fl->id,"Accepter de m&ecirc;me l'image contenue dans la nouvelle : ".$fl->get_html_link(),true);
      }
    }
  }

  $form->add_submit("accept", "Accepter");
  $form->add_submit("delete", "Supprimer");

  $site->add_contents ($form);

  $site->add_contents (new wikihelp());
}


/* Evidemment on pourrait mettre de la moderation massive, mais je ne
 * pense pas que ce soit une super idee concernant la qualité de la
 * modération. C'est pourquoi il n'y a pas de batch action possibles
 * dans les formulaires */

/* presentation des news en attente de moderation */
else
{
  $req = new requete($site->db,"SELECT `nvl_nouvelles`.*,
                                       `asso`.`nom_unix_asso`,
                                       `utilisateurs`.`id_utilisateur`,
                                       CONCAT(`utilisateurs`.`prenom_utl`,
                                              ' ',
                                              `utilisateurs`.`nom_utl`) AS `nom_utilisateur`

                                FROM `nvl_nouvelles`
              LEFT JOIN `asso` ON
                                           `asso`.`id_asso` =
                                           `nvl_nouvelles`.`id_asso`
                                INNER JOIN `utilisateurs` ON
                                           `utilisateurs`.`id_utilisateur` =
                                           `nvl_nouvelles`.`id_utilisateur`

                                WHERE `modere_nvl`='0' ORDER BY `date_nvl`");

  $modhelp = new contents("Mod&eacute;ration des nouvelles",
        "<p>Sur cette page, vous pouvez mod&eacute;rer ".
        "les nouvelles</p>");


  $tabl = new sqltable ("moderenews_list",
      "Nouvelles en attente de mod&eacute;ration",
      $req,
      "moderenews.php",
      "id_nouvelle",
      array ("titre_nvl" => "Titre",
             "nom_utilisateur" => "Auteur",
             "date_nvl" => "Date"),
      array ("edit" => "moderer",
             "delete" => "supprimer"),
      array (),
      array ());

  $modhelp->add ($tabl);
  $site->add_contents ($modhelp);

}


$site->end_page ();

?>
