<?php

/** @file
 *
 * @brief Classe de traduction dokuwiki -> html
 *
 */

/* Copyright 2007,2008
 *
 * - Simon Lopez <simon POINT lopez AT ayolo POINT org>
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


class weekmail_parser
{


   public function weekmail_parser()
   {
   }

   public function parse(&$text)
   {
     return $this->_html_parse($text);
   }

  /**
   * La fonction qui fait tout, tu lui file de la syntaxe wiki
   * elle te pond du xhtml, c'est de la magie en boite de concerve
   * @param $text le texte que tu veux qu'il resorte cossu :p
   */
  function _html_parse(&$text)
  {
    $table   = array();
    $hltable = array();

    //fix : je sais pas comment changer le vin en eau dsl
    $text  = "\n".$text."\n";

    /*les liens (à la base y'en a plein de suportés j'ai fait le ménage
     * ex : telnet, gopher, irc, ...
     */
    $urls = '(https?|file|ftp)';
    $ltrs = '\w';
    $gunk = '/\#~:.?+=&%@!\-';
    $punc = '.:?\-';
    $host = $ltrs.$punc;
    $any  = $ltrs.$gunk.$punc;

    // links
    $this->firstpass($table,$text,"#\[\[([^\]]+?)\]\]#ie","\$this->linkformat('\\1')");

    // cherche les url complètes
    $this->firstpass($table,$text,"#(\b)($urls:[$any]+?)([$punc]*[^$any])#ie","\$this->linkformat('\\2')",'\1','\4');

    // url www version courte
    $this->firstpass($table,$text,"#(\b)(www\.[$host]+?\.[$host]+?[$any]+?)([$punc]*[^$any])#ie","\$this->linkformat('http://\\2')",'\1','\3');
    // les n'emails
    $this->firstpass($table,$text,"#([a-z0-9\-_.]+?)@([\w\-]+\.([\w\-\.]+\.)*[\w]+)#ie", "\$this->linkformat('\\1@\\2')");

    if(isset($conf["macrofunction"]) && is_array($conf["macrofunction"]) && is_callable($conf["macrofunction"]))
      $this->firstpass($table,$text,"#@@([^@]+)@@#ie","\$this->wikimacro('\\1')");

    $text = htmlspecialchars($text);

    /* deuxième pass pour les formatages simples */
    $text = $this->simpleformat($text);

    /* troisième pass - insert les trucs de la première pass */
    reset($table);
    while (list($key, $val) = each($table))
      $text = str_replace($key,$val,$text);

    $text = trim($text);

    $text=str_replace('__dot__','[dot]',str_replace('__at__','[at]',$text));

    return $text;
  }

  function wikimacro($match)
  {
    global $conf;

    if ( !$conf["macrofunction"] || !is_callable($conf["macrofunction"]) )
      return $match;

    return call_user_func( $conf["macrofunction"], $match );
  }

  /**
   * Cette fonction ajoute quelques infos à propos du "headline" qui lui est passé
   * comme ça on pourra faire de la marmelade après (un sommaire)
   */
  function tokenize_headline(&$hltable,$pre,$hline,$lno)
  {
    switch (strlen($pre))
    {
      case 2:
        $lvl = 5;
        break;
      case 3:
        $lvl = 4;
        break;
      case 4:
        $lvl = 3;
        break;
      case 5:
        $lvl = 2;
        break;
      default:
        $lvl = 1;
        break;
    }
    $token = $this->mkToken();
    $hltable[] = array( 'name'  => htmlspecialchars(trim($hline)),
                        'level' => $lvl,
                        'line'  => $lno,
                        'token' => $token );
    return $token;
  }

  function html_buildlist($data,$class,$func)
  {
    $level = 0;
    $opens = 0;
    $ret   = '';
    foreach ($data as $item)
    {
      if( $item['level'] > $level )
        $ret .= "\n<ul class=\"$class\">\n";
      elseif( $item['level'] < $level )
      {
        $ret .= "</li>\n";
        for ($i=0; $i<($level - $item['level']); $i++)
          $ret .= "</ul>\n</li>\n";
      }
      else
        $ret .= "</li>\n";
      $level = $item['level'];
      $ret .= '<li class="level'.$item['level'].'">';
      $ret .= '<span class="li">';
      $ret .= $this->$func($item); //user function
      $ret .= '</span>';
    }
    for ($i=0; $i < $level; $i++)
      $ret .= "</li></ul>\n";
    return $ret;
  }

  function linkformat($match)
  {
    global $conf;
    global $wwwtopdir,$topdir;
    $ret = '';
    $match = str_replace('\\"','"',$match);

    list($link,$name) = split('\|',$match,2);
    $link   = trim($link);
    $name   = trim($name);
    $class  = '';
    $target = '';
    $style  = '';
    $pre    = '';
    $post   = '';
    $more   = '';

    $realname = $name;

    // email
    if(preg_match('#([a-z0-9\-_.]+?)@([\w\-]+\.([\w\-\.]+\.)*[\w]+)#i',$link))
      $this->format_link_email($link,$name,$class,$target,$style,$pre,$post,$more);
    // liens
    else
      $this->format_link($link,$name,$class,$target,$style,$pre,$post,$more);


    if( !strpos($link,'mailto:') && preg_match('/^([a-zA-Z]+):\/\//',$link) ) // liens externe et spéciaux
    {
      if ( preg_match('/dfile:\/\/([0-9]*)(\/preview|\/info|\/download|\/thumb)?/i',$link,$match) )
      {
        if ( empty($realname) && isset($conf["db"]) )
        {
          require_once($topdir."include/entities/files.inc.php");
          $file = new dfile($conf["db"]);
          $file->load_by_id($match[1]);
          return $file->get_html_link();
        }
        if ( !isset($match[2]) || $match[2] == "/download" )
          $link = "http://ae.utbm.fr/d.php?action=download&id_file=".$match[1];
        elseif ( $match[2] == "/preview" )
          $link = "http://ae.utbm.fr/d.php?action=download&download=preview&id_file=".$match[1];
        elseif ( $match[2] == "/thumb" )
          $link = "http://ae.utbm.fr/d.php?action=download&download=thumb&id_file=".$match[1];
        elseif ( $match[2] == "/info" )
          $link = "http://ae.utbm.fr/d.php?id_file=".$match[1];
      }
      else
      {
        //les article://
        $link = preg_replace("/article:\/\//i",'http://ae.utbm.fr/'.$GLOBALS["entitiescatalog"]["page"][3]."?name=",$link);
        //les wiki://
        $link = preg_replace("/wiki:\/\//i",'http://ae.utbm.fr/'.$GLOBALS["entitiescatalog"]["wiki"][3]."?name=",$link);
        if( defined('CMS_ID_ASSO') )
          $link = preg_replace("/sas:\/\//i","images.php?/",$link);
        else
          $link = preg_replace("/sas:\/\//i","http://ae.utbm.fr/sas2/images.php?/",$link);
      }
    }
    elseif ( !strpos($link,'mailto:') && !preg_match("#(\.|/)#",$link) )
    {
      $link = preg_replace("/[^a-z0-9\-_:#]/","_",strtolower(utf8_enleve_accents($link)));
      if ( $link{0} != '#' )
      {
        if ( $link{0} == ':' )
          $link = substr($link,1);
        elseif ( !empty($conf["linksscope"]))
          $link = $conf["linksscope"].$link;

        if ( $conf["linkscontext"] == "wiki" )
          $link = $wwwtopdir.$GLOBALS["entitiescatalog"]["wiki"][3]."?name=".$link;
        else
          $link = $wwwtopdir.$GLOBALS["entitiescatalog"]["page"][3]."?name=".$link;
      }
      if(strpos($link,'mailto:'))
      {
        $pos=strpos($link,'mailto:');
        $link=substr($link,$pos);
      }
    }



    $ret .= $pre;
    $ret .= '<a href="'.$link.'"';
    if($class)  $ret .= ' class="'.$class.'"';
    if($target) $ret .= ' target="'.$target.'"';
    if($style)  $ret .= ' style="'.$style.'"';
    if($more)   $ret .= ' '.$more;
    $ret .= '>';
    $ret .= $name;
    $ret .= '</a>';
    $ret .= $post;

    return $ret;
  }

  /**
   * les trucs simples et pas chiant c'est ici
   */
  function simpleformat($text)
  {
    global $conf;

    $text = preg_replace('#&lt;del&gt;(.*?)&lt;/del&gt;#is','<s>\1</s>',$text); //del
    $text = preg_replace('/__([^_]+?)__/s','<u>\1</u>',$text);  //underline
    $text = preg_replace('/\/\/(.*?)\/\//s','<em>\1</em>',$text);  //emphasize
    $text = preg_replace('/\*\*([^*]+?)\*\*/s','<strong>\1</strong>',$text);  //bold
    $text = preg_replace('/\'\'([^\']+?)\'\'/s','<code>\1</code>',$text);  //code

    $text = preg_replace('#&lt;sub&gt;(.*?)&lt;/sub&gt;#is','<sub>\1</sub>',$text);
    $text = preg_replace('#&lt;sup&gt;(.*?)&lt;/sup&gt;#is','<sup>\1</sup>',$text);

    $text = preg_replace("/\n((&gt;)[^\n]*?\n)+/se","'\n'.\$this->quoteformat('\\0').'\n'",$text);

    $text = preg_replace('/([^-])--([^-])/s','\1&#8211;\2',$text);
    $text = preg_replace('/([^-])---([^-])/s','\1&#8212;\2',$text);
    $text = preg_replace('/&quot;([^\"]+?)&quot;/s','&#8220;\1&#8221;',$text);
    $text = preg_replace('/(\s)\'(\S)/m','\1&#8216;\2',$text);
    $text = preg_replace('/(\S)\'/','\1&#8217;',$text);
    $text = preg_replace('/\.\.\./','\1&#8230;\2',$text);
    $text = preg_replace('/(\d+)x(\d+)/i','\1&#215;\2',$text);

    $text = preg_replace('/&gt;&gt;/i','&raquo;',$text);
    $text = preg_replace('/&lt;&lt;/i','&laquo;',$text);

    $text = preg_replace('/&lt;-&gt;/i','&#8596;',$text);
    $text = preg_replace('/&lt;-/i','&#8592;',$text);
    $text = preg_replace('/-&gt;/i','&#8594;',$text);

    $text = preg_replace('/&lt;=&gt;/i','&#8660;',$text);
    $text = preg_replace('/&lt;=/i','&#8656;',$text);
    $text = preg_replace('/=&gt;/i','&#8658;',$text);

    $text = preg_replace('/\(c\)/i','&copy;',$text);
    $text = preg_replace('/\(r\)/i','&reg;',$text);
    $text = preg_replace('/\(tm\)/i','&trade;',$text);

    //retours à la ligne forcés
    $text = preg_replace('#\\\\\\\\(\s)#',"<br />\\1",$text);

    // dos2unix
    $text = str_replace("\r\n","\n",$text);
    $text = str_replace("\n\r","\n",$text);
    $text = str_replace("\r","\n",$text);

    // lists (blocks leftover after blockformat)
    $text = preg_replace("/(\n( {2,}|\t)[\*\-][^\n]+)(\n( {2,}|\t)[^\n]*)*/se","\"\\n\".\$this->listformat('\\0')",$text);

    // double sauts de ligne = nouveau paragraphe
    $text = str_replace("\n\n","<p />",$text);

    return $text;
  }

  function firstpass(&$table,&$text,$regexp,$replace,$lpad='',$rpad='')
  {
    $ext='';
    if(substr($regexp,-1) == 'e')
    {
      $ext='e';
      $regexp = substr($regexp,0,-1);
    }

    while(preg_match($regexp,$text,$matches))
    {
      $token = $this->mkToken();
      $match = $matches[0];
      $text  = preg_replace($regexp,$lpad.$token.$rpad,$text,1);
      $table[$token] = preg_replace($regexp.$ext,$replace,$match);
    }
  }

  function mkToken()
  {
    return '~'.md5(uniqid(rand(), true)).'~';
  }

  function listformat($block)
  {
    $block = substr($block,1);
    $text = str_replace('\\"','"',$text);

    $ret='';
    $lst=0;
    $lvl=0;
    $enc=0;
    $lines = split("\n",$block);

    $cnt=0;
    $items = array();
    foreach ($lines as $line)
    {
      $lvl  = 0;
      $lvl += floor(strspn($line,' ')/2);
      $lvl += strspn($line,"\t");
      $line = preg_replace('/^[ \t]+/','',$line);
      (substr($line,0,1) == '-') ? $type='ol' : $type='ul';
      $line = preg_replace('/^[*\-]\s*/','',$line);
      $items[$cnt]['level'] = $lvl;
      $items[$cnt]['type']  = $type;
      $items[$cnt]['text']  = $line;
      $cnt++;
    }

    $current['level'] = 0;
    $current['type']  = '';

    $level = 0;
    $opens = array();

    foreach ($items as $item)
    {

      if( $item['level'] > $level )
      {
        $ret .= "\n<".$item['type'].">\n";
        array_push($opens,$item['type']);
      }
      elseif( $item['level'] < $level )
      {
        $ret .= "</li>\n";
        for ($i=0; $i<($level - $item['level']); $i++)
          $ret .= '</'.array_pop($opens).">\n</li>\n";
      }
      else
        $ret .= "</li>\n";

      $level = $item['level'];

      $ret .= '<li class="level'.$item['level'].'">';
      $ret .= '<span class="li">'.$item['text'].'</span>';
    }

    while ($open = array_pop($opens))
    {
      $ret .= "</li>\n";
      $ret .= '</'.$open.">\n";
    }
    return $ret;
  }

  /**
   * formatage des liens
   *
   * $link   URL pour le href=""
   * $name
   * $class  CSS class du lien
   * $target la cible (blank) pour la fenetre courante
   * $style  style aditionnels style=""
   * $pre
   * $post
   * $more
   *
   */

  function format_link(&$link,&$name,&$class,&$target,&$style,&$pre,&$post,&$more)
  {
    $class  = 'urlextern';
    $target = $conf['target']['extern'];
    $pre    = '';
    $post   = '';
    $style  = '';
    $more   = '';
    $link   = $link;
    if(!$name)
      $name = htmlspecialchars($link);
    else
    {
      if(preg_match("#\{\{([^\}]+?)\}\}#ie",$name))
      {
        $name=preg_replace("/\{\{/s","",$name);
        $name=preg_replace("/\}\}/s","",$name);
        $name=str_replace($name, $this->mediaformat($name), $name);
      }
    }
  }

  function format_link_email(&$link,&$name,&$class,&$target,&$style,&$pre,&$post,&$more)
  {
    global $conf;
    $class  = 'mail';
    $target = '';
    $pre    = '';
    $post   = '';
    $style  = '';
    $more   = '';

    $name   = htmlspecialchars($name);

    if($conf['mailguard']=='visible')
    {
      $link = str_replace('@',' [at] ',$link);
      $link = str_replace('.',' [dot] ',$link);
      $link = str_replace('-',' [dash] ',$link);
    }

    if(!$name)
      $name = $link;
    else
    {
      if(preg_match("#\{\{([^\}]+?)\}\}#ie",$name))
      {
        $name=preg_replace("/\{\{/s","",$name);
        $name=preg_replace("/\}\}/s","",$name);
        $name=str_replace($name, mediaformat($name), $name);
      }
    }
    $link   = "mailto:$link";
  }

}

?>
