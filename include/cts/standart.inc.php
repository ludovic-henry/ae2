<?php

/** @file
 *
 *
 */
/* Copyright 2005,2006
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

require_once($topdir."include/lib/dokusyntax.inc.php");
require_once($topdir."include/catalog.inc.php");
require_once($topdir."include/entities/group.inc.php");
require_once($topdir."include/geo.inc.php");

/**
 * @defgroup display Affichage
 */

/**
 * @defgroup display_cts Contents
 * @ingroup display
 */


/** Conteneur standart
 * @ingroup display_cts
 */
class stdcontents
{
  var $title;
  var $divid;
  var $cssclass;

  var $buffer;
  var $toolbox;

  function stdcontents()
  {
    $this->title = null;
    $this->divid = null;
    $this->cssclass = null;
  }

  /** Definie l'element à la position "toolbox"
   * @param $cts stdcontents à definir
   */
  function set_toolbox ( $cts )
  {
    $this->toolbox = $cts;
  }


  function set_help_page ( $page )
  {
    $this->set_toolbox(new contents(false,"<a href=\"".$topdir."article.php?name=$page\"><img src=\"".$topdir."images/icons/16/page.png\" class=\"icon\" alt=\"Article\" /> Aide</a>"));
  }


  function html_render ()
  {
      return $this->buffer;
  }

  function is_cachable()
  {
      return true;
  }

}

/** Conteneur de conteneurs, titre et paragraphes
 * @ingroup display_cts
 */
class contents extends stdcontents
{

  function contents ( $title = false, $buffer = "" )
  {
    $this->title = $title;
    $this->buffer = $buffer;
  }

  /** Ajoute un stdcontents
   * @param $cts    stdcontents à ajouter
   * @param $title  Booléen : affiche ou non le titre
   * @param $box    Booléen : place le contenu dans un cadre ($name requis)
   * @param $name    Nom du cadre
   * @param $class  Style à appliquer sur le cadre
   * @param $onoff  Boite qui peut aparaitre et disparaitre ($title=true, $name doit être défini, la boite sera $name_contents)
   * @param $on    Attribut par defaut d'un boite qui peut apparaite ou disparaitre
   * @param $remember  Se souvenir ou non de la valeur du status de la boite (visible ou non)
   */
  function add ( $cts, $title = false, $box = false, $name = false, $class = false, $onoff = false, $on = true, $remember = true )
  {
    global $wwwtopdir;

    if ( $box && $name )
    {
      $this->buffer .= "<div id=\"$name\" ";
      if ( $class )
        $this->buffer .=" class=\"$class\"";
      $this->buffer .=">\n";
    }

    if ( $cts->toolbox )
    {
      $this->buffer .= "<div class=\"toolbox\">\n";
      $this->buffer .= $cts->toolbox->html_render()."\n";
      $this->buffer .= "</div>\n";
    }

    if ( $onoff && $name && $title)
    {

      $uinf_ref = "on-cts-".$name;

      if ( $remember )
        if ( isset($_SESSION["usersession"][$uinf_ref]) )
          $on = ($_SESSION["usersession"][$uinf_ref] == 1);

      $img = "fld.png";
      if ( !$on )
        $img = "fll.png";

      $this->buffer .= "<h2><a href=\"#\" onclick=\"on_off_icon_store('".$name."','".$wwwtopdir."','$uinf_ref'); return false;\"><img src=\"".$wwwtopdir."images/".$img."\" alt=\"togle\" class=\"icon\" id=\"".$name."_icon\" /> ".$cts->title."</a></h2>\n";
    }
    else if ( $title )
      $this->buffer .= "<h2>".$cts->title."</h2>\n";


    if ( $onoff && $name )
    {
      $this->buffer .=  "<div id=\"".$name."_contents\"";
      if ( !$on )
        $this->buffer .=  " style=\"display: none;\"";
      $this->buffer .=  ">\n";
    }

    $this->buffer .= $cts->html_render()."\n";

    if ( $onoff && $name )
      $this->buffer .= "</div>\n";

    if ( $box && $name )
      $this->buffer .= "</div>\n";
  }



  /** Ajoute un titre
   * @param $level  Niveau du titre (1 à n)
   * @param $title   Titre
   */
  function add_title ( $level, $title, $class = false )
  {
    if(!$class)
      $this->buffer .= "<h".$level.">".$title."</h".$level.">\n";
    else
      $this->buffer .= "<h".$level." class=\"".$class."\">".$title."</h".$level.">\n";
  }

  /** Ajoute un paragraphe
   * @param $contents Paragraphe
   * @see wikicontents
   */
  function add_paragraph ( $contents, $class=false )
  {
    $this->buffer .= "<p";
    if ( $class ) $this->buffer .= " class=\"$class\"";
    $this->buffer .= ">".$contents."</p>\n";
  }

  function puts ( $data )
  {
    $this->buffer .= $data;
  }

}

/** Conteneur de texte structuré
 * @ingroup display_cts
 */
class wikicontents extends contents
{
  var $contents;
  var $wiki;

  /** Crée un stdcontents à partir d'un texte au format DokuWiki et de son titre
   * @param $title  Titre
   * @param $contents  Texte structuré
   */
  function wikicontents($title,$contents,$rendernow=false)
  {
    $this->title = $title;
    $this->contents = $contents;
    if ( $rendernow )
      $this->buffer = doku2xhtml($this->contents);
  }

  function html_render()
  {
    if ( $this->buffer )
      return $this->buffer;

    return $this->buffer = doku2xhtml($this->contents);
  }

  function is_cachable()
  { 
    if ( $this->buffer && (preg_match("/pl2_multi/i",$this->buffer) === 0) )
	return true;
    else
	return false;
  }
}

/** Conteneur de l'aide du texte structuré
 * @ingroup display_cts
 */
class wikihelp extends stdcontents
{

  var $page;

  /** Crée un stdcontents servant d'aide pour le wiki
   */
  function wikihelp()
  {
    $this->title = "Aide syntaxe (type DokuWiki)";
  }

  function html_render ()
  {
    global $wwwtopdir;
    return
    "<h2>Mise en forme élémentaire</h2>".
    "<ul>".
    "<li><b>Gras</b> : **gras**</li>".
    "<li><b>Italique</b> : //italique//</li>".
    "<li><b>Souligné</b> : __souligné__</li>".
    "<li><b>Barré</b> : &lt;del&gt;barré&lt;del&gt;</li>".
    "<li><b>Lien</b> : [[url]], [[url|texte du lien]]</li>".
    "<li><b>Image</b> : {{url}}, {{url?<i>largeur</i>x<i>hauteur</i>}}, centré {{ url }}, droite {{ url}}, gauche {{url }}</li>".
    "<li><b>Notes bas de page</b> : ((note bas de page))</li>".
    "</ul>".
    "<h2>Paragraphes</h2>".
    "<h2>Tableaux</h2>".
    "<p><a href=\"".$wwwtopdir."article.php?name=docs:syntax\">Voir aussi : <b>Aide détaillée</b></a></p>";



  }

}

/**
 * Conteneur des erreurs
 * @ingroup display_cts
 */
class error extends stdcontents
{
  /** Constrtuit un message d'erreur
   * @param $title Titre de l'erreur (ex: Accés refusé, Non trouvé...)
   * @param $description Description de l'erreur
   */
  function error ( $title, $description )
  {
    $this->title = "Erreur : ".$title;
    $this->buffer = "<p>".$description."</p>";
  }

}

/** Conteneur de formulaires
 * @ingroup display_cts
 */
class form extends stdcontents
{
  var $action;
  var $method;
  var $name;

  var $autorefill;

  var $hiddens;
  var $enctype;
  var $error_contents;

  var $work_variables;

  private $_subform_values;

  /** Initialise un formulaire
   * @param $name      Nom du formulaire
   * @param $action    Fichier sur le quel le formulaire sera envoyé
   * @param $allow_refill  Authorise le formulaire à se completer en se basant sur les valeurs envoyé lors de l'appel de la page
   * @param $method    Méthode à utiliser pour envoyer les données (post ou get)
   * @param $title    Titre du formulaire (facultatif)
   */
  function form ( $name, $action, $allow_refill = false, $method = "post", $title = false )
  {
    $this->action = $action;
    $this->method = $method;
    $this->name = $name;
    $this->title = $title;
    $this->enc = false;
    $this->event = array();

    if ( $_REQUEST["magicform"]["name"] == $name )
      $this->autorefill = $allow_refill;

    $this->hiddens=array();
    $this->hiddens["magicform[name]"] = $name;
  }

  /** Ajoute un champ caché au formulaire
   * Valeur à récupérer dans $_REQUEST[$name]
   * @param $name    Nom du champ
   * @param $value  Valeur du champ
   */
  function add_hidden ( $name, $value = "" )
  {
    $this->hiddens[$name] = $value;
  }

  /** Ajoute des fonctions javascript au formulaire
   * Les actions successives d'un meme event sont concatenees par "; "
   * @param $event evenement cible : ex : onsubmit
   * @param $action fonction javascript a appeler : ex : valider_entrees(this)
   */
  function set_event($event, $action){
    if(!isset($this->event[$event]) || empty($this->event[$event]))
      $this->event[$event] = $action;
    else
      $this->event[$event] = implode("; ", array($this->event[$event], $action));
  }
  /** Ajoute un champ texte au formulaire
   * Valeur à récupérer dans $_REQUEST[$name]
   * @param $name    Nom du champ
   * @param $title  Libéllé du champ
   * @param $value  Valeur du champ
   * @param $required  Précise si le champ est obligatoire
   * @param $size    Taille du champ
   */
  function add_text_field ( $name, $title, $value = "", $required = false , $size = false, $fast_clean = false, $enabled = true, $text_after = null, $id = false, $max_lenght = false)
  {
    if ( $this->autorefill && ($_REQUEST[$name] || $_REQUEST[$name] =="0")) $value = $_REQUEST[$name];

    $this->buffer .= "<div class=\"formrow\">\n";

    $this->_render_name($name,$title,$required);

    $this->buffer .= "<div class=\"formfield\"><input type=\"text\" name=\"$name\" value=\"".htmlentities($value,ENT_COMPAT,"UTF-8"). "\"";
    if ( $id )
    {
      $this->buffer .= " id=\"".$id."\"";
    }
    if ( $fast_clean )
      $this->buffer .= " onfocus=\"if(this.value=='$value')this.value=''\" onblur=\"if(this.value=='')this.value='$value'\"";
    if ( $size ) {
      if( $max_lenght )
        $this->buffer .= " size=\"$size\" maxlength=\"$max_lenght\"";
      else
        $this->buffer .= " size=\"$size\" maxlength=\"$size\"";
    }

    if (!$enabled)
      $this->buffer .= " DISABLED";
    $this->buffer .= "/>";

    if ($text_after)
      $this->buffer .= $text_after;

    $this->buffer .= "</div>\n";

    $this->buffer .= "</div>\n";
  }

