<?php

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

class fsearch extends stdcontents
{

  var $nalnum;

  var $nb;
  var $redirect;

  var $dbpg;

  function pg_touve_motclef ( $mot, $count=null )
  {
    $res = array();

    $pattern2 = ereg_replace("(e|é|è|ê|ë|É|È|Ê|Ë)","(e|é|è|ê|ë|É|È|Ê|Ë)",$mot);
    $pattern2 = ereg_replace("(a|à|â|ä|À|Â|Ä)","(a|à|â|ä|À|Â|Ä)",$pattern2);
    $pattern2 = ereg_replace("(i|ï|î|Ï|Î)","(i|ï|î|Ï|Î)",$pattern2);
    $pattern2 = ereg_replace("(c|ç|Ç)","(c|ç|Ç)",$pattern2);
    $pattern2 = ereg_replace("(o|O|Ò|ò|ô|Ô)","(o|O|Ò|ò|ô|Ô)",$pattern2);
    $pattern2 = ereg_replace("(u|ù|ü|û|Ü|Û|Ù)","(u|ù|ü|û|Ü|Û|Ù)",$pattern2);
    $pattern2 = ereg_replace("(n|ñ|Ñ)","(n|ñ|Ñ)",$pattern2);

    $pattern = ereg_replace("(e|é|è|ê|ë|É|È|Ê|Ë)","e",$mot);
    $pattern = ereg_replace("(a|à|â|ä|À|Â|Ä)","a",$pattern);
    $pattern = ereg_replace("(i|ï|î|Ï|Î)","i",$pattern);
    $pattern = ereg_replace("(c|ç|Ç)","c",$pattern);
    $pattern = ereg_replace("(o|O|Ò|ò|ô|Ô)","(o|O|Ò|ò|ô|Ô)",$pattern);
    $pattern = ereg_replace("(u|ù|ü|û|Ü|Û|Ù)","u",$pattern);
    $pattern = ereg_replace("(n|ñ|Ñ)","n",$pattern);
    $sqlpattern = mysql_real_escape_string($pattern);

    $req = new requete($this->dbpg,"SELECT id_motclef,nom_motclef,titre_motclef " .
      "FROM pg_motclef " .
      "WHERE nom_motclef REGEXP '^".utf8_decode($sqlpattern)."' ".
      "ORDER BY nom_motclef".
      (is_null($count)?"":" LIMIT $count") );

    while ( list($id,$clef,$titre) = $req->get_row() )
      $res[$id]=array(utf8_encode(/*$cle*/$titre),eregi_replace($pattern2,"<b>\\0</b>", utf8_encode($titre)));
    // titre a la place de cle pour compatibilité avec le moteur de pgae.php en attendant màj
    return $res;
  }

