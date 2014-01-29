<?php

/** @file
 *
 *
 */
/* Copyright 2005 - 2010
 * - Julien Etelain < julien at pmad dot net >
 * - Simon lopez < simon dot lopez at ayolo dot org >
 * - Benjamin Collet < bcollet at oxynux dot org>
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

$timing["all"] -= microtime(true);

setlocale(LC_ALL, "fr_FR.UTF8");

if (!strncmp('/var/www/taiste', $_SERVER['SCRIPT_FILENAME'], 15))
    $GLOBALS["taiste"] = true;
else
    $GLOBALS["taiste"] = false;

require_once($topdir . "include/mysql.inc.php");
require_once($topdir . "include/mysqlae.inc.php");
require_once($topdir . "include/entities/std.inc.php");
require_once($topdir . "include/entities/utilisateur.inc.php");
require_once($topdir . "include/cts/standart.inc.php");

//if ( !isset($wwwtopdir) )
//{
if ($GLOBALS["taiste"]) {
    $wwwtopdir = "/taiste/";
    $fstopdir = $_SERVER['DOCUMENT_ROOT'] . "/../taiste/";
} else {
    $wwwtopdir = "/";
    $fstopdir = $_SERVER['DOCUMENT_ROOT'] . "/";
}
//}


/** Classe générant l'interface
 * @see site
 * @ingroup display
 */
class interfaceweb
{
    var $db;
    var $dbrw;
    var $user;

    var $contents;
    var $sides;
    var $sides_ref;
    var $boxes;

    protected $buffer = "";

    var $section;
    var $title;

    var $extracss;
    var $rss;
    var $extrajs;

    var $compact;

    var $params; // cache des paramètres

    var $meta_keywords;
    var $meta_description;
    var $alternate;

    var $tab_array = array(
        array("accueil", "index.php", "Accueil",
            array(
                array("index.php", "Les nouvelles"),
                array("events.php", "Aujourd'hui"),
                array("weekmail.php", "Le weekmail"),
            )),
        array("presentation", "article.php?name=presentation", "L'AE",
            array(
                array("article.php?name=presentation", "Présentation"),
                array("article.php?name=presentation:services", "Services quotidiens"),
                array("article.php?name=presentation:carteae", "La carte AE"),
                array("article.php?name=presentation:siteae", "Le site AE"),
                array("article.php?name=presentation:activites", "Activités et clubs"),
                array("activites.php?view=trombino", "Responsables des clubs"),
            )),
        array("matmatronch", "matmatronch/", "Matmatronch"),
        array("wiki", "wiki2/", "Wiki"),
        array("sas", "sas2/", "SAS"),
        array("forum", "forum2/", "Forum",
            array(
                array("forum2/index.php", "Sommaire"),
                array("forum2/search.php?page=unread", "Messages non lus"),
                array("forum2/search.php", "Recherche"),
                array("forum2/search.php?page=starred", "Favoris"),
                array("forum2/admin/", "Administration"),
            )),
        array("services", "article.php?name=services", "Services"),
        //array ("pg", "pgae.php", "Petit géni"),
        //e-boutic -> services
        //array ("e-boutic", "e-boutic/", "E-boutic"),
        array("fichiers", "d.php", "Fichiers",
            array(
                array("d.php", "Fichiers de l'AE"),
                array("asso.php", "Fichiers des associations et des clubs")
            )),
        array("liens", "article.php?name=liens", "Partenaires"),
        array("aide", "article.php?name=docs:index", "Aide"));

    /** Constructeur
     * @param $db instance de la base de donnée pour la lecture
     * @param $dbrw instance de la base de donéne pour l'écriture (+lecture)
     */
    function interfaceweb($db, $dbrw = false)
    {
        $this->db = $db;
        $this->dbrw = $dbrw;

        $this->sides["left"] = array();
        $this->sides["right"] = array();

        $this->user = new utilisateur($db, $dbrw);
        $this->extracss = array();
        $this->extrajs = array();
        $this->rss = array();
        $this->contents = array();
        $this->alternate = array();

    }

    /**
     * Permet de choisir de générer une page pour version mobile
     * @param $bool  true|false
     */
    public function set_mobile($bool)
    {
        /**
         * Define whether we want a mobile rendering or not
         * Will be redefined by set_mobile().
         *
         * We use a constant so it can be used by any class.
         * Notice that interfaceweb:interfaceweb is called everytime
         * you create a page instance.
         */
        if ($bool) define("MOBILE", true);

        /* Reset tab menu in mobile mode */
        if (defined("MOBILE")) $this->tab_array = array();

        /* Check if user is connected */
        if (!$this->user->is_valid()) {
            if ($GLOBALS["taiste"])
                $frm = new form("connect", "/taiste/connect.php", true, "POST", "Connexion");
            else
                $frm = new form("connect", "/connect.php", true, "POST", "Connexion");
            $frm->add_select_field("domain",
                "Connexion",
                array("utbm" => "UTBM / Assidu",
                    "carteae" => "Carte AE",
                    "id" => "ID",
                    "autre" => "E-mail",
                    "alias" => "Alias"));
            $frm->add_text_field("username", "Utilisateur", "", "", 20, true, true, null, false, 35);
            $frm->add_password_field("password", "Mot de passe", "", "", 20);
            $frm->add_checkbox("personnal_computer", "Me connecter automatiquement la prochaine fois", true);
            $frm->add_submit("connect", "Se connecter");
            $frm->add_hidden("mobile");
            $this->add_contents($frm);

            /* Come back here after connexion completed */
            $_SESSION['session_redirect'] = "m/"; /* Oh, a diplodocus ! Shhh !! */

            $this->end_page();
            exit(0);
        }
    }

