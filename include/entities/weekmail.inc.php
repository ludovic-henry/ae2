<?php
/* Copyright 2009
 * - Simon Lopez <simon DOT lopez AT ayolo DOT org >
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

require_once($topdir.'include/entities/files.inc.php');
require_once($topdir.'include/entities/asso.inc.php');
require_once($topdir.'include/lib/weekmail_parser.inc.php');
/**
 * @ingroup stdentity
 * @author Simon Lopez
 *
 */
class weekmail extends stdentity
{

  public $id           = null;
  public $statut       = 0;
  public $date         = null;
  public $titre        = null;
  public $introduction = null;
  public $conclusion   = null;
  public $blague       = null;
  public $astuce       = null;
  public $id_header    = null;
  public $rendu_html   = null;
  public $rendu_txt    = null;

  public function __get($property)
  {
    if(isset($this->$property))
      return $this->$property;
    throw new Exception('Propriété invalide !');
  }

  public function __set($property,$value)
  {
    if(!$this->is_valid())
      return;
    if($property == 'id')
      throw new Exception('Il n\'est pas possible de redéfinir l\'id !');
    elseif(isset($this->$param))
    {
      $this->$property = $value;
      $this->save();
    }
    else
      throw new Exception('Propriété ou valeur invalide !');
  }

  /* chargement du weekmail encore ouvert à l'ajout de
   * nouvelles
   */
  public function load_latest_not_sent()
  {
    $req = new requete($this->db,
                       'SELECT * '.
                       'FROM `weekmail` '.
                       'WHERE `statut_weekmail`=\'0\' '.
                       'ORDER BY `id_weekmail` DESC '.
                       'LIMIT 1');
    if($req->lines==1)
      return $this->_load($req->get_row());
    return false;
  }

  /* chargement du weekmail le plus ancien en attente d'envoi
   * c'est à dire qui est soit le seul en attente d'envoie soit
   * l'avant dernier (cas où on a fermé l'ajout de nouvelles).
   */
  public function load_first_not_sent()
  {
    $req = new requete($this->db,
                       'SELECT * '.
                       'FROM `weekmail` '.
                       'WHERE `statut_weekmail`=\'0\' '.
                       'ORDER BY `id_weekmail` ASC '.
                       'LIMIT 1');
    if($req->lines==1)
      return $this->_load($req->get_row());
    return false;
  }

  public function load_latest_sent()
  {
    $req = new requete($this->db,
                       'SELECT * '.
                       'FROM `weekmail` '.
                       'WHERE `statut_weekmail`=\'1\' '.
                       'ORDER BY `id_weekmail` DESC '.
                       'LIMIT 1');
    if($req->lines==1)
      return $this->_load($req->get_row());
    return false;
  }

  public function can_create_new()
  {
    $req = new requete($this->db,
                       'SELECT * '.
                       'FROM `weekmail` '.
                       'WHERE `statut_weekmail`=\'0\' '.
                       'ORDER BY `id_weekmail` DESC '.
                       'LIMIT 2');
    if($req->lines==0 || $req->lines==1)
      return true;
    return false;
  }

  public function load_by_id($id)
  {
    $req = new requete($this->db,
                       'SELECT * '.
                       'FROM `weekmail` '.
                       'WHERE `id_weekmail`=\''.mysql_real_escape_string($id).'\'');
    if($req->lines==1)
      return $this->_load($req->get_row());
    return false;
  }

  public function create($titre_weekmail)
  {
    $req = new insert($this->dbrw,
                      'weekmail',
                      array('titre_weekmail'=>$titre_weekmail));
    if(!$req->is_success())
      return false;

    $this->id     = $req->get_id();
    $this->titre  = $titre_weekmail;
    return true;
  }

  public function _load($row)
  {
    $this->id           = $row['id_weekmail'];
    $this->statut       = $row['statut_weekmail'];
    $this->date         = $row['date_weekmail'];
    $this->titre        = $row['titre_weekmail'];
    $this->introduction = $row['intro_weekmail'];
    $this->conclusion   = $row['conclusion_weekmail'];
    $this->blague       = $row['blague_weekmail'];
    $this->id_header    = $row['id_file_header_weekmail'];
    $this->rendu_html   = $row['rendu_html_weekmail'];
    $this->rendu_txt    = $row['rendu_txt_weekmail'];
    $this->astuce       = $row['astuce_weekmail'];
    return true;
  }

  public function save()
  {
    new update($this->dbrw,
               'weekmail',
                array('statut_weekmail'=>$this->statut,
                      'date_weekmail'=>$this->date,
                      'titre_weekmail'=>$this->titre,
                      'intro_weekmail'=>$this->introduction,
                      'conclusion_weekmail'=>$this->conclusion,
                      'blague_weekmail'=>$this->blague,
                      'id_file_header_weekmail'=>$this->id_header,
                      'rendu_html_weekmail'=>$this->id_header,
                      'rendu_txt_weekmail'=>$this->rendu_txt),
                array('id_weekmail'=>$this->id));
  }

