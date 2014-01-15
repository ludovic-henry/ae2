/* Copyright 2007
 *
 * - Julien Etelain < julien at pmad dot net >
 *
 * "AE Recherche & Developpement" : Galaxy
 *
 * Ce fichier fait partie du site de l'Association des étudiants
 * de l'UTBM, http://ae.utbm.fr.
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
 
var galaxy=new Array();

var galaxy_see_top_x = 1000;
var galaxy_see_top_y = 1000;

var galaxy_x = 0;
var galaxy_y = 0;
var galaxy_extra = "";

var galaxy_position;

function galaxy_get_contents(obj,x,y)
{
  //obj.innerHTML="<img src=\"galaxy.php?action=area_image&x="+(x*500)+"&y="+(y*500)+"\" width=\"500\" height=\"500\" alt=\"\" />";
  //;
  //area_html
  obj.innerHTML="";
  openInContents( obj.getAttribute("id"), "galaxy.php", "action=area_html&x="+(x*500)+"&y="+(y*500)+galaxy_extra)
}

function galaxy_placeall()
{
  for(i=0;i<16;i++)
  {
    galaxy[i].style.left=((i%4)*500)-galaxy_see_top_x; 
    galaxy[i].style.top=(Math.floor(i/4)*500)-galaxy_see_top_y; 
  }
  galaxy_position.style.left = (galaxy_x*5)+(galaxy_see_top_x/100)-1;
  galaxy_position.style.top = (galaxy_y*5)+(galaxy_see_top_y/100)-1;
}

function galaxy_rotate ( dx, dy )
{
  galaxy_ox=galaxy_x;
  galaxy_oy=galaxy_y;
  
  galaxy_x+=dx;
  galaxy_y+=dy;
  galaxy_see_top_x-=(dx*500);
  galaxy_see_top_y-=(dy*500);
  
  var old_galaxy = galaxy;
  galaxy=new Array();
  i=0;
  for(y=0;y<4;y++)
  {
    for(x=0;x<4;x++)
    {
      ox = (x + dx) % 4;
      oy = (y + dy) % 4;
      if ( ox < 0 ) ox += 4;
      if ( oy < 0 ) oy += 4;
      galaxy[i]=old_galaxy[ox+(oy*4)]; 
      
      //galaxy[i].innerHTML="("+(x+galaxy_x)+","+(y+galaxy_y)+")";
      
      if ( galaxy_ox+ox != x+galaxy_x || galaxy_oy+oy != y+galaxy_y )
        galaxy_get_contents(galaxy[i],x+galaxy_x,y+galaxy_y);
      
      galaxy[i].style.top=(y*500)-galaxy_see_top_y; 
      galaxy[i].style.left=(x*500)-galaxy_see_top_x; 
      i++;
    }
  }
  galaxy_placeall();
}

var galaxy_drag=false;
var galaxy_sx=0,galaxy_sy=0;

var ie=document.all;var nn6=document.getElementById&&!document.all;

function galaxy_mousemove(e) {
  if ( galaxy_drag )
  {
    y = nn6 ? e.clientY : event.clientY;
    x = nn6 ? e.clientX : event.clientX;
    
    galaxy_see_top_x -= x-galaxy_sx;
    galaxy_see_top_y -= y-galaxy_sy;
    
    galaxy_sx=x;
    galaxy_sy=y;
    
    dx=0;
    dy=0;
    
    if ( galaxy_see_top_x > 1250 )
      dx = 1;
      
    if ( galaxy_see_top_y > 1500 )
      dy = 1;
      
    if ( galaxy_see_top_x < 500 )
      dx = -1;
      
    if ( galaxy_see_top_y < 500 )
      dy = -1;      
      
    if(dx!=0||dy!=0)
      galaxy_rotate(dx,dy);
    
    galaxy_placeall();
  }
  return false;}

function galaxy_mousedown(e) {
  galaxy_drag = true;
  
  galaxy_sy = nn6 ? e.clientY : event.clientY;
  galaxy_sx = nn6 ? e.clientX : event.clientX;
  
  return true;}

function galaxy_mouseup(e) {
  galaxy_drag = false;
    return true;}

function no_mousedown(e) {
  return false;}

function no_mouseup(e) {
  return false;}

function init_galaxy(go_x,go_y, extra)
{
  
  galaxy_position=document.getElementById("position");

  galaxy_x=0;
  galaxy_y=0;
  galaxy_extra=extra;
  
  galaxy_see_top_x = go_x;
  galaxy_see_top_y = go_y;
  
  while ( galaxy_see_top_x > 500 )
  {
    galaxy_x++;
    galaxy_see_top_x -= 500;
  }
  
  while ( galaxy_see_top_y > 500 )
  {
    galaxy_y++;
    galaxy_see_top_y -= 500;
  }
  
  galaxy_position.style.left = (galaxy_x*5)+(galaxy_see_top_x/100)-1;
  galaxy_position.style.top = (galaxy_y*5)+(galaxy_see_top_y/100)-1;
  
  for(i=0;i<16;i++)
  {
    galaxy[i]= document.getElementById("square"+i);
    galaxy_get_contents(galaxy[i],galaxy_x+(i%4),galaxy_y+Math.floor(i/4));
    galaxy[i].style.left=((i%4)*500)-galaxy_see_top_x; 
    galaxy[i].style.top=(Math.floor(i/4)*500)-galaxy_see_top_y; 
    galaxy[i].onmousedown = no_mousedown;
    galaxy[i].onmouseup = no_mouseup;
  }
  
  document.getElementById("viewer").onmouseup = galaxy_mouseup;
  document.getElementById("viewer").onmousedown = galaxy_mousedown;
  document.getElementById("viewer").onmousemove = galaxy_mousemove;
}