    /** Défini les boites à afficher sur un coté
     * @param $side Coté (left ou right)
     * @param $boxes Array des nom des boites à afficher
     */
    function set_side_boxes($side, $boxes, $ref = null)
    {
        if ($side != "left" && $side != "right") return;
        $this->sides[$side] = $boxes;

        if ($ref == null) {
            if (isset($this->sides_ref[$side]))
                unset($this->sides_ref[$side]);
        } else
            $this->sides_ref[$side] = $ref;
    }

    /** Ajoute une boite affichable sur le coté
     * $name Nom de la boite
     * $contents Instance de stdcontents à afficher
     */
    function add_box($name, $contents)
    {
        if (is_null($contents))
            return;
        $this->boxes[$name] = $contents;
    }

    /** Ajoute une boite de contenu (dans le centre).
     * Si un titre est défini, alors il sera affiché.
     * @param $contents Instance de stdcontents à afficher.
     */
    function add_contents($contents)
    {
        $this->contents[] = $contents;
    }

    /** Initlialise la page
     * @param $section Nom de la section
     * @param $title Titre de la page
     */
    function start_page($section, $title, $compact = false) // <=> page
    {
        $this->section = $section;
        $this->title = $title;
        $this->compact = $compact;
    }

    /** Calcul de la survie des bars :P
     *
     */
    function get_comptoir()
    {
        return '';
    }

    function add_css($url)
    {
        $this->extracss[] = $url;
    }

    function add_js($url)
    {
        $this->extrajs[] = $url;
    }

    function add_rss($title, $url)
    {
        $this->add_alternate("application/rss+xml", $title, $url);
    }

