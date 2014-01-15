<?php
/*
 * AECMS : CMS pour les clubs et activités de l'AE UTBM
 *
 * Copyright 2007
 * - Julien Etelain < julien dot etelain at gmail dot com >
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

/**
 * @file
 */

/**
 * @defgroup aecms AECMS
 * Des sites en kit pour les activités de l'AE.
 *
 * Organisation d'un AECMS, la fonction install_aecms s'en charge trés bien :
 * <pre>
 * club/
 *   specific
 *     aecms.conf.php
 *     custom.css
 *   aecms --> /var/www/ae/www/taiste/aecms
 *   .htaccess
 *     RewriteEngine On
 *     RewriteRule ^([a-z]*)\.php(.*)$  aecms/$1.php$2 [L]
 *     RewriteRule ^$  aecms/index.php [L]
 *     RewriteRule ^images/(.*)$  aecms/images/$1 [L]
 *     RewriteRule ^css/(.*)$  aecms/css/$1 [L]
 * </pre>
 *
 */

$basedir = dirname(dirname($_SERVER['SCRIPT_FILENAME']));
$basedircomp = explode("/",$basedir);
$aecmsname = end($basedircomp);

// Chargement de la configuration statique
if ( !file_exists($basedir."/specific/aecms.conf.php") ) // COnfiguration par défaut, pour les tests
{
  // Configuration par défaut
  define("CMS_ID_ASSO",1);
  define("CMS_PREFIX","cms:".CMS_ID_ASSO.":");
  $topdir = "../";
}
else
{
  include($basedir."/specific/aecms.conf.php");
  $topdir = dirname(readlink($basedir."/aecms"))."/";
}

// Verification de sécu
if ( CMS_ID_ASSO != intval(CMS_ID_ASSO) )
{
  header("Content-Type: text/html; charset=utf-8");
  $this->buffer.="<p>Site actuellement en maintenance. Merci de votre compréhension.</p>";
  exit();
}

// Configuration générale (en BETA)
/**
 * Repertoire de stockage des fichiers de configurations des AEMCS
 * @ingroup aecms
 */
define("CMS_CONFIGPATH","/var/www/var/aecms_conf");
if(defined('CMS_ALTERNATE'))
  define("CMS_CONFIGFILE",CMS_CONFIGPATH."/cms".CMS_ID_ASSO.".".CMS_ALTERNATE.".conf.php");
else
  define("CMS_CONFIGFILE",CMS_CONFIGPATH."/cms".CMS_ID_ASSO.".conf.php");

// Inclusion des classes AE2
require_once($topdir."include/site.inc.php");
require_once($topdir."include/entities/asso.inc.php");
require_once($topdir."include/entities/page.inc.php");

$aecmstopdir = "./";

// Met à jour le catalogue pour AECMS
$GLOBALS["entitiescatalog"]["catphoto"][3]="photos.php";
$GLOBALS["entitiescatalog"]["photo"][3]="photos.php";
$GLOBALS["entitiescatalog"]["utilisateur"][3]=null;
$GLOBALS["entitiescatalog"]["asso"][3]=null;
$GLOBALS["entitiescatalog"]["page"][3]="index.php";

/*
 * NOTE : Il faudra modifier mysqlae.inc.php pour accepter les inclusions d'autres emplacements...
 * ou trouver une solution moins risquée. En aucun cas les fichiers du CMS et leur configuration
 * ne devront être accessible depuis le WEBDAV. L'idéal serait de désactiver les fichiers PHP autres que
 * ceux du CMS dans les webdav où sera exploité AECMS
 */



/**
 * Classe de gestion de site AECMS
 * @ingroup aecms
 * @author Julien Etelain
 */
class aecms extends site
{
  /** Association/activité dont c'est le site*/
  var $asso;
  /** URL publique du site */
  var $pubUrl;
  /** Paramétres du site */
  var $config;

