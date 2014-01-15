<?

/* Copyright 2007
 * - Julien Etelain < julien at pmad dot net >
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

/*
 * Some parts of this file is subject to version 2.02 of the PHP license
 */
// +----------------------------------------------------------------------+
// | PHP Version 4                                                        |
// +----------------------------------------------------------------------+
// | Copyright (c) 1997-2003 The PHP Group                                |
// +----------------------------------------------------------------------+
// | This source file is subject to version 2.02 of the PHP license,      |
// | that is bundled with this package in the file LICENSE, and is        |
// | available at through the world-wide-web at                           |
// | http://www.php.net/license/2_02.txt.                                 |
// | If you did not receive a copy of the PHP license and are unable to   |
// | obtain it through the world-wide-web, please send a note to          |
// | license@php.net so we can mail you a copy immediately.               |
// +----------------------------------------------------------------------+
// | Authors: Hartmut Holzgraefe <hholzgra@php.net>                       |
// |          Christian Stocker <chregu@bitflux.ch>                       |
// +----------------------------------------------------------------------+

$topdir="./";
require_once($topdir . "include/serverwebdavae.inc.php");
require_once($topdir . "include/entities/files.inc.php");
require_once($topdir . "include/entities/folder.inc.php");
require_once($topdir . "include/entities/asso.inc.php");
/**
 * Serveur WEBDAV d'accès à la partie fichier
 * @ingroup aedrive
 * @author Julien Etelain
 */
class serverwebdavaedrive extends webdavserverae
{

  function get_entity_for_path ( $path )
  {
    $tokens = explode("/",$path);

    if ( count($tokens) > 0 && empty($tokens[0]) )
      array_shift($tokens);

    if ( count($tokens) > 0 && empty($tokens[count($tokens)-1]) )
      array_pop($tokens);

    if ( count($tokens) == 0 )
    {
      $ent = new dfolder($this->db); // la racine est en lecture seule
      $ent->displayname="WebDAV";
      $ent->date_ajout=0;
      return $ent;
    }

    $ent=null;
    $id_folder_parent=null;

    foreach ( $tokens as $idx => $token )
    {
      //echo "token($idx) : $token\n";
      if ( $token != "." )
      {
        if ( $token == ".." )
        {
          if ( !is_null($ent) && get_class($ent) == "dfolder" )
          {
            $prev = $ent;
            $ent = new dfolder($this->db,$this->dbrw);
            $ent->load_by_id($prev->id_folder_parent);
          }
        }
        else
        {
          $ent = new dfolder($this->db,$this->dbrw);
          if ( !$ent->load_by_nom_fichier($id_folder_parent,$token) )
          {
            $ent = new dfile($this->db,$this->dbrw);

            //echo "not a folder, tries for a file\n";

            if ( !$ent->load_by_nom_fichier($id_folder_parent,$token) )
              return null;

            //echo "it's a file ".$ent->nom_fichier."\n";

            if ( $idx != count($tokens)-1 ) // ce n'est pas le dernier element, donc c'est faux (tm)
              return null;

            return $ent;
          }
        }
        //echo "continues (".$ent->nom_fichier.")\n";
        $id_folder_parent = $ent->id;
      }
    }

    return $ent;
  }

  function entinfo($path,$ent)
  {
    $info = array();

    $info["props"] = array();
    $info["props"][] = $this->mkprop("displayname",     $ent->nom_fichier);
    $info["props"][] = $this->mkprop("creationdate",    $ent->date_ajout);
    $info["props"][] = $this->mkprop("getlastmodified", $ent->date_modif);

    if (get_class($ent) == "dfolder")
    {
      $info["props"][] = $this->mkprop("resourcetype", "collection");
      $info["props"][] = $this->mkprop("getcontenttype", "httpd/unix-directory");
      $info["path"] = $this->_urlencode($this->_slashify($path));
    }
    else
    {
      $info["props"][] = $this->mkprop("resourcetype", "");
      $info["props"][] = $this->mkprop("getcontenttype", $ent->mime_type);
      $info["props"][] = $this->mkprop("getcontentlength", $ent->taille);
      $info["path"] = $this->_urlencode($path);
    }

    return $info;
  }

