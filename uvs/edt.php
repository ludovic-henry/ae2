<?php
/** @file
 *
 * @brief Page d'information sur les emplois du temps.
 *
 */

/* Copyright 2007
 * - Pierre Mauduit <pierre POINT mauduit CHEZ utbm POINT fr>
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
 * along with this program; if not, write to the Free Sofware
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA
 * 02111-1307, USA.
 */

$topdir = "../";

include($topdir. "include/site.inc.php");

require_once ($topdir . "include/entities/edt.inc.php");
require_once ($topdir . "include/cts/edt_img.inc.php");
require_once($topdir. "include/entities/uv.inc.php");

$site = new site();

$site->redirect("/pedagogie/edt.php");

$site->add_box("uvsmenu", get_uvsmenu_box() );
$site->set_side_boxes("left",array("uvsmenu", "connexion"));

$site->start_page("services", "Emploi du temps");

$cts = new contents("Pédagogie : Maintenance");
$cts->add_paragraph("La partie pédagogie est partiellement fermée pour une refonte complète.");
$cts->add_paragraph("Pour tout bug ou demande de fonctionnalité, contactez <a href=\"http://ae.utbm.fr/user.php?id_utilisateur=1956\">Gliss</a>.");
$site->add_contents($cts);

if (!$site->user->utbm)
{
  $site->error_forbidden("services","reservedutbm");
}
if (!$site->user->is_valid())
{
  $site->error_forbidden("services");
}

$edt = new edt($site->db, $site->dbrw);

if($_REQUEST['showincts'] == 1)
{
  $cts = new contents("","");
  $cts->add_paragraph("<center><img src=\"./edt.php?render=1&semestre=".
          $_REQUEST['semestre']."&id=". $_REQUEST['id']
          ."\" alt=\"emploi du temps\" /></center>");

  echo "<h1>Emploi du temps - semestre " .
    htmlentities($_REQUEST['semestre'])."</h1>\n";
  echo $cts->html_render();
  exit();

}

if ($_REQUEST['render'] == 1)
{
  isset($_REQUEST['id']) ? $id = intval($_REQUEST['id']) : $id = $site->user->id;

  $sem = (date("m") > 6 ? "A" : "P") . date("y");

  isset($_REQUEST['semestre']) ? $semestre = $_REQUEST['semestre'] : $semestre = $sem;

  $user = new utilisateur($site->db);
  $user->load_by_id($id);


  $edt->load_by_etu_semestre($id, $semestre);

  $edtimg = new edt_img($user->prenom . " ". $user->nom . " (".$user->alias.") ",  $edt->edt_arr);
  $edtimg->generate (false);
  exit();

}

/* suppression d'emploi du temps */
if (isset($_REQUEST['delete']))
{
  $semestre = $_REQUEST['semestre'];
  $edt->delete_edt($site->user->id,
       $semestre);
}

$path = "<a href=\"".$topdir."uvs/\"><img src=\"".$topdir."images/icons/16/lieu.png\" class=\"icon\" />  Pédagogie </a>";
$path .= "/" . " Emploi du temps";
$cts = new contents($path);

$cts->add_paragraph("Sur cette page vous pouvez gérer vos emplois du temps.".
        "<br/><a href=\"#\" onClick=\"javascript:alert('Fonction indisponible pour le moment')\">Ajouter un emploi du temps</a>");
/*        "<br/><a href=\"./create.php\">Ajouter un emploi du temps</a>"); */


$cts->add_paragraph("<h2>Vos emplois du temps disponibles</h2><br/>");

$cts->add_paragraph("<script language=\"javascript\">
function render(sem, iduser)
{
  openInContents('cts2', './edt.php', 'showincts=1&semestre='+sem+'&id='+iduser);
  document.getElementById('cts2').style.display = 'block';

}
</script>
");

$req = new requete($site->db, "SELECT
                                        `semestre_grp`
                                        , `edu_uv_groupe_etudiant`.`id_utilisateur`
                               FROM
                                        `edu_uv_groupe`
                               INNER JOIN
                                        `edu_uv_groupe_etudiant`
                               USING(`id_uv_groupe`)
                               WHERE
                                        `id_utilisateur` = ".$site->user->id."
                               GROUP BY
                                        `semestre_grp`, `id_utilisateur`");
if ($req->lines <= 0)
{
  $cts->add_paragraph("Vous n'avez pas enregistré d'emploi du temps.");
}

else
{
  $tab = array();

  while ($rs = $req->get_row())
    {
      if (!$first)
  $first = $rs['semestre_grp'];

      $tab[] = "<a href=\"javascript:render('".$rs['semestre_grp']."', '".$site->user->id."')\">".
  "Emploi du temps du semestre ".$rs['semestre_grp'].
  "</a> | <a href=\"".$topdir."uvs/edt_ical.php?semestre=".$rs['semestre_grp']."&id=".$site->user->id."\">iCal</a> | ".
  "<a href=\"#\" onClick=\"javascript:alert('Fonction indisponible pour le moment')\">Editer</a> | ".
/*  "<a href=\"./edit.php?semestre=".$rs['semestre_grp']."\">Editer</a> | ". */
  "<a href=\"#\" onClick=\"javascript:alert('Fonction indisponible pour le moment')\">Supprimer</a>";
/*  "<a href=\"\" onClick=\"javascript:if(confirm('Etes vous sûr de souhaiter supprimer cet emploi du temps ?')) window.location = './edt.php?delete&semestre=".$rs['semestre_grp']."'\">Supprimer</a>"; */
    }
  $itemlst = new itemlist("Liste des emploi du temps", false, $tab);
  $cts->add($itemlst);
}

$site->add_contents($cts);

/* contents 2 */

$cts2 = new contents("", "");

if (!$first)
{
  $cts2->puts("<script language=\"javascript\">
document.getElementById('cts2').style.display = 'none';
</script>");
}
else
{
  $cts2->puts("<script language=\"javascript\">
  render('".$first."', '".$site->user->id."');
</script>");
}

$site->add_contents($cts2);


$site->end_page();

?>
