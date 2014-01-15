/* Copyright 2008
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
 
// Le nom des fonctions a été volontairement trés réduit pour limiter les volumes de code HTML

// SqlTableDefineClass
function stdc ( o, a, t )
{
  if ( a == 'c' ) // Check
    o.className = "ck";  
  else if ( a == 'u' ) // Uncheck [not over]
    o.className = "ln"+t;  
  else if ( a == 'uo' ) // Uncheck [over]
    o.className = "ov";      
  else if ( a == 'o' && o.className == "ln"+t ) // Over [unchecked
    o.className = "ov";  
  else if ( a == 'l' && o.className == "ov" ) // Leave [unchecked]
    o.className = "ln"+t;  
}

// SqlTableChecKusingLine
function stckl ( o, tbl, num )
{
  var cb = document.getElementById(tbl + '_c' + num);
  cb.checked = !cb.checked;
  if ( cb.checked )
    stdc(o,'c',(num+1)%2);
  else
    stdc(o,'uo',(num+1)%2);
}

// SqlTableSyncChecK
function stsck ( cb, tbl, num )
{
  var o = document.getElementById(tbl + '_l' + num);

  if ( cb.checked )
    stdc(o,'c',(num+1)%2);
  else
    stdc(o,'uo',(num+1)%2);
}

// SqlTableChecKAllas
function stcka ( g, tbl )
{
  var o = document.getElementById(tbl + '_count');
  for (var num = 0; num < o.value; num++)
  {
    var c = document.getElementById(tbl + '_c' + num);
    var l = document.getElementById(tbl + '_l' + num);
    
    c.checked = g.checked;
    
    if ( c.checked )
      stdc(l,'c',(num+1)%1);
    else
      stdc(l,'u',(num+1)%1);    
  }
}

// SqlTableComparaisonFilter
function stcft ( tbl, col, field )
{
  var opo = document.getElementById(tbl+"_f"+col+"_s");
  var vo = document.getElementById(tbl+"_f"+col+"_v");
  stsetfilter(tbl,col,field,opo.value,vo.value);
}

// SqlTableUnFilTer
function stuft ( o, tbl, col )
{
  stsetfilter(tbl,col,null,"",null);
  stfiltermarkoptions(o);

}

// SqlTableFilTerbyValue
function stftv ( o, tbl, col, field, val )
{
  stsetfilter(tbl,col,field,"=",val);
  stfiltermarkoptions(o);
}

function stfiltermarkoptions ( o )
{
  o = o.parentNode;
	var c = o.parentNode.firstChild;
	while ( c != null) {
		if (c.nodeName == "LI") c.className = "";
		c = c.nextSibling;
	}  
  o.className = "sel";
}  

// SqlTableFilTer
function stft ( tbl, col )
{
	var obj = document.getElementById(tbl+"_f"+col);

	if ( obj.style.display == 'none' )
		obj.style.display = 'block';
	else
		obj.style.display = 'none';
}

// SqlTableFilTerComparaisonField
function stftcf ( tbl, col )
{
  var opo = document.getElementById(tbl+"_f"+col+"_s");
  var vo = document.getElementById(tbl+"_f"+col+"_v");
	if ( opo.value != "" )
		vo.style.visibility = 'visible';
	else
		vo.style.visibility = 'hidden';
}



var sqltable2 = new Array();

// SqlTableSorT
function stst ( tbl, col )
{
  if ( !sqltable2[tbl] )
  {
    sqltable2[tbl] = new Array();
    sqltable2[tbl]['filters'] = new Array();
    sqltable2[tbl]['sort'] = new Array();
  }
  var typo = document.getElementById(tbl+"_"+col+"_t");
  var nsort = "a"+typo.value;
  
  var img = document.getElementById(tbl+"_s"+col+"_i");

  //alert(img.src);

  if ( sqltable2[tbl]['sort'][0] == col )
  {
    if ( sqltable2[tbl]['sort'][1] == nsort )
    {
      nsort = "d"+typo.value;
      img.src = img.src.replace("sort_a","sort_d");
    }
    else
    {
      img.src = img.src.replace("sort_d","sort_a");
    }
  }
  else if ( sqltable2[tbl]['sort'][0] )
  {
    var oimg = document.getElementById(tbl+"_s"+sqltable2[tbl]['sort'][0]+"_i");
    oimg.src = oimg.src.replace("sort_d","sort_a");
  }
  
  sqltable2[tbl]['sort'][0] = col;
  sqltable2[tbl]['sort'][1] = nsort;
  
  stupdate(tbl);
}
  
  
function stsetfilter ( tbl, col, field, op, val )
{
  if ( !sqltable2[tbl] )
  {
    sqltable2[tbl] = new Array();
    sqltable2[tbl]['filters'] = new Array();
    sqltable2[tbl]['sort'] = new Array();
  }
  
  var typo = document.getElementById(tbl+"_"+col+"_t");

  sqltable2[tbl]['filters'][col] = new Array(field,op,typo.value,val);
  
  stupdate(tbl);
  
	var obj = document.getElementById(tbl+"_f"+col);
  obj.style.display = 'none';
  
	var obj = document.getElementById(tbl+"_"+col);
  obj.className = op == "" ? "" : "filtree";
  
}

function stupdate ( tbl )
{
  var d = "fetch&sqltable2="+tbl;
  
  for (field in sqltable2[tbl]['filters'])  
  {
    var filter = sqltable2[tbl]['filters'][field];
    if ( filter[1] != "" )
      d = d+"&__st2f["+filter[0]+"]="+encodeURIComponent(filter[1]+filter[2]+filter[3]);
  }
  
  if ( sqltable2[tbl]['sort'][0] )
    d = d+"&__st2s["+ sqltable2[tbl]['sort'][0]+"]="+ sqltable2[tbl]['sort'][1];
  
  var selfo = document.getElementById(tbl+"_self");
  
  var cts = document.getElementById(tbl+"_contents");
  
  cts.innerHTML="<tr><td colspan=\""+cts.parentNode.rows[0].cells.length+"\">Chargement en cours...</td></tr>";
  
  openInContents( tbl+"_contents", selfo.value, d);
}


