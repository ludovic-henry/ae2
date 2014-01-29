/* Copyright 2004-2006
 * - Maxime Pettazoni
 * - Pierre Mauduit
 * - Laurent Colnat
 * - Julien Etelain < julien at pmad dot net >
 * - Manuel Vonthron < manuel DOT vonthron AT acadis DOT org >
 *
 * Ce fichier fait partie du site de l'Association des Ãtudiants de
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
 * Sets/unsets the pointer and marker in browse mode
 *
 * @param   string    the class name
 * @param  integer    the current number of row
 * @param   string    the action calling this script (over, out or click)
 *
 * @return  boolean  whether pointer is set or not
 */
function setPointer(theClass, currentNum, theAction, basename, the_form)
{
  var color;
  var obj = document.getElementById('ln['+currentNum+']');

  /* traitement out */
  if (theClass == 'ln0' && theAction == 'out')
    color = '#eff7ff';
  else if (theAction == 'out' && theClass == 'ln1')
    color = 'white';
  else if (theAction == 'out' && theClass == 'hilight')
    color = '#ffdd77';
  else if (theAction == 'out')
    color = null;
  else if (theAction == 'click' && document.forms[the_form].elements[basename + currentNum + ']'].checked == true)
  {
    var do_check = document.forms[the_form].elements[basename + currentNum + ']'].checked;
    document.forms[the_form].elements[basename + currentNum + ']'].checked = !do_check;
    if (theClass == 'ln1')
      color = 'white';
    else if (theClass == 'ln0')
      color = '#eff7ff';
  }
  else if (theAction == 'click' && document.forms[the_form].elements[basename + currentNum + ']'].checked == false)
  {
    var do_check = document.forms[the_form].elements[basename + currentNum + ']'].checked;
    document.forms[the_form].elements[basename + currentNum + ']'].checked = !do_check;
    color = '#DFEAF2';
  }
  else if (theAction == 'over')
    color = '#ffcc2a';

    var currentColor = document.getElementById('ln['+currentNum+']').style.backgroundColor;

    if (currentColor.indexOf("rgb") >= 0)
    {
        var rgbStr = currentColor.slice(currentColor.indexOf('(') + 1,
                                     currentColor.indexOf(')'));
        var rgbValues = rgbStr.split(",");
        currentColor = "#";
        var hexChars = "0123456789ABCDEF";
        for (var i = 0; i < 3; i++)
        {
            var v = rgbValues[i].valueOf();
            currentColor += hexChars.charAt(v/16) + hexChars.charAt(v%16);
        }
    }

  if (currentColor != "#DFEAF2")
  {
    obj.style.background = color;
  }
  else if (currentColor == "#DFEAF2" && theAction == 'click')
  {
    obj.style.background = color;
  }

    return true;
} // end of the 'setPointer()' function


/**
 * Checks/unchecks all rows
 *
 * @param   string   the form name
 * @param   string   basename of the element
 * @param   integer  min element count
 * @param   integer  max element count
 *
 * @return  boolean  always true
 */
function setCheckboxesRange(the_form, basename, min, max)
{
  do_check = document.forms[the_form].elements[the_form + '_all'].checked;
    for (var i = min; i < max; i++) {
            document.forms[the_form].elements[basename + i + ']'].checked = do_check;
    }
  return true;

} // end of the 'setCheckboxesRange()' function

function switchSelConnection(obj)
{
    var sel = obj.options[obj.selectedIndex].innerHTML;
    var txt = "identifiant";

    if (sel == "Alias")
      txt = "alias";
    else if (sel == "E-mail" || sel == "UTBM / Assidu")
      txt = "prenom.nom";

  obj.parentNode.parentNode.parentNode.childNodes[2].childNodes[2].childNodes[0].value = txt;
}


function fileListToggle (id)
{
  if (id.style.display == 'none')
    id.style.display = 'block';
  else
    id.style.display = 'none';
}

