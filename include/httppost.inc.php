<?
/** @file
 *
 * @brief Classe d'HTTP Post
 *
 */
/* Copyright 2006
 * - Pierre Mauduit <pierre POINT mauduit CHEZ utbm POINT fr>
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

class http_post
{
  /* l'hote distant */
  var $host;
  /* port de connexion */
  var $port;
  /* l'addresse de la page sur laquelle on souhaite poster */
  var $path;
  /* la requete */
  var $query;

  /* buffer de sortie */
  var $out_buffer;

  function http_post ($host, $port = 80, $path, $query)
  {
    $this->host  = $host;
    $this->path  = $path;
    $this->port  = $port;

    if (!is_array($query))
      $this->query = $query;
    else
      foreach ($query as $key => $value)
	$this->query .= ($key . "=".$value."&");

    $this->post ();
  }

  function post ()
  {

    $post="POST $this->path HTTP/1.1\r\n".
      "Host: $this->host\r\n".
      "Content-type: application/x-www-form-urlencoded\r\n".
      "User-Agent: Mozilla 4.0\r\n".
      "Content-length: ".strlen($this->query)."\r\n".
      "Connection: close\r\n".
      "\r\n$query";

    $sckt = fsockopen($this->host,80);

    if (!$sckt)
      return false;

    fwrite($sckt, $post);

    while (!feof ($sckt))
      $out_buffer .= fgets($sckt, 128);

    fclose($h);
    return;
  }


}

class http_get
{
  /* buffer de sortie */
  var $out_buffer;

  function http_get ($host, $port = 80, $addr)
  {
    $fp = fsockopen($host, $port);
    if (!$fp)
      return;

    else
      {
	$out = "GET $addr HTTP/1.1\r\n";
	$out .= "Host: $host\r\n";
	$out .= "Connection: Close\r\n\r\n";
      }

    fwrite($fp, $out);

    while (!feof($fp))
      $this->out_buffer .= fgets($fp, 128);

    fclose($fp);
  }
}
?>
