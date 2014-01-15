/* Copyright 2008
 * - Simon Lopez < simon dot lopez at ayolo dot org >
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
 * Fonctions pour les slideshow des "box".
 *
 * **RAPPEL** Ce fichier est sous licence GNU GPL. Vous pouvez le ré-utiliser
 * sur votre site internet, mais il doit rester sous licence GNU GPL même si
 * vous le modifiez. Si vous ré-utilisez des sources de gateway.php, ces sources
 * étant sous la même licence, elles devront aussi rester sous GNU GPL.
 * Pour plus d'information : http://www.gnu.org/
 *
 * @author Simon Lopez
 */

/*
 * @ingroup js
 */
var slideshowboxes=new Array();
var slideshowpos=new Array();
function slideshow_onoff(cts,id){
  var cts1;
  if(slideshowboxes[cts]==0)
  {
    slideshowboxes[cts]=1;
    if(cts1 = document.getElementById(id) )
      cts1.innerHTML=cts1.innerHTML.replace(/pause/,'start');
  }
  else
  {
    slideshowboxes[cts]=0;
    if(cts1 = document.getElementById(id) )
      cts1.innerHTML=cts1.innerHTML.replace(/start/,'pause');
  }
}

function forceslideshow(cts,cts2,start_frame, end_frame,fn)
{
  var cts_1;
  var cts_2;
  var add = parseInt(fn,10);
  if(slideshowboxes[cts]==0)
    slideshow_onoff(cts,cts2);
  if( cts_1 = document.getElementById(cts+slideshowpos[cts]) )
  {
    if(add>0)
    {
      if((slideshowpos[cts]+add)>end_frame)
        slideshowpos[cts]=slideshowpos[cts]+add-end_frame+start_frame-1;
      else
        slideshowpos[cts]=slideshowpos[cts]+add;
    }
    else
    {
      if((slideshowpos[cts]+add)<start_frame)
        slideshowpos[cts]=end_frame-start_frame-slideshowpos[cts]-add-1;
      else
        slideshowpos[cts]=slideshowpos[cts]+add;
    }
    if( cts_2 = document.getElementById(cts+slideshowpos[cts]) )
    {
      cts_1.style.display='none';
      cts_2.style.display='block';
    }
  }
  return false;
}

function start_slideshow(cts,start_frame, end_frame, delay, pause)
{
  slideshowboxes[cts]=0;
  slideshowpos[cts]=0;
  setTimeout(switch_slides(cts,start_frame,start_frame,end_frame, delay, pause),delay);
}

function switch_slides(cts,frame, start_frame, end_frame, delay, pause)
{
  if(pause==0 || (pause==1 && slideshowboxes[cts]==0))
  {
    var cts1;
    var cts2;
    if( cts1 = document.getElementById(cts+frame) )
    {
      if (frame == end_frame)
        frame = start_frame;
      else
        frame = frame + 1;
      if( cts2 = document.getElementById(cts+frame) )
      {
        cts1.style.display='none';
        cts2.style.display='block';
        slideshowpos[cts]=frame;
      }
    }
    else
     return (function() {
       setTimeout(switch_slides(cts,frame, start_frame, end_frame, delay, pause), delay);
     })
  }
  return (function() {
    setTimeout(switch_slides(cts,frame, start_frame, end_frame, delay, pause), delay);
  })
}

