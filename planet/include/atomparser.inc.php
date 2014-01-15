<?php
/* Copyright 2007
 *
 * - Simon Lopez < simon DOT lopez AT ayolo DOT org >
 *
 * Ce fichier fait partie du site de l'Association des étudiants
 * de l'UTBM, http://ae.utbm.fr.
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
 * Classe permettant de parser un flux atom
 */
class AtomParser
{
  private $xmlParser   = null;
  private $insideEntry = array();
  private $currentTag  = null;
  private $currentAttr = null;
  private $namespaces  = array('http://www.w3.org/2005/Atom' => 'ATOM 1');
  private $entryTags   = array('ENTRY');
  private $feedTags    = array('FEED');
  private $dateTags    = array('UPDATED','PUBDATE');
  private $hasSubTags  = array('AUTHOR');
  private $feeds       = array();
  private $entries     = array();
  private $entryIndex  = 0;
  private $url         = null;
  private $version     = null;

 /**
  * Constructeur
  */
  function __construct()
  {
    $this->xmlParser = xml_parser_create();
    xml_set_object($this->xmlParser, $this);
    xml_set_element_handler($this->xmlParser, "startElement", "endElement");
    xml_set_character_data_handler($this->xmlParser, "characterData");
  }

 /**
  * Retourne tous les "feed"
  * @access   public
  * @return   array tableau associatif
  */
  public function getFeeds()
  {
    return $this->feeds;
  }

 /**
  * Retourne toutes les "entry"
  * @access   public
  * @return   array tableau associatif
  */
  public function getEntries()
  {
    return $this->entries;
  }

 /**
  * Retourne le nombre d'entry
  * @access   public
  * @return   number
  */
  public function getTotalEntries()
  {
    return count($this->entries);
  }

 /**
  * Retourne "l'entry" numéro X
  * @access   public
  * @param    number  index de "l'entry"
  * @return   array   "l'entry" sous forme de tableau associatif
  */
  public function getEntry($index)
  {
    if($index < $this->getTotalEntries())
      return $this->entries[$index];
    else
      return false;
  }

 /**
  * Retourne un élément en fonction de son nom
  * @access   public
  * @param    string  nom de l'élément
  * @return   string
  */
  public function getFeed($tagName)
  {
    if(array_key_exists(strtoupper($tagName), $this->feeds))
      return $this->feeds[strtoupper($tagName)];
    else
      return false;
  }

 /**
  * Retourne l'url du flux
  * @access   public
  * @return   string
  */
  public function getParsedUrl()
  {
    if(empty($this->url))
      return false;
    else
      return $this->url;
  }

 /**
  * Retourne la version du flux
  * @access   public
  * @return   string
  */
   public function getFeedVersion()
   {
    return $this->version;
   }

 /**
  * Parses un flux
  * @access   public
  * @param    srting  l'url du flux
  */
  public function parse($url)
  {
    $this->url  = $url;
    $URLContent = $this->getUrlContent();
    if($URLContent)
    {
      $segments = str_split($URLContent, 4096);
      foreach($segments as $index=>$data)
      {
        $lastPiese =((count($segments)-1) == $index)? true : false;
        xml_parse($this->xmlParser, $data, $lastPiese)
           or die(sprintf("XML error: %s at line %d",
           xml_error_string(xml_get_error_code($this->xmlParser)),
           xml_get_current_line_number($this->xmlParser)));
      }
      xml_parser_free($this->xmlParser);
    }
    else
      die('Impossible de charger l\'url.');

    if(empty($this->version))
      die('Impossible de déterminer la version.');
  }

 /**
  * Charge le flux ATOM
  * @access   private
  * @return   string
  */
  private function getUrlContent()
  {
    if(empty($this->url))
      return false;
    if($content = @file_get_contents($this->url))
      return $content;
    else
    {
      $ch         = curl_init();
      curl_setopt($ch, CURLOPT_URL, $this->url);
      curl_setopt($ch, CURLOPT_HEADER, false);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      $content    = curl_exec($ch);
      $error      = curl_error($ch);
      curl_close($ch);
      if(empty($error))
        return $content;
      else
        return false;
    }
  }

 /**
  * gère l'évennement de début d'un tag pendant le "parsage"
  * @access   private
  * @param    object  l'objet xmlParser
  * @param    string  nom du tag
  * @param    array   tableau des attributs
  */
  private function startElement($parser, $tagName, $attrs)
  {
    if(!$this->version)
      $this->findVersion($tagName, $attrs);
    array_push($this->insideEntry, $tagName);
    $this->currentTag  = $tagName;
    $this->currentAttr = $attrs;
  }

