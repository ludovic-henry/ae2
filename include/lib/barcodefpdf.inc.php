<?php
/*
Barcode Render Class for PHP using the GD graphics library
Copyright (C) 2001  Karim Mribti

   Version  0.0.7a  2001-04-01

This library is free software; you can redistribute it and/or
modify it under the terms of the GNU Lesser General Public
License as published by the Free Software Foundation; either
version 2.1 of the License, or (at your option) any later version.

This library is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
Lesser General Public License for more details.

You should have received a copy of the GNU Lesser General Public
License along with this library; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

Copy of GNU Lesser General Public License at: http://www.gnu.org/copyleft/lesser.txt

Source code home page: http://www.mribti.com/barcode/
Contact author at: barcode@mribti.com
*/

/* Adaptation FPDF
 * Copyright 2006
 * - Julien Etelain < julien at pmad dot net >
 *
 * Ce fichier fait partie du site de l'Association des etudiants de
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

require_once($topdir . "include/lib/fpdf.inc.php");
/***************************** base class ********************************************/
/** NB: all GD call's is here **/

/* Styles */

/* Global */
define("BCS_BORDER"     ,    1);
define("BCS_TRANSPARENT"    ,    2);
define("BCS_ALIGN_CENTER"   ,    4);
define("BCS_ALIGN_LEFT"     ,    8);
define("BCS_ALIGN_RIGHT"    ,   16);
define("BCS_DRAW_TEXT"      ,  128);
define("BCS_STRETCH_TEXT"   ,  256);
define("BCS_REVERSE_COLOR"  ,  512);
/* For the I25 Only  */
define("BCS_I25_DRAW_CHECK" , 2048);

/* Default values */

/* Global */
define("BCD_DEFAULT_BACKGROUND_COLOR", 0xFFFFFF);
define("BCD_DEFAULT_FOREGROUND_COLOR", 0x000000);
define("BCS_IMAGE_JPEG"     ,   32);
define("BCS_IMAGE_PNG"      ,   64);
define("BCD_DEFAULT_STYLE"           , BCS_BORDER | BCS_ALIGN_CENTER | BCS_IMAGE_PNG);
define("BCD_DEFAULT_WIDTH"           , 460);
define("BCD_DEFAULT_HEIGHT"          , 120);
define("BCD_DEFAULT_FONT"            ,   5);
define("BCD_DEFAULT_XRES"            ,   2);
/* Margins */
define("BCD_DEFAULT_TEXT_OFFSET"     ,   2);
/* For the I25 Only */
define("BCD_I25_NARROW_BAR"          ,   1);
define("BCD_I25_WIDE_BAR"        ,   2);

/* For the C39 Only */
define("BCD_C39_NARROW_BAR"          ,   1);
define("BCD_C39_WIDE_BAR"        ,   2);

/* For Code 128 */
define("BCD_C128_BAR_1"              ,   1);
define("BCD_C128_BAR_2"              ,   2);
define("BCD_C128_BAR_3"              ,   3);
define("BCD_C128_BAR_4"              ,   4);

class PDF_BarcodeObject {

  var $mWidth, $mHeight, $mStyle, $mBgcolor, $mBrush;
  var $mImg, $mFont;
  var $mError;
  var $FPDF;
  var $x;
  var $y;

  function PDF_BarcodeObject ($Width = BCD_DEFAULT_Width, $Height = BCD_DEFAULT_HEIGHT, $Style = BCD_DEFAULT_STYLE, $FPDF, $x=0, $y=0)
  {
    $this->mWidth   = $Width;
    $this->mHeight  = $Height;
    $this->mStyle   = $Style;

    $this->FPDF = &$FPDF;
    $this->x = $x;
    $this->y = $y;
    $this->FPDF->SetFillColor(0,0,0);
    $this->FPDF->SetDrawColor(0,0,0);
    $this->FPDF->SetTextColor(0,0,0);
    $this->FPDF->SetFont('Arial','',8);

  }

  function DrawObject ($xres)
  {
    /* there is not implementation neded, is simply the asbsract function. */
    return false;
  }

  function DrawBorder ()
  {
    $this->FPDF->Rect($this->x,$this->y,$this->mWidth-1, $this->mHeight-1);
  }