    /** Termine et affiche la page
     */
    function end_page() // <=> html_render
    {
        global $fstopdir, $wwwtopdir, $topdir, $timing;
        $timing["render"] -= microtime(true);

        header("Content-Type: text/html; charset=utf-8");

        $this->buffer .= "<html xmlns=\"http://www.w3.org/1999/xhtml\" xmlns:v=\"urn:schemas-microsoft-com:vml\">\n";
        $this->buffer .= "<head>\n";

        $this->buffer .= "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\" />\n"; // (IE6 Legacy support)
        if (!defined('NOTAE')) {
            $this->buffer .= "<title>" . htmlentities($this->title, ENT_COMPAT, "UTF-8") . " - association des etudiants de l'utbm</title>\n";
            if (!defined("MOBILE"))
                $this->buffer .= "<link rel=\"stylesheet\" type=\"text/css\" href=\"" . $wwwtopdir . "themes/default3/css/site3.css?" . filemtime($fstopdir . "themes/default3/css/site3.css") . "\" title=\"AE2-NEW3\" />\n";
            else
                $this->buffer .= "<link rel=\"stylesheet\" type=\"text/css\" href=\"" . $wwwtopdir . "themes/mobile/css/site.css?" . filemtime($fstopdir . "themes/mobile/css/site.css") . "\" title=\"AE2-MOBILE\" />\n";
        } else {
            $this->buffer .= "<title>" . htmlentities($this->title, ENT_COMPAT, "UTF-8") . "</title>\n";
            if (isset($this->css))
                $this->buffer .= "<link rel=\"stylesheet\" type=\"text/css\" href=\"" . $wwwtopdir . $this->css . "?" . filemtime($fstopdir . $this->css) . "\" title=\"AE2-NEW2\" />\n";
            else {
                if (!defined("MOBILE"))
                    $this->buffer .= "<link rel=\"stylesheet\" type=\"text/css\" href=\"" . $wwwtopdir . "themes/default3/css/site3.css?" . filemtime($fstopdir . "themes/default3/css/site3.css") . "\" title=\"AE2-NEW3\" />\n";
                else
                    $this->buffer .= "<link rel=\"stylesheet\" type=\"text/css\" href=\"" . $wwwtopdir . "themes/mobile/css/site.css?" . filemtime($fstopdir . "themes/mobile/css/site.css") . "\" title=\"AE2-MOBILE\" />\n";
            }
        }
        foreach ($this->extracss as $url)
            if (file_exists(htmlentities($fstopdir . $url, ENT_COMPAT, "UTF-8")))
                $this->buffer .= "<link rel=\"stylesheet\" type=\"text/css\" href=\"" .
                    htmlentities($wwwtopdir . $url, ENT_COMPAT, "UTF-8") . "?" .
                    filemtime(htmlentities($fstopdir . $url, ENT_COMPAT, "UTF-8")) . "\" />\n";

        foreach ($this->alternate as $row) {
            $this->buffer .= "<link rel=\"alternate\" " .
                "type=\"" . htmlentities($row[0], ENT_COMPAT, "UTF-8") . "\" " .
                "title=\"" . htmlentities($row[1], ENT_COMPAT, "UTF-8") . "\" " .
                "href=\"" . htmlentities($row[2], ENT_COMPAT, "UTF-8") . "\" />\n";
        }

        if (!empty($this->meta_keywords))
            $this->buffer .= "<meta name=\"keywords\" content=\"" . htmlentities($this->meta_keywords, ENT_COMPAT, "UTF-8") . "\" />\n";

        if (!empty($this->meta_description))
            $this->buffer .= "<meta name=\"description\" content=\"" . htmlentities($this->meta_description, ENT_COMPAT, "UTF-8") . "\" />\n";

        $this->buffer .= "<link rel=\"SHORTCUT ICON\" href=\"" . $wwwtopdir . "favicon.ico?" . filemtime($fstopdir . "favicon.ico") . "\" />\n";
        if (!defined("MOBILE")) {
            $this->buffer .= "<script type=\"text/javascript\">var site_topdir='" . $wwwtopdir . "';</script>\n";
            $this->buffer .= "<script type=\"text/javascript\" src=\"" . $wwwtopdir . "js/site.js?" . filemtime($fstopdir . "js/site.js") . "\"></script>\n";
            $this->buffer .= "<script type=\"text/javascript\" src=\"" . $wwwtopdir . "js/ajax.js?" . filemtime($fstopdir . "js/ajax.js") . "\"></script>\n";
            $this->buffer .= "<script type=\"text/javascript\" src=\"" . $wwwtopdir . "js/dnds.js?" . filemtime($fstopdir . "js/dnds.js") . "\"></script>\n";
            $this->buffer .= "<script type=\"text/javascript\" src=\"" . $wwwtopdir . "js/box_slideshow.js?" . filemtime($fstopdir . "js/box_slideshow.js") . "\"></script>\n";
        } else {
            /*  add manualy extra js scripts. Mobile version have to be light ! */
            $this->buffer .= "<script type=\"text/javascript\" src=\"" . $wwwtopdir . "js/mobile.js?" . filemtime($fstopdir . "js/mobile.js") . "\"></script>\n";
        }

        foreach ($this->extrajs as $url)
            $this->buffer .= "<script type=\"text/javascript\" src=\"" . htmlentities($wwwtopdir . $url, ENT_QUOTES, "UTF-8") . "?" . filemtime(htmlentities($fstopdir . $url, ENT_QUOTES, "UTF-8")) . "\"></script>\n";

        $this->buffer .= "</head>\n";

        $this->buffer .= "<body>\n";
        /* Generate the logo */
        $this->buffer .= "<div id=\"site\">\n";
        if (!defined("MOBILE")) {
            $ovl = false;


            $this->buffer .= "<div id=\"dropmenudiv\" onmouseover=\"clearhidemenu()\" onmouseout=\"dynamichide(event)\"></div>\n";
            $this->buffer .= "<div id=\"overlay\" " . (!$ovl ? "onclick=\"hideConnexionBox()\"" : "") . " style=\"display:" . ($ovl ? 'block' : 'none') . "\"></div>\n";
            if (!$this->user->is_valid()) {
                /* Come back here after ! */
                $_SESSION['session_redirect'] = $_SERVER["REQUEST_URI"];

                $this->buffer .= '<div id="passwordbox" style="display:none">';
                $this->buffer .= '<img id="close" src="/images/actions/delete.png" onclick="hideConnexionBox()" alt="Fermer" ';
                $this->buffer .= 'title="Fermer" />';
                $frm = new form("connect", "/connect.php", true, "POST", "Connexion");
                $jsoch = "javascript:switchSelConnection(this);";
                $frm->add_select_field("domain",
                    "Connexion",
                    array("utbm" => "UTBM / Assidu",
                        "carteae" => "Carte AE",
                        "id" => "ID",
                        "autre" => "E-mail",
                        "alias" => "Alias"),
                    false,
                    "",
                    false,
                    true,
                    $jsoch);
                $frm->add_text_field("username", "Utilisateur", "prenom.nom", "", 20, true, true, null, false, 35);
                $frm->add_password_field("password", "Mot de passe", "", "", 20);
                $frm->add_checkbox("personnal_computer", "Me connecter automatiquement la prochaine fois", false);
                $frm->add_submit("connectbtn", "Se connecter");
                $this->buffer .= $frm->html_render();
                unset($frm);
                $this->buffer .= "</div>\n";
            }
        } /* ifndef MOBILE */ else {
            if ($this->user->is_valid()) {
                $this->buffer .= "<div id=\"overlay\" onclick=\"updateMenu()\" style=\"display:none\"></div>\n";
                $this->buffer .= "<div id=\"menuContent\" style=\"display:none;\">\n";

                $this->buffer .= "<a href=\"./\">Accueil</a>";
                $this->buffer .= "<a href=\"./edt.php\">Emploi du temps</a>";
                $this->buffer .= "<a href=\"./matmat.php\">Mat'Matronch</a>";
                if ($this->user->ae)
                    $this->buffer .= "<p>Compte AE : " . (sprintf("%.2f", $this->user->montant_compte / 100)) . "</p>";
                //$this->buffer .= "<a href=\"./forum2.php\">Forum</a>";

                $this->buffer .= "</div>";
            }
        }

        /* header */
        $this->buffer .= "<div id='header'>\n";
        if (!defined('NOTAE')) {
            if (!defined("MOBILE")) {
                $important = $this->get_param('box.Important');
                if (!empty($important) && $important != "<p />") {
                    $this->buffer .= "<div class=\"box\" id=\"important\">\n";
                    $this->buffer .= "<a class=\"logo\" href=\"http://ae.utbm.fr\"></a>";
                    $this->buffer .= "<div class=\"body\">\n";
                    $this->buffer .= $important . "\n";
                    $this->buffer .= "</div></div>\n";
                }
            }

            //if (isset($_SERVER['HTTPS']))
            $url = "https://ae.utbm.fr";
            //else
            //  $url = "http://ae.utbm.fr";
            if ($GLOBALS["taiste"])
                $url .= "/taiste/";

            if (defined("MOBILE"))
                $url .= 'm/';

            $this->buffer .= "<div id=\"logo\">";

            if (!defined("MOBILE"))
                $this->buffer .= "<a href=\"" . $url . "\"><img src=\"" . $wwwtopdir . "images/ae_header.png\" alt=\"Logo AE\"/></a>";
            else
                $this->buffer .= "<img src=\"" . $wwwtopdir . "images/ae_header.png\" alt=\"Logo AE\"/>";

            $this->buffer .= "</div>\n";
        }
        if (isset($this->logo))
            $this->buffer .= "<div id=\"logo\"><img src=\"" . $wwwtopdir . "images/" . $this->logo . "\" alt=\"Logo\"/></div>\n";

        $this->buffer .= "<div id='headermenu'>\n";
        if (!defined("MOBILE")) {
            if (!$this->user->is_valid()) {
                $this->buffer .= "<script type=\"text/javascript\">\n";
                $this->buffer .= "var menu_utilisateur=new Array();";
                $this->buffer .= "menu_utilisateur[0]='<a class=\"firstdropdown\" href=\"/connect.php\" onclick=\"return showConnexionBox()\">Connexion</a>';";
                $this->buffer .= "menu_utilisateur[1]='<a href=\"/password.php\">Mot de passe perdu</a>';";
                $this->buffer .= "menu_utilisateur[2]='<a href=\"/newaccount.php\">Créer un compte</a>';";
                $this->buffer .= "</script>";
                $this->buffer .= "<div id='login' onmouseover=\"dropdownmenu(this, event, menu_utilisateur)\" onmouseout=\"delayhidemenu()\">\n";
                $this->buffer .= "<a href='/connect.php'>Identification</a>\n";
            } elseif ($this->user->type == "srv") {
                $this->buffer .= "<script type=\"text/javascript\">\n";
                $this->buffer .= "var menu_utilisateur=new Array();";
                $i = 0;
                $this->buffer .= "menu_utilisateur[$i]='<a href=\"/disconnect.php\">Déconnexion</a>';";
                $this->buffer .= "</script>";
                $this->buffer .= "<div id='login' onmouseover=\"dropdownmenu(this, event, menu_utilisateur)\" onmouseout=\"delayhidemenu()\">\n";
                $this->buffer .= "<a href=\"/boutique-utbm/suivi.php\">Suivi commandes</a>\n";
            } else {
                if (!defined('NOTAE') && $this->user->ae) {
                    $this->buffer .= $this->get_comptoir();
                }
                $this->buffer .= "<script type=\"text/javascript\">\n";
                $this->buffer .= "var menu_utilisateur=new Array();";
                $i = 0;
                if (!defined('NOTAE')) {
                    $this->buffer .= "menu_utilisateur[$i]='<a class=\"firstdropdown\" href=\"/user.php?id_utilisateur=" . $this->user->id . "\">Mes informations</a>';";
                    $i++;
                    if ($this->user->ae) {
                        $this->buffer .= "menu_utilisateur[$i]='<a href=\"/user/compteae.php\">Compte AE : " . (sprintf("%.2f", $this->user->montant_compte / 100)) . " €</a>';";
                        $i++;
                    }
                    $i++;
                    $this->buffer .= "menu_utilisateur[$i]='<a href=\"/user/outils.php\">Mes outils</a>';";
                    if ($this->user->is_in_group("root")) {
                        $i++;
                        $req = new requete ($this->db,
                            "SELECT COUNT(*) AS tot FROM `ae_info_todo` " .
                            "WHERE `id_utilisateur_assignee` = '0' " .
                            "AND `status` = '0'");

                        if ($req->lines > 0) {
                            $row = $req->get_row();

                            if (isset($row['tot']) && $row['tot'] > 0)
                                $this->buffer .= "menu_utilisateur[$i]='<a href=\"/ae/infotodo.php\">Tâches équipe info (" .
                                    $row['tot'] . ")</a>';";
                            else
                                $this->buffer .= "menu_utilisateur[$i]='<a href=\"/ae/infotodo.php\">Tâches équipe info</a>';";
                        } else {
                            $this->buffer .= "menu_utilisateur[$i]='<a href=\"/ae/infotodo.php\">Tâches équipe info</a>';";
                        }
                        $i++;
                        $this->buffer .= "menu_utilisateur[$i]='<a href=\"/taiste/\">/taiste</a>';";
                    }
                    $i++;
                    if ($this->user->utbm) {
                        $this->buffer .= "menu_utilisateur[$i]='<a href=\"/trombi/index.php\">Trombinoscope</a>';";
                        $i++;
                    }
                    if ($this->user->is_in_group("jobetu_etu")) {
                        $jobuser = new jobuser_etu($this->db);
                        $jobuser->load_by_id($this->user->id);
                        $jobuser->load_annonces();
                        $this->buffer .= "menu_utilisateur[$i]='<a href=\"/jobetu/board_etu.php\">Mon compte JobEtu (" . count($jobuser->annonces) . ")</a>';";
                        unset($jobuser);
                    } elseif ($this->user->is_in_group("jobetu_client"))
                        $this->buffer .= "menu_utilisateur[$i]='<a href=\"/jobetu/board_client.php\">AE JobEtu</a>';"; else
                        $this->buffer .= "menu_utilisateur[$i]='<a href=\"/jobetu/index.php\">AE JobEtu</a>';";
                    $i++;
                }
                $this->buffer .= "menu_utilisateur[$i]='<a href=\"/disconnect.php\">Déconnexion</a>';";
                $this->buffer .= "</script>";
                $this->buffer .= "<div id='login' onmouseover=\"dropdownmenu(this, event, menu_utilisateur)\" onmouseout=\"delayhidemenu()\">\n";
                $this->buffer .= "<a href=\"/user.php?id_utilisateur=" . $this->user->id . "\">" . $this->user->prenom . " " . $this->user->nom . "</a>";
            }
        } else { /* ifndef MOBILE */
            if ($this->user->is_valid()) {
                $this->buffer .= $this->get_comptoir();
                $this->buffer .= "<a id=\"menu\" href=\"javascript:updateMenu();\">menu</a>";
                $this->buffer .= "<a href=\"disconnect.php\"><img id=\"deco\" src=\"../images/actions/stop.png\" alt=\"Déconnexion\" /></a>";
            }
        } /* ifdef MOBILE */
        $this->buffer .= "</div>\n";

        if (!defined("MOBILE")) {
            if (!defined('NOTAE')) {
                $req = new requete($this->db,
                    "SELECT `asso`.`id_asso`, " .
                    "`asso`.`nom_asso` " .
                    "FROM `asso_membre` " .
                    "INNER JOIN `asso` ON `asso`.`id_asso`=`asso_membre`.`id_asso` " .
                    "WHERE `asso_membre`.`role` > 1 AND `asso_membre`.`date_fin` IS NULL " .
                    "AND `asso_membre`.`id_utilisateur`='" . $this->user->id . "' " .
                    "AND `asso`.`id_asso` != '1' " .
                    "ORDER BY asso.`nom_asso`");
                $req2 = new requete($this->db,
                    "SELECT id_comptoir,nom_cpt " .
                    "FROM cpt_comptoir " .
                    "WHERE id_groupe IN (" . $this->user->get_groups_csv() . ") AND nom_cpt != 'test' " .
                    "AND archive != '1' " .
                    "ORDER BY nom_cpt");

                if ($req->lines > 0
                    || $req2->lines > 0
                    || $this->user->is_in_group("root")
                    || $this->user->is_in_group("moderateur_site")
                    || $this->user->is_in_group("compta_admin")
                    || $this->user->is_in_group("gestion_ae")
                    || $this->user->is_in_group("gestion_syscarteae")
                ) {
                    $this->buffer .= "<script type=\"text/javascript\">\n";
                    $this->buffer .= "var menu_assos=new Array();";
                    $i = 0;
                    $class = "class=\"firstdropdown\"";

                    /* Droits spécifiques */
                    if ($this->user->is_in_group("root")) {
                        $this->buffer .= "menu_assos[" . $i . "]='<a $class href=\"/rootplace/index.php\">Équipe informatique</a>';";
                        $i++;
                        $class = "";
                    }
                    if ($this->user->is_in_group("moderateur_site")) {
                        $this->buffer .= "menu_assos[" . $i . "]='<a $class href=\"/ae/com.php\">Équipe com</a>';";
                        $i++;
                        $class = "";
                    }
                    if ($this->user->is_in_group("compta_admin")) {
                        $this->buffer .= "menu_assos[" . $i . "]='<a $class href=\"/ae/compta.php\">Équipe trésorerie</a>';";
                        $i++;
                        $class = "";
                    }
                    if ($this->user->is_in_group("gestion_ae")) {
                        $this->buffer .= "menu_assos[" . $i . "]='<a $class href=\"/ae/\">Équipe AE</a>';";
                        $i++;
                        $class = "";
                    }
                    if ($this->user->is_in_group("gestion_syscarteae")) {
                        $this->buffer .= "menu_assos[" . $i . "]='<a $class href=\"/ae/syscarteae.php\">Carte AE</a>';";
                        $i++;
                        $class = "";
                    }

                    /* Gestion assos */
                    while (list($id, $nom) = $req->get_row()) {
                        $this->buffer .= "menu_assos[" . $i . "]='<a $class href=\"/asso/index.php?id_asso=$id\">" . str_replace("'", "\'", $nom) . "</a>';";
                        $i++;
                        $class = "";
                    }

                    /* Admins comptoirs */
                    if ($req2->lines > 4) {
                        $this->buffer .= "menu_assos[" . $i . "]='<a $class href=\"/comptoir/admin.php\">Admin : comptoirs</a>';";
                        $i++;
                        $class = "";
                    } else {
                        while (list($id, $nom) = $req2->get_row()) {
                            $this->buffer .= "menu_assos[" . $i . "]='<a $class href=\"/comptoir/admin.php?id_comptoir=$id\">Admin : " . str_replace("'", "\'", $nom) . "</a>';";
                            $i++;
                            $class = "";
                        }
                    }

                    $this->buffer .= "</script>";
                    $this->buffer .= "<div id='assos' onmouseover=\"dropdownmenu(this, event, menu_assos, '150px')\" onmouseout='delayhidemenu()'>\n";
                    $this->buffer .= "Gestion assos/clubs";
                    $this->buffer .= "</div>\n";
                }

                $this->buffer .= "<div id=\"fsearchbox\">\n";
                $this->buffer .= "<form action=\"" . $wwwtopdir . "fsearch.php\" method=\"post\">";
                $this->buffer .= "<input type=\"text\" id=\"fsearchpattern\" name=\"pattern\" onblur=\"fsearch_stop_delayed();\" onkeyup=\"fsearch_keyup(event);\" value=\"\" />\n";
                $this->buffer .= "</form>";
                $this->buffer .= "<div class=\"fend\"></div></div>\n";
            }
            $this->buffer .= "</div>\n";
            if (!defined('NOTAE'))
                $this->buffer .= "<div id=\"fsearchres\"></div>\n";
        } /* ifndef MOBILE */
        $this->buffer .= "</div>\n";
        /* fin header */

        $this->buffer .= "<div class=\"tabsv2\">\n";
        $links = null;

        foreach ($this->tab_array as $entry) {

            $this->buffer .= "<span";
            if ($this->section == $entry[0]) {
                $this->buffer .= " class=\"selected tab" . $entry[0] . "\"";
                $links = $entry[3];
            } else
                $this->buffer .= " class=\"tab" . $entry[0] . "\"";

            $this->buffer .= "><a id=\"tab_" . $entry[0] . "\" href=\"" . $wwwtopdir . $entry[1] . "\"";
            $this->buffer .= " title=\"" . $entry[2] . "\">" . $entry[2] . "</a></span>";
        }

        $this->buffer .= "</div>\n"; // /tabs

        if (!defined("MOBILE")) { /* this is too elaborate for a mobile website */
            if ($links) {
                $this->buffer .= "<div class=\"sectionlinks\">";

                foreach ($links as $entry) {
                    if (($entry[0] == "forum2/admin/") && (!$this->user->is_in_group('root') && !$this->user->is_in_group('moderateur_forum')))
                        continue;

                    if (!strncmp("http://", $entry[0], 7))
                        $this->buffer .= "<a href=\"" . $entry[0] . "\">" . $entry[1] . "</a>";
                    elseif (!empty($entry[0]))
                        $this->buffer .= "<a href=\"" . $wwwtopdir . $entry[0] . "\">" . $entry[1] . "</a>"; else
                        $this->buffer .= "<span>" . $entry[1] . "</span>";
                }

                $this->buffer .= "</div>\n";
            } else
                $this->buffer .= "<div class=\"emptysectionlinks\"></div>\n";
        } /* ifndef MOBILE */

        $this->buffer .= "<div class=\"contents\">\n";
        $idpage = "";

        $mode = $this->user->id > 0 ? "c" : "nc";

        if (!defined("MOBILE")) { /* ths is too elaborate for a mobile version */
            foreach ($this->sides as $side => $names) {
                if (count($names)) {
                    $pattern = "auto_$side";
                    foreach ($this->boxes as $name => $cts) {
                        if (!strncmp($name, $pattern, strlen($pattern))) {
                            $names = array_merge(array($name), $names);
                        }

                    }

                    $idpage .= substr($side, 0, 1);

                    if (isset($this->sides_ref[$side])) {
                        $ref = "dnds_" . $this->sides_ref[$side];
                        if (isset($_SESSION["usersession"][$ref])) {
                            $n_names = array();
                            $elts = explode(",", $_SESSION["usersession"][$ref]);
                            foreach ($elts as $elt) {
                                $name = substr($elt, 5);
                                if (in_array($name, $names))
                                    $n_names[] = $name;
                            }
                            foreach ($names as $name) {
                                if (!in_array($name, $n_names))
                                    $n_names = array_merge(array($name), $n_names);
                            }
                            $names = $n_names;
                        }
                    } else
                        $ref = null;

                    $this->buffer .= "<div id=\"" . $side . "\" class=\"clearfix\">\n";
                    foreach ($names as $name) {

                        if ($cts = $this->boxes[$name]) {
                            $this->buffer .= "<div class=\"box\" id=\"sbox_" . $name . "\">\n";
                            if ($cts->title && ($ref != null))
                                $this->buffer .= "<h1><a onmousedown=\"dnds_startdrag(event,'sbox_" . $name . "','" . $ref . "');\" class=\"dragstartzone\">" . $cts->title . "</a></h1>\n";
                            elseif ($cts->title)
                                $this->buffer .= "<h1>" . $cts->title . "</h1>\n";

                            $this->buffer .= "<div class=\"body\" id=\"sbox_body_" . $name . "\">\n";

                            $this->buffer .= $cts->html_render();

                            $this->buffer .= "</div>\n";
                            $this->buffer .= "</div>\n";
                        }

                    }
                    $this->buffer .= "</div>\n";
                }
            }
        } /* ifndef MOBILE */

        if ($idpage == "") $idpage = "n";

        $this->buffer .= "\n<!-- page -->\n";
        $this->buffer .= "<div class=\"page\" id=\"" . $idpage . "\">\n";

        $i = 0;
        foreach ($this->contents as $cts) {
            $cssclass = "article";

            if (!is_null($cts->cssclass))
                $cssclass = $cts->cssclass;

            $i++;


            $this->buffer .= "<div class=\"" . $cssclass . "\"";
            if ($cts->divid)
                $this->buffer .= " id=\"" . $cts->divid . "\"";
            else
                $this->buffer .= " id=\"cts" . $i . "\"";
            $this->buffer .= ">\n";

            if ($cts->toolbox) {
                $this->buffer .= "<div class=\"toolbox\">\n";
                $this->buffer .= $cts->toolbox->html_render() . "\n";
                $this->buffer .= "</div>\n";
            }

            if ($cts->title)
                $this->buffer .= "<h1>" . $cts->title . "</h1>\n";

            $this->buffer .= $cts->html_render();

            $this->buffer .= "</div>\n";
        }

        $this->buffer .= "</div>\n"; // /page
        $this->buffer .= "<!-- end of page -->\n\n";
        $this->buffer .= "</div>\n"; // /contents
        $this->buffer .= "<div id=\"contentsend\">&nbsp;</div>\n";
        $this->buffer .= "<div id=\"endsite\">";
        $this->buffer .= "<div id=\"endsitelinks\">";
        if (!defined('NOTAE')) {
            if (!defined("MOBILE")) {
                $this->buffer .= "<a href=\"" . $wwwtopdir . "article.php?name=contacts\">CONTACTS</a> ";
                $this->buffer .= "<a href=\"" . $wwwtopdir . "article.php?name=legals\">MENTIONS LÉGALES</a> ";
                $this->buffer .= "<a href=\"" . $wwwtopdir . "copyright_agent.php\">PROPRIÉTÉ INTELLECTUELLE</a>";
                $this->buffer .= "<a href=\"" . $wwwtopdir . "article.php?name=docs:index\">AIDE ET DOCUMENTATION</a> ";
                $this->buffer .= "<a href=\"" . $wwwtopdir . "article.php?name=rd\">R&amp;D</a> ";
            } else { /* TODO */
            }
        } elseif (isset($this->footer))
            $this->buffer = $this->footer;
        $this->buffer .= "</div>"; // /endsitelinks
        $this->buffer .= "</div>"; // /endsite
        $this->buffer .= "</div>\n"; // /site

        if ($this->get_param("backup_server", true)) {
            $this->buffer .= "<div id=\"topalert\">";
            $this->buffer .= "<img width=\"16\" height=\"16\" src=\"" . $wwwtopdir . "themes/default/images/exclamation.png\" />";
            $this->buffer .= "Le système fonctionne actuellement sur le serveur de secours, " .
                "veuillez limiter vos actions au strict minimum.";
            $this->buffer .= "</div>";
        } elseif ($this->get_param("warning_enabled", true)) {
            $this->buffer .= "<div id=\"topalert\">";
            $this->buffer .= "<img width=\"16\" height=\"16\" src=\"" . $wwwtopdir . "themes/default/images/exclamation.png\" />";
            $this->buffer .= $this->get_param("warning_message");
            $this->buffer .= "</div>";
        }
        $this->buffer .= "</body>\n";
        $this->buffer .= "</html>\n";

        /**
         * Reduce page's weight for mobile version
         * May we can always do it ?
         *
         * For taiste version, do not apply !
         *
         * TODO : include css files in buffer and reduce weight too for mobile version
         */
        if (defined("MOBILE") && !$GLOBALS["taiste"]) $this->buffer = strtr($this->buffer, array("\n" => "", "\r" => ""));

        echo $this->buffer;
        $timing["render"] += microtime(true);
        $timing["all"] += microtime(true);
        echo "<!-- ";
        if ($GLOBALS["taiste"]) {
            print_r($timing);
            echo "\non est en taiste\n";
        }
        echo " -->";
    }

