<?php
define('FPDF_FONTPATH', $topdir . 'font/');

define('SECONDSADAY',86400);

require_once($topdir . "include/lib/fpdf.inc.php"); 

class pdfplanning extends FPDF
{

  var $xmargin;
  var $ymargin;

  var $title_height;
  var $days_height;

  var $hcol_width;

  var $nbdays;
  var $start;

  var $day_width;
  var $day_y1;
  var $day_y2;

  function pdfplanning ( $title, $notice, $nbdays, $start=0 )
  {
    global $topdir;

    $this->FPDF();

    $this->xmargin = 15; // Marge X
    $this->ymargin = 20; // Marge Y
    $this->title_height = 20;
    $this->hcol_width = 15;
    $this->days_height = 10;

    $this->SetAutoPageBreak(false);

    $this->AddPage();

    $this->nbdays = $nbdays;
    $this->start = $start;


    $this->SetFont('Arial','',24);
    $this->SetXY($this->xmargin,$this->ymargin);
    $this->Cell($this->w-($this->xmargin*2),$this->title_height-5,utf8_decode($title));

    $this->SetFont('Arial','',8);
    $this->SetXY($this->xmargin,$this->ymargin+$this->title_height-5);
    $this->Cell($this->w-($this->xmargin*2),5,utf8_decode($notice));

    $this->day_y1 = $this->ymargin+$this->title_height+$this->days_height;
    $this->day_y2 = $this->h-$this->ymargin;
    $this->day_width = ($this->w-$this->hcol_width-($this->xmargin*2))/$nbdays;


    $h=0;

    $this->SetFont('Arial','',8);

    $this->SetDrawColor(128);
    $this->Line($this->xmargin,$this->day_y1,$this->w-$this->xmargin,$this->day_y1);

    for ( $t=0;$t<SECONDSADAY;$t+=3600)
    {
      $y1 = $this->day_y1 + ($this->day_y2-$this->day_y1)*$t/SECONDSADAY;
      $y2 = $this->day_y1 + ($this->day_y2-$this->day_y1)*($t+3599)/SECONDSADAY;

      $this->SetDrawColor(0);
      $this->SetXY($this->xmargin,$y1);
      $this->Cell($this->hcol_width,$y2-$y1,utf8_decode($h."H"));

      $this->SetDrawColor(128);
      $this->Line($this->xmargin,$y2,$this->w-$this->xmargin,$y2);

      $h++;
    }

    $this->SetDrawColor(0);
    $y = $this->ymargin+$this->title_height;

    $days_name = array(
    "0" => "Dimanche",
    "1" => "Lundi",
    "2" => "Mardi",
    "3" => "Mercredi",
    "4" => "Jeudi",
    "5" => "Vendredi",
    "6" => "Samedi" );

    for( $day=0;$day<$this->nbdays;$day++)
    {
      $real = ($day*SECONDSADAY)+$this->start;


      $x = $this->hcol_width + $this->xmargin + ($day * $this->day_width);

      $this->SetXY($x,$y);
      $this->Cell($this->day_width,$this->days_height,utf8_decode($days_name[date("w",$real)]." ". date("d/m",$real)));

      $this->Line($x,$y,$x,$this->day_y2);


    }


    
  }

  function add_element ( $start, $end, $title )
  {
    $day1 = floor(($start-$this->start)/SECONDSADAY);
    $day2 = floor(($end-$this->start)/SECONDSADAY);

    //echo "$day1 ==> $day2\n";

    for($day=$day1;$day<=$day2;$day++)
    {
      $sttime=0;
      $endtime=SECONDSADAY-1;

      if ( $day == $day1 )
        $sttime = $start-($day*SECONDSADAY)-$this->start;

      if ( $day == $day2 )
        $endtime = $end-($day*SECONDSADAY)-$this->start;

      if ( $day < $this->nbdays )
        $this->_add_elementsegment($day,$sttime,$endtime,$title);
    }
  }

  function _add_elementsegment ( $day, $sttime, $endtime, $title )
  {
    //echo "$day : $sttime ==> $endtime :: $title<br/>";


    $x = $this->hcol_width + $this->xmargin + ($day * $this->day_width);

    $y1 = $this->day_y1 + (($this->day_y2-$this->day_y1)*$sttime/SECONDSADAY);
    $y2 = $this->day_y1 + (($this->day_y2-$this->day_y1)*$endtime/SECONDSADAY);

    //echo "$x $y1 $y2<br/>";

    $this->SetFillColor(208);
    $this->Rect($x, $y1, $this->day_width, $y2-$y1,"FD");


    $this->SetFont('Arial','',8);
    $this->SetXY($x,$y1);
    $this->MultiCell( $this->day_width,4,utf8_decode($title));

  }


  /*function CellCrop ( $w, $h, $text)
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

  }*/


}
?>