function tab(curr,dest)
{
    if ((curr.getAttribute) && (curr.value.length == curr.getAttribute("maxlength")))
  {
      dest.focus();
  }
}

function on_off (id)
{
  var obj = document.getElementById(id);

  if ( obj.style.display == 'none' ) {
    obj.style.display = 'block';

  } else {
    obj.style.display = 'none';

  }
}

function on_off_icon (id,topdir)
{
  var obj = document.getElementById(id + '_contents');

  var img = document.getElementById(id + '_icon');

  if ( obj.style.display == 'none' ) {
    obj.style.display = 'block';
    img.src = topdir + 'images/fld.png';
  } else {
    obj.style.display = 'none';
    img.src = topdir + 'images/fll.png';
  }
}


function on_off_icon_store (id,topdir,key)
{
  var obj = document.getElementById(id + '_contents');
  var img = document.getElementById(id + '_icon');

  if ( obj.style.display == 'none' ) {
    obj.style.display = 'block';
    img.src = topdir + 'images/fld.png';
    usersession_set(topdir,key,1);
  } else {
    obj.style.display = 'none';
    img.src = topdir + 'images/fll.png';
    usersession_set(topdir,key,0);
  }
}

function on_off_options (name,val,oldval)
{
  var obj = document.getElementById(name + '_' + val + '_contents');

  obj.style.display = 'block';

  if ( oldval && ( oldval != val ) )
  {
    var oobj = document.getElementById(name + '_' + oldval + '_contents');
    oobj.style.display = 'none';
  }
}


function openMatmatronch(topdir, id, width, height) {
    if (width == "")
    width = 280;
    if (height == "")
    height = 390;

    path = "/matmatronch/index.php/image/" + id;


    window.open(path,
                "Photo Mat'Matronch",
                "width="+width+",height="+height);
}

/**
 * Calendar V2 : tinycalendar
 */
function opencal(topdir, __target, type)
{
  var target = document.getElementById(__target);
  var pos = findPos(target);

  var elem = document.getElementById(__target + '_calendar');
  if(elem == null)
  {
      elem = document.createElement('div');
      elem.id = __target + '_calendar';
      elem.className = 'tinycal_box';
      document.body.appendChild(elem);

      elem.style.display = 'block';
      elem.style.zIndex = '99';
      elem.style.left = pos[0] + 150;
      elem.style.top = pos[1] - 20;
      openInContents(__target + '_calendar', topdir + 'gateway.php', 'module=tinycal&target=' + __target + '&type=' + type + '&topdir=' + topdir);
  }
  else
  {
    if(elem.style.display == "none")
      elem.style.display = "block"
    else if(elem.style.display == "block")
      alert("un calendrier est deja ouvert !");
  }
}

function closecal(name)
{
  var elem = document.getElementById(name + '_calendar');
  elem.style.display = 'none';

  return true;
}

function return_val(target_id, value)
{
  target = document.getElementById(target_id);
  target.value = value;
  closecal(target_id);
  return true;
}

function errorMsg()
{
alert("Netscape 6 or Mozilla is needed to install a sherlock plugin");
}

function addEngine(topdir,name,ext,cat,type)
{
if ((typeof window.sidebar == "object") && (typeof
window.sidebar.addSearchEngine == "function"))
{
window.sidebar.addSearchEngine(
"http://"+topdir+name+".src",
"http://"+topdir+name+"."+ext,
name,
cat );
}
else
{
errorMsg();
}
}


function show_obj_top(obj)
{

  var content = document.getElementById(obj);

  content.style.display = 'block';
  content.style.zIndex = 10000000;

  if ( document.all )  // replacons l'élément histoire d'éviter un bug d'IE6
  {
    var target = document.getElementById("left");
    var parent = content.parentNode;
    parent.removeChild(content);
    target.insertBefore(content, document.getElementById("sbox_calendrier").nextSibling);
  }
}

