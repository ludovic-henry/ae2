<?php

/** @file
 *
 * @brief Fonctions diverses et variées.
 *
 */

/* Copyright 2004
 * - Alexandre Belloni <alexandre POINT belloni CHEZ utbm POINT fr>
 * - Thomas Petazzoni <thomas POINT petazzoni CHEZ enix POINT org>
 * - Pierre Mauduit <pierre POINT mauduit CHEZ utbm POINT fr>
 *
 * Ce fichier fait partie du site de l'Association des Ãtudiants de
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

 /* Definit si le site est en mode inscriptions intensives avec usage de l'API commune AE/BDS/INTEG/MMT */
 define('XMLRPC_USE',true);

/**
 * configurations générales
 */
$conf=array(
  'mailguard'=>'visible',//"codage des emails
  'phpok'=>false,
  'htmlok'=>false
);


/** Convertit la date en une chaîne human readable
 *
 * @param start Date de début au format YYYY-MM-DD HH:MM:SS. Si aucune
 * date de fin n'est donnée, alors une chaîne du type "Mardi 4 mai à
 * 14h00" sera générée.
 *
 * @param end Date de fin (optionnelle). Si elle est donnée, une
 * chaîne du type "Du mardi 4 mai à 14h00 au mardi 5 mai à 18h00" sera
 * générée.
 *
 * @return Une chaîne de caractère humainement lisible de la date ou
 * de l'intervalle de date.
 *
 * @deprecated
 */
function HumanReadableDate($start, $end="", $time = true, $year = false)
{
  $start = split("[-: ]", $start);
  $timestamp = mktime($start[3], $start[4], $start[5], $start[1], $start[2], $start[0]);

  if($end != "")
    {
      $end   = split("[-: ]", $end);
      $timestampend = mktime($end[3], $end[4], $end[5], $end[1], $end[2], $end[0]);
    }

  if(setlocale(LC_TIME, "fr_FR.UTF-8") == false)
    die( "Erreur de configuration des locales");

  /* Est-ce qu'une date de fin est donnée ? */
  if($end == "")
    {
    if ($time)
      {
        if ($year)
    return strftime("%A %e %B %Y &agrave; %Hh%M", $timestamp);
        else
    return strftime("%A %e %B &agrave; %Hh%M", $timestamp);
      }
    else
      {
        if ($year)
    return strftime("%A %e %B %Y", $timestamp);
        else
    return strftime("%A %e %B", $timestamp);
      }
    }
  else
    {
      /* Si les dates de début et de fin sont le mÃªme jour,
         on affiche un truc du style "lundi 4 mai de 14h00 à 18h00" */
      if($start[2] == $end[2] && $start[1] == $end[1] && $start[0] == $end[0])
  {
    if ($year)
      return (strftime("%A %e %B %Y de %Hh%M", $timestamp) . strftime(" &agrave; %Hh%M", $timestampend));
    else
      return (strftime("%A %e %B de %Hh%M", $timestamp) . strftime(" &agrave; %Hh%M", $timestampend));
  }
      /* Sinon, on affiche un truc du style "du lundi 4 mai à 14h00 au mardi 5 mai à 15h00" */
      else
  {
    if ($year)
      return (strftime("du %A %e %B %Y à %Hh%M", $timestamp) . strftime(" au %A %e %B à %Hh%M", $timestampend));
    else
      return (strftime("du %A %e %B à %Hh%M", $timestamp) . strftime(" au %A %e %B à %Hh%M", $timestampend));
  }
    }
}

/** Génère une liste de sélection pour un formulaire
 *
 * @param values Un tableau associatif (clé => valeur) donnant la
 * liste des éléments.
 *
 * @param current La clé de l'élement sélectionné
 *
 * @param name Le nom du champ dans le formulaire
 *
 * @return le code html genere
 * @deprecated
 */
function GenerateSelectList($values, $current, $name, $size=1)
{
  $buffer = ("<select name=\"" . $name . "\" size=\"" . $size . "\">\n");

  foreach ($values as $key => $val)
    {
      if($key != $current)
  {
    $buffer .= ("<option value=\"" . $key . "\">" . $val . "</option>\n");
  }
      else
  {
    $buffer .= ("<option value=\"" . $key . "\" selected>" . $val . "</option>\n");
  }
    }
  $buffer .=("</select>\n");
  return $buffer;
}

/** Génération de mot de passe
 * Cette fonction va générer une chaîne aléatoire de la longueur
 * spécifiée. C'est notamment utile pour générer des mots de passe.
 *
 * @param nameLength Longueur de la chaîne
 *
 * @return La chaîne aléatoire
 */
function genere_pass ($nameLength=12)
{
  $NameChars = 'abcdefghijklmnopqrstuvwxyz0123456789ABCDEFGHIJKMNLOP';
  $Vouel = 'aeiouAEIOU';
  $Name = "";

  for ($index = 1; $index <= $nameLength; $index++)
    {
      if ($index % 3 == 0)
  {
    $randomNumber = rand(1,strlen($Vouel));
    $Name .= substr($Vouel,$randomNumber-1,1);
  }
      else
  {
    $randomNumber = rand(1,strlen($NameChars));
    $Name .= substr($NameChars,$randomNumber-1,1);
  }
    }

  return $Name;
}
/** Vérification de l'email
 *
 * @param email L'email à vérifier
 * @param type  0 = email du type "prenom.nom"
 *              1 = email utbm
 *              2 = email assidu
 *              3 = email du type "truc@chose"
 *
 * @return 0 si valide, -1 si invalide
 */
