<?
/** @file
 *
 * @brief fonctions de cryptographie mcrypt permettant
 * l'encryption / la décryption de données.
 *
 */

/* Copyright 2007
 *
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


$ae_secret_key = "AE_SECRET_KEY";

function ae_mcrypt_gen_iv()
{
  return mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256,
					     MCRYPT_MODE_ECB),
			  MCRYPT_DEV_URANDOM);
}

function encrypt_datas($datas)
{
  global $ae_secret_key;
  return mcrypt_encrypt(MCRYPT_RIJNDAEL_256,
			$ae_secret_key,
			$datas,
			MCRYPT_MODE_ECB,
			ae_mcrypt_gen_iv());
}

function decrypt_datas($datas)
{
  global $ae_secret_key;
  $str = mcrypt_encrypt(MCRYPT_RIJNDAEL_256,
			$ae_secret_key,
			$datas,
			MCRYPT_MODE_ECB,
			ae_mcrypt_gen_iv());

  /* PHP ne considère pas le '\0' comme une fin de
   * chaîne, et gère ca dans ses types "en interne".
   *
   * On recherche donc la véritable fin de chaîne,
   * afin de renvoyer les données correctement déchifrées.
   */
  $len = 0;

  for ($i = 0; $i < strlen($str); $i++)
    {
      if ($str[$i] != chr(0))
	$len++;
      else
	break;
    }

  return substr($str, 0, $len);

}


?>