  /**
   * Construteur de site
   * Utilise les constantes CMS_ID_ASSO, CMS_CONFIGFILE et CMS_PREFIX
   */
  function aecms()
  {
    $this->site(false);

    if ( ereg("^/var/www/ae/accounts/([a-z0-9]*)/aecms",$_SERVER['SCRIPT_FILENAME'],$match) )
      $this->pubUrl = "http://ae.utbm.fr/".$match[1]."/";
    else
      $this->pubUrl = "http://".$_SERVER["HTTP_HOST"].dirname($_SERVER["SCRIPT_NAME"])."/";

    $this->tab_array = array (array(CMS_PREFIX."accueil", "index.php", "Accueil"));
    $this->config = array(
      "membres.allowjoinus"=>1,
      "membres.upto"=>ROLEASSO_TRESORIER,
      "boxes.sections"=> CMS_PREFIX."accueil",
      "boxes.names"=>"calendrier",
      "home.news"=>1,
      "home.excludenewssiteae"=>0,
      "css.base"=>"base.css"
    );

    $this->asso = new asso($this->db,$this->dbrw);
    $this->asso->load_by_id(CMS_ID_ASSO);

    $this->set_side_boxes("left",array());
    $this->set_side_boxes("right",array());

    if ( file_exists(CMS_CONFIGFILE) && !isset($_GET["aecms_admin_ignoreconf"]) )
      include(CMS_CONFIGFILE);

    if ($this->is_user_admin())
      $this->tab_array[] = array(CMS_PREFIX."config", "configurecms.php", "Administration");

  }

  function allow_only_logged_users($section="none")
  {
    global $topdir;

    if ( $this->user->is_valid() )
      return;
    if(isset($_REQUEST['connectbtnaecms']))
    {
      switch ($_REQUEST["domain"])
      {
        case "utbm" :
          $this->user->load_by_email($_REQUEST["username"]."@utbm.fr");
        break;
        case "assidu" :
          $this->user->load_by_email($_REQUEST["username"]."@assidu-utbm.fr");
        break;
        case "id" :
          $this->user->load_by_id($_REQUEST["username"]);
        break;
        case "autre" :
          $this->user->load_by_email($_REQUEST["username"]);
        break;
        case "alias" :
          $this->user->load_by_alias($_REQUEST["username"]);
        break;
        default :
          $this->user->load_by_email($_REQUEST["username"]."@utbm.fr");
        break;
      }
      if ( $this->user->is_valid() )
      {
        if ( $this->user->hash != "valid" )
        {
          header("Location: http://ae.utbm.fr/article.php?name=site:activate");
          exit();
        }
        if($this->user->is_password($_POST["password"]))
        {
          $forever=false;
          if ( isset($_REQUEST["personnal_computer"]) )
            $forever=true;
          $this->connect_user($forever);
          $this->user->load_groups();
          return;
        }
      }
    }

    $this->start_page($section,"Identification requise");
    $frm = new form("connect",
                    "http://ae.utbm.fr".$_SERVER["REQUEST_URI"],
                    true,
                    "POST",
                    "Pour accéder à cette section, merci de vous identifier");
    $frm->add_select_field("domain","Connexion",array("utbm"=>"UTBM","assidu"=>"Assidu","id"=>"ID","autre"=>"Autre","alias"=>"Alias"), "autre");
    $frm->add_text_field("username","Utilisateur","prenom.nom","",27,true);
    $frm->add_password_field("password","Mot de passe","","",27);
    $frm->add_checkbox ( "personnal_computer", "Me connecter automatiquement la prochaine fois", false );
    $frm->add_submit("connectbtnaecms","Se connecter");
    $this->add_contents($frm,true);
    $this->end_page();
    exit();
  }

  function start_page ( $section, $title,$compact=false )
  {
    $sections = explode(",",$this->config["boxes.sections"]);
    $boxes = array();

    if ( in_array($section,$sections) )
      $boxes = explode(",",$this->config["boxes.names"]);

    if (isset($this->config["boxes.specific"]))
    {
      $boxes_specific = explode(",",$this->config["boxes.specific"]);
      foreach( $boxes_specific as $name )
      {
        $sections = explode(",",$this->config["boxes.specific.".$name]);
        if (in_array($section, $sections))
          $boxes[] = $name;
      }
    }

    if (! empty($boxes))
    {
      $this->set_side_boxes("right",$boxes,"aecms");

      foreach( $boxes as $name )
      {
        if ( $name == "calendrier" )
          $this->add_box("calendrier",new calendar($this->db,$this->asso->id));
        else
          $this->add_box($name,$this->get_box($name));
      }
    }

    interfaceweb::start_page($section,$title,$compact);
  }

