<?php

/* Copyright 2007
 * - Simon Lopez < simon dot lopez at ayolo dot org >
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


define('USE_ASSERTS', function_exists('assert'));

/**
 * @cond false
 */

class _DiffOp
{
  var $type;
  var $orig;
  var $closing;

  function reverse()
  {
    trigger_error("pure virtual", E_USER_ERROR);
  }

  function norig()
  {
    return $this->orig ? sizeof($this->orig) : 0;
  }

  function nclosing()
  {
    return $this->closing ? sizeof($this->closing) : 0;
  }
}

class _DiffOp_Copy extends _DiffOp
{
  var $type = 'copy';

  function _DiffOp_Copy ($orig, $closing = false)
  {
    if (!is_array($closing))
      $closing = $orig;
    $this->orig = $orig;
    $this->closing = $closing;
  }

  function reverse()
  {
    return new _DiffOp_Copy($this->closing, $this->orig);
  }
}

class _DiffOp_Delete extends _DiffOp
{
  var $type = 'delete';

  function _DiffOp_Delete ($lines)
  {
    $this->orig = $lines;
    $this->closing = false;
  }

  function reverse()
  {
    return new _DiffOp_Add($this->orig);
  }
}

class _DiffOp_Add extends _DiffOp
{
  var $type = 'add';

  function _DiffOp_Add ($lines)
  {
    $this->closing = $lines;
    $this->orig = false;
  }

  function reverse()
  {
    return new _DiffOp_Delete($this->closing);
  }
}

class _DiffOp_Change extends _DiffOp
{
  var $type = 'change';

  function _DiffOp_Change ($orig, $closing)
  {
    $this->orig = $orig;
    $this->closing = $closing;
  }

  function reverse()
  {
    return new _DiffOp_Change($this->closing, $this->orig);
  }
}

class _DiffEngine
{
  function diff ($from_lines, $to_lines)
  {
    $n_from = sizeof($from_lines);
    $n_to = sizeof($to_lines);

    $this->xchanged = $this->ychanged = array();
    $this->xv = $this->yv = array();
    $this->xind = $this->yind = array();
    unset($this->seq);
    unset($this->in_seq);
    unset($this->lcs);

    for ($skip = 0; $skip < $n_from && $skip < $n_to; $skip++)
    {
      if ($from_lines[$skip] != $to_lines[$skip])
        break;
      $this->xchanged[$skip] = $this->ychanged[$skip] = false;
    }

    $xi = $n_from; $yi = $n_to;
    for ($endskip = 0; --$xi > $skip && --$yi > $skip; $endskip++)
    {
      if ($from_lines[$xi] != $to_lines[$yi])
        break;
      $this->xchanged[$xi] = $this->ychanged[$yi] = false;
    }

    for ($xi = $skip; $xi < $n_from - $endskip; $xi++)
      $xhash[$from_lines[$xi]] = 1;
    for ($yi = $skip; $yi < $n_to - $endskip; $yi++)
    {
      $line = $to_lines[$yi];
      if ( ($this->ychanged[$yi] = empty($xhash[$line])) )
        continue;
      $yhash[$line] = 1;
      $this->yv[] = $line;
      $this->yind[] = $yi;
    }
    for ($xi = $skip; $xi < $n_from - $endskip; $xi++)
    {
      $line = $from_lines[$xi];
      if ( ($this->xchanged[$xi] = empty($yhash[$line])) )
        continue;
      $this->xv[] = $line;
      $this->xind[] = $xi;
    }

    $this->_compareseq(0, sizeof($this->xv), 0, sizeof($this->yv));

    $this->_shift_boundaries($from_lines, $this->xchanged, $this->ychanged);
    $this->_shift_boundaries($to_lines, $this->ychanged, $this->xchanged);

    $edits = array();
    $xi = $yi = 0;
    while ($xi < $n_from || $yi < $n_to)
    {
      USE_ASSERTS && assert($yi < $n_to || $this->xchanged[$xi]);
      USE_ASSERTS && assert($xi < $n_from || $this->ychanged[$yi]);

      $copy = array();
      while ( $xi < $n_from && $yi < $n_to
              && !$this->xchanged[$xi] && !$this->ychanged[$yi])
      {
        $copy[] = $from_lines[$xi++];
        ++$yi;
      }
      if ($copy)
        $edits[] = new _DiffOp_Copy($copy);

      $delete = array();
      while ($xi < $n_from && $this->xchanged[$xi])
        $delete[] = $from_lines[$xi++];

      $add = array();
      while ($yi < $n_to && $this->ychanged[$yi])
        $add[] = $to_lines[$yi++];

      if ($delete && $add)
        $edits[] = new _DiffOp_Change($delete, $add);
      elseif ($delete)
        $edits[] = new _DiffOp_Delete($delete);
      elseif ($add)
        $edits[] = new _DiffOp_Add($add);
    }
    return $edits;
  }

