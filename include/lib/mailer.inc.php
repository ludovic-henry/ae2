<?
class mailer
{
  private $to       = array();
  private $img      = array();
  private $from     = 'ae@utbm.fr';
  private $subject  = '';
  private $plaintxt = null;
  private $htmltext = '';
  public function mailer($from,$subject)
  {
    $this->from    = $from;
    $this->subject = $subject;
  }

  public function add_dest($to)
  {
    if(is_array($to))
      foreach($to as $dest)
        $this->to[]=$dest;
    else
      $this->to[]=$to;
  }

  public function add_img($img)
  {
    $this->img[] = $img;
  }

  public function set_plain($txt)
  {
    $this->plaintxt = $txt;
  }

  public function set_html($html)
  {
    $this->htmltext = $html;
  }

  public function send()
  {
    $boundary = "-----=".md5(uniqid(rand()));
    $header   = "MIME-Version: 1.0\n";
    $header  .= "Content-Type: multipart/Alternative; boundary=\"$boundary\"\n";
    $header  .= "\n";
    $msg      = "Ceci est un message au format MIME 1.0 multipart/mixed.\n";
    if(!is_null($this->plaintxt))
    {
      $msg     .= "--$boundary\n";
      $msg     .= "Content-Type: Text/Plain;\n  charset=\"UTF-8\"\n";
      $msg     .= "Content-Transfer-Encoding: quoted-printable\n\n";
      $msg     .= eregi_replace("\\\'","'",$this->plaintxt)."\n";
    }
    $msg     .= "--$boundary\n";
    $msg     .= "Content-Type: Text/HTML;\n  charset=\"UTF-8\"\n";
    $msg     .= "Content-Transfer-Encoding: quoted-printable\n";
    if(!empty($this->img))
    {
      $attach = '';
      foreach($this->img as $img)
      {
        if(is_object($img))
        {
          if( is_a($img,'dfile')  || is_subclass_of($img,'dfile'))
          {
            $filename = $img->get_real_filename();
            if ( file_exists($filename) && $fp = fopen($filename, "rb"))
            {
              $attachment     = fread($fp, filesize($filename));
              fclose($fp);
              $uid            = gen_uid();
              $this->htmltext = str_replace('dfile://'.$img->id,"cid:".$uid,$this->htmltext);
              $attach        .= "--$boundary\n";
              $mime           = mime_content_type($filename);
              $attach        .= "Content-Type: ".$mime."; name=\"".$img->nom_fichier."\"\n";
              $attach        .= "Content-Transfer-Encoding: base64\n";
              $attach        .= "Content-ID: <".$uid.">\n\n";
              $attach        .= chunk_split(base64_encode($attachment))."\n\n\n";
            }
          }
        }
        elseif($fp = fopen($img, "rb"))
        {
          $attachment     = fread($fp, filesize($img));
          fclose($fp);
          $uid            = gen_uid();
          $this->htmltext = str_replace($img,"cid:".$uid,$this->htmltext);
          $attach        .= "--$boundary\n";
          $mime           = mime_content_type($img);
          $attach        .= "Content-Type: ".$mime."; name=\"".basename($img)."\"\n";
          $attach        .= "Content-Transfer-Encoding: base64\n";
          $attach        .= "Content-ID: <".$uid.">\n\n";
          $attach        .= chunk_split(base64_encode($attachment))."\n\n\n";
        }
      }
      $msg   .= eregi_replace("\\\'","'",str_replace('=','=3D', $this->htmltext))."\n";
      $msg   .= $attach;
      unset($attach);
    }
    else
      $msg   .= eregi_replace("\\\'","'",str_replace('=','=3D', $this->htmltext))."\n";
    $msg     .= "--$boundary--\n";
    mail(implode(', ',$this->to),
         $this->subject,
         $msg,
         "Reply-to: ".$this->from."\nFrom: ".$this->from."\n".$header);
    unset($msg);
  }
}
?>
