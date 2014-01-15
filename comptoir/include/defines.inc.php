<?php

/** @file
 *
 * @brief définitions générales du systeme de comptoirs
 *        actions spécials à effectuer (paramétrables)
 *        lors d'achats d'articles (ajout, update, suppression
 *        dans la base ...)
 */

/* Copyright 2005
 * - Julien Etelain <julien CHEZ pmad POINT net>
 * - Pierre Mauduit <pierre POINT mauduit CHEZ utbm POINT fr>
 *
 * Ce fichier fait partie du site de l'Association des étudiants de
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

/**
 * @addtogroup comptoirs
 * @{
 */

/* Action de vente produit */

/* Ignore le stock */
define("ACTION_VSIMPLE",0);

/* Prend en compte le stock */
define("ACTION_VSTOCKLIM",1);
/* Vente de pass (ajoute une entrée dans une table),
 * prends en compte le stock */
define("ACTION_PASS",2);

/* Pour les machines à laver */
define("ACTION_JETON",3);

/* Pour les machines à laver */
define("ACTION_BON",4);

define("ACTION_CLASS",5);

$ActionsProduits = array (
  ACTION_VSIMPLE => "Vente simple (idéal pour les bars)",
  ACTION_VSTOCKLIM => "Vente avec limitation par le stock",
  ACTION_PASS => "Vente de pass (précisez le paramètre)",
  ACTION_JETON => "Vente de jetons (précisez le paramètre)",
  ACTION_BON => "Bon rechargement",
  ACTION_CLASS => "Class class() ou class(param)"
);

/* note pedrov : J'imaginais un truc beaucoup plus "global" que cela
 * genre des actions DB_UPDATE, DB_INSERT, DB_DELETE, avec comme passage
 * de parametres la table concernée, une liste de valeurs pour matcher ...
 */

define("PAIE_CHEQUE",0);
define("PAIE_ESPECS",1);
define("PAIE_BONSITE",2);

$TypesPaiements = array (
  PAIE_CHEQUE => "Chèque",
  PAIE_ESPECS => "Espèces"
);

$TypesPaiementsFull = array (
  PAIE_CHEQUE => "Chèque",
  PAIE_ESPECS => "Espèces",
  PAIE_BONSITE => "Carte bleue"
);

$Banques = array( 0 => "--",
       1 => "Société Générale",
       2 => "Banque Populaire",
       3 => "BNP",
       4 => "Caisse d'Epargne",
       5 => "CIC",
       6 => "Crédit Agricole",
       7 => "Crédit Mutuel",
       8 => "Crédit Lyonnais",
       9 => "La Poste",
       100 => "Autre");


$TypesComptoir = array (0 => "Comptoir classique", 1 => "E-boutic", 2 => "Bureau");


define("ETAT_FACT_A_EXPEDIER",         0x01);
define("ETAT_FACT_A_EXPEDIER_PARTIEL", 0x02);
define("ETAT_FACT_A_RETIRER",          0x04);
define("ETAT_FACT_A_RETIRER_PARTIEL",  0x08);


?>