  function _diag ($xoff, $xlim, $yoff, $ylim, $nchunks)
  {
    $flip = false;

    if ($xlim - $xoff > $ylim - $yoff)
    {
      $flip = true;
      list ($xoff, $xlim, $yoff, $ylim) = array( $yoff, $ylim, $xoff, $xlim);
    }

    if ($flip)
      for ($i = $ylim - 1; $i >= $yoff; $i--)
        $ymatches[$this->xv[$i]][] = $i;
    else
      for ($i = $ylim - 1; $i >= $yoff; $i--)
        $ymatches[$this->yv[$i]][] = $i;

    $this->lcs = 0;
    $this->seq[0]= $yoff - 1;
    $this->in_seq = array();
    $ymids[0] = array();

    $numer = $xlim - $xoff + $nchunks - 1;
    $x = $xoff;
    for ($chunk = 0; $chunk < $nchunks; $chunk++)
    {
      if ($chunk > 0)
        for ($i = 0; $i <= $this->lcs; $i++)
          $ymids[$i][$chunk-1] = $this->seq[$i];

      $x1 = $xoff + (int)(($numer + ($xlim-$xoff)*$chunk) / $nchunks);
      for ( ; $x < $x1; $x++)
      {
        $line = $flip ? $this->yv[$x] : $this->xv[$x];
        if (empty($ymatches[$line]))
          continue;
        $matches = $ymatches[$line];
        reset($matches);
        while (list ($junk, $y) = each($matches))
        if (empty($this->in_seq[$y]))
        {
          $k = $this->_lcs_pos($y);
          USE_ASSERTS && assert($k > 0);
          $ymids[$k] = $ymids[$k-1];
          break;
        }
        while (list ($junk, $y) = each($matches))
        {
          if ($y > $this->seq[$k-1])
          {
          USE_ASSERTS && assert($y < $this->seq[$k]);
          $this->in_seq[$this->seq[$k]] = false;
          $this->seq[$k] = $y;
          $this->in_seq[$y] = 1;
          }
          else if (empty($this->in_seq[$y]))
          {
          $k = $this->_lcs_pos($y);
          USE_ASSERTS && assert($k > 0);
          $ymids[$k] = $ymids[$k-1];
          }
        }
      }
    }

    $seps[] = $flip ? array($yoff, $xoff) : array($xoff, $yoff);
    $ymid = $ymids[$this->lcs];
    for ($n = 0; $n < $nchunks - 1; $n++)
    {
      $x1 = $xoff + (int)(($numer + ($xlim - $xoff) * $n) / $nchunks);
      $y1 = $ymid[$n] + 1;
      $seps[] = $flip ? array($y1, $x1) : array($x1, $y1);
    }
    $seps[] = $flip ? array($ylim, $xlim) : array($xlim, $ylim);

    return array($this->lcs, $seps);
  }

  function _lcs_pos ($ypos)
  {
    $end = $this->lcs;
    if ($end == 0 || $ypos > $this->seq[$end])
    {
      $this->seq[++$this->lcs] = $ypos;
      $this->in_seq[$ypos] = 1;
      return $this->lcs;
    }

    $beg = 1;
    while ($beg < $end)
    {
      $mid = (int)(($beg + $end) / 2);
      if ( $ypos > $this->seq[$mid] )
        $beg = $mid + 1;
      else
        $end = $mid;
    }

    USE_ASSERTS && assert($ypos != $this->seq[$end]);

    $this->in_seq[$this->seq[$end]] = false;
    $this->seq[$end] = $ypos;
    $this->in_seq[$ypos] = 1;
    return $end;
  }

