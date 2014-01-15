-- phpMyAdmin SQL Dump
-- version 2.6.2-Debian-3sarge3
-- http://www.phpmyadmin.net
-- 
-- Host: localhost
-- Generation Time: Nov 09, 2009 at 02:15 PM
-- Server version: 5.0.32
-- PHP Version: 5.2.0-8+etch15
-- 
-- Database: `ae2`
-- 

-- --------------------------------------------------------

-- 
-- Table structure for table `ae_carte`
-- 

CREATE TABLE `ae_carte` (
  `id_carte_ae` int(11) NOT NULL auto_increment,
  `id_cotisation` int(11) NOT NULL default '0',
  `etat_vie_carte_ae` int(2) default '0',
  `date_expiration` date NOT NULL default '0000-00-00',
  `cle_carteae` char(1) default NULL,
  PRIMARY KEY  (`id_carte_ae`),
  KEY `fk_ae_carte_ae_cotisations` (`id_cotisation`)
) ENGINE=MyISAM AUTO_INCREMENT=5115 DEFAULT CHARSET=latin1 PACK_KEYS=0 COMMENT='carte ae' AUTO_INCREMENT=5115 ;

-- --------------------------------------------------------

-- 
-- Table structure for table `groupe`
-- 

CREATE TABLE `groupe` (
  `id_groupe` int(11) NOT NULL auto_increment,
  `nom_groupe` varchar(40) NOT NULL default '',
  `description_groupe` text NOT NULL,
  PRIMARY KEY  (`id_groupe`)
) ENGINE=MyISAM AUTO_INCREMENT=52 DEFAULT CHARSET=latin1 AUTO_INCREMENT=52 ;


-- --------------------------------------------------------

-- 
-- Table structure for table `utl_groupe`
-- 

