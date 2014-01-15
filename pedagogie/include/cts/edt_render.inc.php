<?

/** @file
 *
 * Generation d'emplois du temps a la volee
 *
 */
/* Copyright 2006
 * - Pierre Mauduit <pierre POINT mauduit CHEZ utbm POINT fr>
 *
 * Inspire fortement du travail de MasterJul (Julien Ehrhart)
 * - Julien Ehrhart <julien POINT ehrhart CHEZ utbm POINT fr>
 *
 *
 * Ce fichier fait partie du site de l'Association des Ã‰tudiants de
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

require_once ($topdir . "include/globals.inc.php");


/* necessite la lib php gd2 */

/* La classe emploi du temps (edt) */
class edt_img
{
  /* une image GD emploi du temps */
  var $img;
  /* une image GD logo eventuel */
  var $logo;

  /* un nom */
  var $name;

  /* un tableau de lignes (format MasterJul) */
  /*
   * Specification du format
   * $lines(0 => array ("semaine_seance" => "A",                  'A' semaine A, 'B' semaine B, null toutes les semaines
   *                    "hr_deb_seance" => "08h00",
   *                    "hr_fin_seance" => "10h00",
   *                    "jour_seance" => "Lundi",
   *                    "type_seance" => "TD",
   *                    "grp_seance" => "3",
   *                    "nom_uv" => "IN41",
   *                    "salle_seance" => "B404"),
   *        ...)
   *
   * Il est possible que certains champs manquent, mais d'apres le parcours
   * des sources originales, il semblerait qu'il y ait tout.
   *
   */
  var $lines;

  /* un tableau de couleurs */
  var $colors;
  /* police */
  var $font;
  /* credits */
  var $credits;

  /* dimensions et variables diverses */
  var $dim;

  /* les plages horaires */
  var $horaires;

  /* couleur actuelle */
  var $curr_color;

  /* les affectations de couleurs */
  var $fillcolor;

