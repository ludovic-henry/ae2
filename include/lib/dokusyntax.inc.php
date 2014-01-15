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

require_once($topdir . "include/cts/planning2.inc.php");
require_once($topdir . "include/entities/planning2.inc.php");

class dokusyntax
{

  /**
   * La fonction qui fait tout, tu lui file de la syntaxe wiki
   * elle te pond du xhtml, c'est de la magie en boite de concerve
   * @param $text le texte que tu veux qu'il resorte cossu :p
   */
  function doku2xhtml($text,$summury=false,$extern=false)
  {
    global $parser,$timing,$conf;
    $this->uid=gen_uid();
    $this->extern=$extern;
    $timing["doku2xhtml"] -= microtime(true);
    $js = false;
    $table   = array();
    $hltable = array();

    //preparse
    $text = $this->preparse($text,$hltable);

    //fix : je sais pas comment changer le vin en eau dsl
    $text  = "\n".$text."\n";

    // last revision
    //$text = str_replace("«««site-rev»»»", get_rev(), $text);

    /*les liens (à la base y'en a plein de suportés j'ai fait le ménage
     * ex : telnet, gopher, irc, ...
     */
    $urls = '(https?|file|ftp)';
    $ltrs = '\w';
    $gunk = '/\#~:.?+=&%@!\-';
    $punc = '.:?\-';
    $host = $ltrs.$punc;
    $any  = $ltrs.$gunk.$punc;

    /* first pass */

    // textes préformatés
    $this->firstpass($table,$text,"#<nowiki>(.*?)</nowiki>#se","\$this->preformat('\\1','nowiki')");
    $this->firstpass($table,$text,"#%%(.*?)%%#se","\$this->preformat('\\1','nowiki')");
    $this->firstpass($table,$text,"#<code( (\w+))?>(.*?)</code>#se","\$this->preformat('\\3','code','\\2')");
    $this->firstpass($table,$text,"#<file>(.*?)</file>#se","\$this->preformat('\\1','file')");

    // je sais pas si ça servira mais bon ...
    $this->firstpass($table,$text,"#<html>(.*?)</html>#se","\$this->preformat('\\1','html')");
    $this->firstpass($table,$text,"#<php>(.*?)</php>#se","\$this->preformat('\\1','php')");

    $this->firstpass($table,$text,"#<file>(.*?)</file>#se","\$this->preformat('\\1','file')");



    // block de code
    $this->firstpass($table,$text,"/(\n( {2,}|\t)[^\*\-\n ][^\n]+)(\n( {2,}|\t)[^\n]*)*/se","\$this->preformat('\\0','block')","\n");

    //check if toc is wanted
    if(!isset($parser['toc'])){
      if(strpos($text,'~~NOTOC~~')!== false)
      {
        $text = str_replace('~~NOTOC~~','',$text);
        $parser['toc']  = false;
      }
      else
        $parser['toc']  = true;
    }
    if(!isset($parser['secedit'])) $parser['secedit'] = true;


    //headlines
    $this->format_headlines($table,$hltable,$text);

    // links
    $this->firstpass($table,$text,"#\[\[([^\]]+?)\]\]#ie","\$this->linkformat('\\1')");

    // media
    $this->firstpass($table,$text,"#\{\{([^\}]+?)\}\}#ie","\$this->mediaformat('\\1')");

    // flash
    $this->firstpass($table,$text,"#\{\[([^\}]+?)\]\}#ie","\$this->flashformat('\\1')");

    // cherche les url complètes
    $this->firstpass($table,$text,"#(\b)($urls:[$any]+?)([$punc]*[^$any])#ie","\$this->linkformat('\\2')",'\1','\4');

    // url www version courte
    $this->firstpass($table,$text,"#(\b)(www\.[$host]+?\.[$host]+?[$any]+?)([$punc]*[^$any])#ie","\$this->linkformat('http://\\2')",'\1','\3');

    // windows shares
    $this->firstpass($table,$text,"#([$gunk$punc\s])(\\\\\\\\[$host]+?\\\\[$any]+?)([$punc]*[^$any])#ie","\$this->linkformat('\\2')",'\1','\3');

    // url ftp version courtes
    $this->firstpass($table,$text,"#(\b)(ftp\.[$host]+?\.[$host]+?[$any]+?)([$punc]*[^$any])#ie","\$this->linkformat('ftp://\\2')",'\1','\3');

    // les n'emails
    $this->firstpass($table,$text,"#([a-z0-9\-_.]+?)@([\w\-]+\.([\w\-\.]+\.)*[\w]+)#ie", "\$this->linkformat('\\1@\\2')");

    if(isset($conf["macrofunction"]) && is_array($conf["macrofunction"]) && is_callable($conf["macrofunction"]))
      $this->firstpass($table,$text,"#@@([^@]+)@@#ie","\$this->wikimacro('\\1')");

    $text = htmlspecialchars($text);

    
    //citation
    $text = str_replace( CHR(10), "__slash_n__" , $text );
    while( preg_match("/\[quote=(.*?)\](.*?)\[\/quote\]/i",$text) )
    {
      $text = preg_replace("/\[quote=(.*?)\](.*?)\[\/quote\]/i",
                           "<div class=\"cita\">
    <b>Citation de $1 :</b>
    <div class=\"cits\">$2___</div>___</div>___",
             $text);
    }
    while( preg_match("/\[quote\](.*?)\[\/quote\]/i",$text) )
    {
      $text = preg_replace("/\[quote\](.*?)\[\/quote\]/i",
                           "<div class=\"cita\">
    <b>Citation :</b>
    <div class=\"cits\">$1___</div>___</div>___",
                          $text);
    }
    $text= str_replace('___</div>___</div>___','</div></div>'.CHR(10),$text);
    $text= str_replace('__slash_n__',CHR(10),$text);

    $text=preg_replace("/#\#\#([0-9]+?)\#\#\#/","<div class=\"progressbar\">\n<div class=\"progressbar_prog\" style=\"width:$1%;\"></div>\n<div class=\"progressbar_value\">$1%</div>\n</div>",$text);

    if(isset($conf['bookmarks']) && $conf['bookmarks'])
    {
      global $wwwtopdir;
      $anchor=$wwwtopdir."images/forum/anchor.png";
      while( preg_match("/&lt;bookmark:(.*?)&gt;/i",$text) )
        $text=preg_replace("/&lt;bookmark:(\S+)&gt;/i",
                           "<img src=\"$anchor\" alt=\"$1\" title=\"$1\" /><a name=\"$1\"></a>",
                           $text);
    }

    /* deuxième pass pour les formatages simples */
    $text = $this->simpleformat($text);

    while( preg_match("/\[planning=(.*?)\/(false|true)\/(.*?)\]/i",$text,$matches) )
    {
      $site = $GLOBALS['site'];
      $planningv = new planningv("",$site->db,intval($matches[1]), time(), time()+7*24*3600, $site,$matches[2]==="true",false,intval($matches[3]));
      $text = preg_replace("/\[planning=(.*?)\]/i",
                           $planningv->get_buffer(),
             $text);
    }


    while( preg_match("/\[planning=(.*?)\]/i",$text,$matches) )
    {
      $site = $GLOBALS['site'];
      $planningv = new planningv("",$site->db,intval($matches[1]), time(), time()+7*24*3600, $site);
      $text = preg_replace("/\[planning=(.*?)\]/i",
                           $planningv->get_buffer(),
             $text);
    }


    /* troisième pass - insert les trucs de la première pass */
    reset($table);
    while (list($key, $val) = each($table))
      $text = str_replace($key,$val,$text);

    $text = trim($text);

    $timing["doku2xhtml"] += microtime(true);

    $text=str_replace('__dot__','[dot]',str_replace('__at__','[at]',$text));

    if ( $summury )
      return array($js.$text,$hltable);

    return $js.$text;
  }

