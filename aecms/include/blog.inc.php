<?php
/* Copyright 2009
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
 */

/**
 * @file
 */
require_once($topdir."include/cts/cached.inc.php");
require_once($topdir."include/entities/basedb.inc.php");

/**
 * Conteneur de texte structuré
 * @ingroup display_cts
 */
class blogentrycts extends contents
{

  public  $buffer;
  private $date;
  private $auteur;
  private $intro;
  private $content;
  /** Crée un stdcontents d'une entrée de wiki
   * @param $row
   * @param $contents  Texte structuré
   */
  function blogentrycts($id,$auteur,$date,$titre,$intro,$content=false)
  {
    $this->id      = $id;
    $this->titre   = $titre;
    $this->date    = $date;
    $this->auteur  = $auteur;
    $this->intro   = $intro;
    $this->content = $content;
  }

  function html_render()
  {
    setlocale(LC_TIME, "fr_FR", "fr_FR@euro", "fr", "FR", "fra_fra", "fra");
    $this->buffer = '<div class="blog">'."\n";
    $this->buffer.= '<h1>'.$this->titre.'</h1>'."\n";
    $this->buffer.= '<div class="blogentrypubdate">Le '.
                    strftime("%A %d %B %Y à %Hh%M", datetime_to_timestamp($this->date)).
                    '</div>'."\n";
    $this->buffer.= '<div class="blogentryauthor">Par '.
                    $this->auteur.
                    '</div>'."\n";
    $this->buffer.= '<div class="blogentryintro">'.doku2xhtml($this->intro).'</div>'."\n";
    if( !$this->content )
      $this->buffer.= '<div class="blogentryreadmore"><a href="?id_entry='.$this->id.'">Lire la suite</a></div>'."\n";
    else
      $this->buffer.= '<div class="blogentrycontent">'.doku2xhtml($this->content).'</div>'."\n";
    return $this->buffer."</div>\n";
  }
}

/**
 * Blog pour les aecms.
 *
 * @author Simon Lopez
 */
class blog extends basedb
{
  public    $id      = null;
  protected $id_asso = null;
  private   $sub_id  = null;
  private   $cats    = null;

  /**
   * Charge un blog par id
   * @param $id id du blog
   * @return boolean success
   */
  public function load_by_id ( $id )
  {
    $req = new requete($this->db,
                       "SELECT * ".
                       "FROM `aecms_blog` ".
                       "WHERE `id_blog`='".intval($id)."' ");
   if($req->lines!=1)
     return false;

   $this->_load($req->get_row());
   return true;
  }

  /**
   * Charge le blog
   * @return boolean success
   */
  public function load(&$asso,$subid=false)
  {
    if(!$asso->is_valid())
      return false;
    if(!$subid || is_null($subid))
      $subid='';
    $req = new requete($this->db,
                       "SELECT * ".
                       "FROM `aecms_blog` ".
                       "WHERE `id_asso`='".$asso->id."' ".
                       "AND `sub_id`='".mysql_real_escape_string($subid)."'");
    if($req->lines!=1)
      return false;

    $this->_load($req->get_row());
    return true;
  }

  /**
   * Charge les données du blog
   */
  public function _load ( $row )
  {
    $this->id      = $row['id_blog'];
    $this->id_asso = $row['id_asso'];
    $this->sub_id  = $row['sub_id'];
  }

  /**
   * Crée un blog
   * @param $asso un objet asso valide
   * @param $subid string de l'id secondaire du aecms
   * @return boolean success
   */
  public function create (&$asso,$subid='')
  {
    if(!$asso->is_valid())
      return false;
    if( $this->load($asso,$subid) )
      return true;
    $req = new insert($this->dbrw,
                      'aecms_blog',
                      array('id_asso'=>$asso->id,
                            'sub_id'=>$subid));
    if(!$req->is_success())
      return false;
    $this->id     = $req->get_id();
    $this->asso   = &$asso;
    $this->sub_id = mysql_real_escape_string($subid);
    return true;
  }

