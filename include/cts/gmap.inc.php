<?php
/* Copyright 2007
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
 */

require_once($topdir . "include/entities/pays.inc.php");
require_once($topdir . "include/entities/ville.inc.php");
/**
 * Permet d'afficher un carte (de goolge maps).
 *
 * @author Julien Etelain
 * @ingroup display_cts
 */
class gmap extends stdcontents
{
  var $name;
  var $uid;

  /* google map api key */
  var $key = "__GMAP_KEY__";

  var $markers = array();
  var $paths   = array();
  var $ville   = array();
  var $pays    = null;

  function gmap ( $name )
  {
    $this->name = $name;
    $this->uid="gmap_".gen_uid();
  }

  function add_marker ( $name, $lat, $long, $draggable=false, $dragend=null )
  {
    $this->markers[] = array("name"=>$name,"lat"=>$lat, "long"=>$long, "draggable"=>$draggable, "dragend"=>$dragend );
  }

  function add_geopoint ( &$g )
  {
    if( $g instanceof ville)
    {
      global $site;
      $pays = new pays($site->db);
      $pays->load_by_id($g->id_pays);
      $this->ville[] = &$g;
    }
    elseif( $g instanceof pays)
      $this->pays=&$g;
    else
      $this->add_marker($g->nom,$g->lat,$g->long );
  }

  function add_path ( $name, $latlongs, $color="ff0000" )
  {
    $this->paths[] = array("name"=>$name,"latlongs"=>$latlongs, "color"=>$color );
  }

  function add_geopoint_path ( $name, $geopoints, $color="ff0000" )
  {
    $latlongs=array();
    foreach ($geopoints as $g)
      $latlongs[]=$g;
    $this->add_path($name,$latlongs, $color);
  }

  function html_render()
  {
    global $site;
    $this->buffer .= "<div id=\"".$this->uid."_canvas\" style=\"width: 500px; height: 300px\"></div>";


    if(!isset($GLOBALS["gmaploaded"]))
    {
      $this->buffer .= "
        <script src=\"http://www.google.com/jsapi?key=".$this->key."\" type=\"text/javascript\"></script>\n";
      $GLOBALS["gmaploaded"]=true;
    }
    $this->buffer .= "
      <script type=\"text/javascript\">\n";

    //
    $this->buffer .="google.load(\"maps\", \"2\");\n";
    $this->buffer .="var ".$this->uid.";\n";

    if(is_null($this->pays))
    {
      foreach ( $this->markers as $i => $marker )
        $this->buffer .= "var ".$this->uid."marker_".$i.";\n";

      foreach ( $this->paths as $i => $path )
        $this->buffer .= "var ".$this->uid."path_".$i.";\n";
    }

    $this->buffer .="function initialize() {\n";
    $this->buffer .= $this->uid." = new google.maps.Map2(document.getElementById(\"".$this->uid."_canvas\"));\n";


    if(is_null($this->pays))
    {
      $first = true;

      foreach ( $this->markers as $i => $marker )
      {
        $this->buffer .= "var ".$this->uid."marker_".$i."_point = new google.maps.LatLng(".sprintf("%.12F",$marker['lat']*360/2/M_PI).", ".sprintf("%.12F",$marker['long']*360/2/M_PI).");\n";


        if ( $first )
        {
          $this->buffer .= $this->uid.".setCenter(".$this->uid."marker_".$i."_point, 15);\n";
          $first = false;
        }

        if ( $marker["draggable"] )
        {
          $this->buffer .= $this->uid."marker_".$i." = new google.maps.Marker(".$this->uid."marker_".$i."_point, {draggable: true});\n";
          if ( !is_null($marker["dragend"]) )
            $this->buffer .= "google.maps.Event.addListener(marker, \"dragend\", ".$marker["dragend"]." );\n";
        }
        else
          $this->buffer .= $this->uid."marker_".$i." = new google.maps.Marker(".$this->uid."marker_".$i."_point);\n";

        $this->buffer .= $this->uid.".addOverlay(".$this->uid."marker_".$i.");\n";

      }

      foreach($this->ville as $ville)
      {
        $pays = new pays($site->db);
	$pays->load_by_id($ville->id_pays);
        $this->buffer .= "var ".$this->uid."ville_".$ville->id."_dec = new google.maps.ClientGeocoder();\n";
	$this->buffer .= "
".$this->uid."ville_".$ville->id."_dec.getLatLng(\"".$ville->nom.", ".$ville->cpostal.", ".$pays->nom."\",
function(point)
{
  if(!point)
    return;
  else
  {
    ";
        if($first)
	{
          $this->buffer.="    ".$this->uid.".setCenter(point,12);";
          $first=false;
        }
        $this->buffer.="
    ".$this->uid.".addOverlay(new google.maps.Marker(point));
  }
}
);\n";
      }

      foreach ( $this->paths as $i => $path )
      {
        $points=array();
        foreach( $path["latlongs"] as $point )
        {
          if($point instanceof ville)
          {
            $pays = new pays($site->db);
            $pays->load_by_id($point->id_pays);
            $points[] = $point->nom.", ".$point->cpostal.", ".$pays->nom;
          }
          else
            $points[] = "@".sprintf("%.12F",$point['lat']*360/2/M_PI).", ".sprintf("%.12F",$point['long']*360/2/M_PI);
        }

        $this->buffer .= "var ".$this->uid."path_".$i."_points = \"from: ".implode(" to: ",$points)."\";\n";
        $this->buffer .= $this->uid."path_".$i."= new google.maps.Directions(".$this->uid.");\n";
        $this->buffer .= $this->uid."path_".$i.".load(".$this->uid."path_".$i."_points, {getSteps:true});\n";
      }
    }
    else
    {
      $this->buffer .= 'var '.$this->uid.'pays_'.$this->pays->id."= new google.maps.ClientGeocoder();\n";
      $this->buffer .= $this->uid."pays_".$this->pays->id.".getLatLng(\"".$this->pays->nom."\",
function(point)
{
  if(!point)
    return;
  else
  {
    ".$this->uid.".setCenter(point,5);
    ".$this->uid.".addOverlay(new google.maps.Marker(point));
  }
}
);\n";
    }

    $this->buffer .= $this->uid.".addControl(new google.maps.SmallMapControl());\n";
    $this->buffer .= $this->uid.".addControl(new google.maps.MapTypeControl());\n";

    $this->buffer .= "
    }

    google.setOnLoadCallback(initialize);
    document.onunload=GUnload();

    </script>";


    return $this->buffer;
  }


}



?>
