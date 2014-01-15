<?php

/* Copyright 2010
 * - Mathieu Briand < briandmathieu AT hyprua DOT org >
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

define('FPDF_FONTPATH', $topdir . 'font/');

require_once($topdir . "include/lib/fpdf.inc.php");
require_once($topdir."include/entities/files.inc.php");

class pdfplanning_news extends FPDF
{

  var $xmargin;
  var $ymargin;

  var $positions;
  var $dimensions;

  function pdfplanning_news($db, $title)
  {
    global $topdir;

    $this->FPDF("L", "pt");

    $this->db = $db;
    $this->title = $title;
    $this->xmargin = 10;
    $this->ymargin = 15;
    $this->xmargin_b = 5;
    $this->ymargin_b = 7;
    $this->title_h = 30;
    $this->title_fontsize = 24;
    $this->cell_h = 12;
    $this->fontsize = 8;
    $this->space = 12;
    $this->vspace = 12;
    $this->section_space = 15;
    $this->background_file = 5418;

    $this->colors = array ( 1 => array('r' => 255, 'g' => 0, 'b' => 0),
                            2 => array('r' => 255, 'g' => 255, 'b' => 0),
                            3 => array('r' => 0, 'g' => 255, 'b' => 0),
                            4 => array('r' => 0, 'g' => 255, 'b' => 255),
                            5 => array('r' => 0, 'g' => 0, 'b' => 255),
                            6 => array('r' => 255, 'g' => 0, 'b' => 255),
                            7 => array('r' => 255, 'g' => 127, 'b' => 127),
                            'sem' => array('r' => 127, 'g' => 127, 'b' => 255));

    $this->evenements = array();
    $this->reguliers = array();
    $this->semaine = array();

    $this->SetAutoPageBreak(false);
    $this->AddPage();
  }

  function set_options($xmargin, $ymargin, $xmargin_b, $ymargin_b, $title_h, $title_fontsize, $cell_h, $fontsize, $space, $vspace, $section_space, $background_file)
  {
    $this->xmargin = $xmargin;
    $this->ymargin = $ymargin;
    $this->xmargin_b = $xmargin_b;
    $this->ymargin_b = $ymargin_b;
    $this->title_h = $title_h;
    $this->title_fontsize = $title_fontsize;
    $this->cell_h = $cell_h;
    $this->fontsize = $fontsize;
    $this->space = $space;
    $this->vspace = $vspace;
    $this->section_space = $section_space;
    $this->background_file = $background_file;
  }

  function render()
  {
    $file = new dfile($this->db, $this->dbrw);
    $file->load_by_id($this->background_file);
    if ($file->is_valid())
      $this->Image($file->get_real_filename(), $this->xmargin_b, $this->ymargin_b,
                  $this->w-$this->xmargin_b*2, $this->h-$this->ymargin_b*2,
                  substr(strrchr($file->nom_fichier, '.'), 1));

    $this->SetFont('Courier', '', $this->title_fontsize);
    $this->SetXY($this->xmargin, $this->ymargin);
    $this->Cell($this->w-($this->xmargin*2), $this->ymargin, utf8_decode($this->title), 0, 0, "C");

    $this->SetFont('Arial', '', $this->fontsize);

    $this->days = array_unique(array_merge(array_keys($this->evenements), array_keys($this->reguliers)));
    sort($this->days);
    $numdays = count($this->days);

    $this->larg = ($this->w - 2*$this->xmargin - ($numdays-1)*$this->space) / $numdays;

    $endpos = $this->render_daynames($this->ymargin + $this->title_h);
    $endpos = $this->render_days($this->evenements, $endpos + $this->section_space);
    $endpos = $this->render_days($this->reguliers, $endpos + $this->section_space);
    $this->render_week($this->semaine, $endpos + $this->section_space);
  }

  function add_texte($day, $texte)
  {
    if ($texte[2] == 0)
      $this->semaine[] = $texte;
    elseif ($texte[2] == 1)
      $this->evenements[$day][] = $texte;
    elseif ($texte[2] == 2)
      $this->reguliers[$day][] = $texte;
  }

  function render_daynames($ymargin)
  {
    global $topdir;
    $daynames = array(1 => 'Lundi',
                      2 => 'Mardi',
                      3 => 'Mercredi',
                      4 => 'Jeudi',
                      5 => 'Vendredi',
                      6 => 'Samedi',
                      7 => 'Dimanche');

    $x = $this->xmargin;
    foreach($this->days as $day)
    {
      $colors = $this->colors[$day];

      $this->SetXY($x, $ymargin);
      $this->SetDrawColor($colors['r'], $colors['g'], $colors['b']);
      $this->Image($topdir."images/plannings/haut_".$day.".gif", null, null, $this->larg);
      $this->SetFillColor($colors['r'], $colors['g'], $colors['b']);
      $this->MultiCell($this->larg, $this->cell_h, $daynames[$day], 'TB', 'C', true);
      $this->SetX($x);
      $this->Image($topdir."images/plannings/bas_".$day.".gif", null, null, $this->larg);

      $x += $this->larg + $this->space;
    }
    return $this->getY();
  }

  function render_days($data, $ymargin)
  {
    global $topdir;
    $endpos = 0;

    $x = $this->xmargin;
    $y = $ymargin;

    foreach($this->days as $day)
    {
      $colors = $this->colors[$day];

      $this->SetXY($x, $y);
      $this->SetDrawColor($colors['r'], $colors['g'], $colors['b']);

      if (isset($data[$day]))
      {
        foreach($data[$day] as $texte)
        {
          $this->SetX($x);
          $this->Image($topdir."images/plannings/haut_".$day.".gif", null, null, $this->larg);

          if ($texte[0] != '')
          {
            $this->SetFillColor(255);
            $this->SetX($x);
            $this->myMultiCell($this->larg, $this->cell_h, utf8_decode($texte[0]));
          }

          $this->SetFillColor($colors['r'], $colors['g'], $colors['b']);
          $this->SetX($x);
          $this->myMultiCell($this->larg, $this->cell_h, utf8_decode($texte[1]));
          $this->SetX($x);
          $this->Image($topdir."images/plannings/bas_".$day.".gif", null, null, $this->larg);
          $this->SetY($this->getY() + $this->vspace);
        }
      }
      $x += $this->larg + $this->space;
      $endpos = max($endpos, $this->getY());
    }
    return $endpos;
  }

  function render_week($data, $ymargin)
  {
    global $topdir;

    $y = $ymargin;

    $max_w = 0;
    foreach($this->semaine as $texte)
      $max_w = max($max_w, $this->GetStringWidth(utf8_decode($texte[1])));
    $w = $max_w + 30;
    $x = ($this->w - $max_w) / 2;

    foreach($this->semaine as $texte)
    {
      $this->SetX($x);
      $this->Image($topdir."images/plannings/haut_sem.gif", null, null, $w);

      $texte = "";
      if ($texte[0] != '')
        $texte = $texte[0]." : ";

      $texte .= $texte[1];

      $this->SetFillColor($colors['r'], $colors['g'], $colors['b']);
      $this->SetX($x);
      $this->myMultiCell($w, $this->cell_h, utf8_decode($texte));
      $this->SetX($x);
      $this->Image($topdir."images/plannings/bas_sem.gif", null, null, $w);
      $this->SetY($this->getY() + $this->vspace);
    }
    return $this->getY();
  }

  // Multi Cell sans le bug de couleur de fond...
  function myMultiCell($w, $h, $txt)
  {
    $x = $this->GetX();
    $y = $this->GetY();
    $lignes = array();
    $ligne = "";
    $mots = explode(' ', $txt);

    foreach($mots as $mot)
    {
      if ($this->GetStringWidth($ligne . " " . $mot) <= $w)
      {
        if ($ligne != "")
          $ligne .= " ";
        $ligne .= $mot;
      }
      else
      {
        $lignes[] = $ligne;
        $ligne = $mot;
      }
    }
    $lignes[] = $ligne;

    $this->Rect($x, $y, $w, $h*count($lignes), 'FD');

    foreach($lignes as $ligne)
      $this->Cell($w, $h, $ligne, 0, 2, 'C');
  }

}
?>