  /**
   * Supprime entièrement un blog
   * @return boolean success
   */
  public function delete()
  {
    if(!$this->is_valid())
      return false;
    $req = new requete($this->db,
                       "SELECT `id_entry` ".
                       "FROM `aecms_blog_entries` ".
                       "WHERE `id_blog`='".$this->id."'");
    if($req->lines>0)
    {
      while(list($id)=$req->get_row())
      {
        $cache = new cachedcontents("aecmsblog_".$this->id."_".intval($id));
        $cache->expire();
        $cache = new cachedcontents("aecmsblog_".$this->id."_".intval($id)."_intro");
        $cache->expire();
      }
      //suppression des messages
      new delete($this->dbrw,
                 "aecms_blog_entries",
                 array('id_blog'=>$this->id));
      new delete($this->dbrw,
                 "aecms_blog_entries_comments",
                 array('id_blog'=>$this->id));
    }
    //suppression des auteurs
    new delete($this->dbrw,
               "aecms_blog_writers",
               array('id_blog'=>$this->id));
    //suppression des catégories
    new delete($this->dbrw,
               "aecms_blog_cat",
               array('id_blog'=>$this->id));
    //supression du blog
    new delete($this->dbrw,
               "aecms_blog",
               array('id_blog'=>$this->id));
    $this->id      = null;
    $this->cats    = null;
    $this->id_asso = null;
    return true;
  }

  /**
   * Charge les catégories du blog
   * @return boolean success
   */
  private function load_cats()
  {
    if(!$this->is_valid())
      return false;
    if(is_array($this->cats))
      return true;
    $req = new requete($this->db,
                       "SELECT `id_cat`, `cat_name` ".
                       "FROM `aecms_blog_cat` ".
                       "WHERE `id_blog`='".$this->id."'".
                       "ORDER BY `cat_name` ASC");
    $this->cats=array();
    while(list($id,$name)=$req->get_row())
      $this->cats[$id]=$name;
    return true;
  }

  /**
   * indique si une catégorie existe
   * @param $id identifiant de la catégorie
   * @return vrai si oui, sinon faux
   */
  public function is_cat($id)
  {
    if ( !$this->is_valid() )
      return array();
    $this->load_cats();
    return isset($this->cats[$id]);
  }

  /**
   * Retourne la liste des catégories
   * @return array(id=>nom)
   */
  public function get_cats()
  {
    if ( !$this->is_valid() )
      return array();
    $this->load_cats();
    return $this->cats;
  }

  /**
   * Retourne les catégories sous forme de sqltable
   * @param $page Page qui va être la cible des actions
   * @param $actions actions sur chaque objet (envoyé à %page%?action=%action%&%id_utilisateur%=[id])
   * @param $batch_actions actions possibles sur plusieurs objets (envoyé à page, les id sont le tableau %id_utilisateur%s)
   * @return contents
   */
  public function get_cats_cts($page,$actions=array(),$batch_actions=array())
  {
    if ( !$this->is_valid() )
      return new contents("Catégories");
    $this->load_cats();
    if( empty($this->cats) )
      return new contents("Catégories");
    foreach($this->cats as $id => $cat)
      $cats[]=array('id_cat'=>$id,'cat'=>$cat);
    $tbl = new sqltable(
         'listcatsblog',
          'Catégories',
          $cats,
          $page,
          'id_cat',
          array("cat"=>"Catégorie"),
          $actions,
          $batch_actions);
    return $tbl;
  }

  /**
   * Retourne les catégories sous forme d'itemlist
   * @param $page Page qui va être la cible des actions
   * @param $current_id catégorie courante (defaut : null)
   * @return contents
   */
  public function get_cats_cts_list($page,$current_id=null)
  {
    if ( !$this->is_valid() )
      return false;
    $this->load_cats();
    if( empty($this->cats) )
      return false;
    $list = new itemlist('Catégories','blogcatlist');
    if ( strstr($page,"?"))
      $page.='&id_cat=';
    else
      $page.='?id_cat=';
    $i=0;
    foreach($this->cats as $id => $cat)
    {
      $selected='';
      if($id==$current_id)
        $selected=' blogcatselected';
      $list->add('<a href="'.$page.$id.'">'.$cat.'</a>','blogcatlist'.$i.$selected);
      $i=($i+1)%2;
    }
    return $list;
  }