  function add_color_field ( $name, $mode="rgb", $title, $value = "000000", $required = false )
  {
    $this->add_text_field ( $name, $title, $value, $required ); // En attendant mieu
  }

  function add_geo_field ( $name, $title, $type, $value = null, $required = false)
  {
    if ( $type == "lat" )
    {
      if ( is_null($value) )
        $value="0°0'0.0\"N";
      else if ( $value < 0 )
        $value=geo_radians_to_degrees(-1*$value)."S";
      else
        $value=geo_radians_to_degrees($value)."N";
    }
    else
    {
      if ( is_null($value) )
        $value="0°0'0.0\"E";
      else if ( $value < 0 )
        $value=geo_radians_to_degrees(-1*$value)."O";
      else
        $value=geo_radians_to_degrees($value)."E";
    }

    $this->add_text_field("magicform[geo][$name]",$title,$value,$required);
  }

  /**
   * @deprecated Technique un peu ancienne, smartselect est une généralisation :
   * Utilisez plutôt add_entity_smartselect ( $name, $title, new Utilisateur($site->db)...)
   */
  function add_user_fieldv2 ( $name, $title, $value = "", $required = false, $surnom = false )
  {
    global $topdir;
    $siteroot=$topdir;
    if( defined('CMS_ID_ASSO') )
      $siteroot="../";
    if ( $this->autorefill && ($_REQUEST[$name] || $_REQUEST[$name] =="0")) $value = $_REQUEST[$name];

    $this->buffer .= "<div class=\"formrow\">\n";
    $this->_render_name($name,$title,$required);
    $this->buffer .= "<div class=\"formfield\">";

    $this->buffer .= "<div id=\"$name\" class=\"userfield\">" .
        "<input type=\"hidden\" name=\"".$name."\" id=\"".$name."_id\" value=\"0\" />" .
        "<div id=\"".$name."_fieldbox\" class=\"fieldbox\" style=\"display:none;\">" .
        "<input type=\"text\" id=\"".$name."_field\" onkeyup=\"userselect_keyup(event,'".$name."','".$siteroot."');\" />" .
        "</div>" .
        "<div id=\"".$name."_static\" class=\"staticbox\" onclick=\"userselect_toggle('".$name."');\">" .
        "(personne)" .
        "</div>" .
        "<div class=\"button\">" .
        "<a href=\"#\" id=\"".$name."_button\" onclick=\"userselect_toggle('".$name."'); return false;\">choisir</a>" .
        "</div>" .
        "<div id=\"".$name."_currentuser\" class=\"currentuser\" style=\"display:none;\">" .
        "Aucun utilisateur selectionné. " .
        "</div>" .
        "<div id=\"".$name."_result\" class=\"listing\" style=\"display:none;\">" .
        "Entrez le nom prenom ou surnom de la personne." .
        "</div>" .
        "</div>";



    $this->buffer .= "</div>\n";
    $this->buffer .= "</div>\n";

  }

  /** Ajoute un champ de selection d'une entité (stdentity).
   *
   * Determine automatiquement la forme la plus appropriée pour la selection,
   * en fonction des capacité de la classe de l'entité et de ses préférences.
   *
   * L'id de l'entité séléctionné sera placé dans la variable (0 si aucune).
   *
   * L'entité **doit** être correctement déclarée dans catalog.inc.php
   * y compris le fichier contenant la déclarartion de la classe, même si cela
   * est facultatif pour les autres fonctionalités liées à stdentity.
   *
   * @param $name    Nom du champ
   * @param $title  Libéllé du champ
   * @param $instance Instance de l'entité : soit une vierge, soit la valeur
   * @param $none Authorise à selectionner aucunne entité (0 sera placé dans la variable)
   * @param $required  Précise si le champ est obligatoire [redondant avec $none, mais passons]
   * @param $conds Conditions sur les entités selectionnable (tableau associatif avec nom du champ sql associé à une valeur)
   * @param $force Forcer le dernier cas
   * @see stdentity
   */
  function add_entity_smartselect ( $name, $title, &$instance, $none = false, $required = false, $conds=null, $force=false )
  {
    global $wwwtopdir;

    if ( $this->autorefill && ($_REQUEST[$name] || $_REQUEST[$name] =="0"))
      $instance->load_by_id($_REQUEST[$name]);

    $classname = get_class($instance);

    if ( $instance->can_explore() && is_null($conds) )
    {

      $this->buffer .= "<div class=\"formrow\">\n";
      $this->_render_name($name,$title,$required);
      $this->buffer .= "<div class=\"formfield\">";

      if ( $instance->is_valid() )
        $value=$instance->id;
      else
        $value=0;

      $this->buffer .= "<div id=\"$name\" class=\"userfield\">" .
          "<input type=\"hidden\" name=\"".$name."\" id=\"".$name."_id\" value=\"$value\" />" .
          "<div id=\"".$name."_static\" class=\"staticbox\" onclick=\"exfield_toggle('".$wwwtopdir."','".$name."','".$classname."');\">";
      $verbe = "choisir";
      if ( $instance->is_valid() )
      {
        $this->buffer .= "<img src=\"".$wwwtopdir."images/icons/16/".$GLOBALS["entitiescatalog"][$classname][2]."\" class=\"icon\" alt=\"\" /> ".htmlentities($instance->get_display_name(),ENT_COMPAT,"UTF-8");
        $verbe="changer";
      }
      else
        $this->buffer .= "(aucun)";

      $this->buffer .= "</div>" .
          "<div class=\"button\">" .
          "<a href=\"#\" id=\"".$name."_button\" onclick=\"exfield_toggle('".$wwwtopdir."','".$name."','".$classname."'); return false;\">$verbe</a>" .
          "</div>" .
          "<div id=\"".$name."_result\" class=\"tree\" style=\"display:none;\">";

      if ( $none )
        $this->buffer .= "<ul><li><a href=\"#\" onclick=\"exfield_select('$wwwtopdir','$name','$classname','0','(aucun)','');\">(aucun)</a></li></ul>";

      $this->buffer .= "<ul id=\"".$name."_".$classname."_root\"></ul>".
          "</div>" .
          "</div>";

      $this->buffer .= "</div>\n";
      $this->buffer .= "</div>\n";

      return;
    }

    if ( !$force && (!$instance->can_fsearch() || !is_null($conds)  || $instance->prefer_list()) )
    {
      if ( !$instance->can_enumerate() )
        return;

      $values = $instance->enumerate ( $none, $conds );

      $this->add_select_list_entity_field ( $name, $title, $values, $instance );
      return;
    }


    $this->buffer .= "<div class=\"formrow\">\n";
    $this->_render_name($name,$title,$required);
    $this->buffer .= "<div class=\"formfield\">";

    if ( $instance->is_valid() )
      $value=$instance->id;
    else
      $value=0;

    $constraints='';
    if(is_array($conds) && !empty($conds))
    {
      $uid='constraint_'.gen_uid();
      $constraints=', '.$uid;
      $this->buffer .= "\n<script language=\"javascript\">";
      $this->buffer .= "var ".$uid."=new Array();\n";
      foreach($conds as $sqlfield => $formfield)
        $this->buffer .=$uid."['".$sqlfield."']='".$formfield."';\n";
      $this->buffer .="</script>\n";
    }
    $this->buffer .= "<div id=\"$name\" class=\"userfield\">" .
        "<input type=\"hidden\" name=\"".$name."\" id=\"".$name."_id\" value=\"$value\" />" .
        "<div id=\"".$name."_fieldbox\" class=\"fieldbox\" style=\"display:none;\">" .
        "<input type=\"text\" id=\"".$name."_field\" onkeyup=\"fsfield_keyup ( event, '".$wwwtopdir."','".$name."', '".$classname."' ".$constraints.")\" />" .
        "</div>" .
        "<div id=\"".$name."_static\" class=\"staticbox\" onclick=\"fsfield_toggle('".$wwwtopdir."','".$name."');\">";
    $verbe = "choisir";
    if ( $instance->is_valid() )
    {
      $this->buffer .= "<img src=\"".$wwwtopdir."images/icons/16/".$GLOBALS["entitiescatalog"][$classname][2]."\" class=\"icon\" alt=\"\" /> ".htmlentities($instance->get_display_name(),ENT_COMPAT,"UTF-8");
      $verbe="changer";
    }
    else
      $this->buffer .= "(aucun)";

    $this->buffer .= "</div>" .
        "<div class=\"button\">" .
        "<a href=\"#\" id=\"".$name."_button\" onclick=\"fsfield_toggle('".$wwwtopdir."','".$name."'); return false;\">$verbe</a>" .
        "</div>" .
        "<div id=\"".$name."_result\" class=\"listing\" style=\"display:none;\">";

    if ( $none )
      $this->buffer .= "<ul><li><a href=\"#\" onclick=\"fsfield_sel('$wwwtopdir','$name','0','(aucun)','');\">(aucun)</a></li></ul>";

    $this->buffer .= "</div>" .
        "</div>";

    $this->buffer .= "</div>\n";
    $this->buffer .= "</div>\n";

    $this->buffer .= "<script>fsfield_init('".$wwwtopdir."','".$name."');</script>";
  }




