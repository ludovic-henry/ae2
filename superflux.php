<?
$topdir = "./";
require_once($topdir. "include/site.inc.php");
$site = new site();
$site->start_page("", "Superflux" );

$infofile = $topdir."var/cache/stream-prod";
if ( file_exists($infofile) )
  $GLOBALS["streaminfo"] = unserialize(file_get_contents($infofile));

$cts = new contents("Superflux");
if ( $GLOBALS["streaminfo"]["ogg"] || $GLOBALS["streaminfo"]["mp3"] )
{
  if ( $GLOBALS["streaminfo"]["title"] || $GLOBALS["streaminfo"]["artist"] )
  {
    $cts->add_title(2,"Actuellement");

    $cts->add_paragraph("<b>".
                        htmlentities($GLOBALS["streaminfo"]["title"], ENT_NOQUOTES, "UTF-8").
                        "</b>, interprété par : <b><i>".
                        htmlentities($GLOBALS["streaminfo"]["artist"], ENT_NOQUOTES, "UTF-8").
                        "</i></b>");
  }

  if ( $GLOBALS["streaminfo"]["message"] )
  {
    $cts->add_title(2,"Information");
    $cts->add_paragraph($GLOBALS["streaminfo"]["message"]);
  }
  $cts->add_title(2,"Ecouter");
  $list = new itemlist();

  if ( $GLOBALS["streaminfo"]["mp3"] )
  {
    $list->add("<a href=\"".$wwwtopdir."stream.php\" onclick=\"return popUpStream('".$wwwtopdir."');\">Lecteur web</a>");
    $list->add("<a href=\"".$GLOBALS["streaminfo"]["mp3"]."\">Flux MP3</a>");
  }

  if ( $GLOBALS["streaminfo"]["ogg"] )
    $list->add("<a href=\"".$GLOBALS["streaminfo"]["ogg"]."\">Flux Ogg</a>");

  $cts->add($list);
}
else
{
  $cts->add_paragraph("Indisponible");
  if ( $GLOBALS["streaminfo"]["message"] )
  {
    $cts->add_title(2,"Information");
    $cts->add_paragraph($GLOBALS["streaminfo"]["message"]);
  }
}

$site->add_contents($cts);
$site->end_page();

?>