  function _compareseq ($xoff, $xlim, $yoff, $ylim)
  {
    while ($xoff < $xlim && $yoff < $ylim
           && $this->xv[$xoff] == $this->yv[$yoff])
    {
      ++$xoff;
      ++$yoff;
    }

    while ($xlim > $xoff && $ylim > $yoff
           && $this->xv[$xlim - 1] == $this->yv[$ylim - 1])
    {
      --$xlim;
      --$ylim;
    }

    if ($xoff == $xlim || $yoff == $ylim)
      $lcs = 0;
    else
    {
      $nchunks = min(7, $xlim - $xoff, $ylim - $yoff) + 1;
      list ($lcs, $seps) = $this->_diag($xoff,$xlim,$yoff, $ylim,$nchunks);
    }

    if ($lcs == 0)
    {
      while ($yoff < $ylim)
        $this->ychanged[$this->yind[$yoff++]] = 1;
      while ($xoff < $xlim)
        $this->xchanged[$this->xind[$xoff++]] = 1;
    }
    else
    {
      reset($seps);
      $pt1 = $seps[0];
      while ($pt2 = next($seps))
      {
        $this->_compareseq ($pt1[0], $pt2[0], $pt1[1], $pt2[1]);
        $pt1 = $pt2;
      }
    }
  }

  function _shift_boundaries ($lines, &$changed, $other_changed)
  {
    $i = 0;
    $j = 0;

    USE_ASSERTS && assert('sizeof($lines) == sizeof($changed)');
    $len = sizeof($lines);
    $other_len = sizeof($other_changed);

    while (1)
    {
      while ($j < $other_len && $other_changed[$j])
        $j++;

      while ($i < $len && ! $changed[$i])
      {
        USE_ASSERTS && assert('$j < $other_len && ! $other_changed[$j]');
        $i++; $j++;
        while ($j < $other_len && $other_changed[$j])
          $j++;
      }

      if ($i == $len)
        break;

      $start = $i;

      while (++$i < $len && $changed[$i])
        continue;

      do
      {
        $runlength = $i - $start;

        while ($start > 0 && $lines[$start - 1] == $lines[$i - 1])
        {
          $changed[--$start] = 1;
          $changed[--$i] = false;
          while ($start > 0 && $changed[$start - 1])
            $start--;
          USE_ASSERTS && assert('$j > 0');
          while ($other_changed[--$j])
            continue;
          USE_ASSERTS && assert('$j >= 0 && !$other_changed[$j]');
        }

        $corresponding = $j < $other_len ? $i : $len;

        while ($i < $len && $lines[$start] == $lines[$i])
        {
          $changed[$start++] = false;
          $changed[$i++] = 1;
          while ($i < $len && $changed[$i])
            $i++;

          USE_ASSERTS && assert('$j < $other_len && ! $other_changed[$j]');
          $j++;
          if ($j < $other_len && $other_changed[$j])
          {
            $corresponding = $i;
            while ($j < $other_len && $other_changed[$j])
              $j++;
          }
        }
      } while ($runlength != $i - $start);

      while ($corresponding < $i)
      {
        $changed[--$start] = 1;
        $changed[--$i] = 0;
        USE_ASSERTS && assert('$j > 0');
        while ($other_changed[--$j])
          continue;
        USE_ASSERTS && assert('$j >= 0 && !$other_changed[$j]');
      }
    }
  }
}

/**
 * @endcond
 */


class Diff
{
  var $edits;

  function Diff($old, $new)
  {
    $old = split("\n",$old);
    $new = split("\n",$new);
    $eng = new _DiffEngine;
    $this->edits = $eng->diff($old, $new);
  }

  function reverse ()
  {
    $rev = $this;
    $rev->edits = array();
    foreach ($this->edits as $edit)
      $rev->edits[] = $edit->reverse();

    return $rev;
  }

