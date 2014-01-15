<?php
/** @file
 *
 * @brief Connexion aux bases PostGreSQL de l'AE.
 *
 */

/* Copyright 2007
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

class pgsqlae
{

  var $connection = null;

  function pgsqlae ()
  {
    /* La problématique de la livraison des mots de passe n'a pas une
     * importance capitale ici, au vu des données que l'on garde en
     * base de données PostGreSQL, et des accès possibles (pas de
     * frontend web).
     */

    $this->connection = pg_connect("host=localhost
                                    dbname=geography
                                    user=geography
                                    password=geography");
  }

}

class pgrequete
{
  var $base;
  var $sql;
  var $result;

  var $errno;
  var $errmsg;

  var $lines;

  function pgrequete ($base, $req_sql, $debug = 0)
  {
    $this->base = $base;
    $this->sql = $req_sql;
    $esql = explode(" ", $req_sql);

    if(!$base->connection)
    {
      $this->errmsg = "Non connecté";
      $this->lines = -1;
    }

    $res = pg_query($base->connection, $req_sql);
    $this->errno = pg_last_error($base->connection);

    if ($this->errno != 0)
    {
      $this->errmsg = pg_last_error($base->connection);
      $this->lines = -1;
      return FALSE;
    }

    $this->errmsg = "";

    if($this->result)
      pg_free_result($this->result);

    $this->result = $res;

    if(strcasecmp($esql[0], "SELECT") == 0)
    {
      $this->lines =  pg_num_rows ($res);
    }
    else
    {
      $this->lines =  pg_affected_rows ($res);
    }
    if($debug == 1)
    {
      echo "Votre requete SQL est <b> " . $this->sql . "</b><br/>";
    }
  }

  function get_all_rows ()
  {
    if(!empty($this->result))
      return pg_fetch_all($this->result);
    else
      return;
  }
}



?>
