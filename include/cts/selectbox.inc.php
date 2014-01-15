<?php
/* Copyright 2008
 * - Manuel Vonthron < manuel dot vonthron at acadis dot org >
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
 * Fourni une interface de sélection d'elements a base de
 * deux <select> multiples
 * Inspiree de `selector` de django-admin
 * 
 * Le composant peut etre utilise tel quel ou dans un form
 * il peut etre multiplie dans la meme page sans probleme
 * 
 * @param name
 * @param title
 * @param values array( key => value )
 * @param page page de reception du form
 * @param select_title (sera affiche sous la forme '<truc> disponibles')
 * @see /js/site.js class select_box
 */

class selectbox extends form
{
  public function __construct($name, $title, $values, $action, $select_title=null, $right_val=null, $height=null, $width=null)
  {
    $this->form($name, $action, false, "post", $title);

    $this->values = $values;
    $this->right_values = $right_val;
    $this->sb_from = $name.'_from';
    $this->sb_to = $name.'_to';
    $this->sb_name = $name.'_sb';
    $this->select_title = $select_title;
    $this->width = $width;
    $this->height = $height;

    $this->add_selectbox();
    $this->set_event("onsubmit", "document.getElementById('$this->name').sb.select_all(document.getElementById('$this->sb_to'));");
  }

  private function add_selectbox(){
    if($this->height){
      $hsel = "style=\"height: $this->height;\"";
      $hul = "style=\"margin-top: ".($this->height*0.50-22).";\"";
    }else{
      $hsel = "";
      $hul = "";
    }
    
    
    /** firebug lite */
    //$this->buffer .= "<script type=\"text/javascript\" src=\"http://getfirebug.com/releases/lite/1.2/firebug-lite-compressed.js\"></script>\n\n";
    
    $this->buffer .= "<div class=\"selectbox\" id=\"$this->name\">\n";

    /* div from */
    $this->buffer .= "<div class=\"selectbox_disp\">\n";
    if($this->select_title)
      $this->buffer .= "<h4>".$this->select_title." disponible(s) :</h4>\n";
    $this->buffer .= "<select name=\"$this->sb_from\" id=\"$this->sb_from\" multiple=\"multiple\" $hsel>\n";
    foreach($this->values as $key => $value)
      $this->buffer .= "  <option value=\"".$key."\" "
                        ."ondblclick=\"m(this);\">"
                        .$value."</option>\n";
    $this->buffer .= "</select>\n";
    $this->buffer .= "</div>\n";

    /* actions */
    $this->buffer .= "<ul class=\"selectbox_actions\" $hul>";
    $this->buffer .= "  <li class=\"ajouter\" onclick=\"document.getElementById('$this->name').sb.move(document.getElementById('$this->sb_from'), document.getElementById('$this->sb_to'));\">&nbsp;</li>";
    $this->buffer .= "  <li class=\"enlever\" onclick=\"document.getElementById('$this->name').sb.move(document.getElementById('$this->sb_to'), document.getElementById('$this->sb_from'));\">&nbsp;</li>";
    $this->buffer .= "</ul>";

    /* div to */
    $this->buffer .= "<div class=\"selectbox_choix\">\n";
    if($this->select_title)
      $this->buffer .= "<h4>".$this->select_title." choisi(es) :</h4>\n";
      
    $this->buffer .= "<select name=\"".$this->sb_to."[]\" id=\"$this->sb_to\" multiple=\"multiple\" $hsel>\n";
    if(!empty($this->right_values))
      foreach($this->right_values as $key => $value)
        $this->buffer .= "  <option value=\"".$key."\" "
                          ."ondblclick=\"m(this);\">"
                          .$value."</option>\n";
    $this->buffer .= "</select>\n";
    $this->buffer .= "</div>\n";

    $this->buffer .= "<div class=\"clearboth\"></div>\n";

    $this->buffer .= "</div>\n";
    $this->buffer .= "<script type=\"text/javascript\">\n".
                     "  document.getElementById('$this->name').sb = new select_box(document.getElementById('$this->sb_from'), document.getElementById('$this->sb_to'));\n".
                     "  document.getElementById('$this->sb_from').to = document.getElementById('$this->sb_to');\n".
                   //  "  document.getElementById('$this->sb_from').form.onsubmit = function(e){ document.getElementById('$this->name').sb.select_all(document.getElementById('$this->sb_to')); };\n".
                     "  document.getElementById('$this->sb_to').to = document.getElementById('$this->sb_from');\n".
                     "</script>\n";

    $this->buffer .= "<p></p>";
  }

  public function html_render(){
    $html = "";

    if ( $this->error_contents )
     $html .= "<p class=\"formerror\">Erreur : ".$this->error_contents."</p>\n";

    $html .= "<form action=\"$this->action\" method=\"".strtolower($this->method)."\"".
              " name=\"".$this->name."form\" id=\"".$this->name."form\"".
              " onsubmit=\"$this->sb_name.select_all($this->sb_name.to)\">\n";

    foreach ( $this->hiddens as $key => $value )
      $html .= "<input type=\"hidden\" name=\"$key\" value=\"$value\" />\n";

    $html .= "<div class=\"form\">\n";

    $html .= $this->buffer;

    $html .= "<div class=\"clearboth\"></div>\n";
    $html .= "</div>\n";
    $html .= "</form>\n";

    return $html;
  }
}

?>