  /**
   * ajoute une catégorie
   * @param $cat string nom de la catégorie
   * @return boolean success
   */
  public function add_cat($name)
  {
    if ( !$this->is_valid() )
      return false;
    $this->load_cats();
    if (in_array($name,$this->cats) )
      return true;
    $req = new insert($this->dbrw,
                      "aecms_blog_cat",
                      array('id_blog'=>$this->id,
                            'cat_name'=>mysql_real_escape_string($name)));
    if ( !$req->is_success() )
      return false;
    $this->cats[$req->get_id()]=$name;
    return true;
  }

  /**
   * supprime une catégorie
   * @param $id id de la catégorie
   * @return boolean success
   */
  public function del_cat($id)
  {
    if ( !$this->is_valid() )
      return false;
    $this->load_cats();
    if( !isset($this->cats[$id]) )
      return false;
    new update($this->dbrw,
               "aecms_blog_entries",
               array('id_cat'=>null),
               array('id_blog'=>$this->id,
                     'id_cat'=>intval($id)));
    $req = new delete($this->dbrw,
                      "aecms_blog_cat",
                      array('id_blog'=>$this->id,
                            'id_cat'=>intval($id)));
    if ( ! $req->is_success() )
      return false;
    unset($this->cats[$id]);
    return true;
  }

  /**
   * Détermine si un utilisateur est administrateur du blog
   * @param $user un objet de type utilisateur
   * @return vrai si admin sinon faux
   */
  public function is_admin ( &$user )
  {
    if ( !$this->is_valid() )
      return false;
    global $site;
    if(!$site->asso->is_member_role($user->id,ROLEASSO_MEMBREBUREAU)
       && !$user->is_in_group("root") )
      return false;
    return true;
  }

  /**
   * Détermine si un utilisateur est blogueur
   * @param $user un objet de type utilisateur
   * @return vrai si blogueur ou admin sinon faux
   */
  public function is_writer ( &$user )
  {
    if( !$user->is_valid() )
      return false;
    if ( $this->is_admin($user) )
      return true;
    $req = new requete($this->db,
                       "SELECT `id_utilisateur` ".
                       "FROM `aecms_blog_writers` ".
                       "WHERE `id_blog`='".$this->id."' ".
                       "AND `id_utilisateur`='".$user->id."' ");
    if($req->lines==1)
      return true;
    return false;
  }

  /**
   * ajoute un blogueur
   * @param $user un objet de type utilisateur
   * @return boolean success
   */
  public function add_writer(&$user)
  {
    if ( !$this->is_valid() )
      return false;
    if ( !$user->is_valid() )
      return false;
    if ( $this->is_admin($user) )
      return true;
    $req = new insert($this->dbrw,
                      "aecms_blog_writers",
                      array('id_blog'=>$this->id,
                            'id_utilisateur'=>$user->id));
    return $req->is_success();
  }

  /**
   * supprime un blogueur
   * @param $user un objet de type utilisateur
   * @return boolean success
   */
  public function del_writer(&$user)
  {
    if ( !$this->is_valid() )
      return false;
    if ( !$user->is_valid() )
      return false;
    $req = new delete($this->dbrw,
                      "aecms_blog_writers",
                      array('id_blog'=>$this->id,
                            'id_utilisateur'=>$user->id));
    return $req->is_success();
  }