  /** Ajoute un champ monétaire
   * Valeur à récupérer dans $_REQUEST[$name] (en centimes!)
   * @param $name    Nom du champ
   * @param $title  Libéllé du champ
   * @param $value  Valeur du champ (en centimes!)
   * @param $required  Précise si le champ est obligatoire
   * @param $devise  Devise (pour affichage)
   * @param $size    Taille du champ
   */
  function add_price_field ( $name, $title, $value = "", $required = false, $devise="Euros" , $size = false)
  {

    if ( $this->autorefill && ($_REQUEST[$name] || $_REQUEST[$name] =="0")) $value = $_REQUEST[$name];

    $name = fname_protect($name);

    $this->buffer .= "<div class=\"formrow\">\n";

    $this->_render_name($name,$title,$required);

    $this->buffer .= "<div class=\"formfield\"><input type=\"text\" name=\"magicform[price][$name]\" value=\"".sprintf("%.2f",$value/100)."\"";
    if ( $size )
      $this->buffer .= " size=\"$size\"";
    $this->buffer .= "/> $devise</div>\n";

    $this->buffer .= "</div>\n";
  }



  /** Ajoute un texte d'information
   * @param $info    Texte
   */
  function add_info ( $info )
  {
    $this->buffer .= "<div class=\"formrow\">\n";
    $this->buffer .= "<div class=\"formlabel\"></div>\n";
    $this->buffer .= "<div class=\"formfield\">$info</div>\n";
    $this->buffer .= "</div>\n";
  }


  /** Ajoute une barre de bouton de syntaxe DokuWiki
   * @param $area_name nom du textarea concerné (attribut 'name' et non pas 'id' !)
   */
  function add_dokuwiki_toolbar($area_name,$id_asso=null,$folder=null,$forum=false,$simple=false)
  {
    global $wwwtopdir;
    $siteroot = $wwwtopdir;
    if( defined('CMS_ID_ASSO') )
      $siteroot="../".$wwwtopdir;
    $context="";

    if ( !is_null($id_asso) )
      $context="id_asso=".$id_asso;

    if($forum)
    {
      if(!empty($context))
        $context.='&amp;forum=vrai';
      else
        $context='forum=vrai';
    }
    if ( !is_null($folder) )
    {
      if ( !empty($context) )
        $context.= "&amp;";
      $context.="folder=".rawurlencode($folder);
    }

    $id = "textarea_".$this->name."_".$area_name;

    $tools = array (
      "bold" => array("Gras","**","**","Gras"),
      "italic" => array("Italic","//","//","Italic"),
      "underline" => array("Souligné","__","__","Souligné"),
      "strike" => array("Barré","<del>","</del>","Barré"),
      "h1" => array("Titre de niveau 1","====== "," ======\\n","Titre de niveau 1"),
      "h2" => array("Titre de niveau 2","===== "," =====\\n","Titre de niveau 2"),
      "h3" => array("Titre de niveau 3","==== "," ====\\n","Titre de niveau 3"),
      "h4" => array("Titre de niveau 4","=== "," ===\\n","Titre de niveau 4"),
      "h5" => array("Titre de niveau 5","== "," ==\\n","Titre de niveau 5"),
      "link" => array("Lien interne","[[","]]","Lien interne"),
      "linkext" => array("Lien externe","[[","]]","http://exemple.com/|Lien externe"),
      "ul" => array("Liste à puce","  * ","\\n","Liste à puce"),
      "ol" => array("Liste numérotée","  - ","\\n","Liste numérotée"),
      "quote" => array("Citer","[quote]","[/quote]\\n","Citation"),
      "image" => array("Image","{{","}}","Image"),
      "hr" => array("Ligne horizontale","----\\n","",""));

    $this->buffer .= "<div class=\"formrow\">\n";
    $this->buffer .= "<div class=\"formlabel\"></div>\n";
    $this->buffer .= "<div class=\"formfield\">\n";

    foreach ( $tools as $tool => $infos )
    {
      $this->buffer .=
        "<a onclick=\"insert_tags2('".$id."','".$infos[1]."','".$infos[2]."','".$infos[3]."');\" />".
        "<img src=\"".$siteroot."images/toolbar/".$tool.".png\" alt=\"".$infos[0]."\" title=\"".$infos[0]."\" />".
        "</a> \n";
    }

    $this->buffer .= "<a onclick=\"nl2doku ('". $id ."');\"><img src=\"". $siteroot ."images/toolbar/2slashes.png\" alt=\"2slashes\"
        title=\"Complète les retours à la ligne\" /></a>";

    if(!$simple)
    {
      $this->buffer .=
        "<a onclick=\"selectWikiImage('".$siteroot."','".$id."','$context');\" />".
        "<img src=\"".$siteroot."images/toolbar/browse_image.png\" alt=\"Parcourir image\" title=\"Parcourir image\" />".
        "</a> \n";

      $this->buffer .=
        "<a onclick=\"selectWikiFile('".$siteroot."','".$id."','$context');\" />".
        "<img src=\"".$siteroot."images/toolbar/attach.png\" alt=\"Attacher un fichier\" title=\"Attacher un fichier\" />".
        "</a> \n";
      $this->buffer .= " - <a href=\"".$siteroot."article.php?name=docs:syntax\" target=\"_blank\">aide sur la syntaxe</a>\n";
    }
    $this->buffer .= "</div>\n";
    $this->buffer .= "</div>\n";
  }

  /**
   * Ajout un champs pour joindre un ou plusieurs fichiers
   * @param $name Nom du champs dans le quel sera placé la liste des fichiers (array de dfile)
   * @param $tilte Libéllé du champ
   * @param $file Liste des fichiers initiaux (array de dfile)
   * @param $id_asso Association/Club de l'espace de fichier à proposer par défaut
   * @param $folder Dossier à proposer par défaut pour l'ajout de fichiers
   */
  function add_attached_files_field ( $name, $title, $files, $id_asso=null, $folder=null )
  {
    global $wwwtopdir;

    $name = fname_protect($name);

    if ( $this->autorefill && $_REQUEST[$name] ) $value = $_REQUEST[$name];

    $context="";

    if ( !is_null($id_asso) )
      $context="id_asso=".$id_asso;

    if ( !is_null($folder) )
    {
      if ( !empty($context) )
        $context.= "&amp;";
      $context.="folder=".rawurlencode($folder);
    }

    $this->buffer .= "<div class=\"formrow\">\n";
    $this->_render_name($name,$title,$required);
    $this->buffer .= "<div class=\"formfield\">";

    $ids=array();

    $this->buffer .= "<div class=\"filesselect\" id=\"_files_".$name."_items\">";

    if ( !empty($files) )
    {
      foreach ( $files as $file )
      {
        $ids[] = $file->id;

        /* ATTENTION, toute modification du code HTML doit être faite aussi dans site.js */

        $this->buffer .= "<div class=\"slsitem\" id=\"_files_".$name."_".$file->id."\">";
        $this->buffer .= "<a href=\"".$wwwtopdir."/d.php?id_file=".$file->id."\"><img src=\"".$wwwtopdir."images/icons/16/file.png\" /> ".htmlentities($file->titre,ENT_NOQUOTES,"UTF-8")."</a> ";

        $this->buffer .= "<a href=\"\" onclick=\"removeListFile('$wwwtopdir','$name',".$file->id."); return false;\"><img src=\"".$wwwtopdir."images/actions/delete.png\" alt=\"Enlever\" /></a>";
        $this->buffer .= "</div>\n";
      }
    }
    $this->buffer .= "</div>\n";

    $this->buffer .= "<div class=\"filesselectbutton\">";
    $this->buffer .= "<a href=\"#\" onclick=\"selectListFile('$wwwtopdir','$name','$context'); return false;\"><img src=\"".$wwwtopdir."images/toolbar/attach.png\" alt=\"Ajouter\" /> Joindre un fichier</a>";
    $this->buffer .= "</div>\n";

    $this->buffer .= "<input type=\"hidden\" name=\"magicform[files][$name]\" id=\"_files_".$name."_ids\" value=\"".implode(",",$ids)."\" />";

    $this->buffer .= "</div>\n";
    $this->buffer .= "</div>\n";
  }



  /** Ajoute un champ mot de passe au formulaire
   * Valeur à récupérer dans $_REQUEST[$name]
   * @param $name    Nom du champ
   * @param $title  Libéllé du champ
   * @param $value  Valeur du champ
   * @param $required  Précise si le champ est obligatoire
   * @param $size    Taille du champ
   */
  function add_password_field ( $name, $title, $value = "", $required = false, $size = false )
  {
    if ( $this->autorefill && $_REQUEST[$name] ) $value = $_REQUEST[$name];

    $this->buffer .= "<div class=\"formrow\">\n";
    $this->_render_name($name,$title,$required);
    $this->buffer .= "<div class=\"formfield\"><input type=\"password\" name=\"$name\"";
    if ( $size )
      $this->buffer .= " size=\"$size\"";
    $this->buffer .= "/></div>\n";
    $this->buffer .= "</div>\n";
  }

  /** Ajoute un champ fichier au formulaire
   * @param $name    Nom du champ
   * @param $title  Libéllé du champ
   * @param $required  Précise si le champ est obligatoire
   */
  function add_file_field ( $name, $title, $required = false )
  {
    $this->buffer .= "<div class=\"formrow\">\n";
    $this->_render_name($name,$title,$required);

    $this->buffer .= "<div class=\"formfield\"><input type=\"file\" name=\"$name\" /></div>\n";
    $this->buffer .= "</div>\n";
    $this->enctype = "multipart/form-data";
    $this->method = "post";
  }