  function wikimacro($match)
  {
    global $conf;

    if ( !$conf["macrofunction"] || !is_callable($conf["macrofunction"]) )
      return $match;

    return call_user_func( $conf["macrofunction"], $match );
  }


  /**
   * On préparse le texte ligne par ligne.
   */
  function preparse($text,&$hltable)
  {
    $lines = split("\n",$text);


    for ($l=0; $l<count($lines); $l++)
    {
      $line = $lines[$l];

      // on cherche la fin des trucs à ne pas parser qui sont sur plusieurs lignes
      if($noparse){
        if(preg_match("#^.*?$noparse#",$line))
        {
          $noparse = '';
          $line = preg_replace("#^.*?$noparse#",$line,1);
        }
        else
          continue;
      }

      if(!$noparse)
      {
        // abat les indentations \o/
        if(preg_match('#^(  |\t)#',$line)) continue;
        // on enlève les blocs à pas parser qui sont inline
        $line = preg_replace("#<nowiki>(.*?)</nowiki>#","",$line);
        $line = preg_replace("#%%(.*?)%%#","",$line);
        $line = preg_replace("#<code>(.*?)</code>#","",$line);
        $line = preg_replace("#<file>(.*?)</file>#","",$line);
        $line = preg_replace("#<html>(.*?)</html>#","",$line);
        $line = preg_replace("#<php>(.*?)</php>#","",$line);
        // on cherche le début des block "noparse" multilignes
        if(preg_match('#^.*?<(nowiki|code|php|html|file)( (\w+))?>#',$line,$matches))
        {
           list($noparse) = split(" ",$matches[1]); //on vire les options
          $noparse = '</'.$noparse.'>';
          continue;
        }
        elseif(preg_match('#^.*?%%#',$line))
        {
          $noparse = '%%';
          continue;
        }
      }

      //headlines
      if(preg_match('/^(\s)*(==+)(.+?)(==+)(\s*)$/',$lines[$l],$matches))
      {
        $tk = $this->tokenize_headline($hltable,$matches[2],$matches[3],$l);
        $lines[$l] = $tk;
      }

    }

    return join("\n",$lines);
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
    $name = trim($hline);
    $collapsed = false;
    // If name starts with a tilde (~) that means we hide it by default
    // while also adding a little expander button to show it
    if (strlen ($name) > 0 && $name[0] == '~') {
        $collapsed = true;
        $name = substr ($name, 1);
    }
    $hltable[] = array( 'name'  => htmlspecialchars($name),
                        'level' => $lvl,
                        'line'  => $lno,
                        'token' => $token,
                        'collapsed' => $collapsed);
    return $token;
  }

