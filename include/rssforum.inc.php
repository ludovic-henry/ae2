<?
/*
 * flux rss du forum
 *
 * Copyright 2007
 * - Pierre Mauduit <pierre POINT mauduit CHEZ utbm POINT fr>
 *
 * Ce fichier fait partie du site de l'Association des Étudiants de
 * l'UTBM, http://ae.utbm.fr/
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

require_once($topdir."include/rss.inc.php");
require_once($topdir . "include/lib/dokusyntax.inc.php");
require_once($topdir . "include/lib/bbcode.inc.php");
require_once($topdir . "include/entities/forum.inc.php");
require_once($topdir . "include/entities/utilisateur.inc.php");

class rssfeedforum extends rssfeed
{
  var $nb;
  var $db;

  function rssfeedforum(&$db, $nbmessage = 50, &$user=null)
  {
    $this->db = $db;
    if(is_null($user))
      $user=new utilisateur($this->db);
    $this->user = &$user;
    if (intval($nbmessage) < 0)
      $nbmessage = 50;
    $this->nb = $nbmessage;

    $this->title = "Les " . $nbmessage . " derniers messages du forum de l'AE";
    $this->description = $this->title;
    $this->pubUrl = "http://ae.utbm.fr/forum2/";
    $this->link = $this->pubUrl;

    $this->rssfeed();
  }

  function output_items()
  {
    $forum = new forum($this->db);
    $query='';
    if ( !$forum->is_admin( $this->user ) )
    {
      $grps = $this->user->get_groups_csv();
      $query = "WHERE ((droits_acces_forum & 0x1) OR " .
               "((droits_acces_forum & 0x10) AND id_groupe IN ($grps)) OR " .
               "(id_groupe_admin IN ($grps)) OR " .
               "((droits_acces_forum & 0x100) AND frm_forum.id_utilisateur='".$this->user->id."')) ";
    }
    $req = new requete ($this->db, "SELECT
                                             COALESCE(`surnom_utbm`,CONCAT(`prenom_utl`,' ',`nom_utl`)) AS `nom_utilisateur`
                                             , `frm_message`.`id_message`
                                             , `frm_message`.`id_sujet`
                                             , `frm_message`.`contenu_message`
                                             , `frm_message`.`date_message`
                                             , `frm_message`.`syntaxengine_message`
                                             , `frm_sujet`.`titre_sujet`
                                             , `frm_forum`.`titre_forum`
                                             , `frm_forum`.`id_forum`
                                    FROM `frm_message`
                                    INNER JOIN `utilisateurs` ON `utilisateurs`.`id_utilisateur` = `frm_message`.`id_utilisateur`
                                    LEFT JOIN `utl_etu_utbm` ON `utilisateurs`.`id_utilisateur` = `utl_etu_utbm`.`id_utilisateur`
                                    INNER JOIN `frm_sujet` ON `frm_sujet`.`id_sujet` = `frm_message`.`id_sujet`
                                    INNER JOIN `frm_forum` ON `frm_sujet`.`id_forum` = `frm_forum`.`id_forum`
                                    $query
                                    ORDER BY `frm_message`.`id_message` DESC
                                    LIMIT ".$this->nb);

    while ($row = $req->get_row())
    {
      $forum->load_by_id($row['id_forum']);
      if(!$forum->is_right($this->user,DROIT_LECTURE))
        continue;

      echo "<item>\n";
      echo "\t<title><![CDATA[". $row["titre_sujet"] . ", par ".$row['nom_utilisateur']."]]></title>\n";
      echo "\t<link>".$this->pubUrl."?id_message=".$row["id_message"]."#msg".$row['id_message']."</link>\n";

      if ($row['syntaxengine_message'] == 'doku')
        $content = doku2xhtml($row['contenu_message']);

      elseif ($row['syntaxengine_message'] == 'bbcode')
        $content = bbcode($row['contenu_message']);

      echo "\t<description><![CDATA[".$content."]]></description>\n";
      echo "\t<pubDate>".gmdate("D, j M Y G:i:s T",strtotime($row["date_message"]))."</pubDate>\n";
      echo "\t<guid>".$this->pubUrl."?id_sujet=".$row["id_sujet"]."#msg".$row['id_message']."</guid>\n";
      echo "</item>\n";

    }

  }

  function output ()
  {
    global $topdir;
    header("Content-Type: text/xml; charset=utf-8");
    echo "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n";
    echo "<?xml-stylesheet type=\"text/css\" href=\"http://ae.utbm.fr/themes/default/css/site.css?".filemtime($topdir."themes/default/css/site.css")."\" ?>";
    echo "<rss version=\"2.0\" xmlns:rdf=\"http://www.w3.org/1999/02/22-rdf-syntax-ns#\">\n";
    echo "<channel>\n";

    if ( !empty($this->title) )
      echo "<title>".htmlspecialchars($this->title,ENT_NOQUOTES,"UTF-8")."</title>\n";

    if ( !empty($this->link) )
      echo "<link>".htmlspecialchars($this->link,ENT_NOQUOTES,"UTF-8")."</link>\n";

    if ( !empty($this->description) )
      echo "<description>".htmlspecialchars($this->description,ENT_NOQUOTES,"UTF-8")."</description>\n";

    if ( !empty($this->generator) )
      echo "<generator>".htmlspecialchars($this->generator,ENT_NOQUOTES,"UTF-8")."</generator>\n";

    echo "<pubDate>".gmdate("D, j M Y G:i:s T",$this->pubDate)."</pubDate>\n";

    $this->output_items();

    echo "</channel>\n";
    echo "</rss>\n";
  }
}


?>