  /* exhaustive: if enabled, we will query more ressources, for interactive fsearch this is disabled
   * unauthentified: this is only used by cron script that caches results, the permission are rechecked at another place
  */
  function fsearch ( $site, $exhaustive=true, $unauthentified=false )
  {
    global $wwwtopdir, $topdir;


    if ( $_REQUEST["pattern"] == "" )
      return;

    $this->nalnum = "\. _\n\r,;:'\!\?\(\)\-";

    $this->nb=0;

    $pattern = preg_replace('/(e|é|è|ê|ë|É|È|Ê|Ë)/i','(e|é|è|ê|ë|É|È|Ê|Ë)',$_REQUEST['pattern']);
    $pattern = preg_replace('/(a|à|â|ä|À|Â|Ä)/i','(a|à|â|ä|À|Â|Ä)',$pattern);
    $pattern = preg_replace('/(i|ï|î|Ï|Î)/i','(i|ï|î|Ï|Î)',$pattern);
    $pattern = preg_replace('/(c|ç|Ç)/i','(c|ç|Ç)',$pattern);
    $pattern = preg_replace('/(o|O|Ò|ò|ô|Ô)/i','(o|O|Ò|ò|ô|Ô)',$pattern);
    $pattern = preg_replace('/(u|ù|ü|û|Ü|Û|Ù)/i','(u|ù|ü|û|Ü|Û|Ù)',$pattern);
    $pattern = preg_replace('/(n|ñ|Ñ)/i','(n|ñ|Ñ)',$pattern);
    $pattern = preg_replace('(\d+)', '(0*)$0', $pattern);
    $sqlpattern = mysql_real_escape_string($pattern);
    $pattern = '/'.$pattern.'/i';

    // Utilisateurs
    if ( $unauthentified || ($site->user->is_valid() && ($site->user->cotisant || $site->user->utbm))) {

        if ( $unauthentified || (!$site->user->is_in_group("gestion_ae") && !$site->user->is_asso_role ( 27, 1 ) && !$site->user->is_in_group("visu_cotisants")) ) {
        if ($site->user->cotisant || $unauthentified)
          $force_sql = "AND `publique_utl`>='1'";
        else
          $force_sql = "AND `publique_utl`='2'";
      }
      else
        $force_sql = "";

      $req = new requete($site->db,
                         'SELECT utilisateurs.id_utilisateur ' .
                         'FROM utilisateurs ' .
                         'INNER JOIN utl_etu_utbm ON utl_etu_utbm.id_utilisateur = utilisateurs.id_utilisateur ' .
                         'WHERE (CONCAT(prenom_utl,\' \',nom_utl) REGEXP \'^'.$sqlpattern.'\' '.$force_sql.') OR ' .
                         '(CONCAT(nom_utl,\' \',prenom_utl) REGEXP \'^'.$sqlpattern.'\' '.$force_sql.') OR ' .
                         '(surnom_utbm!=\'\' AND surnom_utbm REGEXP \'^'.$sqlpattern.'\' '.$force_sql.') LIMIT 1');

      if ( $req->lines > 0 ) {
        $req = new requete($site->db,
         'SELECT CONCAT(prenom_utl,\' \',nom_utl),\'1\' as method, utilisateurs.*' .
         ' FROM utilisateurs' .
         ' LEFT JOIN utl_etu USING ( id_utilisateur )' .
         ' WHERE CONCAT(prenom_utl,\' \',nom_utl) REGEXP \'^'.$sqlpattern.'\' '. $force_sql .
         ' UNION DISTINCT SELECT CONCAT(prenom_utl,\' \',nom_utl),\'1\' as method, utilisateurs.*' .
         ' FROM utilisateurs' .
         ' LEFT JOIN utl_etu USING ( id_utilisateur )' .
         ' WHERE CONCAT(nom_utl,\' \',prenom_utl) REGEXP \'^'.$sqlpattern.'\' '.$force_sql .
         ' UNION DISTINCT SELECT surnom_utbm, \'4\' as method, utilisateurs.*' .
         ' FROM utl_etu_utbm' .
         ' INNER JOIN utilisateurs USING (id_utilisateur)' .
         ' LEFT JOIN utl_etu USING ( id_utilisateur )' .
         ' WHERE surnom_utbm!=\'\' AND surnom_utbm REGEXP \'^'.$sqlpattern.'\''.
         ' AND CONCAT(prenom_utl,\' \',nom_utl) NOT REGEXP \'^'.$sqlpattern.'\' '.$force_sql .
         ' LIMIT 3');

        $this->buffer .= "<h2>Personnes</h2>";
        $this->buffer .= "<ul>";

        $this->nb += $req->lines;

        while ( $row = $req->get_row() ) {
          if ( $req->lines == 1 )
            $this->redirect = $wwwtopdir."user.php?id_utilisateur=".$row['id_utilisateur'];

          if ( $row["method"] > 2 )
            $nom = $row['prenom_utl']." ".$row['nom_utl']." : ".preg_replace($pattern,'<b>$0</b>',$row[0]);
          elseif ( $row["method"] == 1 )
            $nom = preg_replace($pattern,'<b>$0</b>',$row[0]);

          $this->buffer .= '<li><div class="imguser"><img src="';

          if (file_exists($topdir."data/matmatronch/".$row['id_utilisateur'].".identity.jpg"))
            $this->buffer .= $wwwtopdir."data/matmatronch/".$row['id_utilisateur'].".identity.jpg";
          elseif (file_exists($topdir."data/matmatronch/".$row['id_utilisateur'].".jpg"))
            $this->buffer .= $wwwtopdir."data/matmatronch/".$row['id_utilisateur'].".jpg";
          else
            $this->buffer .= $wwwtopdir."data/matmatronch/na.gif";

          $this->buffer .= "\" /></div><a href=\"".$wwwtopdir."user.php?id_utilisateur=".$row['id_utilisateur']."\"><img src=\"".$wwwtopdir."images/icons/16/user.png\" class=\"icon\" alt=\"\" /> $nom</a></li>";

        }
        $this->buffer .= "</ul>";
        if ( $nbutils > 3 )
          $this->buffer .= "<p class=\"more\"><a href=\"".$wwwtopdir."matmatronch/?action=simplesearch&amp;pattern=".urlencode($_REQUEST["pattern"])."\">".($nbutils-3)." autre(s) resultat(s)</a></p>";
      }
    }

    // Clubs et associations
    $req = new requete($site->db,"SELECT * FROM `asso` ".
      "LEFT JOIN asso_tag USING (id_asso) " .
      "LEFT JOIN tag USING (id_tag) " .
      "WHERE (nom_asso REGEXP '".$sqlpattern."' ".
      " OR nom_tag REGEXP '".$sqlpattern."') " .
      "AND `hidden`=0 LIMIT 3");

    if ( $req->lines ) {
      $this->nb += $req->lines;

      $this->buffer .= "<h2>Associations et clubs</h2>";
      $this->buffer .= "<ul>";
      while ( $row = $req->get_row() ) {
        if ( $req->lines == 1 )
          $this->redirect = $wwwtopdir."user.php?id_utilisateur=".$row['id_utilisateur'];

        $this->buffer .= "<li><a href=\"".$wwwtopdir."asso.php?id_asso=".$row["id_asso"]."\"><img src=\"".$wwwtopdir."images/icons/16/asso.png\" class=\"icon\" alt=\"\" /> ".preg_replace($pattern,'<b>$0</b>',$row["nom_asso"])."</a></li>";
      }

      $this->buffer .= "</ul>";
    }

    if ($exhaustive) {
        // Produits sur e-boutic
        $req = new requete($site->db,
                           "SELECT `cpt_produits`.*, `cpt_type_produit`.* " .
                           "FROM `cpt_mise_en_vente` ".
                           "INNER JOIN `cpt_produits` ".
                           "USING (`id_produit`) ".
                           "INNER JOIN `cpt_type_produit` ".
                           "USING (`id_typeprod`) ".
                           "WHERE `cpt_mise_en_vente`.`id_comptoir` = 3 ".
                           "AND `cpt_produits`.`prod_archive` = 0 " .
                           "AND datediff(curdate(), `date_fin_produit`) <= 0 ".
                           "AND (`nom_prod` REGEXP '".$sqlpattern."' OR " .
                           "`nom_typeprod` REGEXP '".$sqlpattern."') " .
                           "ORDER BY `nom_typeprod`, `nom_prod` " .
                           "LIMIT 3");

        if ( $req->lines ) {
            $this->nb += $req->lines;

            $this->buffer .= "<h2>e-boutic</h2>";
            $this->buffer .= "<ul>";
            while ( $row = $req->get_row() ) {
                if ( $req->lines == 1 )
                    $this->redirect = $wwwtopdir."e-boutic/?cat=".$row['id_typeprod'];

                $this->buffer .= "<li><a href=\"".$wwwtopdir."e-boutic/?cat=".$row['id_typeprod']."\">" .
                    "<img src=\"".$wwwtopdir."images/icons/16/produit.png\" class=\"icon\" alt=\"\" />" .
                    " ".preg_replace($pattern,'<b>$0</b>',$row["nom_typeprod"]).
                    " : ".preg_replace($pattern,'<b>$0</b>',$row["nom_prod"])."</a></li>";
            }

            $this->buffer .= "</ul>";
        }

        // Nouvelles
        $req = new requete($site->db,
                           "SELECT *, (SELECT date_debut_eve FROM nvl_dates WHERE nvl_dates.id_nouvelle=nvl_nouvelles.id_nouvelle ORDER BY date_debut_eve LIMIT 1) AS `date_debut_eve` " .
                           "FROM `nvl_nouvelles` " .
                           "LEFT JOIN nvl_nouvelles_tag USING (id_nouvelle) " .
                           "LEFT JOIN tag USING (id_tag) " .
                           "WHERE ( titre_nvl REGEXP '".$sqlpattern."' " .
                           " OR nom_tag REGEXP '".$sqlpattern."') " .
                           "AND modere_nvl ='1' " .
                           "ORDER BY date_nvl " .
                           "DESC LIMIT 3");

        if ( $req->lines ) {
            $this->nb += $req->lines;

            $this->buffer .= "<h2>Nouvelles</h2>";
            $this->buffer .= "<ul>";
            while ( $row = $req->get_row() ) {
                if ( $req->lines == 1 )
                    $this->redirect = $wwwtopdir."news.php?id_nouvelle=".$row['id_nouvelle'];

                $nom=$row["titre_nvl"];

                if ($row["date_debut_eve"] )
                    $nom .= " - le ".date("d/m/Y",strtotime($row["date_debut_eve"]));

                $this->buffer .= "<li><a href=\"".$wwwtopdir."news.php?id_nouvelle=".$row['id_nouvelle']."\"><img src=\"".$wwwtopdir."images/icons/16/nouvelle.png\" class=\"icon\" alt=\"\" /> ".preg_replace($pattern,'<b>$0</b>',$nom)."</a></li>";
            }

            $this->buffer .= "</ul>";
        }

        // UVs
        $req = new requete($site->db,"SELECT `id_uv`, `code`, `intitule` " .
                           "FROM `pedag_uv` " .
                           "WHERE `code` REGEXP '^".$sqlpattern."' " .
                           "ORDER BY `code` " .
                           "DESC LIMIT 3");

        if ( $req->lines ) {
            $this->nb += $req->lines;

            $this->buffer .= "<h2>UVs</h2>";
            $this->buffer .= "<ul>";
            while ( $row = $req->get_row() ) {
                if ( $req->lines == 1 )
                    $this->redirect = $wwwtopdir."uvs/uvs.php?id_uv=".$row['id_uv'];

                $this->buffer .= "<li><a href=\"".$wwwtopdir."pedagogie/uv.php?id=".$row['id_uv']."\">".preg_replace($pattern,'<b>$0</b>',$row['code']." : ".$row['intitule'])."</a></li>";
            }

            $this->buffer .= "</ul>";
        }


        // Objets de l'inventaire
        if ( $site->user->is_in_group("gestion_ae") ) {
            require_once($topdir. "include/entities/objet.inc.php");

            $objs=array();

            $obj = new objet($site->db);
            $obj->load_by_cbar($_REQUEST["pattern"]);

            if ( $obj->id > 0 )
                $objs[] = $obj;

            if ( ereg("^([A-Za-z]+)([0-9]+)$",$_REQUEST["pattern"],$regs)) {
                $objtype = new objtype($site->db);
                $objtype->load_by_code($regs[1]);
                if ( $objtype->id > 0 ) {
                    $obj->load_by_num($objtype->id,$regs[2]);
                    if ( $obj->id > 0 )
                        $objs[] = $obj;
                }
            }

            if ( count($objs) ) {
                $this->nb += count($objs);

                $this->buffer .= "<h2>Objets de l'inventaire</h2>";
                $this->buffer .= "<ul>";
                foreach( $objs as $obj)
                    $this->buffer .= "<li>".$obj->get_html_link()."</li>";
                $this->buffer .= "</ul>";
            }
        }
    }
  }
}
?>
