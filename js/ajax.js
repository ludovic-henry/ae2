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

/**
 * @file
 * Fonctions pour requêtes asynchrones au serveur.
 * Base des techniques "AJAX".
 *
 * **RAPPEL** Ce fichier est sous licence GNU GPL. Vous pouvez le ré-utiliser
 * sur votre site internet, mais il doit rester sous licence GNU GPL même si
 * vous le modifiez. Si vous ré-utilisez des sources de gateway.php, ces sources
 * étant sous la même licence, elles devront aussi rester sous GNU GPL.
 * Pour plus d'information : http://www.gnu.org/
 *
 * @author Julien Etelain
 */

/**
 * @defgroup js Fonctions javascript utilitaires
 */

/**
 * @defgroup js_ajax Fonctions javascript "AJAX"
 * Fonctions pour requêtes asynchrones au serveur.
 * Base des techniques "AJAX".
 * @ingroup js
 */

/**
 * Charge des données HTML depuis le serveur dans un element de la page de
 * manière asynchrone.
 * @param name id de l'element de la page dont le contenu sera remplacé par
 *             le résultat de la requête
 * @param page URL à la quelle la requête sera envoyée
 * @param data paramètres à passer en GET ("param1=value1&param2=value2")
 * @return true si la requête a été bien envoyée, false sinon
 * @ingroup js_ajax
 */
function openInContents( name, page, data)
{
  if (window.ActiveXObject)
    var XhrObj = new ActiveXObject("Microsoft.XMLHTTP") ;
  else
    var XhrObj = new XMLHttpRequest();

  if ( !XhrObj ) return false;

  var content = document.getElementById(name);

  XhrObj.open("GET", page+"?"+data);

  XhrObj.onreadystatechange = function()
  {
    if (XhrObj.readyState == 4 && XhrObj.status == 200)
      content.innerHTML = XhrObj.responseText ;
  }

  XhrObj.send(null);

  return true;
}

/**
 * Execute un script renvoyé par une requête au serveur de manière asynchrone.
 * @param page URL à la quelle la requête sera envoyée
 * @param data paramètres à passer en GET ("param1=value1&param2=value2")
 * @return true si la requête a été bien envoyée, false sinon
 * @ingroup js_ajax
 */
function evalCommand( page, data )
{

  if (window.ActiveXObject)
    var XhrObj = new ActiveXObject("Microsoft.XMLHTTP") ;
  else
    var XhrObj = new XMLHttpRequest();

  if ( !XhrObj ) return false;

  XhrObj.open("GET", page+"?"+data);

  XhrObj.onreadystatechange = function()
  {
    if (XhrObj.readyState == 4 && XhrObj.status == 200)
    {
      eval(XhrObj.responseText);
    }
  }

  XhrObj.send(null);

  return true;
}

function evalCallback (page, data, callback)
{
  if (window.ActiveXObject)
    var XhrObj = new ActiveXObject("Microsoft.XMLHTTP") ;
  else
    var XhrObj = new XMLHttpRequest();

  if ( !XhrObj ) return false;

  XhrObj.open("GET", page+"?"+data);

  XhrObj.onreadystatechange = function()
  {
    if (XhrObj.readyState == 4 && XhrObj.status == 200)
    {
      callback(XhrObj.responseText);
    }
  }

  XhrObj.send(null);

  return true;
}

/**
 * Définit un élément dans la variable $_SESSION["usersession"] du coté du
 * serveur.
 * $_SESSION["usersession"] est mémorisé si l'utilisateur est connecté.
 * @param topdir Chemin vers la racine du site (pour trouver le script gateway.php)
 * @param key Clé de l'élément qui sera affecté ($_SESSION["usersession"][key])
 * @param value Valeur à définir
 * @ingroup js_ajax
 */
function usersession_set ( topdir, key, value )
{
  //evalCommand ( topdir + "gateway.php", "module=usersession&set=" + escape(key) + "&value=" + escape(value) );
  evalCommand ( "/gateway.php", "module=usersession&set=" + escape(key) + "&value=" + escape(value) );
}

/**
 * @defgroup display_cts_js Support Javascript.
 *
 * L'ensemble des traitements coté serveurs de ces fonctions est réalisé par
 * gateway.php
 *
 * Pour la selection d'entités, elles doivent être correctement définies dans
 * include/catalog.inc.php : nottament le nom du fichier contenant la déclaration
 * de la classe.
 *
 * @ingroup display_cts
 * @see gateway.php
 * @see include/catalog.inc.php
 */

