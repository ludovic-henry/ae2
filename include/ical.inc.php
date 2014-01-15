<?php





class icalendar
{
  var $name;
  var $filename;
  var $events;

  function icalendar ( $name = "AE EVENEMENTS", $filename = "ae-events.ics" )
  {
    $this->name = $name;
    $this->filename = $filename;
    $this->events=array();
  }

  static function iescape ( $str )
  {
    $str=preg_replace('/([\,\\\\;])/u','\\\\$1', $str);
    $str=preg_replace('/\n/u','\\n', $str);
    $str=preg_replace('/\r/u','', $str);
    return $str;
  }

  function add_event ( $uid, $summary, $description, $start, $end, $dateonly=false, $url=null, $location=null, $lat=null, $long=null )
  {
    $this->events[] = array(
      "uid" => $uid,
      "summary" => $summary,
      "description" => $description,
      "start" => $start,
      "end" => $end,
      "dateonly" => $dateonly,
      "location" => $location,
      "lat" => $lat,
      "long" => $long,
      "url" => $url);
  }

  function render ()
  {
    header("Content-Type: text/calendar; charset=utf-8");
    header("Content-Disposition: filename=".$this->filename."");

    echo "BEGIN:VCALENDAR\n";
    echo "VERSION:2.0\n";
    echo "X-WR-CALNAME:".$this->iescape($this->name)."\n";
    echo "PRODID:-//AE UTBM//AE2 v1//EN\n";
    echo "X-WR-RELCALID:http://ae.utbm.fr/ical.php\n";
    echo "X-WR-TIMEZONE:Europe/Paris\n";
    echo "CALSCALE:GREGORIAN\n";
    echo "METHOD:PUBLISH\n";

    echo "BEGIN:VTIMEZONE
TZID:Europe/Paris
X-LIC-LOCATION:Europe/Paris
BEGIN:DAYLIGHT
TZOFFSETFROM:+0100
TZOFFSETTO:+0200
TZNAME:CEST
DTSTART:19700329T020000
RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=-1SU
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:+0200
TZOFFSETTO:+0100d
TZNAME:CET
DTSTART:19701025T030000
RRULE:FREQ=YEARLY;BYMONTH=10;BYDAY=-1SU
END:STANDARD
END:VTIMEZONE
";

    foreach ( $this->events as $event )
    {
      echo "BEGIN:VEVENT\n";
      echo "UID:".$this->iescape($event["uid"])."\n";
      echo "SUMMARY:".$this->iescape($event['summary'])."\n";
      echo "DESCRIPTION:".$this->iescape($event['description'])."\n";

      if ( isset($event['dateonly'])  && $event['dateonly'] )
      {
        echo "DTSTART;TZID=Europe/Paris;VALUE=DATE:".date("Ymd",$event['start'])."\n";
        echo "DTEND;TZID=Europe/Paris;VALUE=DATE:".date("Ymd",$event['end'])."\n";
      }
      else
      {
        echo "DTSTART;TZID=Europe/Paris:".date("Ymd",$event['start'])."T".date("His",$event['start'])."\n";
        echo "DTEND;TZID=Europe/Paris:".date("Ymd",$event['end'])."T".date("His",$event['end'])."\n";
      }

      if ( isset($event['location']) && $event['location'] )
        echo "LOCATION:".$this->iescape($event['location'])."\n";

      if ( isset($event['lat']) && isset($event['long']) && !is_null($event['lat']) && !is_null($event['long'])  )
        echo "GEO:".sprintf("%.12F",$event['lat']*360/2/M_PI).";".sprintf("%.12F",$event['long']*360/2/M_PI)."\n";

      if ( isset($event['url']) && $event['url'] )
        echo "URL:".$this->iescape($event["url"])."\n";

      echo "END:VEVENT\n";
    }
    echo "END:VCALENDAR\n";
  }


}



?>