function CheckEmail($email, $type = 0)
{
  /* L'email est vide, c'est pas bon. */
  if (empty($email))
    return false;

  if ($type == 0)
    {
      if (filter_var($email,FILTER_VALIDATE_EMAIL))
  return true;
      else
  return false;
    }

  /* Email utbm: prenom.nom@utbm.fr */
  elseif ($type == 1)
    {
      if (ereg("^([a-z\-]+)\.([a-z0-9\-]+)@utbm\.fr$", $email))
  return true;
      else
  return false;
    }

  elseif ($type == 2)
    {
      if (ereg("^([a-z\-]+)\.([a-z0-9\-]+)@assidu-utbm\.fr$", $email) ||
    ereg("^([a-z\-]+)\.([a-z0-9\-]+)-([0-9]{4})@assidu-utbm\.fr$", $email))
  return true;
      else
  return false;
    }

  elseif ($type == 3)
    {
      if (filter_var( $email,FILTER_VALIDATE_EMAIL))
  return true;
      else
  return false;
    }

  return false;
}

/* obtention de la revision actuelle du site (subversion) */
function get_rev ()
{
  return exec ("/usr/share/php5/exec/rev_info.sh");
}


/** Convertit un nom
  *
  * Cette fonction convertit un nom en majuscule, de manière à
  * uniformiser l'aspect des noms dans la base.
  *
  * @param nom Le nom à normaliser
  *
  * @result Le nom normalisé
  */
function convertir_nom($nom)
{
  return trim(mb_strtoupper($nom));
}

/** Convertit un prénom
  *
  * Cette fonction convertit un prénom en majuscule, de manière à
  * uniformiser l'aspect des prénoms dans la base.
  *
  * @param nom Le prénom à normaliser
  *
  * @result Le prénom normalisé
  */

function do_prenom_stuff($frags)
{
  $i = 1;
  $string = ucfirst(strtolower(trim($frags[0])));
  while($frags[$i])
  {
    if(!strstr($frags[$i],"-"))
      $string .= "-" . ucfirst(strtolower(trim($frags[$i])));
    $i++;
  }
  return $string;
}

function convertir_prenom ($string)
{
  $string = trim($string);

  // cas de l'exemple pierre-emmanuel, et de celui pierre - emmanuel
  if (strstr($string,"-"))
  {
    $frags = explode("-",$string);
    $string = do_prenom_stuff($frags);
  }

  // cas de l'exemple pierre emmanuel
  else if (strstr($string," "))
  {
    $frags = explode(" ",$string);
    $string = do_prenom_stuff($frags);
  }

  // cas de l'exemple laurent
  if (!(strstr($string," ")) && !(strstr($string,"-")))
  {
    $string = ucfirst(strtolower($string));
  }
  return $string;
}


/* Changement du séparateur pour &amp */
ini_set("arg_separator.output", "&amp;");

function utf8_enleve_accents ($text)
{
  $text = ereg_replace("(é|è|ê|ë|É|È|Ê|Ë)","e",$text);
  $text = ereg_replace("(à|â|ä|À|Â|Ä)","a",$text);
  $text = ereg_replace("(ï|î|Ï|Î)","i",$text);
  $text = ereg_replace("(ç|Ç)","c",$text);
  $text = ereg_replace("(ô|Ô|ò|Ò)","o",$text);
  $text = ereg_replace("(ù|ü|û|Ü|Û|Ù)","u",$text);
  $text = ereg_replace("(ñ|Ñ)","n",$text);
  return $text;
}

function utf8_pattern_accents ($text)
{
  $text = ereg_replace("(e|é|è|ê|ë|É|È|Ê|Ë)","(e|é|è|ê|ë|É|È|Ê|Ë)",$text);
  $text = ereg_replace("(a|à|â|ä|À|Â|Ä)","(a|à|â|ä|À|Â|Ä)",$text);
  $text = ereg_replace("(i|ï|î|Ï|Î)","(i|ï|î|Ï|Î)",$text);
  $text = ereg_replace("(c|ç|Ç)","(c|ç|Ç)",$text);
  $text = ereg_replace("(u|ù|ü|û|Ü|Û|Ù)","(u|ù|ü|û|Ü|Û|Ù)",$text);
  $text = ereg_replace("(n|ñ|Ñ)","(n|ñ|Ñ)",$text);
  return $text;
}


/**
 * Cette fonction génère un identifiant unique
 * \return string identifiant unique
 */
function gen_uid ()
{
  return str_replace('.','_',uniqid('', true));
}

/**
 * Cette fonction permet de loguer une action
 * @param $dbrw Lien à la base de données en lecture et écriture.
 * @param $action_log Action effectuée (exemple : Suppression facture)
 * @param $description_log Détails de l'opération effectuée
 * @param $context_log Contexte dans lequel l'opération a été effectuée (compteae, rezome...)
 * @param $user utilisateur effectuant l'action
 */
function _log(&$dbrw, $action_log, $description_log, $context_log, &$user)
{
  if(!is_a($user,'utilisateur') || !$user->is_valid())
  {
    throw new Exception('Il faut un objet de type utilisateur (ou en héritant !) valide');
    return false;
  }
  $time_log = date("Y-m-d H:i:s", time());
  $req = new insert($dbrw, "logs", array( "id_utilisateur" => $user->id,
                                          "time_log" => $time_log,
                                          "action_log" => $action_log,
                                          "description_log" => $description_log,
                                          "context_log" => $context_log ));
  return true;
}

?>
