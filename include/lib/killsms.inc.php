<?php

/** @file
 *
 * @brief Classe de traduction du language sms et d'évaluation de qualité
 *
 */

/* Copyright 2007
 *
 * - Simon Lopez <simon POINT lopez AT ayolo POINT org>
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

define( 'NO_PONCTUATION' , 10);
define( 'NO_UPPERCASE' , 5);
define( 'SMS_WORD' , 4);
define( 'MIXED_CASE', 3);
define( 'NUMBERS', 2); //nombre dans un mot
define( 'ACCEPTABLE_LIMIT', 10);

/**
 * Traduction du language sms et d'évaluation de qualité
 *
 * Accessoire indispensable aux extremistes du français :p
 *
 * @author Simon Lopez
 * @ingroup useless
 */
class killsms
{
  var $dico = array();
  var $db;
  var $dbrw;

  function killsms($db, $dbrw)
  {
    $this->db=$db;
    $this->dbrw=$dbrw;
  }
  function is_sms($text,$explain=FALSE)
  {
    if (empty($this->dico))
      $this->get_dico();
    if($explain)
      $e = TRUE;
    $points = 0;
    if($e)
    {
      $foundErrors = array("words"=>array(),"mixedcase"=>array(),"numbers"=>array());
      $errors = array();
    }

    $text = strip_tags(trim($text));
    $text = strtr($text,"àâäéèêëçùûüôöÀÂÉÈÊËÇÙÛÜÔÖ","aaaeeeecuuuooAAEEEECUUUOO");
    $text = ereg_replace("([!?.]){2,}","\\1",$text);
    if(!ereg("[.?!]",$text))
    {
      $points += NO_PONCTUATION;
      if($e)
        $errors[] = "Aucune ponctuation.";
      $sentences = explode(". ",ereg_replace("[?!]",".",$text));
      foreach($sentences as $s)
      {
        if(ereg("^[a-z]",trim($s)))
        {
          $points += NO_UPPERCASE;
          $errors[] = "Pas de majuscule au début de cette phrase: ".$s;
        }
      }
      $text = ereg_replace("[.,;:]"," ",$text);
      $text = ereg_replace("[ ]{2,}"," ",$text);
      $text = explode(" ",stripslashes($text));
      foreach($text as $w)
      {
        if(!ereg("^[a-zA-Z0-9]+$",$w) || ereg("^[0-9]+$",$w))
          continue;
        if(array_key_exists(str_replace($mot,$this->dico))
        {
          $points += SMS_WORD;
          if($e)
            $foundErrors['words'][] = $w;
        }
        elseif(ereg("^(([A-Z]+[a-z]+){2,}|([a-z]+[A-Z]+[a-z]*)+)$",$w))
        {
          $points += MIXED_CASE;
          if($e)
            $foundErrors['mixedcase'][] = $w;
        }
        elseif(ereg("[0-9]+",$w) && !ereg("^[A-Z]{2}[0-9]$",$w))
        {
          $points += NUMBERS;
          if($e)
            $foundErrors['numbers'][] = $w;
        }
      }
      if($e)
      {
        if(count($foundErrors['words']) > 0)
          $errors[] = 'Mots correspondants au dictionnaire SMS: '.implode(", ",$foundErrors['words']);
        if(count($foundErrors['mixedcase']) > 0)
          $errors[] = 'Mots contenant des mAjUsCuLeS: '.implode(", ",$foundErrors['mixedcase']);
        if(count($foundErrors['numbers']) > 0)
          $errors[] = 'Mots contenant des chiffres: '.implode(", ",$foundErrors['numbers']);
        return array("score"=>$points,"errors"=>$errors);
      }
      else
      {
        if($point > ACCEPTABLE_LIMIT)
          return $points;
        else
          return false;
      }
    }
  }

  function traduire($texte)
  {
    if (empty($this->dico))
      $this->get_dico();
    $texte = ereg_replace("[ ]{2,}"," ",$texte);
    $texte = eregi_replace("[^a-z0-9éàèùêûôâç ]","",$texte);
    $mots = explode(" ",$texte);
    foreach($mots as $i=>$mot)
      if(array_key_exists($mot,$this->dico))
        $mots[$i] = $this->dico[$mot];
    $texte = implode(" ",$mots);
    return $texte;
  }

  function get_dico()
  {
    /*requette sql qui va bien pour chopper les mots dans la base */
    $req = new requete($this->db, "SELECT sms,french FROM `sms_translations`");
    while (list($sms,$french) = $req->get_row())
      $word[$sms]=$french;
  }

}

?>