  /**
   * Retourne la liste des blogueurs sous forme de sqltable
   * @param $page Page qui va être la cible des actions
   * @param $actions actions sur chaque objet (envoyé à %page%?action=%action%&%id_utilisateur%=[id])
   * @param $batch_actions actions possibles sur plusieurs objets (envoyé à page, les id sont le tableau %id_utilisateur%s)
   * @return contents
   */
  public function get_writers_cts($page,$actions=array(),$batch_actions=array())
  {
    global $topdir;
    require_once($topdir. "include/cts/sqltable.inc.php");
    $req = new requete($this->db,
                       "SELECT `aecms_blog_writers`.`id_utilisateur` ".
                       ", CONCAT( `utilisateurs`.`prenom_utl` ".
                       "         ,' ' ".
                       "         ,`utilisateurs`.`nom_utl`) ".
                       "    AS `nom_utilisateur` ".
                       "FROM `aecms_blog_writers` ".
                       "INNER JOIN `utilisateurs` USING(`id_utilisateur`) ".
                       "WHERE `id_blog`='".$this->id."'");
    $tbl = new sqltable(
          'listwritersblog',
          'Blogueurs',
          $req,
          $page,
          'id_utilisateur',
          array("nom_utilisateur"=>"Utilisateur"),
          $actions,
          $batch_actions);
    return $tbl;
  }

  /**
   * ajoute un billet
   * @param $user un objet de type utilisateur (le posteur)
   * @param $titre le titre
   * @param $intro une introduction
   * @param $content le contenu
   * @param $pub boolean publié? (défault vrai)
   * @param $idcat id de la catégorie (défault null)
   * @param $date timestamp de publication (défault time())
   * @return boolean success
   */
  public function add_entry(&$user,
                            $titre,
                            $intro,
                            $contenu,
                            $pub=true,
                            $idcat=null,
                            $date=null)
  {
    if( !$this->is_valid() )
      return false;
    if( !$user->is_valid() )
      return false;
    if( !is_null($idcat) && !isset($this->cats[$idcat]) )
      $idcat=null;
    if( is_null($date) )
      $date=time();
    $date=date("Y-m-d H:i:s",$date);
    if($pub)
      $pub='y';
    else
      $pub='n';
    $req = new insert($this->dbrw,
                      "aecms_blog_entries",
                      array('id_blog'=>$this->id,
                            'id_utilisateur'=>$user->id,
                            'id_cat'=>intval($idcat),
                            'date'=>$date,
                            'pub'=>$pub,
                            'titre'=>$titre,
                            'intro'=>$intro,
                            'contenu'=>$contenu));
    if( !$req->is_success() )
      return false;
    return $req->get_id();
  }

  /**
   * modifie un billet
   * @param $id identifiant du billet
   * @param $titre le titre
   * @param $intro une introduction
   * @param $content le contenu
   * @param $pub boolean publié?
   * @param $idcat id de la catégorie
   * @param $date timestamp de publication
   * @return boolean success
   */
  public function update_entry($id,
                               $titre,
                               $intro,
                               $contenu,
                               $pub,
                               $idcat,
                               $date)
  {
    if( !$this->is_valid() )
      return false;
    $req = new requete($this->db,
                       "SELECT `id_entry` ".
                       "FROM `aecms_blog_entries` ".
                       "WHERE `id_blog`='".$this->id."' ".
                       "AND `id_entry`='".intval($id)."'");
    if( $req->lines!=1 )
      return false;
    if( is_null($date) )
      $date=time();
    $date=date("Y-m-d H:i:s",$date);
    if($pub)
      $pub='y';
    else
      $pub='n';
    $req = new update($this->dbrw,
                      "aecms_blog_entries",
                      array('id_cat'=>intval($idcat),
                            'date'=>$date,
                            'pub'=>$pub,
                            'titre'=>$titre,
                            'intro'=>$intro,
                            'contenu'=>$contenu),
                      array('id_blog'=>$this->id,
                            'id_entry'=>intval($id)));
    $cache = new cachedcontents("aecmsblog_".$this->id."_".intval($id));
    $cache->expire();
    $cache = new cachedcontents("aecmsblog_".$this->id."_".intval($id)."_intro");
    $cache->expire();
    return $req->is_success();
  }