    /**
     * Rendu de la page en mode popup (sans header, sans boites laterales)
     */
    function popup_end_page()
    {
        global $fstopdir, $wwwtopdir;

        header("Content-Type: text/html; charset=utf-8");

        //$this->buffer .= "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01//EN\" \"http://www.w3.org/TR/html4/strict.dtd\">";

        $this->buffer .= "<html xmlns=\"http://www.w3.org/1999/xhtml\" xmlns:v=\"urn:schemas-microsoft-com:vml\">\n";
        $this->buffer .= "<head>\n";
        $this->buffer .= "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\" />\n";
        $this->buffer .= "<title>" . htmlentities($this->title, ENT_COMPAT, "UTF-8") . " - association des etudiants de l'utbm</title>\n";
        $this->buffer .= "<link rel=\"stylesheet\" type=\"text/css\" href=\"" . $wwwtopdir . "themes/default/css/site.css?" . filemtime($fstopdir . "themes/default/css/site.css") . "\" title=\"AE2-NEW2\" />\n";
        $this->buffer .= "<link rel=\"stylesheet\" type=\"text/css\" href=\"" . $wwwtopdir . "css/popup.css?" . filemtime($fstopdir . "css/popup.css") . "\" />\n";
        foreach ($this->extracss as $url)
            $this->buffer .= "<link rel=\"stylesheet\" type=\"text/css\" href=\"" . htmlentities($wwwtopdir . $url, ENT_COMPAT, "UTF-8") . "\" />\n";

        foreach ($this->rss as $title => $url)
            $this->buffer .= "<link rel=\"alternate\" type=\"application/rss+xml\" title=\"" . htmlentities($title, ENT_COMPAT, "UTF-8") . "\" href=\"" . htmlentities($url, ENT_COMPAT, "UTF-8") . "\" />";

        $this->buffer .= "<link rel=\"SHORTCUT ICON\" href=\"" . $wwwtopdir . "favicon.ico\" />\n";
        $this->buffer .= "<script type=\"text/javascript\">var site_topdir='" . $wwwtopdir . "';</script>\n";
        $this->buffer .= "<script type=\"text/javascript\" src=\"" . $wwwtopdir . "js/site.js\" async></script>\n";
        $this->buffer .= "<script type=\"text/javascript\" src=\"" . $wwwtopdir . "js/ajax.js\" async></script>\n";
        $this->buffer .= "<script type=\"text/javascript\" src=\"" . $wwwtopdir . "js/dnds.js\" async></script>\n";

        foreach ($this->extrajs as $url)
            $this->buffer .= "<script defer async type=\"text/javascript\" src=\"" . htmlentities($wwwtopdir . $url, ENT_QUOTES, "UTF-8") . "\"></script>\n";

        $this->buffer .= "</head>\n";

        $this->buffer .= "<body>\n";
        /* Generate the logo */

        $this->buffer .= "<div id=\"popup\">";

        $i = 0;
        foreach ($this->contents as $cts) {
            $cssclass = "article";

            if (!is_null($cts->cssclass))
                $cssclass = $cts->cssclass;

            $i++;
            $this->buffer .= "<div class=\"" . $cssclass . "\"";
            if ($cts->divid)
                $this->buffer .= " id=\"" . $cts->divid . "\"";
            else
                $this->buffer .= " id=\"cts" . $i . "\"";
            $this->buffer .= ">\n";

            if ($cts->toolbox) {
                $this->buffer .= "<div class=\"toolbox\">\n";
                $this->buffer .= $cts->toolbox->html_render() . "\n";
                $this->buffer .= "</div>\n";
            }

            if ($cts->title)
                $this->buffer .= "<h1>" . $cts->title . "</h1>\n";

            $this->buffer .= $cts->html_render();
            $this->buffer .= "</div>\n";
        }

        $this->buffer .= "</div>\n";
        $this->buffer .= "</body>\n";
        $this->buffer .= "</html>\n";
        echo $this->buffer;
    }