CREATE TABLE `utl_groupe` (
  `id_groupe` int(11) NOT NULL default '0',
  `id_utilisateur` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id_groupe`,`id_utilisateur`),
  KEY `fk_utl_groupe_utilisateurs` (`id_utilisateur`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;


-- --------------------------------------------------------

-- 
-- Table structure for table `utilisateurs`
-- 

CREATE TABLE `utilisateurs` (
  `id_utilisateur` int(11) NOT NULL auto_increment,
  `type_utl` enum('std','srv') NOT NULL default 'std',
  `nom_utl` varchar(64) NOT NULL default '',
  `prenom_utl` varchar(64) NOT NULL default '',
  `email_utl` varchar(128) NOT NULL default '',
  `pass_utl` varchar(34) default NULL,
  `hash_utl` varchar(32) NOT NULL default '',
  `sexe_utl` enum('1','2') default '1',
  `date_naissance_utl` date default NULL,
  `addresse_utl` varchar(128) default NULL,
  `tel_maison_utl` varchar(32) default NULL,
  `tel_portable_utl` varchar(32) default NULL,
  `alias_utl` varchar(128) default NULL,
  `utbm_utl` enum('0','1') default '0',
  `etudiant_utl` enum('0','1') default '0',
  `ancien_etudiant_utl` enum('0','1') default '0',
  `ae_utl` enum('0','1') default '0',
  `modere_utl` enum('0','1') default '0',
  `droit_image_utl` enum('0','1') default '0',
  `montant_compte` int(32) default NULL,
  `site_web` varchar(92) default NULL,
  `date_maj_utl` datetime default NULL,
  `derniere_visite_utl` datetime default NULL,
  `publique_utl` enum('0','1') NOT NULL default '1',
  `publique_mmtpapier_utl` enum('0','1') NOT NULL default '1',
  `tovalid_utl` enum('none','email','utbmemail','utbm') default 'none',
  `id_ville` int(11) default NULL,
  `id_pays` int(11) default NULL,
  `tout_lu_avant_utl` datetime default NULL,
  `signature_utl` text,
  `serviceident` varchar(255) default NULL,
  `id_licence_default_sas` int(11) default NULL,
  PRIMARY KEY  (`id_utilisateur`),
  KEY `email_utl` (`email_utl`),
  KEY `nom_utl` (`nom_utl`,`prenom_utl`),
  KEY `alias_utl` (`alias_utl`),
  KEY `id_ville` (`id_ville`),
  KEY `id_pays` (`id_pays`),
  KEY `date_naissance_utl` (`date_naissance_utl`),
  KEY `ae_utl` (`ae_utl`),
  KEY `etudiant_utl` (`etudiant_utl`),
  KEY `utbm_utl` (`utbm_utl`)
) ENGINE=MyISAM AUTO_INCREMENT=6578 DEFAULT CHARSET=latin1 AUTO_INCREMENT=6578 ;

-- --------------------------------------------------------

-- 
-- Table structure for table `ae_cotisations`
-- 

CREATE TABLE `ae_cotisations` (
  `id_cotisation` int(11) NOT NULL auto_increment,
  `id_utilisateur` int(11) NOT NULL default '0',
  `date_cotis` datetime NOT NULL default '0000-00-00 00:00:00',
  `date_fin_cotis` date NOT NULL default '0000-00-00',
  `a_pris_cadeau` tinyint(1) NOT NULL default '0',
  `a_pris_carte` tinyint(1) NOT NULL default '0',
  `mode_paiement_cotis` tinyint(1) NOT NULL default '0',
  `prix_paye_cotis` int(4) default '0',
  PRIMARY KEY  (`id_cotisation`),
  KEY `fk_ae_cotisations_utilisateurs` (`id_utilisateur`),
  KEY `date_fin_cotis` (`date_fin_cotis`)
) ENGINE=MyISAM AUTO_INCREMENT=7342 DEFAULT CHARSET=latin1 COMMENT='historisation des cotisations' AUTO_INCREMENT=7342 ;

-- --------------------------------------------------------

-- 
-- Table structure for table `aecms_blog`
-- 

CREATE TABLE `aecms_blog` (
  `id_blog` int(11) NOT NULL auto_increment,
  `id_asso` int(10) NOT NULL,
  `sub_id` varchar(10) default NULL,
  PRIMARY KEY  (`id_blog`),
  UNIQUE KEY `onecmsoneblog` (`id_asso`,`sub_id`)
) ENGINE=MyISAM AUTO_INCREMENT=6 DEFAULT CHARSET=latin1 AUTO_INCREMENT=6 ;

-- --------------------------------------------------------

-- 
-- Table structure for table `aecms_blog_cat`
-- 

CREATE TABLE `aecms_blog_cat` (
  `id_cat` int(11) NOT NULL auto_increment,
  `id_blog` int(11) NOT NULL,
  `cat_name` varchar(50) NOT NULL,
  PRIMARY KEY  (`id_cat`),
  KEY `id_blog` (`id_blog`)
) ENGINE=MyISAM AUTO_INCREMENT=32 DEFAULT CHARSET=latin1 AUTO_INCREMENT=32 ;

-- --------------------------------------------------------

-- 
-- Table structure for table `aecms_blog_entries`
-- 

CREATE TABLE `aecms_blog_entries` (
  `id_entry` int(11) NOT NULL auto_increment,
  `id_blog` int(11) NOT NULL,
  `id_cat` int(11) default NULL,
  `id_utilisateur` int(11) default NULL,
  `date` datetime NOT NULL default '0000-00-00 00:00:00',
  `pub` enum('y','n') NOT NULL default 'n',
  `titre` varchar(100) NOT NULL,
  `intro` text NOT NULL,
  `contenu` text NOT NULL,
  PRIMARY KEY  (`id_entry`),
  KEY `id_blog` (`id_blog`),
  KEY `id_cat` (`id_cat`)
) ENGINE=MyISAM AUTO_INCREMENT=201 DEFAULT CHARSET=latin1 AUTO_INCREMENT=201 ;

-- --------------------------------------------------------

-- 
-- Table structure for table `aecms_blog_entries_comments`
-- 

CREATE TABLE `aecms_blog_entries_comments` (
  `id_comment` int(11) NOT NULL auto_increment,
  `id_blog` int(11) NOT NULL,
  `id_entry` int(11) NOT NULL,
  `date` datetime NOT NULL,
  `nom` varchar(200) NOT NULL,
  `comment` text NOT NULL,
  PRIMARY KEY  (`id_comment`),
  KEY `id_blog` (`id_blog`),
  KEY `id_entry` (`id_entry`)
) ENGINE=MyISAM AUTO_INCREMENT=225 DEFAULT CHARSET=latin1 AUTO_INCREMENT=225 ;

-- --------------------------------------------------------

-- 
-- Table structure for table `aecms_blog_writers`
-- 

CREATE TABLE `aecms_blog_writers` (
  `id_blog` int(11) NOT NULL,
  `id_utilisateur` int(11) NOT NULL,
  PRIMARY KEY  (`id_blog`,`id_utilisateur`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- 
-- Table structure for table `aecms_stats`
-- 

CREATE TABLE `aecms_stats` (
  `id_asso` int(10) NOT NULL,
  `sub_id` varchar(10) NOT NULL,
  `hour` int(2) NOT NULL,
  `day` int(1) NOT NULL,
  `week` int(2) NOT NULL,
  `year` int(4) NOT NULL,
  `hits` int(11) NOT NULL,
  PRIMARY KEY  (`id_asso`,`sub_id`,`hour`,`day`,`week`,`year`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- 
-- Table structure for table `asso`
-- 

CREATE TABLE `asso` (
  `id_asso` int(11) NOT NULL auto_increment,
  `id_asso_parent` int(11) default NULL,
  `nom_asso` varchar(128) NOT NULL default '',
  `nom_unix_asso` varchar(128) NOT NULL default '',
  `adresse_postale` text,
  `email_asso` varchar(128) default NULL,
  `siteweb_asso` varchar(128) default NULL,
  `login_email` varchar(64) default NULL,
  `passwd_email` varchar(64) default NULL,
  `ajout` timestamp NULL default CURRENT_TIMESTAMP,
  `distinct_benevole_asso` varchar(8) NOT NULL,
  `hidden` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`id_asso`),
  KEY `fk_asso_asso` (`id_asso_parent`)
) ENGINE=MyISAM AUTO_INCREMENT=113 DEFAULT CHARSET=latin1 AUTO_INCREMENT=113 ;

-- --------------------------------------------------------

-- 
-- Table structure for table `asso_membre`
-- 

CREATE TABLE `asso_membre` (
  `id_asso` int(11) NOT NULL default '0',
  `id_utilisateur` int(11) NOT NULL default '0',
  `date_debut` date NOT NULL default '0000-00-00',
  `date_fin` date default NULL,
  `role` int(2) NOT NULL default '0',
  `desc_role` varchar(128) default NULL,
  PRIMARY KEY  (`id_asso`,`id_utilisateur`,`date_debut`),
  KEY `fk_asso_membre_utilisateurs` (`id_utilisateur`),
  KEY `date_fin` (`date_fin`),
  KEY `role` (`role`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- 
-- Table structure for table `asso_tag`
-- 

CREATE TABLE `asso_tag` (
  `id_asso` int(11) NOT NULL,
  `id_tag` int(11) NOT NULL,
  PRIMARY KEY  (`id_asso`,`id_tag`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- 
-- Table structure for table `auth_asso`
-- 

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `ae2`.`auth_asso` AS select `ae2`.`utilisateurs`.`alias_utl` AS `username`,`ae2`.`asso`.`nom_unix_asso` AS `groups` from ((`ae2`.`asso_membre` join `ae2`.`utilisateurs` on((`ae2`.`asso_membre`.`id_utilisateur` = `ae2`.`utilisateurs`.`id_utilisateur`))) join `ae2`.`asso` on((`ae2`.`asso_membre`.`id_asso` = `ae2`.`asso`.`id_asso`))) where (isnull(`ae2`.`asso_membre`.`date_fin`) and (`ae2`.`asso_membre`.`role` >= _latin1'2') and (`ae2`.`utilisateurs`.`alias_utl` is not null));

-- --------------------------------------------------------

-- 
-- Table structure for table `auth_group`
-- 

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `ae2`.`auth_group` AS select `ae2`.`utilisateurs`.`alias_utl` AS `username`,`ae2`.`utilisateurs`.`pass_utl` AS `passwd`,`ae2`.`groupe`.`nom_groupe` AS `groups` from ((`ae2`.`utl_groupe` join `ae2`.`utilisateurs` on((`ae2`.`utl_groupe`.`id_utilisateur` = `ae2`.`utilisateurs`.`id_utilisateur`))) join `ae2`.`groupe` on((`ae2`.`utl_groupe`.`id_groupe` = `ae2`.`groupe`.`id_groupe`))) where (`ae2`.`utilisateurs`.`alias_utl` is not null);

-- --------------------------------------------------------

-- 
-- Table structure for table `svn_depot`
-- 

CREATE TABLE `svn_depot` (
  `id_depot` int(6) NOT NULL auto_increment,
  `nom` varchar(16) NOT NULL,
  `type` enum('public','private','aeinfo') NOT NULL default 'public',
  `id_asso` int(11) default NULL,
  PRIMARY KEY  (`id_depot`),
  UNIQUE KEY `groupetype` (`nom`,`type`)
) ENGINE=MyISAM AUTO_INCREMENT=22 DEFAULT CHARSET=latin1 AUTO_INCREMENT=22 ;


-- --------------------------------------------------------

-- 
-- Table structure for table `svn_member_depot`
-- 

CREATE TABLE `svn_member_depot` (
  `id_depot` int(6) NOT NULL,
  `id_utilisateur` int(11) NOT NULL,
  `right` enum('','r','rw') NOT NULL,
  PRIMARY KEY  (`id_depot`,`id_utilisateur`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- 
-- Table structure for table `auth_svn`
-- 

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `ae2`.`auth_svn` AS select `ae2`.`utilisateurs`.`alias_utl` AS `username`,`ae2`.`svn_depot`.`nom` AS `groups` from ((`ae2`.`svn_depot` join `ae2`.`asso_membre` on((`ae2`.`svn_depot`.`id_asso` = `ae2`.`asso_membre`.`id_asso`))) join `ae2`.`utilisateurs` on((`ae2`.`asso_membre`.`id_utilisateur` = `ae2`.`utilisateurs`.`id_utilisateur`))) where (isnull(`ae2`.`asso_membre`.`date_fin`) and (`ae2`.`asso_membre`.`role` >= _latin1'1') and (`ae2`.`utilisateurs`.`alias_utl` is not null)) union select `ae2`.`utilisateurs`.`alias_utl` AS `username`,`ae2`.`svn_depot`.`nom` AS `groups` from ((`ae2`.`svn_member_depot` join `ae2`.`utilisateurs` on((`ae2`.`svn_member_depot`.`id_utilisateur` = `ae2`.`utilisateurs`.`id_utilisateur`))) join `ae2`.`svn_depot` on((`ae2`.`svn_member_depot`.`id_depot` = `ae2`.`svn_depot`.`id_depot`)));

-- --------------------------------------------------------

-- 
-- Table structure for table `auth_svn_rw`
-- 

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `ae2`.`auth_svn_rw` AS select `ae2`.`utilisateurs`.`alias_utl` AS `username`,`ae2`.`svn_depot`.`nom` AS `groups` from ((`ae2`.`svn_depot` join `ae2`.`asso_membre` on((`ae2`.`svn_depot`.`id_asso` = `ae2`.`asso_membre`.`id_asso`))) join `ae2`.`utilisateurs` on((`ae2`.`asso_membre`.`id_utilisateur` = `ae2`.`utilisateurs`.`id_utilisateur`))) where (isnull(`ae2`.`asso_membre`.`date_fin`) and (`ae2`.`asso_membre`.`role` >= _latin1'1') and (`ae2`.`utilisateurs`.`alias_utl` is not null)) union select `ae2`.`utilisateurs`.`alias_utl` AS `username`,`ae2`.`svn_depot`.`nom` AS `groups` from ((`ae2`.`svn_member_depot` join `ae2`.`utilisateurs` on((`ae2`.`svn_member_depot`.`id_utilisateur` = `ae2`.`utilisateurs`.`id_utilisateur`))) join `ae2`.`svn_depot` on((`ae2`.`svn_member_depot`.`id_depot` = `ae2`.`svn_depot`.`id_depot`))) where (`ae2`.`svn_member_depot`.`right` = _latin1'rw');

-- --------------------------------------------------------

-- 
-- Table structure for table `auth_user`
-- 

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `ae2`.`auth_user` AS select `ae2`.`utilisateurs`.`alias_utl` AS `username`,`ae2`.`utilisateurs`.`pass_utl` AS `passwd` from `ae2`.`utilisateurs` where (`ae2`.`utilisateurs`.`alias_utl` is not null);

-- --------------------------------------------------------

-- 
-- Table structure for table `bk_auteur`
-- 

CREATE TABLE `bk_auteur` (
  `id_auteur` int(11) NOT NULL auto_increment,
  `nom_auteur` varchar(128) NOT NULL default '',
  PRIMARY KEY  (`id_auteur`)
) ENGINE=MyISAM AUTO_INCREMENT=471 DEFAULT CHARSET=latin1 AUTO_INCREMENT=471 ;

-- --------------------------------------------------------

-- 
-- Table structure for table `bk_book`
-- 

CREATE TABLE `bk_book` (
  `id_objet` int(11) NOT NULL default '0',
  `id_editeur` int(11) NOT NULL default '0',
  `id_serie` int(11) default NULL,
  `num_livre` int(11) default NULL,
  `isbn_livre` varchar(13) default NULL,
  PRIMARY KEY  (`id_objet`),
  KEY `id_editeur` (`id_editeur`),
  KEY `id_serie` (`id_serie`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- 
-- Table structure for table `bk_editeur`
-- 

CREATE TABLE `bk_editeur` (
  `id_editeur` int(11) NOT NULL auto_increment,
  `nom_editeur` varchar(128) NOT NULL default '',
  PRIMARY KEY  (`id_editeur`)
) ENGINE=MyISAM AUTO_INCREMENT=51 DEFAULT CHARSET=latin1 AUTO_INCREMENT=51 ;

-- --------------------------------------------------------

-- 
-- Table structure for table `bk_livre_auteur`
-- 

CREATE TABLE `bk_livre_auteur` (
  `id_objet` int(11) NOT NULL default '0',
  `id_auteur` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id_objet`,`id_auteur`),
  KEY `id_objet` (`id_objet`),
  KEY `id_auteur` (`id_auteur`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- 
-- Table structure for table `bk_serie`
-- 

CREATE TABLE `bk_serie` (
  `id_serie` int(11) NOT NULL auto_increment,
  `nom_serie` varchar(128) NOT NULL default '',
  PRIMARY KEY  (`id_serie`)
) ENGINE=MyISAM AUTO_INCREMENT=234 DEFAULT CHARSET=latin1 AUTO_INCREMENT=234 ;

-- --------------------------------------------------------

-- 
-- Table structure for table `boutique_parametres`
-- 

CREATE TABLE `boutique_parametres` (
  `nom_param` varchar(32) NOT NULL default '',
  `valeur_param` text NOT NULL,
  `description_param` text NOT NULL,
  PRIMARY KEY  (`nom_param`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- 
-- Table structure for table `boutiqueut_centre_cout`
-- 

CREATE TABLE `boutiqueut_centre_cout` (
  `id_utilisateur` int(6) NOT NULL,
  `centre_cout` varchar(20) NOT NULL,
  `contact` varchar(250) default NULL,
  PRIMARY KEY  (`id_utilisateur`,`centre_cout`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- 
-- Table structure for table `boutiqueut_debitfacture`
-- 

CREATE TABLE `boutiqueut_debitfacture` (
  `id_facture` int(11) NOT NULL auto_increment,
  `id_utilisateur` int(11) default NULL,
  `nom` varchar(64) default NULL,
  `prenom` varchar(64) default NULL,
  `adresse` text,
  `eotp` varchar(50) default NULL,
  `objectif` varchar(200) default NULL,
  `contact` varchar(200) default NULL,
  `centre_financier` varchar(20) default NULL,
  `centre_cout` varchar(20) default NULL,
  `date_facture` datetime NOT NULL default '0000-00-00 00:00:00',
  `mode_paiement` char(2) NOT NULL default '',
  `montant_facture` int(12) NOT NULL default '0',
  `etat_facture` tinyint(2) default NULL,
  `ready` tinyint(1) NOT NULL,
  PRIMARY KEY  (`id_facture`),
  KEY `fk_cpt_debitfacture_utilisateurs` (`id_utilisateur`),
  KEY `mode_paiement` (`mode_paiement`),
  KEY `date_facture` (`date_facture`)
) ENGINE=MyISAM AUTO_INCREMENT=82 DEFAULT CHARSET=latin1 PACK_KEYS=1 AUTO_INCREMENT=82 ;

-- --------------------------------------------------------

-- 
-- Table structure for table `boutiqueut_produits`
-- 

CREATE TABLE `boutiqueut_produits` (
  `id_produit` int(11) NOT NULL auto_increment,
  `id_typeprod` int(11) NOT NULL default '0',
  `nom_prod` varchar(64) default NULL,
  `prix_vente_prod` int(7) NOT NULL default '0',
  `prix_vente_prod_service` int(7) NOT NULL default '0',
  `prix_achat_prod` int(7) NOT NULL default '0',
  `stock_global_prod` int(4) NOT NULL default '0',
  `prod_archive` binary(1) default '0',
  `url_logo_prod` text NOT NULL,
  `description_prod` text NOT NULL,
  `a_retirer_prod` tinyint(1) default NULL,
  `description_longue_prod` text,
  `id_file` int(11) default NULL,
  `date_fin_produit` datetime default NULL,
  `id_produit_parent` int(11) default NULL,
  PRIMARY KEY  (`id_produit`),
  KEY `fk_cpt_produits_cpt_type_produit` (`id_typeprod`),
  KEY `id_file` (`id_file`)
) ENGINE=MyISAM AUTO_INCREMENT=24 DEFAULT CHARSET=latin1 AUTO_INCREMENT=24 ;

-- --------------------------------------------------------

-- 
-- Table structure for table `boutiqueut_reapro`
-- 

CREATE TABLE `boutiqueut_reapro` (
  `id_produit` int(6) NOT NULL,
  `date_reapro` datetime NOT NULL,
  `quantite` int(11) NOT NULL,
  PRIMARY KEY  (`id_produit`,`date_reapro`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- 
-- Table structure for table `boutiqueut_service_utl`
-- 

CREATE TABLE `boutiqueut_service_utl` (
  `id_utilisateur` int(6) NOT NULL,
  `centre_financier` varchar(20) NOT NULL default '',
  PRIMARY KEY  (`centre_financier`,`id_utilisateur`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- 
-- Table structure for table `boutiqueut_type_produit`
-- 

CREATE TABLE `boutiqueut_type_produit` (
  `id_typeprod` int(11) NOT NULL auto_increment,
  `nom_typeprod` text NOT NULL,
  `url_logo_typeprod` varchar(128) NOT NULL default '',
  `description_typeprod` text NOT NULL,
  `id_file` int(11) default NULL,
  `css` varchar(255) NOT NULL,
  PRIMARY KEY  (`id_typeprod`),
  KEY `id_file` (`id_file`)
) ENGINE=MyISAM AUTO_INCREMENT=6 DEFAULT CHARSET=latin1 AUTO_INCREMENT=6 ;

-- --------------------------------------------------------

-- 
-- Table structure for table `boutiqueut_vendu`
-- 

CREATE TABLE `boutiqueut_vendu` (
  `id_facture` int(11) NOT NULL default '0',
  `id_produit` int(11) NOT NULL default '0',
  `quantite` int(12) NOT NULL default '0',
  `prix_unit` int(12) NOT NULL default '0',
  `a_retirer_vente` tinyint(1) default NULL,
  PRIMARY KEY  (`id_facture`,`id_produit`,`prix_unit`),
  KEY `fk_cpt_vendu_cpt_produits` (`id_produit`),
  KEY `a_retirer_vente` (`a_retirer_vente`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- 
-- Table structure for table `boutiqueut_verrou`
-- 

CREATE TABLE `boutiqueut_verrou` (
  `id_utilisateur` int(11) NOT NULL default '0',
  `id_produit` int(11) NOT NULL default '0',
  `prix_services` int(1) NOT NULL default '0',
  `quantite` char(32) NOT NULL default '',
  `date_res` datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`id_utilisateur`,`id_produit`),
  KEY `fk_cpt_verrou_cpt_produits` (`id_produit`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- 
-- Table structure for table `commentaire_entreprise`
-- 

CREATE TABLE `commentaire_entreprise` (
  `id_com_ent` int(11) NOT NULL auto_increment,
  `id_utilisateur` int(11) NOT NULL default '0',
  `id_ent` int(11) NOT NULL default '0',
  `id_contact` int(11) default NULL,
  `date_com_ent` date NOT NULL default '0000-00-00',
  `commentaire_ent` text NOT NULL,
  PRIMARY KEY  (`id_com_ent`),
  KEY `fk_commentaire_contact_entreprise_utilisateurs` (`id_utilisateur`),
  KEY `fk_commentaire_contact_entreprise_contact_entreprise` (`id_contact`),
  KEY `id_ent` (`id_ent`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=latin1 AUTO_INCREMENT=2 ;

-- --------------------------------------------------------

-- 
-- Table structure for table `contact_entreprise`
-- 

CREATE TABLE `contact_entreprise` (
  `id_contact` int(11) NOT NULL auto_increment,
  `id_ent` int(11) NOT NULL default '0',
  `nom_contact` varchar(128) NOT NULL default '',
  `telephone_contact` varchar(32) NOT NULL default '',
  `service_contact` varchar(128) NOT NULL default '',
  `email_contact` varchar(128) default NULL,
  `fax_contact` varchar(32) default NULL,
  PRIMARY KEY  (`id_contact`),
  KEY `fk_contact_entreprise_entreprise` (`id_ent`)
) ENGINE=MyISAM AUTO_INCREMENT=6 DEFAULT CHARSET=latin1 AUTO_INCREMENT=6 ;

-- --------------------------------------------------------

-- 
-- Table structure for table `cpg_campagne`
-- 

CREATE TABLE `cpg_campagne` (
  `id_campagne` int(11) NOT NULL auto_increment,
  `nom_campagne` varchar(255) NOT NULL,
  `description_campagne` text NOT NULL,
  `id_groupe` int(11) NOT NULL,
  `date_debut_campagne` date default NULL,
  `date_fin_campagne` date default NULL,
  `id_asso` int(11) NOT NULL default '1',
  PRIMARY KEY  (`id_campagne`)
) ENGINE=MyISAM AUTO_INCREMENT=45 DEFAULT CHARSET=latin1 PACK_KEYS=1 AUTO_INCREMENT=45 ;

-- --------------------------------------------------------

-- 
-- Table structure for table `cpg_participe`
-- 

CREATE TABLE `cpg_participe` (
  `id_campagne` int(11) NOT NULL,
  `id_utilisateur` int(11) NOT NULL,
  `date_participation` date NOT NULL,
  PRIMARY KEY  (`id_campagne`,`id_utilisateur`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- 
-- Table structure for table `cpg_question`
-- 

CREATE TABLE `cpg_question` (
  `id_question` int(11) NOT NULL auto_increment,
  `id_campagne` int(11) NOT NULL,
  `nom_question` varchar(255) NOT NULL,
  `description_question` text NOT NULL,
  `type_question` enum('list','text','radio','checkbox') default 'text',
  `reponses_question` text,
  `limites_reponses_question` int(2) NOT NULL,
  PRIMARY KEY  (`id_question`)
) ENGINE=MyISAM AUTO_INCREMENT=89 DEFAULT CHARSET=latin1 PACK_KEYS=1 AUTO_INCREMENT=89 ;

-- --------------------------------------------------------

-- 
-- Table structure for table `cpg_reponse`
-- 

CREATE TABLE `cpg_reponse` (
  `id_campagne` int(11) NOT NULL,
  `id_question` int(11) NOT NULL,
  `id_utilisateur` int(11) NOT NULL,
  `valeur_reponse` text NOT NULL,
  PRIMARY KEY  (`id_campagne`,`id_question`,`id_utilisateur`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- 
-- Table structure for table `cpt_association`
-- 

CREATE TABLE `cpt_association` (
  `id_assocpt` int(2) NOT NULL auto_increment,
  `montant_ventes_asso` int(10) NOT NULL default '0',
  `montant_rechargements_asso` int(10) NOT NULL default '0',
  PRIMARY KEY  (`id_assocpt`)
) ENGINE=MyISAM AUTO_INCREMENT=113 DEFAULT CHARSET=latin1 AUTO_INCREMENT=113 ;

-- --------------------------------------------------------

-- 
-- Table structure for table `cpt_comptoir`
-- 

CREATE TABLE `cpt_comptoir` (
  `id_comptoir` int(2) NOT NULL auto_increment,
  `id_groupe` int(11) NOT NULL default '0',
  `id_assocpt` int(2) NOT NULL default '0',
  `id_groupe_vendeur` int(11) NOT NULL default '0',
  `nom_cpt` text NOT NULL,
  `type_cpt` tinyint(1) NOT NULL default '0',
  `id_salle` int(11) default NULL,
  `rechargement` tinyint(1) NOT NULL default '1',
  PRIMARY KEY  (`id_comptoir`),
  KEY `fk_cpt_comptoir_groupe` (`id_groupe`),
  KEY `fk_cpt_comptoir_cpt_association` (`id_assocpt`),
  KEY `fk_cpt_comptoir_groupe1` (`id_groupe_vendeur`),
  KEY `id_salle` (`id_salle`),
  KEY `type_cpt` (`type_cpt`)
) ENGINE=MyISAM AUTO_INCREMENT=15 DEFAULT CHARSET=latin1 AUTO_INCREMENT=15 ;

-- --------------------------------------------------------

-- 
-- Table structure for table `cpt_debitfacture`
-- 

CREATE TABLE `cpt_debitfacture` (
  `id_facture` int(11) NOT NULL auto_increment,
  `id_utilisateur` int(11) NOT NULL default '0',
  `id_comptoir` int(2) NOT NULL default '0',
  `id_utilisateur_client` int(11) NOT NULL default '0',
  `date_facture` datetime NOT NULL default '0000-00-00 00:00:00',
  `mode_paiement` char(2) NOT NULL default '',
  `montant_facture` int(12) NOT NULL default '0',
  `transacid` int(8) default NULL,
  `etat_facture` tinyint(2) default NULL,
  PRIMARY KEY  (`id_facture`),
  KEY `fk_cpt_debitfacture_utilisateurs` (`id_utilisateur`),
  KEY `fk_cpt_debitfacture_cpt_comptoir` (`id_comptoir`),
  KEY `fk_cpt_debitfacture_utilisateurs1` (`id_utilisateur_client`),
  KEY `mode_paiement` (`mode_paiement`),
  KEY `date_facture` (`date_facture`)
) ENGINE=MyISAM AUTO_INCREMENT=177371 DEFAULT CHARSET=latin1 AUTO_INCREMENT=177371 ;

-- --------------------------------------------------------

-- 
-- Table structure for table `cpt_mise_en_vente`
-- 

CREATE TABLE `cpt_mise_en_vente` (
  `id_produit` int(11) NOT NULL default '0',
  `id_comptoir` int(2) NOT NULL default '0',
  `stock_local_prod` int(4) NOT NULL default '0',
  `date_mise_en_vente` datetime default NULL,
  PRIMARY KEY  (`id_produit`,`id_comptoir`),
  KEY `fk_cpt_mise_en_vente_cpt_comptoir` (`id_comptoir`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- 
-- Table structure for table `cpt_produits`
-- 

CREATE TABLE `cpt_produits` (
  `id_produit` int(11) NOT NULL auto_increment,
  `id_typeprod` int(11) NOT NULL default '0',
  `id_assocpt` int(2) NOT NULL default '0',
  `nom_prod` varchar(64) default NULL,
  `prix_vente_barman_prod` int(7) NOT NULL default '0',
  `prix_vente_prod` int(7) NOT NULL default '0',
  `prix_vente_prod_cotisant` int(7) NOT NULL default '0',
  `prix_achat_prod` int(7) NOT NULL default '0',
  `meta_action_prod` varchar(32) default NULL,
  `action_prod` int(1) default NULL,
  `cbarre_prod` varchar(16) default NULL,
  `stock_global_prod` int(4) NOT NULL default '0',
  `prod_archive` binary(1) NOT NULL default '\0',
  `url_logo_prod` text NOT NULL,
  `description_prod` text NOT NULL,
  `frais_port_prod` int(10) default NULL,
  `postable_prod` tinyint(1) default NULL,
  `a_retirer_prod` tinyint(1) default NULL,
  `description_longue_prod` text,
  `id_file` int(11) default NULL,
  `id_groupe` int(11) default NULL,
  `date_fin_produit` datetime default NULL,
  `id_produit_parent` int(11) default NULL,
  `mineur` enum('0','16','18') default '0',
  `limite_utilisateur` int(11) default '-1',
  PRIMARY KEY  (`id_produit`),
  KEY `fk_cpt_produits_cpt_type_produit` (`id_typeprod`),
  KEY `fk_cpt_produits_cpt_association` (`id_assocpt`),
  KEY `id_file` (`id_file`),
  KEY `cbarre_prod` (`cbarre_prod`)
) ENGINE=MyISAM AUTO_INCREMENT=601 DEFAULT CHARSET=latin1 AUTO_INCREMENT=601 ;

-- --------------------------------------------------------

-- 
-- Table structure for table `cpt_rechargements`
-- 

CREATE TABLE `cpt_rechargements` (
  `id_rechargement` int(11) NOT NULL auto_increment,
  `id_utilisateur` int(11) NOT NULL default '0',
  `id_comptoir` int(2) NOT NULL default '0',
  `id_utilisateur_operateur` int(11) NOT NULL default '0',
  `id_assocpt` int(2) NOT NULL default '0',
  `montant_rech` int(7) NOT NULL default '0',
  `type_paiement_rech` int(1) NOT NULL default '0',
  `banque_rech` int(3) NOT NULL default '0',
  `date_rech` datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`id_rechargement`),
  KEY `fk_cpt_rechargements_utilisateurs` (`id_utilisateur`),
  KEY `fk_cpt_rechargements_cpt_comptoir` (`id_comptoir`),
  KEY `fk_cpt_rechargements_utilisateurs1` (`id_utilisateur_operateur`),
  KEY `fk_cpt_rechargements_cpt_association` (`id_assocpt`)
) ENGINE=MyISAM AUTO_INCREMENT=25587 DEFAULT CHARSET=latin1 AUTO_INCREMENT=25587 ;

-- --------------------------------------------------------

-- 
-- Table structure for table `cpt_tracking`
-- 

CREATE TABLE `cpt_tracking` (
  `id_utilisateur` int(11) NOT NULL,
  `id_comptoir` int(11) NOT NULL,
  `logged_time` datetime NOT NULL,
  `activity_time` datetime NOT NULL,
  `closed_time` datetime default NULL,
  PRIMARY KEY  (`id_utilisateur`,`id_comptoir`,`logged_time`),
  KEY `closed_time` (`closed_time`),
  KEY `activity_time` (`activity_time`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- 
-- Table structure for table `cpt_type_produit`
-- 

CREATE TABLE `cpt_type_produit` (
  `id_typeprod` int(11) NOT NULL auto_increment,
  `id_assocpt` int(2) NOT NULL default '0',
  `nom_typeprod` text NOT NULL,
  `action_typeprod` text NOT NULL,
  `url_logo_typeprod` varchar(128) NOT NULL default '',
  `description_typeprod` text NOT NULL,
  `id_file` int(11) default NULL,
  `css` varchar(255) NOT NULL,
  PRIMARY KEY  (`id_typeprod`),
  KEY `fk_cpt_type_produit_cpt_association` (`id_assocpt`),
  KEY `id_file` (`id_file`)
) ENGINE=MyISAM AUTO_INCREMENT=41 DEFAULT CHARSET=latin1 AUTO_INCREMENT=41 ;

-- --------------------------------------------------------

-- 
-- Table structure for table `cpt_vendu`
-- 

CREATE TABLE `cpt_vendu` (
  `id_facture` int(11) NOT NULL default '0',
  `id_produit` int(11) NOT NULL default '0',
  `id_assocpt` int(2) NOT NULL default '0',
  `quantite` int(12) NOT NULL default '0',
  `prix_unit` int(12) NOT NULL default '0',
  `a_retirer_vente` tinyint(1) default NULL,
  `a_expedier_vente` tinyint(1) default NULL,
  PRIMARY KEY  (`id_facture`,`id_produit`,`id_assocpt`,`prix_unit`),
  KEY `fk_cpt_vendu_cpt_produits` (`id_produit`),
  KEY `fk_cpt_vendu_cpt_association` (`id_assocpt`),
  KEY `a_retirer_vente` (`a_retirer_vente`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- 
-- Table structure for table `cpt_verrou`
-- 

CREATE TABLE `cpt_verrou` (
  `id_utilisateur` int(11) NOT NULL default '0',
  `id_produit` int(11) NOT NULL default '0',
  `id_comptoir` int(2) NOT NULL default '0',
  `prix_cotisant` int(1) NOT NULL default '0',
  `quantite` char(32) NOT NULL default '',
  `date_res` datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`id_utilisateur`,`id_produit`,`id_comptoir`),
  KEY `fk_cpt_verrou_cpt_produits` (`id_produit`),
  KEY `fk_cpt_verrou_cpt_comptoir` (`id_comptoir`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- 
-- Table structure for table `cpta_budget`
-- 

CREATE TABLE `cpta_budget` (
  `id_budget` int(11) NOT NULL auto_increment,
  `id_classeur` int(11) NOT NULL default '0',
  `nom_budget` varchar(128) NOT NULL default '',
  `total_budget` int(32) NOT NULL default '0',
  `date_budget` datetime NOT NULL default '0000-00-00 00:00:00',
  `valide_budget` tinyint(1) default NULL,
  `projets_budget` text,
  `termine_budget` enum('0','1') default '0',
  PRIMARY KEY  (`id_budget`),
  KEY `fk_cpta_budget_cpta_classeur` (`id_classeur`)
) ENGINE=MyISAM AUTO_INCREMENT=22 DEFAULT CHARSET=latin1 AUTO_INCREMENT=22 ;

-- --------------------------------------------------------

-- 
-- Table structure for table `cpta_classeur`
-- 

CREATE TABLE `cpta_classeur` (
  `id_classeur` int(11) NOT NULL auto_increment,
  `id_cptasso` int(11) NOT NULL default '0',
  `date_debut_classeur` date NOT NULL default '0000-00-00',
  `date_fin_classeur` date NOT NULL default '0000-00-00',
  `nom_classeur` varchar(128) NOT NULL default '',
  `ferme` tinyint(1) NOT NULL default '0',
  PRIMARY KEY  (`id_classeur`),
  KEY `fk_cpta_classeur_compte_asso` (`id_cptasso`)
) ENGINE=MyISAM AUTO_INCREMENT=429 DEFAULT CHARSET=latin1 AUTO_INCREMENT=429 ;

-- --------------------------------------------------------

-- 
-- Table structure for table `cpta_cpasso`
-- 

CREATE TABLE `cpta_cpasso` (
  `id_cptasso` int(11) NOT NULL auto_increment,
  `id_asso` int(11) NOT NULL default '0',
  `id_cptbc` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id_cptasso`),
  KEY `fk_compte_asso_asso` (`id_asso`),
  KEY `fk_compte_asso_cpta_cpbancaire` (`id_cptbc`)
) ENGINE=MyISAM AUTO_INCREMENT=89 DEFAULT CHARSET=latin1 AUTO_INCREMENT=89 ;

-- --------------------------------------------------------

-- 
-- Table structure for table `cpta_cpbancaire`
-- 

CREATE TABLE `cpta_cpbancaire` (
  `id_cptbc` int(11) NOT NULL auto_increment,
  `nom_cptbc` varchar(128) NOT NULL default '',
  `solde_cptbc` int(11) default NULL,
  `date_releve_cptbc` date default NULL,
  `num_cptbc` varchar(25) default NULL,
  PRIMARY KEY  (`id_cptbc`),
  KEY `num_cptbc` (`num_cptbc`)
) ENGINE=MyISAM AUTO_INCREMENT=44 DEFAULT CHARSET=latin1 AUTO_INCREMENT=44 ;

-- --------------------------------------------------------

-- 
-- Table structure for table `cpta_cpbancaire_lignes`
-- 

CREATE TABLE `cpta_cpbancaire_lignes` (
  `id_cptbc` int(11) NOT NULL,
  `num_ligne_cptbc` int(11) NOT NULL auto_increment,
  `date_ligne_cptbc` date NOT NULL,
  `date_valeur_ligne_cptbc` date NOT NULL,
  `libelle_ligne_cptbc` varchar(60) default NULL,
  `commentaire_ligne_cptbc` varchar(256) default NULL,
  `montant_ligne_cptbc` int(11) NOT NULL,
  `devise_ligne_cptbc` enum('EUR') NOT NULL default 'EUR',
  `libbanc_ligne_cptbc` varchar(30) NOT NULL,
  PRIMARY KEY  (`id_cptbc`,`num_ligne_cptbc`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

-- 
-- Table structure for table `cpta_facture`
-- 

CREATE TABLE `cpta_facture` (
  `id_efact` int(11) NOT NULL auto_increment,
  `id_classeur` int(11) NOT NULL,
  `nom_facture` varchar(128) NOT NULL,
  `adresse_facture` text NOT NULL,
  `date_facture` date NOT NULL,
  `titre_facture` varchar(128) NOT NULL,
  `montant_facture` int(11) NOT NULL,
  `id_op` int(11) default NULL,
  PRIMARY KEY  (`id_efact`)
) ENGINE=MyISAM AUTO_INCREMENT=154 DEFAULT CHARSET=latin1 AUTO_INCREMENT=154 ;

-- --------------------------------------------------------

-- 
-- Table structure for table `cpta_facture_ligne`
-- 

CREATE TABLE `cpta_facture_ligne` (
  `num_ligne_efact` int(11) NOT NULL auto_increment,
  `id_efact` int(11) NOT NULL,
  `prix_unit_ligne_efact` int(11) NOT NULL,
  `quantite_ligne_efact` int(11) NOT NULL,
  `designation_ligne_efact` varchar(64) NOT NULL,
  PRIMARY KEY  (`num_ligne_efact`,`id_efact`)
) ENGINE=MyISAM AUTO_INCREMENT=300 DEFAULT CHARSET=latin1 AUTO_INCREMENT=300 ;

-- --------------------------------------------------------

-- 
-- Table structure for table `cpta_libelle`
-- 

CREATE TABLE `cpta_libelle` (
  `id_libelle` int(11) NOT NULL auto_increment,
  `id_asso` int(11) NOT NULL default '0',
  `nom_libelle` varchar(64) NOT NULL default '',
  PRIMARY KEY  (`id_libelle`),
  KEY `id_asso` (`id_asso`)
) ENGINE=MyISAM AUTO_INCREMENT=106 DEFAULT CHARSET=latin1 AUTO_INCREMENT=106 ;

-- --------------------------------------------------------

-- 
-- Table structure for table `cpta_ligne_budget`
-- 

CREATE TABLE `cpta_ligne_budget` (
  `id_budget` int(11) NOT NULL default '0',
  `num_lignebudget` int(11) NOT NULL auto_increment,
  `id_opclb` int(11) NOT NULL default '0',
  `description_ligne` varchar(128) NOT NULL default '',
  `montant_ligne` int(32) NOT NULL default '0',
  PRIMARY KEY  (`id_budget`,`num_lignebudget`),
  KEY `fk_cpta_ligne_budget_cpta_op_clb` (`id_opclb`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

-- 
-- Table structure for table `cpta_notefrais`
-- 

CREATE TABLE `cpta_notefrais` (
  `id_notefrais` int(11) NOT NULL auto_increment,
  `id_classeur` int(11) default NULL,
  `id_asso` int(11) NOT NULL,
  `id_utilisateur` int(11) NOT NULL,
  `date_notefrais` datetime NOT NULL,
  `commentaire_notefrais` varchar(128) NOT NULL,
  `total_notefrais` int(11) NOT NULL,
  `avance_notefrais` int(11) NOT NULL,
  `total_payer_notefrais` int(11) NOT NULL,
  `valide_notefrais` enum('0','1') NOT NULL,
  PRIMARY KEY  (`id_notefrais`),
  KEY `id_classeur` (`id_classeur`),
  KEY `id_asso` (`id_asso`),
  KEY `id_utilisateur` (`id_utilisateur`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=latin1 AUTO_INCREMENT=2 ;

-- --------------------------------------------------------

-- 
-- Table structure for table `cpta_notefrais_ligne`
-- 

CREATE TABLE `cpta_notefrais_ligne` (
  `id_notefrais` int(11) NOT NULL,
  `num_notefrais_ligne` int(11) NOT NULL auto_increment,
  `designation_ligne_notefrais` varchar(128) NOT NULL,
  `prix_ligne_notefrais` int(11) NOT NULL,
  PRIMARY KEY  (`id_notefrais`,`num_notefrais_ligne`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

-- 
-- Table structure for table `cpta_op_clb`
-- 

CREATE TABLE `cpta_op_clb` (
  `id_opclb` int(11) NOT NULL auto_increment,
  `id_asso` int(11) default NULL,
  `id_opstd` int(11) default NULL,
  `libelle_opclb` varchar(128) NOT NULL default '',
  `type_mouvement` tinyint(1) NOT NULL default '0',
  PRIMARY KEY  (`id_opclb`),
  KEY `fk_cpta_op_clb_asso` (`id_asso`),
  KEY `fk_cpta_op_clb_cpta_op_plcptl` (`id_opstd`)
) ENGINE=MyISAM AUTO_INCREMENT=1199 DEFAULT CHARSET=latin1 AUTO_INCREMENT=1199 ;

-- --------------------------------------------------------

-- 
-- Table structure for table `cpta_op_plcptl`
-- 

CREATE TABLE `cpta_op_plcptl` (
  `id_opstd` int(11) NOT NULL auto_increment,
  `code_plan` varchar(8) NOT NULL default '',
  `libelle_plan` varchar(128) NOT NULL default '',
  `type_mouvement` tinyint(1) NOT NULL default '0',
  PRIMARY KEY  (`id_opstd`)
) ENGINE=MyISAM AUTO_INCREMENT=516 DEFAULT CHARSET=latin1 AUTO_INCREMENT=516 ;

-- --------------------------------------------------------

-- 
-- Table structure for table `cpta_operation`
-- 

CREATE TABLE `cpta_operation` (
  `id_op` int(11) NOT NULL auto_increment,
  `id_opclb` int(11) default NULL,
  `id_cptasso` int(11) default NULL,
  `id_opstd` int(11) default NULL,
  `id_utilisateur` int(11) default NULL,
  `id_asso` int(11) default NULL,
  `id_op_liee` int(11) default NULL,
  `id_ent` int(11) default NULL,
  `id_classeur` int(11) NOT NULL default '0',
  `num_op` int(32) NOT NULL default '0',
  `montant_op` int(32) NOT NULL default '0',
  `date_op` date NOT NULL default '0000-00-00',
  `commentaire_op` varchar(128) NOT NULL default '',
  `op_effctue` tinyint(1) NOT NULL default '0',
  `mode_op` tinyint(4) default NULL,
  `num_cheque_op` varchar(32) default NULL,
  `id_libelle` int(11) default NULL,
  PRIMARY KEY  (`id_op`),
  KEY `fk_cpta_operation_cpta_op_clb` (`id_opclb`),
  KEY `fk_cpta_operation_cpta_op_plcptl` (`id_opstd`),
  KEY `fk_cpta_operation_utilisateurs` (`id_utilisateur`),
  KEY `fk_cpta_operation_asso` (`id_asso`),
  KEY `fk_cpta_operation_cpta_operation` (`id_op_liee`),
  KEY `fk_cpta_operation_entreprise` (`id_ent`),
  KEY `fk_cpta_operation_cpta_classeur` (`id_classeur`),
  KEY `fk_cpta_operation_cpta_cpasso` (`id_cptasso`),
  KEY `id_libelle` (`id_libelle`)
) ENGINE=MyISAM AUTO_INCREMENT=9470 DEFAULT CHARSET=latin1 AUTO_INCREMENT=9470 ;

-- --------------------------------------------------------

-- 
-- Table structure for table `cpta_operation_cpblg`
-- 

CREATE TABLE `cpta_operation_cpblg` (
  `id_op` int(11) NOT NULL,
  `id_cptbc` int(11) NOT NULL,
  `num_ligne_cptbc` int(11) NOT NULL,
  PRIMARY KEY  (`id_op`,`id_cptbc`,`num_ligne_cptbc`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- 
-- Table structure for table `cpta_operation_files`
-- 

CREATE TABLE `cpta_operation_files` (
  `id_op` int(11) NOT NULL,
  `id_file` int(11) NOT NULL,
  PRIMARY KEY  (`id_op`,`id_file`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- 
-- Table structure for table `cv_trajet`
-- 

CREATE TABLE `cv_trajet` (
  `id_trajet` int(11) NOT NULL auto_increment,
  `id_utilisateur` int(11) NOT NULL default '0',
  `type_trajet` int(1) NOT NULL default '0',
  `id_ville_dep_trajet` int(11) default NULL,
  `id_ville_arrivee_trajet` int(11) default NULL,
  `date_prop_trajet` datetime NOT NULL default '0000-00-00 00:00:00',
  `comments_trajet` text NOT NULL,
  `id_ent` int(11) default NULL,
  PRIMARY KEY  (`id_trajet`),
  KEY `fk_cv_trajet_utilisateurs` (`id_utilisateur`)
) ENGINE=MyISAM AUTO_INCREMENT=224 DEFAULT CHARSET=latin1 AUTO_INCREMENT=224 ;

-- --------------------------------------------------------

-- 
-- Table structure for table `cv_trajet_date`
-- 

CREATE TABLE `cv_trajet_date` (
  `id_trajet` int(11) NOT NULL default '0',
  `trajet_date` date NOT NULL default '0000-00-00',
  PRIMARY KEY  (`id_trajet`,`trajet_date`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- 
-- Table structure for table `cv_trajet_etape`
-- 

CREATE TABLE `cv_trajet_etape` (
  `id_trajet` int(11) NOT NULL default '0',
  `trajet_date` date NOT NULL default '0000-00-00',
  `id_etape` int(11) NOT NULL auto_increment,
  `id_utilisateur` int(11) NOT NULL default '0',
  `id_ville_etape` int(11) NOT NULL default '0',
  `date_prop_etape` datetime NOT NULL default '0000-00-00 00:00:00',
  `comments_etape` text NOT NULL,
  `accepted_etape` enum('0','1','2','3') default '0',
  PRIMARY KEY  (`id_trajet`,`trajet_date`,`id_etape`),
  KEY `accepted_etape` (`accepted_etape`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

-- 
-- Table structure for table `d_file`
-- 

CREATE TABLE `d_file` (
  `id_file` int(11) NOT NULL auto_increment,
  `id_rev_file_last` int(11) default NULL,
  `nom_fichier_file` varchar(96) NOT NULL default '',
  `titre_file` varchar(96) default NULL,
  `id_folder` int(11) NOT NULL default '0',
  `description_file` text,
  `date_ajout_file` datetime NOT NULL default '0000-00-00 00:00:00',
  `date_modif_file` datetime default NULL,
  `id_asso` int(11) default NULL,
  `nb_telechargement_file` int(11) NOT NULL default '0',
  `mime_type_file` varchar(64) NOT NULL default '',
  `taille_file` int(11) NOT NULL default '0',
  `id_utilisateur` int(11) default NULL,
  `id_groupe` int(11) NOT NULL default '0',
  `id_groupe_admin` int(11) NOT NULL default '0',
  `droits_acces_file` int(11) NOT NULL default '0',
  `modere_file` smallint(1) NOT NULL default '0',
  PRIMARY KEY  (`id_file`),
  KEY `id_folder` (`id_folder`),
  KEY `id_asso` (`id_asso`),
  KEY `id_utilisateur` (`id_utilisateur`),
  KEY `id_groupe` (`id_groupe`),
  KEY `id_groupe_admin` (`id_groupe_admin`)
) ENGINE=MyISAM AUTO_INCREMENT=4754 DEFAULT CHARSET=latin1 AUTO_INCREMENT=4754 ;

-- --------------------------------------------------------

-- 
-- Table structure for table `d_file_lock`
-- 

CREATE TABLE `d_file_lock` (
  `id_file` int(11) NOT NULL,
  `id_utilisateur` int(11) NOT NULL,
  `time_file_lock` datetime NOT NULL,
  PRIMARY KEY  (`id_file`,`id_utilisateur`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- 
-- Table structure for table `d_file_rev`
-- 

CREATE TABLE `d_file_rev` (
  `id_file` int(11) NOT NULL,
  `id_rev_file` int(11) NOT NULL auto_increment,
  `id_utilisateur_rev_file` int(11) NOT NULL,
  `date_rev_file` datetime NOT NULL,
  `filesize_rev_file` int(11) NOT NULL,
  `mime_type_rev_file` varchar(64) NOT NULL,
  `comment_rev_file` varchar(256) NOT NULL,
  PRIMARY KEY  (`id_file`,`id_rev_file`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

-- 
-- Table structure for table `d_file_tag`
-- 

CREATE TABLE `d_file_tag` (
  `id_tag` int(11) NOT NULL,
  `id_file` int(11) NOT NULL,
  PRIMARY KEY  (`id_tag`,`id_file`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- 
-- Table structure for table `d_folder`
-- 

CREATE TABLE `d_folder` (
  `id_folder` int(11) NOT NULL auto_increment,
  `titre_folder` varchar(96) default NULL,
  `nom_fichier_folder` varchar(96) default NULL,
  `id_folder_parent` int(11) default NULL,
  `description_folder` text,
  `date_ajout_folder` datetime NOT NULL default '0000-00-00 00:00:00',
  `date_modif_folder` datetime default NULL,
  `id_asso` int(11) default NULL,
  `id_utilisateur` int(11) default NULL,
  `id_groupe` int(11) NOT NULL default '0',
  `id_groupe_admin` int(11) NOT NULL default '0',
  `droits_acces_folder` int(11) NOT NULL default '0',
  `modere_folder` smallint(1) NOT NULL default '0',
  PRIMARY KEY  (`id_folder`),
  KEY `id_folder_parent` (`id_folder_parent`),
  KEY `id_asso` (`id_asso`),
  KEY `id_utilisateur` (`id_utilisateur`),
  KEY `id_groupe` (`id_groupe`),
  KEY `id_groupe_admin` (`id_groupe_admin`)
) ENGINE=MyISAM AUTO_INCREMENT=1590 DEFAULT CHARSET=latin1 AUTO_INCREMENT=1590 ;

-- --------------------------------------------------------

-- 
-- Table structure for table `edu_uv`
-- 

CREATE TABLE `edu_uv` (
  `id_uv` int(11) NOT NULL auto_increment,
  `code_uv` char(4) default NULL,
  `intitule_uv` varchar(128) default NULL,
  `objectifs_uv` text NOT NULL,
  `programme_uv` text NOT NULL,
  `cours_uv` enum('0','1') default '1',
  `td_uv` enum('0','1') default '1',
  `tp_uv` enum('0','1') default '1',
  `ects_uv` int(2) default NULL,
  `id_folder` int(11) default NULL,
  `id_lieu` int(11) default NULL,
  `projet_uv` enum('0','1') default '0',
  PRIMARY KEY  (`id_uv`),
  UNIQUE KEY `code_uv` (`code_uv`)
) ENGINE=MyISAM AUTO_INCREMENT=409 DEFAULT CHARSET=latin1 AUTO_INCREMENT=409 ;

-- --------------------------------------------------------

-- 
-- Table structure for table `edu_uv_comments`
-- 

CREATE TABLE `edu_uv_comments` (
  `id_comment` int(11) NOT NULL auto_increment,
  `id_uv` int(11) NOT NULL,
  `id_utilisateur` int(11) NOT NULL,
  `note_obtention_uv` enum('A','B','C','D','E','F','Fx') default NULL,
  `comment_uv` text NOT NULL,
  `interet_uv` enum('0','1','2','3','4','5') default '3',
  `utilite_uv` enum('0','1','2','3','4','5') NOT NULL default '3',
  `note_uv` enum('0','1','2','3','4','5') NOT NULL default '3',
  `travail_uv` enum('0','1','2','3','4','5') NOT NULL default '3',
  `qualite_uv` enum('0','1','2','3','4','5') default '3',
  `date_commentaire` datetime NOT NULL,
  `state_comment` tinyint(1) NOT NULL default '0',
  PRIMARY KEY  (`id_comment`),
  UNIQUE KEY `UNIQ_COMMENT` (`id_uv`,`id_utilisateur`)
) ENGINE=MyISAM AUTO_INCREMENT=440 DEFAULT CHARSET=latin1 COMMENT='Commentaires sur les UVs' AUTO_INCREMENT=440 ;

-- --------------------------------------------------------

-- 
-- Table structure for table `edu_uv_dept`
-- 

CREATE TABLE `edu_uv_dept` (
  `id_uv` int(11) NOT NULL,
  `id_dept` varchar(16) NOT NULL default '',
  `uv_cat` varchar(2) default NULL,
  PRIMARY KEY  (`id_uv`,`id_dept`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='association UVs / Département';

-- --------------------------------------------------------

-- 
-- Table structure for table `edu_uv_groupe`
-- 

CREATE TABLE `edu_uv_groupe` (
  `id_uv_groupe` int(11) NOT NULL auto_increment,
  `id_uv` int(11) NOT NULL,
  `type_grp` enum('C','TD','TP') NOT NULL,
  `numero_grp` int(1) default '1',
  `heure_debut_grp` time default NULL,
  `heure_fin_grp` time default NULL,
  `jour_grp` int(1) NOT NULL,
  `frequence_grp` int(1) NOT NULL,
  `semestre_grp` varchar(5) NOT NULL,
  `salle_grp` varchar(4) default NULL,
  `id_lieu` int(11) default NULL,
  PRIMARY KEY  (`id_uv_groupe`),
  KEY `id_uv` (`id_uv`),
  KEY `id_lieu` (`id_lieu`),
  KEY `semestre_grp` (`semestre_grp`)
) ENGINE=MyISAM AUTO_INCREMENT=3665 DEFAULT CHARSET=latin1 AUTO_INCREMENT=3665 ;

-- --------------------------------------------------------

-- 
-- Table structure for table `edu_uv_groupe_etudiant`
-- 

CREATE TABLE `edu_uv_groupe_etudiant` (
  `id_uv_groupe` int(11) NOT NULL,
  `id_utilisateur` int(11) NOT NULL,
  `semaine_etu_grp` enum('AB','A','B') NOT NULL default 'AB',
  PRIMARY KEY  (`id_uv_groupe`,`id_utilisateur`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- 
-- Table structure for table `edu_uv_obtention`
-- 

CREATE TABLE `edu_uv_obtention` (
  `id_uv` int(11) NOT NULL,
  `id_utilisateur` int(11) NOT NULL default '0',
  `note_obtention` enum('A','B','C','D','E','F','Fx','EQUIV') NOT NULL default 'A',
  `semestre_obtention` varchar(4) NOT NULL,
  PRIMARY KEY  (`id_uv`,`id_utilisateur`,`note_obtention`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='Obtention des UVs';

-- --------------------------------------------------------

-- 
-- Table structure for table `entreprise`
-- 

CREATE TABLE `entreprise` (
  `id_ent` int(11) NOT NULL auto_increment,
  `nom_entreprise` varchar(128) NOT NULL default '',
  `rue_entreprise` varchar(128) default NULL,
  `ville_entreprise` varchar(128) default NULL,
  `cpostal_entreprise` varchar(16) default NULL,
  `pays_entreprise` varchar(64) default NULL,
  `telephone_entreprise` varchar(32) default NULL,
  `email_entreprise` varchar(128) default NULL,
  `fax_entreprise` varchar(32) default NULL,
  `id_ville` int(11) default NULL,
  `siteweb_entreprise` varchar(128) NOT NULL,
  PRIMARY KEY  (`id_ent`),
  KEY `id_ville` (`id_ville`)
) ENGINE=MyISAM AUTO_INCREMENT=840 DEFAULT CHARSET=latin1 AUTO_INCREMENT=840 ;

-- --------------------------------------------------------

-- 
-- Table structure for table `entreprise_secteur`
-- 

CREATE TABLE `entreprise_secteur` (
  `id_ent` int(11) NOT NULL,
  `id_secteur` int(11) NOT NULL,
  PRIMARY KEY  (`id_ent`,`id_secteur`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- 
-- Table structure for table `fax_fbx`
-- 

CREATE TABLE `fax_fbx` (
  `id_fax` int(11) NOT NULL auto_increment,
  `idfree_fax` int(11) NOT NULL,
  `idtfree_fax` varchar(16) NOT NULL,
  `numdest_fax` varchar(32) NOT NULL,
  `filename_fax` varchar(256) NOT NULL,
  `id_utilisateur` int(11) NOT NULL,
  `id_asso` int(11) NOT NULL,
  `date_fax` datetime NOT NULL,
  PRIMARY KEY  (`id_fax`)
) ENGINE=MyISAM AUTO_INCREMENT=59 DEFAULT CHARSET=latin1 COMMENT='Table des instances de fax envoyé via la Freebox de l''AE ' AUTO_INCREMENT=59 ;

-- --------------------------------------------------------

-- 
-- Table structure for table `fimu_inscr`
-- 

CREATE TABLE `fimu_inscr` (
  `id_inscr` int(5) NOT NULL auto_increment,
  `id_utilisateur` int(11) NOT NULL default '0',
  `jour1` enum('0','1') default NULL,
  `jour2` enum('0','1') default NULL,
  `jour3` enum('0','1') default NULL,
  `jour4` enum('0','1') default NULL,
  `jour5` enum('0','1') default NULL,
  `jour6` enum('0','1') default NULL,
  `choix1_choix` enum('pilote','regisseur','accueil','signaletic','autres') default 'pilote',
  `choix1_com` text,
  `choix2_choix` enum('pilote','regisseur','accueil','signaletic','autres') default 'pilote',
  `choix2_com` text,
  `lang1_lang` tinytext,
  `lang1_lvl` tinytext,
  `lang1_com` text,
  `lang2_lang` tinytext,
  `lang2_lvl` tinytext,
  `lang2_com` text,
  `lang3_lang` tinytext,
  `lang3_lvl` tinytext,
  `lang3_com` text,
  `permis` enum('O','N') NOT NULL default 'N',
  `voiture` enum('O','N') NOT NULL default 'N',
  `afps` enum('O','N') NOT NULL default 'N',
  `afps_com` tinytext,
  `poste_preced` text,
  `remarques` text,
  PRIMARY KEY  (`id_inscr`)
) ENGINE=MyISAM AUTO_INCREMENT=59 DEFAULT CHARSET=latin1 AUTO_INCREMENT=59 ;

-- --------------------------------------------------------

-- 
-- Table structure for table `frm_forum`
-- 

CREATE TABLE `frm_forum` (
  `id_forum` int(11) NOT NULL auto_increment,
  `titre_forum` varchar(64) NOT NULL,
  `description_forum` text,
  `categorie_forum` enum('0','1') NOT NULL default '0',
  `id_forum_parent` int(11) default NULL,
  `id_asso` int(11) default NULL,
  `id_sujet_dernier` int(11) default NULL,
  `nb_sujets_forum` int(11) NOT NULL default '0',
  `id_utilisateur` int(11) default NULL,
  `id_groupe` int(11) NOT NULL,
  `id_groupe_admin` int(11) NOT NULL,
  `droits_acces_forum` int(11) NOT NULL,
  `ordre_forum` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id_forum`),
  KEY `id_groupe_admin` (`id_groupe_admin`),
  KEY `id_groupe` (`id_groupe`),
  KEY `id_utilisateur` (`id_utilisateur`),
  KEY `id_asso` (`id_asso`),
  KEY `id_forum_parent` (`id_forum_parent`),
  KEY `droits_acces_forum` (`droits_acces_forum`),
  KEY `id_sujet_dernier` (`id_sujet_dernier`),
  KEY `categorie_forum` (`categorie_forum`)
) ENGINE=MyISAM AUTO_INCREMENT=189 DEFAULT CHARSET=latin1 AUTO_INCREMENT=189 ;

-- --------------------------------------------------------

-- 
-- Table structure for table `frm_message`
-- 

CREATE TABLE `frm_message` (
  `id_message` int(11) NOT NULL auto_increment,
  `id_utilisateur` int(11) default NULL,
  `id_sujet` int(11) NOT NULL,
  `titre_message` varchar(128) default NULL,
  `contenu_message` text NOT NULL,
  `date_message` datetime NOT NULL,
  `syntaxengine_message` varchar(8) NOT NULL,
  `id_utilisateur_moderateur` int(11) default NULL,
  PRIMARY KEY  (`id_message`),
  KEY `id_utilisateur` (`id_utilisateur`),
  KEY `id_sujet` (`id_sujet`),
  KEY `id_utilisateur_moderateur` (`id_utilisateur_moderateur`),
  KEY `date_message` (`date_message`),
  FULLTEXT KEY `titre_message` (`titre_message`,`contenu_message`)
) ENGINE=MyISAM AUTO_INCREMENT=2160224 DEFAULT CHARSET=latin1 AUTO_INCREMENT=2160224 ;

-- --------------------------------------------------------

-- 
-- Table structure for table `frm_sujet`
-- 

CREATE TABLE `frm_sujet` (
  `id_sujet` int(11) NOT NULL auto_increment,
  `id_utilisateur` int(11) default NULL,
  `id_forum` int(11) NOT NULL,
  `titre_sujet` varchar(92) NOT NULL,
  `soustitre_sujet` varchar(128) default NULL,
  `type_sujet` tinyint(4) NOT NULL,
  `icon_sujet` varchar(32) default NULL,
  `date_sujet` datetime NOT NULL,
  `id_message_dernier` int(11) default NULL,
  `nb_messages_sujet` int(11) NOT NULL default '0',
  `date_fin_annonce_sujet` datetime default NULL,
  `id_utilisateur_moderateur` int(11) default NULL,
  `id_nouvelle` int(11) default NULL,
  `id_catph` int(11) default NULL,
  `id_sondage` int(11) default NULL,
  `id_group` int(11) default NULL,
  PRIMARY KEY  (`id_sujet`),
  KEY `id_sondage` (`id_sondage`),
  KEY `id_catph` (`id_catph`),
  KEY `id_nouvelle` (`id_nouvelle`),
  KEY `id_utilisateur_moderateur` (`id_utilisateur_moderateur`),
  KEY `id_message_dernier` (`id_message_dernier`),
  KEY `id_forum` (`id_forum`),
  KEY `id_utilisateur` (`id_utilisateur`),
  KEY `type_sujet` (`type_sujet`),
  KEY `date_sujet` (`date_sujet`),
  FULLTEXT KEY `titre_sujet` (`titre_sujet`),
  FULLTEXT KEY `soustitre_sujet` (`soustitre_sujet`)
) ENGINE=MyISAM AUTO_INCREMENT=9907 DEFAULT CHARSET=latin1 AUTO_INCREMENT=9907 ;

-- --------------------------------------------------------

-- 
-- Table structure for table `frm_sujet_utilisateur`
-- 

CREATE TABLE `frm_sujet_utilisateur` (
  `id_sujet` int(6) NOT NULL default '0',
  `id_utilisateur` int(6) NOT NULL default '0',
  `id_message_dernier_lu` int(8) default NULL,
  `etoile_sujet` enum('0','1') default NULL,
  PRIMARY KEY  (`id_sujet`,`id_utilisateur`),
  KEY `id_message_dernier_lu` (`id_message_dernier_lu`),
  KEY `etoile_sujet` (`etoile_sujet`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- 
-- Table structure for table `galaxy_link`
-- 

CREATE TABLE `galaxy_link` (
  `id_star_a` int(11) NOT NULL,
  `id_star_b` int(11) NOT NULL,
  `tense_link` int(11) default NULL,
  `max_tense_stars_link` int(11) default NULL,
  `ideal_length_link` float default NULL,
  `length_link` float default NULL,
  `vx_link` float default NULL,
  `vy_link` float default NULL,
  `dx_link` float default NULL,
  `dy_link` float default NULL,
  `delta_link_a` float default NULL,
  `delta_link_b` float default NULL,
  PRIMARY KEY  (`id_star_a`,`id_star_b`),
  KEY `length_link` (`length_link`),
  KEY `id_star_a` (`id_star_a`),
  KEY `id_star_b` (`id_star_b`)
) ENGINE=MEMORY DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- 
-- Table structure for table `galaxy_star`
-- 

CREATE TABLE `galaxy_star` (
  `id_star` int(11) NOT NULL auto_increment,
  `x_star` float default '0',
  `y_star` float default '0',
  `dx_star` float default '0',
  `dy_star` float default '0',
  `nblinks_star` int(11) NOT NULL default '0',
  `sum_tense_star` int(11) NOT NULL default '0',
  `max_tense_star` int(11) NOT NULL default '0',
  `rx_star` int(11) default NULL,
  `ry_star` int(11) default NULL,
  `fixe_star` int(1) NOT NULL default '0',
  PRIMARY KEY  (`id_star`),
  KEY `x_star` (`x_star`,`y_star`)
) ENGINE=MEMORY AUTO_INCREMENT=6533 DEFAULT CHARSET=latin1 AUTO_INCREMENT=6533 ;

-- --------------------------------------------------------

-- 
-- Table structure for table `geopoint`
-- 

CREATE TABLE `geopoint` (
  `id_geopoint` int(11) NOT NULL auto_increment,
  `id_ville` int(11) default NULL,
  `lat_geopoint` float default NULL,
  `long_geopoint` float default NULL,
  `eloi_geopoint` float default NULL,
  `type_geopoint` varchar(32) NOT NULL,
  `nom_geopoint` varchar(64) NOT NULL,
  PRIMARY KEY  (`id_geopoint`),
  KEY `id_ville` (`id_ville`),
  KEY `type_geopoint` (`type_geopoint`),
  KEY `nom_geopoint` (`nom_geopoint`),
  KEY `lat_geopoint` (`lat_geopoint`,`long_geopoint`)
) ENGINE=MyISAM AUTO_INCREMENT=20130 DEFAULT CHARSET=latin1 AUTO_INCREMENT=20130 ;

-- --------------------------------------------------------

-- 
-- Table structure for table `inv_emprunt`
-- 

CREATE TABLE `inv_emprunt` (
  `id_emprunt` int(11) NOT NULL auto_increment,
  `id_utilisateur` int(11) default NULL,
  `id_asso` int(11) default NULL,
  `id_utilisateur_op` int(11) default NULL,
  `date_demande_emp` datetime NOT NULL default '0000-00-00 00:00:00',
  `date_prise_emp` datetime default NULL,
  `date_retour_emp` datetime default NULL,
  `date_debut_emp` datetime NOT NULL default '0000-00-00 00:00:00',
  `date_fin_emp` datetime NOT NULL default '0000-00-00 00:00:00',
  `caution_emp` int(32) default NULL,
  `prix_paye_emp` int(11) default NULL,
  `emprunteur_ext` varchar(128) default NULL,
  `notes_emprunt` text,
  `etat_emprunt` int(2) NOT NULL default '0',
  PRIMARY KEY  (`id_emprunt`),
  KEY `fk_inv_emprunt_utilisateurs` (`id_utilisateur`),
  KEY `fk_inv_emprunt_asso` (`id_asso`),
  KEY `fk_inv_emprunt_utilisateurs1` (`id_utilisateur_op`)
) ENGINE=MyISAM AUTO_INCREMENT=1038 DEFAULT CHARSET=latin1 AUTO_INCREMENT=1038 ;

-- --------------------------------------------------------

-- 
-- Table structure for table `inv_emprunt_objet`
-- 

CREATE TABLE `inv_emprunt_objet` (
  `id_objet` int(11) NOT NULL default '0',
  `id_emprunt` int(11) NOT NULL default '0',
  `retour_effectif_emp` datetime default NULL,
  PRIMARY KEY  (`id_objet`,`id_emprunt`),
  KEY `fk_inv_emprunt_objet_inv_emprunt` (`id_emprunt`),
  KEY `stop_doublons` (`id_objet`,`retour_effectif_emp`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- 
-- Table structure for table `inv_jeu`
-- 

CREATE TABLE `inv_jeu` (
  `id_objet` int(11) NOT NULL,
  `id_serie` int(11) default NULL,
  `etat_jeu` varchar(32) NOT NULL,
  `nb_joueurs_jeu` varchar(32) NOT NULL,
  `duree_jeu` varchar(32) NOT NULL,
  `langue_jeu` varchar(16) NOT NULL,
  `difficulte_jeu` varchar(16) NOT NULL,
  PRIMARY KEY  (`id_objet`),
  KEY `id_serie` (`id_serie`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- 
-- Table structure for table `inv_objet`
-- 

CREATE TABLE `inv_objet` (
  `id_objet` int(11) NOT NULL auto_increment,
  `id_asso` int(11) NOT NULL default '0',
  `id_salle` int(11) NOT NULL default '0',
  `id_op` int(11) default NULL,
  `id_asso_prop` int(11) NOT NULL default '0',
  `id_objtype` int(11) NOT NULL default '0',
  `nom_objet` varchar(128) default NULL,
  `num_objet` int(32) NOT NULL default '0',
  `cbar_objet` varchar(64) NOT NULL default '',
  `num_serie` varchar(128) NOT NULL default '',
  `date_achat` date NOT NULL default '0000-00-00',
  `prix_objet` int(32) NOT NULL default '0',
  `caution_objet` int(11) NOT NULL default '0',
  `prix_emprunt_objet` int(11) NOT NULL default '0',
  `objet_empruntable` tinyint(1) NOT NULL default '0',
  `en_etat` tinyint(1) NOT NULL default '0',
  `archive_objet` tinyint(1) NOT NULL default '0',
  `notes_objet` text,
  PRIMARY KEY  (`id_objet`),
  KEY `fk_inv_objet_asso` (`id_asso`),
  KEY `fk_inv_objet_sl_salle` (`id_salle`),
  KEY `fk_inv_objet_cpta_operation` (`id_op`),
  KEY `fk_inv_objet_asso1` (`id_asso_prop`),
  KEY `fk_inv_objet_inv_type_objets` (`id_objtype`)
) ENGINE=MyISAM AUTO_INCREMENT=2260 DEFAULT CHARSET=latin1 AUTO_INCREMENT=2260 ;

-- --------------------------------------------------------

-- 
-- Table structure for table `inv_objet_evenement`
-- 

CREATE TABLE `inv_objet_evenement` (
  `id_objeven` int(11) NOT NULL auto_increment,
  `id_objet` int(11) NOT NULL default '0',
  `id_emprunt` int(11) default NULL,
  `id_utilisateur` int(11) NOT NULL default '0',
  `type_objeven` int(32) NOT NULL default '0',
  `date_even` datetime NOT NULL default '0000-00-00 00:00:00',
  `notes_even` text,
  PRIMARY KEY  (`id_objeven`),
  KEY `fk_inv_objet_evenement_inv_objet` (`id_objet`),
  KEY `fk_inv_objet_evenement_inv_emprunt` (`id_emprunt`),
  KEY `fk_inv_objet_evenement_utilisateurs` (`id_utilisateur`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

-- 
-- Table structure for table `inv_type_objets`
-- 

CREATE TABLE `inv_type_objets` (
  `id_objtype` int(11) NOT NULL auto_increment,
  `nom_objtype` varchar(128) NOT NULL default '',
  `prix_objtype` int(32) NOT NULL default '0',
  `caution_objtype` int(11) NOT NULL default '0',
  `prix_emprunt_objtype` int(11) NOT NULL default '0',
  `code_objtype` varchar(6) NOT NULL default '',
  `empruntable_objtype` tinyint(1) NOT NULL default '0',
  `notes_objtype` text,
  PRIMARY KEY  (`id_objtype`)
) ENGINE=MyISAM AUTO_INCREMENT=34 DEFAULT CHARSET=latin1 AUTO_INCREMENT=34 ;

-- --------------------------------------------------------

-- 
-- Table structure for table `job_annonces`
-- 

CREATE TABLE `job_annonces` (
  `id_annonce` int(11) NOT NULL auto_increment,
  `id_client` int(11) NOT NULL,
  `id_select_etu` int(11) default NULL,
  `date` date NOT NULL,
  `titre` text NOT NULL,
  `job_type` int(11) NOT NULL,
  `desc` text NOT NULL,
  `divers` text,
  `profil` text NOT NULL,
  `start_date` date default NULL,
  `duree` text,
  `nb_postes` int(11) NOT NULL default '1',
  `indemnite` text,
  `lieu` text,
  `type_contrat` text,
  `allow_diff` tinyint(1) default '0',
  `provided` enum('false','true') NOT NULL default 'false',
  `closed` tinyint(1) NOT NULL default '0',
  PRIMARY KEY  (`id_annonce`)
) ENGINE=MyISAM AUTO_INCREMENT=49 DEFAULT CHARSET=latin1 AUTO_INCREMENT=49 ;

-- --------------------------------------------------------

-- 
-- Table structure for table `job_annonces_etu`
-- 

CREATE TABLE `job_annonces_etu` (
  `id_relation` int(11) NOT NULL auto_increment,
  `id_annonce` int(11) NOT NULL,
  `id_etu` int(11) NOT NULL,
  `relation` enum('apply','reject','selected') default NULL,
  `comment` text,
  PRIMARY KEY  (`id_relation`)
) ENGINE=MyISAM AUTO_INCREMENT=1377 DEFAULT CHARSET=latin1 AUTO_INCREMENT=1377 ;

-- --------------------------------------------------------

-- 
-- Table structure for table `job_feedback`
-- 

CREATE TABLE `job_feedback` (
  `id_fback` int(11) NOT NULL auto_increment,
  `id_annonce` int(11) NOT NULL,
  `note_client` int(11) default NULL,
  `avis_client` text,
  `note_etu` int(11) default NULL,
  `avis_etu` text,
  `valid` tinyint(1) NOT NULL default '1',
  PRIMARY KEY  (`id_fback`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=latin1 AUTO_INCREMENT=3 ;

-- --------------------------------------------------------

-- 
-- Table structure for table `job_pdf_cv`
-- 

CREATE TABLE `job_pdf_cv` (
  `id_cv` int(11) NOT NULL auto_increment,
  `id_utl` int(11) NOT NULL,
  `date` date NOT NULL,
  `lang` enum('fr','en','de','cn','ar','it','pt','kr') default NULL,
  PRIMARY KEY  (`id_cv`)
) ENGINE=MyISAM AUTO_INCREMENT=99 DEFAULT CHARSET=latin1 AUTO_INCREMENT=99 ;

-- --------------------------------------------------------

-- 
-- Table structure for table `job_prefs`
-- 

CREATE TABLE `job_prefs` (
  `id_utilisateur` int(11) NOT NULL,
  `pub_cv` enum('false','true') NOT NULL default 'false',
  `mail_prefs` enum('part','full') NOT NULL default 'part',
  `pub_num` enum('false','true') default 'false',
  PRIMARY KEY  (`id_utilisateur`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- 
-- Table structure for table `job_types`
-- 

CREATE TABLE `job_types` (
  `id_type` int(11) NOT NULL default '0',
  `nom` text NOT NULL,
  PRIMARY KEY  (`id_type`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- 
-- Table structure for table `job_types_etu`
-- 

CREATE TABLE `job_types_etu` (
  `id_type` int(11) NOT NULL,
  `id_utilisateur` int(11) NOT NULL,
  PRIMARY KEY  (`id_type`,`id_utilisateur`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- 
-- Table structure for table `licences`
-- 

CREATE TABLE `licences` (
  `id_licence` int(11) NOT NULL auto_increment,
  `titre` varchar(50) NOT NULL,
  `description` varchar(255) default NULL,
  `url` varchar(255) default NULL,
  `icone` varchar(255) default NULL,
  PRIMARY KEY  (`id_licence`)
) ENGINE=MyISAM AUTO_INCREMENT=11 DEFAULT CHARSET=latin1 PACK_KEYS=0 AUTO_INCREMENT=11 ;

-- --------------------------------------------------------

-- 
-- Table structure for table `loc_lieu`
-- 

CREATE TABLE `loc_lieu` (
  `id_lieu` int(11) NOT NULL auto_increment,
  `id_lieu_parent` int(11) default NULL,
  `id_ville` int(11) NOT NULL,
  `nom_lieu` varchar(64) NOT NULL,
  `lat_lieu` double default NULL,
  `long_lieu` double default NULL,
  `eloi_lieu` double default NULL,
  PRIMARY KEY  (`id_lieu`),
  KEY `id_lien_parent` (`id_lieu_parent`),
  KEY `id_ville` (`id_ville`)
) ENGINE=MyISAM AUTO_INCREMENT=20052 DEFAULT CHARSET=latin1 PACK_KEYS=1 AUTO_INCREMENT=20052 ;

-- --------------------------------------------------------

-- 
-- Table structure for table `loc_pays`
-- 

CREATE TABLE `loc_pays` (
  `id_pays` int(3) NOT NULL auto_increment,
  `nom_pays` varchar(32) NOT NULL,
  `nomeng_pays` varchar(32) NOT NULL,
  `indtel_pays` int(4) default NULL,
  PRIMARY KEY  (`id_pays`)
) ENGINE=MyISAM AUTO_INCREMENT=213 DEFAULT CHARSET=latin1 AUTO_INCREMENT=213 ;

-- --------------------------------------------------------

-- 
-- Table structure for table `loc_ville`
-- 

CREATE TABLE `loc_ville` (
  `id_ville` mediumint(7) NOT NULL auto_increment,
  `id_pays` tinyint(3) default '0',
  `nom_ville` varchar(64) default NULL,
  `cpostal_ville` varchar(8) default NULL,
  `lat_ville` float default '0',
  `long_ville` float default '0',
  `eloi_ville` float default '0',
  `pg_ville` tinyint(4) default '0',
  PRIMARY KEY  (`id_ville`),
  KEY `id_pays` (`id_pays`),
  KEY `nom_ville` (`nom_ville`),
  KEY `cpostal_ville` (`cpostal_ville`),
  KEY `pg_ville` (`pg_ville`)
) ENGINE=MyISAM AUTO_INCREMENT=2283475 DEFAULT CHARSET=latin1 AUTO_INCREMENT=2283475 ;

-- --------------------------------------------------------

-- 
-- Table structure for table `logs`
-- 

CREATE TABLE `logs` (
  `id_log` int(11) NOT NULL auto_increment,
  `id_utilisateur` int(11) NOT NULL,
  `time_log` datetime NOT NULL,
  `action_log` varchar(128) NOT NULL,
  `description_log` text NOT NULL,
  `context_log` varchar(128) NOT NULL,
  PRIMARY KEY  (`id_log`)
) ENGINE=MyISAM AUTO_INCREMENT=8746 DEFAULT CHARSET=latin1 AUTO_INCREMENT=8746 ;

-- --------------------------------------------------------

-- 
-- Table structure for table `mc_contrat`
-- 

CREATE TABLE `mc_contrat` (
  `id_utilisateur` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- 
-- Table structure for table `mc_creneaux`
-- 

CREATE TABLE `mc_creneaux` (
  `id_creneau` int(11) NOT NULL auto_increment,
  `id_machine` int(11) NOT NULL,
  `debut_creneau` datetime default NULL,
  `fin_creneau` datetime default NULL,
  `id_utilisateur` int(11) default NULL,
  `id_jeton` int(11) default NULL,
  PRIMARY KEY  (`id_creneau`),
  KEY `id_jeton` (`id_jeton`),
  KEY `id_utilisateur` (`id_utilisateur`),
  KEY `debut_creneau` (`debut_creneau`),
  KEY `id_machine` (`id_machine`),
  KEY `fin_creneau` (`fin_creneau`)
) ENGINE=MyISAM AUTO_INCREMENT=86094 DEFAULT CHARSET=latin1 AUTO_INCREMENT=86094 ;

-- --------------------------------------------------------

-- 
-- Table structure for table `mc_jeton`
-- 

CREATE TABLE `mc_jeton` (
  `id_jeton` int(11) NOT NULL auto_increment,
  `id_salle` int(11) NOT NULL default '0',
  `type_jeton` enum('laver','secher') NOT NULL default 'laver',
  `nom_jeton` varchar(8) NOT NULL default '',
  PRIMARY KEY  (`id_jeton`),
  KEY `id_salle` (`id_salle`),
  KEY `nom_jeton` (`nom_jeton`)
) ENGINE=MyISAM AUTO_INCREMENT=386 DEFAULT CHARSET=latin1 AUTO_INCREMENT=386 ;

-- --------------------------------------------------------

-- 
-- Table structure for table `mc_jeton_utilisateur`
-- 

CREATE TABLE `mc_jeton_utilisateur` (
  `id_jeton` int(11) NOT NULL default '0',
  `id_utilisateur` int(11) NOT NULL default '0',
  `id_gap` int(11) default NULL,
  `prise_jeton` datetime NOT NULL default '0000-00-00 00:00:00',
  `retour_jeton` datetime default NULL,
  `penalite` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id_jeton`,`id_utilisateur`,`prise_jeton`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- 
-- Table structure for table `mc_machines`
-- 

CREATE TABLE `mc_machines` (
  `id` int(11) NOT NULL auto_increment,
  `lettre` varchar(1) NOT NULL,
  `type` enum('laver','secher') NOT NULL,
  `loc` int(11) NOT NULL,
  `hs` int(11) default '0',
  PRIMARY KEY  (`id`),
  KEY `type` (`type`),
  KEY `loc` (`loc`),
  KEY `hs` (`hs`)
) ENGINE=MyISAM AUTO_INCREMENT=15 DEFAULT CHARSET=latin1 AUTO_INCREMENT=15 ;

-- --------------------------------------------------------

-- 
-- Table structure for table `mc_reservations`
-- 

CREATE TABLE `mc_reservations` (
  `id` int(11) NOT NULL,
  `id_util` int(11) NOT NULL,
  `id_creneau` int(11) NOT NULL,
  `date` date NOT NULL,
  `id_jeton` int(11) default NULL,
  `prise_jeton` datetime default NULL,
  `retour_jeton` datetime default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- 
-- Table structure for table `ml_todo`
-- 

CREATE TABLE `ml_todo` (
  `num_todo` int(11) NOT NULL auto_increment,
  `action_todo` enum('SUBSCRIBE','UNSUBSCRIBE','CREATE') NOT NULL,
  `ml_todo` varchar(128) NOT NULL,
  `email_todo` varchar(128) default NULL,
  PRIMARY KEY  (`num_todo`)
) ENGINE=MyISAM AUTO_INCREMENT=18810 DEFAULT CHARSET=latin1 AUTO_INCREMENT=18810 ;

-- --------------------------------------------------------

-- 
-- Table structure for table `mmt_instru_musique`
-- 

CREATE TABLE `mmt_instru_musique` (
  `id_instru_musique` int(11) NOT NULL auto_increment,
  `nom_instru_musique` varchar(64) NOT NULL,
  PRIMARY KEY  (`id_instru_musique`)
) ENGINE=MyISAM AUTO_INCREMENT=18 DEFAULT CHARSET=latin1 AUTO_INCREMENT=18 ;

-- --------------------------------------------------------

-- 
-- Table structure for table `nvl_canal`
-- 

CREATE TABLE `nvl_canal` (
  `id_canal` int(11) NOT NULL auto_increment,
  `nom_canal` varchar(32) NOT NULL,
  `id_asso` int(11) default NULL,
  PRIMARY KEY  (`id_canal`),
  KEY `id_asso` (`id_asso`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=latin1 AUTO_INCREMENT=3 ;

-- --------------------------------------------------------

-- 
-- Table structure for table `nvl_dates`
-- 

CREATE TABLE `nvl_dates` (
  `id_nouvelle` int(11) NOT NULL default '0',
  `id_dates_nvl` int(11) NOT NULL auto_increment,
  `id_salres` int(11) default NULL,
  `date_debut_eve` datetime NOT NULL default '0000-00-00 00:00:00',
  `date_fin_eve` datetime NOT NULL default '0000-00-00 00:00:00',
  `titre_eve` varchar(128) default NULL,
  PRIMARY KEY  (`id_nouvelle`,`id_dates_nvl`),
  KEY `fk_nvl_dates_sl_reservation` (`id_salres`),
  KEY `date_debut_eve` (`date_debut_eve`),
  KEY `date_fin_eve` (`date_fin_eve`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

-- 
-- Table structure for table `nvl_nouvelles`
-- 

CREATE TABLE `nvl_nouvelles` (
  `id_nouvelle` int(11) NOT NULL auto_increment,
  `id_utilisateur` int(11) NOT NULL default '0',
  `id_asso` int(11) default NULL,
  `titre_nvl` varchar(128) NOT NULL default '',
  `resume_nvl` text,
  `contenu_nvl` text NOT NULL,
  `date_nvl` datetime NOT NULL default '0000-00-00 00:00:00',
  `modere_nvl` tinyint(1) NOT NULL default '0',
  `id_utilisateur_moderateur` int(11) default NULL,
  `type_nvl` tinyint(1) NOT NULL default '1',
  `asso_seule_nvl` enum('0','1') NOT NULL default '0',
  `id_lieu` int(11) default NULL,
  `id_canal` int(11) NOT NULL default '1',
  PRIMARY KEY  (`id_nouvelle`),
  KEY `fk_nvl_nouvelles_utilisateurs` (`id_utilisateur`),
  KEY `fk_nvl_nouvelles_asso` (`id_asso`),
  KEY `id_canal` (`id_canal`),
  KEY `type_nvl` (`type_nvl`),
  KEY `modere_nvl` (`modere_nvl`),
  KEY `date_nvl` (`date_nvl`)
) ENGINE=MyISAM AUTO_INCREMENT=1759 DEFAULT CHARSET=latin1 AUTO_INCREMENT=1759 ;

-- --------------------------------------------------------

-- 
-- Table structure for table `nvl_nouvelles_files`
-- 

CREATE TABLE `nvl_nouvelles_files` (
  `id_nouvelle` int(11) NOT NULL,
  `id_file` int(11) NOT NULL,
  PRIMARY KEY  (`id_nouvelle`,`id_file`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- 
-- Table structure for table `nvl_nouvelles_tag`
-- 

CREATE TABLE `nvl_nouvelles_tag` (
  `id_tag` int(11) NOT NULL,
  `id_nouvelle` int(11) NOT NULL,
  PRIMARY KEY  (`id_tag`,`id_nouvelle`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- 
-- Table structure for table `pages`
-- 

CREATE TABLE `pages` (
  `id_page` int(2) NOT NULL auto_increment,
  `nom_page` varchar(64) NOT NULL default '',
  `id_groupe` int(11) NOT NULL default '0',
  `id_utilisateur` int(11) NOT NULL default '0',
  `texte_page` text NOT NULL,
  `date_page` datetime NOT NULL default '0000-00-00 00:00:00',
  `titre_page` text NOT NULL,
  `section_page` varchar(24) default NULL,
  `id_groupe_modal` int(11) NOT NULL default '0',
  `droits_acces_page` int(11) NOT NULL default '0',
  `modere_page` int(1) NOT NULL default '0',
  PRIMARY KEY  (`id_page`),
  KEY `fk_pages_groupe` (`id_groupe`),
  KEY `fk_pages_utilisateurs` (`id_utilisateur`)
) ENGINE=MyISAM AUTO_INCREMENT=188 DEFAULT CHARSET=latin1 AUTO_INCREMENT=188 ;

-- --------------------------------------------------------

-- 
-- Table structure for table `parrains`
-- 

CREATE TABLE `parrains` (
  `id_utilisateur` int(11) NOT NULL default '0',
  `id_utilisateur_fillot` int(11) NOT NULL default '0',
  `id_photo` int(11) default NULL,
  PRIMARY KEY  (`id_utilisateur`,`id_utilisateur_fillot`),
  KEY `fk_parrains_utilisateurs1` (`id_utilisateur_fillot`),
  KEY `fk_parrains_sas_photos` (`id_photo`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- 
-- Table structure for table `pays`
-- 

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `ae2`.`pays` AS select `ae2`.`loc_pays`.`id_pays` AS `id_pays`,`ae2`.`loc_pays`.`nom_pays` AS `nom_pays`,`ae2`.`loc_pays`.`nomeng_pays` AS `nomeng_pays`,`ae2`.`loc_pays`.`indtel_pays` AS `indtel_pays` from `ae2`.`loc_pays`;

-- --------------------------------------------------------

-- 
-- Table structure for table `pedag_cursus`
-- 

CREATE TABLE `pedag_cursus` (
  `id_cursus` int(11) NOT NULL auto_increment,
  `type` enum('FILIERE','MINEUR','AUTRE') default NULL,
  `intitule` varchar(128) NOT NULL,
  `name` varchar(12) default NULL,
  `description` text,
  `responsable` varchar(64) default NULL,
  `departement` enum('Humas','TC','GI','GESC','IMAP','MC','EDIM') default NULL,
  `nb_some_of` tinyint(4) default NULL,
  `nb_all_of` tinyint(4) default NULL,
  `closed` tinyint(1) NOT NULL default '0',
  PRIMARY KEY  (`id_cursus`)
) ENGINE=MyISAM AUTO_INCREMENT=25 DEFAULT CHARSET=latin1 AUTO_INCREMENT=25 ;

-- --------------------------------------------------------

-- 
-- Table structure for table `pedag_cursus_utl`
-- 

CREATE TABLE `pedag_cursus_utl` (
  `id_utilisateur` int(11) NOT NULL,
  `id_cursus` int(11) NOT NULL,
  PRIMARY KEY  (`id_utilisateur`,`id_cursus`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- 
-- Table structure for table `pedag_groupe`
-- 

CREATE TABLE `pedag_groupe` (
  `id_groupe` int(11) NOT NULL auto_increment,
  `id_uv` int(11) NOT NULL,
  `type` enum('C','TD','TP','THE') default NULL,
  `num_groupe` tinyint(4) NOT NULL,
  `freq` int(1) NOT NULL default '1',
  `semestre` varchar(5) NOT NULL,
  `debut` time NOT NULL,
  `fin` time NOT NULL,
  `jour` int(1) NOT NULL,
  `salle` varchar(5) default NULL,
  PRIMARY KEY  (`id_groupe`),
  UNIQUE KEY `uniqueuh` (`id_uv`,`type`,`num_groupe`,`semestre`)
) ENGINE=MyISAM AUTO_INCREMENT=5245 DEFAULT CHARSET=latin1 COMMENT='Groupes pour les UV ex TD2 de RE41 du dimanche 10h' AUTO_INCREMENT=5245 ;

-- --------------------------------------------------------

-- 
-- Table structure for table `pedag_groupe_utl`
-- 

CREATE TABLE `pedag_groupe_utl` (
  `id_groupe` int(11) NOT NULL,
  `id_utilisateur` int(11) NOT NULL,
  `semaine` enum('A','B') default NULL,
  PRIMARY KEY  (`id_groupe`,`id_utilisateur`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- 
-- Table structure for table `pedag_resultat`
-- 

CREATE TABLE `pedag_resultat` (
  `id_resultat` int(11) NOT NULL auto_increment,
  `id_uv` int(11) NOT NULL,
  `id_utilisateur` int(11) NOT NULL,
  `semestre` varchar(5) NOT NULL,
  `note` enum('A','B','C','D','E','F','FX','ABS','EQUIV') NOT NULL,
  `cancelled` tinyint(1) NOT NULL default '0',
  PRIMARY KEY  (`id_resultat`)
) ENGINE=MyISAM AUTO_INCREMENT=3536 DEFAULT CHARSET=latin1 COMMENT='Résultats aux UV' AUTO_INCREMENT=3536 ;

-- --------------------------------------------------------

-- 
-- Table structure for table `pedag_uv`
-- 

CREATE TABLE `pedag_uv` (
  `id_uv` int(11) NOT NULL auto_increment,
  `code` varchar(4) NOT NULL,
  `intitule` varchar(128) NOT NULL,
  `type` enum('CS','TM','EC','CG','Ext','RN') default NULL,
  `responsable` varchar(64) default NULL,
  `semestre` enum('A','P','AP','closed') NOT NULL,
  `guide_objectifs` text,
  `guide_programme` text,
  `guide_c` tinyint(4) default NULL,
  `guide_td` tinyint(4) default NULL,
  `guide_tp` tinyint(4) default NULL,
  `guide_the` tinyint(4) default NULL,
  `guide_credits` tinyint(4) default NULL,
  `state` enum('VALID','PENDING','MODIFIED') NOT NULL,
  `tc_available` tinyint(1) NOT NULL default '1',
  PRIMARY KEY  (`id_uv`),
  KEY `code` (`code`)
) ENGINE=MyISAM AUTO_INCREMENT=400 DEFAULT CHARSET=latin1 COMMENT='Liste des UV disponibles ou non' AUTO_INCREMENT=400 ;

-- --------------------------------------------------------

-- 
-- Table structure for table `pedag_uv_alias`
-- 

CREATE TABLE `pedag_uv_alias` (
  `id_uv_source` int(11) NOT NULL,
  `id_uv_cible` int(11) NOT NULL,
  `commentaire` text,
  PRIMARY KEY  (`id_uv_source`,`id_uv_cible`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- 
-- Table structure for table `pedag_uv_antecedent`
-- 

CREATE TABLE `pedag_uv_antecedent` (
  `id_uv_source` int(11) NOT NULL,
  `id_uv_cible` int(11) NOT NULL,
  `commentaire` text,
  `obligatoire` tinyint(1) NOT NULL default '0',
  PRIMARY KEY  (`id_uv_source`,`id_uv_cible`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- 
-- Table structure for table `pedag_uv_commentaire`
-- 

CREATE TABLE `pedag_uv_commentaire` (
  `id_commentaire` int(11) NOT NULL auto_increment,
  `id_uv` int(11) NOT NULL,
  `id_utilisateur` int(11) NOT NULL,
  `note_generale` enum('-1','0','1','2','3','4') default NULL,
  `note_utilite` enum('-1','0','1','2','3','4') default NULL,
  `note_interet` enum('-1','0','1','2','3','4') default NULL,
  `note_enseignement` enum('-1','0','1','2','3','4') default NULL,
  `note_travail` enum('-1','0','1','2','3','4') default NULL,
  `content` text,
  `date` datetime NOT NULL,
  `valid` tinyint(1) NOT NULL default '1',
  `eval_comment` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id_commentaire`)
) ENGINE=MyISAM AUTO_INCREMENT=437 DEFAULT CHARSET=latin1 COMMENT='Commentaires sur les UV' AUTO_INCREMENT=437 ;

-- --------------------------------------------------------

-- 
-- Table structure for table `pedag_uv_cursus`
-- 

CREATE TABLE `pedag_uv_cursus` (
  `id_uv` int(11) NOT NULL,
  `id_cursus` int(11) NOT NULL,
  `relation` enum('SOME_OF','ALL_OF') NOT NULL,
  PRIMARY KEY  (`id_uv`,`id_cursus`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- 
-- Table structure for table `pedag_uv_dept`
-- 

CREATE TABLE `pedag_uv_dept` (
  `id_uv` int(11) NOT NULL,
  `departement` enum('Humas','TC','GI','GESC','IMAP','MC','EDIM') NOT NULL default 'Humas',
  PRIMARY KEY  (`id_uv`,`departement`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- 
-- Table structure for table `perms`
-- 

CREATE TABLE `perms` (
  `id` int(11) NOT NULL auto_increment,
  `nom` varchar(255) NOT NULL,
  `shift` int(11) NOT NULL default '0',
  `drink` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=latin1 AUTO_INCREMENT=2 ;

-- --------------------------------------------------------

-- 
-- Table structure for table `pg_category`
-- 

CREATE TABLE `pg_category` (
  `id_pgcategory` int(11) NOT NULL auto_increment,
  `id_pgcategory_parent` int(11) default NULL,
  `nom_pgcategory` varchar(32) NOT NULL,
  `description_pgcategory` varchar(128) NOT NULL,
  `ordre_pgcategory` int(11) NOT NULL default '1',
  `couleur_bordure_web_pgcategory` varchar(6) default NULL,
  `couleur_titre_web_pgcategory` varchar(6) default NULL,
  `couleur_contraste_web_pgcategory` varchar(6) default NULL,
  `couleur_bordure_print_pgcategory` varchar(10) default NULL,
  `couleur_titre_print_pgcategory` varchar(10) default NULL,
  `couleur_contraste_print_pgcategory` varchar(10) default NULL,
  PRIMARY KEY  (`id_pgcategory`),
  KEY `id_pgcategory_parent` (`id_pgcategory_parent`)
) ENGINE=MyISAM AUTO_INCREMENT=222 DEFAULT CHARSET=latin1 AUTO_INCREMENT=222 ;

-- --------------------------------------------------------

-- 
-- Table structure for table `pg_category_tags`
-- 

CREATE TABLE `pg_category_tags` (
  `id_tag` int(11) NOT NULL,
  `id_pgcategory` int(11) NOT NULL,
  PRIMARY KEY  (`id_tag`,`id_pgcategory`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- 
-- Table structure for table `pg_fiche`
-- 

CREATE TABLE `pg_fiche` (
  `id_pgfiche` int(11) NOT NULL,
  `id_pgcategory` int(11) NOT NULL,
  `id_rue` int(11) NOT NULL,
  `id_entreprise` int(11) default NULL,
  `description_pgfiche` text NOT NULL,
  `longuedescription_pgfiche` text NOT NULL,
  `tel_pgfiche` varchar(32) NOT NULL,
  `fax_pgfiche` varchar(32) NOT NULL,
  `email_pgfiche` varchar(128) NOT NULL,
  `website_pgfiche` varchar(128) NOT NULL,
  `numrue_pgfiche` varchar(16) NOT NULL,
  `adressepostal_pgfiche` varchar(128) NOT NULL,
  `placesurcarte_pgfiche` enum('0','1') NOT NULL default '0',
  `contraste_pgfiche` enum('0','1') NOT NULL default '0',
  `appreciation_pgfiche` tinyint(4) default NULL,
  `commentaire_pgfiche` varchar(128) default NULL,
  `date_maj_pgfiche` datetime NOT NULL,
  `date_validite_pgfiche` datetime default NULL,
  `id_utilisateur_maj` int(11) default NULL,
  `archive_pgfiche` enum('0','1') NOT NULL default '0',
  `infointerne_pgfiche` varchar(128) default NULL,
  PRIMARY KEY  (`id_pgfiche`),
  KEY `id_pgcategory` (`id_pgcategory`),
  KEY `id_utilisateur_maj` (`id_utilisateur_maj`),
  KEY `id_rue` (`id_rue`),
  KEY `id_entreprise` (`id_entreprise`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- 
-- Table structure for table `pg_fiche_arretbus`
-- 

CREATE TABLE `pg_fiche_arretbus` (
  `id_pgfiche` int(11) NOT NULL,
  `id_arretbus` int(11) NOT NULL,
  PRIMARY KEY  (`id_pgfiche`,`id_arretbus`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- 
-- Table structure for table `pg_fiche_extra_pgcategory`
-- 

CREATE TABLE `pg_fiche_extra_pgcategory` (
  `id_pgfiche` int(11) NOT NULL,
  `id_pgcategory` int(11) NOT NULL,
  `titre_extra_pgcategory` varchar(128) default NULL,
  `soustire_extra_pgcategory` varchar(128) default NULL,
  PRIMARY KEY  (`id_pgfiche`,`id_pgcategory`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- 
-- Table structure for table `pg_fiche_reduction`
-- 

CREATE TABLE `pg_fiche_reduction` (
  `id_pgfiche` int(11) NOT NULL,
  `id_typereduction` int(11) NOT NULL,
  `valeur_reduction` varchar(32) NOT NULL,
  `unite_reduction` varchar(32) NOT NULL,
  `commentaire_reduction` varchar(128) NOT NULL,
  `date_maj_reduction` datetime default NULL,
  `date_validite_reduction` datetime default NULL,
  PRIMARY KEY  (`id_pgfiche`,`id_typereduction`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- 
-- Table structure for table `pg_fiche_service`
-- 

CREATE TABLE `pg_fiche_service` (
  `id_pgfiche` int(11) NOT NULL,
  `id_service` int(11) NOT NULL,
  `commentaire_service` varchar(128) NOT NULL,
  `date_maj_service` datetime default NULL,
  `date_validite_service` datetime default NULL,
  PRIMARY KEY  (`id_pgfiche`,`id_service`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- 
-- Table structure for table `pg_fiche_tags`
-- 

CREATE TABLE `pg_fiche_tags` (
  `id_tag` int(11) NOT NULL,
  `id_pgfiche` int(11) NOT NULL,
  PRIMARY KEY  (`id_tag`,`id_pgfiche`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- 
-- Table structure for table `pg_fiche_tarif`
-- 

CREATE TABLE `pg_fiche_tarif` (
  `id_pgfiche` int(11) NOT NULL,
  `id_typetarif` int(11) NOT NULL,
  `min_tarif` int(11) NOT NULL,
  `max_tarif` int(11) NOT NULL,
  `commentaire_tarif` varchar(128) NOT NULL,
  `date_maj_tarif` datetime default NULL,
  `date_validite_tarif` datetime default NULL,
  PRIMARY KEY  (`id_pgfiche`,`id_typetarif`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- 
-- Table structure for table `pg_lignebus`
-- 

CREATE TABLE `pg_lignebus` (
  `id_lignebus` int(11) NOT NULL auto_increment,
  `id_reseaubus` int(11) NOT NULL,
  `id_lignebus_parent` int(11) default NULL,
  `nom_lignebus` varchar(32) NOT NULL,
  `couleur_lignebus` varchar(6) NOT NULL default '000000',
  PRIMARY KEY  (`id_lignebus`),
  KEY `id_reseaubus` (`id_reseaubus`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

-- 
-- Table structure for table `pg_lignebus_arrets`
-- 

CREATE TABLE `pg_lignebus_arrets` (
  `id_lignebus` int(11) NOT NULL,
  `id_arretbus` int(11) NOT NULL,
  `num_passage_arret` int(11) NOT NULL,
  PRIMARY KEY  (`id_lignebus`,`id_arretbus`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- 
-- Table structure for table `pg_lignebus_passage`
-- 

CREATE TABLE `pg_lignebus_passage` (
  `id_lignebus_passage` int(11) NOT NULL auto_increment,
  `id_lignebus` int(11) NOT NULL,
  `jours_passage` int(11) NOT NULL,
  `debut_passage` datetime NOT NULL,
  `fin_passage` datetime NOT NULL,
  `id_arretbus_debut` int(11) NOT NULL,
  `id_arretbus_fin` int(11) NOT NULL,
  `sens_passage` smallint(6) NOT NULL default '0',
  `exception_passage` smallint(6) NOT NULL default '0',
  PRIMARY KEY  (`id_lignebus_passage`),
  KEY `id_lignebus` (`id_lignebus`),
  KEY `id_arretbus_debut` (`id_arretbus_debut`),
  KEY `id_arretbus_fin` (`id_arretbus_fin`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

-- 
-- Table structure for table `pg_lignebus_passage_arretbus`
-- 

CREATE TABLE `pg_lignebus_passage_arretbus` (
  `id_lignebus_passage` int(11) NOT NULL,
  `id_arretbus` int(11) NOT NULL,
  `heure_passage` time NOT NULL,
  PRIMARY KEY  (`id_lignebus_passage`,`id_arretbus`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- 
-- Table structure for table `pg_reseaubus`
-- 

CREATE TABLE `pg_reseaubus` (
  `id_reseaubus` int(11) NOT NULL auto_increment,
  `id_reseaubus_parent` int(11) default NULL,
  `nom_reseaubus` varchar(32) NOT NULL,
  `siteweb_reseaubus` varchar(64) NOT NULL,
  PRIMARY KEY  (`id_reseaubus`),
  KEY `id_reseaubus_parent` (`id_reseaubus_parent`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=latin1 AUTO_INCREMENT=5 ;

-- --------------------------------------------------------

-- 
-- Table structure for table `pg_rue`
-- 

CREATE TABLE `pg_rue` (
  `id_rue` int(11) NOT NULL auto_increment,
  `nom_rue` varchar(64) NOT NULL,
  `id_typerue` int(11) NOT NULL,
  `id_ville` int(11) NOT NULL,
  `id_rue_entree` int(11) default NULL,
  `num_entree_rue` varchar(16) default NULL,
  `complement_rue` varchar(16) default NULL,
  PRIMARY KEY  (`id_rue`),
  KEY `id_typerue` (`id_typerue`),
  KEY `id_ville` (`id_ville`),
  KEY `id_rue_entree` (`id_rue_entree`)
) ENGINE=MyISAM AUTO_INCREMENT=981 DEFAULT CHARSET=latin1 AUTO_INCREMENT=981 ;

-- --------------------------------------------------------

-- 
-- Table structure for table `pg_service`
-- 

CREATE TABLE `pg_service` (
  `id_service` int(11) NOT NULL auto_increment,
  `nom_service` varchar(64) NOT NULL,
  `description_service` text NOT NULL,
  `website_service` varchar(128) NOT NULL,
  PRIMARY KEY  (`id_service`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=latin1 AUTO_INCREMENT=2 ;

-- --------------------------------------------------------

-- 
-- Table structure for table `pg_typereduction`
-- 

CREATE TABLE `pg_typereduction` (
  `id_typereduction` int(11) NOT NULL auto_increment,
  `nom_typereduction` varchar(64) NOT NULL,
  `description_typereduction` text NOT NULL,
  `website_typereduction` varchar(128) NOT NULL,
  PRIMARY KEY  (`id_typereduction`)
) ENGINE=MyISAM AUTO_INCREMENT=10 DEFAULT CHARSET=latin1 AUTO_INCREMENT=10 ;

-- --------------------------------------------------------

-- 
-- Table structure for table `pg_typerue`
-- 

CREATE TABLE `pg_typerue` (
  `id_typerue` int(11) NOT NULL auto_increment,
  `nom_typerue` varchar(64) NOT NULL,
  PRIMARY KEY  (`id_typerue`)
) ENGINE=MyISAM AUTO_INCREMENT=88 DEFAULT CHARSET=latin1 AUTO_INCREMENT=88 ;

-- --------------------------------------------------------

-- 
-- Table structure for table `pg_typetarif`
-- 

CREATE TABLE `pg_typetarif` (
  `id_typetarif` int(11) NOT NULL auto_increment,
  `nom_typetarif` varchar(64) NOT NULL,
  `description_typetarif` text NOT NULL,
  `id_typetarif_parent` int(11) default NULL,
  PRIMARY KEY  (`id_typetarif`),
  KEY `id_typetarif_parent` (`id_typetarif_parent`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=latin1 AUTO_INCREMENT=2 ;

-- --------------------------------------------------------

-- 
-- Table structure for table `pl_gap`
-- 

CREATE TABLE `pl_gap` (
  `id_gap` int(11) NOT NULL auto_increment,
  `id_planning` int(11) NOT NULL default '0',
  `start_gap` datetime default NULL,
  `end_gap` datetime default NULL,
  PRIMARY KEY  (`id_gap`,`id_planning`)
) ENGINE=MyISAM AUTO_INCREMENT=16743 DEFAULT CHARSET=latin1 AUTO_INCREMENT=16743 ;

-- --------------------------------------------------------

-- 
-- Table structure for table `pl_gap_user`
-- 

CREATE TABLE `pl_gap_user` (
  `id_planning` int(11) NOT NULL default '0',
  `id_gap` int(11) NOT NULL default '0',
  `id_utilisateur` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id_planning`,`id_gap`,`id_utilisateur`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- 
-- Table structure for table `pl_planning`
-- 

CREATE TABLE `pl_planning` (
  `id_planning` int(11) NOT NULL auto_increment,
  `id_asso` int(11) NOT NULL default '0',
  `name_planning` varchar(64) NOT NULL default '',
  `user_per_gap` smallint(6) NOT NULL default '0',
  `start_date_planning` datetime default NULL,
  `end_date_planning` datetime default NULL,
  `weekly_planning` enum('1','0') NOT NULL default '0',
  PRIMARY KEY  (`id_planning`),
  KEY `id_asso` (`id_asso`)
) ENGINE=MyISAM AUTO_INCREMENT=193 DEFAULT CHARSET=latin1 AUTO_INCREMENT=193 ;

-- --------------------------------------------------------

-- 
-- Table structure for table `planet_flux`
-- 

CREATE TABLE `planet_flux` (
  `id_flux` int(6) NOT NULL auto_increment,
  `url` text NOT NULL,
  `nom` varchar(150) NOT NULL,
  `id_utilisateur` int(6) NOT NULL,
  `modere` enum('0','1') default '0',
  `type` enum('RSS','ATOM') NOT NULL default 'RSS',
  PRIMARY KEY  (`id_flux`),
  UNIQUE KEY `url` (`url`(512))
) ENGINE=MyISAM AUTO_INCREMENT=18 DEFAULT CHARSET=latin1 AUTO_INCREMENT=18 ;

-- --------------------------------------------------------

-- 
-- Table structure for table `planet_flux_tags`
-- 

CREATE TABLE `planet_flux_tags` (
  `id_flux` int(6) NOT NULL,
  `id_tag` int(6) NOT NULL,
  PRIMARY KEY  (`id_flux`,`id_tag`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- 
-- Table structure for table `planet_tags`
-- 

CREATE TABLE `planet_tags` (
  `id_tag` int(6) NOT NULL auto_increment,
  `tag` varchar(32) NOT NULL,
  `modere` enum('0','1') NOT NULL default '0',
  PRIMARY KEY  (`id_tag`),
  UNIQUE KEY `tag` (`tag`)
) ENGINE=MyISAM AUTO_INCREMENT=9 DEFAULT CHARSET=latin1 AUTO_INCREMENT=9 ;

-- --------------------------------------------------------

-- 
-- Table structure for table `planet_user_flux`
-- 

CREATE TABLE `planet_user_flux` (
  `id_utilisateur` int(6) NOT NULL,
  `id_flux` int(6) NOT NULL,
  `view` enum('0','1') NOT NULL default '1',
  PRIMARY KEY  (`id_utilisateur`,`id_flux`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- 
-- Table structure for table `planet_user_tags`
-- 

CREATE TABLE `planet_user_tags` (
  `id_utilisateur` int(6) NOT NULL,
  `id_tag` int(6) NOT NULL,
  PRIMARY KEY  (`id_utilisateur`,`id_tag`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- 
-- Table structure for table `pre_parrainage`
-- 

CREATE TABLE `pre_parrainage` (
  `semestre` varchar(3) NOT NULL,
  `id_utilisateur` int(6) NOT NULL,
  `tc` enum('0','1') NOT NULL default '0',
  `branche` varchar(6) NOT NULL,
  `id_utilisateur_parrain` int(6) default NULL,
  PRIMARY KEY  (`semestre`,`id_utilisateur`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- 
-- Table structure for table `pull_participations`
-- 

CREATE TABLE `pull_participations` (
  `id_participation` int(11) NOT NULL auto_increment,
  `nom` varchar(255) NOT NULL,
  `prenom` varchar(255) NOT NULL,
  `date_de_naissance` date NOT NULL,
  `email` varchar(255) NOT NULL,
  `telephone` varchar(15) NOT NULL,
  `adresse_rue` varchar(255) NOT NULL,
  `adresse_additional` varchar(255) NOT NULL,
  `adresse_ville` varchar(255) NOT NULL,
  `adresse_codepostal` varchar(5) NOT NULL,
  `contribution_nom` varchar(255) NOT NULL,
  `contribution_parent` varchar(255) NOT NULL,
  `contribution_siteweb` varchar(255) NOT NULL,
  `contribution_depot` varchar(255) NOT NULL,
  `contribution_description` mediumtext NOT NULL,
  `univ` varchar(100) NOT NULL default 'NA',
  `role_univ` varchar(50) NOT NULL default 'NA',
  PRIMARY KEY  (`id_participation`),
  UNIQUE KEY `email` (`email`)
) ENGINE=MyISAM AUTO_INCREMENT=19 DEFAULT CHARSET=latin1 AUTO_INCREMENT=19 ;

-- --------------------------------------------------------

-- 
-- Table structure for table `sas_cat_photos`
-- 

CREATE TABLE `sas_cat_photos` (
  `id_catph` int(11) NOT NULL auto_increment,
  `id_groupe` int(11) NOT NULL default '0',
  `id_utilisateur` int(11) NOT NULL default '0',
  `id_groupe_admin` int(11) NOT NULL default '0',
  `id_catph_parent` int(11) default NULL,
  `id_photo` int(11) default NULL,
  `nom_catph` varchar(128) NOT NULL default '',
  `date_debut_catph` datetime default '0000-00-00 00:00:00',
  `date_fin_catph` datetime default '0000-00-00 00:00:00',
  `droits_acces_catph` int(32) NOT NULL default '0',
  `modere_catph` tinyint(1) NOT NULL default '0',
  `meta_id_asso_catph` int(11) default NULL,
  `meta_mode_catph` int(1) default NULL,
  `id_lieu` int(11) default NULL,
  PRIMARY KEY  (`id_catph`),
  KEY `fk_sas_cat_photos_groupe` (`id_groupe`),
  KEY `fk_sas_cat_photos_utilisateurs` (`id_utilisateur`),
  KEY `fk_sas_cat_photos_groupe1` (`id_groupe_admin`),
  KEY `fk_sas_cat_photos_sas_cat_photos` (`id_catph_parent`),
  KEY `id_lieu` (`id_lieu`),
  KEY `droits_acces_catph` (`droits_acces_catph`),
  KEY `modere_catph` (`modere_catph`)
) ENGINE=MyISAM AUTO_INCREMENT=1131 DEFAULT CHARSET=latin1 AUTO_INCREMENT=1131 ;

-- --------------------------------------------------------

-- 
-- Table structure for table `sas_palette`
-- 

CREATE TABLE `sas_palette` (
  `r` int(3) unsigned NOT NULL default '0',
  `g` int(3) unsigned NOT NULL default '0',
  `b` int(3) unsigned NOT NULL default '0',
  `idx` int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (`idx`),
  KEY `r` (`r`),
  KEY `g` (`g`),
  KEY `b` (`b`)
) ENGINE=MEMORY DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- 
-- Table structure for table `sas_palette_photos`
-- 

CREATE TABLE `sas_palette_photos` (
  `idx` int(11) NOT NULL default '0',
  `id_photo` int(11) NOT NULL default '0',
  PRIMARY KEY  (`idx`,`id_photo`),
  UNIQUE KEY `id_photo` (`id_photo`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- 
-- Table structure for table `sas_personnes_photos`
-- 

CREATE TABLE `sas_personnes_photos` (
  `id_photo` int(8) NOT NULL default '0',
  `id_utilisateur` int(8) NOT NULL default '0',
  `accord_phutl` enum('0','1') default '0',
  `modere_phutl` enum('0','1') default '0',
  `vu_phutl` enum('0','1') NOT NULL default '0',
  PRIMARY KEY  (`id_photo`,`id_utilisateur`),
  KEY `fk_sas_personnes_photos_utilisateurs` (`id_utilisateur`),
  KEY `vu_phutl` (`vu_phutl`),
  KEY `modere_phutl` (`modere_phutl`),
  KEY `accord_phutl` (`accord_phutl`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- 
-- Table structure for table `sas_photos`
-- 

CREATE TABLE `sas_photos` (
  `id_photo` int(11) NOT NULL auto_increment,
  `id_catph` int(11) NOT NULL default '0',
  `id_utilisateur` int(11) NOT NULL default '0',
  `id_groupe` int(11) NOT NULL default '0',
  `id_groupe_admin` int(11) NOT NULL default '0',
  `id_utilisateur_photographe` int(11) default NULL,
  `id_licence` int(11) default NULL,
  `date_prise_vue` datetime default '0000-00-00 00:00:00',
  `modere_ph` tinyint(1) NOT NULL default '0',
  `commentaire_ph` text,
  `droits_acces_ph` int(16) NOT NULL default '0',
  `incomplet` tinyint(1) default NULL,
  `droits_acquis` tinyint(1) default NULL,
  `couleur_moyenne` int(11) default NULL,
  `classification` int(11) default NULL,
  `supprime_ph` tinyint(1) NOT NULL default '0',
  `meta_id_asso_ph` int(11) default NULL,
  `date_ajout_ph` datetime default NULL,
  `id_utilisateur_moderateur` int(11) default NULL,
  `type_media_ph` enum('0','1') NOT NULL default '0',
  `titre_ph` varchar(80) default NULL,
  `id_asso_photographe` int(11) default NULL,
  `iso` int(4) default NULL,
  `focale` int(5) default NULL,
  `flash` int(1) default '-1',
  `exposuretime` varchar(30) default NULL,
  `aperture` varchar(30) default NULL,
  `ouverture` varchar(10) default NULL,
  `manufacturer` varchar(255) default NULL,
  `model` varchar(255) default NULL,
  PRIMARY KEY  (`id_photo`),
  KEY `fk_sas_photos_sas_cat_photos` (`id_catph`),
  KEY `fk_sas_photos_utilisateurs` (`id_utilisateur`),
  KEY `fk_sas_photos_groupe` (`id_groupe`),
  KEY `fk_sas_photos_utilisateurs1` (`id_utilisateur_photographe`),
  KEY `id_asso_photographe` (`id_asso_photographe`),
  KEY `modere_ph` (`modere_ph`),
  KEY `incomplet` (`incomplet`),
  KEY `droits_acquis` (`droits_acquis`),
  KEY `droits_acces_ph` (`droits_acces_ph`)
) ENGINE=MyISAM AUTO_INCREMENT=100190 DEFAULT CHARSET=latin1 AUTO_INCREMENT=100190 ;

-- --------------------------------------------------------

-- 
-- Table structure for table `sas_photos_tag`
-- 

CREATE TABLE `sas_photos_tag` (
  `id_tag` int(11) NOT NULL,
  `id_photo` int(11) NOT NULL,
  PRIMARY KEY  (`id_tag`,`id_photo`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- 
-- Table structure for table `sas_retrait`
-- 

CREATE TABLE `sas_retrait` (
  `id_retrait` int(11) NOT NULL auto_increment,
  `id_utilisateur` int(11) NOT NULL default '0',
  `id_photo` int(11) NOT NULL default '0',
  `date_retrait` datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`id_retrait`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

-- 
-- Table structure for table `sdn_a_repondu`
-- 

CREATE TABLE `sdn_a_repondu` (
  `id_sondage` int(11) NOT NULL default '0',
  `id_utilisateur` int(11) NOT NULL default '0',
  `date_reponse` datetime default NULL,
  PRIMARY KEY  (`id_sondage`,`id_utilisateur`),
  KEY `fk_sdn_a_repondu_utilisateurs` (`id_utilisateur`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- 
-- Table structure for table `sdn_reponse`
-- 

CREATE TABLE `sdn_reponse` (
  `num_reponse` int(11) NOT NULL auto_increment,
  `id_sondage` int(11) NOT NULL default '0',
  `nom_reponse` varchar(128) default NULL,
  `nb_reponse` varchar(32) default NULL,
  PRIMARY KEY  (`num_reponse`),
  KEY `fk_sdn_reponse_sdn_sondage` (`id_sondage`)
) ENGINE=MyISAM AUTO_INCREMENT=130 DEFAULT CHARSET=latin1 AUTO_INCREMENT=130 ;

-- --------------------------------------------------------

-- 
-- Table structure for table `sdn_sondage`
-- 

CREATE TABLE `sdn_sondage` (
  `id_sondage` int(11) NOT NULL auto_increment,
  `question` varchar(128) default NULL,
  `total_reponses` varchar(32) default NULL,
  `date_sondage` date default NULL,
  `date_fin` date default NULL,
  PRIMARY KEY  (`id_sondage`),
  KEY `date_fin` (`date_fin`)
) ENGINE=MyISAM AUTO_INCREMENT=28 DEFAULT CHARSET=latin1 AUTO_INCREMENT=28 ;

-- --------------------------------------------------------

-- 
-- Table structure for table `secteur`
-- 

CREATE TABLE `secteur` (
  `id_secteur` int(11) NOT NULL auto_increment,
  `nom_secteur` varchar(64) NOT NULL,
  PRIMARY KEY  (`id_secteur`)
) ENGINE=MyISAM AUTO_INCREMENT=14 DEFAULT CHARSET=latin1 AUTO_INCREMENT=14 ;

-- --------------------------------------------------------

-- 
-- Table structure for table `site_boites`
-- 

CREATE TABLE `site_boites` (
  `nom_boite` varchar(32) NOT NULL default '',
  `contenu_boite` text NOT NULL,
  `description_boite` text NOT NULL,
  PRIMARY KEY  (`nom_boite`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- 
-- Table structure for table `site_parametres`
-- 

CREATE TABLE `site_parametres` (
  `nom_param` varchar(32) NOT NULL default '',
  `valeur_param` text NOT NULL,
  `description_param` text NOT NULL,
  PRIMARY KEY  (`nom_param`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- 
-- Table structure for table `site_sessions`
-- 

CREATE TABLE `site_sessions` (
  `id_session` varchar(64) NOT NULL default '',
  `id_utilisateur` int(11) NOT NULL default '0',
  `date_debut_sess` datetime NOT NULL default '0000-00-00 00:00:00',
  `derniere_visite` datetime NOT NULL default '0000-00-00 00:00:00',
  `connecte_sess` enum('1','0') NOT NULL default '1',
  `expire_sess` datetime default NULL,
  PRIMARY KEY  (`id_session`),
  KEY `fk_site_sessions_utilisateurs` (`id_utilisateur`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- 
-- Table structure for table `sl_association`
-- 

CREATE TABLE `sl_association` (
  `id_asso` int(11) NOT NULL default '0',
  `id_salle` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id_asso`,`id_salle`),
  KEY `fk_sl_association_sl_salle` (`id_salle`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- 
-- Table structure for table `sl_batiment`
-- 

CREATE TABLE `sl_batiment` (
  `id_batiment` int(11) NOT NULL auto_increment,
  `id_site` int(11) NOT NULL default '0',
  `nom_bat` varchar(128) NOT NULL default '',
  `bat_fumeur` int(32) NOT NULL default '0',
  `convention_bat` tinyint(1) NOT NULL default '0',
  `notes_bat` text,
  PRIMARY KEY  (`id_batiment`),
  KEY `fk_sl_batiment_sl_site` (`id_site`)
) ENGINE=MyISAM AUTO_INCREMENT=11 DEFAULT CHARSET=latin1 PACK_KEYS=0 AUTO_INCREMENT=11 ;

-- --------------------------------------------------------

-- 
-- Table structure for table `sl_reservation`
-- 

CREATE TABLE `sl_reservation` (
  `id_salres` int(11) NOT NULL auto_increment,
  `id_utilisateur` int(11) NOT NULL default '0',
  `id_utilisateur_op` int(11) default NULL,
  `id_salle` int(11) NOT NULL default '0',
  `id_asso` int(11) default NULL,
  `date_demande_res` datetime NOT NULL default '0000-00-00 00:00:00',
  `date_debut_salres` datetime NOT NULL default '0000-00-00 00:00:00',
  `date_fin_salres` datetime NOT NULL default '0000-00-00 00:00:00',
  `date_accord_res` datetime default NULL,
  `description_salres` text NOT NULL,
  `convention_salres` tinyint(1) NOT NULL default '0',
  `etat_salres` int(32) NOT NULL default '0',
  `notes_salres` text,
  PRIMARY KEY  (`id_salres`),
  KEY `fk_sl_reservation_utilisateurs` (`id_utilisateur`),
  KEY `fk_sl_reservation_utilisateurs1` (`id_utilisateur_op`),
  KEY `fk_sl_reservation_sl_salle` (`id_salle`),
  KEY `fk_sl_reservation_asso` (`id_asso`),
  KEY `convention_salres` (`convention_salres`),
  KEY `date_accord_res` (`date_accord_res`),
  KEY `date_debut_salres` (`date_debut_salres`)
) ENGINE=MyISAM AUTO_INCREMENT=2902 DEFAULT CHARSET=latin1 AUTO_INCREMENT=2902 ;

-- --------------------------------------------------------

-- 
-- Table structure for table `sl_salle`
-- 

CREATE TABLE `sl_salle` (
  `id_salle` int(11) NOT NULL auto_increment,
  `id_batiment` int(11) NOT NULL default '0',
  `nom_salle` varchar(128) NOT NULL default '',
  `etage` int(32) NOT NULL default '0',
  `salle_fumeur` tinyint(1) NOT NULL default '0',
  `convention_salle` tinyint(1) NOT NULL default '0',
  `reservable` tinyint(1) NOT NULL default '0',
  `surface_salle` int(32) NOT NULL default '0',
  `tel_salle` varchar(32) NOT NULL default '',
  `notes_salle` text,
  PRIMARY KEY  (`id_salle`),
  KEY `fk_sl_salle_sl_batiment` (`id_batiment`),
  KEY `convention_salle` (`convention_salle`)
) ENGINE=MyISAM AUTO_INCREMENT=44 DEFAULT CHARSET=latin1 AUTO_INCREMENT=44 ;

-- --------------------------------------------------------

-- 
-- Table structure for table `sl_site`
-- 

CREATE TABLE `sl_site` (
  `id_site` int(11) NOT NULL auto_increment,
  `nom_site` varchar(128) NOT NULL default '',
  `site_fumeur` tinyint(1) NOT NULL default '0',
  `convention_site` tinyint(1) NOT NULL default '0',
  `notes_site` text,
  `id_ville` int(11) default NULL,
  PRIMARY KEY  (`id_site`)
) ENGINE=MyISAM AUTO_INCREMENT=7 DEFAULT CHARSET=latin1 AUTO_INCREMENT=7 ;

-- --------------------------------------------------------

-- 
-- Table structure for table `sms_translations`
-- 

CREATE TABLE `sms_translations` (
  `sms` varchar(26) NOT NULL,
  `french` varchar(255) default NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- 
-- Table structure for table `sso_api_keys`
-- 

CREATE TABLE `sso_api_keys` (
  `key` varchar(255) character set utf8 collate utf8_unicode_ci NOT NULL default '',
  `detail` varchar(32) default NULL,
  `allow_inscription` enum('0','1') default '0',
  `https` enum('0','1') NOT NULL default '1',
  PRIMARY KEY  (`key`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- 
-- Table structure for table `stats_browser`
-- 

CREATE TABLE `stats_browser` (
  `browser` varchar(20) NOT NULL default '',
  `visites` int(6) NOT NULL,
  PRIMARY KEY  (`browser`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- 
-- Table structure for table `stats_os`
-- 

CREATE TABLE `stats_os` (
  `os` varchar(20) NOT NULL,
  `visites` int(6) NOT NULL,
  PRIMARY KEY  (`os`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- 
-- Table structure for table `stats_page`
-- 

CREATE TABLE `stats_page` (
  `page` varchar(255) NOT NULL,
  `visites` int(6) NOT NULL,
  PRIMARY KEY  (`page`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- 
-- Table structure for table `tag`
-- 

CREATE TABLE `tag` (
  `id_tag` int(11) NOT NULL auto_increment,
  `nom_tag` varchar(96) NOT NULL,
  `modere_tag` enum('0','1') NOT NULL default '0',
  `nombre_tag` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id_tag`),
  KEY `nom_tag` (`nom_tag`)
) ENGINE=MyISAM AUTO_INCREMENT=3023 DEFAULT CHARSET=latin1 AUTO_INCREMENT=3023 ;

-- --------------------------------------------------------

-- 
-- Table structure for table `tracking`
-- 

CREATE TABLE `tracking` (
  `id_ticket` int(11) NOT NULL auto_increment,
  `title_ticket` varchar(128) NOT NULL,
  `type_ticket` int(11) NOT NULL,
  `content_ticket` text NOT NULL,
  `private_ticket` int(1) NOT NULL,
  `component_ticket` int(11) NOT NULL,
  `last_ticket_history` int(11) NOT NULL,
  `id_utilisateur_owner` int(11) NOT NULL,
  PRIMARY KEY  (`id_ticket`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

-- 
-- Table structure for table `tracking_history`
-- 

CREATE TABLE `tracking_history` (
  `id_ticket_history` int(11) NOT NULL auto_increment,
  `priority_ticket` int(11) NOT NULL,
  `status_ticket` int(11) NOT NULL,
  `comment_ticket` text,
  `date_ticket_history` date default NULL,
  `id_ticket` int(11) NOT NULL,
  `id_utilisateur_reporter` int(11) NOT NULL,
  PRIMARY KEY  (`id_ticket_history`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

-- 
-- Table structure for table `trombi_commentaire`
-- 

CREATE TABLE `trombi_commentaire` (
  `id_commentaire` int(11) NOT NULL auto_increment,
  `id_commente` int(11) NOT NULL,
  `id_commentateur` int(11) NOT NULL,
  `commentaire` text NOT NULL,
  `date_commentaire` datetime NOT NULL,
  `modere_commentaire` enum('0','1') NOT NULL default '0',
  `id_utilisateur_moderateur` int(11) default NULL,
  PRIMARY KEY  (`id_commentaire`),
  UNIQUE KEY `U_TROMBI_COMMENTAIRE` (`id_commente`,`id_commentateur`)
) ENGINE=MyISAM AUTO_INCREMENT=568 DEFAULT CHARSET=latin1 COMMENT='Table des commentaires du trombinoscope des promos' AUTO_INCREMENT=568 ;

-- --------------------------------------------------------

-- 
-- Table structure for table `utbm_planning`
-- 

CREATE TABLE `utbm_planning` (
  `id_planning_evt` int(11) NOT NULL auto_increment,
  `id_type_planning` int(2) default NULL,
  `date_debut` datetime NOT NULL,
  `date_fin` datetime NOT NULL,
  `id_entity` int(11) default NULL,
  PRIMARY KEY  (`id_planning_evt`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='Planning UTBM' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

-- 
-- Table structure for table `utl_etu`
-- 

CREATE TABLE `utl_etu` (
  `id_utilisateur` int(11) NOT NULL default '0',
  `citation` text,
  `adresse_parents` varchar(128) default NULL,
  `ville_parents` varchar(128) default NULL,
  `cpostal_parents` varchar(32) default NULL,
  `pays_parents` varchar(128) default NULL,
  `tel_parents` varchar(128) default NULL,
  `nom_ecole_etudiant` varchar(128) default NULL,
  `visites` int(6) default '0',
  `id_ville` int(11) default NULL,
  `id_pays` int(11) default NULL,
  PRIMARY KEY  (`id_utilisateur`),
  KEY `id_ville` (`id_ville`),
  KEY `id_pays` (`id_pays`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- 
-- Table structure for table `utl_etu_utbm`
-- 

CREATE TABLE `utl_etu_utbm` (
  `id_utilisateur` int(11) NOT NULL default '0',
  `semestre_utbm` int(2) default NULL,
  `branche_utbm` varchar(6) default NULL,
  `filiere_utbm` varchar(6) default NULL,
  `surnom_utbm` varchar(128) default NULL,
  `email_utbm` varchar(128) default NULL,
  `promo_utbm` int(2) default NULL,
  `date_diplome_utbm` date default NULL,
  `role_utbm` varchar(3) default 'etu',
  `departement_utbm` varchar(4) default 'na',
  PRIMARY KEY  (`id_utilisateur`),
  KEY `email_utbm` (`email_utbm`),
  KEY `surnom_utbm` (`surnom_utbm`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- 
-- Table structure for table `utl_extra`
-- 

CREATE TABLE `utl_extra` (
  `id_utilisateur` int(11) NOT NULL,
  `musicien_utl` enum('0','1') default NULL,
  `taille_tshirt_utl` enum('XS','S','M','L','XL','XXL','XXXL') default NULL,
  `permis_conduire_utl` enum('0','1') default NULL,
  `date_permis_conduire_utl` date default NULL,
  `hab_elect_utl` enum('0','1') default NULL,
  `afps_utl` enum('0','1') default NULL,
  `sst_utl` enum('0','1') default NULL,
  `site_web` varchar(96) default NULL,
  `id_flickr` varchar(64) NOT NULL,
  `jabber_utl` varchar(128) default NULL,
  PRIMARY KEY  (`id_utilisateur`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- 
-- Table structure for table `utl_joue_instru`
-- 

CREATE TABLE `utl_joue_instru` (
  `id_utilisateur` int(11) NOT NULL,
  `id_instru_musique` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id_utilisateur`,`id_instru_musique`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- 
-- Table structure for table `utl_parametres`
-- 

CREATE TABLE `utl_parametres` (
  `id_utilisateur` int(11) NOT NULL default '0',
  `nom_param` varchar(32) NOT NULL default '',
  `valeur_param` text NOT NULL,
  PRIMARY KEY  (`id_utilisateur`,`nom_param`),
  KEY `nom_param` (`nom_param`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- 
-- Table structure for table `utl_trombi`
-- 

CREATE TABLE `utl_trombi` (
  `id_utilisateur` int(11) NOT NULL,
  `autorisation` enum('0','1') NOT NULL default '0',
  `photo` enum('0','1') NOT NULL default '0',
  `famille` enum('0','1') NOT NULL default '0',
  `infos_personnelles` enum('0','1') default '0',
  `associatif` enum('0','1') NOT NULL default '0',
  `commentaires` enum('0','1') NOT NULL default '0',
  PRIMARY KEY  (`id_utilisateur`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- 
-- Table structure for table `villes`
-- 

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `ae2`.`villes` AS select `ae2`.`loc_ville`.`id_ville` AS `id_ville`,`ae2`.`loc_ville`.`id_pays` AS `id_pays`,`ae2`.`loc_ville`.`nom_ville` AS `nom_ville`,`ae2`.`loc_ville`.`cpostal_ville` AS `cpostal_ville` from `ae2`.`loc_ville`;

-- --------------------------------------------------------

-- 
-- Table structure for table `vt_a_vote`
-- 

CREATE TABLE `vt_a_vote` (
  `id_election` int(11) NOT NULL default '0',
  `id_utilisateur` int(11) NOT NULL default '0',
  `date_vote` int(32) default NULL,
  PRIMARY KEY  (`id_election`,`id_utilisateur`),
  KEY `fk_vt_a_vote_utilisateurs` (`id_utilisateur`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- 
-- Table structure for table `vt_candidat`
-- 

CREATE TABLE `vt_candidat` (
  `id_utilisateur` int(11) NOT NULL default '0',
  `id_poste` int(11) NOT NULL default '0',
  `id_liste` int(11) default NULL,
  `nombre_voix` int(32) NOT NULL default '0',
  PRIMARY KEY  (`id_utilisateur`,`id_poste`),
  KEY `fk_vt_candidat_vt_postes` (`id_poste`),
  KEY `fk_vt_candidat_vt_liste_candidat` (`id_liste`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- 
-- Table structure for table `vt_election`
-- 

CREATE TABLE `vt_election` (
  `id_election` int(11) NOT NULL auto_increment,
  `id_groupe` int(11) NOT NULL default '0',
  `date_debut` datetime NOT NULL default '0000-00-00 00:00:00',
  `date_fin` datetime NOT NULL default '0000-00-00 00:00:00',
  `nom_elec` varchar(128) NOT NULL default '',
  PRIMARY KEY  (`id_election`),
  KEY `fk_vt_election_groupe` (`id_groupe`)
) ENGINE=MyISAM AUTO_INCREMENT=14 DEFAULT CHARSET=latin1 AUTO_INCREMENT=14 ;

-- --------------------------------------------------------

-- 
-- Table structure for table `vt_liste_candidat`
-- 

CREATE TABLE `vt_liste_candidat` (
  `id_liste` int(11) NOT NULL auto_increment,
  `id_utilisateur` int(11) NOT NULL default '0',
  `id_election` int(11) NOT NULL default '0',
  `nom_liste` varchar(128) NOT NULL default '',
  PRIMARY KEY  (`id_liste`),
  KEY `fk_vt_liste_candidat_utilisateurs` (`id_utilisateur`),
  KEY `fk_vt_liste_candidat_vt_election` (`id_election`)
) ENGINE=MyISAM AUTO_INCREMENT=6 DEFAULT CHARSET=latin1 AUTO_INCREMENT=6 ;

-- --------------------------------------------------------

-- 
-- Table structure for table `vt_postes`
-- 

CREATE TABLE `vt_postes` (
  `id_poste` int(11) NOT NULL auto_increment,
  `id_election` int(11) NOT NULL default '0',
  `nom_poste` varchar(128) NOT NULL default '',
  `description_poste` text NOT NULL,
  `votes_total` int(32) NOT NULL default '0',
  `votes_blancs` int(32) NOT NULL default '0',
  PRIMARY KEY  (`id_poste`),
  KEY `fk_vt_postes_vt_election` (`id_election`)
) ENGINE=MyISAM AUTO_INCREMENT=93 DEFAULT CHARSET=latin1 AUTO_INCREMENT=93 ;

-- --------------------------------------------------------

-- 
-- Table structure for table `weekmail`
-- 

CREATE TABLE `weekmail` (
  `id_weekmail` int(11) NOT NULL auto_increment,
  `titre_weekmail` varchar(255) NOT NULL,
  `date_weekmail` date NOT NULL,
  `intro_weekmail` text NOT NULL,
  `blague_weekmail` text NOT NULL,
  `conclusion_weekmail` text NOT NULL,
  `id_file_header_weekmail` int(11) NOT NULL,
  `statut_weekmail` enum('0','1') NOT NULL default '0',
  `rendu_html_weekmail` longtext,
  `rendu_txt_weekmail` longtext,
  PRIMARY KEY  (`id_weekmail`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=latin1 AUTO_INCREMENT=4 ;

-- --------------------------------------------------------

-- 
-- Table structure for table `weekmail_news`
-- 

CREATE TABLE `weekmail_news` (
  `id_news` int(11) NOT NULL auto_increment,
  `id_weekmail` int(11) NOT NULL,
  `id_utilisateur` int(11) NOT NULL,
  `id_asso` int(11) NOT NULL,
  `titre` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `modere` enum('0','1') NOT NULL default '0',
  `rank` int(11) default NULL,
  PRIMARY KEY  (`id_news`)
) ENGINE=MyISAM AUTO_INCREMENT=20 DEFAULT CHARSET=latin1 PACK_KEYS=1 AUTO_INCREMENT=20 ;

-- --------------------------------------------------------

-- 
-- Table structure for table `wiki`
-- 

CREATE TABLE `wiki` (
  `id_wiki` int(11) NOT NULL auto_increment,
  `id_utilisateur` int(11) default NULL,
  `id_groupe` int(11) NOT NULL,
  `id_groupe_admin` int(11) default NULL,
  `droits_acces_wiki` int(11) NOT NULL,
  `id_wiki_parent` int(11) default NULL,
  `id_asso` int(11) default NULL,
  `id_rev_last` int(11) default NULL,
  `name_wiki` varchar(64) default NULL,
  `fullpath_wiki` varchar(512) NOT NULL,
  `namespace_behaviour` enum('0','1') NOT NULL default '0',
  `section_wiki` varchar(48) default NULL,
  PRIMARY KEY  (`id_wiki`),
  KEY `id_utilisateur` (`id_utilisateur`),
  KEY `id_groupe` (`id_groupe`),
  KEY `is_groupe_admin` (`id_groupe_admin`),
  KEY `id_wiki_parent` (`id_wiki_parent`),
  KEY `id_asso` (`id_asso`),
  KEY `fullpath_wiki` (`fullpath_wiki`)
) ENGINE=MyISAM AUTO_INCREMENT=2029 DEFAULT CHARSET=latin1 AUTO_INCREMENT=2029 ;

-- --------------------------------------------------------

-- 
-- Table structure for table `wiki_lock`
-- 

CREATE TABLE `wiki_lock` (
  `id_wiki` int(11) NOT NULL,
  `id_utilisateur` int(11) NOT NULL,
  `time_lock` datetime NOT NULL,
  PRIMARY KEY  (`id_wiki`,`id_utilisateur`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- 
-- Table structure for table `wiki_ref_file`
-- 

CREATE TABLE `wiki_ref_file` (
  `id_wiki` int(11) NOT NULL,
  `id_file` int(11) NOT NULL,
  PRIMARY KEY  (`id_wiki`,`id_file`),
  KEY `id_wiki` (`id_wiki`),
  KEY `id_file` (`id_file`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- 
-- Table structure for table `wiki_ref_missingwiki`
-- 

CREATE TABLE `wiki_ref_missingwiki` (
  `id_wiki` int(11) NOT NULL,
  `fullname_wiki_rel` varchar(512) NOT NULL,
  PRIMARY KEY  (`id_wiki`,`fullname_wiki_rel`),
  KEY `id_wiki` (`id_wiki`),
  KEY `fullname_wiki_rel` (`fullname_wiki_rel`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- 
-- Table structure for table `wiki_ref_wiki`
-- 

CREATE TABLE `wiki_ref_wiki` (
  `id_wiki` int(11) NOT NULL,
  `id_wiki_rel` int(11) NOT NULL,
  PRIMARY KEY  (`id_wiki`,`id_wiki_rel`),
  KEY `id_wiki` (`id_wiki`),
  KEY `id_wiki_rel` (`id_wiki_rel`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- 
-- Table structure for table `wiki_rev`
-- 

CREATE TABLE `wiki_rev` (
  `id_wiki` int(11) NOT NULL,
  `id_rev` int(11) NOT NULL auto_increment,
  `id_utilisateur_rev` int(11) NOT NULL,
  `date_rev` datetime NOT NULL,
  `contents_rev` text NOT NULL,
  `title_rev` varchar(128) NOT NULL,
  `comment_rev` varchar(128) NOT NULL,
  PRIMARY KEY  (`id_wiki`,`id_rev`),
  KEY `id_wiki` (`id_wiki`),
  KEY `id_utilisateur_rev` (`id_utilisateur_rev`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;
