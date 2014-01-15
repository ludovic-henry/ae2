<?
/* Copyright 2007
 * - Simon Lopez < simon DOT lopez AT ayolo DOT org >
 *
 * Ce fichier fait partie du site de l'Association des étudiants de
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

class planet extends stdentity
{

  function planet( &$site )
  {
    $this->site=&$site;
  }

  function get_tabs()
  {
    if($this->site->user->is_in_group("moderateur_site"))
    {
      return array(array("","planet/index.php", "Planet"),
                   array("perso","planet/index.php?view=perso", "Personnaliser"),
                   array("add","planet/index.php?view=add", "Proposer"),
                   array("modere","planet/index.php?view=modere", "Modération")
                  );
    }
    else
    {
      return array(array("","planet/index.php", "Planet"),
                   array("perso","planet/index.php?view=perso", "Personnaliser"),
                   array("add","planet/index.php?view=add", "Proposer")
                  );
    }
  }

  /* je sais c'est sale, mais le temps de faire plus proprement ...*/
  function load_by_id($id)
  {
  }

  function _load($var)
  {
  }

  function delete($id_flux)
  {
    $sql="";
    if(!$this->site->user->is_in_group("moderateur_site"))
      $sql="AND `id_utilisateur`='".$this->site->user->id."'";
    $req = new requete($this->site->db,"SELECT `id_flux` FROM `planet_flux` WHERE `id_flux`='".$id_flux."' ".$sql." LIMIT 1");
    if($req->lines==1)
    {
      $_req = new delete($this->site->dbrw, "planet_flux", array("id_flux" => $id_flux));
      $_req = new delete($this->site->dbrw, "planet_flux_tags", array("id_flux" => $id_flux));
      $_req = new delete($this->site->dbrw, "planet_user_flux", array("id_flux" => $id_flux));
    }
  }

  function add_flux($url,$nom,$tags=array())
  {
    $req = new requete($this->site->db,"SELECT `id_flux` FROM `planet_flux` WHERE `url`='".$url."' LIMIT 1");
    if($req->lines==1)
    {
      return "Le flux \"".$url."\" est déjà présent.";
    }
    else
    {
      $_req = new insert($this->site->dbrw,"planet_flux", array('url'=>$url,'nom'=>$nom,'id_utilisateur' => $this->site->user->id,'modere'=>0));
      if(!empty($tags))
      {
        $fluxid=$_req->get_id();
        $req = new requete($this->site->db,"SELECT `id_tag` FROM `planet_tags`");
        while ( list($id) = $req->get_row() )
          if(isset($tags[$id]))
            $_req = new insert($this->site->dbrw,"planet_flux_tags", array('id_flux'=>$fluxid,'id_tag'=>$id));
      }
      return "Le flux \"".$nom."\" (".$url.") a bien été ajouté.";
    }
  }

  function add_tag($tag)
  {
    $req = new requete($this->site->db,"SELECT `id_tag` FROM `planet_tags` WHERE `tag`='".strtoupper($tag)."' LIMIT 1");
    if($req->lines==1)
      return "Le tag \"".strtoupper($_REQUEST["tag"])."\" existe déjà.";
    else
    {
      $_req = new insert($this->site->dbrw,"planet_tags", array('tag'=>strtoupper($tag),'modere'=>0));
      return "Le tag \"".strtoupper($_REQUEST["tag"])."\" a été ajouté.";
    }
  }

}