    /** Charge tous les paramètres du site.
     * ATTENTION: ceci est UNIQUEMENT concu pour stocker des paramètres.
     * @private
     */
    function load_params()
    {
        $this->params = array();

        $req = new requete($this->db, "SELECT `nom_param`,`valeur_param` " .
            "FROM `site_parametres`");

        while (list($id, $name) = $req->get_row())
            $this->params[$id] = $name;

        $this->params["backup_server"] = serialize($_SERVER["BACKUP_AE_SERVER"]);
    }

    /**
     * Obtient un paramètre du site.
     * @param $name Nom du paramètre
     * @param $value $default par défaut retrouné si il n'est pas définit
     */
    function get_param($name, $default = null)
    {
        if (!$this->params)
            $this->load_params();

        if (!isset($this->params[$name]))
            return $default;

        return unserialize($this->params[$name]);
    }


    /**
     * Définit un paramètre du site.
     * @param $name Nom du paramètre
     * @param $value Valeur du paramètre.
     */
    function set_param($name, $value)
    {
        if (!$this->params)
            $this->load_params();

        $value = serialize($value);

        if (!isset($this->params[$name])) {
            $sql = new insert($this->dbrw, "site_parametres",
                array(
                    "nom_param" => $name,
                    "valeur_param" => $value
                ));
            $this->params[$name] = $value;
        } elseif ($this->params[$name] !== $value) {
            $sql = new update($this->dbrw, "site_parametres",
                array("valeur_param" => $value),
                array("nom_param" => $name)); //$this->buffer .= " onmouseover=\"tabsection('".$entry[0]."', 'hoversectionlinks');\"";
            $this->params[$name] = $value;
        }
    }


