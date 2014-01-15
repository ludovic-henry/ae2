<?php

/* Copyright 2007
 * - Simon Lopez < simon dot lopez at ayolo dot org >
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

require_once($topdir. "include/site.inc.php");
require_once($topdir."include/cts/sqltable.inc.php");
require_once($topdir."include/entities/svn.inc.php");


$site = new site ();

if ( !$site->user->is_in_group("root") )
  $site->error_forbidden("none","group",7);

$site->start_page("none","Administration / Gestion des droits");
$cts = new contents("<a href=\"./\">Administration</a> / Révocation des droits");

if(isset($_REQUEST['action']) && $_REQUEST['action']=='cleanup')
{
  //à vérifier !!!!
  $sql='DELETE '.
       'FROM utl_groupe '.
       'WHERE ';
  if(isset($_REQUEST['id_groupe']))
    $sql.='id_groupe='.intval($_REQUEST['id_groupe']).' AND ';
  $sql.='NOT EXISTS'.
       ' ('.
       '   SELECT * '.
       '   FROM '.
       '     `ae_cotisations` '.
       '   WHERE '.
       '     `ae_cotisations`.`id_utilisateur`=`utl_groupe`.`id_utilisateur` '.
       '      AND `date_fin_cotis` > NOW() '.
       ' )';
  //$req = new requete($site->db,$sql);
}

$frm = new form('bygroupe',
                '?',
                false,
                'POST',
                'Gestion par groupe');
$sql='SELECT id_groupe '.
     ',nom_groupe '.
     'FROM groupe '.
     'WHERE id_groupe NOT IN ( 7, 20, 25, 35,36, 39, 42, 45 )';
$req = new requete($site->db,$sql);
$groupe=array();
while(list($id,$nom)=$req->get_row())
  $groupe[$id]=$nom;
$frm->add_select_field('id_groupe',
                       'Groupe',
                       $groupe,
                       $_REQUEST['id_groupe']);
$frm->add_select_field('action',
                       'Action',
                       array('rien'=>'Voir',
                             '_cleanup'=>'Nettoyer'));
$frm->add_submit("valid","Go!");
$cts->add($frm,true);

$sql = 'SELECT '.
       '      `u`.`id_utilisateur` '.
       '      ,CONCAT(`u`.`prenom_utl`,\' \',`u`.`nom_utl`) AS nom_utilisateur '.
       '      ,COUNT(*) AS nb '.
       'FROM '.
       '      utl_groupe g '.
       'INNER JOIN utilisateurs u '.
       '      USING ( id_utilisateur ) '.
       'LEFT JOIN ae_cotisations c '.
       '      ON c. id_utilisateur=g.id_utilisateur '.
       '      AND c.date_fin_cotis >= NOW() '.
       'WHERE '.
       '      id_cotisation IS NULL '.
       '      AND id_groupe NOT IN ( 7, 20, 25, 35,36, 39, 42, 45 ) ';
if(isset($_REQUEST['id_groupe']))
  $sql.='      AND id_groupe='.intval($_REQUEST['id_groupe']).' ';
$sql.= 'GROUP BY '.
       '      `u`.`id_utilisateur`'.
       '      ,`u`.`prenom_utl`'.
       '      ,`u`.`nom_utl`';
$req = new requete($site->db,$sql);
$cts->add(new sqltable('bad_rights',
                       'BOUH ! montrons les du doigt !',
                       $req,
                       '',
                       'id_utilisateur',
                       array('nom_utilisateur'=>'Utilisateur',
                             'nb'=>'Occurences'),
                       array(),
                       array(),
                       array()
                      ),
          true
         );

$site->add_contents($cts);
$site->end_page();

?>
