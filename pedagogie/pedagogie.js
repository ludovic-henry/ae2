/**
 * Copyright 2008
 * - Manuel Vonthron  <manuel DOT vonthron AT acadis DOT org>
 *
 * Ce fichier fait partie du site de l'Association des Étudiants de
 * l'UTBM, http://ae.utbm.fr/
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

var edt = {
  add_uv_seance: function(id, type, semestre, calling){
    url = 'uv_groupe.php?action=new&id_uv='+id+'&type='+type+'&semestre='+semestre+'&mode=popup&calling='+calling;
    win = window.open(url, "add_seance_"+id+"_"+type, "toolbar=no,location=no,menubar=no,width=600,height=350");
  },

  disp_freq_choice: function(elemid, freq, uvid, type){
    e = document.getElementById(elemid);
    if(!e)  return;

    if(freq != 2)
      e.innerHTML = "";
    else
      e.innerHTML = "<select name=\"freq_"+uvid+"_"+type+"\">  \
                      <option value=\"A\">Semaine A</option> \
                      <option value=\"B\">Semaine B</option> \
                     </select>";
  },

  /* pour l instant juste une redirection */
  add: function(path){
    if (typeof path == "undefined")
      path = "";
    document.location.href=path+"edt.php?action=new" ;
  },
  add_auto: function(path){
    if (typeof path == "undefined")
      path = "";
    document.location.href=path+"edt.php?action=new&method=auto" ;
  },

  select_uv: function(optionelt){
  },

  remove: function(elemid){
    o = document.getElementById(elemid);
    o.parentNode.removeChild(o);
  },


  /**
   * recupere un arbre json pour mettre a jour les seances d'une UV
   * data = [{
   *          'id':42,
   *          'value':'pouet',
   *          'content':'a afficher',
   *          'style':'boldmoncul',
   *          'onclick':'payetachatte()'
   *         },
   *         {...}
   *        ]
   */
  refresh_list: function(selectid){
    //xhr =
  },

  /**
   * remplacement d'une boite de seance d'une UV par une autre pour les alias
   */
  switch_boxes: function(elemid, uvid, sem){
    openInContents(elemid, 'edt.php', 'action=get_uv_edt_box&id_uv='+uvid+'&semestre='+sem);
  }
}
