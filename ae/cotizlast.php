<?php
/* Copyright 2011 Jérémie Laval <jeremie dot laval at gmail dot com>
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

$topdir = "../";

require_once($topdir. 'include/site.inc.php');
require_once($topdir. 'include/entities/cotisation.inc.php');
require_once($topdir. "include/cts/user.inc.php");
require_once($topdir. 'include/cts/gallery.inc.php');
require_once($topdir. 'include/cts/special.inc.php');
require_once($topdir. 'include/entities/uv.inc.php');
require_once($topdir. 'include/entities/ville.inc.php');
require_once($topdir. 'include/entities/pays.inc.php');

$site = new site ();

$site->allow_only_logged_users();

if (!$site->user->is_in_group('gestion_ae'))
    $site->error_forbidden('services');

$site->start_page ('services', 'Dernières cotisations');
$site->add_css("css/mmt.css");

$cts = new contents ("Dernières cotisations");

$user = new utilisateur ($site->db);
$gallery = new gallery ();

$num = 15;
if (isset ($_REQUEST['num']) && intval ($_REQUEST['num']) > 0)
    $num = intval ($_REQUEST['num']);

$req = new requete ($site->db, 'SELECT `utilisateurs`.*, `utl_etu`.citation,
        `utl_etu`.adresse_parents, `utl_etu`.ville_parents,
        `utl_etu`.cpostal_parents, `utl_etu`.pays_parents, `utl_etu`.tel_parents,
        `utl_etu`.nom_ecole_etudiant, `utl_etu`.visites, `utl_etu`.id_ville,
        `utl_etu`.id_pays, `utl_etu_utbm`.semestre_utbm,
        `utl_etu_utbm`.branche_utbm, `utl_etu_utbm`.filiere_utbm,
        `utl_etu_utbm`.surnom_utbm, `utl_etu_utbm`.email_utbm,
        `utl_etu_utbm`.promo_utbm, `utl_etu_utbm`.date_diplome_utbm,
        `utl_etu_utbm`.role_utbm, `utl_etu_utbm`.departement_utbm,
        `utilisateurs`.`id_ville` as `id_ville`,
        `utl_etu`.`id_ville` as `ville_parents`,
        `utilisateurs`.`id_pays` as `id_pays`, `utl_etu`.`id_pays` as `pays_parents`
        FROM `ae_cotisations`
        LEFT JOIN `utilisateurs` ON `utilisateurs`.id_utilisateur=`ae_cotisations`.`id_utilisateur`
        LEFT JOIN `utl_etu` ON `utl_etu`.`id_utilisateur`=`utilisateurs`.`id_utilisateur`
        LEFT JOIN `utl_etu_utbm` ON `utl_etu_utbm`.`id_utilisateur`=`utilisateurs`.`id_utilisateur`
        ORDER BY `id_cotisation` DESC LIMIT '.$num);

while ($row = $req->get_row()) {
    $user->_load_all($row);
    $gallery->add_item(new userinfov2($user, "small", false, "user.php", false));
}

$cts->add ($gallery);
$site->add_contents ($cts);

$site->end_page ();

?>
