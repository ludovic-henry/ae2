<?
/** @file
 * Generation d'images d'arbre genealogique
 *
 */
/* Copyright 2005,2006
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
 * 02111-1307, USA.
 */
require_once ($topdir . "include/globals.inc.php");
require_once ($topdir . "include/watermark.inc.php");


class genealogie
{
  /* Fichier de configuration du graphe */
  var $out_conf;

  /* un nom */
  var $nom;

  /* utilisateur (id) */
  var $id_utl;

  /* utilisateur (surnom) */
  var $surnom;

  /* connexion a la base */
  var $db;

  /* fichier de configuration du graphe */
  var $conf_file;

  /* fichier png de sortie */
  var $png_file;
  /* fillots deja "explores" */
  var $explored;

  function genealogie ($name = "genealogie",
           $vspace = "1.2")
  {
    /* nom */
    $this->name = $name;

    /* identifiant de graphique */
    $rand_id = substr(md5(microtime(true)), 0, 5);
    /* fichiers */
    $this->conf_file = "/tmp/". $rand_id . "_genea.data";
    $this->png_file = "/tmp/".  $rand_id . "_genea.png";

    /* debut de config */
    $this->out_conf = "digraph $this->name {\n";
    $this->out_conf .= "\tranksep = \"$vspace equally\";\n";
    $this->out_conf .= "\tnode [shape=box,style=filled,color=firebrick1];\n";
 }

  function generate_filiation_utl ($id_utl, $db)
  {
    /* affectation variables membres */
    $this->id_utl = $id_utl;
    $this->db = $db;
    /* tableau des fillots deja parses */
    $this->explored = array ();

    $this->generate_conf_utl ();
  }

  /*
   * Generation de la configuration pour utilisateur
   *
   */
  function generate_conf_utl ()
  {
    /* on recupere le surnom de l'etudiant dont on veut
     * la "descendance"
     */
    $req = "SELECT COALESCE(`surnom_utbm`,`alias_utl`),
                   CONCAT(`prenom_utl`,' ',`nom_utl`) AS `nom`

            FROM `utilisateurs`
            INNER JOIN `utl_etu_utbm` USING ( `id_utilisateur` )
            WHERE `utilisateurs`.`id_utilisateur` = ". $this->id_utl;

    $sql = new requete ($this->db, $req);
    $rs = $sql->get_row ();
    $this->surnom = $rs[0];
    $nom = $rs[1] . "\\n" . $rs[0];

    $this->get_childs ($this->id_utl, $nom, 3);

    /* fin configuration */
    $this->out_conf .= "}\n";
  }

  function generate_conf_from_array($datas)
  {
    foreach ($datas as $nom => $nom_child)
      $this->write_on_conf ($nom, $nom_child);
    /* fin configuration */
    $this->out_conf .= "}\n";
  }

  function generate_conf_from_string ($datas)
  {
    $this->out_conf = $datas;
  }
  /*
   * Recuperation des surnoms des fillots
   * attention : il est possible que cet algorithme soit
   * tres vite lourd au cours du temps
   */
  function get_childs ($id, $nom, $rank)
  {
    if($rank==0)
      return;
    $req = "SELECT COALESCE(`surnom_utbm`,`alias_utl`),
                   CONCAT(`utilisateurs`.`prenom_utl`,' ', `utilisateurs`.`nom_utl`) AS `nom`,
                   `utilisateurs`.`id_utilisateur`
            FROM `utilisateurs`
            INNER JOIN `utl_etu_utbm` USING ( `id_utilisateur` )
            LEFT JOIN `parrains`
                 ON `utilisateurs`.`id_utilisateur`
                   = `parrains`.`id_utilisateur_fillot`
      WHERE `parrains`.`id_utilisateur` = ". $id;

    $sql = new requete ($this->db, $req);

    /* condition de sortie */
    if ($sql->lines == 0)
      return;

    for ($i = 0; $i < $sql->lines; $i++)
      {
  /* recuperation du surnom */
  $infos = $sql->get_row();
  $nom_child = $infos[1] . "\\n" . $infos[0];
  $id_child  = $infos[2];
  $this->write_on_conf ($nom, $nom_child);
  if (!in_array($id_child, $this->explored))
    {
      $this->explored[] = $id_child;
      //$this->write_on_conf ($nom, $nom_child);
      /* descente recursive dans la genealogie */
      $this->get_childs($id_child, $nom_child, $rank-1);
    }
      }
    return;
  }

  /*
   * ecriture dans la configuration
   *
   */
  function write_on_conf ($nom_parrain, $nom_fillot)
  {
    $this->out_conf .= "\t\"".$nom_parrain."\" -> \"". $nom_fillot."\"\n";
  }

  /*
   * generation du graphe
   */
  function generate ()
  {
    file_put_contents ($this->conf_file, $this->out_conf);

    /* appel du binaire */
    exec ("/usr/share/php5/exec/genealog.sh " . $this->conf_file .
     " " . $this->png_file);

    if (!file_exists ($this->png_file))
    {
      $this->destroy ();
      return;
    }

    /* tunage sauce AE */
    $img_wmarked = new img_watermark (imagecreatefrompng($this->png_file));
    $img_wmarked->save_image($this->png_file);
    $img_wmarked->destroy();

    header("Pragma: public");
    header("Expires: 0");
    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
    header("Content-Type: image/png");
    header("Content-Disposition: inline; filename=".
    basename($this->png_file));
    readfile($this->png_file);

    $this->destroy ();
  }
  /*
   * destruction propre
   *
   */
  function destroy ()
  {
    @unlink ($this->png_file);
    @unlink ($this->conf_file);
  }
}

?>