  public function set_header($id_file)
  {
    if(!$this->is_valid())
      return;
    $this->id_header = intval($id_file);
    new update($this->dbrw,
               'weekmail',
                array('id_file_header_weekmail'=>$this->id_header),
                array('id_weekmail'=>$this->id));
  }

  public function set_titre($titre)
  {
    if(!$this->is_valid())
      return;
    $this->titre = $titre;
    new update($this->dbrw,
               'weekmail',
                array('titre_weekmail'=>$this->titre),
                array('id_weekmail'=>$this->id));
  }

  public function set_intro($intro)
  {
    if(!$this->is_valid())
      return;
    $this->introduction = $intro;
    new update($this->dbrw,
               'weekmail',
                array('intro_weekmail'=>$this->introduction),
                array('id_weekmail'=>$this->id));
  }

  public function set_blague($blague)
  {
    if(!$this->is_valid())
      return;
    $this->blague = $blague;
    new update($this->dbrw,
               'weekmail',
                array('blague_weekmail'=>$this->blague),
                array('id_weekmail'=>$this->id));
  }

  public function set_astuce($astuce)
  {
    if(!$this->is_valid())
      return;
    $this->astuce = $astuce;
    new update($this->dbrw,
               'weekmail',
                array('astuce_weekmail'=>$this->astuce),
                array('id_weekmail'=>$this->id));
  }

  public function set_conclusion($conclusion)
  {
    if(!$this->is_valid())
      return;
    $this->conclusion = $conclusion;
    new update($this->dbrw,
               'weekmail',
                array('conclusion_weekmail'=>$this->conclusion),
                array('id_weekmail'=>$this->id));
  }

  public function add_news($id_utl,$id_asso=null,$titre,$content,$modere=0)
  {
    if(!$this->is_valid())
      return false;
    new insert($this->dbrw,
               'weekmail_news',
               array('id_weekmail'=>$this->id,
                     'id_utilisateur'=>$id_utl,
                     'id_asso'=>$id_asso,
                     'titre'=>$titre,
                     'content'=>$content,
                     'modere'=>$modere));
  }

  public function preview_news($id_asso=null,$titre,$content)
  {
    $titre = htmlspecialchars($titre);
    $asso = null;
    if(!is_null($id_asso))
    {
      $req = new requete($this->db,
                         'SELECT '.
                         ' `nom_asso` '.
                         'FROM `asso` '.
                         'WHERE `id_asso`=\''.$id_asso.'\' ');
      if($req->lines==1)
        list($asso)=$req->get_row();
    }
    $buffer  = '<table bgcolor="#333333" width="700px">';
    $buffer .= '<tr><td align="center"><br />';
    $buffer .= '<table bgcolor="#ffffff" width="600" border="0" cellspacing="0" cellpadding="0" align="center">';
    if(!is_null($asso))
      $titre = '['.$asso.'] '.$titre;
    $buffer .= '<tr bgcolor="#00BBFF"><td style="padding:2px 5px 2px 5px"><font color="#ffffff">';
    $buffer .= $titre;
    $buffer .= '</font></td></tr>';
    $buffer .= '<tr><td style="padding:2px 5px 2px 5px">';
    $buffer .= $this->_render_content($content);
    $buffer .= '<br />&nbsp;</td></tr>';
    $buffer .= '</table>';
    $buffer .= '<br />&nbsp;</td></tr>';
    $buffer .= '</table>';
    return $buffer;
  }

  private function _render_content($content)
  {
    $parser = new weekmail_parser();
    return $parser->parse($content);
  }

  public function test_render()
  {
    return $this->_render_html();
  }

