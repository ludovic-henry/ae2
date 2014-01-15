<?php

/** @file
 *
 * @brief Fonctions générales du site en version i-Mode (tm)
 *
 * Remarques :
 * - Pas de cookies donc pas de $_COOKIE ni de $_SESSION (TODO:émuler $_SESSION?)
 * - Les pages (HTML+images) doivent faire moins de 10ko
 */
/* Copyright 2007
 * - Julien Etelain <julien CHEZ pmad POINT net>
 *
 * Ce fichier fait partie du site de l'Association des 0tudiants de
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

if ( $_SERVER["REMOTE_ADDR"] == "192.168.2.75" )
  $GLOBALS["is_using_ssl"] = true;
else
  $GLOBALS["is_using_ssl"] = false;

require_once($topdir . "include/mysql.inc.php");
require_once($topdir . "include/mysqlae.inc.php");
require_once($topdir . "include/entities/std.inc.php");
require_once($topdir . "include/entities/utilisateur.inc.php");
require_once($topdir . "include/globals.inc.php");
require_once($topdir . "include/entities/cotisation.inc.php");

/**
 * @defgroup display_i I-Mode/Wap2
 * @ingroup display
 */

/** La classe principale du site
 * @ingroup display_i
 */
class isite
{
	var $db;
	var $dbrw;

  var $sid;
  var $title;
	var $user;
	var $contents;

	var $expirable;

  /** Constructeur de la classe */
  function isite ()
  {
    $this->expirable = false;

    $this->db = new mysqlae ();
    if (!$this->db)
      $this->db_error();

    $this->dbrw = new mysqlae ("rw");
    if (!$this->dbrw)
      $this->db_error();

		$this->user = new utilisateur( $this->db, $this->dbrw );

    if ( isset($_REQUEST['sid']) && !empty($_REQUEST['sid']) )
      $this->load_session($_REQUEST['sid']);

    $this->contents = array();
  }

  /** Erreur de connexion au serveur MySQL */
  function db_error ()
  {
    echo "<html>";
    echo "<head>";
    echo "<title>Erreur - AE UTBM</title>";
    echo "</head>\n";
    echo "<body>\n";
    echo "<h1>Erreur de connexion au serveur MySQL</h1>\n";
    echo "<p>Une erreur s'est produite lors de la connexion au serveur de base " .
      "de donn&eacutes;es MySQL. Veuillez r&eacute;essayer plus tard !</p>\n";
    echo "</body>";
    echo "</html>\n";
    die();
  }

  function load_session ( $sid )
  {
    $req = new requete($this->db, "SELECT `id_utilisateur`, `connecte_sess`, `expire_sess` FROM `site_sessions` WHERE `id_session` = '" .
                       mysql_escape_string($sid) . "'");
    list($uid,$connecte,$expire) = $req->get_row();

    if ( !is_null($expire) )
    {
      if ( strtotime($expire) < time() ) // Session expirée, fait le ménage
      {
        $req = new delete($site->dbrw, "site_sessions", array("id_session"=>$sid) );

        if ( isset($_COOKIE['AE2_SESS_ID']) )
        {
          setcookie ("AE2_SESS_ID", "", time() - 3600, "/", "ae.utbm.fr", 0);
          unset($_COOKIE['AE2_SESS_ID']);
        }

        return;
      }
      $this->expirable = true;
      $expire = date("Y-m-d H:i:s",time()+(15*60)); // Session expire dans 15 minutes
    }

    $req = new update($this->dbrw, "site_sessions",
          array(
            "derniere_visite"	=> date("Y-m-d H:i:s"),
            "expire_sess"=>$expire
            ),array("id_session" => $sid));

    $this->user->load_by_id($uid);

    if ($this->user->hash != "valid")
      $this->user->id = null;
    else
    {
      $this->user->visite();
      $this->sid = $sid;
    }
  }

  function add_contents(&$cts)
  {
    $this->contents[]=$cts;
  }


	function connect_user ($forever=false)
	{
    $this->expirable = !$forever;

    if ( $forever )
      $expire = null;
    else
      $expire = date("Y-m-d H:i:s",time()+(15*60)); // Session expire dans 15 minutes

		$this->sid = md5(rand(0,32000) . $_SERVER['REMOTE_ADDR'] . rand(0,32000));

		$req = new insert($this->dbrw, "site_sessions",
						array(
							"id_session"			=> $this->sid,
							"id_utilisateur"		=> $this->user->id,
							"date_debut_sess"	=> date("Y-m-d H:i:s"),
							"derniere_visite"	=> date("Y-m-d H:i:s"),
							"expire_sess" => $expire
							));

    $this->user->visite();

		return $sid;
	}

  function start_page ( $title )
  {
    $this->title = $title;
	}