    /**
     * Vérifie que l'utilisateur est vraiment sûre de procéder à une opération.
     * Certifié "boulet proof(tm)".
     * Remarque: ne fonctionne pas dans le cas de passage de tableaux en GET/POST
     * @param $section Section de la page de confirmation
     * @param $message Message à afficher
     * @param $uid identifiant unique de la question
     * @param $level niveau d'incidence (0:pas grave, 1:peu risqué, 2:très risqué, 3:risque la colère des administrateurs)
     */
    function is_sure($section, $message, $uid = null, $level = 0)
    {
        if (isset($_POST["___i_am_really_sure"])) {
            if ($GLOBALS["svalid_call"])
                return true;
            return false;
        } elseif (isset($_POST["___finally_i_want_to_cancel"]))
            return false;

        if (!$uid) $uid = $section . md5($message);

        $this->start_page($section, "Êtes vous sûr ?");

        $cts = new contents("Confirmation");

        if ($level == 2)
            $cts->add_paragraph("ATTENTION", "huge");

        $cts->add_paragraph($message);

        if ($level == 2)
            $cts->add_paragraph("Cette opération <b>pourrait avoir de lourdes conséquences</b> sur le <b>bon fonctionnement des services</b> si elle été appliquée sur un élément critique. <b>Contactez un administrateur en cas de doute</b>.");

        $cts->add_paragraph("Êtes vous sûr ?");
        if ($level == 3) {
            $phrase_magique = 'oui je suis sur de vouloir faire ça';
            $cts->add_paragraph('Tapez dans le champ correspondant et en toutes lettres la phrase "' . str_replace(' ', '&nbsp;', $phrase_magique) . '"');
        }

        $frm = new form("suretobesurefor" . $uid, "?");
        $frm->allow_only_one_usage();

        foreach ($_POST as $key => $val)
            if ($key != "magicform") {
                if ($key == "__script__")
                    $frm->add_hidden($key, htmlspecialchars($val));
                else if (is_array($val)) {
                    foreach ($val as $k => $v)
                        $frm->add_hidden($key . '[' . $k . ']', $v);
                } else
                    $frm->add_hidden($key, $val);
            }
        foreach ($_GET as $key => $val)
            if ($key != "magicform") {
                if (is_array($val)) {
                    foreach ($val as $k => $v)
                        $frm->add_hidden($key . '[' . $k . ']', $v);
                } else
                    $frm->add_hidden($key, $val);
            }

        if ($level == 3) {
            $_uid = gen_uid();
            $frm->add_text_field('____really_sure__' . $_uid, 'Tapez la phrase magique :', '', true, 50);
        }

        $frm->add_submit("___i_am_really_sure", "OUI");
        $frm->add_submit("___finally_i_want_to_cancel", "NON");

        $cts->add($frm);
        if ($level == 3)
            $cts->puts('<script type="text/javascript">
var txt = document.getElementsByName("____really_sure__' . $_uid . '")[0];
var sub = document.getElementById("___i_am_really_sure");
sub.disabled = true;
txt.onkeyup = function (event){
  if ( event != null )
  {
    var sub = document.getElementById("___i_am_really_sure");
    var txt = document.getElementsByName("____really_sure__' . $_uid . '")[0];
    if (txt.value == "' . $phrase_magique . '")
      sub.disabled = false;
    else
      sub.disabled = true;
  }
}
</script>');

        $this->add_contents($cts);

        $this->end_page();
        exit();
    }

    function set_meta_information($keywords, $description)
    {
        $this->meta_keywords = $keywords;
        $this->meta_description = $description;
    }

    function add_alternate($type, $title, $href)
    {
        $this->alternate[] = array($type, $title, $href);
    }

    function add_alternate_geopoint(&$geopoint)
    {
        global $wwwtopdir;
        $this->add_alternate("application/vnd.google-earth.kml+xml", "KML", $wwwtopdir . "loc.php?id_geopoint=" . $geopoint->id . "&action=kml");
    }


}

?>
