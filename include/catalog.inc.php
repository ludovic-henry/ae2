<?php
/* Copyright 2006,2007
 * - Julien Etelain < julien at pmad dot net >
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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA
 */

/**
 * @file
 * @author Julien Etelain
 * @author Pierre Mauduit
 */

/**
 * Donnés sur les différents objets traités.
 *
 * Chaque nom de classe est associée à un tableau :
 * - [0] Nom du champ SQL d'identification
 * - [1] Nom du champ SQl du nom (ou alias systèmatiquement utilisé)
 * - [2] Fichier d'iconne associée (du dossier images/icons/(16|32)/)
 * - [3] Url de la page d'information
 * - [4] Nom de table SQL (facultatif)
 * - [5] Fichier où est déclarée la classe (facultatif)
 * - [6] Nom de la table pour associer des tags aux objets de l'entité (facultatif)
 *
 * @ingroup stdentity
 */
$GLOBALS['entitiescatalog'] = array (
  'utilisateur'     => array ('id_utilisateur', 'nom_utilisateur'/*alias*/, 'user.png', 'user.php', null, 'utilisateur.inc.php'),
  'page'            => array ('id_page', 'titre_page', 'page.png', 'article.php'),
  'wiki'            => array ('id_wiki', 'fullpath_wiki', 'page.png', 'wiki2/'),
  'asso'            => array ('id_asso', 'nom_asso', 'asso.png', 'asso.php', 'asso', 'asso.inc.php', 'asso_tag' ),
  'group'           => array ('id_groupe', 'nom_groupe', 'group.png', 'group.php', 'groupe' ),
  'sitebat'         => array ('id_site', 'nom_site', 'site.png', 'sitebat.php' ),
  'salle'           => array ('id_salle', 'nom_salle', 'salle.png', 'salle.php' ),
  'batiment'        => array ('id_batiment', 'nom_bat', 'batiment.png', 'batiment.php' ),
  'objtype'         => array ('id_objtype','nom_objtype','objtype.png','objtype.php', 'inv_type_objets' ),
  'objet'           => array ('id_objet','nom_objet','objet.png','objet.php'),
  'reservation'     => array ('id_salres','id_salres','reservation.png','reservation.php'),
  'assocpt'         => array ('id_assocpt', 'nom_asso', 'asso.png', 'asso.php'),
  'typeproduit'     => array ('id_typeprod', 'nom_typeprod', 'typeprod.png', 'comptoir/admin.php', 'cpt_type_produit' ),
  'catphoto'        => array ('id_catph', 'nom_catph', 'catph.png', 'sas2/', 'sas_cat_photos' ),
  'photo'           => array ('id_photo', 'id_photo', 'photo.png', 'sas2/', 'sas_photos', null, 'sas_photos_tag' ),
  'licence'         => array ('id_licence', 'titre', 'licence.png', 'sas2/licences.php', 'licences'),

  // Compta : Classeurs
  'classeur_compta' => array ('id_classeur', 'nom_classeur', 'classeur.png', 'compta/classeur.php'),
  'compte_asso'     => array ('id_cptasso', 'nom_cptasso','compte.png','compta/cptasso.php'),
  'budget'          => array ('id_budget','nom_budget','budget.png','compta/budget.php'),
  'compte_bancaire' => array ('id_cptbc','nom_cptbc','cptbc.png','compta/cptbc.php'),
  'operation'       => array ('id_op', 'id_op', 'file.png', 'compta/classeur.php'),
  'efact'           => array ('id_efact','titre_facture','file.png','compta/efact.php','cpta_facture','efact.inc.php'),
  'notefrais'       => array ('id_notefrais','id_notefrais','file.png','compta/notefrais.php','cpta_notefrais','notefrais.inc.php'),

  'emprunt'         => array ('id_emprunt', 'id_emprunt', 'emprunt.png', 'emprunt.php', 'inv_emprunt' ),
  'produit'         => array ('id_produit', 'nom_prod', 'produit.png', 'comptoir/admin.php', 'cpt_produits','produit.inc.php' ),
  'facture'         => array ('id_facture', 'id_facture', 'emprunt.png', 'comptoir/gen_fact.php', 'cpt_debitfacture' ),
  'editeur'         => array ('id_editeur', 'nom_editeur', 'editeur.png', 'biblio/', 'bk_editeur'),
  'serie'           => array ('id_serie', 'nom_serie', 'serie.png', 'biblio/', 'bk_serie'),
  'auteur'          => array ('id_auteur', 'nom_auteur', 'auteur.png', 'biblio/', 'bk_auteur'),
  'livre'           => array ('id_livre', 'nom_livre', 'livre.png', 'biblio/'),
  'jeu'             => array ('id_jeu', 'nom_jeu', 'jeu.png', 'biblio/'),
  'sondage'         => array ('id_sondage','question','sondage.png','sondage.php'),
  'comptoir'        => array ('id_comptoir', 'nom_cpt', 'misc.png', false, 'cpt_comptoir' ),
  'dfile'           => array ('id_file', 'titre_file', 'file.png', 'd.php',false,'files.inc.php','d_file_tag'),
  'dfolder'         => array ('id_folder', 'titre_folder', 'folder.png', 'd.php',false,'folder.inc.php'),

  // Forum
  'admin_forum'     => array ('idx_forum', 'admin_forum', 'forum.png', 'forum2/admin/update_forum.php'),
  'forum'           => array ('id_forum', 'titre_forum', 'forum.png', 'forum2/'),
  'sujet'           => array ('id_sujet', 'titre_sujet', 'sujet.png', 'forum2/'),
  'message'         => array ('id_message', 'id_message', 'message.png', 'forum2/'),

  // Localisation
  'pays'            => array ('id_pays', 'nom_pays', 'pays.png', 'loc.php', 'loc_pays','pays.inc.php'),
  'ville'           => array ('id_ville', 'nom_ville', 'ville.png', 'loc.php', 'loc_ville','ville.inc.php'),
  'lieu'            => array ('id_lieu', 'nom_geopoint', 'lieu.png', 'loc.php', 'loc_lieu','lieu.inc.php'),

  // Compta : Entreprises
  'entreprise'      => array ('id_ent', 'nom_entreprise', 'entreprise.png', 'entreprise.php', 'entreprise','entreprise.inc.php' ),
  'secteur'         => array ('id_secteur', 'nom_secteur', 'lieu.png', 'entreprise.php', 'secteur'),

  // Pedagogie
  'uv'              => array ('id_uv', 'code', 'emprunt.png', 'pedagogie/uv.php', 'pedag_uv', '../../pedagogie/include/uv.inc.php'),

  // Nouvelles
  'nouvelle'        => array ('id_nouvelle', 'titre_nvl', 'misc.png', 'news.php', 'nvl_nouvelles', 'news.inc.php', 'nvl_nouvelles_tag'),
  'canalnouvelles'  => array ('id_canal', 'nom_canal', 'misc.png', 'news.php', 'nvl_canal', 'news.inc.php'),

  // Petit Géni 2 : Reseaux de bus
  'reseaubus'       => array ('id_reseaubus', 'nom_reseaubus', 'misc.png', 'pg2/bus.php', 'pg_reseaubus', 'bus.inc.php' ),
  'lignebus'        => array ('id_lignebus', 'nom_lignebus', 'misc.png', 'pg2/bus.php', 'pg_lignebus', 'bus.inc.php' ),
  'arretbus'        => array ('id_arretbus', 'nom_geopoint', 'misc.png', 'pg2/bus.php', 'geopoint', 'bus.inc.php' ),

  // Petit Géni 2 : Rues
  'typerue'         => array ('id_typerue', 'nom_typerue', 'misc.png', 'pg2/rue.php', 'pg_typerue', 'rue.inc.php' ),
  'rue'             => array ('id_rue', 'nom_rue', 'misc.png', 'pg2/rue.php', 'pg_rue', 'rue.inc.php' ),

  // Petit Géni 2 : Fiches
  'pgcategory'      => array ('id_pgcategory', 'nom_pgcategory', 'misc.png', 'pg2/', 'pg_category', 'pgfiche.inc.php', 'pg_category_tags' ),
  'pgfiche'         => array ('id_pgfiche', 'nom_geopoint', 'misc.png', 'pg2/', 'pg_fiche', 'pgfiche.inc.php', 'pg_fiche_tags' ),

  // Petit Géni 2 : Types
  'service'         => array ('id_service', 'nom_service', 'misc.png', null, 'pg_service', 'pgtype.inc.php'),
  'typetarif'       => array ('id_typetarif', 'nom_typetarif', 'misc.png', null, 'pg_typetarif', 'pgtype.inc.php'),
  'typereduction'   => array ('id_typereduction', 'nom_typereduction', 'misc.png', null, 'pg_typereduction', 'pgtype.inc.php'),

  'planet_flux'     => array (null,'planet',null,null,null,null,'planet_flux_tags')
  );

?>
