<?php
/* Copyright 2006
 * - Julien Etelain < julien at pmad dot net >
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
$topdir="../";
require_once("include/compta.inc.php");
require_once($topdir . "include/cts/sqltable.inc.php");
require_once("include/comptes.inc.php");

$site = new sitecompta();

$site->allow_only_logged_users("services");

if ( !$site->user->is_in_group("compta_admin") )
  $site->error_forbidden("services");


if ($_REQUEST['action'] == "addcptasso")
{
  $cptasso = new compte_asso($site->db,$site->dbrw);
  $cpbc  = new compte_bancaire($site->db);
  $asso  = new asso($site->db);

  $asso->load_by_id($_REQUEST["id_asso"]);
  $cpbc->load_by_id($_REQUEST["id_cptbc"]);

  if ( $asso->id > 0 && $cpbc->id > 0 )
    $cptasso->ajouter( $asso->id,$cpbc->id);
}




$site->start_page ("accueil",
       "Administration de la compta");


/* test ajout compte */
if (isset($_REQUEST['cpt_nom']))
{
  $cpt = new compte_bancaire($site->db, $site->dbrw);
  $ret = $cpt->create ($_REQUEST['cpt_nom']);

  if ($ret == true)
  {
    $cpt_res = new contents ("Ajout d'un compte");
    $cpt_res->add_paragraph ("Compte $cpt->nom ajoute avec succes.");
  }
  else
  {
    $cpt_res = new error ("Erreur",
      "Erreur lors de l'ajout du compte.");
  }
}

/* test suppresion compteS */
if ($_REQUEST['action'] == "deletes")
{
  $cpt = new compte_bancaire ($this->db, $this->dbrw);
  /* TODO : supprimer si necessaire ? */

}

/* test edition compte */
if ($_REQUEST['action'] == "edit")
{
  $cpt = new compte_bancaire ($site->db, $site->dbrw);

  $cpt->load_by_id ($_REQUEST['id_cptbc']);

  $cpt_edit = new contents ("Edition d'un compte.");
  $cpt_edit_f = new form("cpt_edit_f",
       "admin.php",
       true,
       "modification d'un compte");
  $cpt_edit_f->add_text_field("cpt_nom",
           "nom du compte",
            $cpt->nom,
            true);
  $cpt_edit_f->add_submit("cpt_sub_edit",
        "Editer");

  $cpt_edit->add ($cpt_edit_f);
}

/* test formulaire edition compte */
if (isset($_REQUEST['cpt_edit_f']))
{
  /* modification du compte */
  $cpt = new compte_bancaire ($site->db, $site->dbrw);
  $cpt->load_by_id ($_REQUEST['id_cptbc']);
  $ret = $cpt->modifier_nom ($_REQUEST['cpt_nom']);

  if ($ret == false)
    $cpt_res = new error ("Erreur",
        "Erreur lors de l'�dition du nom");
  else
    {
      $cpt_res = new contents ("Edition du compte");
      $cpt_res->add_paragraph ("Compte edite avec succes.");
    }
}

/* objets graphiques */


$cpt_add = new contents ("Comptes");


$add_form_b = new form("add_cpt_b",
     "admin.php",
     true,"POST",
     "Ajout d'un compte");
$add_form_b->add_text_field ("cpt_nom",
           "nom du compte",
           "", true);

$add_form_b->add_submit("cpt_sub_add", "Creer");

$cpt_add->add ($add_form_b,true);

$frm = new form("addcptasso","admin.php",true,"POST","Ajout d'un compte association");
$frm->add_hidden("action","addcptasso");
$frm->add_select_field("id_cptbc","Compte bancaire",$site->get_lst_cptbc());
$frm->add_select_field("id_asso","Association",$site->get_lst_asso(false));
$frm->add_submit("addcptasso", "Ajouter");

$cpt_add->add ($frm,true);

if ($_REQUEST['action'] != "edit")
{
  $req_sql = new requete ($site->db,
        "SELECT * FROM `cpta_cpbancaire`");

  $liste_cptes_bancaires = new sqltable ("cpta_cpbancaire",
           "Liste des comptes bancaires",
           $req_sql,
           "./admin.php",
           "id_cptbc",
           array("id_cptbc" => "numero",
                 "nom_cptbc" => "nom du compte"),
           array("edit" => "edition",
               "delete" => "supprimer"),
           array("deletes" => "supprimer"),
           array());
}

if (isset($cpt_dbg))
  $site->add_contents ($cpt_dbg);


if (isset($cpt_res))
  $site->add_contents ($cpt_res);

if (isset($cpt_edit))
  $site->add_contents ($cpt_edit);



$site->add_contents ($cpt_add);
if ($_REQUEST['action'] != "edit")
  $site->add_contents ($liste_cptes_bancaires);

$site->end_page ();
?>
