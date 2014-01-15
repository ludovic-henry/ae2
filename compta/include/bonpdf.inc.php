<?php
/*
 * Created on 30 janv. 2006
 *
 * To change the template for this generated file go to
 * Window - Preferences - PHPeclipse - PHP - Code Templates
 */

require_once($topdir . "include/lib/fpdf.inc.php");

class compta_bonpdf extends FPDF
{
  function compta_bonpdf()
  {


    $this->FPDF();
  }

  function add_op ( $op,$trez,$cla,$asso,$cpbc,$opclb,$opstd,$utl,$assotier,$ent,$cptasso2 )
  {
    $this->AddPage();
    /*
     *  AE
     *
     *  ASSO, COMPTE BANCAIRE
     *      N° CLASSEUR-NUM
     *
     *       DEBIT | CREDIT
     *          MONTANT
     *  mode/ num chèque
     *  type/code plan
     *  date
     *  bénéficiaire
     *  commentaire
     *
     *                trésorier
     *                signature
     */
    $compte_bancaire = $cpbc->nom;
    $asso_nom = $asso->nom;
    $num_bon  = $cla->nom."-".$op->num;
    if ( $opclb->id > 0 )
    {
      $type_mouvement = $opclb->type_mouvement;
      $type_libelle = $opclb->libelle;
    }
    else if ( $opstd->id > 0 )
    {
      $type_mouvement = $opstd->type_mouvement;
      $type_libelle = $opstd->code." ".$opstd->libelle;
    }
    $date = date("d/m/Y",$op->date);
    $mode = $op->mode;
    $num_cheque = $op->num_cheque;
    $commentaire = $op->commentaire;
    $nom_trez = $trez->prenom." ".$trez->nom;

    $montant = $op->montant/100;

    if ( $cptasso2->id > 0 )
    {
      $cpbc2  = new compte_bancaire($cptasso2->db);
      $assotier->load_by_id($cptasso2->id_asso);
      $cpbc2->load_by_id($cptasso2->id_cptbc);

      $beneficiaire = $assotier->nom." sur ".$cpbc2->nom;

      if($utl->id > 0)
      {
        $intermediaire = $utl->prenom." ".$utl->nom;
      }
    }
    elseif ( $assotier->id > 0 )
    {
       $beneficiaire = $assotier->nom;

      if($utl->id > 0)
      {
        $intermediaire = $utl->prenom." ".$utl->nom;
      }
    }
    elseif( $ent->id > 0 )
    {
       $beneficiaire = $ent->nom;
      if($utl->id > 0)
      {
        $intermediaire = $utl->prenom." ".$utl->nom;
      }
    }
    elseif($utl->id > 0)
    {
       $beneficiaire = $utl->prenom." ".$utl->nom;
       $intermediaire = "";
    }


    $this->SetY(10);
    $this->SetX(130);
    $this->SetFont('Arial','B',25);
    $this->Cell(60,20,utf8_decode('N°'.$num_bon),1,0,'C');
    $this->Ln();


    $this->SetY(50);
    $this->SetX(20);
       $this->SetFont('Arial','B',15);
    $this->Cell(170,15,utf8_decode('Activité: '.$asso_nom),0,0,'L');
    $this->Ln();
    $this->SetX(20);
    $this->Cell(170,15,utf8_decode('Libellé: '.$type_libelle),0,0,'L');
    $this->Ln();

       $this->SetFont('Arial','B',20);
       $this->SetX(20);
       $y = $this->GetY()+7;
       $this->Cell(85,14,utf8_decode('CREDIT'),1,0,'C');
    $this->Cell(85,14,utf8_decode('DEBIT'),1,0,'C');
    $this->Ln();

    $this->SetX(20);
    $this->Cell(170,14,utf8_decode('MONTANT: '.$montant.' Euros'),1,0,'C');
    $this->Ln();

    $x = 35;
    if ( $type_mouvement > 0 )
      $x += 85;
    $this->Line($x,$y,$x+55,$y);
    $this->Line($x,$y-1,$x+55,$y-1);
    $this->Line($x,$y+1,$x+55,$y+1);

    $y = $this->GetY();

    $this->SetFont('Arial','',12);
    $this->Rect(20,$y,85,50);
    $this->Rect(105,$y,85,50);

    for($i=0;$i<40;$i+=10)
    {
      $this->Rect(23,$y+$i+3,4,4);
      $this->Rect(23+85,$y+$i+3,4,4);
    }

    if ( $type_mouvement > 0 )
    {

      $this->SetX(30);
      $this->Cell(85,10,utf8_decode('Chèque N°'.$num_cheque),0,0,'L');
      $this->Cell(85,10,utf8_decode('Chèque N°'),0,0,'L');
      $this->Ln();
      $this->SetX(30);
      $this->Cell(85,10,utf8_decode('Liquide'),0,0,'L');
      $this->Cell(85,10,utf8_decode('Liquide'),0,0,'L');
      $this->Ln();
      $this->SetX(30);
      $this->Cell(85,10,utf8_decode('Virement'),0,0,'L');
      $this->Cell(85,10,utf8_decode('Virement'),0,0,'L');
      $this->Ln();
      $this->SetX(30);
      $this->Cell(85,10,utf8_decode('Carte bancaire'),0,0,'L');
      $this->Cell(85,10,utf8_decode('Carte bancaire'),0,0,'L');
      $this->Ln();
      $this->SetX(20);
      $this->Cell(85,10,utf8_decode('Date: '.$date),0,0,'L');
      $this->Cell(85,10,utf8_decode('Date:'),0,0,'L');
      $this->Ln();

      $this->Rect(23,$y+(($mode-1)*10)+3,4,4,'F');

      $this->SetX(20);
         $this->Cell(85,14,utf8_decode('Debiteur: '.$beneficiaire),1,0,'L');
      $this->Cell(85,14,utf8_decode('Crediteur: '),1,0,'L');
    }
    else
    {
      $this->SetX(30);
      $this->Cell(85,10,utf8_decode('Chèque N°'),0,0,'L');
      $this->Cell(85,10,utf8_decode('Chèque N°'.$num_cheque),0,0,'L');
      $this->Ln();
      $this->SetX(30);
      $this->Cell(85,10,utf8_decode('Liquide'),0,0,'L');
      $this->Cell(85,10,utf8_decode('Liquide'),0,0,'L');
      $this->Ln();
      $this->SetX(30);
      $this->Cell(85,10,utf8_decode('Virement'),0,0,'L');
      $this->Cell(85,10,utf8_decode('Virement'),0,0,'L');
      $this->Ln();
      $this->SetX(30);
      $this->Cell(85,10,utf8_decode('Carte bancaire'),0,0,'L');
      $this->Cell(85,10,utf8_decode('Carte bancaire'),0,0,'L');
      $this->Ln();
      $this->SetX(20);
      $this->Cell(85,10,utf8_decode('Date:'),0,0,'L');
      $this->Cell(85,10,utf8_decode('Date: '.$date),0,0,'L');
      $this->Ln();

      $this->Rect(23+85,$y+(($mode-1)*10)+3,4,4,'F');

      $this->SetX(20);
         $this->Cell(85,14,utf8_decode('Debiteur: '),1,0,'L');
      $this->Cell(85,14,utf8_decode('Crediteur: '.$beneficiaire),1,0,'L');
    }
    $this->Ln();

    $y = $this->GetY();
    $this->Rect(20,$y,170,40);

    $this->SetY($y+2);
    $this->SetX(20);
    $this->MultiCell(160,5,utf8_decode("Commentaires:\n".$commentaire),0,"L");

    if ( $intermediaire )
    {
      $this->SetX(20);
      $this->Cell(160,5,utf8_decode('Intermediaire: '.$intermediaire),0,0,'L');
    }

    $this->SetY($y+40+5);
    $this->SetX(105);
    $this->Cell(85,6,utf8_decode('Nom: '.$nom_trez),0,0,'L');
    $this->Ln();
    $this->SetX(105);
    $this->Cell(85,6,utf8_decode('Signature:'),0,0,'L');

  }


  function Header()
  {
    global $topdir;
    /* Logo AE */
    $this->Image($topdir . 'images/Ae-blanc.jpg',10,8,0,20);
    /* fonte */
    $this->SetFont('Arial','B',18);
    /* Couleur */
    $this->SetTextColor(0, 0, 0);
    $this->SetY(30);
    $this->SetX(20);
    $this->Cell(170, 20, utf8_decode("Justificatif du libellé"),0,0,'L');
    /* Jump lines */
    //$this->Ln(30);
  }

  function Footer()
  {
    //Position at 1.5 cm from bottom
    $this->SetY(-20);
    $this->SetFont('Arial','B',10);
    $this->Cell(0,5,utf8_decode('Association des étudiants - Université de technologie de Belfort-Montbéliard'),0,0,'C');
    $this->Ln();
    $this->SetFont('Arial','',10);
    $this->Cell(0,4,'6 Bd Anatole France 90 000 BELFORT',0,0,'C');
    $this->Ln();
  }



}

?>