function hide_obj(obj)
{
  var content = document.getElementById(obj);
  content.style.display = 'none';
}

function ho(obj)
{
  hide_obj(obj);
}

function sot(obj)
{
  show_obj_top(obj);
}

function switchphoto (dest,src)
{
  var img = document.getElementById(dest);

  if ( img )
    img.src = src;

}

function toggle(id_tglnum,id)
{

      var toHide = null;
      var imgToChange = null;

      toHide = document.getElementById("tgl"+id_tglnum+"_"+id);
      imgToChange = document.getElementById("tgl"+id_tglnum+"_img"+id);

      toHidezeClass = toHide.getAttribute('class');
      imgToChangezeClass = imgToChange.getAttribute('class');

      if (toHide == null)
      {
        alert("objects to hide not found !");
        return null;
      }
      if (imgToChange == null)
      {
        alert("image to change not found !");
        return null;
      }

      if (!toHidezeClass)
      {
  toHide.setAttribute('class', 'tgloff');
      //  toHide.class = "tgloff";
      }


      toHidezeClass = toHide.getAttribute('class');
      imgToChangezeClass = imgToChange.getAttribute('class');


      if (toHidezeClass == "tglon")
      {
  toHide.setAttribute('class', 'tgloff');
         //toHide.class = "tgloff";
        toHide.style.display = "none";
  imgToChange.src = "/images/fll.png";

      }
      else
      {
  toHide.setAttribute('class', 'tglon');
   //toHide.class = "tglon";
        toHide.style.display = "inline";
        imgToChange.src = "/images/fld.png";
      }
}

function insert_tags(txtarea, lft, rgt, sample_text)
{
  sample_text = typeof(sample_text) != 'undefined' ? sample_text : 'votre tAExte'; /* pas de passage d'arguments par défaut en JS alors on fait autrement */

  if (lft == '[[' && rgt == ']]') /* balises d'URL */
    {
      var _url = prompt("Entrez l'URL:","http://");

      if (_url != "" && _url != "http://") {
  lft="[[" + _url + "|";
  rgt="]]";
  insert_tags(txtarea, lft, rgt);
      }
      else
  insert_tags(txtarea, lft, " "+rgt); /* vieux truandage pour passer outre le test */

      return;
    }
  else if (document.all) /* IE */
    {
      _selection = document.selection.createRange().text;
      if (_selection == "")
        _selection = sample_text;

      document.selection.createRange().text = lft + _selection + rgt;
    }
  else if (document.getElementById) /* Firefox... */
    {
      var _length = txtarea.textLength;
      var _start = txtarea.selectionStart;
      var _end = txtarea.selectionEnd;
      if (_end==1 || _end==2)
  _end = _length;
      var s1 = (txtarea.value).substring(0,_start);
      var s2 = (txtarea.value).substring(_start, _end)
      var s3 = (txtarea.value).substring(_end, _length);

      if(s2 == "")
        s2 = sample_text;

      txtarea.value = s1 + lft + s2 + rgt + s3;

    }
}

function setSelectionRange(input, selectionStart, selectionEnd)
{
  if (input.createTextRange)
  {
    var range = input.createTextRange();
    range.collapse(true);
    range.moveEnd('character', selectionEnd);
    range.moveStart('character', selectionStart);
    range.select();
  }
  else if (input.setSelectionRange)
  {
    input.focus();
    input.setSelectionRange(selectionStart, selectionEnd);
  }
  else
  {
    input.selectionStart = selectionStart;
    input.selectionEnd = selectionEnd;
  }
}