  function _stat($admin=false)
  {
    if($this->is_user_admin())
      return;
    $alt = '';
    if(defined('CMS_ALTERNATE'))
      $alt = CMS_ALTERNATE;
    $h = (int)date('H');
    $d = (int)date('w');
    $w = (int)date('W');
    $y = (int)date('Y');
    $req = new requete($this->db,
                       'SELECT `hour` '.
                       'FROM `aecms_stats` '.
                       'WHERE `id_asso` = \''.$this->asso->id.'\' '.
                       'AND `sub_id`=\''.mysql_real_escape_string($alt).'\' '.
                       'AND `day`=\''.$d.'\' '.
                       'AND `week`=\''.$w.'\' '.
                       'AND `year`=\''.$y.'\'');
    if($req->lines!='24')
    {
      for($i=0;$i<24;$i++)
      {
        $hits=0;
        if(!$damin && $i==$h)
          $hits=1;
        new insert($this->dbrw,
                   'aecms_stats',
                   array('id_asso'=>$this->asso->id,
                         'sub_id'=>$alt,
                         'hour'=>$i,
                         'day'=>$d,
                         'week'=>$w,
                         'year'=>$y,
                         'hits'=>$hits));
      }
      new requete($this->dbrw,
                  'DELETE FROM `aecms_stats` '.
                  'WHERE `id_asso` = \''.$this->asso->id.'\' '.
                  'AND `sub_id`=\''.mysql_real_escape_string($alt).'\' '.
                  'AND `year`<\''.($y-3).'\'');
    }
    else
      new requete($this->dbrw,
                 'UPDATE `aecms_stats` '.
                 'SET `hits` = `hits`+1 '.
                 'WHERE `id_asso` = \''.$this->asso->id.'\' '.
                 'AND `sub_id`=\''.mysql_real_escape_string($alt).'\' '.
                 'AND `day`=\''.$d.'\' '.
                 'AND `week`=\''.$w.'\' '.
                 'AND `year`=\''.$y.'\' '.
                 'AND `hour`=\''.$h.'\'');
  }

  /**
   * Enregistre la configuration acteulle ( tab_array et config )
   * Ecrit le fichier CMS_CONFIGFILE, et si nécessaire creer le dossier CMS_CONFIGPATH
   */
  function save_conf()
  {
    if ( !$this->is_user_admin() )
      return;

    if ( !file_exists(CMS_CONFIGPATH) )
      mkdir(CMS_CONFIGPATH);

    $f = fopen(CMS_CONFIGFILE,"wt");

    if ( !$f )
      return;

    fwrite($f,"<?php\n");


    fwrite($f,'$'."this->config = array(\n");

    $n=0;
    $cnt=count($this->config);

    if ( $cnt == 0 )
      fwrite($f,");\n");
    else
    {
      foreach ( $this->config as $key => $value )
      {
        if ( is_numeric($value) || is_bool($value) )
          fwrite($f,' \''.addcslashes($key,'\'\\').'\' => '.str_replace(",",".",$value).'');
        else
          fwrite($f,' \''.addcslashes($key,'\'\\').'\' => \''.addcslashes($value,'\'\\').'\'');

        $n++;
        if ( $n == $cnt )
          fwrite($f,");\n");
        else
          fwrite($f,",\n");
      }
    }

    fwrite($f,'$'."this->tab_array = array(\n");

    $n=0;
    $cnt=count($this->tab_array)-1;

    if ( $cnt == 0 )
      fwrite($f,");\n");
    else
    {
      global $_REQUEST;
      foreach ( $this->tab_array as $row )
      {
        if ( $row[0] != CMS_PREFIX."config" )
        {
          $grp="null";
          if(isset($row[3]) && !empty($row[3]))
            $grp=intval($row[3]);
          if($_REQUEST["action"] != "addonglet")
            fwrite($f," array(\"".$row[0]."\",\"".$row[1]."\",\"".$row[2]."\",".$grp);
          else
            fwrite($f," array(\"".addslashes($row[0])."\",\"".addslashes($row[1])."\",\"".addslashes($row[2])."\",".$grp);
          $n++;
          if ( $n == $cnt )
            fwrite($f,"));\n");
          else
            fwrite($f,"),\n");
        }
      }
    }
    fwrite($f,"\n?>");

    fclose($f);
  }