var fsearch_display_query='';
var fsearch_sequence=0;
var fsearch_actual_sequence=0;
var fsearch_timeout_id = null;
var fobj = null;
var fsearchres = null;

function fsearch_keyup(event)
{
  if ( event != null )
  {
    if ( event.ctrlKey || event.keyCode == 13 ) return false;
    if ( event.keyCode == 27 ) // ESC
    {
      fsearch_stop();
      return false;
    }
  }

  if (fsearch_timeout_id != null) {
      window.clearTimeout (fsearch_timeout_id);
      fsearch_timeout_id = null;
  }

  if (fobj == null)
    fobj = document.getElementById('fsearchpattern');

  if ( !fobj ) return false;

  var length = fobj.value.length;
  if (length == 0) {
      fsearch_stop();
      return false;
  }

  fsearch_query ();

  return true;
}

function fsearch_query ()
{
    var seqid = fsearch_sequence = fsearch_sequence + 1;
    var pattern = fobj.value;
    evalCallback (site_topdir + "gateway.php",
                  "module=fsearch&fsearch_sequence="+seqid+"&topdir="+site_topdir+"&pattern="+pattern,
                  function (result) {
                      if (result == null || result == '') {
                          fsearch_stop ();
                          return;
                      }
                      if (seqid != fsearch_sequence || pattern != fobj.value)
                          return;
                      if (fsearchres == null)
                          fsearchres = document.getElementById('fsearchres');

                      fsearchres.style.zIndex = 100000;
                      fsearchres.style.display = 'block';
                      fsearchres.innerHTML = result;
                      fsearch_display_query= pattern;
                  });
}

function fsearch_stop ( )
{
  if (fsearchres == null)
    fsearchres = document.getElementById('fsearchres');

  fsearchres.style.display = 'none';
  fsearch_display_query='';
}

function fsearch_stop_delayed( field ) {
    window.setTimeout(fsearch_stop, 500);
}


/**
 * Champ de selection d'utilisateur par recherche : Ouvre / Ferme la recherche
 *
 * @ingroup display_cts_js
 * @deprecated fsfield gère mieu les saisies claviers
 */
function userselect_toggle(ref)
{
  var obj1 = document.getElementById(ref+"_fieldbox");
  var obj2 = document.getElementById(ref+"_static");
  var obj3 = document.getElementById(ref+"_currentuser");
  var obj4 = document.getElementById(ref+"_result");
  var obj5 = document.getElementById(ref+"_button");
  var obj6 = document.getElementById(ref+"_field");

  if ( obj1.style.display == 'none' ) {
    obj1.style.display = 'block';
    obj2.style.display = 'none';
    obj3.style.display = 'block';
    obj4.style.display = 'block';
    obj5.innerHTML="fermer";
    obj6.value="";
    obj6.focus();
  } else {
    obj1.style.display = 'none';
    obj2.style.display = 'block';
    obj3.style.display = 'none';
    obj4.style.display = 'none';
    obj5.innerHTML="changer";
  }
}

/**
 * Champ de selection d'utilisateur par recherche : Selection de l'utilisateur
 *
 * @ingroup display_cts_js
 * @deprecated fsfield gère mieu les saisies claviers
 */
function userselect_set_user(topdir, ref,id,nom)
{
  var obj1 = document.getElementById(ref+"_fieldbox");
  var obj2 = document.getElementById(ref+"_static");
  var obj3 = document.getElementById(ref+"_currentuser");
  var obj4 = document.getElementById(ref+"_result");
  var obj5 = document.getElementById(ref+"_id");
  var obj6 = document.getElementById(ref+"_button");

  obj1.style.display = 'none';
  obj2.style.display = 'block';
  obj3.style.display = 'none';
  obj4.style.display = 'none';

  obj6.innerHTML="changer";

  obj5.value=id;
  obj2.innerHTML="<img src=\""+topdir+"images/icons/16/user.png\" class=\"icon\" alt=\"\" /> "+nom;
  obj4.innerHTML="";
  openInContents( ref + "_currentuser", topdir + "gateway.php", "module=userinfo&targettopdir=" + topdir + "&id_utilisateur=" + id );

}

/**
 * Champ de selection d'utilisateur par recherche : Lancement de la recherche
 * lors de la saisie dans le champ de recherche
 *
 * @ingroup display_cts_js
 * @deprecated fsfield gère mieu les saisies claviers
 */
var userselect_sequence=0;
var userselect_actual_sequence=0;