  function DrawSingleBar($xPos, $yPos, $xSize, $ySize)
  {

    $this->FPDF->Rect($this->x+$xPos,$this->y+$yPos,$xSize,$ySize,'F');

    return true;

  }

  function GetError()
  {
    return $this->mError;
  }

  function GetFontHeight()
  {
    return 5;
  }

  function GetStyle ()
  {
    return $this->mStyle;
  }

  function SetStyle ($Style)
  {
    $this->mStyle = $Style;
  }

}

/*
 *  Render for Code 128-A
 *  Code 128-A is a continuous, multilevel and include all upper case alphanumeric characters and ASCII control characters .
 */


class PDF_C128AObject extends PDF_BarcodeObject
{
  var $mCharSet, $mChars;

  function PDF_C128AObject($Width, $Height, $Style, $Value, $FPDF, $x=0, $y=0)
  {
    $this->PDF_BarcodeObject($Width, $Height, $Style, &$FPDF, $x, $y);
    $this->mValue   = $Value;
    $this->mChars   = " !\"#$%&'()*+´-./0123456789:;<=>?@ABCDEFGHIJKLMNOPQRSTUVWXYZ[\\]^_";
    $this->mCharSet = array ("212222",   /*   00 */
                 "222122",   /*   01 */
                 "222221",   /*   02 */
                 "121223",   /*   03 */
                 "121322",   /*   04 */
                 "131222",   /*   05 */
                 "122213",   /*   06 */
                 "122312",   /*   07 */
                 "132212",   /*   08 */
                 "221213",   /*   09 */
                 "221312",   /*   10 */
                 "231212",   /*   11 */
                 "112232",   /*   12 */
                 "122132",   /*   13 */
                 "122231",   /*   14 */
                 "113222",   /*   15 */
                 "123122",   /*   16 */
                 "123221",   /*   17 */
                 "223211",   /*   18 */
                 "221132",   /*   19 */
                 "221231",   /*   20 */
                 "213212",   /*   21 */
                 "223112",   /*   22 */
                 "312131",   /*   23 */
                 "311222",   /*   24 */
                 "321122",   /*   25 */
                 "321221",   /*   26 */
                 "312212",   /*   27 */
                 "322112",   /*   28 */
                 "322211",   /*   29 */
                 "212123",   /*   30 */
                 "212321",   /*   31 */
                 "232121",   /*   32 */
                 "111323",   /*   33 */
                 "131123",   /*   34 */
                 "131321",   /*   35 */
                 "112313",   /*   36 */
                 "132113",   /*   37 */
                 "132311",   /*   38 */
                 "211313",   /*   39 */
                 "231113",   /*   40 */
                 "231311",   /*   41 */
                 "112133",   /*   42 */
                 "112331",   /*   43 */
                 "132131",   /*   44 */
                 "113123",   /*   45 */
                 "113321",   /*   46 */
                 "133121",   /*   47 */
                 "313121",   /*   48 */
                 "211331",   /*   49 */
                 "231131",   /*   50 */
                 "213113",   /*   51 */
                 "213311",   /*   52 */
                 "213131",   /*   53 */
                 "311123",   /*   54 */
                 "311321",   /*   55 */
                 "331121",   /*   56 */
                 "312113",   /*   57 */
                 "312311",   /*   58 */
                 "332111",   /*   59 */
                 "314111",   /*   60 */
                 "221411",   /*   61 */
                 "431111",   /*   62 */
                 "111224",   /*   63 */
                 "111422",   /*   64 */
                 "121124",   /*   65 */
                 "121421",   /*   66 */
                 "141122",   /*   67 */
                 "141221",   /*   68 */
                 "112214",   /*   69 */
                 "112412",   /*   70 */
                 "122114",   /*   71 */
                 "122411",   /*   72 */
                 "142112",   /*   73 */
                 "142211",   /*   74 */
                 "241211",   /*   75 */
                 "221114",   /*   76 */
                 "413111",   /*   77 */
                 "241112",   /*   78 */
                 "134111",   /*   79 */
                 "111242",   /*   80 */
                 "121142",   /*   81 */
                 "121241",   /*   82 */
                 "114212",   /*   83 */
                 "124112",   /*   84 */
                 "124211",   /*   85 */
                 "411212",   /*   86 */
                 "421112",   /*   87 */
                 "421211",   /*   88 */
                 "212141",   /*   89 */
                 "214121",   /*   90 */
                 "412121",   /*   91 */
                 "111143",   /*   92 */
                 "111341",   /*   93 */
                 "131141",   /*   94 */
                 "114113",   /*   95 */
                 "114311",   /*   96 */
                 "411113",   /*   97 */
                 "411311",   /*   98 */
                 "113141",   /*   99 */
                 "114131",   /*  100 */
                 "311141",   /*  101 */
                 "411131"    /*  102 */
                 );
  }