  function isEmpty ()
  {
    foreach ($this->edits as $edit)
      if ($edit->type != 'copy')
        return false;
    return true;
  }

  function lcs ()
  {
    $lcs = 0;
    foreach ($this->edits as $edit)
      if ($edit->type == 'copy')
        $lcs += sizeof($edit->orig);
    return $lcs;
  }

  function orig()
  {
    $lines = array();
    foreach ($this->edits as $edit)
      if ($edit->orig)
        array_splice($lines, sizeof($lines), 0, $edit->orig);
    return $lines;
  }

  function closing()
  {
    $lines = array();
    foreach ($this->edits as $edit)
      if ($edit->closing)
        array_splice($lines, sizeof($lines), 0, $edit->closing);
    return $lines;
  }

  function _check ($from_lines, $to_lines)
  {
    if (serialize($from_lines) != serialize($this->orig()))
      trigger_error("Reconstructed original doesn't match", E_USER_ERROR);
    if (serialize($to_lines) != serialize($this->closing()))
      trigger_error("Reconstructed closing doesn't match", E_USER_ERROR);

    $rev = $this->reverse();
    if (serialize($to_lines) != serialize($rev->orig()))
      trigger_error("Reversed original doesn't match", E_USER_ERROR);
    if (serialize($from_lines) != serialize($rev->closing()))
      trigger_error("Reversed closing doesn't match", E_USER_ERROR);

    $prevtype = 'none';
    foreach ($this->edits as $edit)
    {
      if ( $prevtype == $edit->type )
        trigger_error("Edit sequence is non-optimal", E_USER_ERROR);
      $prevtype = $edit->type;
    }

    $lcs = $this->lcs();
    trigger_error("Diff okay: LCS = $lcs", E_USER_NOTICE);
  }
}

class MappedDiff extends Diff
{

  function MappedDiff($from_lines, $to_lines,
                      $mapped_from_lines, $mapped_to_lines)
  {
    assert(sizeof($from_lines) == sizeof($mapped_from_lines));
    assert(sizeof($to_lines) == sizeof($mapped_to_lines));

    $this->Diff($mapped_from_lines, $mapped_to_lines);

    $xi = $yi = 0;
    for ($i = 0; $i < sizeof($this->edits); $i++)
    {
      $orig = &$this->edits[$i]->orig;
      if (is_array($orig))
      {
        $orig = array_slice($from_lines, $xi, sizeof($orig));
        $xi += sizeof($orig);
      }

      $closing = &$this->edits[$i]->closing;
      if (is_array($closing))
      {
        $closing = array_slice($to_lines, $yi, sizeof($closing));
        $yi += sizeof($closing);
      }
    }
  }
}

class DiffFormatter
{
  var $leading_context_lines = 0;
  var $trailing_context_lines = 0;

  function format($diff)
  {
    $xi = $yi = 1;
    $block = false;
    $context = array();

    $nlead = $this->leading_context_lines;
    $ntrail = $this->trailing_context_lines;

    $this->_start_diff();

    foreach ($diff->edits as $edit)
    {
      if ($edit->type == 'copy')
      {
        if (is_array($block))
        {
          if (sizeof($edit->orig) <= $nlead + $ntrail)
            $block[] = $edit;
          else
          {
            if ($ntrail)
            {
              $context = array_slice($edit->orig, 0, $ntrail);
              $block[] = new _DiffOp_Copy($context);
            }
            $this->_block($x0, $ntrail + $xi - $x0,
                    $y0, $ntrail + $yi - $y0,
                    $block);
            $block = false;
          }
        }
        $context = $edit->orig;
      }
      else
      {
        if (! is_array($block))
        {
          $context = array_slice($context, sizeof($context) - $nlead);
          $x0 = $xi - sizeof($context);
          $y0 = $yi - sizeof($context);
          $block = array();
          if ($context)
            $block[] = new _DiffOp_Copy($context);
        }
        $block[] = $edit;
      }

      if ($edit->orig)
        $xi += sizeof($edit->orig);
      if ($edit->closing)
        $yi += sizeof($edit->closing);
    }

    if (is_array($block))
      $this->_block($x0, $xi - $x0,
              $y0, $yi - $y0,
              $block);

    return $this->_end_diff();
  }