  function format_headlines(&$table,&$hltable,&$text)
  {
    global $parser;
    global $conf;
    global $lang;
    global $ID;
    $uid=$this->uid;
    $lang = 'fr'; // bein quoi ?
    $collapseCurrent = 100;

    $last = 0;
    $cnt  = 0;
    foreach($hltable as $hl)
    {
      $cnt++;
      $headline   = '';
      if($cnt - 1) $headline .= '</div>';
      $headline  .= '<a name="h_'.$uid.'_'.($cnt).'"></a>';
      $headline  .= '<a name="'.preg_replace("/[^a-z0-9\-_:#]/","_",strtolower(utf8_enleve_accents($hl['name']))).'"></a>';
      $headline  .= '<h'.$hl['level'].($hl['level'] > $collapseCurrent ? ' style="display: none;"' : '').'>';
      $headline  .= $hl['name'];
      $headline  .= '</h'.$hl['level'].'>';
      if ($hl['level'] <= $collapseCurrent)
          $collapseCurrent = 100;
      if ($hl['collapsed']) {
          $collapseCurrent = $hl['level'];
          $headline  .= '<a onclick="toggleSectionVisibility(this);">[+]</a>';
      }
      $headline  .= '<div class="level'.$hl['level'].'"'.(($hl['collapsed'] || $hl['level'] > $collapseCurrent) ? ' style="display: none;"' : '').'>';

      if($hl['level'] <= $conf['maxtoclevel'])
      {
        $content[]  = array('id'    => 'h_'.$uid.'_'.$cnt,
                            'name'  => $hl['name'],
                            'level' => $hl['level']);
      }

      $table[$hl['token']] = $headline;
    }

    if ($cnt)
    {
      $token = $this->mktoken();
      $text .= $token;
      $table[$token] = '</div>';
    }

    if ($parser['toc'] && count($content) > 2)
    {
      $token = $this->mktoken();
      $text  = $token.$text;
      $table[$token] = $this->html_toc($content);
    }
  }

