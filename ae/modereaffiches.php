<?php
/* Copyright 2010
 *
 * - Maxime Petazzoni < sam at bulix dot org >
 * - Pierre Mauduit < pierre dot mauduit at utbm dot fr >
 * - Benjamin Collet < bcollet at oxynux dot org >
 * - Mathieu Briand < briandmathieu at hyprua dot org >
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
require_once($topdir . "include/entities/affiche.inc.php");
require_once($topdir."include/entities/files.inc.php");

$site = new site ();

if (!$site->user->is_in_group ("moderateur_site"))
  $site->error_forbidden("accueil");

$site->start_page ("services", "Modération des affiches");

/*suppression via la sqltable */
if ((isset($_REQUEST['id_affiche']))
    && ($_REQUEST['action'] == "delete"))
{
  $affiche = new affiche ($site->db, $site->dbrw);
  $id = intval($_REQUEST['id_affiche']);
  $affiche->load_by_id ($id);
  $affiche->delete ();

  $site->add_contents (new contents("Suppression",
            "<p>Suppression eff&eacute;ctu&eacute;e avec succ&egrave;s</p>"));
}

/* moderation */
if ((isset($_REQUEST['id_aff']))
    && ($_REQUEST['action'] == "mod"))
{
  $affiche = new affiche ($site->db, $site->dbrw);

  $id = intval($_REQUEST['id_aff']);

  /* suppression */
  if (isset($_REQUEST['delete']))
  {
    $affiche->load_by_id ($id);
    $affiche->delete ();
    $site->add_contents (new contents("Suppression",
          "<p>Suppression eff&eacute;ctu&eacute;e avec succ&egrave;s</p>"));
  }
  /* accepte en moderation */
  if (isset($_REQUEST['accept']))
  {
    $affiche->load_by_id($id);

    $affiche->save_affiche(
           $_REQUEST['id_asso'],
           $_REQUEST['aff_title'],
           $_REQUEST['aff_deb'],
           $_REQUEST['aff_fin'],
           true,$site->user->id);

            $site->add_contents (new contents("Mod&eacute;ration",
          "<p>Mod&eacute;ration eff&eacute;ctu&eacute;e avec succ&egrave;s</p>"));

    $fl = new dfile($site->db,$site->dbrw);
    $fl->load_by_id($affiche->id_file);
    $fl->set_modere();
  }
}



/* edition d'une affiche */
if (isset($_REQUEST['id_affiche']) &&
    ($_REQUEST['action'] == "edit"))
{
  /* un objet affiche */
  /* note : a ce stade, un read only est suffisant */
  $affiche = new affiche ($site->db);
  $affiche->load_by_id ($_REQUEST['id_affiche']);

  $site->add_contents(new contents ("Aper&ccedil;u de l'affiche :",
         "<p>Dans le cadre ci-dessous, vous allez avoir un ".
         "aper&ccedil;u de l'affiche</p>"));

  $site->add_contents ($affiche->get_contents ());

  /* on affiche un formulaire d'edition */
  $form = new form ("edit_aff",
        "modereaffiches.php?id_aff=".$_REQUEST['id_affiche'].
        "&action=mod",
        true,
        "post",
        "Edition de \"".$affiche->titre."\"");


  $form->add_entity_select("id_asso", "Association concern&eacute;e", $site->db, "asso",$affiche->id_asso,true);

  /* titre */
  $form->add_text_field ("aff_title", "Titre de l'affiche :",$affiche->titre, true,"80");

  /* dates */
  $form->add_datetime_field("aff_deb","Date et heure de d&eacute;but", $affiche->date_deb, true);
  $form->add_datetime_field("aff_fin","Date et heure de fin", $affiche->date_fin, true);

  $form->add_submit("accept", "Accepter");
  $form->add_submit("delete", "Supprimer");

  $site->add_contents ($form);
}


/* Evidemment on pourrait mettre de la moderation massive, mais je ne
 * pense pas que ce soit une super idee concernant la qualité de la
 * modération. C'est pourquoi il n'y a pas de batch action possibles
 * dans les formulaires */

/* presentation des affiches en attente de moderation */
else
{
  $req = new requete($site->db,"SELECT `aff_affiches`.*,
          `utilisateurs`.`id_utilisateur`,
          CONCAT(`utilisateurs`.`prenom_utl`,
          ' ',
          `utilisateurs`.`nom_utl`) AS `nom_utilisateur`
        FROM `aff_affiches`
        INNER JOIN `utilisateurs` USING (id_utilisateur)
        WHERE `modere_aff`='0' ORDER BY `date_modifie`");

  $modhelp = new contents("Mod&eacute;ration des affiches",
        "<p>Sur cette page, vous pouvez mod&eacute;rer ".
        "les affiches</p>");


  $tabl = new sqltable ("modereaffiche_list",
      "Affiches en attente de mod&eacute;ration",
      $req,
      "modereaffiches.php",
      "id_affiche",
      array ("titre_aff" => "Titre",
             "nom_utilisateur" => "Auteur",
             "date_deb" => "Date de début",
             "date_fin" => "Date de fin"),
      array ("edit" => "moderer",
             "delete" => "supprimer"),
      array (),
      array ());

  $modhelp->add ($tabl);
  $site->add_contents ($modhelp);

}


$site->end_page ();

?>