function insert_tags2(objid, lft, rgt, deftext)
{
  var obj = document.getElementById(objid);

  if ( !obj )
    return;

  if ( document.selection )
  {
    oldlen = obj.value.length;


    obj.focus();
    range = document.selection.createRange();
    if ( range.text == "")
    {
      len=deftext.length;
      range.text = lft + deftext + rgt;
    }
    else
    {
      len=range.text.length;
      range.text = lft + range.text + rgt;
    }
    range.select();

    range = document.selection.createRange();
    if ( window.opera && rgt.substring(rgt.length-1) == "\n" )
    {
      range.moveStart('character', -rgt.length-len+1);
      range.moveEnd('character', -rgt.length+1);
    }
    else
    {
      range.moveStart('character', -rgt.length-len);
      range.moveEnd('character', -rgt.length);
    }
    range.select();
  }
  else if ( obj.selectionStart != null )
  {
    obj.focus();
    var start = obj.selectionStart;
    var end = obj.selectionEnd;

    var s1 = obj.value.substring(0,start);
    var s2 = obj.value.substring(start, end)
    var s3 = obj.value.substring(end);

    if(s2 == "")
      s2 = deftext;

    obj.value = s1 + lft + s2 + rgt + s3;

    setSelectionRange(obj,start+lft.length,start+lft.length+s2.length);
  }
}


function nl2doku (id) {
  var reg1 = /([^\n\\])\n([^\n ])/g;
  var reg2 = /(\*|-)(.+)\\\\/g;
  var d = document.getElementById (id);

  d.value = d.value.replace (reg1, '$1\\\\\n$2').replace (reg2, '$1$2');
}


function popUpStream(topdir)
{
  window.open(topdir+"stream.php?get=popup", "stream", "width=300,height=350,status=no,scrollbars=yes,resizable=yes");
  return false;
}

var onSelectedFile;
var onSelectedFileFieldName;

function onSelectedWikiFile ( id, titre  )
{
  insert_tags2(onSelectedFileFieldName, "[[", "]]", "dfile://"+id);
}

function onSelectedWikiImage ( id, titre  )
{
  insert_tags2(onSelectedFileFieldName, "{{", "}}", "dfile://"+id);
}

function _selectFile ( topdir,context )
{
  window.open(topdir+"explorer.php?"+context, "fileselector", "width=750,height=500,status=no,scrollbars=yes,resizable=yes");
}

function selectWikiImage(topdir,field,context)
{
  onSelectedFileFieldName = field;
  onSelectedFile = onSelectedWikiImage;
  _selectFile(topdir,context);
}

function selectWikiFile(topdir,field,context)
{
  onSelectedFileFieldName = field;
  onSelectedFile = onSelectedWikiFile;
  _selectFile(topdir,context);
}

var listFileTopDir;
var listFileField;

function onSelectedListFile ( id, titre )
{
  var contener = document.getElementById("_files_"+listFileField+"_items");
  var values = document.getElementById("_files_"+listFileField+"_ids");

  //Visuel

  var elem = document.createElement("div");
  var buffer = "";

  elem.setAttribute("id","_files_"+listFileField+"_"+id);
  elem.setAttribute("class","slsitem");

  elem.innerHTML= "<a href=\""+listFileTopDir+"/d.php?id_file="+id+"\"><img src=\""+listFileTopDir+"images/icons/16/file.png\" /> "+titre+"</a> <a href=\"\" onclick=\"removeListFile('"+listFileTopDir+"','"+listFileField+"',"+id+"); return false;\"><img src=\""+listFileTopDir+"images/actions/delete.png\" alt=\"Enlever\" /></a>";

  contener.insertBefore(elem,null);

  // Données
  if ( values.value == "" )
    values.value = id;
  else
    values.value = values.value + "," + id;
}

function removeListFile(topdir,field,id)
{
  var element = document.getElementById("_files_"+field+"_"+id);
  var values = document.getElementById("_files_"+field+"_ids");

  // Visuel
  var contener = element.parentNode;
  contener.removeChild(element);

  // Données
  var ids = values.value.split(",");
  var nouv = "";
  for(i=0;i<ids.length;i++)
  {
    if ( ids[i] != id )
    {
      if ( nouv != "" )
        nouv = nouv + "," + ids[i];
      else
        nouv = ids[i];
    }
  }
  values.value=nouv;
}