  function GetCharIndex ($char)
  {
    for ($i=0; $i < 64 ;$i++)
      {
    if ($this->mChars[$i] == $char)
      return $i;
      }

    return -1;
  }

  function GetBarSize ($char,$xres)
  {
    switch ($char)
      {
      case '1':
    $cVal = BCD_C128_BAR_1;
    break;
      case '2':
    $cVal = BCD_C128_BAR_2;
    break;
      case '3':
    $cVal = BCD_C128_BAR_3;
    break;
      case '4':
    $cVal = BCD_C128_BAR_4;
    break;
      default:
    $cVal = 0;
      }

    return  $cVal * $xres;
  }


  function GetSize($xres)
  {
    $len = strlen($this->mValue);

    if ($len == 0)
      {
    $this->mError = "Null value";
    return false;
      }

    $ret = 0;

    for ($i=0;$i<$len;$i++)
      {
    if (($id = $this->GetCharIndex($this->mValue[$i])) == -1)
      {
        $this->mError = "C128A not include the char '".$this->mValue[$i]."'";
        return false;
      }
    else
      {
        $cset = $this->mCharSet[$id];
        $ret += $this->GetBarSize($cset[0], $xres);
        $ret += $this->GetBarSize($cset[1], $xres);
        $ret += $this->GetBarSize($cset[2], $xres);
        $ret += $this->GetBarSize($cset[3], $xres);
        $ret += $this->GetBarSize($cset[4], $xres);
        $ret += $this->GetBarSize($cset[5], $xres);
      }
      }

    /* length of Check character */
    $cset = $this->GetCheckCharValue();
    $CheckSize = 0;
    for ($i=0; $i < 6 ;$i++)
      {
    $CheckSize += $this->GetBarSize($cset[$i], $xres);
      }
    $StartSize = 2*BCD_C128_BAR_2*$xres + 3*BCD_C128_BAR_1*$xres + BCD_C128_BAR_4*$xres;
    $StopSize  = 2*BCD_C128_BAR_2*$xres + 3*BCD_C128_BAR_1*$xres + 2*BCD_C128_BAR_3*$xres;

    return $StartSize + $ret + $CheckSize + $StopSize;
  }

  function GetCheckCharValue()
  {
    $len = strlen($this->mValue);
    $sum = 103; // 'A' type;
    for ($i=0; $i < $len ;$i++)
      {
    $sum +=  $this->GetCharIndex($this->mValue[$i]) * ($i+1);
      }
    $check  = $sum % 103;
    return $this->mCharSet[$check];
  }

  function DrawStart($DrawPos, $yPos, $ySize, $xres)
  {
    /* Start code is '211412' */
    $this->DrawSingleBar($DrawPos, $yPos, $this->GetBarSize('2', $xres) , $ySize);
    $DrawPos += $this->GetBarSize('2', $xres);
    $DrawPos += $this->GetBarSize('1', $xres);
    $this->DrawSingleBar($DrawPos, $yPos, $this->GetBarSize('1', $xres) , $ySize);
    $DrawPos += $this->GetBarSize('1', $xres);
    $DrawPos += $this->GetBarSize('4', $xres);
    $this->DrawSingleBar($DrawPos, $yPos, $this->GetBarSize('1', $xres) , $ySize);
    $DrawPos += $this->GetBarSize('1', $xres);
    $DrawPos += $this->GetBarSize('2', $xres);

    return $DrawPos;
  }

