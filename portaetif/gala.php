<?php

/* Copyright 2007
 *
 * - Simon Lopez < simon dot lopez at ayolo dot org >
 *
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
include($topdir. "include/site.inc.php");
include("include/log.inc.php");
$site = new site ();

if (!$site->user->is_in_group ("gestion_ae") && !$site->user->is_in_group ("portaetif"))
  $site->error_forbidden();

$site->start_page("gala","Gala 2008");

$site->add_css("css/gala.css");
$site->set_side_boxes("left",array());
$site->set_side_boxes("right",array());

if ( $_REQUEST["action"] == "getpass" )
{
  $user = new utilisateur($site->db,$site->dbrw);
  $user->load_by_id($_REQUEST["id_utilisateur"]);
  if ( $user->id > 0 )
  {
    $sql = 'SELECT quantite FROM zzz_places_gala WHERE id_utilisateur='.$user->id;
    $req = new requete($site->db,$sql);
    $nb=0;
    if ($req->lines<1)
      $Erreur = "Aucune place en stock pour vous.";
    else
      list($nb)=$req->get_row();
    if ( $nb>0)
    {
      $cts=new contents("Bienvenue au gala de prestige 2008 de l'UTBM");
      $cts->add_paragraph("Il vous reste $nb places à retirer, combien voulez vous en retirer maintenant ?");

      $frm = new form("getnbpass","gala.php",true,"POST","");
      $frm->add_hidden("action","getnbpass");
      $frm->add_hidden("id_utilisateur",$user->id);
      $vals=array();
      for($i=0;$i<$nb+1;$i++)
        $vals[$i]=$i;
      $frm->add_select_field('nb_places','Nombre de places',$vals);
      $frm->add_submit("get","Retirer des places");
      $cts->add($frm,true);
      $site->add_contents($cts);
      $site->end_page();
      exit();
    }
    else
      $Erreur = "Aucune place en stock pour vous.";
  }
  else
    $Erreur = "Une erreur a été détectée, êtes-vous sûr d'avoir bien rempli le champ avec votre nom ?";
}
elseif( $_REQUEST["action"] == "getnbpass" && isset($_REQUEST["nb_places"]) && $_REQUEST["nb_places"]>0)
{
  $user = new utilisateur($site->db,$site->dbrw);
  $user->load_by_id($_REQUEST["id_utilisateur"]);
  if ( $user->id > 0 )
  {
    $sql = 'SELECT quantite FROM zzz_places_gala WHERE id_utilisateur='.$user->id;
    $nb=0;
    $req = new requete($site->db,$sql);
    if ( $req->lines<1 )
      $Erreur = "Aucune place ne semble réservée à votre nom.";
    else
      list($nb)=$req->get_row();
    if($nb>0 && $nb>=$_REQUEST["nb_places"])
    {
      $cts=new contents("Le gala souhaite la bienvenue à :");
      $cts->add_paragraph('<div id="welcomeuh">'.$user->get_display_name().'</div>');
      $nb=$nb-$_REQUEST["nb_places"];
      new update($site->dbrw,
                 'zzz_places_gala',
                 array('quantite'=>$nb),
                 array('id_utilisateur'=>$user->id));
      if($nb>0)
        $cts->add_paragraph("<br />&nbsp;<br />Il vous reste $nb places à retirer.");
      $cts->puts('<script type="text/javascript">
function delayer(){
    window.location = "gala.php"
}
setTimeout(\'delayer()\', 10000);
</script>
');
      $site->add_contents($cts);
      $site->end_page();
      exit();
    }
    $Erreur = "Une erreur est survenue :/ please, try again.";
  }
  else
    $Erreur = "Une erreur est survenue :/ please, try again.";
}


$cts = new contents("Bienvenue au gala de prestige 2008 de l'UTBM");
$frm = new form("getpass","gala.php",true,"POST","Gala");
$frm->add_info("Veuillez entrer votre nom ci-dessous pour pouvoir retirer vos places :");
$frm->add_hidden("action","getpass");
if ( $Erreur ) $frm->error($Erreur);
$frm->add_user_fieldv2("id_utilisateur","");
$frm->add_submit("get","Retirer des places");
$cts->add($frm,true);
/*$cts->puts("<script type='text/javascript'>
              userselect_toggle('id_utilisateur');
            </script>");
*/
/* c'est tout */
$site->add_contents($cts);

$site->end_page();

?>