  function PROPFIND(&$options, &$files)
  {
    $ent = $this->get_entity_for_path($options["path"]);

    if ( is_null($ent) )
      return false;

    if ( $ent->is_valid() && !$ent->is_right($this->user,DROIT_LECTURE) )
      return "403 Forbidden";

    $files["files"] = array();
    $files["files"][] = $this->entinfo($options["path"],$ent);

    if ( !empty($options["depth"]) && get_class($ent) == "dfolder" )
    {
      $options["path"] = $this->_slashify($options["path"]);

      $sub = $ent->get_folders($this->user);
      $sent= new dfolder($this->db);
      while ( $row = $sub->get_row() )
      {
        $sent->_load($row);
        $files["files"][] = $this->entinfo($options["path"].$sent->nom_fichier,$sent);
        // TODO recursion needed if "Depth: infinite"
      }

      $sub = $ent->get_files($this->user);
      $sent= new dfile($this->db);
      while ( $row = $sub->get_row() )
      {
        $sent->_load($row);
        $files["files"][] = $this->entinfo($options["path"].$sent->nom_fichier,$sent);
      }
    }
    return true;
  }

  function GET(&$options)
  {
    $ent = $this->get_entity_for_path($options["path"]);

    if ( is_null($ent) )
      return false;

    if ( $ent->is_valid() && !$ent->is_right($this->user,DROIT_LECTURE) )
      return "403 Forbidden";

    if ( get_class($ent) == "dfolder" )
    {
      $path = $this->_slashify($options["path"]);

      if ($path != $options["path"])
      {
        header("Location: ".$this->base_uri.$path);
        exit;
      }

		  header("Content-Type: text/html; charset=utf-8");

      $format = "%15s  %-19s  %-s\n";

      echo "<html><head><title>Index of ".htmlspecialchars($options['path'])."</title></head>\n";

      echo "<h1>Index of ".htmlspecialchars($options['path'])."</h1>\n";

      echo "<pre>";
      printf($format, "Size", "Last modified", "Filename");
      echo "<hr>";

      $sub = $ent->get_folders($this->user);
      while ( $row = $sub->get_row() )
      {
        $name = htmlspecialchars($row['nom_fichier_folder']);
        printf($format,
                number_format($row['taille_folder']),
                $row['date_modif_folder'],
                "<a href='$name/'>$name</a>");
      }

      $sub = $ent->get_files($this->user);
      while ( $row = $sub->get_row() )
      {
        $name = htmlspecialchars($row['nom_fichier_file']);
        printf($format,
                number_format($row['taille_file']),
                $row['date_ajout_file'],
                "<a href='$name'>$name</a>");
      }
      echo "</pre>";
      echo "</html>\n";
      return false;
    }

    $options['mimetype'] = $ent->mime_type;
    $options['mtime'] = $ent->date_modif;
    $options['size'] = $ent->taille;
    $options['stream'] = fopen($ent->get_real_filename(), "r");

    return true;
  }

  function PUT(&$options)
  {

    if ( !$this->user->is_valid() )
      return "403 Forbidden";

    list($ppath,$nom_fichier)=$this->_explode_path($options["path"]);

    $parent = $this->get_entity_for_path($ppath);

    if ( is_null($parent) || get_class($parent) != "dfolder" )
        return "409 Conflict";

    if ( !$parent->is_valid() )
      return "403 Forbidden";

    $ent = $parent->get_child_by_nom_fichier($nom_fichier);

    if ( !is_null($ent) )
    {
      if ( get_class($ent) == "dfolder" )
        return "409 Conflict";

      if ( !$ent->is_right($this->user,DROIT_ECRITURE) )
        return "403 Forbidden";

      if ( $ent->is_locked($this->user) )
        return "409 Conflict";

      $stat = "204 No Content";

      // Mise à jour du contenu
      $ent->_new_revision($this->user->id,$options["content_length"],$options["content_type"]);
    }
    else
    {
      if ( !$parent->is_right($this->user,DROIT_AJOUTITEM) )
        return "403 Forbidden";

      $stat = "201 Created";

      // Nouveau fichier
      $ent = new dfile($this->db,$this->dbrw);
      $ent->herit($parent);
      $ent->id_utilisateur = $this->user->id;
      $ent->droits_acces |= 0x330; // Droits minimaux, pour pas se faire signaler de faux "bugs"
      $ent->create_empty ( $parent->id, $nom_fichier, $options["content_length"], $options["content_type"] );
    }

    // Ecrit le contenu
    $stream = fopen($ent->get_real_filename(),"w");

    if ( $stream === false )
      return "403 Forbidden";

    if (!empty($options["ranges"])) // Seulement un morceau
    {
      if (0 == fseek($stream, $option["ranges"][0]["start"], SEEK_SET))
      {
        $length = $option["ranges"][0]["end"]-$option["ranges"][0]["start"]+1;
        if (!fwrite($stream, fread($options["stream"], $length)))
          $stat = "403 Forbidden";
      }
      else
        $stat = "403 Forbidden";
    }
    else // Tout le contenu
    {
      while (!feof($options["stream"]))
      {
        if (false === fwrite($stream, fread($options["stream"], 4096)))
        {
          $stat = "403 Forbidden";
          break;
        }
      }
    }

    fclose($stream);

    $ent->generate_thumbs(); // Fabrique les miniatures (pour l'accés web)

    return $stat;
  }

