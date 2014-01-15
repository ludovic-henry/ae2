<?php
/* Copyright 2006
 * - Laurent Colnat
 * - Portions par Pierre Mauduit ;-)
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

require_once ($topdir . "include/interface.inc.php");

$id = intval ($_REQUEST['id']);
$req = new requete (new mysqlae(),
		    "SELECT `citation` FROM `utl_etu`
                       WHERE `id_utilisateur` = $id
                     LIMIT 1");
if ($req->lines)
{
	$res = $req->get_row ();
	$citation = $res[0];
}

header("Content-Type: text/html; charset=utf-8");

echo "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01//EN\" ".
     "\"http://www.w3.org/TR/html4/strict.dtd\">";

echo "<html>\n";
echo "<head>\n";
echo "<meta http-equiv=\"Content-Type\" content=\"text/html; ".
     "charset=UTF-8\">\n";


echo "<title>Photo Matmatronch</title>
      <link rel=\"stylesheet\" type=\"text/css\" href=\"".
       $topdir."themes/default/css/site.css\" />
      </head>
      <body>\n";

echo "<center>";
echo "<a href=\"javascript:window.close()\">".
     "<img src=\"".$topdir."data/matmatronch/" .
     $id .".jpg\" style=\"margin-bottom: 0.5em; margin-top: 0.5em;\">".
     "</a><br/>";
if (!empty($citation) && isset($citation))
	echo "<i>" . $citation . "</i><br/><br/>";

echo "<input type=\"submit\" class=\"connectsubmit\" id=\"connectsubmit\"".
     " value=\"Fermer cette fenetre\" OnClick=\"window.close()\"/>";

echo "</center><br/>";
echo "</body>\n</html>\n";

?>
