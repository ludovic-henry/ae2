<?php
/* Copyright 2011
 * - Jérémie Laval < jeremie dot laval at gmail dot com >
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

/* Ici, on utilise une version sérialisé d'un array associatif $tab[$val] = $val
 * (histoire de pas les rendre disponible par tarball ou svn) pour stocker les noms
 * et prénoms des nouveaux diplomes (liste fournie par l'administration pour des
 * choses spécifiques comme le Gala) et du coup faire un traitement sélectif pour
 * le groupe n°42 (produit eboutic notamment) */

define ('NAMES_PATH', 'nvdiplomes/names');
define ('FNAMES_PATH','nvdiplomes/firstnames');

$names = null;
$fnames = null;

function escape_name ($iname)
{
    $iname = ereg_replace("(e|é|è|ê|ë|É|È|Ê|Ë)","e",$iname);
    $iname = ereg_replace("(a|à|â|ä|À|Â|Ä)","a",$iname);
    $iname = ereg_replace("(i|ï|î|Ï|Î)","i",$iname);
    $iname = ereg_replace("(c|ç|Ç)","c",$iname);
    $iname = ereg_replace("(o|O|Ò|ò|ô|Ô)","(o|O|Ò|ò|ô|Ô)",$iname);
    $iname = ereg_replace("(u|ù|ü|û|Ü|Û|Ù)","u",$iname);
    $iname = ereg_replace("(n|ñ|Ñ)","n",$iname);

    return $iname;
}

function is_nouveau_diplome ($user)
{
    if ($names == null)
        $names = unserialize (file_get_contents (NAMES_PATH));
    if ($fnames == null)
        $fnames = unserialize (file_get_contents (FNAMES_PATH));

    echo "Nom demande: ".escape_name ($user->nom)."\n";
    echo "Prénom demande: ".escape_name ($user->prenom)."\n";

    return array_key_exists (strtoupper (escape_name ($user->nom)), $names)
        && array_key_exists (strtoupper (escape_name ($user->prenom)), $fnames);
}

?>