  /** Ajoute un champ texte multi-lignes au formulaire
   * Valeur à récupérer dans $_REQUEST[$name]
   * @param $name    Nom du champ
   * @param $title  Libéllé du champ
   * @param $value  Valeur du champ
   * @param $width  Largeur du champs en colone (20)
   * @param $height  Hauteur du champs en lignes (3)
   * @param $required  Précise si le champ est obligatoire
   */
  function add_text_area ( $name, $title, $value="", $width=40, $height=3, $required = false, $allow_extend=false, $enable=true)
  {
    if ( $this->autorefill && $_REQUEST[$name] ) $value = $_REQUEST[$name];
    $this->buffer .= "<div class=\"formrow\">\n";
    $this->_render_name($name,$title,$required);
    $this->buffer .= "<div class=\"formfield\"><textarea name=\"$name\" id=\"textarea_".$this->name."_".$name."\" rows=\"$height\" cols=\"$width\"";

    if (!$enable)
      $this->buffer .= " disabled=\"disabled\"";

    $this->buffer .= ">";



    $this->buffer .= htmlentities($value,ENT_NOQUOTES,"UTF-8")."</textarea>";
    if($allow_extend)
    {
      $this->buffer .= "<br />\n";
      $this->buffer .= "<input type=\"button\" value=\"+ +\" onclick=\"extend_textarea('textarea_".$this->name."_".$name."')\">\n";
      $this->buffer .= "<input type=\"button\" value=\"- -\" onclick=\"reduce_textarea('textarea_".$this->name."_".$name."')\">\n";
    }
    $this->buffer .= "</div>\n</div>\n";
  }

  /** Ajoute un champ date au formulaire
   * Valeur à récupérer dans $_REQUEST[$name] sous forme d'un timestamp unix
   * @param $name    Nom du champ
   * @param $title  Libéllé du champ
   * @param $value  Valeur du champ (timestamp unix, -1 ou null pour aucun)
   * @param $required  Précise si le champ est obligatoire
   */
  function add_date_field ( $name, $title, $value = -1, $required = false,  $enabled = true)
  {
    global $wwwtopdir;

    if ( $this->autorefill && $_REQUEST[$name] ) $value = $_REQUEST[$name];
    $this->buffer .= "<div class=\"formrow\">\n";
    $this->_render_name($name,$title,$required);

    $this->buffer .= "<div class=\"formfield\"><input type=\"text\" id=\"$name\" name=\"magicform[date][$name]\" value=\"";
    if ( $value != -1 && !is_null($value) )
      $this->buffer .= date("d/m/Y",$value);
    $this->buffer .= "\" ";
    if (!$enabled)
      $this->buffer .= "DISABLED";
    $this->buffer .= "/>";
    $this->buffer .= "<a href=\"javascript:opencal('".$wwwtopdir."', '$name','date')\">";
    $this->buffer .= "<img src=\"".$wwwtopdir."images/icons/16/ical.png\">";
    $this->buffer .= "</a>";
    $this->buffer .= "</div>\n";
    $this->buffer .= "</div>\n";
  }


  /** Ajoute un champ date et heure au formulaire
   * Valeur à récupérer dans $_REQUEST[$name] sous forme d'un timestamp unix
   * @param $name    Nom du champ
   * @param $title  Libéllé du champ
   * @param $value  Valeur du champ (timestamp unix, -1 ou null pour aucun)
   * @param $required  Précise si le champ est obligatoire
   */
  function add_datetime_field ( $name, $title, $value = -1, $required = false )
  {
    global $wwwtopdir;
    if ( $this->autorefill && $_REQUEST[$name] ) $value = $_REQUEST[$name];
    $this->buffer .= "<div class=\"formrow\">\n";
    $this->_render_name($name,$title,$required);

    $this->buffer .= "<div class=\"formfield\"><input type=\"text\" id=\"$name\" name=\"magicform[datetime][$name]\" value=\"";
    if ( $value != -1 && !is_null($value) )
      $this->buffer .= date("d/m/Y H:i:s",$value);
    $this->buffer .= "\" />";
    $this->buffer .= "<a href=\"javascript:opencal('".$wwwtopdir."','$name','datetime')\">";
    $this->buffer .= "<img src=\"".$wwwtopdir."images/icons/16/ical.png\">";
    $this->buffer .= "</a>";
    $this->buffer .= "</div>\n";
    $this->buffer .= "</div>\n";
  }

  /** Ajoute un champ date et heure au formulaire
   * Valeur à récupérer dans $_REQUEST[$name] sous forme d'un timestamp unix
   * @param $name    Nom du champ
   * @param $title  Libéllé du champ
   * @param $value  Valeur du champ (timestamp unix, -1 ou null pour aucun)
   * @param $required  Précise si le champ est obligatoire
   */
  function add_datetime_field_old ( $name, $title, $value = -1, $required = false )
  {
    global $wwwtopdir;
    if ( $this->autorefill && $_REQUEST[$name] ) $value = $_REQUEST[$name];
    $this->buffer .= "<div class=\"formrow\">\n";
    $this->_render_name($name,$title,$required);

    $this->buffer .= "<div class=\"formfield\"><input type=\"text\" id=\"$name\" name=\"magicform[datetime][$name]\" value=\"";
    if ( $value != -1 && !is_null($value) )
      $this->buffer .= date("d/m/Y H:i:s",$value);
    $this->buffer .= "\" />";
    $this->buffer .= "<a href=\"javascript:openCalendar('".$wwwtopdir."','".$this->name."','$name','datetime')\">";
    $this->buffer .= "<img src=\"".$wwwtopdir."images/calendar.png\">";
    $this->buffer .= "</a>";
    $this->buffer .= "</div>\n";
    $this->buffer .= "</div>\n";
  }

  /** Ajoute un champ heure au formulaire
   * Valeur à récupérer dans $_REQUEST[$name] sous forme d'un timestamp unix
   * @param $name    Nom du champ
   * @param $title  Libéllé du champ
   * @param $value  Valeur du champ (timestamp unix)
   * @param $required  Précise si le champ est obligatoire
   */
  function add_time_field ( $name, $title, $value = -1, $required = false )
  {
    global $topdir;
    if ( $this->autorefill && $_REQUEST[$name] ) $value = $_REQUEST[$name];
    $this->buffer .= "<div class=\"formrow\">\n";
    $this->_render_name($name,$title,$required);

    $this->buffer .= "<div class=\"formfield\"><input type=\"text\" id=\"$name\" name=\"magicform[time][$name]\" value=\"";
    if ( $value != -1 && !is_null($value) )
      $this->buffer .= date("H:i",$value);
    $this->buffer .= "\" />";
    $this->buffer .= "</div>\n";
    $this->buffer .= "</div>\n";
  }

  /** Ajoute un champ à cocher au formulaire
   * Valeur à récupérer dans $_REQUEST[$name] sous forme d'un booléen
   * @param $name    Nom du champ
   * @param $title  Libéllé du champ
   * @param $checked  Valeur du champ (booléen)
   * @param $disabled  Checkbox non active
   */
  function add_checkbox ( $name, $title, $checked=false,$disabled=false )
  {
    if ( $this->autorefill && $_REQUEST[$name] ) $checked = $_REQUEST[$name];

    $name = fname_protect($name);

    $this->buffer .= "<div class=\"formrow\">\n";
    $this->buffer .= "<div class=\"formlabel\"></div>";
    $this->buffer .= "<div class=\"formfield\"><input type=\"checkbox\" class=\"chkbox\" name=\"magicform[boolean][$name]\" value=\"true\"";
    if ( $checked )
      $this->buffer .= " checked=\"checked\"";
    if ( $disabled )
      $this->buffer .= " disabled=\"disabled\"";
    $this->buffer .= " /> $title</div>\n";
    $this->buffer .= "</div>\n";

  }

  /** Ajoute un champ à cocher au formulaire

   * @param $name    Nom du champ
   * @param $title    Libéllé
   * @param $values  Valeurs possibles (key=>Titre)
   * @param $checked  Nom de la radio box activee par defaut
   * @param $disabled  Nom de la radio box desactivee non active
   * @param $required  Précise si le champ est obligatoire
   * @param $imgs  Tableau associatif des items et des images
   */
  function add_radiobox_field ( $name, $title=false, $values, $value=false , $disabled=false, $required = false, $imgs=array(), $inline=true, $nodiv=false  )
  {
    global $topdir;
    if(empty($values))
      return;
    if(!is_array($values))
      return;

    if ( $this->autorefill && $_REQUEST[$name] ) $value = $_REQUEST[$name];
    if ( !$nodiv )
      $this->buffer .= "<div class=\"formrow\">\n";
    $this->_render_name($name,$title,$required);
    if ( !$nodiv )
      $this->buffer .= "<div class=\"formfield\">";

    $i=1;
    foreach ( $values as $key => $item )
    {
      $this->buffer .= "<input type=\"radio\" name=\"$name\" class=\"radiobox\" value=\"$key\" id=\"__".$name."_".$key."\"";
      if ( $key == $value )
        $this->buffer .= " checked=\"checked\"";
      if ( $key == $disabled )
        $this->buffer .= " disabled=\"disabled\"";
      $this->buffer .= " />&nbsp;";

      if ( isset($imgs[$key]) )
      {
        $this->buffer .= "<img src=\"".$topdir.$imgs[$key]."\" alt=\"".htmlentities($item,ENT_NOQUOTES,"UTF-8")."\" title=\"".htmlentities($item,ENT_NOQUOTES,"UTF-8")."\" ";

        if ( $key != $disabled )
          $this->buffer .= "onclick=\"document.getElementById('__".$name."_".$key."').checked = true;\" ";
        $this->buffer .= "/>";
      }
      else
      {
        if ( $key != $disabled )
          $this->buffer .= "<span onclick=\"document.getElementById('__".$name."_".$key."').checked = true;\">$item</span>";

        else
          $this->buffer .= $item;
      }

      if(!$inline && $i != count($values) )
        $this->buffer .= "<br />\n";

      $i++;
    }
    if ( !$nodiv )
    {
      $this->buffer .= "</div>\n";
      $this->buffer .= "</div>\n";
    }
  }

