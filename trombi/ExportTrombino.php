<?php
$topdir = "../";

include($topdir . "include/site.inc.php");
require_once($topdir . "include/cts/sqltable.inc.php");
require_once($topdir . "include/entities/asso.inc.php");
require_once($topdir . "include/cts/user.inc.php");
require_once($topdir . "sas2/include/photo.inc.php");
require_once($topdir . "include/cts/gallery.inc.php");
require_once($topdir . "include/cts/special.inc.php");
require_once($topdir . "include/globals.inc.php");
require_once($topdir . "include/entities/ville.inc.php");
require_once($topdir . "include/entities/pays.inc.php");
require_once($topdir . "include/entities/asso.inc.php");

require_once($topdir . "include/graph.inc.php");
require_once($topdir . "include/cts/imgcarto.inc.php");
require_once($topdir . "include/pgsqlae.inc.php");
require_once("include/entities/commentaire.inc.php");
require_once("include/cts/commentaire.inc.php");

$site = new site();
if ($site->user->is_in_group("root")) {
    if ($_GET["promo"]=="" || $_GET["promo"]==null){
        echo "usage : promo=10";
        return;
    }
    header ("Content-Type:text/xml");
    $result = "<xml>";
    try {
        $req = new requete($site->db, "SELECT photo,famille,infos_personnelles,associatif,commentaires,utilisateurs.id_utilisateur,utilisateurs.nom_utl, utilisateurs.prenom_utl, utl_etu_utbm.surnom_utbm, utilisateurs.email_utl, utilisateurs.tel_portable_utl FROM `utilisateurs` join utl_trombi on utilisateurs.id_utilisateur = utl_trombi.id_utilisateur JOIN utl_etu_utbm ON utilisateurs.id_utilisateur = utl_etu_utbm.id_utilisateur  WHERE autorisation !=1 AND utl_etu_utbm.promo_utbm =".$_GET["promo"]);
    } catch (Exception $e) {
        echo "main request doesn't work" . $e;
    }
    if ($req->lines > 0) {
        while ($row = $req->get_row()) {
            try {
                $id_user = $row["id_utilisateur"];
                $result .= "<utilisateur>";
                $result .= "<nom>" . $row["nom_utl"] . "</nom>";
                $result .= "<prenom>" . $row["prenom_utl"] . "</prenom>";
                if($row["infos_personnelles"]==1){
                    if($row["surnom_utbm"]!=""){
                        $result .= "<surnom>" . $row["surnom_utbm"] . "</surnom>";
                    }
                $result .= "<email>" . $row["email_utl"] . "</email>";
                    if( $row["tel_portable_utl"]){
                $result .= "<tel>" . $row["tel_portable_utl"] . "</tel>";
                    }
                }
            } catch (Exception $e) {
                echo "unable to get basic info " . $e;
            }
            if($row["famille"]==1){
            try {
                $req_fillots = new requete($site->db, "SELECT nom_utl,prenom_utl,  utl_etu_utbm.surnom_utbm from parrains join utl_etu_utbm on parrains.id_utilisateur_fillot=utl_etu_utbm.id_utilisateur join utilisateurs on parrains.id_utilisateur_fillot=utilisateurs.id_utilisateur where parrains.id_utilisateur = " .   $id_user. " ");
                while ($row_fillots = $req_fillots->get_row()) {
                    if($row_fillots["surnom_utbm"] !=""){
                        $result .= "<fillot>" . $row_fillots["surnom_utbm"] . "</fillot>";
                    }else{
                        $result .= "<fillot>" . $row_fillots["nom_utl"] ." ".$row_parrains["prenom_utl"]. "</fillot>";

                    }                }
                $req_parrains = new requete($site->db, "SELECT nom_utl,prenom_utl, utl_etu_utbm.surnom_utbm from parrains join utl_etu_utbm on parrains.id_utilisateur=utl_etu_utbm.id_utilisateur join utilisateurs on parrains.id_utilisateur=utilisateurs.id_utilisateur where parrains.id_utilisateur_fillot = " .  $id_user . "");
                while ($row_parrains = $req_parrains->get_row()) {
                    if($row_parrains["surnom_utbm"] !=""){
                        $result .= "<parrain>" . $row_parrains["surnom_utbm"] . "</parrain>";
                    }else{
                        $result .= "<parrain>" . $row_parrains["nom_utl"] ." ".$row_parrains["prenom_utl"]. "</parrain>";

                    }
                }
            } catch (Exception $e) {
                echo "unable to get parrain / fillot " . $e;
            }
            }
            if($row["associatif"]==1){
            try {
                $req_assoc = new requete($site->db,
                    "SELECT `asso`.`nom_asso`,`asso_membre`.`desc_role`, role, " .
                    "IF(`asso`.`id_asso_parent` IS NULL,`asso_membre`.`role`+100,`asso_membre`.`role`) AS `role`, " .
                    "`asso_membre`.`date_debut`, `asso_membre`.`desc_role`, " .
                    "CONCAT(`asso`.`id_asso`,',',`asso_membre`.`date_debut`) as `id_membership` " .
                    "FROM `asso_membre` " .
                    "INNER JOIN `asso` ON `asso`.`id_asso`=`asso_membre`.`id_asso` " .
                    "WHERE asso_membre.id_utilisateur=" . $id_user);

                while ($row_assoc = $req_assoc->get_row()) {
                    $result .= "<asso><nom>" . $row_assoc["nom_asso"] . "</nom>";
                    if ($row_assoc['desc_role']==""){
                        if ((int)$row_assoc['role']>100){
                            $roleXML=$GLOBALS['ROLEASSO100'][(int)$row_assoc['role']];
                        }
                        else{
                            $roleXML=$GLOBALS['ROLEASSO'][(int)$row_assoc['role']];
                        }
                    }
                    else{
                        $roleXML=$row_assoc['desc_role'];
                    }
                    $result .= "<role>" . htmlspecialchars($roleXML) . "</role></asso>";
                }
            } catch (Exception $e) {
                echo "unable to fetch assos" . $e;
            }
            }
            if($row["commentaire"]==1){
            try {
                $req_comment = new requete($site->db, "select commentaire, utl_etu_utbm.surnom_utbm from trombi_commentaire join utl_etu_utbm on trombi_commentaire.id_commentateur=utl_etu_utbm.id_utilisateur where id_commente = " . $id_user);
                while ($row_comment = $req_comment->get_row()) {
                    $result .= "<commentaire><nom>" . $row_comment["surnom_utbm"] . "</nom>";
                    $result .= "<contenu>" . $row_comment['commentaire'] . "</contenu></commentaire>";
                }
            } catch (Exception $e) {
                echo "unable to get comment " . $e;
            }
            }
            if($row["photo"]==1){
                $result.="<photo>oui</photo>";
            }else{
                $result.="<photo>non</photo>";
            }
            $result .= "</utilisateur>";

        }
        $result.="</xml>";
        echo $result;
    }

}