  /**
   * supprime un billet
   * @param $id identifiant du billet
   * @return boolean success
   */
  public function delete_entry($id)
  {
    if( !$this->is_valid() )
      return false;
    $req = new delete($this->dbrw,
                      "aecms_blog_entries",
                      array('id_blog'=>$this->id,
                            'id_entry'=>intval($id)));
    new delete($this->dbrw,
                 "aecms_blog_entries_comments",
                 array('id_blog'=>$this->id,
                       'id_entry'=>intval($id)));
    $cache = new cachedcontents("aecmsblog_".$this->id."_".intval($id));
    $cache->expire();
    $cache = new cachedcontents("aecmsblog_".$this->id."_".intval($id)."_intro");
    $cache->expire();
    return $req->is_success();
  }

  /**
   * publie un billet
   * @param $id identifiant du billet
   * @return boolean success
   */
  public function publish_entry($id)
  {
    if( !$this->is_valid() )
      return false;
    $req = new update($this->dbrw,
                      "aecms_blog_entries",
                      array('pub'=>'y'),
                      array('id_blog'=>$this->id,
                            'id_entry'=>intval($id)));
    $cache = new cachedcontents("aecmsblog_".$this->id."_".intval($id));
    $cache->expire();
    $cache = new cachedcontents("aecmsblog_".$this->id."_".intval($id)."_intro");
    $cache->expire();
    return $req->is_success();
  }

  /**
   * Retourne un contents d'un billet
   * @param $id identifiant du billet
   * @return array
   */
  public function get_entry_row($id)
  {
    $req = new requete($this->db,
                       "SELECT * ".
                       "FROM `aecms_blog_entries` ".
                       "WHERE `id_blog`='".$this->id."' ".
                       "AND `id_entry`='".intval($id)."'");
    if($req->lines==0)
      return false;
    return $req->get_row();
  }

  /**
   * Retourne un contents d'un billet
   * @param $id identifiant du billet
   * @param $user utilisateur souhaitant accéder au billet
   * @return contents
   */
  public function get_cts_entry($id,&$user)
  {
    global $_REQUEST;
    $lim = "AND `pub`='y' AND `date` < NOW()";
    if( $this->is_writer($user) )
      $lim = '';
    $req = new requete($this->db,
                       "SELECT `id_entry` ".
                       ",`id_utilisateur` ".
                       ",`date` ".
                       ",`titre` ".
                       ",`intro` ".
                       ",`contenu` ".
                       ",`id_cat` ".
                       "FROM `aecms_blog_entries` ".
                       "WHERE `id_blog`='".$this->id."' ".
                       "AND `id_entry`='".intval($id)."' ".
                       $lim);
    if($req->lines==0)
      return new contents('Billet non trouvé');
    list($id_entry,$utl,$date,$titre,$intro,$content,$id_cat)=$req->get_row();
    $_REQUEST['id_cat']=$id_cat;
    $cache = new cachedcontents("aecmsblog_".$this->id."_".$id_entry);
    if ( $cache->is_cached() )
      return $cache->get_cache();
    $user = new utilisateur($this->db);
    if( !$user->load_by_id($utl) )
      $auteur = 'Annonyme';
    else
      $auteur = $user->get_display_name();
    $cts = new blogentrycts($id_entry,$auteur,$date,$titre,$intro,$content);
    $cache->set_contents($cts);
    return $cache->get_cache();
  }

  /**
   * Poste un commentaire
   * @param $id id du billet
   * @param $nom nom du posteur
   * @param $comment texte du commentaire
   * @return boolean success
   */
  private function comment($id,$nom,$comment)
  {
    if(empty($comment) || empty($nom))
      return;
//    $comment = preg_replace('#\\\\\\\\(\s)#',"<br />\\1",$comment);
    $comment = htmlspecialchars($comment);
    $comment = str_replace("\n","<br />",$comment);
    $req = new insert($this->dbrw,
                      'aecms_blog_entries_comments',
                      array('id_blog'=>$this->id,
                            'id_entry'=>intval($id),
                            'date'=>date("Y-m-d H:i:s",time()),
                            'nom'=>htmlspecialchars($nom),
                            'comment'=>$comment));
    return $req->is_success();
  }

