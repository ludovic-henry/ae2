<?php

/** @file
 *
 *
 */
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

/** Conteneur standart
 * @ingroup display_cts
 */
class box_slideshow extends stdcontents
{

  function box_slideshow($title=null,$delay=5000,$pause=true)
  {
    $this->title = $title;
    $this->divid = null;
    $this->cssclass = null;
    $this->slides=array();
    $this->delay=$delay;
    $this->pause=$pause;
    $this->buffer="";
    $this->nb=0;
    $this->uid=gen_uid();
  }

  function add_slide($cts)
  {
    if(!is_null($cts))
    {
      $this->slides[$this->nb]=$cts;
      $this->nb++;
    }
  }

  function is_empty()
  {
    return empty($this->slides);
  }

  function html_render ()
  {
    if($this->nb==0)
      return "";
    $uid=$this->uid;
    if($this->nb > 1)
    {
      $this->buffer.="<script type=\"text/javascript\">\n";
      if($this->pause && $this->nb>1)
      {
        $this->buffer.="start_slideshow('slideshow$uid', 0, ".(count($this->slides)-1).", ".$this->delay.",1);\n";
        $this->buffer.="</script>\n";
        $this->buffer.="<div class='slidebox_pause' id='slideshowonoff$uid'>";
        $this->buffer.="<a href='#' class='slidenav' onclick=\"forceslideshow('slideshow$uid', 'slideshowonoff$uid', 0, ".(count($this->slides)-1).",'-1'); return false;\">&laquo;</a> ";
        $this->buffer.="<a href='#' onclick=\"slideshow_onoff('slideshow$uid','slideshowonoff$uid'); return false;\">pause</a> ";
        $this->buffer.="<a href='#' class='slidenav' onclick=\"forceslideshow('slideshow$uid', 'slideshowonoff$uid', 0, ".(count($this->slides)-1).",'1'); return false;\">&raquo;</a></div>";
      }
      else
      {
        $this->buffer.="start_slideshow('slideshow$uid', 0, ".(count($this->slides)-1).", ".$this->delay.",0);\n";
        $this->buffer.="</script>\n";
      }
    }

    for($i=0;$i<$this->nb;$i++)
    {
      if($i==0)
        $this->buffer.="<div id='slideshow$uid$i' class='slideshow_box' style=\"display:block\">".$this->slides[$i]->html_render()."</div>\n";
      else
        $this->buffer.="<div id='slideshow$uid$i' class='slideshow_box' style=\"display:none\">".$this->slides[$i]->html_render()."</div>\n";
    }
    return $this->buffer;
  }

}