  /** Ajoute une liste à choix au formulaire
   * Valeur à récupérer dans $_REQUEST[$name] (clé de la valeur selectionée)
   * @param $name    Nom du champ
   * @param $title  Libéllé du champ
   * @param $values  Valeurs possibles array( clé => valeur)
   * @param $value  Valeur selectionnée
   */
  function add_select_field ( $name, $title, $values, $value = false, $prefix="" ,$required = false, $enabled = true, $jscript_onchange = null)
  {
    if (empty($values))
      return;

    if ( $this->autorefill && $_REQUEST[$name] ) $value = $_REQUEST[$name];
    $this->buffer .= "<div class=\"formrow\">";
    $this->_render_name($name,$title,$required);


    $this->buffer .= "<div class=\"formfield\">$prefix";
    $this->buffer .= "<select name=\"$name\" ";

    if ($jscript_onchange != null)
      $this->buffer .= "onchange=\"$jscript_onchange\" ";

    if (!$enabled)
      $this->buffer .= "DISABLED";
    $this->buffer .= ">\n";

    foreach ( $values as $key => $item )
    {
      $this->buffer .= "<option value=\"$key\"";
      if ( $value == $key )
        $this->buffer .= " selected=\"selected\"";
      $this->buffer .= ">".htmlentities($item,ENT_NOQUOTES,"UTF-8")."</option>\n";
    }

    $this->buffer .= "</select></div>\n";
    $this->buffer .= "</div>";

  }


  /**
   * Ajoute une sleection d'entité par liste à choix au formulaire.
   * Exploité par add_entity_smartselect et add_entity_select
   *
   * @param $name    Nom du champ
   * @param $title  Libéllé du champ
   * @param $value Valeurs autorisées (id => Nom, id=0 pour null)
   * @param $std Instance de l'entité : soit vierge, soit la valeur selectionnée
   * @see add_entity_smartselect
   * @see add_entity_select
   */
  private function add_select_list_entity_field ( $name, $title, $values, $std )
  {
    global $wwwtopdir;

    static $uid=0;
    $uid++;

    if (empty($values))
      return;

    $class=get_class($std);

    if ( $this->autorefill && $_REQUEST[$name] ) $value = $_REQUEST[$name];

    $this->buffer .= "<div class=\"formrow\">";
    $this->_render_name($name,$title,$required);
    $this->buffer .= "<div class=\"formfield\">";

    if ( $GLOBALS["entitiescatalog"][$class][2] )
      $this->buffer .= "<img src=\"".$wwwtopdir."images/icons/16/".$GLOBALS["entitiescatalog"][$class][2]."\" class=\"icon\" alt=\"\" /> ";

    $this->buffer .= "<select name=\"$name\" id=\"sd".$uid."_field\"";

    if ( $std->can_describe() )
      $this->buffer .= "onchange=\"openInContents('sd".$uid."_desc', '".$wwwtopdir."gateway.php', 'module=entdesc&class=".$class."&id='+document.getElementById('sd".$uid."_field').value)\"";

    $this->buffer .= ">\n";

    foreach ( $values as $key => $item )
    {
      $this->buffer .= "<option value=\"$key\"";
      if ( $std->id == $key )
        $this->buffer .= " selected=\"selected\"";
      $this->buffer .= ">".htmlentities($item,ENT_NOQUOTES,"UTF-8")."</option>\n";
    }

    $this->buffer .= "</select>";

    if ( $std->can_describe() )
    {
      $this->buffer .= " (<span id=\"sd".$uid."_desc\">";
      if ( $std->is_valid() )
        $this->buffer .= htmlentities($std->get_description(),ENT_NOQUOTES,"UTF-8");
      else
        $this->buffer .= "rien";
      $this->buffer .= "</span>)";
    }

    $this->buffer .= "</div>\n</div>";
  }

  /**
   *
   *
   */
  function add_rights_field ( $basedb, $category=false, $admin=false, $context="sas" )
  {
    global $wwwtopdir;

    $this->add_hidden("magicform[processrights]",true);

    if ( $admin )
      $this->add_entity_select( "rights_id_group_admin", "Propri&eacute;taire", $basedb->db, "group",$basedb->id_groupe_admin);

    $this->add_entity_select( "rights_id_group", "Groupe", $basedb->db, "group",$basedb->id_groupe);

    $this->buffer .= "<div class=\"_right_opts\">";

    $this->add_radiobox_field ( "__rights_lect", "Droits d'acc&egrave;s",
            array( 0x111 => "Lecture par tous"),
            $basedb->droits_acces & 0x111 );
    $this->add_radiobox_field ( "__rights_lect", "",
            array( 0x110 => "Lecture par les membres du groupe s&eacute;lectionn&eacute;"),
            $basedb->droits_acces & 0x111 );

    $this->buffer .= "</div>";

    /* Est-ce vraiment utile ?
    if ( $admin || ($basedb->droits_acces & 0x111==0x010) )
      $this->add_radiobox_field ( "__rights_lect", "",
                array( 0x010 => "Lecture par les membres du groupe s&eacute;lectionn&eacute; uniquement (pas le propri&eacute;taire)"),
                $basedb->droits_acces & 0x111 );
    */

    if ( $context =="wiki") // Droits un peu plus complexes pour le wiki
    {
      $this->buffer .= "<div class=\"_right_opts\">";
      $this->add_radiobox_field ( "__rights_ecrt", "Droits d'écriture",
              array( 0x222 => "Ecriture par tous"),
              $basedb->droits_acces & 0x222 );
      $this->add_radiobox_field ( "__rights_ecrt", "",
              array( 0x220 => "Ecriture par les membres du groupe s&eacute;lectionn&eacute;"),
              $basedb->droits_acces & 0x222 );
      $this->buffer .= "</div>";

      $this->buffer .= "<div class=\"_right_opts\">";
      $this->add_radiobox_field ( "__rights_ajout", "Droits d'ajout",
              array( 0x444 => "Ajout par tous"),
              $basedb->droits_acces & 0x444 );
      $this->add_radiobox_field ( "__rights_ajout", "",
              array( 0x440 => "Ajout par les membres du groupe s&eacute;lectionn&eacute;"),
              $basedb->droits_acces & 0x444 );
      $this->buffer .= "</div>";
    }
    elseif ( $category )
    {
      $add = (($basedb->droits_acces >> 8) & 0xC ) | (($basedb->droits_acces >> 4) & 0xC ) | ($basedb->droits_acces & 0xC );
      if ( $context =="files")
      {
        $this->buffer .= "<div class=\"_right_opts\">";
        $this->add_radiobox_field ( "__rights_ajoutsub", "Droits d'ajout pour le groupe",
                    array( 0x8 => "Ajout de fichiers"),$add );
        $this->add_radiobox_field ( "__rights_ajoutsub", "",
                    array( 0x4 => "Ajout de dossiers"),$add );
        $this->add_radiobox_field ( "__rights_ajoutsub", "",
                    array( 0xC => "Ajout de fichiers et dossiers"),$add );
        $this->buffer .= "</div>";
      }
      else
      {
        if ( $add & 0x4 ) $add = 0x4;
        $this->buffer .= "<div class=\"_right_opts\">";
        $this->add_radiobox_field ( "__rights_ajoutsub", "Droits d'ajout pour le groupe",
                    array( 0x8 => "Ajout de photos"),$add );
        $this->add_radiobox_field ( "__rights_ajoutsub", "",
                    array( 0x4 => "Ajout de cat&eacute;gories"),$add );
        $this->buffer .= "</div>";
      }
    }
    elseif ( $context =="files")
    {
      $this->buffer .= "<div class=\"_right_opts\">";
      $this->add_radiobox_field ( "__rights_ecrt", "Droits d'écriture",
              array( 0x200 => "Ecriture par uniquement le propriétaire"),
              $basedb->droits_acces & 0x200 );
      $this->add_radiobox_field ( "__rights_ecrt", "",
              array( 0x220 => "Ecriture par les membres du groupe s&eacute;lectionn&eacute;"),
              $basedb->droits_acces & 0x222 );
      $this->buffer .= "</div>";

    }

    $this->add_info("<a href=\"".$wwwtopdir."article.php?name=docs:basedb\">Aide sur les droits d'accés</a>");

  }

  /** Ajoute un bouton de validation
   * Valeur à récupérer dans $_REQUEST[$name]
   * @param $name    Nom du champ
   * @param $title  Libéllé du champ
   */
  function add_submit ( $name, $title)
  {
    $this->buffer .= "<div class=\"formrow\">";
    $this->_render_name($name,"",false);
    $this->buffer .= "<div class=\"formfield\">";
    $this->buffer .= "<input type=\"submit\" id=\"$name\" name=\"$name\" value=\"$title\" class=\"isubmit\" />";
    $this->buffer .= "</div></div>\n";
  }
  /** Ajoute un bouton
   * @param $name    Nom du champ
   * @param $title  Libéllé du champ
   * @param $action  Action (javascript) lors de l'appui
   */
  function add_button ($name, $title, $action = "")
  {
    $this->buffer .= "<div class=\"formrow\">";
    $this->_render_name($name,"",false);
    $this->buffer .= "<div class=\"formfield\">";
    $this->buffer .= "<input type=\"button\" id=\"$name\" name=\"$name\" value=\"$title\" onclick=\"$action\" class=\"isubmit\" />";
    $this->buffer .= "</div></div>\n";
  }