  function MKCOL($options)
  {

    list($ppath,$nom_fichier)=$this->_explode_path($options["path"]);

    $ent = $this->get_entity_for_path($ppath);

    if ( is_null($ent) )
        return "409 Conflict";

    if ( get_class($ent) != "dfolder" || !$ent->is_valid() )
        return "403 Forbidden";

    if ( !$ent->is_filename_avaible($nom_fichier) )
        return "405 Method not allowed";

    if (!empty($this->_SERVER["CONTENT_LENGTH"])) // no body parsing yet
      return "415 Unsupported media type";

    if ( !$ent->is_right($this->user,DROIT_AJOUTCAT) || !$this->user->is_valid() )
      return "403 Forbidden";

    $new_ent = new dfolder($this->db,$this->dbrw);
    $new_ent->herit($ent);
    $new_ent->id_utilisateur = $this->user->id;
    $new_ent->droits_acces |= 0xDD0; // Droits minimaux, pour pas se faire signaler de faux "bugs"
    $new_ent->add_folder ( $nom_fichier, $ent->id, "", $ent->id_asso );

    if ( !$new_ent->is_valid() )
      return "500 Internal server error";

    return "201 Created";
  }

  function DELETE($options)
  {
    $ent = $this->get_entity_for_path($options["path"]);

    if ( is_null($ent) )
      return "404 Not found";

    if ( !$ent->is_right($this->user,DROIT_ECRITURE) || !$this->user->is_valid() )
      return "403 Forbidden";

    $ent->delete();

    return "204 No Content";
  }

  function MOVE($options)
  {

    if (!empty($this->_SERVER["CONTENT_LENGTH"]))
      return "415 Unsupported media type";

    if (isset($options["dest_url"]))
      return "502 bad gateway";

    // 1- La source

    $ent_src = $this->get_entity_for_path($options["path"]);

    if ( is_null($ent_src) )
      return "404 Not found";

    if ( !$this->user->is_valid() )
      return "403 Forbidden";

    if ( !$ent_src->is_valid() ||
         is_null($ent_src->id_folder_parent) ) // Racine, et dossier dans la racine intouchable
      return "403 Forbidden";

    if ( get_class($ent_src) == "dfolder" && ($options["depth"] != "infinity") )
      // RFC 2518 Section 9.2, last paragraph
      return "400 Bad request";

    // 2- Repertoire cible (parent de la destination) / Destination

    $ent_dst = $this->get_entity_for_path($options["dest"]);
    $ent_folder_dst = null;
    $created = true;

    if ( !is_null($ent_dst) ) // La destination existe déjà
    {
      if ( get_class($ent_dst) == "dfolder" ) // La destination est un dossier
      {
        if ( !$options["overwrite"] ) // On ne veut pas overwrite, echec donc
        {
          return "412 precondition failed";
        }
        // Dans ce cas, on considére que la destination est en fait le repertoire cible
        $ent_folder_dst = $ent_dst; // Le repertoire cible est donc la destination
        $ent_dst = $ent_folder_dst->get_child_by_nom_fichier($this->_basename($options["path"])); // La destination est donc le fichier/dossier nommé comme la source dans le repertoire cible
        $created=false;
      }
    }

    if ( is_null($ent_folder_dst) ) // On n'a pas encore déterminé le repertoire cible
    {
      if ( is_null($ent_dst) ) // La destination n'existe pas, on récupére le repertoire cible
      {
        list($ppath,$nom_fichier)=$this->_explode_path($options["dest"]);
        $ent_folder_dst = $this->get_entity_for_path($ppath);
      }
      else // La destination existe, son parent est le repertoire cible
      {
        $ent_folder_dst = new dfolder($this->db);
        $ent_folder_dst->load_by_id($ent_dst->id_folder_parent);
        $nom_fichier = $ent_dst->nom_fichier;
      }
    }

    if ( is_null($ent_folder_dst) )
      return "409 Conflict";

    // Verifie que l'on peut écrire dans le repertoire cible
    if ( !$ent_folder_dst->is_valid() || !$ent_folder_dst->is_right($this->user,DROIT_ECRITURE) )
      return "403 Forbidden";

    if ( !is_null($ent_dst) ) // La destination existe déjà
    {
      if ( $options["overwrite"]) // Si l'overwrite est actif, alors on supprime la destination
      {
        if ( !$ent_dst->is_right($this->user,DROIT_ECRITURE) || !$this->user->is_valid() )
          return "403 Forbidden";

        $ent_dst->delete();

        $created=false;
        // Note: on pourrai faire un backup automatique dans ces cas là
      }
      else // Sinon echec
        return "412 precondition failed";
    }

    // 3- Et enfin... on fait le changement
    if ( !$ent_src->move_to($ent_folder_dst->id,$nom_fichier) )
      return "500 Internal server error";

    return $created ? "201 Created" : "204 No Content";
  }

