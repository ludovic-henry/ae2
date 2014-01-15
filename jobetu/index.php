<?
/* Copyright 2007
 * - Manuel Vonthron < manuel DOT vonthron AT acadis DOT org >
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

$topdir = "../";

require_once($topdir . "include/site.inc.php");
require_once($topdir . "include/cts/board.inc.php");
require_once($topdir . "include/cts/tagcloud.inc.php");
require_once("include/jobetu.inc.php");
require_once("include/cts/jobetu.inc.php");
require_once("include/jobuser_etu.inc.php");

define("GRP_JOBETU_ETU", 36);

$site = new site();
$site->start_page("services", "AE Job Etu");


$header = new contents("Bienvenue sur AE Job Etu");

$intro = <<<EOF
AE Job Etu aide étudiants et employeurs (particuliers ou entreprises) à entrer en relation afin de contractualiser des jobs.
Les étudiants peuvent donc offrir leurs services et les employeurs en demander, AE JobEtu les met en correspondance.

A lire :
  * [[http://ae.utbm.fr/article.php?name=docs:jobetu:cgu|Conditions Générales d'Utilisation]]
  * [[http://ae.utbm.fr/article.php?name=docs:jobetu:faq|F.A.Q. Générale]]
  * [[http://ae.utbm.fr/article.php?name=docs:jobetu:faq-recruteurs|F.A.Q. Recruteurs]]
  * [[http://ae.utbm.fr/article.php?name=docs:jobetu:faq-candidats|F.A.Q. Etudiants]]

//AE JobEtu est un service proposé par l'Association des Etudiants de l'UTBM.//
EOF;

$header->add_paragraph(doku2xhtml($intro));
$site->add_contents($header);

if( isset($_REQUEST['activate']) )
{
  $site->allow_only_logged_users("services");
  if( $site->user->is_in_group("jobetu_etu") ) header("Location: board_etu.php"); // ya bien des boulets pour s'inscrire deux fois

  $error = "";
  if( isset($_REQUEST['magicform']['name']) && $_REQUEST['magicform']['name'] == "activ_form" )
  {
    if( isset($_REQUEST['accept_cgu']) && $_REQUEST['accept_cgu'] == 1)
    {
        $site->user->add_to_group(GRP_JOBETU_ETU);
        header("Location: board_etu.php");
        exit;
    }
    else
      $error = "Vous devez impérativement accepter les CGU de AE JobEtu pour continuer";
  }

  $cts = new contents("Faites partie de AE Job Etu !");
  $cts->add_paragraph("Vous vous apprêtez à vous inscrire en temps que candidat à AE Job Etu.");
  $text = <<<EOF
Quelques mots sur le fonctionnement du service :
  * Les 'recruteurs' (particuliers ou entreprises) déposent leur annonce sur le site, en font notamment la description, indiquent également le type de travail dont il s'agit.
  * Par défaut, les annonces vous seront proposées selon les compétences que vous aurez sélectionnées dans votre profil (auquel vous accederez après cette page), vous pourrez également accéder à toutes les annonces disponibles, quelques soient les qualifications requises, via l'onglet "tout jobetu".
  * Vous pourrez alors poster votre candidature à une annonce, ainsi qu'y joindre un message si vous le souhaitez, sorte de mini lettre de motivation.
  * Le client recevra alors toutes les candidatures qui lui sont offertes et pourra faire son choix parmi celles ci, vous serez tenu au courant de cette évolution via votre tableau de bord, ou bien même par mail si vous le souhaitez
  * A la fin du contrat, le demandeur pourra mettre une appréciation à votre prestation (positive, négative ou neutre) s'il le souhaite, afin de vous permettre de mettre en avant votre sérieux pour de futures candidatures.

Rappelons que l'inscription à AE JobEtu est soumise à l'acceptation des [[http://ae.utbm.fr/article.php?name=docs:jobetu:cgu|conditions générales d'utilisation]].
EOF;
  $cts->add_paragraph(doku2xhtml($text));

  $frm = new form("activ_form", "index.php?activate", false, "POST");
  if($error)
    $frm->error($error);
  $frm->add_checkbox("accept_cgu", "Je reconnais avoir lu et accepter les <a href=\"http://ae.utbm.fr/article.php?name=docs:jobetu:cgu\">CGU d'AE Job Etu</a>");
  $frm->add_submit("go", "Activer mon compte");
  $cts->add($frm);
  $site->add_contents($cts, true);
}
else
{
  $link_etu = new contents("Vous êtes étudiant ?");

  if($site->user->is_in_group('jobetu_etu'))
  {
    $link_etu->add_paragraph("Gérez vos annonces, vos candidatures, votre profil depuis votre tableau de bord.");
    $link_etu->add_paragraph("<div align='center'><a href='board_etu.php'><img src=\"$topdir/images/jobetu/etu_2.png\" alt=\"Accédez à votre tableau de bord\" /></a></div>");
  }
  else
  {
    $link_etu->add_paragraph("Inscrivez vous à AE JobEtu pour pouvoir répondre aux annonces disponibles.");
    $link_etu->add_paragraph("<div align='center'><a href='index.php?activate'><img src=\"$topdir/images/jobetu/etu_1.png\" alt=\"Activez votre compte !\" /></a></div>");
  }
  $link_etu->add_paragraph("<div align='center'>Astuce : ne loupez aucune annonce grâce au <a href=\"rss.php\"> Flux RSS</a></div>");

  $link_client = new contents("Vous êtes un particulier, une entreprise ?");

  if($site->user->is_in_group('jobetu_client'))
  {
    $link_client->add_paragraph("Gérez vos annonces, les candidatures, vos préférences depuis votre tableau de bord.");
    $link_client->add_paragraph("<div align='center'><a href='board_client.php'><img src=\"$topdir/images/jobetu/client_2.png\" alt=\"Accédez à votre tableau de bord\" /></a></div>");
  }
  else
  {
    $link_client->add_paragraph("Passez dès maintenant votre annonce sur AE JobEtu (inscription au site requise).");
    $link_client->add_paragraph("<div align='center'><a href='depot.php'><img src=\"$topdir/images/jobetu/client_1.png\" alt=\"Passez votre annonce !\" /></a></div>");
    $link_client->add_paragraph("<div align='center'><a href='board_client.php'>Ou connectez vous pour accéder à votre tableau de bord.</a></div>");
  }


  $board = new board();
    $board->add($link_client, true);
    $board->add($link_etu, true);

  $site->add_contents($board);


  $tags = new contents("Compétences actuellement disponibles");

  $sql = new requete($site->db, "SELECT nom, COUNT(id_type) AS val
                                FROM `job_types_etu`
                                NATURAL JOIN `job_types`
                                WHERE id_utilisateur NOT IN (SELECT id_etu FROM `job_annonces_etu` NATURAL JOIN `job_annonces` WHERE closed = '0' AND relation = 'selected')
                                GROUP BY id_type
                                ORDER BY nom DESC
                                ");

  $array_tags = array();
  while($row = $sql->get_row())
    $array_tags[ $row['nom'] ] = $row['val'];

  $tags->add( new tagcloud($array_tags, null, "{qty} étudiants sont actuellements disponibles pour {name}") );
  $site->add_contents($tags, true);
}

$site->end_page();

?>
