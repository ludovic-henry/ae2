<?php

/* Copyright 2007
 * - Julien Etelain <julien CHEZ pmad POINT net>
 *
 * Ce fichier fait partie du site de l'Association des Étudiants de
 * l'UTBM, http://ae.utbm.fr.
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License a
 * published by the Free Software Foundation; either version 2 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Sofware
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA
 * 02111-1307, USA.
 */

$topdir = "./";

if ( preg_match('/^\/var\/www\/ae\/www\/(taiste|taiste21)\//', $_SERVER['SCRIPT_FILENAME']) )
  $infofile = $topdir."var/cache/stream";
else
  $infofile = $topdir."var/cache/stream-prod";

if ( file_exists($infofile) )
  $GLOBALS["streaminfo"] = unserialize(file_get_contents($infofile));

if ( $_REQUEST["get"] == "qt.pls" )
{
  header("Content-Type: audio/x-scpls");
  echo "[playlist]\n";
  echo "File1=".$GLOBALS["streaminfo"]["mp3"]."\n";
  echo "Title1=\n";
  echo "Length1=-1\n";
  echo "NumberOfEntries=1\n";
  echo "Version=2\n";
  exit();
}
if ( $_REQUEST["get"] == "real.ram" )
{
  header("Content-Type: audio/x-pn-realaudio");
  echo $GLOBALS["streaminfo"]["mp3"]."\n";
  exit();
}
if ( $_REQUEST["get"] == "info" )
{
  header("Content-Type: text/html; charset=utf-8");
  echo htmlentities($GLOBALS["streaminfo"]["title"], ENT_NOQUOTES, "UTF-8").
       " - ". htmlentities($GLOBALS["streaminfo"]["artist"], ENT_NOQUOTES, "UTF-8");
  exit();
}
if ( $_REQUEST["get"] == "popup" )
{
  header("Content-Type: text/html; charset=utf-8");

  if ( !$GLOBALS["streaminfo"]["mp3"] )
  {
    echo "<p>Indisponible actuellement</p>";
    exit();
  }

  echo "<html>\n";
  echo "<head>\n";
  echo "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\" />\n";
  echo "<title>Superflux - Lecteur web</title>\n";
  echo "<link rel=\"stylesheet\" type=\"text/css\" href=\"css/superflux.css\" />\n";
  echo "<script type=\"text/javascript\" src=\"js/site.js\"></script>\n";
  echo "<script type=\"text/javascript\" src=\"js/ajax.js\"></script>\n";
  echo "<script type=\"text/javascript\" src=\"js/superflux.js\"></script>\n";
  echo "</head>\n";
  echo "<body>\n";

  echo "<h1>Superflux</h1>";
  echo "<p id=\"ecoute\">Actuellement</p>";
  echo "<p id=\"streaminfo\">".htmlentities($GLOBALS["streaminfo"]["title"], ENT_NOQUOTES, "UTF-8").
       " - ". htmlentities($GLOBALS["streaminfo"]["artist"], ENT_NOQUOTES, "UTF-8")."</p>";

  $plug = "quicktime";

  if ( stripos($_SERVER["HTTP_USER_AGENT"],"Windows") !== FALSE )
    // Sous windows, 99.99% de chances que WMP soit présent
    $plug = "wmp";
  elseif ( stripos($_SERVER["HTTP_USER_AGENT"],"Mac OS X") !== FALSE )
    // Sous Mac OS X, QuickTime forcément présent
    $plug = "quicktime";
  elseif ( stripos($_SERVER["HTTP_USER_AGENT"],"Linux") !== FALSE )
    // Sous linux, c'est le problème du navigateur
    $plug = "autre";

  if (isset($_REQUEST["plug"]))
    $plug = $_REQUEST["plug"];

  $plugins = array("quicktime"=>"QuickTime","wmp"=>"Windows Media Player","real"=>"Real Player","vlc"=>"VLC","autre"=>"Autre");

  if ( $plug == "wmp" )
  {
    echo "<object classid=\"CLSID:22d6f312-b0f6-11d0-94ab-0080c74c7e95\"
    codebase=\"http://activex.microsoft.com/activex/controls/mplayer/en/nsmp2inf.cab#Version=5,1,52,701\"
    type=\"application/x-oleobject\" width=\"250\" height=\"60\">
                  <param name=\"filename\" value=\"".$GLOBALS["streaminfo"]["mp3"]."\">
                  <param name=\"autostart\" value=\"1\">
                  <embed width=\"250\" height=\"60\" src=\"".$GLOBALS["streaminfo"]["mp3"]."\"
                  autostart=\"1\" type=\"application/x-mplayer2\"
                  pluginspage=\"http://www.microsoft.com/Windows/MediaPlayer/download/default.asp\">
                  </embed></object>";
  }
  else if ( $plug == "real" )
  {
    echo "<object width=\"250\" height=\"60\" classid=\"clsid:CFCDAA03-8BE4-11cf-B84B-0020AFBBCCFA\">
    <param name=\"autostart\" value=\"true\">
    <param name=\"src\" value=\"http://".$_SERVER["HTTP_HOST"].$_SERVER["SCRIPT_NAME"]."?get=real.ram\">
    <embed src=\"http://".$_SERVER["HTTP_HOST"].$_SERVER["SCRIPT_NAME"]."?get=real.ram\"
     width=\"250\" height=\"60\" type=\"audio/x-pn-realaudio-plugin\"
     autostart=\"true\"></embed></object>";
  }
  else if ( $plug == "vlc" )
  {
    $vlcStream = $GLOBALS["streaminfo"]["mp3"];

    if ( $GLOBALS["streaminfo"]["ogg"] ) // Ogg préféré pour VLC
      $vlcStream = $GLOBALS["streaminfo"]["ogg"];

    echo "<object classid=\"clsid:E23FE9C6-778E-49D4-B537-38FCDE4887D8\"
          codebase=\"http://downloads.videolan.org/pub/videolan/vlc/latest/win32/axvlc.cab\"
          id=\"stream\" type=\"application/x-oleobject\" width=\"1\" height=\"1\">
      <param name=\"autoplay\" value=\"true\" />
      <param name=\"src\" value=\"$vlcStream\" />
      <embed type=\"application/x-vlc-plugin\"
         name=\"stream\"
         autoplay=\"yes\" loop=\"yes\" width=\"1\" height=\"1\"
         target=\"$vlcStream\" /></object><br />VLC :
        <a href=\"javascript:;\" onclick=\"if(!document.all) document.stream.play(); else document.getElementById('stream').play();\">Play</a>
        <a href=\"javascript:;\" onclick=\"if(!document.all) document.stream.stop(); else document.getElementById('stream').stop();\">Stop</a>";
  }
  else if ( $plug == "autre" )
  {
    if ( $GLOBALS["streaminfo"]["ogg"] )
      echo "<embed width=\"250\" height=\"60\" src=\"".$GLOBALS["streaminfo"]["ogg"]."\" autoplay=\"true\" type=\"application/ogg\"></embed>";
    else
      echo "<embed width=\"250\" height=\"60\" src=\"".$GLOBALS["streaminfo"]["mp3"]."\" autoplay=\"true\" type=\"audio/mpeg\"></embed>";
  }
  else
  {
    echo "<object classid=\"clsid:02BF25D5-8C17-4B23-BC80-D3488ABDDC6B\"
     codebase=\"http://www.apple.com/qtactivex/qtplugin.cab#version=6,0,2,0\" width=\"250\" height=\"60\">
    <param name=\"src\" value=\"http://".$_SERVER["HTTP_HOST"].$_SERVER["SCRIPT_NAME"]."?get=qt.pls\" />
    <param name=\"autoplay\" value=\"true\" />
    <embed width=\"250\" height=\"60\" type=\"image/x-quicktime\" src=\"http://".$_SERVER["HTTP_HOST"].$_SERVER["SCRIPT_NAME"]."?get=qt.pls\"
      pluginspage=\"http://www.apple.com/quicktime/download/\"
      autoplay=\"true\" qtsrc=\"http://".$_SERVER["HTTP_HOST"].$_SERVER["SCRIPT_NAME"]."?get=qt.pls\"></embed></object>";
  }

  echo "<p id=\"players\">Lecteur audio :</p>";
  echo "<ul>";
  foreach ( $plugins as $key => $desc)
  {
    echo "<li><a href=\"?get=popup&amp;plug=$key\">$desc</a></li>";
  }
  echo "</ul>";

  echo "</body>\n";
  echo "</html>\n";

  exit();
}

require_once($topdir. "include/site.inc.php");

$site = new site();

$site->start_page("services","Streaming");

$cts = new contents("Superflux, la webradio");

if ( $GLOBALS["streaminfo"]["mp3"] )
{
  $cts->add_paragraph("<a href=\"stream.php?get=popup\" onclick=\"return popUpStream('".$wwwtopdir."');\">Ecouter avec le lecteur web</a>");
  $cts->add_paragraph("Flux MP3 : <a href=\"".$GLOBALS["streaminfo"]["mp3"]."\">".$GLOBALS["streaminfo"]["mp3"]."</a>");
}

if ( $GLOBALS["streaminfo"]["ogg"] )
  $cts->add_paragraph("Flux Ogg (recommandé) : <a href=\"".$GLOBALS["streaminfo"]["ogg"]."\">".$GLOBALS["streaminfo"]["ogg"]."</a>");

$site->add_contents($cts);

$site->end_page();



?>
