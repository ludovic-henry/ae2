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

function nextItem(item, nodeName) {
	if (item == null) return;
	var next = item.nextSibling;
	while (next != null) {
		if (next.nodeName == nodeName) return next;
		next = next.nextSibling;
	}
	return null;
}

function previousItem(item, nodeName) {
	var previous = item.previousSibling;
	while (previous != null) {
		if (previous.nodeName == nodeName) return previous;
		previous = previous.previousSibling;
	}
	return null
}

function moveBefore(item1, item2) {
	var parent = item1.parentNode;
	parent.removeChild(item1);
	item1.style.top=0;
	parent.insertBefore(item1, item2);
}

function moveAfter(item1, item2) {
	var parent = item1.parentNode;
	parent.removeChild(item1);
				item1.style.top=0;

	parent.insertBefore(item1, item2 ? item2.nextSibling : null);
}
var ie=document.all;var nn6=document.getElementById&&!document.all;var dnds_isdrag=false;var dnds_y;var dnds_dobj;
var dnds_old_zIndex;
var dnds_coll;
function dnds_movemouse(e){	if (dnds_isdrag)	{
		var mouse_y = nn6 ? e.clientY : event.clientY;
		dnds_dobj.style.top  = dnds_ty + mouse_y - dnds_y;
    
    	
		var move_to_item=null;
		var previous = previousItem(dnds_dobj, dnds_dobj.nodeName);		
		while (previous != null) {
			if ( dnds_dobj.offsetTop <= previous.offsetTop ) {
				move_to_item = previous;
			}
			previous = previousItem(previous, dnds_dobj.nodeName);
		}
		if (move_to_item != null) {
			moveBefore(dnds_dobj, move_to_item);
			dnds_y = mouse_y;
			return;
		}

		move_to_item=null;
		var next = nextItem(dnds_dobj, dnds_dobj.nodeName);
		while (next != null) {
			if ( next.offsetTop+next.offsetHeight-dnds_dobj.offsetHeight <= dnds_dobj.offsetTop ) {
				move_to_item = next;
			}
			next = nextItem(next, dnds_dobj.nodeName);
		}
		
		if (move_to_item != null) {
			moveAfter(dnds_dobj,move_to_item);
			dnds_y = mouse_y;
			return;
		}    
    
    	return false;
    }}function dnds_startdrag(e,obj,coll) {

	var fobj


	dnds_coll = coll;    dnds_isdrag = true;    dnds_dobj = document.getElementById(obj);    dnds_ty = parseInt(dnds_dobj.style.top+0);    dnds_y = nn6 ? e.clientY : event.clientY;
    
    dnds_old_zIndex=dnds_dobj.style.zIndex;
    dnds_dobj.style.zIndex = 100000;
        document.onmousemove=dnds_movemouse;    return false;}

function dnds_mousedown(e) {	var fobj = nn6 ? e.target : event.srcElement;	if (fobj.className=="dragstartzone")		return false;}


function dnds_mouseup(e)
{
	if ( dnds_isdrag )
	{
		dnds_dobj.style.top=0;
		dnds_isdrag = false;
		dnds_dobj.style.zIndex=dnds_old_zIndex;
		
		var fchild = dnds_dobj.parentNode.firstChild;
		
		if ( fchild.nodeName != dnds_dobj.nodeName )
			fchild = nextItem( fchild,dnds_dobj.nodeName );

		var res=null;

		while ( fchild != null )
		{
			if ( res != null )
				res = res + ',' + fchild.getAttribute("id");
			else
				res = fchild.getAttribute("id");
			fchild = nextItem( fchild,dnds_dobj.nodeName );
		}
		
		usersession_set("./",dnds_coll,res);
		
		return false;
	}
}

document.onmousedown=dnds_mousedown;
document.onmouseup=dnds_mouseup;

