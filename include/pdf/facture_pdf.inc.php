<?
/*
 * Generation de facture pdf a la volee
 *
 */
/* Copyright 2006
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


define('FPDF_FONTPATH', $topdir . 'font/');
require_once ($topdir . "include/lib/fpdf.inc.php");

class facture_pdf extends FPDF
{
  /* array reference facturant
   *
   * comprenant un "name" => chaine de caractere
   *               "addr" => array de lignes pour l'addresse
   *               "logo" => chemin vers un logo eventuel (false sinon)
   *
   */
  var $facturing_infos;
  /* array reference factur� (meme combat pour le tableau) */
  var $factured_infos;
  /* chaine date */
  var $date_facturation;
  /* un numero de reference */
  var $fact_ref_num;
  /* un titre */
  var $fact_titre;

  /* un tableau de ce qui est factur�
   *
   * array ([0] => (designation, qte, prix, sous total), ...)
   *
   */
  var $bought;

  /* le total */
  var $total;

  /* activation de la pagination FPDF */
  var $pagination;

  /* @brief constructeur de la classe
   */
  function facture_pdf ($facturing_infos,
            $factured_infos,
            $date_facturation,
            $fact_titre,
            $fact_ref_num,
            $bought)
  {
    /* affectation des variables */
    $this->facturing_infos  = $facturing_infos;
    $this->factured_infos   = $factured_infos;
    $this->date_facturation = $date_facturation;
    $this->fact_ref_num     = $fact_ref_num;
    $this->fact_titre       = utf8_decode($fact_titre);
    $this->bought           = $bought;
    $this->pagination=true;

    /* on passe au constructeur h�rit� */
    $this->FPDF();
  }

  /* @brief constructeur de la classe
   */
  function set_infos ($facturing_infos,
            $factured_infos,
            $date_facturation,
            $fact_titre,
            $fact_ref_num,
            $bought)
  {
    /* affectation des variables */
    $this->facturing_infos  = $facturing_infos;
    $this->factured_infos   = $factured_infos;
    $this->date_facturation = $date_facturation;
    $this->fact_ref_num     = $fact_ref_num;
    $this->fact_titre       = utf8_decode($fact_titre);
    $this->bought           = $bought;
    $this->total            = 0;
    //TODO: RAZ numéro de page
    // en attendant, on sucre la pagination
    $this->pagination=false;
  }



  /*
   * @brief Fonction Entete dans le PDF
   */
  function Header ()
  {
    /* Logo facturant */
    if ($this->facturing_infos['logo'])
    {
        $x = 10;
        $y = 8;
        list($width, $height, $type, $attr) = getimagesize($this->facturing_infos['logo']);
        $w = 80;
        $h = 80*$height/$width;
        if ( $h > 20 )
        {
            $h = 20;
            $w = 20*$width/$height;
        }
        $y += (20-$h)/2;
        $this->Image($this->facturing_infos['logo'],$x,+$y,$w,$h);
    }

    $this->SetXY(10, 30);


    /* addresse facturant */
    /* fonte */
    $this->SetFont('Arial','B', 8);
    /* Couleur */
    $this->SetTextColor(0, 0, 0);
    if ( isset($this->facturing_infos['addr']) && is_array($this->facturing_infos['addr']) )
    foreach ($this->facturing_infos['addr'] as $line)
      $this->Cell (190, 3, $line, 0, 1, 'L');

    /** TITRE  CENTRE **/
    /* fonte */
    $this->SetFont('Arial','B',25);
    /* Couleur */
    $this->SetTextColor(0, 0, 0);
    /* titre */
    $this->Cell(190, 5, $this->fact_titre,0,0,'R');
    /* Jump lines */
    $this->Ln(5);

    /** REFERENCE FACTURE **/
    $this->Cell(210,20,utf8_decode("Facture n°") . $this->fact_ref_num,0,0,'C');
    $this->Ln(20);
    $this->SetFont('Arial','I',15);
    /* date */
    $this->Cell(190,10, $this->date_facturation,0,1,'R');
    $this->Ln(0.5);
    $this->SetFont('Arial','I',8);
    $this->Cell(190,3,$this->factured_infos['name'],0,1,'R');
    if ( isset($this->factured_infos['addr']) && is_array($this->factured_infos['addr']) )
    foreach ($this->factured_infos['addr'] as $line)
      $this->Cell (190, 3, $line, 0, 1, 'R');
    //horizontal line
    $this->Line(10,$this->GetY(),200,$this->GetY());
    $this->Ln(10);
    //Police de caractere
    $this->SetFont('Arial','B',14);
    //affichage de la d�signation
    $this->Cell(80,13,utf8_decode("Désignation"), "B", 0, "");
    /* prix unitaire */
    $this->cell(40,13,"Prix unitaire", "B", 0, "R");
    //quantit�
    $this->Cell(30,13,utf8_decode("Quantité"), "B", 0, "R");
    //prix
    $this->Cell(40,13,"Total", "B", 0, "R");
    //marge
    $this->Ln(20);
  }
  /*
   * @brief Fonction Pied de page dans le PDF
   */
  function Footer()
  {
    //Arial italic 8
    $this->SetFont('Arial','I',8);
    /* Couleur */
    $this->SetTextColor(0, 0, 0);
    $this->SetY(-20);
    //Page number
    if ( $this->pagination )
    $this->Cell(0,10,'Page '.$this->PageNo().' - {nb}',0,0,'C');
  }

  /* @brief Fonction de traitement des donn�es
   */
  function print_items()
  {
    /* fonte */
    $this->SetFont('Times','',12);

    for ($i = 0; $i < count($this->bought); $i++)
      {
    /* si taille du nom est trop grande, on tronque */
    if (strlen($this->bought[$i]['nom']) > 50)
      $this->bought[$i]['nom'] = substr($this->bought[$i]['nom'],0,47) . "...";
    /* calcul du sous total */
    $this->bought[$i]['sous_total'] = $this->bought[$i]['prix'] *
      $this->bought[$i]['quantite'];
    /* incr�mentation du total */
    $this->total += $this->bought[$i]['sous_total'];
    /* Affichage dans le corps du pdf */
    $this->print_line($this->bought[$i]['nom'],
              $this->bought[$i]['prix'],
              $this->bought[$i]['quantite'],
              $this->bought[$i]['sous_total']);
      }
    $this->print_total();
    $this->print_mentions_legales();
    return;
  }
  /*
   * fonction d'affichage du total dans le PDF
   */
  function print_total()
  {


    $this->Ln(10);
    //Police de caractere
    $this->SetFont('Arial','B',14);
    /* total */
    $this->Cell(150,10,utf8_decode("Total à payer : "), "B", 0, "R");
    $this->total = sprintf("%.2f", $this->total / 100);
    $this->Cell(40,10,$this->total . " Euros", "B", 0, "R");
    //marge
    $this->Ln(10);
  }

  function print_mentions_legales()
  {
    $this->Ln(10);
    //Police de caractere
    $this->SetFont('Arial','',14);
    /* total */
    $this->Cell(150,10,utf8_decode("TVA non-applicable, art.293B CGI"), "B", 0, "");
    //marge
    $this->Ln(10);
    if(isset($this->facturing_infos['asso']))
    {
      $this->Ln(10);
      $this->Cell(95,10,utf8_decode("Facturant : ".$this->facturing_infos['asso']), "B", 0, "");
      $this->Cell(95,10,utf8_decode("Facturé : AE - Carte AE"), "B", 0, "R");
      //marge
      $this->Ln(40);
    }
  }


  /* fonction permettant d'ajouter une ligne dans le PDF
   *  @param pdt d�signation du produit
   *  @param prix_un prix unitaire
   *  @param num quantit� consomm�e
   *  @param total sous-total (en fait �gal � prx_un * num)
   *  @param color (optionel) permet une colorisation en bleu
   */
  function print_line($pdt,$prx_un, $num, $total)
  {
    //Police de caractere
    $this->SetFont('Arial','',12);
    //affichage de la d�signation
    $this->Cell(80,5,$pdt, "B", 0, "");
   // prix unitaire
    $this->Cell(40,5,sprintf("%.2f",$prx_un / 100), "B", 0, "R");
   //quantit�
    $this->Cell(30,5,$num, "B", 0, "R");
    //prix
    $total = sprintf("%.2f", $total / 100);
    $this->Cell(40,5,$total, "B", 1, "R");
  }
  /*
   * @brief : rendu et download de la facture par le client
   * j'h�sitais entre ca et une fonction qui se serait appel�e
   * prout_facture (), mais ca faisait trop PHPcoincoin ..., et puis
   * la ca claque marketinguement parlant ...
   *
   */
  function renderize ()
  {
    $this->AliasNbPages ();
    $this->AddPage ();
    $this->print_items ();
    $this->Output ("facture_".$this->fact_ref_num.".pdf", "D");
  }
}
?>
