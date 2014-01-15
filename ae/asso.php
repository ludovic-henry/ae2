<?php

// Déprécié, conservé temporairement en attendant la mise à jour de tous les liens

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

if ( $_REQUEST['action'] == "edit" )
{
  header("Location:../asso.php?page=edit&id_asso=".$_REQUEST["id_asso"]);
  exit();
}
else if ( $_REQUEST['action'] == "admin" || $_REQUEST['page'] == "admin" )
{
  header("Location:../asso/membres.php?id_asso=".$_REQUEST["id_asso"]);
  exit();
}

header("Location:../asso.php");
exit();

?>
