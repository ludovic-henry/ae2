<?php

/** @file
 *
 * @brief Classe de traduction bbcode -> html
 *
 */

/* Copyright 2007
 *
 * - Pierre Mauduit <pierre POINT mauduit CHEZ utbm POINT fr>
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



//bbcode
function bbcode($text)
{
  global $topdir;

  $text = preg_replace("/\[(\w+):(\w+)\]/","[$1]", $text);
  $text = preg_replace("/\[(\w+)=\"([^\"]+)\"\]/","[$1=\"$2\"]", $text);
  $text = preg_replace("/\[\/(\w+):(\w+)\]/","[/$1]", $text);

  $text = preg_replace("/\[(\w+):(\w+):(\w+)\]/","[$1]", $text);
  $text = preg_replace("/\[\/(\w+):(\w+):(\w+)\]/","[/$1]", $text);



  //destruction des codes html manuels et remplacement des caractères spéciaux

  $text = str_replace( ">"    , "&gt;" , $text );
  $text = str_replace( "<"    , "&lt;" , $text );
  $text = str_replace( "\""    , "&quot;" , $text );
  $text = str_replace( "'"    , "&#146;" , $text );
  $text = str_replace( "" , "&#146;" , $text );

  $text = str_replace( "é"    , "&eacute;" , $text );
  $text = str_replace( "è"    , "&egrave;" , $text );
  $text = str_replace( "à"    , "&agrave;" , $text );
  $text = str_replace( "ç"    , "&ccedil;" , $text );
  $text = str_replace( ""    , "&oelig;" , $text );

  $text = str_replace('"',"&#147;", $text );
  $text = str_replace("'", '&#146;', $text);
  $text = str_replace("", '&euro;', $text);
  $text = str_replace("&amp;#", '&#', $text);

  //conversion des retours à la ligne
  $text = str_replace( CHR(10), "<br>" , $text );
  //bare
  $text = str_replace( "[hr]", "<hr>" , $text );
  //images
  $text = preg_replace("#\[img\]((ht|f)tp://)([^\r\n\t<\"]*?)\[/img\]#sie", "'<img src=\\1' . str_replace(' ', '%20', '\\3') . '>'",
           $text);
  //url
  $text = preg_replace("/\[url\](.+?)\[\/url\]/", "<a href=\"$1\" target=\"blank\">$1</a>", $text);
  $text = preg_replace("/\[url=(.+?)\](.+?)\[\/url\]/", "<a href=\"$1\" target=\"blank\">$2</a>", $text);
  $text = preg_replace("/\[URL\](.+?)\[\/URL\]/", "<a href=\"$1\" target=\"blank\">$1</a>", $text);
  $text = preg_replace("/\[URL=(.+?)\](.+?)\[\/URL\]/", "<a href=\"$1\" target=\"blank\">$2</a>", $text);


  //email
  $text = preg_replace("/\[email\](.+?)\[\/email\]/", "<a href=\"mailto:$1\">$1</a>", $text);
  $text = preg_replace("/\[email=(.+?)\](.+?)\[\/email\]/", "<a href=\"mailto:$1\">$2</a>", $text);
  //gras
  $text = preg_replace("/\[b\](.+?)\[\/b\]/", "<b>$1</b>", $text);
  //italique
  $text = preg_replace("/\[i\](.+?)\[\/i\]/", "<i>$1</i>", $text);
  //souligné
  $text = preg_replace("/\[u\](.+?)\[\/u\]/", "<u>$1</u>", $text);
  //justifié
  $text = preg_replace("/\[justifie\](.+?)\[\/justifie\]/", "<div style=\"text-align: justify\">$1</div>", $text);
  //centré
  $text = preg_replace("/\[centre\](.+?)\[\/centre\]/", "<div style=\"text-align: center\">$1</div>", $text);
  //droite
  $text = preg_replace("/\[droite\](.+?)\[\/droite\]/", "<div style=\"text-align: right\">$1</div>", $text);
  //code
  $text = preg_replace("/\[code\](.+?)\[\/code\]/", "<div class=\"codetop\">Code :</div><br/><pre>$1</pre>", $text);
  //citation
  while( preg_match("/\[quote=(.+?)\](.+?)\[\/quote\]/i",$text) )
  {
    $text = preg_replace("/\[quote=(.+?)\](.+?)\[\/quote\]/i",
                         "<div style=\"margin: 10px 4px 10px 30px; padding: 4px;\">
  <b>Citation de $1 :</b>
  <div style=\"border: 1px #374a70 solid;
  margin-top:2px;
  padding: 4px;
  text-align: justify;
  background-color: #ecf4fe;\">$2</div></div>",
           $text);
  }
  while( preg_match("/\[quote\](.+?)\[\/quote\]/i",$text) )
  {
    $text = preg_replace("/\[quote\](.+?)\[\/quote\]/i",
                         "<div style=\"margin: 10px 4px 10px 30px; padding: 4px;\">
  <b>Citation :</b>
  <div style=\"border: 1px #374a70 solid;
  margin-top:2px;
  padding: 4px;
  text-align: justify;
  background-color: #ecf4fe;\">$1</div></div>",
                        $text);
  }

  //couleur
  $text = preg_replace("/\[color=(.+?)\](.+?)\[\/color\]/", "<font color=$1>$2</font>", $text);
  //taille
  $text = preg_replace("/\[size=(.+?)\](.+?)\[\/size\]/", "<font style=\"font-size:$1px\">$2</font>", $text);
  //formattage pre
  $text = preg_replace("/\[pre\](.+?)\[\/pre\]/", "<pre>$1</pre>", $text);

  //smilies
  $smTags = array(":-)"=>"smile.png",
                  ":)"=>"smile.png",
                  "^_^"=>"happy.png",
                  "^^"=>"happy.png",
                  ";)"=>"wink.png",
                  ";-)"=>"wink.png",
                  ":-/"=>"confused.png",
                  ":/"=>"confused.png",
                  ":-|"=>"neutral.png",
                  ":|"=>"neutral.png",
                  ":-D"=>"lol.png",
                  ":D"=>"lol.png",
                  ":-o"=>"omg.png",
                  ":-O"=>"omg.png",
                  ":o"=>"omg.png",
                  ":O"=>"omg.png",
                  "8-O"=>"omg.png",
                  "Oo"=>"dizzy.png",
                  "O_o"=>"dizzy.png",
                  "O_O"=>"dizzy.png",
                  "o_o"=>"dizzy.png",
                  "o_O"=>"dizzy.png",
                  ":'("=>"cry.png",
                  ";-("=>"cry.png",
                  ";("=>"cry.png",
                  ":-p"=>"tongue.png",
                  ":-P"=>"tongue.png",
                  ":p"=>"tongue.png",
                  ":P"=>"tongue.png"
                  );
  $smPath = $topdir."/images/forum/smilies/";
  foreach ( $smTags as $tag => $img )
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
       $tag = preg_replace('!\?!i', '\?', $tag);
       $tag = preg_replace('!\+!i', '\+', $tag);
       $tag = preg_replace('!\*!i', '\*', $tag);
       $tag = preg_replace('!\.!i', '\.', $tag);
       $tag = preg_replace('!\|!i', '\|', $tag);
       $text = preg_replace('! ' . $tag . '!i', '<img src="'.$smPath.$img.'" alt="" />', $text);
    }

  return $text;
}

class bbcontents extends contents
{
  var $contents;
  var $wiki;

  /** Crée un stdcontents à partir d'un texte au format DokuWiki et de son titre
   * @param $title  Titre
   * @param $contents  Texte structuré
   */
  function bbcontents($title,$contents,$rendernow=false)
  {
    $this->title = $title;
    $this->contents = $contents;
    if ( $rendernow )
      $this->buffer = bbcode($this->contents);
  }

  function html_render()
  {
    if ( $this->buffer )
      return $this->buffer;

    return $this->buffer = bbcode($this->contents);
  }
}


?>
