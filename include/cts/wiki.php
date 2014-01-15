<?php

/** @file
 *
 *
 */
/* Copyright 2005,2006
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
 * @defgroup display Affichage
 */

/**
 * @defgroup display_cts Contents
 * @ingroup display
 */


/** Conteneur de diffwiki
 * @ingroup display_cts
 */
class diffwiki extends stdcontents
{
  var $action;
  var $name;

  /** Initialise un formulaire
   * @param $name      Nom du formulaire
   * @param $action    Fichier sur le quel le formulaire sera envoyé
   * @param $row       Un table de "diff"
   * @param $method    Méthode à utiliser pour envoyer les données (post ou get)
   * @param $title     Titre du formulaire (facultatif)
   */
  function diffwiki ( $name, $action, $rows, $method = "post", $title = false )
  {
    $this->name = $name;
    $this->title = $title;
    if(!is_array($rows)||empty($rows))
      return;
    $this->buffer ="<form action=\"$action\" method=\"".strtolower($method)."\"";
    $this->buffer.=" name=\"frm_".$this->name."\" id=\"frm_".$this->name."\" >";
    $this->buffer.="<div class=\"form\">\n";
    $this->buffer .= "<div class=\"formrow\">";
    $this->buffer .= "<div class=\"formfield\">";
    $this->buffer .= "<input type=\"submit\" id=\"submit_t\" name=\"submit\" value=\"Voir les différences\" class=\"isubmit\" />";
    $this->buffer .= "</div></div>\n";
    $this->buffer.="<div class=\"formrow\">\n";
    $this->buffer.="<ul class=\"diff\" id=\"$name\">\n";
    $i=0;
    foreach ( $rows as $row )
    {
      $this->buffer.="<li>";
      $this->buffer.="<input type=\"radio\" name=\"rev_comp\" class=\"radiobox\" value=\"".$row['value']."\" id=\"__rev_comp_".$row['value']."\"";
      if($i==0)
        $this->buffer.=" style=\"visibility:hidden\" checked=\"checked\"";
      if($i==1)
        $this->buffer.=" checked=\"checked\"";
      $this->buffer.=" />&nbsp;";
      $this->buffer.="<input type=\"radio\" name=\"rev_orig\" class=\"radiobox\" value=\"".$row['value']."\" id=\"__rev_orig_".$row['value']."\"";
      if($i==0)
        $this->buffer.=" checked=\"checked\"";
      $this->buffer.=" />&nbsp;";
      $this->buffer.=$row['desc'];
      $this->buffer.="</li>\n";
      $i++;
    }
    $this->buffer.= "</ul>\n";
    $this->buffer.= "</div>\n";
    $this->buffer .= "<div class=\"formrow\">";
    $this->buffer .= "<div class=\"formfield\">";
    $this->buffer .= "<input type=\"submit\" id=\"submit_b\" name=\"submit\" value=\"Voir les différences\" class=\"isubmit\" />";
    $this->buffer .= "</div></div>\n";
    $this->buffer.= "<div class=\"clearboth\"></div>\n";
    $this->buffer.= "</div>\n";
    $this->buffer.= "</form>\n";
  }
}

/* WARNING !
  pas d'héritage multiple en php donc on va faire àa du mieux qu'on peut !
*/
require_once $topdir. "include/lib/text_diff/Diff.php";
require_once $topdir. "include/lib/text_diff/Diff/Renderer.php";
require_once $topdir. "include/lib/text_diff/Diff/Renderer/inline.php";
class diff extends Text_Diff_Renderer
{
  var $title;
  var $divid=null;
  var $cssclass=null;
  var $buffer;
  var $toolbox;

  var $_leading_context_lines = 10000;
  var $_trailing_context_lines = 10000;
  var $_ins_prefix = '<span class="diffins">';
  var $_ins_suffix = '</span>';
  var $_del_prefix = '<span class="diffdel">';
  var $_del_suffix = '</span>';
  var $_block_header = '';
  var $_split_level = 'lines';

  function set_toolbox ( $cts )
  {
  }
  function set_help_page ( $page )
  {
  }
  function html_render ()
  {
    return $this->buffer;
  }

  function _blockHeader($xbeg, $xlen, $ybeg, $ylen)
  {
    return $this->_block_header;
  }
  function _startBlock($header)
  {
    return $header;
  }
  function _lines($lines, $prefix = ' ', $encode = true)
  {
    if ($encode)
      array_walk($lines, array(&$this, '_encode'));
    if ($this->_split_level == 'words')
      return implode('', $lines);
    else
      return implode("\n", $lines) . "\n";
  }
  function _added($lines)
  {
    array_walk($lines, array(&$this, '_encode'));
    $lines[0] = $this->_ins_prefix . $lines[0];
    $lines[count($lines) - 1] .= $this->_ins_suffix;
    return $this->_lines($lines, ' ', false);
  }
  function _deleted($lines, $words = false)
  {
    array_walk($lines, array(&$this, '_encode'));
    $lines[0] = $this->_del_prefix . $lines[0];
    $lines[count($lines) - 1] .= $this->_del_suffix;
    return $this->_lines($lines, ' ', false);
  }
  function _changed($orig, $final)
  {
    if ($this->_split_level == 'words')
    {
      $prefix = '';
      while ($orig[0] !== false
             && $final[0] !== false
             && substr($orig[0], 0, 1) == ' '
             && substr($final[0], 0, 1) == ' ')
      {
        $prefix .= substr($orig[0], 0, 1);
        $orig[0] = substr($orig[0], 1);
        $final[0] = substr($final[0], 1);
      }
      return $prefix . $this->_deleted($orig) . $this->_added($final);
    }
    $text1 = implode("\n", $orig);
    $text2 = implode("\n", $final);
    $nl = "\0";
    $diff = new Text_Diff($this->_splitOnWords($text1, $nl),
                          $this->_splitOnWords($text2, $nl));
    $renderer = new Text_Diff_Renderer_inline(array_merge($this->getParams(),
                                              array('split_level' => 'words')));
    return str_replace($nl, "\n", $renderer->render($diff)) . "\n";
  }
  function _splitOnWords($string, $newlineEscape = "\n")
  {
    $string = str_replace("\0", '', $string);
    $words = array();
    $length = strlen($string);
    $pos = 0;
    while ($pos < $length)
    {
      $spaces = strspn(substr($string, $pos), " \n");
      $nextpos = strcspn(substr($string, $pos + $spaces), " \n");
      $words[] = str_replace("\n", $newlineEscape, substr($string, $pos, $spaces + $nextpos));
      $pos += $spaces + $nextpos;
    }
    return $words;
  }
  function _encode(&$string)
  {
    $string = htmlspecialchars($string);
  }

  function diff($old,$new)
  {
    $this->title="Différences entre les révisions ".$old['rev']." et ".$new['rev'];
    $diff = &new Text_Diff('auto',array(split("\n",$old['cts']),split("\n",$new['cts'])));
    $lines = split("\n",$this->render($diff));
    foreach($lines as $line)
      $this->buffer.=$line."<br />";
  }
}

?>