function userselect_keyup(event,ref,topdir)
{
  if ( event != null )
    if ( event.ctrlKey || event.keyCode == 27 || event.keyCode == 13  )
      return false;

  var obj = document.getElementById(ref+'_field');

  if ( !obj ) return false;

  userselect_sequence=userselect_sequence+1;

  evalCommand( topdir + "gateway.php", "module=userfield&userselect_sequence="+userselect_sequence+"&pattern="+obj.value+"&ref="+ref );

  return true;
}

/*
 * Entities : Fast Search Field (fsfield)
 * @see gateway.php
 */
var fsfield_current_sequence = new Array();
var fsfield_sequence = new Array();
/**
 * Champ de selection par recherche : Initialisation d'un champ
 * @ingroup display_cts_js
 */
function fsfield_init ( topdir, field )
{
  fsfield_current_sequence[field]=0;
  fsfield_sequence[field]=0;
}

/**
 * Champ de selection par recherche : Ouvre / Ferme la recherche
 * @ingroup display_cts_js
 */
function fsfield_toggle ( topdir, field )
{
  var obj1 = document.getElementById(field+"_fieldbox");
  var obj2 = document.getElementById(field+"_static");
  var obj4 = document.getElementById(field+"_result");
  var obj5 = document.getElementById(field+"_button");
  var obj6 = document.getElementById(field+"_field");

  if ( obj1.style.display == 'none' )
  {
    obj1.style.display = 'block';
    obj2.style.display = 'none';
    obj4.style.display = 'block';
    obj5.innerHTML="annuler";
    obj6.value="";
    obj6.focus();
  }
  else
  {
    obj1.style.display = 'none';
    obj2.style.display = 'block';
    obj4.style.display = 'none';
    obj5.innerHTML="changer";
  }
}

/**
 * Champ de selection par recherche : Selection de l'élément
 * @ingroup display_cts_js
 */
function fsfield_sel ( topdir, field, id, title, iconfile )
{
  // Bloque les reponses pas encore données par le serveur (évite que la boite re-aparaisse après avoir choisi l'élément)
  fsfield_current_sequence[field] = fsfield_sequence[field];

  var obj1 = document.getElementById(field+"_fieldbox");
  var obj2 = document.getElementById(field+"_static");
  var obj4 = document.getElementById(field+"_result");
  var obj5 = document.getElementById(field+"_id");
  var obj6 = document.getElementById(field+"_button");

  obj1.style.display = 'none';
  obj2.style.display = 'block';
  obj4.style.display = 'none';
  obj6.innerHTML="changer";

  obj5.value=id;
  obj2.innerHTML="<img src=\""+topdir+"images/icons/16/"+iconfile+"\" class=\"icon\" alt=\"\" /> "+title;
  obj4.innerHTML="";
}

/**
 * Champ de selection par recherche : Lancement de la recherche lors de la
 * saisie dans le champ de recherche.
 *
 * @ingroup display_cts_js
 */
function fsfield_keyup ( event, topdir, field, myclass, constraints )
{
  if ( event != null )
    if ( event.ctrlKey || event.keyCode == 27 || event.keyCode == 13  )
      return false;

  var obj = document.getElementById(field + '_field');

  if ( !obj ) return false;

  fsfield_sequence[field] = fsfield_sequence[field]+1;
  if(typeof(constraints) == 'object')
  {
    var append='';
    for ( var sqlfield in constraints )
    {
      var obj2 = document.getElementById(constraints[sqlfield]).value;
      if(obj2 && obj2!='')
        append=append+'&conds['+sqlfield+']='+obj2;
    }
    evalCommand( topdir + "gateway.php",
      "module=fsfield"+
      "&topdir="+topdir+
      "&pattern="+obj.value+
      "&field="+field+
      "&class="+myclass+
      "&sequence="+fsfield_sequence[field]+
      append);
  }
  else
  {
    evalCommand( topdir + "gateway.php",
      "module=fsfield"+
      "&topdir="+topdir+
      "&pattern="+obj.value+
      "&field="+field+
      "&class="+myclass+
      "&sequence="+fsfield_sequence[field] );
  }
  return true;
}

/*
 * Entities : Tooltip
 * @see gateway.php
 */

var tooltip_active='';
var tooltip_element = document.createElement("div");

tooltip_element.setAttribute('id','systooltip');
tooltip_element.style.position="absolute";

/**
 * Determine la position absolue (x,y) d'un élément par rapport au document (racine)
 * @param obj Objet dont on recherche la position absolue
 * @return un tableau [x,y] contenant la position absolue
 * @ingroup js
 */