  /**
   * Determine si l'utilisateur connecté est administrateur du AECMS.
   * @return true si l'utilisateur est administrateur, false sinon.
   */
  function is_user_admin()
  {
    if ( !$this->user->is_valid() )
      return false;

    if ( !$this->asso->is_member_role($this->user->id,ROLEASSO_MEMBREBUREAU)
         && !$this->user->is_in_group("root") )
      return false;
    if(!defined('ADMIN_SECTION'))
      define('ADMIN_SECTION',true);
    return true;
  }

  /**
   * Renvoie le stdcontents pour la boite demandée, si la boite en gestion est une "page"
   * @return une instance de stdcontents ou NULL si la boite demandée n'existe pas
   */
  function get_box ( $name )
  {
    $page = new page ($this->db);
    $page->load_by_pagename(CMS_PREFIX."boxes:".$name);

    if ( !$page->is_valid() || !$page->is_right($this->user,DROIT_LECTURE) )
      return null;

    return $page->get_contents();
  }


  function end_page () // <=> html_render
  {
    global $wwwtopdir, $basedir ;

    header("Content-Type: text/html; charset=utf-8");
    if(isset($this->config['stats']))
      $this->_stat(defined('ADMIN_SECTION'));
    $this->buffer ="<html>\n";
    $this->buffer.="<head>\n";
    $this->buffer.="<meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\" />\n";
    $this->buffer.="<title>".$this->title." - ".htmlentities($this->asso->nom,ENT_NOQUOTES,"UTF-8")."</title>\n";
    $this->buffer.="<link rel=\"stylesheet\" type=\"text/css\" href=\"" . $wwwtopdir . "css/doku.css\" />\n";
    $this->buffer.="<link rel=\"stylesheet\" type=\"text/css\" href=\"" . $aecmstopdir . "css/".$this->config["css.base"]."\" />\n";

    foreach ( $this->extracss as $url )
      $this->buffer.="<link rel=\"stylesheet\" type=\"text/css\" href=\"" . htmlentities($wwwtopdir . $url,ENT_NOQUOTES,"UTF-8"). "\" />\n";

    if ( file_exists($basedir."/specific/custom.css") )
      $this->buffer.="<link rel=\"stylesheet\" type=\"text/css\" href=\"" . $aecmstopdir . "specific/custom.css?".filemtime($basedir."/specific/custom.css")."\" />\n";

    foreach ( $this->rss as $title => $url )
      $this->buffer.="<link rel=\"alternate\" type=\"application/rss+xml\" title=\"".htmlentities($title,ENT_NOQUOTES,"UTF-8")."\" href=\"".htmlentities($url,ENT_NOQUOTES,"UTF-8")."\" />";

    if ( file_exists($basedir."favicon.ico") )
      $this->buffer .= "<link rel=\"SHORTCUT ICON\" href=\"" . $wwwtopdir . "favicon.ico\" />\n";
    foreach ( $this->extrajs as $url )
      $this->buffer.="<script type=\"text/javascript\" src=\"".htmlentities($wwwtopdir.$url,ENT_QUOTES,"UTF-8")."\"></script>\n";

    $this->buffer.="<script type=\"text/javascript\" src=\"/js/site.js\">var site_topdir='$wwwtopdir';</script>\n";
    $this->buffer.="<script type=\"text/javascript\" src=\"/js/ajax.js\"></script>\n";
    $this->buffer.="<script type=\"text/javascript\" src=\"/js/dnds.js\"></script>\n";
    $this->buffer.="</head>\n";

    $this->buffer.="<body>\n";
        /* Generate the logo */
    $this->buffer.="<div id=\"site\">";

    if (!$this->compact )
    {
      $this->buffer.="<div id=\"logo\"><a href=\"".htmlentities($this->pubUrl,ENT_QUOTES,"UTF-8")."\">";
      $this->buffer.=htmlentities($this->asso->nom,ENT_QUOTES,"UTF-8");
      $this->buffer.="</a></div>\n";
    }

    $this->buffer.="<div class=\"tabsv2\">\n";
    $links=null;

    foreach ($this->tab_array as $entry)
    {
      if(   isset($entry[3])
         && !is_null($entry[3])
         && !$this->user->is_in_group_id($entry[3])
         && !$this->is_user_admin()
        )
        continue;
      $this->buffer.="<span";
      if ($this->section == $entry[0])
      {
        $this->buffer.=" class=\"selected\"";
        $links=$entry[4];
      }
      $this->buffer.="><a id=\"tab_".$entry[0]."\" href=\"" . $aecmstopdir . $entry[1] . "\"";
      $this->buffer.=" title=\"" . stripslashes($entry[2]) . "\">".
        stripslashes($entry[2]) . "</a></span>\n";
    }

    $this->buffer.="</div>\n"; // /tabs

    if ( $links )
    {
      $this->buffer.="<div class=\"sectionlinks\">\n";

      foreach ( $links as $entry )
      {
        if ( ereg("http://(.*)",$entry[0]) )
          $this->buffer.="<a href=\"".$entry[0]."\">".$entry[1]."</a>\n";
        else
          $this->buffer.="<a href=\"".$aecmstopdir.$entry[0]."\">".$entry[1]."</a>\n";
      }

      $this->buffer.="</div>\n";
    }
    else
      $this->buffer.="<div class=\"emptysectionlinks\"></div>\n";

    $this->buffer.="<div class=\"contents\">\n";
    $idpage = "";

    foreach ( $this->sides as $side => $names )
    {
      if ( count($names) )
      {
        $idpage .= substr($side,0,1);
        $this->buffer.="<div id=\"$side\">\n";
        foreach ( $names as $name )
        {
          if ( $cts = $this->boxes[$name] )
          {
            $this->buffer.="<div class=\"box\" id=\"sbox_$name\">\n";
            if ( !empty($cts->title) )
            $this->buffer.="<h1>".$cts->title."</h1>\n";
            $this->buffer.="<div class=\"body\" id=\"sbox_body_$name\">\n";
            $this->buffer.=$cts->html_render();
            $this->buffer.="</div>\n";
            $this->buffer.="</div>\n";
          }

        }
        $this->buffer.="</div>\n";
      }
    }

    if ( $idpage == "" ) $idpage = "n";

    $this->buffer.="\n<!-- page -->\n";
    $this->buffer.="<div class=\"page\" id=\"$idpage\">\n";

    foreach ( $this->contents as $cts )
    {
      $cssclass = "article";

      if ( !is_null($cts->cssclass) )
        $cssclass = $cts->cssclass;

      $this->buffer.="<div class=\"$cssclass\"";
      if ( $cts->divid )
        $this->buffer.=" id=\"".$cts->divid."\"";
      $this->buffer.=">\n";

      if ( $cts->toolbox )
      {
        $this->buffer.="<div class=\"toolbox\">\n";
        $this->buffer.=$cts->toolbox->html_render()."\n";
        $this->buffer.="</div>\n";
      }

      if ( $cts->title )
        $this->buffer.="<h1>".$cts->title."</h1>\n";

      $this->buffer.=$cts->html_render();
      $this->buffer.="</div>\n";
    }

    $this->buffer.="</div>\n";
    $this->buffer.="<!-- end of page -->\n\n";

    if($this->config['footer'])
    {
      global $topdir;
      require_once($topdir."include/cts/cached.inc.php");
      $path = CMS_ID_ASSO;
      if(defined('CMS_ALTERNATE'))
        $path.="_".CMS_ALTERNATE;
      $cache = new cachedcontents("aecmsfooter_".$path);
      if ( !$cache->is_cached() )
        $cache->set_contents(new contents(false,doku2xhtml($this->config['footer'])));
      $cache=$cache->get_cache();
      $this->buffer.=$cache->buffer."\n";
    }

    $this->buffer.="<p class=\"footer\">\n";

    if ( !is_null($this->asso->id_parent) )
    {
      $this->buffer.="<a href=\"/\">association des etudiants de l'utbm</a>";
      $this->buffer.=" - <a href=\"index.php?name=:legals\">informations légales</a>";
      $this->buffer.=" - <a href=\"contact.php\">contact</a>";
    }
    else
    {
      $this->buffer.="<a href=\"index.php?name=legals\">informations légales</a>";
      $this->buffer.=" - <a href=\"contact.php\">contact</a>";
    }

    $this->buffer.="</p>\n";

    $this->buffer.="</div>\n"; // /contents
    $this->buffer.="<div id=\"endsite\"></div></div>\n";
    $this->buffer.="</body>\n";
    $this->buffer.="</html>\n";
    echo $this->buffer;
  }


}

$site = new aecms();

?>