  /**
   * Supprime un commentaire
   * @param id_entry id du billet
   * @param id_comment id du commentaire
   * @return boolean success
   */
  private function delete_comment($id_entry,$id_comment)
  {
    $req = new delete($this->dbrw,
                 "aecms_blog_entries_comments",
                 array('id_blog'=>$this->id,
                       'id_entry'=>intval($id_entry),
                       'id_comment'=>intval($id_comment)));
    return $req->is_success();
  }

  /**
   * Retourne un content avec les commentaires d'un billet
   * et le formulaire de commentaire
   * @param $id identifiant du billet
   * @param $user un objet de type utilisateur (defaut null)
   * @return contents
   */
  public function cts_comments($id, &$user=null)
  {
    $admin = $this->is_writer($user);
    /**
     * on supprime les commenataires
     */
    if(isset($_REQUEST['id_comment']) && $admin)
      $this->delete_comment($id,$_REQUEST['id_comment']);
    /**
     * On enregistre aussi les commentaires valides
     */
    $math=false;
    if(is_null($user) || !$user->is_valid())
    {
      $first  = (rand()%20);
      $second = (rand()%20);
      $math=true;
    }
    if(isset($_REQUEST['action'])
       && $_REQUEST['action']=='comment'
       && $GLOBALS["svalid_call"]
       && !empty($_REQUEST['comment']))
    {
      if(isset($_REQUEST['__math_first'])
         && isset($_REQUEST['__math_second'])
         && !empty($_REQUEST['nom'])
         && !empty($_REQUEST['prenom'])
         )
      {
        $name = convertir_prenom($_REQUEST['prenom']).
                " ".
                convertir_nom($_REQUEST['nom']);
        if(($_REQUEST['__math_first']+$_REQUEST['__math_second'])==$_REQUEST['__math_result'])
          $this->comment($id,$name,$_REQUEST['comment']);
      }
      elseif(!$math)
      {
          $this->comment($id,
                         $user->get_display_name(),
                         $_REQUEST['comment']);
      }
    }
    $math=false;
    if(is_null($user) || !$user->is_valid())
    {
      $first  = (rand()%20);
      $second = (rand()%20);
      $math=true;
    }
    $cts = new contents(false,'<div class="article blogcomments">'."\n");
    $cts->puts('<h1>Commentaires</h1>'."\n");
    $frm = new form('comment',
                    'blog.php',
                    false,
                    'POST',
                    'Ajouter un commentaire');
    $frm->allow_only_one_usage();
    $frm->add_hidden('id_entry',$id);
    $frm->add_hidden('action','comment');
    if($math)
    {
      $frm->add_text_field('nom','Nom','',true);
      $frm->add_text_field('prenom','Prénom','',true);
      $frm->add_info('Aucune mise en forme !');
      $frm->add_text_area("comment","Commentaire",$billet['intro'],40,5,true);
      $frm->add_hidden('__math_first',$first);
      $frm->add_hidden('__math_second',$second);
      $frm->add_info('Filtre anti spam, merci d\'effectuer cette petite opération :');
      $frm->add_text_field('__math_result',$first.' + '.$second,'',true);
    }
    else
    {
      $frm->add_hidden('id_utilisateur',$user->id);
      $frm->add_text_area("comment","Commentaire",$billet['intro'],40,5,true);
    }
    $frm->add_submit("submit","Commenter");
    if(!$math)
      $cts->add($frm,true);

    $req = new requete($this->db,
                       'SELECT `id_comment` '.
                       ', `date` '.
                       ',`nom` '.
                       ',`comment` '.
                       'FROM `aecms_blog_entries_comments` '.
                       'WHERE `id_blog`=\''.$this->id.'\' '.
                       'AND `id_entry`=\''.intval($id).'\' '.
                       'ORDER BY `date` ASC');
    setlocale(LC_TIME, "fr_FR", "fr_FR@euro", "fr", "FR", "fra_fra", "fra");
    $i=0;
    $del='';
    while(list($id_com,$date,$nom,$comment)=$req->get_row())
    {
      if($admin)
        $del=' (<a href="blog.php?id_entry='.$id.'&id_comment='.$id_com.'">Supprimer</a>)';
      $cts->add(new contents("Par ".$nom. " le ".
                             strftime("%A %d %B %Y à %Hh%M",
                                      datetime_to_timestamp($date)).
                             $del,
                             "<div class='blogcomment$i'>".$comment."</div>"),true);
      $i=($i+1)%2;
    }
    $cts->puts('</div>'."\n");
    return $cts;
  }