  function DrawStop($DrawPos, $yPos, $ySize, $xres)
  {
    /* Stop code is '2331112' */
    $this->DrawSingleBar($DrawPos, $yPos, $this->GetBarSize('2', $xres) , $ySize);
    $DrawPos += $this->GetBarSize('2', $xres);
    $DrawPos += $this->GetBarSize('3', $xres);
    $this->DrawSingleBar($DrawPos, $yPos, $this->GetBarSize('3', $xres) , $ySize);
    $DrawPos += $this->GetBarSize('3', $xres);
    $DrawPos += $this->GetBarSize('1', $xres);
    $this->DrawSingleBar($DrawPos, $yPos, $this->GetBarSize('1', $xres) , $ySize);
    $DrawPos += $this->GetBarSize('1', $xres);
    $DrawPos += $this->GetBarSize('1', $xres);
    $this->DrawSingleBar($DrawPos, $yPos, $this->GetBarSize('2', $xres) , $ySize);
    $DrawPos += $this->GetBarSize('2', $xres);

    return $DrawPos;
  }

  function DrawCheckChar($DrawPos, $yPos, $ySize, $xres)
  {
    $cset = $this->GetCheckCharValue();
    $this->DrawSingleBar($DrawPos, $yPos, $this->GetBarSize($cset[0], $xres) , $ySize);
    $DrawPos += $this->GetBarSize($cset[0], $xres);
    $DrawPos += $this->GetBarSize($cset[1], $xres);
    $this->DrawSingleBar($DrawPos, $yPos, $this->GetBarSize($cset[2], $xres) , $ySize);
    $DrawPos += $this->GetBarSize($cset[2], $xres);
    $DrawPos += $this->GetBarSize($cset[3], $xres);
    $this->DrawSingleBar($DrawPos, $yPos, $this->GetBarSize($cset[4], $xres) , $ySize);
    $DrawPos += $this->GetBarSize($cset[4], $xres);
    $DrawPos += $this->GetBarSize($cset[5], $xres);

    return $DrawPos;
  }

  function DrawObject ($xres)
  {
    $len = strlen($this->mValue);
    if (($size = $this->GetSize($xres))==0)
      return false;

    if ($this->mStyle & BCS_ALIGN_CENTER) $sPos = (($this->mWidth - $size ) / 2);
    else if ($this->mStyle & BCS_ALIGN_RIGHT) $sPos = $this->mWidth - $size;
    else $sPos = 0;


      /* Total height of bar code -Bars only- */
      if ($this->mStyle & BCS_DRAW_TEXT) $ysize = $this->mHeight - 2 - $this->GetFontHeight($this->mFont);
      else $ysize = $this->mHeight - 2;


    /* Draw text */
    if ($this->mStyle & BCS_DRAW_TEXT)
      {
            $this->FPDF->SetY($this->y+$ysize);
            $this->FPDF->SetX($this->x);
            $this->FPDF->Cell($this->mWidth, $this->GetFontHeight() ,$this->mValue,0,0,'C');
      }

    $cPos = 0;
    $DrawPos = $this->DrawStart($sPos, 1 , $ysize, $xres);
    do
      {
    $c     = $this->GetCharIndex($this->mValue[$cPos]);
    $cset  = $this->mCharSet[$c];
    $this->DrawSingleBar($DrawPos, 1, $this->GetBarSize($cset[0], $xres) , $ysize);
    $DrawPos += $this->GetBarSize($cset[0], $xres);
    $DrawPos += $this->GetBarSize($cset[1], $xres);
    $this->DrawSingleBar($DrawPos, 1, $this->GetBarSize($cset[2], $xres) , $ysize);
    $DrawPos += $this->GetBarSize($cset[2], $xres);
    $DrawPos += $this->GetBarSize($cset[3], $xres);
    $this->DrawSingleBar($DrawPos, 1, $this->GetBarSize($cset[4], $xres) , $ysize);
    $DrawPos += $this->GetBarSize($cset[4], $xres);
    $DrawPos += $this->GetBarSize($cset[5], $xres);
    $cPos++;
      } while ($cPos < $len);

    $DrawPos = $this->DrawCheckChar($DrawPos, 1 , $ysize, $xres);
    $DrawPos =  $this->DrawStop($DrawPos, 1 , $ysize, $xres);

    return true;
  }
}

?>
