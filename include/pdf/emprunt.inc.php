<?php


define('FPDF_FONTPATH', $topdir . 'font/');
require_once($topdir . "include/lib/fpdf.inc.php");

class pdfemprunt extends FPDF
{

  var $emp;
  var $user;
  var $asso;
  var $user_op;

  function pdfemprunt ( &$emp, &$user, &$asso, &$user_op )
  {
    $this->emp = $emp;
    $this->user = $user;
    $this->asso = $asso;
    $this->user_op = $user_op;

    $this->FPDF();

  }

  function Header ()
  {

    $this->SetXY(10, 15);

    $this->SetFont('Arial','B', 10);
    $this->SetTextColor(0, 0, 0);
    $this->Cell (190, 3, utf8_decode("Association des étudiants de l'UTBM"), 0, 1, 'L');
    $this->Cell (190, 3, utf8_decode("6 Bd Anatole France - 9000 Belfort"), 0, 1, 'L');

    /** TITRE  CENTRE **/

    $this->Ln(5);

    $this->SetFont('Arial','B',25);
    $this->SetTextColor(0, 0, 0);
    $this->Cell(190, 20, utf8_decode("Pret de matériel n°".$this->emp->id),0,1,'C');

    $this->Ln(10);


    if ( $this->PageNo() == 1 )
    {
      $this->SetFont('Arial','',12);



      $this->Write(5, utf8_decode("Le ".strftime("%A %d %B %G",$this->emp->date_debut).",\n"));

      if ( $this->asso->id == 1 )
        $qui = $this->user->nom." ".$this->user->prenom;
      elseif ( $this->asso->id > 0 && $this->asso->id_asso_parent > 0 )
        $qui = $this->user->nom." ".$this->user->prenom." représentant l'association \"".$this->asso->nom."\"";
      elseif ( $this->asso->id > 0 )
        $qui = $this->user->nom." ".$this->user->prenom." dans le cadre du club \"".$this->asso->nom."\"";
      else
        $qui = $this->user->nom." ".$this->user->prenom;

      $this->Write(5, utf8_decode("L'association des étudiants de l'UTBM consent un pret de matériel à $qui.\n"));
      $this->Ln(3);

      $this->Write(5, utf8_decode("Le pret est valable du ".strftime("%A %d %B %G",$this->emp->date_debut)." au ".strftime("%A %d %B %G",$this->emp->date_fin).".\n"));
      $this->Ln(3);

      if ( $this->emp->caution )
      {
        $this->Write(5, utf8_decode("Un chèque de caution de ".sprintf("%.2f",$this->emp->caution/100)." euros doit être établi à l'ordre de \"AE UTBM\". " .
          "Si le matériel n'est pas restitué dans les délais, le chèque de caution sera encaissé dans les " .
          "7 jours après expiration du pret consenti. De même si le matériel est restitué dans un mauvais " .
          "état, le chèque de caution sera encaissé, hors accord entre les deux parties.\n"));
        $this->Ln(3);
      }

      if ( $this->emp->prix_paye )
      {
        $this->Write(5, utf8_decode("Un participation aux frais d'entretient du matériel d'un montant de ".sprintf("%.2f",$this->emp->prix_paye/100)." euros doit être payé. " .
          "Ce document tient lieu de recu, une facture pourra être émise si nécessaire.\n"));

        $this->Ln(3);
      }

      $this->Write(5, utf8_decode("Ce pret ne peut pas être prolongé de quelque manière que ce soit. Un nouveau pret devra être établi " .
        "si nécessaire.\n"));

      $this->Ln(5);
    }

    $this->Line(10,$this->GetY(),200,$this->GetY());

    $this->Ln(5);

    $this->SetFont('Arial','',20);
    $this->SetTextColor(0, 0, 0);
    $this->Cell(190, 12, utf8_decode("Liste du matériel"),0,1,'L');


    $this->SetFont('Arial','B',14);

    $this->Cell(30,13,utf8_decode("N°"), "B", 0, "");
    $this->Cell(50,13,utf8_decode("Type"), "B", 0, "");
    $this->Cell(100,13,utf8_decode("Désignation"), "B", 1, "");

  }

  function Footer()
  {

    $this->SetFont('Arial','',8);
    /* Couleur */
    $this->SetTextColor(0, 0, 0);
    $this->SetY(-60);

    $this->Cell(90,5, utf8_decode("Le ".strftime("%A %d %B %G",$this->emp->date_debut).",\n"), 0, 1, 'L');

    $this->Ln(1);

    $this->SetFont('Arial','',12);
    $qui =$this->user->nom." ".$this->user->prenom;
    $op = $this->user_op->nom." ".$this->user_op->prenom;
    $this->Cell(90,5, utf8_decode($qui), 0, 0, 'L');
    $this->Cell(90,5, utf8_decode($op), 0, 1, 'R');

    $this->SetFont('Arial','I',8);
    $this->Cell(90,5, utf8_decode("signature:"), 0, 0, 'L');
    $this->Cell(90,5, utf8_decode("signature:"), 0, 0, 'R');

    $this->SetY(-20);
    //Page number
    $this->Cell(0,10,'Page '.$this->PageNo().' - {nb}',0,0,'C');
  }




  function objects( $req )
  {
    $this->AliasNbPages ();

        $this->AddPage ();
    $this->SetFont('Arial','',12);
    while ( $row = $req->get_row() )
    {

      $this->Cell(30,5,utf8_decode($row['id_objet']), 0, 0, 'L');
      $this->Cell(50,5,utf8_decode($row['nom_objtype']), 0, 0, 'L');
      $this->Cell(100,5,utf8_decode($row['nom_objet']. " (".$row['cbar_objet'].")"), 0, 1, 'L');
    }
  }

}



?>