  /**
   * Retourne le nombre de commentaires d'un billet
   * @param $id identifiant du billet
   * @return int le nombre de commentaires
   */
  public function num_comment($id)
  {
    $req = new requete($this->db,
                       'SELECT COUNT(*) '.
                       'FROM `aecms_blog_entries_comments` '.
                       'WHERE `id_blog`=\''.$this->id.'\' '.
                       'AND `id_entry`=\''.intval($id).'\'');
    list($nb)=$req->get_row();
    return $nb;
  }

  /**
   * Retourne les billets en attente sous forme de sqltable
   * @param $page Page qui va être la cible des actions
   * @param $actions actions sur chaque objet (envoyé à %page%?action=%action%&%id_utilisateur%=[id])
   * @param $batch_actions actions possibles sur plusieurs objets (envoyé à page, les id sont le tableau %id_utilisateur%s)
   * @return contents
   */
  public function get_cts_waiting_entries($page,$actions=array(),$batch_actions=array())
  {
    global $topdir;
    require_once($topdir. "include/cts/sqltable.inc.php");
    $req = new requete($this->db,
                       "SELECT `id_entry` ".
                       ",`id_utilisateur` ".
                       ",`date` ".
                       ",`titre` ".
                       ", CONCAT( `prenom_utl` ".
                       "         ,' ' ".
                       "         ,`nom_utl`) ".
                       "    AS `nom_utilisateur` ".
                       "FROM `aecms_blog_entries` ".
                       "INNER JOIN `utilisateurs` USING(`id_utilisateur`) ".
                       "WHERE `id_blog`='".$this->id."' ".
                       "AND (`pub`='n' OR `date` > NOW()) ".
                       "ORDER BY `titre` ASC");
    $tbl = new sqltable(
          'listwaitingentriesblog',
          'Billets en attente de publication',
          $req,
          $page,
          'id_entry',
          array("titre"=>"Titre",
                "date"=>"Date",
                "nom_utilisateur"=>"Blogguer"),
          $actions,
          $batch_actions);
    return $tbl;
  }

  /**
   * Retourne un contents des billets d'une cattégorie
   * @param $id identifiant du billet
   * @param $page numéro de la page à afficher
   * @return contents
   */
  public function get_cts_cat($id,$page=0)
  {
    $begin = 10*intval($page);
    $end   = 10+10*intval($page);
    $this->load_cats();
    if (!isset($this->cats[$id]) )
      return $this->get_cts();
    $cts = new contents("Catégorie ".$this->cats[$id]);
    $req = new requete($this->db,
                       "SELECT COUNT(*) ".
                       "FROM `aecms_blog_entries` ".
                       "WHERE `id_blog`='".$this->id."' ".
                       "AND `id_cat`='".intval($id)."' ".
                       "AND `pub`='y' ".
                       "AND `date` < NOW()");
    list($total)=$req->get_row();
    if($total==0)
    {
      $cts->add_paragraph("Il n'y a aucun billet dans cette catégorie.","blogempty");
      return $cts;
    }
    if( $begin>=$total )
    {
      $page=0;
      $begin=0;
    }
    $end = $begin+10;
    if($begin>0)
      $cts->puts("<div class='blogprevious blognavtop'><a href='?id_cat=".
                 $id."&id_page=".($page-1)."'>Billets précédents</a></div>");
    if($end<$total)
      $cts->puts("<div class='blognext blognavtop'><a href='?id_cat=".$id.
                 "&id_page=".($page+1)."'>Billets suivants</a></div>");
    $req = new requete($this->db,
                       "SELECT `id_entry` ".
                       ",`id_utilisateur` ".
                       ",`date` ".
                       ",`titre` ".
                       ",`intro` ".
                       "FROM `aecms_blog_entries` ".
                       "WHERE `id_blog`='".$this->id."' ".
                       "AND `id_cat`='".intval($id)."' ".
                       "AND `pub`='y' ".
                       "ORDER BY `date` DESC ".
                       "LIMIT ".$begin.",".$end."");
    $user = new utilisateur($this->db);
    while(list($id,$utl,$date,$titre,$intro)=$req->get_row())
    {
      $cache = new cachedcontents("aecmsblog_".$this->id."_".intval($id)."_intro");
      if ( !$cache->is_cached() )
      {
        if( !$user->load_by_id($utl) )
          $auteur = 'Annonyme';
        else
          $auteur = $user->get_display_name();
        $cache->set_contents(new blogentrycts($id,$auteur,$date,$titre,$intro));
      }
      $cts->add($cache->get_cache(),true);
    }
    if($begin>0)
      $cts->add_paragraph("<div class='blogprevious blognavbottom'><a href='?id_cat=".
                          $id."&id_page=".($page-1)."'>Billets précédents</a></div>");
    if($end<$total)
      $cts->add_paragraph("<div class='blognext blognavbottom'><a href='?id_cat=".
                          $id."&id_page=".($page+1)."'>Billets suivants</a></div>");
    return $cts;
  }