  /* constructeur */
  function edt_img($name, $lines, $logo = false, $printsem=true, $zonemidi=true)
  {
    global $topdir;
    $this->printsem = ($printsem!=false);
    $this->name = $name . " - UTBM";
    $this->credits = "";

    /* taille totale de l'image */
    $this->dim['width'] = 800;
    $this->dim['height'] = 850;

    /* police */
    $this->font = $topdir ."font/verdana.ttf";

    /* l'entete (titre, logo ...) prend 70 px */
    $this->dim['entete'] = 70;
    /* marge de gauche de 45 px */
    $this->dim['mg'] = 45;
    /* largeur de colone de 125 pixels */
    $this->dim['lj'] = 125;
    /* Les journées commencent à 8 heures du matin
     * (60 * 8) - échelle de 1px par minute          */
    $this->dim['dh'] = 480;
    /* horaires */
    $this->horaires = array(array("8","00"),
                            array("9","00"),
                            array("10","00"),
                            array("10","15"),
                            array("11","15"),
                            array("12","15"),
                            array("13","00"),
                            array("14","00"),
                            array("15","00"),
                            array("16","00"),
                            array("16","15"),
                            array("17","15"),
                            array("18","15"),
                            array("19","15"),
                            array("20","15"));
    /* logo ? */
    if (file_exists($logo))
      {
        $this->logo = imagecreatefrompng($logo);
      }
    else
      $this->logo = false;

    $this->lines = $lines;

    /* on commence la generation de l'edt à proprement parler ici */

    /* creation de l'image GD */
    $this->img = imagecreatetruecolor ($this->dim['width'],
                                       $this->dim['height']);


    /* definition des couleurs */
    $this->colors['blanc']           = imagecolorallocate ($this->img,255,255,255);
    $this->colors['noir']            = imagecolorallocate ($this->img,0,0,0);
    $this->colors['rouge']           = imagecolorallocate ($this->img,140,30,30);
    $this->colors['bleu']            = imagecolorallocate ($this->img,50,89,122);
    $this->colors['bleu_mat']        = imagecolorallocate ($this->img,140,204,214);
    $this->colors['bleu_clair']      = imagecolorallocate ($this->img,131,170,203);
    $this->colors['rouge_mat']       = imagecolorallocate ($this->img,228,174,158);
    $this->colors['violet_mat']      = imagecolorallocate ($this->img,230,195,231);
    $this->colors['beige_mat']       = imagecolorallocate ($this->img,224,197,166);
    $this->colors['rose_pale_mat']   = imagecolorallocate ($this->img,222,194,194);
    $this->colors['jaune']           = imagecolorallocate ($this->img,226,225,122);
    $this->colors['vert']             = imagecolorallocate ($this->img,179,212,126);
    $this->colors['gris']            = imagecolorallocate ($this->img,192,192,192);
    $this->colors['orange_mat']      = imagecolorallocate ($this->img,216,119,84);
    $this->colors['sable_mat']       = imagecolorallocate ($this->img,235,217,141);
    $this->colors['violet_gris_mat'] = imagecolorallocate ($this->img,161,178,203);
    $this->colors['vert_pale_mat']   = imagecolorallocate ($this->img,219,186,10);
    $this->colors['vert_mat']        = imagecolorallocate ($this->img,102,185,46);

    // reassociation tableau afin de pouvoir acceder aux elements a
    // l'aide d'un identifiant numerique.
    $i = 0;
    foreach ($this->colors as $curcol)
      $this->colors[$i++] = $curcol;

    imagefill($this->img,0,0, $this->colors['blanc']);


    // Trame de fond hachurée

    for($i = $this->dim['mg'] + 1, $j = $this->dim['entete'] + 1;
        $i < $this->dim['width'] * 2, $j < $this->dim['height'] * 2;
        $i+=10, $j+=10)
      imageline($this->img,
                $i,
                $this->dim['entete'] + 1,
                $this->dim['mg'] + 1,
                $j,
                $this->colors['gris']);

    imagefilledrectangle($this->img,
                         0,
                         $this->dim['height'] - 15,
                         $this->dim['width'] - 2,
                         $this->dim['height'] - 2,
                         $this->colors['blanc']);

    imagefilledrectangle($this->img,
                         $this->dim['width'] - 5,
                         $this->dim['mg'],
                         $this->dim['width'] - 2,
                         $this->dim['height'] - 2,
                         $this->colors['blanc']);
    // Cadre de la semaine avec bordure doublï¿½e


    imagerectangle ($this->img,
                    $this->dim['mg'],
                    $this->dim['entete'] - 1,
                    $this->dim['width'] - 5,
                    $this->dim['height'] - 16,
                    $this->colors['noir']);

    imagerectangle ($this->img,
                    $this->dim['mg'] + 1,
                    $this->dim['entete'],
                    $this->dim['width'] - 6,
                    $this->dim['height'] - 17,
                    $this->colors['noir']);

    // Colonnes des jours et leur ï¿½paisseur doublï¿½e
    // Mardi
    imagerectangle ($this->img,
                    $this->dim['mg'] + $this->dim['lj'],
                    $this->dim['entete'],
                    $this->dim['mg'] + $this->dim['lj'] * 2,
                    $this->dim['height'] - 16,
                    $this->colors['noir']);

    imagerectangle ($this->img,
                    $this->dim['mg'] + $this->dim['lj'] + 1,
                    $this->dim['entete'],
                    $this->dim['mg'] + $this->dim['lj'] * 2 + 1,
                    $this->dim['height'] - 16,
                    $this->colors['noir']);
    // Jeudi
    imagerectangle ($this->img,
                    $this->dim['mg'] + $this->dim['lj'] * 3,
                    $this->dim['entete'],
                    $this->dim['mg'] + $this->dim['lj'] * 4,
                    $this->dim['height'] - 16,
                    $this->colors['noir']);

    imagerectangle ($this->img,
                    $this->dim['mg'] + $this->dim['lj'] * 3 + 1,
                    $this->dim['entete'],
                    $this->dim['mg'] + $this->dim['lj'] * 4 + 1,
                    $this->dim['height'] - 16,
                    $this->colors['noir']);
    // Samedi
    imagerectangle ($this->img,
                    $this->dim['mg'] + $this->dim['lj'] * 5,
                    $this->dim['entete'],
                    $this->dim['mg'] + $this->dim['lj'] * 6,
                    $this->dim['height'] - 16,
                    $this->colors['noir']);

    imagerectangle ($this->img,
                    $this->dim['mg'] + $this->dim['lj'] * 5 + 1,
                    $this->dim['entete'],
                    $this->dim['mg'] + $this->dim['lj'] * 6,
                    $this->dim['height'] - 16,
                    $this->colors['noir']);

    // Affichage des jours
    $jour = array("Lundi","Mardi","Mercredi","Jeudi","Vendredi","Samedi");
    imagettftext($this->img,10,0,90,63,
                 $this->colors['noir'],$this->font,$jour[0]);
    imagettftext($this->img,10,0,220,63,
                 $this->colors['noir'],$this->font,$jour[1]);
    imagettftext($this->img,10,0,330,63,
                 $this->colors['noir'],$this->font,$jour[2]);
    imagettftext($this->img,10,0,465,63,
                 $this->colors['noir'],$this->font,$jour[3]);
    imagettftext($this->img,10,0,580,63,
                 $this->colors['noir'],$this->font,$jour[4]);
    imagettftext($this->img,10,0,710,63,
                 $this->colors['noir'],$this->font,$jour[5]);
    // Affichage des horaires classiques
    $hstd = $this->horaires;
    for($i = 0;$i < count($this->horaires);$i++)
      {
        imageline($this->img,
                  $this->dim['mg'] - 4,
                  $hstd[$i][0] * 60 + $hstd[$i][1] - $this->dim['dh'] + $this->dim['entete'],
                  $this->dim['mg'] + 4,
                  $hstd[$i][0] * 60 + $hstd[$i][1] - $this->dim['dh'] + $this->dim['entete'],
                  $this->colors['noir']);

        $ecart = $hstd[$i][0] * 60 + $hstd[$i][1] - ($hstd[$i+1][0]*60+$hstd[$i+1][1]);

        // Si les heures sont rapprochées, on décale celle du dessus
        if(($ecart > -5) && ($ecart < 0))
          $j=7;
        else $j=0;

        imagettftext($this->img,
                     7,
                     0,
                     7,
                     $hstd[$i][0] * 60 + $hstd[$i][1] - $this->dim['dh'] +
                     $this->dim['entete'] + 3 - $j,
                     $this->colors['noir'],
                     $this->font,
                     $hstd[$i][0] ."h". $hstd[$i][1]);
      }
    if ($zonemidi)
    {
      // Zone du temps de midi
      imagefilledrectangle($this->img,
                           $this->dim['mg'] + 1,
                           12 * 60 + 15 - $this->dim['dh'] + $this->dim['entete'],
                           $this->dim['mg'] + $this->dim['lj'] * 6 - 1,
                           13 * 60 + 00 - $this->dim['dh'] + $this->dim['entete'],
                           $this->colors['bleu_clair']);

      imagerectangle($this->img,
                     $this->dim['mg'] + 1,
                     12 * 60 + 15 - $this->dim['dh'] + $this->dim['entete'],
                     45 + $this->dim['lj'] * 6 - 1,
                     13 * 60 + 00 - $this->dim['dh'] + $this->dim['entete'],
                     $this->colors['noir']);

      imagerectangle($this->img,
                     $this->dim['mg'] + 1,
                     12 * 60 + 15 - $this->dim['dh'] + $this->dim['entete'] + 1,
                     45 + $this->dim['lj'] * 6 - 1,
                     13 * 60 + 00 - $this->dim['dh'] + $this->dim['entete'] - 1,
                     $this->colors['noir']);

      // Zone du samedi aprem
      imagefilledrectangle($this->img,
                           $this->dim['mg'] + $this->dim['lj'] * 5 + 2,
                           13 * 60 + 00 - $this->dim['dh'] + $this->dim['entete'] + 1,
                           $this->dim['mg'] + $this->dim['lj'] * 6 - 2,
                           $this->dim['height'] - 18,
                           $this->colors['bleu_clair']);
    }

    // Affichage de l'établissement
    //imagettftext($this->img,10,0,$this->dim['mg'],42,$noir,$police,$NomEtab);

    // Date de génération

    $date=date("d-m-Y");
    $heure=date("G\hi");
    imagettftext($this->img,
                 7,
                 0,
                 $this->dim['mg'],
                 $this->dim['height'] - 5,
                 $this->colors['noir'],
                 $this->font, "AE UTBM - http://ae.utbm.fr/");

    // Signature / credits

    imagettftext($this->img,
                 7,
                 0,
                 578,
                 $this->dim['height'] - 5,
                 $this->colors['noir'],
                 $this->font,
                 $this->credits);

    // Placement du logo
    if ( $this->logo )
      imagecopy($this->img,$this->logo,650,7,0,0,142,42);


    /* titrage */
    imagettftext($this->img,
                 12,
                 0,
                 $this->dim['mg'],
                 23,
                 $this->colors['rouge'],
                 $this->font,
                 $this->name);

    /* couleur actuelle */
    $this->curr_color = 5;

    // Cadre principal de l'image
    imagerectangle ($this->img,
                    0,
                    0,
                    $this->dim['width'] - 1,
                    $this->dim['height'] - 1,
                    $this->colors['noir']);

    /* affectation de couleurs */
    $this->fillcolor = array();
  }

