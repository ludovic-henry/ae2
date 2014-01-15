<?php
/* Copyright 2011
 * - Jérémie Laval < jeremie dot laval at gmail dot com >
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

require_once($topdir . "include/lib/barcodefpdf.inc.php");

class eticket_pdf extends FPDF
{
    var $bandeau_partenaires;
    var $img_header;

    function eticket_pdf ($imgheader)
    {
        global $topdir;

        $this->FPDF ();

        $this->bandeau_partenaires = $topdir."images/eticket/bandeau_partenaires.jpg";
        $this->img_header = $imgheader;
        $this->SetAutoPageBreak (false);
    }

    function renderize($user_infos, $code)
    {
        /* $user_infos is a map containing:
             - 'prenom' : First name of the dude
             - 'nom': Family name
             - 'nickname': Nickname
             - 'avatar' : file path to the guy identity avatar
           $code is a string containing the whatever encoded thing we have to print as barcode
        */
        $this->AddPage ();
        $this->SetFont('Times','',14);

        $this->SetX (87);
        $this->Image ($user_infos['avatar'], null, null, 30, 35);
        $this->Ln(5);

        $this->add_line ('Prénom:', $user_infos['prenom']);
        $this->add_line ('Nom:', $user_infos['nom']);
        $this->add_line ('Surnom:', $user_infos['nickname']);

        $this->Ln(30);
        $this->SetX (70);
        $this->add_code ($code);
    }

    function add_line($key, $value)
    {
        $this->SetX (60);
        $this->Cell(20,9,utf8_decode($key), "B", 0, "");
        $this->Cell(60,9,utf8_decode($value), "B", 1, "R");
    }

    function add_code ($code)
    {
        /* We try to use the same informations as carteae.inc.php */
        $code = strtoupper ($code);
        $barcode = new PDF_C128AObject(67, 25, BCS_ALIGN_CENTER | BCS_DRAW_TEXT, $code, &$this, $this->GetX (), $this->GetY ());
        if (!$barcode->DrawObject (0.25))
            echo "Error occured with barcode : ".$barcode->GetError ()."\n";
    }

    function Header ()
    {
        $this->SetFont('Arial','',12);

        $this->Image ($this->img_header, null, null, 0, 0, 'JPG');
        $this->Ln(40);
    }

    /* Met le bandeau des partenaires en footer */
    function Footer ()
    {
        // Positionnement à 1,5 cm du bas
        $this->SetY(-25);
        $this->Image ($this->bandeau_partenaires, 0, $this->GetY(), 210, 20);
    }
}

?>