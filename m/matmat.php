<?php
/* Copyright 2011
 * - Antoine Tenart < antoine dot tenart at gmail dot com >
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

/**
 * Mobile Mat'matronch
 */

$topdir = "../";

require_once($topdir. "include/site.inc.php");

function handle_phone ($ph) {
  $reg = "$(\+|(0[1-9]))([0-9]+)$";
  return preg_replace ($reg, "$3", $ph);
}

$site = new site();
$site->set_mobile(true);
$site->start_page("matmatronch", "MatMaTronch");
$site->add_css ("/themes/mobile/css/matmat.css");


$cts = new contents();
$cts->add_title(1, "Mat'Matronch", "mob_title");

if (!$site->user->ae && !$site->user->utbm) {
  $cts->add_paragraph ("Section réservée aux cotisants AE et aux membres de l'UTBM.");
  $site->add_contents ($cts);
  $site->end_page ();
  exit (0);
}

if (isset($_REQUEST["simplesearch"])) {
    if (isset($_REQUEST["pattern"])) {
      $pattern = stdentity::_fsearch_prepare_sql_pattern($_REQUEST["pattern"]);
      $pattern = handle_phone ($pattern);
      $pattern = strtr($pattern, array(' ' => '|'));

      $cond_nom = "`utilisateurs`.nom_utl REGEXP '".$pattern."'";
      $cond_prenom = "`utilisateurs`.prenom_utl REGEXP '".$pattern."'";
      $cond_surnom = "`utl_etu_utbm`.surnom_utbm REGEXP '".$pattern."'";
      $cond_tel = "`utilisateurs`.tel_portable_utl REGEXP '".$pattern."'";
      $cond_mail = "`utilisateurs`.email_utl REGEXP '".$pattern."'";

      $req = new requete($site->db,
            "SELECT `utilisateurs`.id_utilisateur,
              `utilisateurs`.nom_utl,
              `utilisateurs`.prenom_utl,
              `utilisateurs`.email_utl,
              `utilisateurs`.tel_portable_utl,
              `utl_etu_utbm`.surnom_utbm,
              `utl_etu_utbm`.email_utbm
            FROM `utilisateurs`
            LEFT JOIN `utl_etu_utbm` ON `utl_etu_utbm`.id_utilisateur=`utilisateurs`.id_utilisateur
            WHERE (".$cond_nom." OR
              ".$cond_prenom." OR
              ".$cond_surnom." OR
              ".$cond_tel." OR
              ".$cond_mail.")
              AND `utilisateurs`.publique_utl >= '".($site->user->cotisant?'1':'2')."'
            ORDER BY (CASE WHEN ".$cond_nom." THEN 1 ELSE 0 END) +
              (CASE WHEN ".$cond_prenom." THEN 1 ELSE 0 END) +
              (CASE WHEN ".$cond_surnom." THEN 1 ELSE 0 END) +
              (CASE WHEN ".$cond_tel." THEN 1 ELSE 0 END) +
              (CASE WHEN ".$cond_mail." THEN 1 ELSE 0 END) DESC,
              `utilisateurs`.id_utilisateur DESC
            LIMIT 10"
        );

      if ( $req->lines == 0 )
        $cts->add_title (1, "Aucun résultat");
      else if ( $req->lines == 1 )
        $cts->add_title (1, "Résultat");
      else
        $cts->add_title (1, "Résultats");

      while ($row = $req->get_row()) {
        $exif = @exif_read_data("/data/matmatronch/".$row["id_utilisateur"].".jpg", 0, true);
        $date_prise_vue = $exif["FILE"]["FileDateTime"] ? $exif["FILE"]["FileDateTime"] : '';

        $cts->puts(
            "<div class=\"utl\">\n".
              "<b>".$row["prenom_utl"]." ".$row["nom_utl"]."</b>\n<br/>\n".
              "<b>".$row["surnom_utbm"]."</b>\n<br/>\n".
              "<img src=\"/data/matmatronch/".$row["id_utilisateur"].".identity.jpg?".$date_prise_vue."\"/>\n".
              "<a href=\"mailto:".$row["email_utl"]."\">".$row["email_utl"]."</a>\n<br/>\n".
              "<a href=\"tel:".$row["tel_portable_utl"]."\">".$row["tel_portable_utl"]."</a>\n<br/>\n".
            "</div>\n"
          );
      }
  }
}

$site->add_contents($cts);

$frm = new form("mtmsearch","./matmat.php",true,"POST","Recherche");
$frm->add_text_field("pattern", "Nom, surnom, téléphone ...", "", true);
$frm->add_submit("simplesearch", "Rechercher");

$site->add_contents($frm);

/* Do not cross. */
$site->end_page();

?>