  function html_toc($toc)
  {
    global $topdir;
    $ret  = "\n";
    $ret .= '<div class="toc">'."\n";
    $ret .= '<div class="tocheader">'."\n";
    $ret .= '<a href="#" onclick="on_off_icon(\'toc\',\''.$topdir.'\'); ';
    $ret .= 'return false;"><img src="'.$topdir.'images/fld.png" alt="togle" class="icon" id="toc_icon" />'."\n";
    $ret .= 'Table des matières'."\n";
    $ret .= '</div>'."\n";
    $ret .= '<div id="toc_contents">'."\n";
    $ret .= $this->html_buildlist($toc,'toc','html_list_toc');
    $ret .= '</div>'."\n";
    $ret .= '</div>'."\n";
    return $ret;
  }

  function html_buildlist($data,$class,$func)
  {
    $level = array();
    $k = 0;
    $opens = 0;
    $ret   = '';

    $level[0] = 0;
    foreach ($data as $item)
    {
      if( $item['level'] > $level[$k] ){
        $ret .= "\n<ul class=\"$class\">\n";
        $k++;
      }
      elseif( $item['level'] < $level[$k] )
      {
        $ret .= "</li>\n";
        for (; $level[$k] > $item['level']; $k--)
          $ret .= "</ul>\n</li>\n";
      }
      else
        $ret .= "</li>\n";
      $level[$k] = $item['level'];
      $ret .= '<li class="level'.$item['level'].'">';
      $ret .= '<span class="li">';
      $ret .= $this->$func($item); //user function
      $ret .= '</span>';
    }
    for (; $k > 0; $k--)
      $ret .= "</li></ul>\n";
    return $ret;
  }

  function html_list_toc($item)
  {
    $ret = '<a href="#'.$item['id'].'" class="toc">';
    $ret .= $item['name'];
    $ret .= '</a>';
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
          $link = "http://ae.utbm.fr/d.php?action=download&amp;id_file=".$match[1];
        elseif ( $match[2] == "/preview" )
          $link = "http://ae.utbm.fr/d.php?action=download&amp;download=preview&amp;id_file=".$match[1];
        elseif ( $match[2] == "/thumb" )
          $link = "http://ae.utbm.fr/d.php?action=download&amp;download=thumb&amp;id_file=".$match[1];
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
    $text = preg_replace('/^(\s)*----+(\s*)$/m','<hr noshade="noshade" size="1" />',$text); //hr

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

    // tableaux
    $text = preg_replace("/\n(([\|\^][^\n]*?)+[\|\^]\n)+/se","\"\\n\".\$this->tableformat('\\0')",$text);

    //smileys
    $text = $this->smileys($text);

    // footnotes
    $text = $this->footnotes($text);

    // double sauts de ligne = nouveau paragraphe
    $text = str_replace("\n\n","<p />",$text);

    return $text;
  }

  /**
   * les footnotes
   */
  function footnotes($text)
  {
    $num = 0;
    while (preg_match('/\(\((.+?)\)\)/s',$text,$match))
    {
      $num++;
      $fn    = $match[1];
      $linkt = '<a href="#fn'.$num.'" name="fnt'.$num.'" class="fn_top">'.$num.')</a>';
      $linkb = '<a href="#fnt'.$num.'" name="fn'.$num.'" class="fn_bot">'.$num.')</a>';
      $text  = preg_replace('/ ?\(\((.+?)\)\)/s',$linkt,$text,1);
      if($num == 1) $text .= '<div class="footnotes">';
      $text .= '<div class="fn">'.$linkb.' '.$fn.'</div>';
    }

    if($num) $text .= '</div>';
    return $text;
  }