  function COPY($options)
  {
    ini_set("display_errors", 1);

    if (!empty($this->_SERVER["CONTENT_LENGTH"]))
      return "415 Unsupported media type";

    if (isset($options["dest_url"]))
      return "502 bad gateway";

    // 1- La source

    $ent_src = $this->get_entity_for_path($options["path"]);

    if ( is_null($ent_src) )
      return "404 Not found";

    if ( !$this->user->is_valid() )
      return "403 Forbidden";

    if ( !$ent_src->is_valid() ||
         is_null($ent_src->id_folder_parent) ) // Racine, et dossier dans la racine intouchable
      return "403 Forbidden";

    // 2- Repertoire cible (parent de la destination) / Destination

    $ent_dst = $this->get_entity_for_path($options["dest"]);
    $ent_folder_dst = null;
    $created = true;

    if ( is_null($ent_dst) ) // La destination n'existe pas, on récupére le repertoire cible
    {
      list($ppath,$nom_fichier)=$this->_explode_path($options["dest"]);
      $ent_folder_dst = $this->get_entity_for_path($ppath);
    }
    else // La destination existe, son parent est le repertoire cible
    {
      $ent_folder_dst = new dfolder($this->db);
      $ent_folder_dst->load_by_id($ent_dst->id_folder_parent);
      $nom_fichier = $ent_dst->nom_fichier;
    }

    if ( is_null($ent_folder_dst) )
      return "409 Conflict";

    // Verifie que l'on peut écrire dans le repertoire cible
    if ( !$ent_folder_dst->is_valid() || !$ent_folder_dst->is_right($this->user,DROIT_ECRITURE) )
      return "403 Forbidden";

    if ( !is_null($ent_dst) ) // La destination existe déjà
    {
      if ( $options["overwrite"]) // Si l'overwrite est actif, alors on supprime la destination
      {
        if ( !$ent_dst->is_right($this->user,DROIT_ECRITURE) || !$this->user->is_valid() )
          return "403 Forbidden";

        $ent_dst->delete();

        $created=false;
        // Note: on pourrai faire un backup automatique dans ces cas là
      }
      else // Sinon echec
        return "412 precondition failed";
    }

    // 3- Et enfin... on fait le boulot

    $depth=-1;
    if ( $options["depth"] != "infinity")
      $depth = intval($options["depth"]);

    if ( get_class($ent_src) == "dfolder" )
      $new_ent = new dfolder($this->db,$this->dbrw);
    else
      $new_ent = new dfile($this->db,$this->dbrw);

    if ( !$new_ent->create_copy_of ( $ent_src, $ent_folder_dst->id, $nom_fichier,$depth ) )
      return "500 Internal server error";

    return $created ? "201 Created" : "204 No Content";
  }

  function PROPPATCH(&$options)
  {
    foreach ($options["props"] as $key => $prop)
      $options["props"][$key]['status'] = "403 Forbidden";

    return "";
  }

  function _explode_path ( $path )
  {
    $tokens = explode("/",$path);

    if ( count($tokens) > 0 && empty($tokens[count($tokens)-1]) )
      array_pop($tokens);

    $nom_fichier = array_pop($tokens);

    return array(implode("/",$tokens),$nom_fichier);
  }

  function _basename ( $path )
  {
    $tokens = explode("/",$path);
    if ( count($tokens) > 0 && empty($tokens[count($tokens)-1]) )
      array_pop($tokens);
    return array_pop($tokens);
  }


}

$dav = new serverwebdavaedrive();

if ( isset($_GET["test"]) )
{
  ini_set("display_errors", 1);

  echo "<pre>";
  echo "\n/public/AE\n";
  print_r($dav->get_entity_for_path("/public/AE"));
  echo "\n/public/AE/\n";
  print_r($dav->get_entity_for_path("/public/AE/"));
  echo "\n/public/AE/Com/ChargeGraphique.pdf\n";
  print_r($dav->get_entity_for_path("/public/AE/Com/ChargeGraphique.pdf"));
  echo "\n/\n";
  print_r($dav->get_entity_for_path("/"));
  echo "\n\n";
  echo "</pre>";
  $opt["path"] = "/";
  $dav->GET($opt);
  exit();
}

$dav->ServeRequest();

?>