function selectListFile(topdir,field,context)
{
  listFileTopDir=topdir;
  listFileField=field;
  onSelectedFile = onSelectedListFile;
  _selectFile(topdir,context);
}


/* menus "dropdown" */

var disappeardelay=250;  //dispartion des menus apres X milisecondes

var ie4=document.all;
var ns6=document.getElementById&&!document.all;

/*if (ie4||ns6)
  document.write('<div id="dropmenudiv" onMouseover="clearhidemenu()" onMouseout="dynamichide(event)"></div>');
*/
function getposOffset(what, offsettype)
{
  var totaloffset=(offsettype=="left")? what.offsetLeft : what.offsetTop;
  var parentEl=what.offsetParent;
  while (parentEl!=null)
  {
    totaloffset=(offsettype=="left")? totaloffset+parentEl.offsetLeft : totaloffset+parentEl.offsetTop;
    parentEl=parentEl.offsetParent;
  }
  return totaloffset;
}


function showhide(obj, e, visible, hidden, menuwidth)
{
  if (typeof menuwidth!="undefined" && menuwidth!="")
  {
    dropmenuobj.widthobj=dropmenuobj.style;
    dropmenuobj.widthobj.width=menuwidth;
  }
  if (e.type=="click" && obj.visibility==hidden || e.type=="mouseover")
    obj.visibility=visible;
  else if (e.type=="click")
    obj.visibility=hidden;
}

function iecompattest()
{
  return (document.compatMode && document.compatMode!="BackCompat")? document.documentElement : document.body;
}

function clearbrowseredge(obj, whichedge)
{
  var edgeoffset=0;
  if (whichedge=="rightedge")
  {
    var windowedge=ie4 && !window.opera? iecompattest().scrollLeft+iecompattest().clientWidth-15 : window.pageXOffset+window.innerWidth-15;
    dropmenuobj.contentmeasure=dropmenuobj.offsetWidth;
    if (windowedge-dropmenuobj.x < dropmenuobj.contentmeasure)
      edgeoffset=dropmenuobj.contentmeasure-obj.offsetWidth;
  }
  else
  {
    var topedge=ie4 && !window.opera? iecompattest().scrollTop : window.pageYOffset;
    var windowedge=ie4 && !window.opera? iecompattest().scrollTop+iecompattest().clientHeight-15 : window.pageYOffset+window.innerHeight-18;
    dropmenuobj.contentmeasure=dropmenuobj.offsetHeight;
    // move up
    if (windowedge-dropmenuobj.y < dropmenuobj.contentmeasure)
    {
      edgeoffset=dropmenuobj.contentmeasure+obj.offsetHeight;
      if ((dropmenuobj.y-topedge)<dropmenuobj.contentmeasure)
        edgeoffset=dropmenuobj.y+obj.offsetHeight-topedge;
    }
  }
  return edgeoffset;
}

function populatemenu(what)
{
  if (ie4||ns6)
    dropmenuobj.innerHTML=what.join("");
}


function dropdownmenu(obj, e, menucontents, menuwidth)
{
  if (typeof menuwidth=="undefined")
    menuwidth="";
  if (window.event)
    event.cancelBubble=true;
  else if (e.stopPropagation)
    e.stopPropagation();
  clearhidemenu();
  dropmenuobj=document.getElementById? document.getElementById("dropmenudiv") : dropmenudiv;
  dropmenuobj.style.display = 'block';
  populatemenu(menucontents);
  if (ie4||ns6)
  {
    showhide(dropmenuobj.style, e, "visible", "hidden", menuwidth);
    dropmenuobj.x=getposOffset(obj, "left");
    dropmenuobj.style.left=dropmenuobj.x-clearbrowseredge(obj, "rightedge")+"px";
  }
  return clickreturnvalue();
}

function clickreturnvalue()
{
  if (ie4||ns6)
    return false;
  return true;
}