  /**
   * on remplace les smileys :)
   */
  function smileys($text)
  {
    global $wwwtopdir;
    $smileys = array(

      ":-o"=>"omg.png",
      ":-O"=>"omg.png",
      ":o"=>"omg.png",
      ":O"=>"omg.png",
      "8-O"=>"omg.png",

      ":-("=>"sad.png",
      ":("=>"sad.png",

      ":-)"=>"smile.png",
      ":)"=>"smile.png",
      ":-/"=>"confused.png",
      ":/"=>"confused.png",
      "^_^"=>"happy.png",
      ";)"=>"wink.png",
      ";-)"=>"wink.png",
      ":-|"=>"neutral.png",
      ":|"=>"neutral.png",
      ":-D"=>"lol.png",
      ":D"=>"lol.png",
      "Oo"=>"dizzy.png",
      "O_o"=>"dizzy.png",
      "O_O"=>"dizzy.png",
      "o_o"=>"dizzy.png",
      "o_O"=>"dizzy.png",
      ":&#8217;("=>"cry.png",
      ";-("=>"cry.png",
      ";("=>"cry.png",
      "x)"=>"caca.png",
      "x-)"=>"caca.png",
      ":caca:"=>"caca.png",
      ":-p"=>"tongue.png",
      ":-P"=>"tongue.png",
      ":p"=>"tongue.png",
      ":P"=>"tongue.png",
      ':!:'=>'exclaim.gif',
      ':?:'=>'question.gif',
      'FIXME'=>'fixme.gif',
      'DELETEME'=>'delete.gif'
                     );
    $smPath = $wwwtopdir."images/forum/smilies/";
    foreach($smileys as $tag => $img)
    {
      if ( file_exists($smPath . "/" . $img) )
      {
        $tag = preg_replace('!\]!i', '\]', $tag);
        $tag = preg_replace('!\[!i', '\[', $tag);
        $tag = preg_replace('!\)!i', '\)', $tag);
        $tag = preg_replace('!\(!i', '\(', $tag);
        $tag = preg_replace('!\!!i', '\!', $tag);
        $tag = preg_replace('!\^!i', '\^', $tag);
        $tag = preg_replace('!\$!i', '\$', $tag);
        $tag = preg_replace('!\{!i', '\}', $tag);
        $tag = preg_replace('!\}!i', '\{', $tag);
        $tag = preg_replace('#\?#i', '\?', $tag);
        $tag = preg_replace('!\+!i', '\+', $tag);
        $tag = preg_replace('!\*!i', '\*', $tag);
        $tag = preg_replace('!\.!i', '\.', $tag);
        $tag = preg_replace('!\|!i', '\|', $tag);
        $text = preg_replace('!( |^|\n)'.$tag.'( |$|\n)!i', "$1<img src=\"".$smPath.$img."\" alt=\"\" />$2", $text);

      }
    }
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

  /**
   * quote quote quodec !
   */
  function quoteformat($block)
  {
    $block = trim($block);
    $lines = split("\n",$block);

    $lvl = 0;
    $ret = "";
    foreach ($lines as $line)
    {
      $cnt = 0;
      while(substr($line,0,4) == '&gt;')
      {
        $line = substr($line,4);
        $cnt++;
      }

      if($cnt > $lvl)
        for ($i=0; $i< $cnt - $lvl; $i++)
          $ret .= '<div class="quote">';
      elseif($cnt < $lvl)
        for ($i=0; $i< $lvl - $cnt; $i++)
          $ret .= "</div>\n";

      $ret .= ltrim($line)."\n";
      $lvl = $cnt;
    }

    for ($i=0; $i< $lvl; $i++)
      $ret .= "</div>\n";

    return $ret;
  }

  function tableformat($block)
  {
    $block = trim($block);

    if(preg_match("/@(.+?)@/",$block))
    {
      preg_match("/@(.+?)@/s",$block,$match);
      $class = str_replace('@', '', $match[0]);
      $block = preg_replace('/@(.+?)@/s', '', $block);
    }

    $lines = split("\n",$block);
    $ret = "";
    $rows = array();
    $gen_graph="not_yet";
    $graph=array();
    for($r=0; $r < count($lines); $r++)
    {
      $line = $lines[$r];
      $line = preg_replace('/[\|\^]\s*$/', '', $line);
      $c = -1;
      for($chr=0; $chr < strlen($line); $chr++)
      {
        if($line[$chr] == '^')
        {
          $c++;
          $rows[$r][$c]['head'] = true;
          $rows[$r][$c]['data'] = '';
        }
        elseif($line[$chr] == '|')
        {
          $c++;
          $rows[$r][$c]['head'] = false;
          $rows[$r][$c]['data'] = '';
        }
        else
          $rows[$r][$c]['data'].= $line[$chr];
      }
      if ($c==1 && $gen_stat == "not_yet")
        $gen_stat=true;
      elseif($c>1 && $gen_stat == "not_yet")
      {
        $gen_graph=false;
        unset($graph);
      }
    }
    if($gen_stat == "not_yet" )
    {
      $gen_graph=false;
      unset($graph);
    }

    // et là les tables de la loi furent !
    if(isset($class))
      $ret .= "<table class=\"".$class."\">\n";
    else
      $ret .= "<table class=\"inline dokutable\">\n";
    for($r=0; $r < count($rows); $r++)
    {
      $ret .= "  <tr>\n";
      for ($c=0; $c < count($rows[$r]); $c++)
      {
        $cspan=1;
        $format=$this->alignment($rows[$r][$c]['data']);
        $format=$format['align'];
        $data = trim($rows[$r][$c]['data']);
        $data = $this->smileys($data);
        $head = $rows[$r][$c]['head'];
        while($c < count($rows[$r])-1 && $rows[$r][$c+1]['data'] == '')
        {
          $c++;
          $cspan++;
        }
        if($cspan > 1)
        {
          $gen_graph=false;
          unset($graph);
          $cspan = 'colspan="'.$cspan.'"';
        }
        else
          $cspan = '';

        if ($head)
          $ret .= "    <th class=\"inline $format\" $cspan>$data</th>\n";
        else
          $ret .= "    <td class=\"inline $format\" $cspan>$data</td>\n";
      }
      $ret .= "  </tr>\n";
      if($gen_graph && !$head)
      {
        if(!ereg("^[0-9]+(\,[0-9]{1,2})?\%$",$rows[$r][1]["data"]))
        {
          $gen_graph=false;
          unset($graph);
        }
        elseif(!isset($graph[$rows[$r][0]["data"]]))
          $graph[$rows[$r][0]["data"]]=(float)str_replace("%", "",str_replace(",",".",$rows[$r][1]["data"]));
        else
          $graph[$rows[$r][0]["data"]]=$graph[$rows[$r][0]["data"]]+(float)str_replace("%", "",str_replace(",",".",$rows[$r][1]["data"]));
      }
      elseif($gen_graph && $r>0)
      {
        $gen_graph==false;
        unset($graph);
      }
    }
    $ret .= "</table>\n\n";

    if($gen_graph)
    {
      global $js;
      if(!$GLOBALS['js'])
      {
        $GLOBALS['js']=true;
        $_js = "<script language=\"JavaScript\">\n";
        $_js.= "function switchid(obj,id1,id2){\n";
        $_js.= "tohide = document.getElementById(id1);\n";
        $_js.= "tohide.style.display = 'none';\n";
        $_js.= "toshow = document.getElementById(id2);\n";
        $_js.= "toshow.style.display = 'block';\n";
        $_js.= "spanunselect = document.getElementById(id1+0);\n";
        $_js.= "spanunselect.className='';\n";
        $_js.= "aunselect = document.getElementById(id1+1);\n";
        $_js.= "aunselect.className='';\n";
        $_js.= "spanselect = document.getElementById(id2+0);\n";
        $_js.= "spanselect.className='selected';\n";
        $_js.= "aselect = document.getElementById(id2+1);\n";
        $_js.= "aselect.className='selected';\n";
        $_js.= "}\n";
        $_js.="</script>\n";
      }
      else
        $_js="";
      global $topdir;
      require_once($topdir . "include/graph.inc.php");
      if(!empty($graph))
      {
        $total=0;
        $data="";
        foreach($graph as $key => $value)
        {
          $value=str_replace(",",".",$value);
          if(!empty($data))
            $data.=";".rawurlencode($key)."|".$value;
          else
            $data=rawurlencode($key)."|".$value;
          $total=$total+(float)$value;
        }
        $total=round($total,0);
        $id=substr(md5(microtime(true)), 0, 6);
        $_ret ="<div id=\"".$id."\" class=\"tabs\">\n";
        $_ret.="<span id=\"".$id."10\" class=\"selected\"><a href=\"javascript:switchid(this,'".$id."2','".$id."1');\" id=\"".$id."11\" class=\"selected\" title=\"Tableau\">Tableau</a></span>\n";
        $_ret.="<span id=\"".$id."20\"><a href=\"javascript:switchid(this,'".$id."1','".$id."2');\" id=\"".$id."21\" title=\"Graph\">Graph</a></span>\n";
        $_ret.="</div>\n";
        $_ret.="<div id=\"".$id."1\" style=\"display:block;\">".$ret."</div>\n";
        $_ret.="<div id=\"".$id."2\" style=\"display:none;\">";
        if($total==100)
          $_ret.= "<img src=\"".$topdir."gen_graph.php?action=cam&values=".$data."\" /></div>\n";
        else
          $_ret.= "<img src=\"".$topdir."gen_graph.php?action=bar&values=".$data."\" /></div>\n";
        $ret = $_js.$_ret;
      }
    }
    return $ret;
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
      $line = $this->smileys($line);
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

  function preformat($text,$type,$option='')
  {
    global $conf;
    $text = str_replace('\\"','"',$text);

    if($type == 'php' && !$conf['phpok']) $type='file';
    if($type == 'html' && !$conf['htmlok']) $type='file';

    switch ($type)
    {
      case 'php':
          ob_start();
          eval($text);
          $text = ob_get_contents();
          ob_end_clean();
        break;
      case 'html':
        break;
      case 'nowiki':
        $text = htmlspecialchars($text);
        break;
      case 'file':
        $text = htmlspecialchars($text);
        $text = '<pre class="file">'.$text.'</pre>';
        break;
      case 'code':
        $text = htmlspecialchars($text);
        $text = '<pre class="code">'.$text.'</pre>';
        break;
      case 'block':
        $text  = substr($text,1);
        $lines = split("\n",$text);
        $text  = '';
        foreach($lines as $line)
          $text .= substr($line,2)."\n";
        $text = htmlspecialchars($text);
        $text = '<pre class="pre">'.$text.'</pre>';
        break;
    }
    return $text;
  }

  function mediaformat($text)
  {
    global $conf;
    global $wwwtopdir;
    global $topdir;
    $name = str_replace('\\"','"',$text);
    $ret .= $pre;
    $format=$this->alignment($name);
    list($img,$name) = split('\|',$format['src'],2);
    $img=trim($img);
    list($img,$sizes) = split('\?',$img,2);
    list($width,$height) = split('x',$sizes,2);
    $name=trim($name);
    //les dfiles://
    $img = preg_replace("/dfile:\/\/([0-9]*)\/preview/i","http://ae.utbm.fr/d.php?action=download&download=preview&id_file=$1",$img);
    $img = preg_replace("/dfile:\/\/([0-9]*)\/thumb/i","http://ae.utbm.fr/d.php?action=download&download=thumb&id_file=$1",$img);
    $img = preg_replace("/dfile:\/\//i","http://ae.utbm.fr/d.php?action=download&id_file=",$img);
    if( defined('CMS_ID_ASSO') )
      $img = preg_replace("/sas:\/\//i","images.php?/",$img);
    else
      $img = preg_replace("/sas:\/\//i","http://ae.utbm.fr/sas2/images.php?/",$img);

    if ( preg_match("/\.flv$/i",$img) )
    {
      if ( !$width )
        $width=400;

      if ( !$height )
        $height=300;

      if ( !preg_match("/([a-z0-9]+):\/\//",$img) )
      {
        if ( substr($img,0,strlen($wwwtopdir)) == $wwwtopdir )
          $img = "../../".substr($img,strlen($wwwtopdir));
        else
          $img = "../../".$img;
      }
      $ret .= "<object type=\"application/x-shockwave-flash\" data=\"".$wwwtopdir."images/flash/flvplayer.swf\" width=\"400\" height=\"300\" class=\"media".$format["align"]."\">";
      $ret .="<param name=\"movie\" value=\"".$wwwtopdir."images/flash/flvplayer.swf\" />";
      $ret .="<param name=\"FlashVars\" value=\"flv=".$img."\" />";
      $ret .="<param name=\"wmode\" value=\"transparent\" />";
      $ret .="</object>";
      return $ret;
    }

    $ret .= '<img src="'.$img.'"';
    $ret .= ' class="media'.$format['align'].'"';
    if(!empty($width))
      $ret .= ' width="'.$width.'"';
    if(!empty($height))
      $ret .= ' height="'.$height.'"';
    $ret .= ' alt="'.$name.'" title="'.$name.'" />';
    return $ret;
  }

  /*
   * format :
   * url(|param,valeur(;param,valeur(...)))
   *
   */
  function flashformat($text)
  {
    $format=$this->alignment($text);
    list($url,$sizes,$params) = split('\|',$format['src'],3);
    $sizes=trim($sizes);
    $params=trim($params);
    $url=trim($url);
    if(!preg_match("/^(http:\/\/)?([^\/]+)/i",$url))
      return '';
    $sizes=trim($sizes);
    $x=0;
    $y=0;
    if(!empty($sizes))
    {
      $sizes=explode(';',$sizes);
      foreach($sizes as $size)
      {
        list($name,$value)=split('=',$size,3);
        if($name=='x')
          $x=intval($value);
        elseif($name=='y')
          $y=intval($value);
      }
    }
    else
    {
      $x=400;
      $y=300;
    }
    if(empty($params))
    {
      $oembed_content = $this->oembed_fetch($url, $x);
      if (!empty($oembed_content))
        return $oembed_content;
    }

    $ret='<div class="externflash media'.$format['align'].'">';
    $ret .= "<object type=\"application/x-shockwave-flash\" data=\"".$url."\" width=\"$x\" height=\"$y\" class=\"media".$format["align"]."\">";
    $ret .= "<param name=\"movie\" value=\"".$url."\" />";
    if(!empty($params))
    {
      $params=explode(';',$params);
      foreach($params as $param)
      {
        list($name,$value)=split(',',$param,2);
        $name=trim($name);
        if(!empty($name) && $name!='movie')
          $ret .= "<param name=\"".$name."\" value=\"".$value."\" />";
      }
    }
    return $ret.'</object></div>'.chr(13);
  }

  function oembed_fetch($url, $maxwidth=False)
  {
    $oembed_url = $this->get_oembed_url($url);

    if (empty($oembed_url))
      return '';

    if ($maxwidth)
      $oembed_url .= "&maxwidth=".$maxwidth;

    $session = curl_init($oembed_url);
    curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
    $reponse = curl_exec($session);
    $error = curl_error($session);
    curl_close($session);

    if ( !empty($error) )
      return 'Impossible de récupérer le fichier oEmbed de la vidéo.';

    $xml = simplexml_load_string($reponse);

    if (empty($xml->html))
      return 'Fichier oEmbed non supporté.';

    return $xml->html;
  }

  function get_oembed_url($url)
  {
    // Pour les plus courants on retourne direct l'url
    if(preg_match('/http\:\/\/.*\.youtube\.[^.]*\//',$url))
      return "http://www.youtube.com/oembed?format=xml&url=".$url;
    elseif(preg_match('/http\:\/\/.*\.dailymotion.[^.]*\//',$url))
      return "http://www.dailymotion.com/services/oembed?format=xml&url=".$url;
    elseif(preg_match('/http\:\/\/.*\.vimeo.[^.]*\//',$url))
      return "http://vimeo.com/api/oembed.xml?url=".$url;

    // Mais ta gueule !
    libxml_use_internal_errors(true);
    $dom = new DOMDocument;

    if (!$dom->loadHTMLFile($url))
      return '';
    else
    {
      $xml = simplexml_import_dom($dom);

      foreach($xml->head->link as $lnk)
      {
        if (($lnk['rel'] == "alternate") && (($lnk['type'] == "text/xml+oembed") || ($lnk['type'] == "application/xml+oembed")))
          return $lnk['href'];
      }
    }

    return '';
  }

  function alignment($texte)
  {
    $r=false;
    $l=false;
    $left=rtrim($texte);
    $right=ltrim($texte);
    if($texte != $right)
      $r=true;
    if($texte != $left)
      $l=true;

    if ($l && $r)
      return array('src'=>$texte, 'align'=>"center");
    elseif($r)
      return array('src'=>$texte, 'align'=>"right");
    elseif($l)
      return array('src'=>$texte, 'align'=>"left");
    else
      return array('src'=>$texte, 'align'=>"");
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


function doku2xhtml($text,$summury=false,$extern=false)
{
  global $syntaxengine;
  if ( !isset($syntaxengine) )
    $syntaxengine = new dokusyntax();
  return $syntaxengine->doku2xhtml($text,$summury,$extern);
}

?>
