<?php
define('FPDF_FONTPATH', $topdir . 'font/');

require_once($topdir . "include/lib/barcodefpdf.inc.php");

class pdfetiquette extends FPDF
{

	var $width;
	var $height;
	var $xmargin;
	var $ymargin;
	var $pos;
	var $npp;
	var $npl;

	var $i;

	function pdfetiquette()
	{
		global $topdir;

		$this->FPDF();

		$this->width = 60; // Largeur d'une carte
		$this->height = 25; // Hauteur d'une carte
		$this->xmargin = 15; // Marge X
		$this->ymargin = 20; // Marge Y
		$this->npp = 30; // Nombre par page
		$this->npl = 3; // Nombre par ligne

		$this->SetAutoPageBreak(false);

		$this->i = 0;

		$this->pos = array (
			"owner" => array ("x"=>0,"y"=>1,"w"=>25,"h"=>3.5),
			"label" => array ("x"=>26,"y"=>1,"w"=>34,"h"=>3.5),
			"cbar" => array ("x"=>20,"y"=>4,"w"=>40,"h"=>22),
			"logo" => array ("x"=>1,"y"=>4,"w"=>18,"h"=>21)
			);


	}

	function add_etiquette ( $owner, $name, $barcode, $logo=null )
	{
    		if ( $this->i % $this->npp == 0 )
    		{
    			$this->AddPage();
    			$this->i = 0;
    		}



		$x = ($this->i % 3) * $this->width         + $this->xmargin;
		$y = intval ($this->i / 3) * $this->height + $this->ymargin;

		$this->Rect($x, $y, $this->width, $this->height);

		$cbar = new PDF_C128AObject($this->pos['cbar']['w'], $this->pos['cbar']['h'],
						BCS_ALIGN_CENTER | BCS_DRAW_TEXT,
						$barcode,
						&$this,
						$x+$this->pos['cbar']['x'],
						$y+$this->pos['cbar']['y']
						);
		$cbar->DrawObject(0.30);

		$this->SetFont('Arial','',8);
		$this->SetXY($x+$this->pos['label']['x'],$y+$this->pos['label']['y']);
		$this->CellCrop($this->pos['label']['w'],$this->pos['label']['h'],utf8_decode($name));

		$this->SetXY($x+$this->pos['owner']['x'],$y+$this->pos['owner']['y']);
		$this->CellCrop($this->pos['owner']['w'],$this->pos['owner']['h'],utf8_decode($owner));

		if ( $logo )
		{

			list($width, $height, $type, $attr) = getimagesize($logo);

			$w = $this->pos['logo']['w'];
			$h = $this->pos['logo']['w']*$height/$width;

			if ( $h > $this->pos['logo']['h'] )
			{
				$h = $this->pos['logo']['h'];
				$w = $this->pos['logo']['h']*$width/$height;
			}

			$y += ($this->pos['logo']['h']-$h)/2;
			$x += ($this->pos['logo']['w']-$w)/2;

			$this->Image($logo,$this->pos['logo']['x']+$x,$this->pos['logo']['y']+$y,$w,$h);


		}

		$this->i++;
	}


	function CellCrop ( $w, $h, $text)
	{
		$cw=&$this->CurrentFont['cw'];
		$l=0;
		$sl=0;
		$tw=0;
		$wmax=$w*1000/$this->FontSize;

		$wmax2=$wmax-($cw["."]*3);

		$nb=strlen($text);
		while($l<$nb)
		{
			$c = $text{$l};
			if ( $tw + $cw[$c] > $wmax ) break;

			if ( $tw + $cw[$c] < $wmax2 )
				$sl = $l;

			$tw+=$cw[$c];
			$l++;
		}

		if ( $l != $nb )
			$text = substr($text,0,$sl)."...";


		$this->Cell($w,$h,$text);



	}


}
?>