function contains_ns6(a, b)
{
  while (b.parentNode)
  if ((b = b.parentNode) == a)
    return true;
  return false;
}

function dynamichide(e)
{
  if (ie4&&!dropmenuobj.contains(e.toElement))
    delayhidemenu();
  else if (ns6&&e.currentTarget!= e.relatedTarget&& !contains_ns6(e.currentTarget, e.relatedTarget))
    delayhidemenu();
}

function hidemenu(e)
{
  if (typeof dropmenuobj!="undefined")
  {
    if (ie4||ns6)
      dropmenuobj.style.visibility="hidden";
  }
}

function delayhidemenu()
{
  if (ie4||ns6)
    delayhide=setTimeout("hidemenu()",disappeardelay);
}

function clearhidemenu()
{
  if (typeof delayhide!="undefined")
    clearTimeout(delayhide);
}

document.onclick=hidemenu;

/*fin menus drop down */


/* connexion topmoumoute */
function resize(){
var htmlheight = document.body.parentNode.clientHeight;
var windowheight = window.screen.height;
var frame = document.getElementById("frame1");
if ( htmlheight < windowheight )
 { frame.style.height = windowheight + "px"; }
}
function showConnexionBox()
{
  var e=document.getElementById('overlay');
  var htmlheight = document.body.parentNode.clientHeight;
  var windowheight = window.screen.height;
  if ( htmlheight < windowheight )
    e.style.height = windowheight + "px";
  else
    e.style.height = htmlheight + "px";
  e.style.display = 'block';
  center('passwordbox');
  return false;
}

function hideConnexionBox()
{
  var elem=document.getElementById('passwordbox');
  elem.style.display = 'none';
  elem=document.getElementById('overlay');
  elem.style.display = 'none';
  return false;
}

function center(name)
{

  element = document.getElementById(name);

  var my_width  = 0;
  var my_height = 0;

  if ( typeof( window.innerWidth ) == 'number' )
  {
    my_width  = window.innerWidth;
    my_height = window.innerHeight;
  }
  else if ( document.documentElement &&
            (
              document.documentElement.clientWidth ||
              document.documentElement.clientHeight
            )
          )
  {
    my_width  = document.documentElement.clientWidth;
    my_height = document.documentElement.clientHeight;
  }
  else if ( document.body &&
            ( document.body.clientWidth || document.body.clientHeight )
          )
  {
    my_width  = document.body.clientWidth;
    my_height = document.body.clientHeight;
  }

  element.style.position = 'absolute';
  element.style.zIndex   = 99;

  var scrollY = 0;

  if ( document.documentElement && document.documentElement.scrollTop )
    scrollY = document.documentElement.scrollTop;
  else if ( document.body && document.body.scrollTop )
    scrollY = document.body.scrollTop;
  else if ( window.pageYOffset )
    scrollY = window.pageYOffset;
  else if ( window.scrollY )
    scrollY = window.scrollY;

  if (element.style.display != 'none')
  {
    var setX = ( my_width  - element.offsetWidth ) / 2;
    var setY = ( my_height - element.offsetHeight ) / 2 + scrollY;
  }
  else
  {
    var els = element.style;
    var originalVisibility = els.visibility;
    var originalPosition = els.position;
    els.visibility = 'hidden';
    els.position = 'absolute';
    els.display = '';
    var originalWidth = element.clientWidth;
    var originalHeight = element.clientHeight;
    els.display = 'none';
    els.position = originalPosition;
    els.visibility = originalVisibility;
    var setX = ( my_width  - originalWidth  ) / 2;
    var setY = ( my_height - originalHeight ) / 2 + scrollY;
  }
  setX = ( setX < 0 ) ? 0 : setX;
  setY = ( setY < 0 ) ? 0 : setY;

  element.style.left = setX + "px";
  element.style.top  = setY + "px";

  element.style.display  = 'block';

}

/* fin connexion topmoumoute */