  /** Selecteur d'une entité definie dans entitiescatalog
   * @param $name    Nom du champ
   * @param $title  Libéllé du champ
   * @param $db Lien vers la base de donnés
   * @param $entityclass Classe de l'entité
   * @param $value   Valeur selectionnée
   * @param $none Authorise aucune valeur (=0)
   * @param $conds Conditions sur l'entitée (niveau sql)
   * @see add_entity_smartselect à préférer
   */
  function add_entity_select ( $name, $title, $db, $entityclass, $value=false, $none=false, $conds=array(), $order=false)
  {
    global $topdir;

    if ( !class_exists($entityclass)
         && isset($GLOBALS["entitiescatalog"][$entityclass][5])
         && $GLOBALS["entitiescatalog"][$entityclass][5] )
      require_once($topdir."include/entities/".$GLOBALS["entitiescatalog"][$entityclass][5]);

    if (class_exists($entityclass)) // Nouvelle méthode
    {
      $std = new $entityclass($db);
      $values = $std->enumerate ( $none, $conds, $order );
      $std->load_by_id($value);
      $this->add_select_list_entity_field ( $name, $title, $values, $std );
      return;
    }
    elseif ( $entityclass == "group") // déprécié
    {
      $title = $title." (!)";
      $values=enumerates_groups($db);
    }
    else // déprécié
    {
      $title = $title." (!)";

      if ( $none )
        $values=array(0=>"-");
      else
        $values=array();

      $firststatement = true;

      if ( $entityclass == "salle" ) // Cas particulier
        $sql = "SELECT `id_salle`,CONCAT(nom_bat,' / ',nom_salle) FROM sl_salle INNER JOIN sl_batiment ON sl_batiment.id_batiment=sl_salle.id_batiment";

      elseif ( $entityclass == "assocpt") // Autre cas particulier
        $sql = "SELECT `id_assocpt`,`nom_asso` FROM asso INNER JOIN cpt_association ON asso.id_asso=cpt_association.id_assocpt";

      else if ( $GLOBALS["entitiescatalog"][$entityclass][4] )
        $sql = "SELECT `".$GLOBALS["entitiescatalog"][$entityclass][0]."`,`".$GLOBALS["entitiescatalog"][$entityclass][1]."` FROM `".$GLOBALS["entitiescatalog"][$entityclass][4]."`";
      else
        return; // non supporté

      foreach ($conds as $key => $value)
      {
        if( $firststatement )
        {
          $sql .= " WHERE ";
          $firststatement = false;
        }
        else
          $sql .= " AND ";

        if ( is_null($value) )
          $sql .= "(`" . $key . "` is NULL)";
        else
          $sql .= "(`" . $key . "`='" . mysql_escape_string($value) . "')";

      }

      if($order)
        $sql .= " ORDER BY ".$order;
      else
        $sql .= " ORDER BY 2";
      $req = new requete($db,$sql);

      while ( $row = $req->get_row() )
        $values[$row[0]] = $row[1];
    }

    $prefix="";

    if ( $GLOBALS["entitiescatalog"][$entityclass][2] )
      $prefix = "<img src=\"".$topdir."images/icons/16/".$GLOBALS["entitiescatalog"][$entityclass][2]."\" class=\"icon\" alt=\"\" /> ";

    $this->add_select_field ( $name, $title, $values, $value,$prefix );
  }

  function set_focus ( $name )
  {
    $this->buffer .= "<script>document.".$this->name.".".$name.".focus();</script>";
  }

  /** Ajoute une erreur qui ser a affiché avant la formulaire
   * @param $error erreur à afficher
   */
  function error ( $error )
  {
    $this->error_contents = $error;
  }

  /** Authorise le présent formulaire a être utilisé une et une seule foi.
   * La protection anti boulet par excellance, nottament pour éviter que des
   * actions soient reproduites en raison d'un retour en arrière.
   * De plus on s'assure que l'appel en resultant est bien le resultat du formulaire et non
   * un appel venant d'un site extréieur.
   * La variable $GLOBALS["svalid_call"] permet de vérifier que l'appel est valide.
   * L'usage de cette fonction est FORTEMENT recommandé pour les formulaires sensibles
   * (débit/credit carte ae par exemple).
   */
  function allow_only_one_usage()
  {
    $opuid = md5(uniqid(rand(), true)); // Identifiant unique de l'opération
    $_SESSION["forms"][$this->name]["once"][$opuid] = true;
    $_SESSION["forms"][$this->name]["once"]["init"] = true;
    $this->add_hidden("magicform[ticket]",$opuid);
  }

  /** Ajoute du HTML brut **/
  function puts ( $data )
  {
    $this->buffer .= $data;
  }

  /** Ajoute un sous formulaire
   * @param $frm Formualaire
   * @param $check Associé à une checkbox (le "name" du formulaire sera utilisé) (incompatible avec $option)
   * @param $option Associé à une optionbox (le "name" du formulaire sera utilisé)  (incompatible avec $check)
   * @param $checked Si checkbox ou optionbox, etat par défaut
   * @param $value Si optionbox valeur de l'option
   * @param $line Affichage ou non en ligne
   * @param $onoff Sous-formulaire cachable
   * @param $on Si cachable etat par défaut (true=affiché, false=caché)
   */
  function add ( $frm, $check=false, $option=false, $checked=false, $value=false, $line=false, $onoff=false,$on=true )
  {
    global $topdir;

    foreach ( $frm->hiddens  as $k => $v )
    {
      if ( $k != "magicform[name]" )
        $this->hiddens[$k] = $v;
    }

    if(isset($frm->event) && !empty($frm->event)){
      foreach($frm->event as $event=>$action){
        $this->set_event($event, $action);
      }
    }

    if ( $frm->enctype )
      $this->enctype = $frm->enctype;

    $this->buffer .= "<div class=\"formrow\"";

    if ( $frm->name )
      $this->buffer .= " name=\"".$frm->name."_row\" id=\"".$frm->name."_row\"";

    $this->buffer .= ">";

    if ( !$line )
      $this->buffer .= "<div class=\"fullrow\">";
    else
      $this->buffer .= "<div class=\"linedrow\">";
    $name = $frm->name;



    if ( $check )
    {
      $this->buffer .= "<div class=\"subformlabel\">";
      $this->buffer .= "<input type=\"checkbox\" id=\"__$name\" name=\"magicform[boolean][".$name."]\" class=\"chkbox\" value=\"true\"";
      if ( $checked )
        $this->buffer .= " checked=\"checked\"";

      if ( $onoff )
        $this->buffer .= " onclick=\"on_off('".$name."_contents');\"";

      $this->buffer .= " /> ".$frm->title."</div>\n";

      if ( $onoff )
        $on = $checked;

      if ( !$onoff )
        $onclick="document.getElementById('__$name').checked = true; ";
    }
    elseif ( $option )
    {
      $this->buffer .= "<div class=\"subformlabel\">";

      $this->buffer .= "<input type=\"radio\" id=\"__".$name."_".$value."\" name=\"$name\" class=\"radiobox\" value=\"$value\"";
      if ( $onoff )
        $this->buffer .= " onclick=\"on_off_options('$name','$value',".$name."_val); ".$name."_val='$value';\"";

      if ( $checked )
        $this->buffer .= " checked=\"checked\"";

      $this->buffer .= " /> ".$frm->title."</div>\n";

      if ( $onoff )
      {
        $on = $checked;

        if ( $checked )
        {
          $this->work_variables[$name."_val"] = $value;
          $this->buffer .= "<script>".$name."_val='$value';</script>";
        }
        else if ( !isset($this->work_variables[$name."_val"]) )
        {
          $this->work_variables[$name."_val"] = "";
          $this->buffer .= "<script>".$name."_val='';</script>";
        }
      }

      if ( !$onoff )
        $onclick="document.getElementById('__".$name."_".$value."').checked = true;";

      $name .= "_".$value;
    }
    else  if ( $onoff )
    {
      $img = "fld.png";
      if ( !$on )
        $img = "fll.png";

      $this->buffer .= "<div class=\"subformlabel\">";
      $this->buffer .= "<a href=\"#\" onclick=\"on_off_icon('".$name."','".$topdir."'); return false;\"><img src=\"".$topdir."images/".$img."\" alt=\"togle\" class=\"icon\" id=\"".$name."_icon\" /> ".$frm->title."</a>";
      $this->buffer .= "</div>\n";
    }
    else
    {
      $this->buffer .= "<div class=\"subformlabel\">".$frm->title."</div>";
    }

    $class="subform";
    if ( $line )
      $class="subforminline";

    $this->buffer .=  "<div class=\"$class\" id=\"".$name."_contents\"";
    if ( !$on && $onoff )
      $this->buffer .=  " style=\"display: none;\"";
    if ( $onclick )
      $this->buffer .=  " onclick=\"$onclick\"";

    $this->buffer .=  "> <!-- ".$name."_contents -->\n";

    $this->buffer .= $frm->buffer;

    $this->buffer .= "</div><!-- end of ".$name."_contents -->\n";


    $this->buffer .= "</div><!-- end of fullrow/linedrow -->\n";
    $this->buffer .= "</div>\n";
  }

  /**
   * Ajoute un sous formulaire.
   *
   * form::add est trop compliqué, on s'enmèle toujours entre les paramètres,
   * c'est pourquoi cette fonction existe en combinaison des classes subform,
   * subformcheck et subformoption.
   *
   * @param $subfrm Sous formulaire (subform, subformcheck ou subformoption)
   * @param $onoff Cache le sous-forumulaire si non actif
   * @param $line Affiche en formulaire sur une ligne
   */
  function addsub ( subform $subfrm, $onoff=false, $line=false )
  {
    $check = false;
    $option=false;
    $checked=false;
    $value=false;

    $class = get_class($subfrm);


    if ( $class == "subformoption" )
    {
      $option=true;
      $value=$subfrm->value;
      if ( isset($this->_subform_values[$subfrm->name]) )
        $on = $checked = ($this->_subform_values[$subfrm->name]==$value);
      else
        $on = $checked = $subfrm->on;

    }
    else if ( $class == "subformcheck" )
    {
      $check=true;
      if ( isset($this->_subform_values[$subfrm->name]) )
        $on = $checked = $this->_subform_values[$subfrm->name];
      else
        $on = $checked = $subfrm->on;
    }
    else
    {
      if ( isset($this->_subform_values[$subfrm->name]) )
        $on = $this->_subform_values[$subfrm->name];
      else
        $on = $subfrm->on;
    }

    //$_subform_values

    //$subfrm->title .= "($class)";

    $this->add ( $subfrm, $check, $option, $checked, $value, $line, $onoff, $on );
  }