  function _block($xbeg, $xlen, $ybeg, $ylen, &$edits)
  {
    $this->_start_block($this->_block_header($xbeg, $xlen, $ybeg, $ylen));
    foreach ($edits as $edit)
    {
      if ($edit->type == 'copy')
        $this->_context($edit->orig);
      elseif ($edit->type == 'add')
        $this->_added($edit->closing);
      elseif ($edit->type == 'delete')
        $this->_deleted($edit->orig);
      elseif ($edit->type == 'change')
        $this->_changed2($edit->orig, $edit->closing);
      else
        trigger_error("Unknown edit type", E_USER_ERROR);
    }
    $this->_end_block();
  }

  function _start_diff()
  {
    ob_start();
  }

  function _end_diff()
  {
    $val = ob_get_contents();
    ob_end_clean();
    return $val;
  }

  function _block_header($xbeg, $xlen, $ybeg, $ylen)
  {
    if ($xlen > 1)
      $xbeg .= "," . ($xbeg + $xlen - 1);
    if ($ylen > 1)
      $ybeg .= "," . ($ybeg + $ylen - 1);

    return $xbeg . ($xlen ? ($ylen ? 'c' : 'd') : 'a') . $ybeg;
  }

  function _start_block($header)
  {
    echo $header;
  }

  function _end_block()
  {
  }

  function _lines($lines, $prefix = ' ')
  {
    foreach ($lines as $line)
      echo "$prefix $line\n";
  }

  function _context($lines)
  {
    $this->_lines($lines);
  }

  function _deleted($lines)
  {
    $this->_lines($lines, "<");
  }

  function _changed2($orig, $closing)
  {
    $this->_deleted($orig);
    //echo "---\n";
    $this->_added($closing);
  }
}

/**
 * @cond false
 */
define('NBSP', "\xC2\xA0");

class _HWLDF_WordAccumulator
{
  function _HWLDF_WordAccumulator ()
  {
    $this->_lines = array();
    $this->_line = '';
    $this->_group = '';
    $this->_tag = '';
  }

  function _flushGroup ($new_tag)
  {
    if ($this->_group !== '')
    {
      if ($this->_tag == 'mark')
        $this->_line .= '<span class="diffchange">'.$this->_group.'</span>';
      else
        $this->_line .= $this->_group;
    }
    $this->_group = '';
    $this->_tag = $new_tag;
  }

  function _flushLine ($new_tag)
  {
    $this->_flushGroup($new_tag);
    if ($this->_line != '')
      $this->_lines[] = $this->_line;
    $this->_line = '';
  }

  function addWords ($words, $tag = '')
  {
    if ($tag != $this->_tag)
      $this->_flushGroup($tag);

    foreach ($words as $word)
    {
      if ($word == '')
        continue;
      if ($word[0] == "\n")
      {
        $this->_group .= NBSP;
        $this->_flushLine($tag);
        $word = substr($word, 1);
      }
      assert(!strstr($word, "\n"));
      $this->_group .= $word;
    }
  }

  function getLines()
  {
    $this->_flushLine('~done');
    return $this->_lines;
  }
}
/**
 * @endcond
 */
class WordLevelDiff extends MappedDiff
{
  function WordLevelDiff ($orig_lines, $closing_lines)
  {
    list ($orig_words, $orig_stripped) = $this->_split($orig_lines);
    list ($closing_words, $closing_stripped) = $this->_split($closing_lines);

    $this->MappedDiff($orig_words, $closing_words,
              $orig_stripped, $closing_stripped);
  }

  function _split($lines)
  {
    if (!preg_match_all('/ ( [^\S\n]+ | [0-9_A-Za-z\x80-\xff]+ | . ) (?: (?!< \n) [^\S\n])? /xs', implode("\n", $lines), $m)
        && !preg_match_all('/ ( [^\S\n]+ | [0-9_A-Za-z\x80-\xff]+ | . ) (?: (?!< \n) [^\S\n])? /xs', implode("\n", $lines), $m) )
      return array(array(''), array(''));
    return array($m[0], $m[1]);
  }