  private function _render_html()
  {
    $buffer = '<html><body bgcolor="#333333" width="700px"><table bgcolor="#333333" width="700px">
<tr><td align="center">
<table bgcolor="#ffffff" width="600" border="0" cellspacing="0" cellpadding="0" align="center">
<tr><td width="600"><a href="http://ae.utbm.fr"><img src="http://ae.utbm.fr/d.php?id_file='.$this->id_header.'&action=download" border="0"/></a></td></tr>';
// intro
    $buffer .= '<tr bgcolor="#000000"><td style="padding:2px 5px 2px 5px"><font color="#ffffff">Introduction</font></td></tr>
<tr><td style="padding:2px 5px 2px 5px">'.$this->_render_content($this->introduction).'<br />&nbsp;</td></tr>';
// sommaire
    $buffer .= '<tr bgcolor="#000000"><td style="padding:2px 5px 2px 5px"><font color="#ffffff">Sommaire</font></td></tr>
<tr><td style="padding:2px 5px 2px 5px">
<ul>';
    $req = new requete($this->db,
                       'SELECT '.
                       ' `nom_asso` '.
                       ', `titre` '.
                       ', `content` '.
                       'FROM `weekmail_news` '.
                       'LEFT JOIN `asso` USING(`id_asso`) '.
                       'WHERE `id_weekmail`=\''.$this->id.'\' '.
                       'AND `modere`=\'1\' '.
                       'ORDER BY `rank` ASC');
    while(list($asso,$titre,$content)=$req->get_row())
    {
      if(!is_null($asso))
        $asso = '['.$asso.'] ';
      else
        $asso = '';
      $buffer .= '<li>'.$asso.htmlspecialchars($titre).'</li>';
    }
    $buffer .= '</ul>
</td></tr>';
// news ...
    $req->go_first();
    while(list($asso,$titre,$content)=$req->get_row())
    {
      if(!is_null($asso))
        $titre = '['.$asso.'] '.htmlspecialchars($titre);
      else
        $titre = htmlspecialchars($titre);
      $buffer .= '<tr bgcolor="#00BBFF"><td style="padding:2px 5px 2px 5px"><font color="#ffffff">';
      $buffer .= $titre;
      $buffer .= '</font></td></tr>';
      $buffer .= '<tr><td style="padding:2px 5px 2px 5px">';
      $buffer .= $this->_render_content($content);
      $buffer .= '<br />&nbsp;</td></tr>';
    }
// blague
    if(!is_null($this->blague) && !empty($this->blague))
    {
      $buffer .= '<tr bgcolor="#000000">
<td style="padding:2px 5px 2px 5px"><font color="#ffffff">La blague !</font></td></tr>
<tr><td style="padding:2px 5px 2px 5px">';
      $buffer .= $this->_render_content($this->blague);
      $buffer .= '<br />&nbsp;</td></tr>';
    }
// Le saviez-vous
    if (!is_null ($this->astuce) && !empty($this->astuce)) {
        $buffer .= '<tr bgcolor="#000000"><td style="padding:2px 5px 2px 5px"><font color="#ffffff">Le saviez-vous ?</font></td></tr><tr><td style="padding:2px 5px 2px 5px">';
        $buffer .= $this->_render_content($this->astuce);
        $buffer .= '<br />&nbsp;</td></tr>';
    }
// conclusion
    $buffer .= '<tr bgcolor="#000000"><td style="padding:2px 5px 2px 5px"><font color="#ffffff">Le mot de la fin</font></td></tr>
<tr><td style="padding:2px 5px 2px 5px">';
    $buffer .= $this->_render_content($this->conclusion);
    $buffer .= '<br /></td></tr>';
// fin
    $buffer .= '</table><br />
</td></tr></table>
</body>
</html>';
    return $buffer;
  }

  private function _render_txt()
  {
    $buffer = 'Bonjour,
Pour voir le contenu de ce weekmail, merci de vous rendre à l\'adresse
suivante :
http://ae.utbm.fr/weekmail.php

Cordialement,
L\'AE';
    return $buffer;
  }

  public function send ( )
  {
    if($this->is_sent())
      return false;

    global $topdir;
    require_once($topdir.'include/lib/mailer.inc.php');
    $mailer = new mailer('Association des Etudiants <ae@utbm.fr>',
                         '[weekmail] '.$this->titre);
    $mailer->add_dest(array('etudiants@utbm.fr',
                            //'enseignants@utbm.fr',
                            //'iatoss@utbm.fr',
                            'personnels@utbm.fr',
                            'aude.petit@utbm.fr',
                            'info@ml.aeinfo.net'));
    $this->rendu_html = $this->_render_html();
    $this->rendu_txt  = $this->_render_txt();
    $mailer->set_html($this->rendu_html);
    $mailer->set_plain($this->rendu_txt);
    $mailer->send();
    new update($this->dbrw,
               'weekmail',
               array('statut_weekmail'=>1,
                     'date_weekmail'=>date('Y-m-d'),
                     'rendu_html_weekmail'=>$this->rendu_html,
                     'rendu_txt_weekmail'=>$this->rendu_txt),
               array('id_weekmail'=>$this->id));
    /*new delete($this->dbrw,
               'weekmail_news',
               array('id_weekmail'=>$this->id));*/
    return true;
  }

  public function is_valid( )
  {
    if(!is_null($this->id) && $this->id>0)
      return true;
    return false;
  }

  public function is_sent ( )
  {
    return ($this->is_valid() && $this->statut==1)?true:false;
  }

}


?>