function findPos(obj)
{
  var curleft = curtop = 0;
  if (obj.offsetParent)
  {
    curleft = obj.offsetLeft
    curtop = obj.offsetTop
    while (obj = obj.offsetParent)
    {
      curleft += obj.offsetLeft
      curtop += obj.offsetTop
    }
  }
  return [curleft,curtop];
}

/**
 * Bulle d'information : Affiche une bulle d'information dans un délai de 1 seconde
 * @ingroup display_cts_js
 */
function show_tooltip ( ref, topdir, myclass, id )
{
  document.body.appendChild(tooltip_element);


  tooltip_active = ref;
  setTimeout("go_tooltip('" + ref + "','" + topdir + "','" + myclass + "','" + id + "')", 1000);
}

/**
 * Bulle d'information : Affiche une bulle d'information (asynchrone)
 * @ingroup display_cts_js
 */
function go_tooltip ( ref, topdir, myclass, id )
{
  if ( tooltip_active != ref )
    return;

  if (window.ActiveXObject)
    var XhrObj = new ActiveXObject("Microsoft.XMLHTTP") ;
  else
    var XhrObj = new XMLHttpRequest();

  if ( !XhrObj ) return false;

  XhrObj.open("GET", topdir+"gateway.php?module=entinfo&topdir="+topdir+"&class="+myclass+"&id="+id);

  XhrObj.onreadystatechange = function()
  {
    if (XhrObj.readyState == 4 && XhrObj.status == 200)
    {
      if ( tooltip_active == ref )
      {
        var elem = document.getElementById(ref);
        var pos = findPos(elem);
        tooltip_element.innerHTML = XhrObj.responseText ;
        tooltip_element.style.left=pos[0];
        tooltip_element.style.top=pos[1]+elem.offsetHeight+13;
        tooltip_element.style.display = 'block';
      }
    }
  }

  XhrObj.send(null);
  return true;
}

/**
 * Bulle d'information : Cache un bulle ou annule son affichage
 * @ingroup display_cts_js
 */
function hide_tooltip ( ref )
{
  tooltip_active='';
  tooltip_element.style.display = 'none';
}

/*
 * Entities : Explorer Field
 *
 */

/**
 * Champ de selection par exploration  : Ouvre / Ferme l'arbre de selection
 * @ingroup display_cts_js
 */
function exfield_toggle ( topdir, field, myclass )
{
  var obj2 = document.getElementById(field+"_static");
  var obj4 = document.getElementById(field+"_result");
  var obj5 = document.getElementById(field+"_button");
  var obj7 = document.getElementById(field+"_"+myclass+"_root");

  if ( obj4.style.display == 'none' )
  {
    obj2.style.display = 'none';
    obj4.style.display = 'block';
    obj5.innerHTML="annuler";
    obj7.innerHTML="";

    exfield_explore ( topdir, field, myclass, myclass, 'root' );
  }
  else
  {
    obj2.style.display = 'block';
    obj4.style.display = 'none';
    obj5.innerHTML="changer";
    obj7.innerHTML="";
  }
}

/**
 * Champ de selection par exploration : Explore / ferme un noeud de l'arber
 * @ingroup display_cts_js
 */
function exfield_explore ( topdir, field, myclass, eclass, eid )
{
  var obj = document.getElementById(field+"_"+eclass+"_"+eid);

  if ( obj.innerHTML != "" )
    obj.innerHTML = "";
  else
    openInContents( field+"_"+eclass+"_"+eid, topdir+"gateway.php", "module=exfield&topdir="+topdir+"&field="+field+"&class="+myclass+"&eclass="+eclass+"&eid="+eid);
}

/**
 * Champ de selection par exploration : Selection d'une valeur
 * @ingroup display_cts_js
 */
function exfield_select ( topdir, field, myclass, id, title, iconfile )
{

  var obj2 = document.getElementById(field+"_static");
  var obj4 = document.getElementById(field+"_result");
  var obj5 = document.getElementById(field+"_id");
  var obj6 = document.getElementById(field+"_button");
  var obj7 = document.getElementById(field+"_"+myclass+"_root");

  obj2.style.display = 'block';
  obj4.style.display = 'none';
  obj6.innerHTML="changer";

  obj5.value=id;
  obj2.innerHTML="<img src=\""+topdir+"images/icons/16/"+iconfile+"\" class=\"icon\" alt=\"\" /> "+title;
  obj7.innerHTML="";
}

