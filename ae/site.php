<?php
/* Copyright 2006
 * - Pierre Mauduit
 *
 * Ce fichier fait partie du site de l'Association des Étudiants de
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
/*
 * @brief gestion du site de l'AE; modification des boites,
 * articles ?, etc ...
 *
 * Accessible par l'administration du site
 */



$topdir = "../";

require_once($topdir. "include/site.inc.php");
require_once($topdir. "include/cts/sqltable.inc.php");

$site = new site ();

if (!$site->user->is_in_group ("moderateur_site"))
  $site->error_forbidden("accueil");

/* else */
$site->start_page ("accueil", "Gestion du site");

/* objets graphiques de base */

$cts_base = new contents ("Gestion du site","<p>Cette page vous permet "
        ."de gérer le site de l'AE. Ceci implique la ".
        "modification des textes dans les boites, etc".
        "...</p>");

$site->add_contents ($cts_base);


$req = new requete ($site->db,
        "SELECT * FROM `site_boites` WHERE `nom_boite` != 'news_help'");

/* formulaire */
if (!isset($_REQUEST['action']))
{
  $cts_mod_box = new sqltable ("frm_mod_box",
             "Modification des boites",
             $req,
             "site.php",
             "nom_boite",
             array ("nom_boite" => "nom de la boite",
              "description_boite" => "description"),
             array("edit" => "Editer la boite"),
             array("edit" => "Editer la / les boite(s)"));
  $site->add_contents ($cts_mod_box);
}


/* si formulaire de modification des boites postes (nouvelles valeurs) */

if (isset($_REQUEST['frm_edit_box_ct']))
{
  foreach ($_REQUEST['frm_edit_box_ct'] as $nom_boite => $contenu_boite)
  {
    $site->set_param("box.".$nom_boite,doku2xhtml($contenu_boite));

    $req = new update ($site->dbrw,
       "site_boites",
       array ("contenu_boite" => $contenu_boite),
       array ("nom_boite" => $nom_boite));
    if ($req != false)
      $site->add_contents(new contents("Modification boite ". $nom_boite,
           "La boite a &eacute;t&eacute; modifi&eacute;e avec succ&egrave;s"));
    else
      $site->add_contents(new error("Erreur Modification",
              "Une erreur est survenue lors de la modification "
              ."de la boite " . $nom_boite));
  }
}



/* sinon, on propose un formulaire d'edition de la ou des boites */
if (isset($_REQUEST['action']))
{
  $frm_edit_box = new form ("frm_edit_box",
          "site.php",
          true,
          "post",
          "Edition des boites");

  /* modification d'une seule boite */
  if ((isset($_REQUEST['nom_boite'])) &&
      (!isset($_REQUEST['nom_boites'])))
  {
    $req = new requete ($site->db,
      "SELECT `contenu_boite`, `nom_boite` FROM `site_boites` ".
      "WHERE `nom_boite` = '".
      mysql_real_escape_string($_REQUEST['nom_boite'])
      ."'");
    $ct = $req->get_row ();
    if( $_REQUEST['nom_boite'] == "Important" )
      $frm_edit_box->add_dokuwiki_toolbar("frm_edit_box_ct[".$ct['nom_boite']."]");
    /* un seul resultat */
    $frm_edit_box->add_text_area ("frm_edit_box_ct[".$ct['nom_boite']."]",
        "Contenu de la boite " .
        $ct['nom_boite'],
        $ct['contenu_boite']);
  }

  /* modification de plusieurs boites */
  if (isset($_REQUEST['nom_boites']))
  {
    /* mysql protection (meme si envoye en post) */
    for ($i = 0; $i < count ($_REQUEST['nom_boites']); $i++)
      $boxes_t[$i] = "'".
      mysql_real_escape_string($_REQUEST['nom_boites'][$i]).
      "'";

    $boxes = implode(",", $boxes_t);


    $req = new requete ($site->db,
      "SELECT `contenu_boite`, `nom_boite` ".
      "FROM `site_boites` ".
      "WHERE `nom_boite` IN (".
      $boxes .")");
    for ($i = 0; $i < $req->lines; $i++)
    {
      $ct[] = $req->get_row ();
      $frm_edit_box->add_text_area ("frm_edit_box_ct[".
            $ct[$i]['nom_boite']."]",
            "Contenu de la boite " .
            $ct[$i]['nom_boite'],
            $ct[$i]['contenu_boite'],
            80,
            10);
    }
  }
  $frm_edit_box->add_submit("frm_edit_box_submit", "Modifier");

  $site->add_contents($frm_edit_box);

}


$site->end_page ();

exit();
?>