  function end_page ()
  {
    global $topdir;
    echo "<html>";
    echo "<head>";
    echo "<title>".htmlentities($this->title,ENT_COMPAT,"UTF-8")." - AE UTBM</title>";
    echo "</head>\n";
    echo "<body bgcolor=\"#DEEBF5\">";

		foreach ( $this->contents as $cts )
			echo $cts->ihtml_render();

		echo "<hr />\n";
		echo "<a href=\"".$topdir."i/?sid=".$this->sid."\" accesskey=\"0\">&#59115; Accueil</a>\n";

    echo "</body>";
    echo "</html>\n";

  }
}

/**
 * @ingroup display_i
 */
class istdcontents
{
  var $buffer;

  function istdcontents ( $prefill="")
  {
    $this->buffer = $prefill;
  }

  function ihtml_render()
  {
    return $this->buffer;

  }

}

/**
 * @ingroup display_i
 */
class icontents extends istdcontents
{

  function add_title( $level=1, $text, $align=null)
  {
    if ( $level == 2 )
    {

      $this->buffer .="<table width=\"100%\" cellspacing=\"0\" cellpadding=\"0\"><tr><td bgcolor=\"#FFD74D\"";
      if ( !is_null($align) )
        $this->buffer .= " align=\"$align\"";
      $this->buffer .="><font color=\"#000000\">";
      $this->buffer .= $text;
      $this->buffer .="</font></td>";      $this->buffer .="</tr>";      $this->buffer .="</table>";

      return;
    }

    $this->buffer .="<h$level";
    if ( !is_null($align) )
      $this->buffer .= " align=\"$align\"";
    $this->buffer .= "><font size=\"3\">";
    $this->buffer .= $text;
    $this->buffer .= "</font></h$level>\n";
  }

  function add_paragraph( $text, $align=null)
  {
    $this->buffer .="<p";
    if ( !is_null($align) )
      $this->buffer .= " align=\"$align\"";
    $this->buffer .= ">";
    $this->buffer .= $text;
    $this->buffer .= "</p>\n";
  }

  function puts ( $d )
  {
    $this->buffer .= $d;
  }

  function add_hr( )
  {
    $this->buffer .= "<hr />\n";
  }

  function add ( &$cts )
  {
    $this->buffer .= $cts->ihtml_render();
  }
}

/**
 * @ingroup display_i
 */
class iform extends istdcontents
{

  var $action;
  var $hiddens;

  function iform ($action)
  {
    global $site;

    if ( strpos($action,"?") )
      $this->action = $action."&sid=".$site->sid;
    else
      $this->action = $action."?sid=".$site->sid;

    $this->hiddens = array();
  }

	function add_hidden ( $name, $value = "" )
	{
		$this->hiddens[$name] = $value;
	}

  function add_text_field ( $name, $title, $value = "", $required=false )
  {
		$this->_render_name($name,$title,$required);
		$this->buffer .= "<input type=\"text\" name=\"$name\" /><br />\n";
  }

  function add_password_field ( $name, $title, $value = "", $required=false )
  {
		$this->_render_name($name,$title,$required);
		$this->buffer .= "<input type=\"password\" name=\"$name\" /><br />\n";
  }

  function add_info ( $info )
  {
		$this->buffer .= "$info<br />\n";
  }

  function add_text_area ( $name, $title, $value="", $width=14, $height=4, $required = false )
  {
		$this->_render_name($name,$title,$required);
		$this->buffer .= "<textarea name=\"$name\" rows=\"$height\" cols=\"$width\">";
		$this->buffer .= htmlentities($value,ENT_NOQUOTES,"UTF-8")."</textarea>\n";
  }

	function add_select_field ( $name, $title, $values, $value = null)
	{
		$this->_render_name($name,$title,$required);

		$this->buffer .= "<select name=\"$name\">\n";
		foreach ( $values as $key => $item )
		{
			$this->buffer .= "<option value=\"$key\"";
			if ( $value == $key )
				$this->buffer .= " selected=\"selected\"";
			$this->buffer .= ">".htmlentities($item,ENT_NOQUOTES,"UTF-8")."</option>\n";
		}

		$this->buffer .= "</select><br/>\n";
  }

	function add_submit ( $name, $title )
	{
		$this->buffer .= "<input type=\"submit\" name=\"$name\" value=\"$title\" /><br />\n";
	}

	function _render_name ( $name, $title, $required )
	{
		if ( !$title )
			return;

		$this->buffer .= $title;

		if ( $required )
			$this->buffer .= " *";

		$this->buffer .= "<br/>";

	}

  function ihtml_render ()
  {
    $html = "<form action=\"".htmlentities($this->action,ENT_COMPAT,"UTF-8")."\" method=\"post\">\n";

    foreach ( $this->hiddens as $key => $value )
			$html .= "<input type=\"hidden\" name=\"$key\" value=\"".htmlentities($value,ENT_COMPAT,"UTF-8")."\" />\n";

    return $html.$this->buffer."</form>\n";
  }

}

$site = new isite();

?>