  /* tracage d'un cours */
  function draw_course ($line)
  {
    /* variables WTF ? */
    $DVNum     = 34;
    $DVGroupe  = 58;
    $DVMatiere = 18;
    $DVSalle   = 48;

    // Permet d'indiquer sur quel attribut on fait varier la couleur
    // Utilisée à l'origine car on pouvait faire des EDT de classe, salle ou enseignant
    $ColorSwitch="nom_uv";

    // Si on n'a pas besoin d'afficher le groupe
    // On prend un peu de place pour écarter la salle
    if(empty($line['semaine_seance']) || $line['semaine_seance'] == null)
      $DVSalle+=6;

    if(!$this->fillcolor[$line[$ColorSwitch]])
      $this->fillcolor[$line[$ColorSwitch]] = $this->colors[$this->curr_color++];

    if (!empty($line['couleur_seance']))
      {
        //Conversion des composantes
        $r=hexdec (substr($line['couleur_seance'],1,2));
        $g=hexdec (substr($line['couleur_seance'],3,2));
        $b=hexdec (substr($line['couleur_seance'],5,2));
        $this->fillcolor[$line[$ColorSwitch]]= imagecolorallocate ($this->img,
                                                             $r,
                                                             $g,
                                                             $b);
      }

    // Remise à zéro de la couleur
    $groupe_color = $this->colors['noir'];

    //if ($line['couleur_txt_seance']== "0" || $_GET['colors']==2)
    /*
    if ($line['couleur_txt_seance']== "0")
      $groupe_color = $this->colors['noir'];
    else
      $groupe_color = $this->colors['blanc'];
    */

    // Extraction des heures / minutes de début et fin des cours
    $HrDebut1 = substr($line['hr_deb_seance'],0,2);
    $HrDebut2 = substr($line['hr_deb_seance'],3,2);

    $HrFin1 = substr($line['hr_fin_seance'],0,2);
    $HrFin2 = substr($line['hr_fin_seance'],3,2);

    // Gestion du décalage en fonction du jour
    switch($line['jour_seance'])
      {
      case "Lundi":
        $deca_jour = $this->dim['mg'];
        break;
      case "Mardi":
        $deca_jour = $this->dim['mg'] + $this->dim['lj'];
        break;
      case "Mercredi":
        $deca_jour=$this->dim['mg'] + $this->dim['lj'] * 2;
        break;
      case "Jeudi":
        $deca_jour=$this->dim['mg'] + $this->dim['lj'] * 3;
        break;
      case "Vendredi":
        $deca_jour=$this->dim['mg'] + $this->dim['lj'] * 4;
        break;
      case "Samedi":
        $deca_jour=$this->dim['mg'] + $this->dim['lj'] * 5;
        break;
      }

    // On dessine la cellule du cours : Remplissage de la case, trait
    // pointillé vertical en cas de cours en groupe et décalage des
    // infos à droite

    /* semaine A */
    if($line['semaine_seance']=="A")
      {
        imagerectangle ($this->img,
                        $deca_jour + 1,
                        $HrDebut1 * 60 + $HrDebut2 - $this->dim['dh'] + $this->dim['entete'],
                        $deca_jour + $this->dim['lj'] / 2,
                        $HrFin1 * 60 + $HrFin2 - $this->dim['dh'] + $this->dim['entete'],
                        $this->colors['noir']);

        imagefilledrectangle ($this->img,
                              $deca_jour + 2,
                              $HrDebut1 * 60 + $HrDebut2 - $this->dim['dh']
                              + $this->dim['entete'] + 1,
                              $deca_jour + $this->dim['lj'] / 2 - 1,
                              $HrFin1 * 60 + $HrFin2 - $this->dim['dh']
                              + $this->dim['entete'] - 1,
                              $this->fillcolor[$line[$ColorSwitch]]);
      }

    /* semaine B */
    else if($line['semaine_seance']=="B")
      {
        imagerectangle ($this->img,
                        $deca_jour+ $this->dim['lj'] / 2,
                        $HrDebut1 * 60 + $HrDebut2 - $this->dim['dh'] + $this->dim['entete'],
                        $deca_jour + $this->dim['lj'],
                        $HrFin1 * 60 + $HrFin2 - $this->dim['dh'] + $this->dim['entete'],
                        $this->colors['noir']);

        imagefilledrectangle ($this->img,
                              $deca_jour + 1 + $this->dim['lj'] /2,
                              $HrDebut1 * 60 + $HrDebut2- $this->dim['dh']
                              + $this->dim['entete'] + 1,
                              $deca_jour + $this->dim['lj'] - 1,
                              $HrFin1 * 60 + $HrFin2 - $this->dim['dh'] +
                              $this->dim['entete'] - 1,
                              $this->fillcolor[$line[$ColorSwitch]]);

        // On décale les informations sur la droite
        $decal_groupe_h=60;
      }
    /* cours normal */
    else
      {
        imagerectangle ($this->img,
                        $deca_jour + 1,
                        $HrDebut1 * 60 + $HrDebut2
                        - $this->dim['dh'] + $this->dim['entete'],
                        $deca_jour + $this->dim['lj'],
                        $HrFin1 * 60 + $HrFin2 - $this->dim['dh'] + $this->dim['entete'],
                        $this->colors['noir']);

        imagefilledrectangle ($this->img,
                              $deca_jour + 2,
                              $HrDebut1 * 60 + $HrDebut2 - $this->dim['dh'] +
                              $this->dim['entete'] + 1,
                              $deca_jour + $this->dim['lj'] - 1,
                              $HrFin1 * 60 + $HrFin2 - $this->dim['dh'] +
                              $this->dim['entete'] - 1,
                              $this->fillcolor[$line[$ColorSwitch]]);
      }

    /* affichage du type de seance */
    if ($line['grp_seance'] == 0)
      $grps = '';
    else
      $grps = $line['grp_seance'];

    imagettftext($this->img,
                 10,
                 0,
                 $deca_jour + 8 + $decal_groupe_h,
                 $HrDebut1 * 60 + $HrDebut2 - $this->dim['dh'] + $this->dim['entete'] + $DVNum,
                 $groupe_color,
                 $this->font,
                 $line['type_seance'] .' '. $grps);

    // Affichage du groupe
    if(!(empty($line['semaine_seance'])  || $line['semaine_seance'] == null) && $this->printsem)
      imagettftext($this->img,
                   8,
                   0,
                   $deca_jour + 8 + $decal_groupe_h,
                   $HrDebut1 * 60 + $HrDebut2- $this->dim['dh']+ $this->dim['entete'] + $DVGroupe,
                   $groupe_color,
                   $this->font,
                   "Sem. " . $line['semaine_seance']);

    // Ecriture du libellé de la matière
    imagettftext($this->img,
                 12,
                 0,
                 $deca_jour + 8 + $decal_groupe_h,
                 $HrDebut1 * 60 + $HrDebut2 - $this->dim['dh']+ $this->dim['entete'] + $DVMatiere,
                 $groupe_color,
                 $this->font,
                 $line['nom_uv']);

    // Affichage de la salle
    imagettftext($this->img,
                 10,
                 0,
                 $deca_jour + 8 + $decal_groupe_h,
                 $HrDebut1 * 60 + $HrDebut2 - $this->dim['dh'] + $this->dim['entete'] + $DVSalle,
                 $groupe_color,
                 $this->font,
                 $line['salle_seance']);

    $decal_groupe_h = 0;
  }

  /* visualisation */
  function show_edt ($wm)
  {
    global $topdir;
    require_once ($topdir . "include/watermark.inc.php");

    header("Content-Type: image/png");
    if ($wm)
      {
        $wm_img = new img_watermark ($this->img);
        imagepng ($wm_img->img);
        $wm_img->destroy ();
        return;
      }
    imagepng($this->img);
    $this->destroy ();
  }

  /* generation des plages horaires */
  function generate ($wm = true)
  {
    if (count($this->lines) > 0)
    {
      foreach ($this->lines as $line)
        $this->draw_course ($line);
    }
    $this->show_edt ($wm);
  }

  /* "destructeur"
   * */
  function destroy ()
  {
    @imagedestroy ($this->img);
    if ($this->logo)
      @imagedestroy ($this->logo);
  }

}
?>