 /**
  * gère l'évennement de fin d'un tag pendant le "parsage"
  * @access   private
  * @param    object  l'objet xmlParser
  * @param    string  nom du tag
  */
  private function endElement($parser, $tagName)
  {
    if($tagName=='ENTRY')
       $this->entryIndex++;
    array_pop($this->insideEntry);
    $this->currentTag = $this->insideEntry[count($this->insideEntry)-1];
  }

 /**
  * gère les données du tag pendant le "parssage"
  * @access   private
  * @param    object  un objet xmlParser
  * @param    string  un tag
  */
  private function characterData($parser, $data)
  {
    if(in_array($this->currentTag, $this->dateTags))
      $data = strtotime($data);

    if($this->inFeed())
    {
      if(in_array($this->getParentTag(), $this->hasSubTags))
      {
        if(! is_array($this->feeds[$this->getParentTag()]))
          $this->feeds[$this->getParentTag()] = array();
        $this->feeds[$this->getParentTag()][$this->currentTag] .= strip_tags($this->decodehtml((trim($data))));
        return;
      }
      else
      {
        if(! in_array($this->currentTag, $this->hasSubTags))
          $this->feeds[$this->currentTag] .= strip_tags($this->decodehtml((trim($data))));
      }

      if(!empty($this->currentAttr))
      {
        $this->feeds[$this->currentTag . '_ATTRS'] = $this->currentAttr;
        if(strlen($this->feeds[$this->currentTag]) < 2)
        {
          if(count($this->currentAttr) == 1)
            foreach($this->currentAttr as $attrVal)
              $this->feeds[$this->currentTag] = $attrVal;
          else
            $this->feeds[$this->currentTag] = $this->currentAttr;
        }
      }
    }
    elseif($this->inEntry())
    {
      if(in_array($this->getParentTag(), $this->hasSubTags))
      {
        if(! is_array($this->entries[$this->entryIndex][$this->getParentTag()]))
          $this->entries[$this->entryIndex][$this->getParentTag()] = array();
        if($this->currentTag=='LINK')
        {
          if(!empty($this->currentAttr)
             && isset($this->currentAttr['REL'])
             && $this->currentAttr['REL']=='alternate')
            $this->entries[$this->entryIndex][$this->getParentTag()][$this->currentTag]=$this->currentAttr['href'];
          return;
        }
        $this->entries[$this->entryIndex][$this->getParentTag()][$this->currentTag] .= strip_tags($this->decodehtml((trim($data))));
        return;
      }
      else
      {
        if(! in_array($this->currentTag, $this->hasSubTags))
          $this->entries[$this->entryIndex][$this->currentTag] .= strip_tags($this->decodehtml((trim($data))));
      }
      if(!empty($this->currentAttr))
      {
        $this->entries[$this->entryIndex][$this->currentTag . '_ATTRS'] = $this->currentAttr;
        if(strlen($this->entries[$this->entryIndex][$this->currentTag]) < 2)
        {
          if(count($this->currentAttr) == 1)
            foreach($this->currentAttr as $attrVal)
               $this->entries[$this->entryIndex][$this->currentTag] = $attrVal;
          else
             $this->entries[$this->entryIndex][$this->currentTag] = $this->currentAttr;
        }
      }
    }
  }

 /**
  * Trouve la version du flux
  * @access   private
  * @param    string  tag courrant
  * @param    array   tableau d'attributs
  * @return   void
  */
  private function findVersion($tagName, $attrs)
  {
    $namespace = array_values($attrs);
    foreach($this->namespaces as $value =>$version)
    {
      if(in_array($value, $namespace))
      {
        $this->version = $version;
        return;
      }
    }
  }

 /**
  * Retourne le tag parent
  * @access private
  * @return string
  */
  private function getParentTag()
  {
    return $this->insideEntry[count($this->insideEntry) - 2];
  }

 /**
  * Indique si l'on est dans un 'Feed'
  * @access   private
  * @return   bool
  */
  private function inFeed()
  {
    if($this->version == 'ATOM 1')
    {
      if(   in_array('FEED', $this->insideEntry)
         && !in_array('ENTRY', $this->insideEntry)
         && $this->currentTag != 'FEED')
      return true;
    }

    return false;
  }

 /**
  * Indique si on est dans une 'Entry'
  * @access   private
  * @return   bool
  */
  private function inEntry()
  {
    if($this->version == 'ATOM 1')
    {
      if(in_array('ENTRY', $this->insideEntry) && $this->currentTag != 'ENTRY')
      return true;
    }

    return false;
  }

 /**
  * Remplace le HTML codé par les bons caractères
  * @access   private
  * @param    string
  * @return   string
  */
  private function decodehtml($string)
  {
    $table = get_html_translation_table(HTML_ENTITIES, ENT_QUOTES);
    $table = array_flip($table);
    $table += array('&apos;' => "'");
    return strtr($string, $table);
  }
}

?>