  function orig ()
  {
    $orig = new _HWLDF_WordAccumulator;

    foreach ($this->edits as $edit)
    {
      if ($edit->type == 'copy')
        $orig->addWords($edit->orig);
      elseif ($edit->orig)
        $orig->addWords($edit->orig, 'mark');
    }
    return $orig->getLines();
  }

  function closing ()
  {
    $closing = new _HWLDF_WordAccumulator;

    foreach ($this->edits as $edit)
    {
      if ($edit->type == 'copy')
        $closing->addWords($edit->closing);
      elseif ($edit->closing)
        $closing->addWords($edit->closing, 'mark');
    }
    return $closing->getLines();
  }
}

class UnifiedDiffFormatter extends DiffFormatter
{
  function UnifiedDiffFormatter($context_lines = 4)
  {
    $this->leading_context_lines = $context_lines;
    $this->trailing_context_lines = $context_lines;
  }

  function _block_header($xbeg, $xlen, $ybeg, $ylen)
  {
    if ($xlen != 1)
      $xbeg .= "," . $xlen;
    if ($ylen != 1)
      $ybeg .= "," . $ylen;
    return "@@ -$xbeg +$ybeg @@\n";
  }

  function _added($lines)
  {
    $this->_lines($lines, "+");
  }

  function _deleted($lines)
  {
    $this->_lines($lines, "-");
  }

  function _changed($orig, $final)
  {
    $this->_deleted($orig);
    $this->_added($final);
  }
}

class TableDiffFormatter extends DiffFormatter
{
  function TableDiffFormatter()
  {
    $this->leading_context_lines = 2;
    $this->trailing_context_lines = 2;
  }

  function _pre($text)
  {
    $text = htmlspecialchars($text);
    $text = str_replace('  ',' &nbsp;',$text);
    return $text;
  }

  function _block_header( $xbeg, $xlen, $ybeg, $ylen )
  {
    $l1 = 'Ligne '.$xbeg;
    $l2 = 'Ligne '.$ybeg;
    $r = '<tr><td class="diff-blockheader" colspan="2">'.$l1.":</td>\n" .
      '<td class="diff-blockheader" colspan="2">'.$l2.":</td></tr>\n";
    return $r;
  }

  function _start_block( $header )
  {
    print( $header );
  }

  function _end_block()
  {
  }

  function _lines( $lines, $prefix=' ', $color="white" )
  {
  }

  function addedLine( $line )
  {
    $line = str_replace('  ','&nbsp; ',$line);
    return '<td>+</td><td class="diff-addedline">' .
      $line.'</td>';
  }

  function deletedLine( $line )
  {
    $line = str_replace('  ','&nbsp; ',$line);
    return '<td>-</td><td class="diff-deletedline">' .
      $line.'</td>';
  }

  function emptyLine()
  {
    return '<td colspan="2">&nbsp;</td>';
  }

  function contextLine( $line )
  {
    $line = str_replace('  ','&nbsp; ',$line);
    return '<td> </td><td class="diff-context">'.$line.'</td>';
  }

  function _added($lines)
  {
    foreach ($lines as $line)
      print( '<tr>' . $this->emptyLine() . $this->addedLine( $line ) . "</tr>\n" );
  }

  function _deleted($lines)
  {
    foreach ($lines as $line)
      print( '<tr>' . $this->deletedLine( $line ) . $this->emptyLine() . "</tr>\n" );
  }

  function _context( $lines )
  {
    foreach ($lines as $line)
      print( '<tr>' . $this->contextLine( $line ) . $this->contextLine( $line ) . "</tr>\n" );
  }

  /* cette fonction pose pb, faudra chercher pourquoi */
  function _changed( $orig, $closing )
  {
    $diff = new WordLevelDiff( $orig, $closing );
    $del = $diff->orig();
    $add = $diff->closing();
    while ( $line = array_shift( $del ) )
    {
      $aline = array_shift( $add );
      print( '<tr>' . $this->deletedLine( $line ) . $this->addedLine( $aline ) . "</tr>\n" );
    }
    $this->_added( $add );
  }
}
