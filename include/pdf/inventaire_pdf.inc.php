<?
/* Copyright 2010
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
require_once ($topdir . "include/lib/fpdf.inc.php");

class inventaire_pdf extends FPDF
{
    var $name;
    var $title;
    var $date;

    /* array de array contenant 'nom objet', 'nom salle ou club', 'date achat', 'prix achat'
     */
    var $items;
    var $total;
    var $pagination;

    function inventaire_pdf($name, $title, $date, $infos)
    {
        $this->name = $name;
        $this->title = $title;
        $this->date = $date;
        $this->items = $infos;
        $this->total = 0;
        $this->pagination = true;

        $this->FPDF();
    }

    function Header ()
    {
        $this->SetFont('Arial','B',25);
        $this->SetTextColor(0, 0, 0);
        $this->Cell(190, 5, utf8_decode ($this->title),0,0,'R');
        $this->Ln(5);

        $this->SetFont('Arial','I',15);
        $this->Cell(190,10, $this->date,0,1,'R');
        $this->Line(10,$this->GetY(),200,$this->GetY());
        $this->Ln(10);
        $this->SetFont('Arial','B',14);
        $this->Cell(80, 13, utf8_decode ('Désignation'), "B", 0, "");
        $this->cell(40, 13, 'Salle ou club', "B", 0, "R");
        $this->Cell(30, 13, 'Date achat', "B", 0, "R");
        $this->Cell(40, 13, 'Prix achat', "B", 0, "R");
        $this->Ln(20);
    }

    function Footer()
    {
        $this->SetFont('Arial','I',8);
        $this->SetTextColor(0, 0, 0);
        $this->SetY(-20);
        if ( $this->pagination )
            $this->Cell(0,10,'Page '.$this->PageNo().' - {nb}',0,0,'C');
    }

    function print_items()
    {
        $this->SetFont('Times','',12);

        for ($i = 0; $i < count($this->items); $i++) {
            if (strlen($this->items[$i]['nom']) > 50)
                $this->items[$i]['nom'] = substr($this->items[$i]['nom'],0,47) . "...";
            $this->total += $this->items[$i]['prix'];
            $this->print_line($this->items[$i]['nom'],
                              $this->items[$i]['lien'],
                              $this->items[$i]['date'],
                              $this->items[$i]['prix']);
        }
        $this->print_total($this->total);
    }

    function print_total ($total)
    {
        $this->Ln(10);
        $this->SetFont('Arial','B',14);
        $this->Cell(150,10,'Total : ', "B", 0, "R");
        $this->total = sprintf("%.2f", $this->total / 100);
        $this->Cell(40,10,$this->total . " Euros", "B", 0, "R");
        $this->Ln(10);
    }

    function print_line($pdt,$lien, $date, $prix)
    {
        $this->SetFont('Arial','',12);
        $this->Cell(80,5,utf8_decode ($pdt), "B", 0, "");
        $this->Cell(40,5,utf8_decode ($lien), "B", 0, "R");
        $this->Cell(30,5,$date, "B", 0, "R");
        $this->Cell(40,5,sprintf("%.2f", $prix / 100), "B", 1, "R");
    }

    function renderize ()
    {
        $this->AliasNbPages ();
        $this->AddPage ();
        $this->print_items ();
        $this->Output ("inventaire_".$this->name."_".$this->date.".pdf", "D");
    }
}
?>