function hide_with_cookies(ctsp,cookiename){
  if(!cookiename)
    return false;
  var cts;
  if( cts = document.getElementById(ctsp) )
    cts.style.display='none';
  expire = new Date();
  var hour=expire.getHours();
  var min=expire.getMinutes();
  var sec=expire.getSeconds();
  if(hour<12)
    var left=((12-hour)*60-min)*60-sec;
  else
    var left=((24-hour)*60-min)*60-sec;
  expire.setTime(expire.getTime() + (left*1000));
  document.cookie = cookiename + '=1; expires='+expire.toGMTString();
}

/**
 * @see /include/cts/selectbox.inc.php
 */
var select_box = function(from, to){
  this.init(from, to);
}
select_box.prototype = {
  from: null,
  to: null,
  self: null,

  init: function(from, to){
    this.from = from;
    this.to = to;
  },

  add_to: function(elem, content, value, otherone){
    var o = new Option(content, value);
    o.ondblclick = function(e){m(this);};
    elem.options[elem.length] = o;
  },

  remove_from: function(elem, index){
    if(elem.length > 0){
      elem.options[index] = null;
    }
  },

  move: function(from, to){
    var contents = new Array();
    var values = new Array();
    var count = 0;

    for(var i = from.length-1; i >= 0; i--){
      if(from.options[i].selected){
        contents[count] = from.options[i].text;
        values[count] = from.options[i].value;
        this.remove_from(from, i);
        count++;
      }
    }
    for(i = count-1; i >= 0; i--){
      this.add_to(to, contents[i], values[i], from);
    }
  },

  move_all: function(from, to){
    this.select_all(from);
    this.move(from, to);
  },

  /* appele notamment en onsubmit pour recuperer les valeurs du select des choix */
  select_all: function(elem){
    for(var i = elem.length-1; i >= 0; i--){
      elem.options[i].selected = true;
    }
  }
}
m = function(elem){
  from = elem.parentNode;
  to = elem.parentNode.to;
  e = elem.parentNode.parentNode.parentNode.sb;
  e.move(from, to);
}

function extend_textarea(id)
{
   var element = document.getElementById(id);
   element.rows+=10;
}

function reduce_textarea(id)
{
   var element = document.getElementById(id);
   if (element.rows>20)
      element.rows = element.rows-10;
}

function toggleSectionVisibility (node)
{
    if (node == null)
        return;
    var root = node;
    var title = node.previousSibling;

    while (node != null) {
        var sibling = node.nextSibling;
		if (sibling == null || sibling.style == null)
			break;
        if (sibling.nodeName[0] == "H" && sibling.nodeName.length == 2 && sibling.nodeName <= title.nodeName)
            break;

        if (sibling.style.length == 0) {
            sibling.style.setProperty ('display', 'none', 'important');
            if (node == root)
                root.innerText = '[+]';
        } else {
            sibling.style.removeProperty ('display');
            if (node == root)
                root.innerText = '[-]';
        }

        node = sibling;
    }
}

if (window.addEventListener) {
    var keys = [];
    var konami = "38,38,40,40,37,39,37,39,66,65";
    window.addEventListener("keydown", function(e){
      keys.push(e.keyCode);
      if (keys.toString()==konami) {
        keys = [];
        var id = document.getElementById('site');
        if (id.style.display == 'none')
          id.style.display = 'block';
        else
          id.style.display = 'none';
      } else if(keys.length==10) {
        keys.shift();
      };
    }, true);

    rightKey = "39";
    leftKey = "37";
    window.addEventListener("keydown", function(e){
      if ((e.keyCode == leftKey) || (e.keyCode == rightKey))
      {
        if (e.keyCode == leftKey)
          linkDiv = document.getElementById('back');
        else
          linkDiv = document.getElementById('next');
        if (linkDiv != null)
        {
          window.location= linkDiv.getElementsByTagName('a')[0].href
        }
      }
    }, true);
};