  /**
   * Définit le sous-formulaire selectionné d'un groupe exclusif.
   * Cette fonction est prioritaire sur les valeurs passés aux contructeurs des
   * sous-formulaires.
   * Remarque: doit être appelé avant les addsub
   * @param $name Nom du groupe
   * @param $value Valeur selectionné
   */
  function set_subformoption_value ( $name, $value )
  {
    if ( !isset($this->_subform_values) )
      $this->_subform_values = array();
    $this->_subform_values[$name] = $value;
  }

  /**
   * Définit l'etat d'ouverture / selection d'un sous-formulaire.
   * Cette fonction est prioritaire sur les valeurs passés aux contructeurs des
   * sous-formulaires.
   * Remarque: doit être appelé avant les addsub
   * @param $name Nom du sous-formulaire
   * @param $on Ouvert / Coché
   */
  function set_subform_checked ( $name, $on )
  {
    if ( !isset($this->_subform_values) )
      $this->_subform_values = array();
    $this->_subform_values[$name] = $on;
  }

  function html_render ()
  {
    $html = "";

    if ( $this->error_contents )
      $html .= "<p class=\"formerror\">Erreur : ".$this->error_contents."</p>\n";

    $html .= "<form action=\"$this->action\" method=\"".strtolower($this->method)."\"";
    if ( $this->name )
      $html .= " name=\"".$this->name."\" id=\"".$this->name."\"";
    if ( $this->enctype )
      $html .= " enctype=\"".$this->enctype."\"";
    foreach($this->event as $event=>$action)
      $html .= " ".strtolower($event)."=\"javascript:".$action.";\"";
    $html .= ">\n";
    foreach ( $this->hiddens as $key => $value )
      $html .= "<input type=\"hidden\" name=\"$key\" value=\"$value\" />\n";
    $html .= "<div class=\"form\">\n";
    $html .= $this->buffer;
    $html .= "<div class=\"clearboth\"></div>\n";
    $html .= "</div>\n";
    $html .= "</form>\n";
    return $html;
  }

  /**
   * @protected
   */
  protected function _render_name ( $name, $title, $required )
  {
    if ( !$title )
    {
      $this->buffer .= "<div class=\"formlabel\"></div>";
      return;
    }

    if ( $required && $this->autorefill && $_REQUEST[$name]=="" )
      $this->buffer .= "<div class=\"formlabel missing\">";
    else
      $this->buffer .= "<div class=\"formlabel\">";

    $this->buffer .= $title;
    if ( $required )
      $this->buffer .= " *";
    $this->buffer .= "</div>";
  }


}

/**
 * Sous-formulaire
 * @ingroup display_cts
 * @see form::addsub
 */
class subform extends form
{
  var $on;
  function subform ( $name, $title = false, $on=true )
  {
    $this->on = $on;
    $this->form($name, null, null, null, $title);
  }
}

/**
 * Sous-formulaire selectionnable
 * @ingroup display_cts
 * @see form::addsub
 */
class subformcheck extends subform
{
  function subformcheck ( $name, $title, $checked=true )
  {
    $this->subform($name, $title, $checked);
  }
}

/**
 * Sous-formulaire selectionnable exclusif
 * @ingroup display_cts
 * @see form::addsub
 */
class subformoption extends subformcheck
{
  var $value;
  function subformoption ( $name, $value, $title, $selected=true )
  {
    $this->value=$value;
    $this->subformcheck($name, $title, $selected);
  }
}




/** Conteneur de table
 * @ingroup display_cts
 */
class table extends stdcontents
{
  var $tclass;
  var $tid;

  function table ( $title=false, $class= false, $id=false )
  {
    $this->title = $title;
    $this->tclass = $class;
    $this->tid = $id;
  }

  function set_head ( $heads )
  {

  }

  function add_row ( $row, $class=false, $id=false )
  {

    $this->buffer .= "<tr";
    if ( $class )
      $this->buffer .= " class=\"$class\"";
    if ( $id )
      $this->buffer .= " id=\"$id\"";
    $this->buffer .= ">\n";
    foreach ( $row as $cell )
    {
      if ( !is_array($cell) )
      {
        $this->buffer .= "  <td>".$cell."</td>\n";
      }
      else
      {
        $this->buffer .= "  <td";
        if ( $cell[1] )
        {
          $this->buffer .= " class=\"".$cell[1]."\"";
        }
        if ( $cell[2] )
        {
          $this->buffer .= " id=\"".$cell[2]."\"";
        }

        $this->buffer .= ">".$cell[0]."</td>\n";
      }
    }
    $this->buffer .= "</tr>\n";
  }

  function rows_from_sql ( $res )
  {

  }

  function html_render ()
  {
    $buf = "<table";
    if ( $this->tid ) $buf .= " id=\"$this->tid\"";
    if ( $this->tclass ) $buf .= " class=\"".$this->tclass."\"";
    return $buf.">\n".$this->buffer."</table>\n";
  }

}

/** Conteneur d'images
 * @ingroup display_cts
 */
class image extends stdcontents
{

  var $src;
  var $class;
  var $border;

  /** Consrtuit une image
   * @param $title Titre
   * @param $src URL de l'image
   * @param $class Class CSS
   * @param $border une bordure ou non ?
   */
  function image ( $title, $scr,$class=false,$border=false )
  {
    $this->title  = $title;
    $this->scr    = $scr;
    $this->class  = $class;
    $this->border = $border;
  }

  function html_render ()
  {
    $buf = "<img src=\"".$this->scr."\" alt=\"".$this->title."\"";
    if(!$this->border)
      $buf .= " border=\"0\"";
    if ( $this->class )
      $buf .= " class=\"".$this->class."\"";
    $buf .= " />";
    return $buf;
  }

}

/**
 * Conteneur de boit d'outils
 * @see stdcontents::set_toolbox
 * @ingroup display_cts
 */
class toolbox extends stdcontents
{
  /** Consrtuit une boite d'outils
   * @param $tools Outils (array(link=>nom))
   */
  function toolbox ( $tools )
  {
    foreach( $tools as $link => $title )
    {
      $this->buffer .="<a href=\"".htmlentities($link,ENT_NOQUOTES,"UTF-8")."\">".htmlentities($title,ENT_NOQUOTES,"UTF-8")."</a>";
    }
  }

}

/**
 * Conteneur de listes
 * @ingroup display_cts
 */
class itemlist extends stdcontents
{
  /**
   * Initialise la liste
   * @param $title Titre
   * @param $class Class CSS
   * @param $list Copie les elements de l'array vers la nouvelle liste
   */
  function itemlist ( $title=false,$class=false,$list=array() )
  {
    $this->title = $title;
    $this->class = $class;
    foreach($list as $item)
      $this->add($item);
  }

  /** Ajoute un élément à la liste
   * @param $item HTML brut ou objet itemlist à ajouter
   * @param $class Class CSS
   */
  function add ( $item, $class=false )
  {
    if ( is_object($item) )
      $this->buffer .= "<li".($class?" class=\"".$class."\"":"").">".$item->title."\n".$item->html_render()."</li>\n";
    else
      $this->buffer .= "<li".($class?" class=\"".$class."\"":"").">".$item."</li>\n";
  }

  /** Ajoute du HTML brut **/
  function puts ( $data )
  {
    $this->buffer .= $data;
  }

  function html_render()
  {
    return "<ul".($this->class?" class=\"".$this->class."\"":"").">\n".$this->buffer."</ul>\n";
  }
}

/**
 * Affiche une liste d'onglets.
 * @author Julien Etelain
 * @ingroup display_cts
 */
class tabshead extends stdcontents
{

  var $entries;
  var $sel;
  var $tclass;

  function tabshead($entries, $sel,$class="",$tclass="tabs")
  {
    $this->entries = $entries;
    $this->sel = $sel;
    $this->tclass = $tclass.$class;
  }

  function html_render()
  {
    global $wwwtopdir;

    $this->buffer .= "<div class=\"".$this->tclass."\">\n";

    foreach ($this->entries as $entry)
    {
      $this->buffer .= "<span";
      if ($this->sel == $entry[0])
        $this->buffer .= " class=\"selected\"";
      $this->buffer .= "><a href=\"" . htmlentities($wwwtopdir . $entry[1],ENT_NOQUOTES,"UTF-8") . "\"";
      if ($this->sel == $entry[0])
        $this->buffer .= " class=\"selected\"";
      $this->buffer .= " title=\"" .  htmlentities($entry[2],ENT_QUOTES,"UTF-8") . "\">" . $entry[2] . "</a></span>\n";
    }
    $this->buffer .= "<div class=\"clearboth\"></div>\n";
    $this->buffer .= "</div>\n";

    return $this->buffer;
  }
}


/**
 * @defgroup display_cts_formsupport Support magicform
 * Fonctions pour le traitement des valeurs des formulaires.
 * Le code de prise en charge des formulaires se trouve à la fin de standart.inc.php
 * @ingroup display_cts
 * @{
 */

/**
 * Transforme un nom de variable REQUEST pour pouvoir être utilisé comme clé ou
 * valeur d'un tableau passé en REQUEST
 * Utilisé pour les "magic forms"
 * @param $fname Nom de variable (nom,nom[1][2]...)
 * @return le nom transformé
 * @see set_request_fname_unprotect
 */
function fname_protect ( $fname )
{
  return ereg_replace("\[([^]]*)\]","|\\1",$fname);
}

/**
 * Définit une variable REQUEST à partir de son nom transformé par fname_protect
 * Utilisé pour les "magic forms"
 * @param $fname Nom de variable tranformé par fname_protect
 * @param $value Valeur à définir
 * @see fname_protect
 */
