<?php
/* Copyright 2006-2007
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

$topdir = "./";
require_once($topdir. "include/site.inc.php");

$site = new site();

if ( isset($_REQUEST['topdir']) && ($_REQUEST['topdir']=="./" || $_REQUEST['topdir'] =="../" || $_REQUEST['topdir'] =="./../") )
  $wwwtopdir = $_REQUEST['topdir'];

if ( $_REQUEST['module']=="fsearch" )
{
  header("Content-Type: text/html; charset=UTF-8");

  if ($_REQUEST["pattern"] == "")
    exit();

  require_once($topdir. "include/cts/fsearchcache.inc.php");
  $cache = new fsearchcache ();
  $content = null;
  if ($cache->can_get_cached_contents ())
      $content = $cache->get_cached_contents ($site->user, $_REQUEST["pattern"]);

  if ($content == null) {
      require_once($topdir. "include/cts/fsearch.inc.php");
      $fsearch = new fsearch ( $site, false );
      $content = $fsearch->buffer;
      if (!empty ($content) && strlen ($_REQUEST["pattern"]) > 4)
          $cache->set_temporarily_cached_contents($_REQUEST["pattern"], $content);
  }

  echo $content;
  exit ();
}
elseif ( $_REQUEST['module']=="explorer" )
{
  header("Content-Type: text/html; charset=utf-8");

  require_once($topdir."include/entities/files.inc.php");
  require_once($topdir."include/entities/folder.inc.php");

  $folder = new dfolder($site->db);

  if ( !isset($_REQUEST["id_folder"]) || !$_REQUEST["id_folder"] )
    $folder->id = null;
  else
    $folder->load_by_id($_REQUEST["id_folder"]);

  $field = $_REQUEST["field"];

  if ( is_null($folder->id) )
    $sub1 = new requete($this->db,"SELECT `d_folder`.`id_folder`, ".
    "IF(`asso`.`id_asso` IS NULL,`d_folder`.`titre_folder`, `asso`.`nom_asso`) AS `titre_folder` ".
    "FROM `d_folder` ".
    "LEFT JOIN `asso` ON `asso`.`id_asso` = `d_folder`.`id_asso` ".
    "WHERE `d_folder`.`id_folder_parent` IS NULL ".
    "ORDER BY `asso`.`nom_asso`");
  else
    $sub1 = $folder->get_folders ( $site->user );

  $fd = new dfolder(null);
  while ( $row = $sub1->get_row() )
  {
    $fd->_load($row);
    echo "<li><a href=\"#\" onclick=\"zd_seldir('$field','".$fd->id."','$wwwtopdir'); return false;\"><img src=\"".$wwwtopdir."images/icons/16/folder.png\" alt=\"dossier\" /> ".htmlentities($fd->titre,ENT_COMPAT,"UTF-8")."</a><ul id=\"".$field."_".$fd->id."_cts\" style=\"display:none;\"></ul></li>";
  }

  if ( !is_null($folder->id) )
  {
    $sub2 = $folder->get_files ( $site->user);
    $fd = new dfile(null);
    while ( $row = $sub2->get_row() )
    {
      $fd->_load($row);
      $img = $wwwtopdir."images/icons/16/".$fd->get_icon_name();
      echo "<li><a href=\"#\" onclick=\"zd_selfile('$field','".$fd->id."','$wwwtopdir'); return false;\"><img src=\"$img\" alt=\"fichier\" /> ".htmlentities($fd->titre,ENT_COMPAT,"UTF-8")."</a></li>";
    }
  }
}
elseif ( $_REQUEST['module']=="usersession" )
{
  /**** NOTE IMPORTANTE ****
   * En raison de ce module, les valeurs de $_SESSION["usersession"] ne peuvent être
   * considéré comme "sûres"
   */

  if ( isset($_REQUEST["set"]) )
  {
    $_SESSION["usersession"][$_REQUEST["set"]]   = $_REQUEST["value"];


    if ( $site->user->is_valid() ) // mémorise le usersession
      $site->user->set_param("usersession",$_SESSION["usersession"]);


    //echo "alert('".$_REQUEST["set"]."=".$_REQUEST["set"]."');";
  }

  exit();
}
elseif ( $_REQUEST['module']=="userfield" )
{
  header("Content-Type: text/javascript; charset=UTF-8");
  $buffer="";

  if ( !$site->user->is_valid() && !count($_SESSION["Comptoirs"])) exit();

  $pattern = mysql_real_escape_string($_REQUEST["pattern"]);

  $pattern = ereg_replace("(e|é|è|ê|ë|É|È|Ê|Ë)","(e|é|è|ê|ë|É|È|Ê|Ë)",$pattern);
  $pattern = ereg_replace("(a|à|â|ä|À|Â|Ä)","(a|à|â|ä|À|Â|Ä)",$pattern);
  $pattern = ereg_replace("(i|ï|î|Ï|Î)","(i|ï|î|Ï|Î)",$pattern);
  $pattern = ereg_replace("(c|ç|Ç)","(c|ç|Ç)",$pattern);
  $pattern = ereg_replace("(o|O|ò|Ò|ô|Ô)","(o|O|ò|Ò|ô|Ô)",$pattern);
  $pattern = ereg_replace("(u|ù|ü|û|Ü|Û|Ù)","(u|ù|ü|û|Ü|Û|Ù)",$pattern);
  $pattern = ereg_replace("(n|ñ|Ñ)","(n|ñ|Ñ)",$pattern);

  $req = new requete($site->db,
    "SELECT `id_utilisateur`,CONCAT(`prenom_utl`,' ',`nom_utl`) " .
    "FROM `utilisateurs` " .
    "WHERE CONCAT(`prenom_utl`,' ',`nom_utl`) REGEXP '^".$pattern."' " .
    "UNION SELECT `id_utilisateur`,CONCAT(`nom_utl`,' ',`prenom_utl`) " .
    "FROM `utilisateurs` " .
    "WHERE CONCAT(`nom_utl`,' ',`prenom_utl`) REGEXP '^".$pattern."' " .
    "UNION SELECT `utilisateurs`.`id_utilisateur`,CONCAT(`surnom_utbm`,' (',`prenom_utl`,' ',`nom_utl`,')') " .
    "FROM `utl_etu_utbm` " .
    "INNER JOIN `utilisateurs` ON `utl_etu_utbm`.`id_utilisateur` = `utilisateurs`.`id_utilisateur` " .
    "WHERE `surnom_utbm`!='' AND `surnom_utbm` REGEXP '^".$pattern."' " .
    "ORDER BY 2 LIMIT 10");

  if ( !$req || $req->errno != 0) // Si l'expression régulière envoyée par l'utilisateur est invalide, on évite l'erreur mysql
  {
    $buffer .=  "<ul>";
    $buffer .=  "<li>Recherche invalide.</li>";
    $buffer .=  "</ul>";
    $buffer .=  "<div class=\"clearboth\"></div>";
    exit();
  }

  $buffer .=  "<ul>";

  while ( list($id,$email) = $req->get_row() )
  {
    $buffer .=  "<li><div class=\"imguser\"><img src=\"";

    if (file_exists($topdir."data/matmatronch/".$id.".identity.jpg"))
      $buffer .=  $wwwtopdir."data/matmatronch/".$id.".identity.jpg";
    elseif (file_exists($topdir."data/matmatronch/".$id.".jpg"))
      $buffer .=  $wwwtopdir."data/matmatronch/".$id.".jpg";
    else
      $buffer .=  $wwwtopdir."data/matmatronch/na.gif";

    $buffer .=  "\" /></div><a href=\"#\" onclick=\"userselect_set_user('$wwwtopdir','".$_REQUEST["ref"]."',$id,'".addslashes(htmlspecialchars($email))."'); return false;\">".htmlspecialchars($email)."</a></li>";
  }
  $buffer .=  "</ul>";
  $buffer .=  "<div class=\"clearboth\"></div>";

  // si la requete a été trop longue on ne l'affiche pas !
  echo "if ( ".$_REQUEST['userselect_sequence']." > userselect_actual_sequence ) {\n";
  echo "  userselect_actual_sequence=".$_REQUEST['userselect_sequence'].";\n";
  echo "  var content = document.getElementById('".$_REQUEST['ref']."_result');\n";
  echo "  content.innerHTML ='".addslashes($buffer)."';\n";
  echo "}\n";
}
elseif ( $_REQUEST['module']=="userinfo" )
{
  if ( !$site->user->is_valid() && !count($_SESSION["Comptoirs"])) exit();

  $user = new utilisateur($site->db,$site->dbrw);
  $user->load_by_id($_REQUEST["id_utilisateur"]);
  if ( $user->id < 0 )
    $user = &$site->user;

  if (file_exists($topdir."data/matmatronch/".$user->id.".identity.jpg"))
    echo "<img src=\"".$wwwtopdir."data/matmatronch/".$user->id.".jpg\" alt=\"\" />\n";
  else
    echo "<img src=\"".$wwwtopdir."data/matmatronch/na.gif"."\" alt=\"\" />\n";

  echo "<p class=\"nomprenom\">". $user->prenom . " " . $user->nom . "</p>";
  if ( $user->surnom )
    echo "<p class=\"surnom\">'' ". $user->surnom . " ''</p>";
  echo "<div class=\"clearboth\"></div>";
  exit();
}
elseif ( $_REQUEST['module']=="entinfo" )
{
  $class = $_REQUEST['class'];

  if ( class_exists($class) )
    $std = new $class($site->db);

  elseif ( isset($GLOBALS["entitiescatalog"][$class][5]) && $GLOBALS["entitiescatalog"][$class][5] )
  {
    include($topdir."include/entities/".$GLOBALS["entitiescatalog"][$class][5]);
    if ( class_exists($class) )
      $std = new $class($site->db);
  }

  if ($class=="utilisateur")
    $std->load_all_by_id($_REQUEST['id']);
  else
    $std->load_by_id($_REQUEST['id']);

  if ( !$std->is_valid() )
  {
    echo "?";
    exit();
  }

  if ( !$std->allow_user_consult($site->user) )
    exit();

  if ( $std->can_preview() )
    echo "<p class=\"stdpreview\"><img src=\"".$wwwtopdir.$std->get_preview()."\" alt=\"".htmlentities($std->get_display_name(),ENT_COMPAT,"UTF-8")."\" /></p>";

  echo "<p class=\"stdinfo\">".$std->get_html_extended_info()."</p>";
  echo "<div class=\"clearboth\"></div>";
  exit();

}
elseif ( $_REQUEST['module']=="entdesc" )
{
  $class = $_REQUEST['class'];

  if ( class_exists($class) )
    $std = new $class($site->db);

  elseif ( isset($GLOBALS["entitiescatalog"][$class][5]) && $GLOBALS["entitiescatalog"][$class][5] )
  {
    include($topdir."include/entities/".$GLOBALS["entitiescatalog"][$class][5]);
    if ( class_exists($class) )
      $std = new $class($site->db);
  }

  $std->load_by_id($_REQUEST['id']);

  if ( !$std->is_valid() )
  {
    echo "?";
    exit();
  }

  if ( !$std->allow_user_consult($site->user) )
    exit();

  echo htmlentities($std->get_description(),ENT_NOQUOTES,"UTF-8");

  exit();
}
elseif ( $_REQUEST['module']=="fsfield" )
{
  $class = $_REQUEST['class'];
  $field = $_REQUEST['field'];


  if ( !ereg("^([a-z0-9]*)$",$class) )
    exit();

  $std = null;

  if ( class_exists($class) )
    $std = new $class($site->db);

  elseif ( isset($GLOBALS["entitiescatalog"][$class][5]) && $GLOBALS["entitiescatalog"][$class][5] )
  {
    include($topdir."include/entities/".$GLOBALS["entitiescatalog"][$class][5]);
    if ( class_exists($class) )
      $std = new $class($site->db);
  }

  if ( is_null($std) )
    exit();

  if ( !$std->can_fsearch() )
    exit();

  if ( !$std->allow_user_consult($site->user) )
    exit();

  if ( $_REQUEST['pattern'] != "" )
  {
    $conds=array();
    if(isset($_REQUEST['conds']) && !empty($_REQUEST['conds']) && is_array($_REQUEST['conds']))
      $conds=$_REQUEST['conds'];
    $res = $std->fsearch ( $_REQUEST['pattern'], 6 , $conds);
    if ( !is_null($res) )
    {
      $buffer = "<ul class=\"fsfield_list\">";
      foreach ( $res as $id => $name )
      {
        $buffer .= "<li>";

        $std->id = $id;
        if ( $std->can_preview() )
        {
          $img = $std->get_preview();
          if ( !is_null($img) )
            $buffer .= "<div class=\"imguser\"><img src=\"".$wwwtopdir.$img."\" /></div>";
        }

        $buffer .= "<a href=\"#\" onclick=\"fsfield_sel('$wwwtopdir','$field',$id,'".addslashes(htmlspecialchars($name))."','".$GLOBALS["entitiescatalog"][$class][2]."'); return false;\">";
        $buffer .= htmlspecialchars($name);
        $buffer .= "</a>";
        $buffer .= "</li>";
      }
      $buffer .=  "</ul>";
      $buffer .=  "<div class=\"clearboth\"></div>";
    }
    else
      $buffer="<p class=\"error\">Requête invalide</p>";
  }
  else
    $buffer="";

  echo "if ( ".$_REQUEST['sequence']." > fsfield_current_sequence['".$field."'] )\n{\n";
  echo "  fsfield_current_sequence['".$field."']=".$_REQUEST['sequence'].";\n";
  echo "  var content = document.getElementById('".$field."_result');\n";
  echo "  content.style.zIndex = 100000;\n";
  echo "  content.style.display = 'block';\n";
  echo "  content.innerHTML ='".addslashes($buffer)."';\n";
  echo "}\n";

  exit();
}
elseif ( $_REQUEST['module']=="exfield" )
{
  $class = $_REQUEST['class'];
  $field = $_REQUEST['field'];
  $eclass = $_REQUEST['eclass'];

  if ( !ereg("^([a-z0-9]*)$",$class) || !ereg("^([a-z0-9]*)$",$class) )
    exit();

  $std = null;

  if ( class_exists($eclass) )
    $std = new $eclass($site->db);

  elseif ( isset($GLOBALS["entitiescatalog"][$eclass][5]) && $GLOBALS["entitiescatalog"][$eclass][5] )
  {
    include($topdir."include/entities/".$GLOBALS["entitiescatalog"][$eclass][5]);
    if ( class_exists($eclass) )
      $std = new $eclass($site->db);
  }

  if ( is_null($std) )
    exit();

  if ( $_REQUEST['eid'] == "root" )
  {
    $std = $std->get_root_element();
    if ( is_null($std) )
      exit();
  }
  else
    $std->load_by_id($_REQUEST['eid']);

  if ( !$std->is_valid() )
    exit();

  if ( !$std->allow_user_consult($site->user) )
    exit();

  $childs = $std->get_childs($site->user);

  if ( is_null($childs) || count($childs) == 0 )
    exit();

  foreach ( $childs as $child )
  {
    $name = $child->get_display_name();

    echo "<li>";

    echo "<a href=\"#\" onclick=\"";
    if ( get_class($child) == $class )
      echo "exfield_select('$wwwtopdir','$field','$class','".$child->id."','".addslashes(htmlspecialchars($name))."','".$GLOBALS["entitiescatalog"][$class][2]."');";
    else
      echo "exfield_explore('$wwwtopdir','$field','$class','".get_class($child)."','".$child->id."');";
    echo "return false;\">";

    echo "<img src=\"".$wwwtopdir."images/icons/16/".$GLOBALS["entitiescatalog"][get_class($child)][2]."\" alt=\"\" />";
    echo htmlspecialchars($name);
    echo "</a>";

    echo "<ul id=\"".$field."_".get_class($child)."_".$child->id."\"></ul>";

    echo "</li>";
  }


  exit();
}
elseif( $_REQUEST['module']=="tinycal" )
{
  $cal = new tinycalendar($site->db);
  $cal->set_target($_REQUEST['target']);
  $cal->set_type($_REQUEST['type']);
  $cal->set_ext_topdir($_REQUEST['topdir']);
  echo $cal->html_render();
  exit();
}
elseif ($_REQUEST['module'] == 'eticket-ident' && isset ($_REQUEST['id_utilisateur']) && isset($_REQUEST['secret'])) {
    /* Utilisé par le logiciel de validation des etickets pour récupérer
       des infos utilisateurs si il a un lien internet */
    require_once($topdir. "include/mysql.inc.php");

    $req = new requete ($site->db, 'SELECT id_ticket FROM cpt_etickets WHERE secret=\''.mysql_real_escape_string($_REQUEST['secret']).'\'');
    if ($req->lines > 0) {
        $req = new requete ($site->db, 'SELECT utl.prenom_utl, utl.nom_utl, utl_utbm.surnom_utbm FROM utilisateurs AS utl LEFT JOIN utl_etu_utbm AS utl_utbm ON utl.id_utilisateur = utl_utbm.id_utilisateur WHERE utl.id_utilisateur='.intval(mysql_real_escape_string($_REQUEST['id_utilisateur'])));
        $line = $req->get_row ();
        if ($line != null) {
            echo $line['prenom_utl'] . '|^' . $line['nom_utl'] . '|^' . $line['surnom_utbm'];
        }
    } else {
        echo '0';
    }
    exit ();
}
elseif($_REQUEST['module'] == 'appli-mobile')
{
	require_once($topdir. "include/mysql.inc.php");
	if($_REQUEST['req'] == 'login')
	{
		switch ($_REQUEST["domain"])
		{
		  case "utbm" :
		    $site->user->load_by_email($_REQUEST["username"]."@utbm.fr");
		  break;
		  case "assidu" :
		    $site->user->load_by_email($_REQUEST["username"]."@assidu-utbm.fr");
		  break;
		  case "id" :
		    $site->user->load_by_id($_REQUEST["username"]);
		  break;
		  case "autre" :
		    $site->user->load_by_email($_REQUEST["username"]);
		  break;
		  case "alias" :
		    $site->user->load_by_alias($_REQUEST["username"]);
		  break;
		  case "carteae":
		    $site->user->load_by_carteae($_REQUEST["username"], true, false);
		  break;
		  default :
		    $site->user->load_by_email($_REQUEST["username"]."@utbm.fr");
		  break;
		}

		if ( !$site->user->is_valid() )
		{
		  echo "echec";
		  exit();
		}

		if ( $site->user->hash != "valid" )
		{
		  echo "utilisateur non valide";
		  exit();
		}

		if ( $site->user->is_password($_REQUEST["password"]) )
		{
		  

		  $req = new requete($site->db, "SELECT serviceident FROM `utilisateurs` WHERE id_utilisateur = ".$site->user->id."");
		  if($req->lines != 1)
		  {
			  echo "erreur";
			  exit();
		  }
		  list( $servident ) = $req->get_row();
		  if(is_null($servident) || empty($servident))
		  {
			  $site->user->gen_serviceident();
			  $req = new requete($site->db, "SELECT serviceident FROM `utilisateurs` WHERE id_utilisateur = ".$site->user->id."");
			  if($req->lines != 1)
			  {
				  echo "erreur";
				  exit();
			  }
			  list( $servident ) = $req->get_row();
			  
		  }

		  echo $site->user->id."\n";
		  echo $servident."\n";
		  exit();
		}
		echo "erreur";
		exit();
	}
	if(!isset($_REQUEST['serviceident']) || !isset($_REQUEST['id']))
	{
		echo "identifiant non valide";
		exit();
	}
	$site->user->load_by_service_ident($_REQUEST['id'],$_REQUEST['serviceident']);
	if ( !$site->user->is_valid() )
	{
		echo "identifiant non valide";
		exit();
	}

	if($_REQUEST['req'] == 'montant')
	{
		if(!$site->user->ae)
		{
			echo "utilisateur non ae";
			exit();
		}
		echo $site->user->montant_compte;
		exit();

	}
	elseif($_REQUEST['req'] == 'comptoir')
	{
	    $req = new requete ($site->dbrw,
		   "UPDATE `cpt_tracking` SET `closed_time`='".date("Y-m-d H:i:s")."'
		    WHERE `activity_time` <= '".date("Y-m-d H:i:s",time()-intval(ini_get("session.gc_maxlifetime")))."'
		    AND `closed_time` IS NULL");


	    // 2- On récupère les infos sur les bars ouverts
	    $req = new requete ($site->dbrw,
		   "SELECT MAX(activity_time),id_comptoir
		    FROM `cpt_tracking`
		    WHERE `activity_time` > '".date("Y-m-d H:i:s",time()-intval(ini_get("session.gc_maxlifetime")))."'
		    AND `closed_time` IS NULL
		    GROUP BY id_comptoir");

	    while ( list($act,$id) = $req->get_row() )
	      $activity[$id]=strtotime($act);

	    // 3- On récupère les infos sur tous les bars
	    $req = new requete ($site->dbrw,
		   "SELECT id_comptoir, nom_cpt
		    FROM cpt_comptoir
		    WHERE type_cpt='0'
		    AND id_comptoir != '4'
		    AND id_comptoir != '8'
		    AND id_comptoir != '13'
		    ORDER BY nom_cpt");
	    $list='';
	    $i=0;
	    while ( list($id,$nom) = $req->get_row() )
	    {
	      $i++;
	      $led = 2;
	      $descled = "ouvert";

	      if ( !isset($activity[$id]) )
	      {
		$led = 0;
	      }
	      elseif ( time()-$activity[$id] > 600 )
	      {
		$led = 1;
	      }
	      echo "$nom:$led\n";
	    }
	    exit();

	}
	elseif($_REQUEST['req'] == 'com')
	{
		
	    $req = new requete ($site->db, "SELECT * FROM message_com WHERE id_utilisateur = ".$site->user->id." AND date > '".date("Y-m-d H:i:s",time()-30)."' ");
	    if($req->lines != 0 && !$site->user->is_in_group("root"))
	    {
		    echo "Pas de spam";
		    exit();
	    }
	    $req = new requete ($site->db, "SELECT COUNT(*) FROM message_com WHERE id_utilisateur = ".$site->user->id." AND date > '".date("Y-m-d H:i:s",time()-3600)."' ");
	    list( $nb_message ) = $req->get_row();
	    if($nb_message > 30 && !$site->user->is_in_group("root"))
	    {
		    echo "Quota excédé";
		    exit();
	    }
	    if($site->user->is_in_group("root"))
              $req = new requete ($site->dbrw,
		    "INSERT INTO message_com (id_utilisateur, message) VALUES (".$site->user->id.", '".mysql_real_escape_string($_REQUEST['mess'])."')");
	    else
	      $req = new requete ($site->dbrw,
		    "INSERT INTO message_com (id_utilisateur, message) VALUES (".$site->user->id.", '".mysql_real_escape_string(htmlentities($_REQUEST['mess'],ENT_QUOTES,"UTF-8"))."')");
	    echo "Ok";
	    exit();

	}
	exit();
}
elseif($_REQUEST['module'] == 'ecrancom' &&  $_REQUEST['secret'] == "messageForTheLulz"  )
{

	require_once($topdir. "include/mysql.inc.php");
	$req = new requete ($site->dbrw,
		   "SELECT MAX(activity_time)
		    FROM `cpt_tracking`
		    WHERE id_comptoir = 2");
	list( $activity ) = $req->get_row();
	$activity = time()-strtotime($activity);

	$son = "";
	if(rand(0,2) < 1)
		$son = "sncf.ogg";
	else
		$son = "msn.ogg";

	/*if($activity > 600 && $activity < 607)
	{
		echo "Le lion\nlion.mp3\nEH OH!\nON PICOLE!";
		exit();
	}
	if($activity > intval(ini_get("session.gc_maxlifetime")) && $activity < (intval(ini_get("session.gc_maxlifetime"))+7))
	{
		echo "Le lion\nlion.mp3\nNOOOON!\n Le foyer est fermé :'-(";
		exit();
	}*/
	

	$req = new requete($site->db, "SELECT id_message,".
		"IF(utl_etu_utbm.surnom_utbm!='' AND utl_etu_utbm.surnom_utbm IS NOT NULL,utl_etu_utbm.surnom_utbm, CONCAT(`utilisateurs`.`prenom_utl`,' ',`utilisateurs`.`nom_utl`)) as `nom_utilisateur`, ".
		"message FROM `message_com` 
		JOIN utilisateurs ON utilisateurs.id_utilisateur = message_com.id_utilisateur
		LEFT JOIN utl_etu_utbm ON utilisateurs.id_utilisateur = utl_etu_utbm.id_utilisateur
		WHERE vu = 0 ORDER BY id_message LIMIT 1");
	if($req->lines != 1)
	{
		exit();
	}
	list( $id_message, $nom_utilisateur, $message ) = $req->get_row();

	$req = new requete($site->dbrw, "UPDATE `message_com` SET vu = 1 WHERE id_message = $id_message");

	echo "$nom_utilisateur\n$son\n$message";
	exit();
}

if ( $_REQUEST['class'] == "calendar" )
{
  if(isset($_REQUEST['subclass']) && !empty($_REQUEST['subclass']))
    $subclass=$_REQUEST['subclass'];
  else
    $subclass='';
  if(isset($_REQUEST['id_box']) && !empty($_REQUEST['id_box']))
    $cts = new calendar($site->db,null,$subclass,$_REQUEST['id_box']);
  else
    $cts = new calendar($site->db);
}
else
  $cts = new contents();

echo $cts->html_render();



?>
