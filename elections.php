<?php

/** @file
 *
 * @brief la page des Election
 *
 */

/* Copyright 2005
 * - Pierre Mauduit <pierre POINT mauduit CHEZ utbm POINT fr>
 *
 * Ce fichier fait partie du site de l'Association des Étudiants de
 * l'UTBM, http://ae.utbm.fr.
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License a
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

$topdir = "./";
require_once($topdir . "include/site.inc.php");
require_once($topdir . "include/elections.inc.php");

$site = new site ();

if ( isset($_REQUEST["id_election"]))
{
  $elec = new election($site->db,$site->dbrw);
  $elec->load_by_id($_REQUEST["id_election"]);
  if ( $elec->id < 1 )
  {
    $site->error_not_found("accueil");
    exit();
  }

  if ( isset($_REQUEST['vote']))
  {

    if ( $elec->fin >= time() && $elec->debut <= time() &&
      $site->user->is_in_group_id($elec->id_groupe) && ! $elec->a_vote($site->user->id) )
    $elec->enregistre_vote($site->user->id, $_REQUEST['vote']);

    $Message = "Votre vote a bien été enregistré";

  }

  if ( $_REQUEST["page"] == "results" )
  {
    if ( $elec->fin >= time() /*&& !$site->user->is_in_group("gestion_ae")*/ )
      $site->error_forbidden("accueil");

    $site->start_page("accueil","Resultats: ".$elec->nom);

    $cts = new contents("Resultats: ".$elec->nom);

    $sql = new requete($site->db,"SELECT COUNT(*) FROM vt_a_vote WHERE id_election='".$elec->id."'");

    list($rtotal) = $sql->get_row();

    if ( $rtotal == 0 )
    {

      $cts->add_paragraph("Aucun vote enregistré.");

    }
    else
    {


      $sql = new requete($site->db,"SELECT * FROM vt_liste_candidat WHERE id_election='".$elec->id."' ORDER BY `nom_liste`");
      while ( $row = $sql->get_row() )
        $listes[$row["id_liste"]] = $row["nom_liste"];

      $listes[0] = "Indépendants";
      $listes[-1] = "Abstention";


      $tbl = new table(false,"elections");

      foreach ( $listes as $id => $name )
      {
        $row[$id] = $name;
      }
      $tbl->add_row($row);
      $cts->add($tbl);



      $sql = new requete($site->db,"SELECT id_poste,nom_poste,votes_blancs,votes_total FROM vt_postes WHERE id_election='".$elec->id."'");
      while ( list($id_poste,$nom_poste,$blanc,$total) = $sql->get_row() )
      {
        $cdts = array();
        $tbl = new table($nom_poste,"elections");

        $sql2 = new requete($site->db,"SELECT vt_candidat.*,utilisateurs.*,utl_etu_utbm.* " .
            "FROM vt_candidat " .
            "INNER JOIN `utilisateurs` ON `utilisateurs`.`id_utilisateur`=`vt_candidat`.`id_utilisateur` " .
            "LEFT JOIN `utl_etu_utbm` ON `utl_etu_utbm`.`id_utilisateur`=`utilisateurs`.`id_utilisateur` " .
            "WHERE id_poste='".$id_poste."' " .
            "ORDER BY utilisateurs.nom_utl");

        while ( $srow = $sql2->get_row() )
        {
          $id_liste = intval($srow["id_liste"]);
          $idradio= "__vote_".$id_poste."_".$srow['id_utilisateur'];

          $cdts[$id_liste] .= "<div class=\"candidat\"><br/>";

          if ( file_exists($topdir."data/matmatronch/".$srow['id_utilisateur'].".identity.jpg") )
            $cdts[$id_liste] .= "<img src=\"/data/matmatronch/".$srow['id_utilisateur'].".identity.jpg\" alt=\"\" onclick=\"document.getElementById('$idradio').checked = true;\" /><br/>\n";
          else
            $cdts[$id_liste] .= "<img src=\"/data/matmatronch/na.gif"."\" alt=\"\" onclick=\"document.getElementById('$idradio').checked = true;\" /><br/>\n";

          $cdts[$id_liste] .= $srow['prenom_utl']." ".$srow['nom_utl'];

          if ( $srow['surnom_utbm'] )
            $cdts[$id_liste] .= "<br/><i>".$srow['surnom_utbm']."</i>";

          $cdts[$id_liste] .= "<br/>".$srow['nombre_voix']." soit ".round($srow['nombre_voix']*100/$total,2)." %";
          $cdts[$id_liste] .= "</div>";
        }


        $cdts[-1] = "Abstention";
        $cdts[-1] .= "<br/>".$blanc." soit ".round($blanc*100/$total,2)." %";

        foreach ( $listes as $id => $name )
        {
          if ( !$cdts[$id] )
            $row[$id] = "&nbsp;";
          else
            $row[$id] = $cdts[$id];
        }
        $tbl->add_row($row);
        $cts->add($tbl,true);
      }
    }

    $site->add_contents($cts);

    $site->end_page();
    exit();
  }


  $site->start_page("accueil","Election: ".$elec->nom);

  $cts = new contents("Election: ".$elec->nom);

  if ( $elec->fin < time() )
  {
    $cts->add_paragraph("Election terminée.");
    $cts->add_paragraph("<a href=\"elections.php?id_election=".$elec->id."&amp;page=results\">Election terminée.</a>");

  }
  elseif ( $elec->debut > time() )
  {
    $cts->add_paragraph("Election programmé pour le ".textual_plage_horraire($elec->debut,$elec->fin));
    if ( !$site->user->is_in_group_id($elec->id_groupe) && $site->user->is_valid() )
      $cts->add_paragraph("Remarque: Vous n'avez pas le droit de voter pour cette election.");
  }
  elseif( !$site->user->is_valid() )
  {
    $site->allow_only_logged_users("accueil");
  }
  elseif ( !$site->user->is_in_group_id($elec->id_groupe) )
  {
    $cts->add_paragraph("Vous n'avez pas le droit de voter pour cette election.");
  }
  elseif( $elec->a_vote($site->user->id) )
  {
    if ( $Message )
      $cts->add_paragraph("$Message, les resultats seront disponibles le ".date("d/m/Y à H:i:s",$elec->fin+1));
    else
      $cts->add_paragraph("Vous avez déjà voté, les resultats seront disponibles le ".date("d/m/Y à H:i:s",$elec->fin+1));
  }
  else
  {
    $cts->puts("<form action=\"elections.php?id_election=".$elec->id."\" method=\"POST\">");

    $sql = new requete($site->db,"SELECT * FROM vt_liste_candidat WHERE id_election='".$elec->id."' ORDER BY `nom_liste`");
    while ( $row = $sql->get_row() )
      $listes[$row["id_liste"]] = $row["nom_liste"];

    $listes[0] = "Indépendants";
    $listes[-1] = "Abstention";


    $tbl = new table(false,"elections");

    foreach ( $listes as $id => $name )
    {
      $row[$id] = $name;
    }
    $tbl->add_row($row);
    $cts->add($tbl);



    $sql = new requete($site->db,"SELECT id_poste,nom_poste FROM vt_postes WHERE id_election='".$elec->id."'");
    while ( list($id_poste,$nom_poste) = $sql->get_row() )
    {
      $cdts = array();
      $tbl = new table($nom_poste,"elections");

      $sql2 = new requete($site->db,"SELECT vt_candidat.*,utilisateurs.*,utl_etu_utbm.* " .
          "FROM vt_candidat " .
          "INNER JOIN `utilisateurs` ON `utilisateurs`.`id_utilisateur`=`vt_candidat`.`id_utilisateur` " .
          "LEFT JOIN `utl_etu_utbm` ON `utl_etu_utbm`.`id_utilisateur`=`utilisateurs`.`id_utilisateur` " .
          "WHERE id_poste='".$id_poste."' " .
          "ORDER BY utilisateurs.nom_utl");

      while ( $srow = $sql2->get_row() )
      {
        $id_liste = intval($srow["id_liste"]);
        $idradio= "__vote_".$id_poste."_".$srow['id_utilisateur'];

        $cdts[$id_liste] .= "<div class=\"candidat\"><input type=\"radio\" id=\"$idradio\" name=\"vote[$id_poste]\" value=\"".$srow['id_utilisateur']."\" /><br/>";

        if ( file_exists($topdir."data/matmatronch/".$srow['id_utilisateur'].".identity.jpg") )
          $cdts[$id_liste] .= "<img src=\"/data/matmatronch/".$srow['id_utilisateur'].".identity.jpg\" alt=\"\" onclick=\"document.getElementById('$idradio').checked = true;\" /><br/>\n";
        else
          $cdts[$id_liste] .= "<img src=\"/data/matmatronch/na.gif"."\" alt=\"\" onclick=\"document.getElementById('$idradio').checked = true;\" /><br/>\n";

        $cdts[$id_liste] .= $srow['prenom_utl']." ".$srow['nom_utl'];

        if ( $srow['surnom_utbm'] )
          $cdts[$id_liste] .= "<br/><i>".$srow['surnom_utbm']."</i>";

        $cdts[$id_liste] .= "</div>";
      }


      $cdts[-1] = "<input type=\"radio\" name=\"vote[$id_poste]\" value=\"-1\" checked=\"checked\" /><br/>Abstention";

      foreach ( $listes as $id => $name )
      {
        if ( !$cdts[$id] )
          $row[$id] = "&nbsp;";
        else
          $row[$id] = $cdts[$id];
      }
      $tbl->add_row($row);
      $cts->add($tbl,true);
    }
    $cts->add_paragraph("<input type=\"submit\" value=\"voter\" />","center");
    $cts->puts("</form>");
  }

  $site->add_contents($cts);

  $site->end_page();
  exit();
}

$site->start_page("accueil","Elections");

$cts = new contents("Elections");

$grps = $site->user->get_groups_csv();

$sql = new requete($site->db,"SELECT * FROM `vt_election` " .
    "WHERE `date_debut`<= NOW() " .
    "AND `date_fin` >= NOW() " .
    "AND `id_groupe` IN ($grps)");

$cts->add_title(2,"Elections en cours");

if ( $sql->lines == 0 )
  $cts->add_paragraph("Aucune elections en cours.");
else
{
  $lst = new itemlist();
  while ( $row = $sql->get_row() )
    $lst->add("<a href=\"elections.php?id_election=".$row["id_election"]."\">".$row["nom_elec"]."</a> jusqu'au ".date("d/m/Y H:i",strtotime($row["date_fin"])));
  $cts->add($lst);
}

$site->add_contents($cts);
$site->end_page();
?>