function set_request_fname_unprotect ( $fname, $value )
{
  if ( strchr($fname,"|") )
  {
    $parts = explode("|",$fname);
    $var = &$_REQUEST;
    foreach ( $parts as $part )
      $var = &$var[$part];
    $var = $value;
  }
  $_REQUEST[$fname]=$value;
}

/**
 * Converti une valeur monétaire saisie en format interne (centimes)
 * Ne supporte pas les séparateurs décimaux autres que les espsaces.
 * Supporte . et ,
 * @param $prix Valeur saisie par l'utilisateur
 * @return la valeur correspondante en format interne (centimes)
 */
function get_prix ( $prix  )
{
  $prix = str_replace(",",".",$prix);
  $prix = str_replace(" ","",$prix);
  return $prix*100;
}

/**
 * Converti une date saisie en timestamp.
 * Formats supportés :
 * - AAAA:MM:JJ HH:MM:SS
 * - AAAA-MM-JJ HH:MM:SS
 * - JJ:MM:AAAA HH:MM:SS
 * - JJ-MM-AAAA HH:MM:SS
 * - JJ/MM/AAAA HH:MM:SS
 * - JJ-MM-AAAA HH:MM
 * - JJ/MM/AAAA HH:MM
 * - JJ-MM-AAAA
 * - JJ/MM/AAAA
 *
 * @param $datetime date saisie par un utilisateur
 * @return le timestamp correspondant, null si le format n'est reconnu
 * @todo vérifier que toutes les pages utilisant les forms supportent bien
 * le renvoie de null en cas d'erreur
 */
function datetime_to_timestamp ( $datetime ) {

  // AAAA:MM:JJ HH:MM:SS
  if ( ereg("([0-9]{4}):([0-9]{1,2}):([0-9]{1,2}) ([0-9]{1,2}):([0-9]{1,2}):([0-9]{1,2})",$datetime,$reg) )
    return mktime ( $reg[4], $reg[5], $reg[6] , $reg[2] , $reg[3], $reg[1]);

  // AAAA-MM-JJ HH:MM:SS
  else if ( ereg("([0-9]{4})-([0-9]{1,2})-([0-9]{1,2}) ([0-9]{1,2}):([0-9]{1,2}):([0-9]{1,2})",$datetime,$reg) )
    return mktime ( $reg[4], $reg[5], $reg[6] , $reg[2] , $reg[3], $reg[1]);

  // JJ:MM:AAAA HH:MM:SS
  else if ( ereg("([0-9]{1,2}):([0-9]{1,2}):([0-9]{4}) ([0-9]{1,2}):([0-9]{1,2}):([0-9]{1,2})",$datetime,$reg) )
    return mktime ( $reg[4], $reg[5], $reg[6] , $reg[2] , $reg[1], $reg[3]);

  // JJ-MM-AAAA HH:MM:SS
  else if ( ereg("([0-9]{1,2})-([0-9]{1,2})-([0-9]{4}) ([0-9]{1,2}):([0-9]{1,2}):([0-9]{1,2})",$datetime,$reg) )
    return mktime ( $reg[4], $reg[5], $reg[6] , $reg[2] , $reg[1], $reg[3]);

  // JJ/MM/AAAA HH:MM:SS
  else if ( ereg("([0-9]{1,2})/([0-9]{1,2})/([0-9]{4}) ([0-9]{1,2}):([0-9]{1,2}):([0-9]{1,2})",$datetime,$reg) )
    return mktime ( $reg[4], $reg[5], $reg[6] , $reg[2] , $reg[1], $reg[3]);

  // JJ-MM-AAAA HH:MM
  else if ( ereg("([0-9]{1,2})-([0-9]{1,2})-([0-9]{4}) ([0-9]{1,2}):([0-9]{1,2})",$datetime,$reg) )
    return mktime ( $reg[4], $reg[5], 0, $reg[2] , $reg[1], $reg[3]);

  // JJ/MM/AAAA HH:MM
  else if ( ereg("([0-9]{1,2})/([0-9]{1,2})/([0-9]{4}) ([0-9]{1,2}):([0-9]{1,2})",$datetime,$reg) )
    return mktime ( $reg[4], $reg[5], 0, $reg[2] , $reg[1], $reg[3]);

  // JJ-MM-AAAA
  else if ( ereg("([0-9]{1,2})-([0-9]{1,2})-([0-9]{4})",$datetime,$reg) )
    return mktime ( 0, 0, 0, $reg[2] , $reg[1], $reg[3]);

  // JJ/MM/AAAA
  else if ( ereg("([0-9]{1,2})/([0-9]{1,2})/([0-9]{4})",$datetime,$reg) )
    return mktime ( 0, 0, 0, $reg[2] , $reg[1], $reg[3]);

  return null;
}

/**
 * Converti une heure saisie en timestamp.
 * Format supporté : HH:MM
 * @param $time Heure saisie par un utilisateur
 * @return le timestamp correspondant (secondes depuis 00:00),
 * null si le format n'est reconnu
 * @todo vérifier que toutes les pages utilisant les forms supportent bien
 * le renvoie de null en cas d'erreur
 */
function time_to_timestamp ($time)
{
  if ( ereg("([0-9]{1,2}):([0-9]{1,2})",$time,$reg) )
    return mktime ( $reg[2], $reg[1], 0, 0, 0, 0);

  return null;
}

/**@}*/

/**
 * @ingroup display
 */
function textual_plage_horraire ( $debut, $fin )
{
  if ( $fin-$debut < 120 )
    return strftime("%A %d %B %G %H:%M",$fin);

  if( date("d/m/Y",$debut) != date("d/m/Y",$fin) )
    return strftime("%A %d %B %G %H:%M",$debut) . " jusqu'au ".strftime("%A %d %B %G %H:%M",$fin);
  else
    return strftime("%A %d %B %G %H:%M",$debut) . " jusqu'&agrave; ".date("H:i",$fin);
}

/*
  "magic" forms system handler
  Used to automaticly convert human data to usual types,
  also check if required fields are not missing.
*/
if ( isset($_REQUEST["magicform"]) )
{
  $name = $_REQUEST["magicform"]["name"];

  if ( isset($_SESSION["forms"][$name]["once"]) ) // formulaire à usage "UNIQUE", verifions le ticket
  {
    $ticket = $_REQUEST["magicform"]["ticket"];
    if ( $_SESSION["forms"][$name]["once"][$ticket] )
    {
      // Tout va bien, le ticket est "libre"
      unset($_SESSION["forms"][$name]["once"][$ticket]); // on supprime le ticket
      $GLOBALS["svalid_call"] = true;
    }
    else if ( !isset($_SESSION["forms"][$name]["once"]["init"]) )
    // la session a du expirée, évitons de facher l'utilisateur
      $GLOBALS["svalid_call"] = true;
    else
      $GLOBALS["svalid_call"] = false; // ticket non valide ou deja utilisé
  }
  else
    $GLOBALS["svalid_call"] = false; // Ce n'est pas un formulaire à usage unique

  if ( $_REQUEST["magicform"]["date"] )
  {
    foreach ( $_REQUEST["magicform"]["date"] as $name => $value )
      set_request_fname_unprotect($name,datetime_to_timestamp($value));
  }

  if ( $_REQUEST["magicform"]["datetime"] )
  {
    foreach ( $_REQUEST["magicform"]["datetime"] as $name => $value )
      set_request_fname_unprotect($name,datetime_to_timestamp($value));
  }

  if ( $_REQUEST["magicform"]["time"] )
  {
    foreach ( $_REQUEST["magicform"]["time"] as $name => $value )
      set_request_fname_unprotect($name,time_to_timestamp($value));
  }

  if ( $_REQUEST["magicform"]["price"] )
  {
    foreach ( $_REQUEST["magicform"]["price"] as $name => $value )
      set_request_fname_unprotect($name,get_prix($value));
  }

  if ( $_REQUEST["magicform"]["boolean"] )
  {
    foreach ($_REQUEST["magicform"]["boolean"] as $name => $value)
      set_request_fname_unprotect($name, ($value == "true"));
  }
  if ($_REQUEST["magicform"]["files"])
  {
    require_once($topdir."include/entities/files.inc.php");
    $db = new mysqlae(); // $site n'est pas encore dispo...
    foreach ( $_REQUEST["magicform"]["files"] as $name => $value )
    {
      $files = array();
      if ( !empty($value) )
      {
        $ids = explode(",",$value);
        foreach( $ids as $id )
        {
          $file = new dfile($db);
          if ( $file->load_by_id($id) )
            $files[] = $file;
        }
      }
      set_request_fname_unprotect($name,$files);
    }
    unset($db);
  }

  if ( $_REQUEST["magicform"]["processrights"] )
  {
    if ( isset($_REQUEST["__rights_ecrt"]) && isset($_REQUEST["__rights_ajout"]) )
      $_REQUEST["rights"] = 0x200 | intval($_REQUEST["__rights_lect"]) | intval($_REQUEST["__rights_ecrt"]) | intval($_REQUEST["__rights_ajout"]);
    elseif ( isset($_REQUEST["__rights_ecrt"]) )
      $_REQUEST["rights"] = intval($_REQUEST["__rights_lect"]) | intval($_REQUEST["__rights_ecrt"]);
    else
      $_REQUEST["rights"] = 0x200 | intval($_REQUEST["__rights_lect"]) |
              (intval($_REQUEST["__rights_lect"])*intval($_REQUEST["__rights_ajoutsub"]));

  }

  if ( isset($_REQUEST["magicform"]["geo"]) && count($_REQUEST["magicform"]["geo"]) > 0  )
  {
    foreach ( $_REQUEST["magicform"]["geo"] as $name => $value )
    {
      $conv = geo_degrees_to_radians($value);
      if ( is_null($conv) )
        set_request_fname_unprotect($name,$value);
      else
        set_request_fname_unprotect($name,$conv);
    }
  }

}



?>