  /**
   * Retourne un contents de tous les billets
   * @param $page numéro de la page à afficher
   * @return contents
   */
  public function get_cts($page=0)
  {
    $begin = 10*intval($page);
    $end   = 10+10*intval($page);
    $cts = new contents();
    $req = new requete($this->db,
                       "SELECT COUNT(*) ".
                       "FROM `aecms_blog_entries` ".
                       "WHERE `id_blog`='".$this->id."' ".
                       "AND `pub`='y' ");
    list($total)=$req->get_row();
    if($total==0)
    {
      $cts->add_paragraph("Il n'y a pas encore billet.",'blogempty');
      return $cts;
    }
    if( $begin>=$total )
    {
      $page=0;
      $begin=0;
    }
    $end = $begin+10;
    if($begin>0)
      $cts->puts("<div class='blogprevious blognavtop'><a href='?id_cat=".
                 $id."&id_page=".($page-1)."'>Billets précédents</a></div>");
    if($end<$total)
      $cts->puts("<div class='blognext blognavtop'><a href='?id_cat=".$id.
                 "&id_page=".($page+1)."'>Billets suivants</a></div>");
    $req = new requete($this->db,
                       "SELECT `id_entry` ".
                       ",`id_utilisateur` ".
                       ",`date` ".
                       ",`titre` ".
                       ",`intro` ".
                       "FROM `aecms_blog_entries` ".
                       "WHERE `id_blog`='".$this->id."' ".
                       "AND `pub`='y' ".
                       "AND `date` < NOW() ".
                       "ORDER BY `date` DESC ".
                       "LIMIT ".$begin.",".$end."");
    $user = new utilisateur($this->db);
    while(list($id,$utl,$date,$titre,$intro)=$req->get_row())
    {
      $cache = new cachedcontents("aecmsblog_".$this->id."_".intval($id)."_intro");
      if ( !$cache->is_cached() )
      {
        if( !$user->load_by_id($utl) )
          $auteur = 'Annonyme';
        else
          $auteur = $user->get_display_name();
        $cache->set_contents(new blogentrycts($id,$auteur,$date,$titre,$intro));
      }
      $cts->add($cache->get_cache(),true);
    }
    if($begin>0)
      $cts->puts("<div class='blogprevious blognavbottom'><a href='?id_cat=".
                 $id."&id_page=".($page-1)."'>Billets précédents</a></div>");
    if($end<$total)
      $cts->puts("<div class='blognext blognavbottom'><a href='?id_cat=".
                 $id."&id_page=".($page+1)."'>Billets suivants</a></div>");
    return $cts;
  }
}

?>
