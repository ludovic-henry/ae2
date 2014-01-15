<?php
/**
 * Copyright 2008
 * - Manuel Vonthron  <manuel DOT vonthron AT acadis DOT org>
 * - Pierre Mauduit <pierre POINT mauduit CHEZ utbm POINT fr>
 *
 * Ce fichier fait partie du site de l'Association des Étudiants de
 * l'UTBM, http://ae.utbm.fr/
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

class add_uv_edt_box extends form
{
  public function __construct($uv, $sem=SEMESTER_NOW)
  {
    if( !($uv instanceof uv) )
      throw new Exception("Incorrect type");

    $this->form($uv->code, null, null, null, $uv->code." - ".$uv->intitule);

    $code = $uv->code;
    $this->buffer = "";

    if(!$uv->extra_loaded)
      $uv->load_extra();

    /** UV possedant des alias */
    if($uv->has_alias()){
      $aliasbuf = "<b>Attention, cette UV possède ".count($uv->aliases)." alias</b>.
        Peut-être l'UV qui vous correspond en fait partie :";
      foreach($uv->aliases as $alias)
        $aliasbuf .= "<input type=\"button\" onclick=\"edt.switch_boxes('".$uv->code."_row', ".$alias['id'].", '$sem');\" value=\"Échanger avec ".$alias['code']."\" />\n";
    }
    $this->buffer .= $aliasbuf;

    /* si UV sans C/TD/TP, peut etre une TX ou un stage */
    if(empty($uv->guide['c']) && empty($uv->guide['td']) && empty($uv->guide['tp'])){
      /* ou alors c'est une erreur */
      if(empty($uv->guide['the'])){
        $this->buffer .= "<p><b>Désolé</b>, aucune information sur les nombres
          d'heures de cours/TD/TP/THE n'ont été donné concernant cette UV,
          il est nécessaire de corriger la fiche pour continuer.</p>";

      /* mais sinon c'est cool */
      }else{
        $this->buffer .= "<p><b>Cette UV ne semble comporter que des heures
          hors emplois du temps</b>, c'est le cas pour les TX, TW... ou les
          stages. Vous ne pouvez pas lui ajouter de
          \"séances\" mais elle apparaitra bien sur votre emploi du temps.</p>";
        $this->buffer .= "<p>Si vous pensez que l'absence d'heures de cours
          est une erreur, vous pouvez corriger la fiche.</p>";
        $this->buffer .= "  <input type=\"hidden\" name=\"seance_".$uv->id."_the\" value=\"1\" />\n";
      }

    /* UV normale */
    }else{
      $this->buffer .= "<p><i>Selon nos informations, les enseignements de cette UV
        sont composés de "
          .$uv->guide['c']."h de Cours, "
          .$uv->guide['td']."h de TD et "
          .$uv->guide['tp']."h de TP</i></p>";

      $this->buffer .= $this->build_uv_choice($uv, $sem, GROUP_C);
      $this->buffer .= $this->build_uv_choice($uv, $sem, GROUP_TD);
      $this->buffer .= $this->build_uv_choice($uv, $sem, GROUP_TP);

      if(!empty($uv->guide['the']))
        $this->buffer .= "<p><i>Cette UV comporte également ".$uv->guide['the']." heures hors emploi du temps.</i></p>";

    }

    $this->buffer .= "<p><input type=\"button\" onclick=\"edt.remove('".$uv->code."_row');\" value=\"Annuler l'inscription\" />";
    $this->buffer .= "<input type=\"button\" onclick=\"window.open('uv.php?action=edit&id=$uv->id');\" value=\"Corriger la fiche\" />";
    $this->buffer .= "<input type=\"button\" onclick=\"window.open('uv.php?view=suivi&id=$uv->id&semestre=$sem');\" value=\"Corriger les séances\" />";
    $this->buffer .= "<input type=\"button\" style=\"font-weight: bold;\" onclick=\"window.open('uv.php?id=$uv->id');\" value=\"Voir la fiche\" /></p>";

  }

  private function build_uv_choice($uv, $sem, $type){
    global $_GROUP;

    if($uv->guide[ $_GROUP[$type]['short'] ]){
      $groups = $uv->get_groups($type, $sem);
      $divid = $uv->id."_".$type;
      $sel_id = "seance_".$uv->id."_".$_GROUP[$type]['short'];

      $buffer  = "<div class=\"formrow\">\n";
      $buffer .= "  <div class=\"formlabel\">".$_GROUP[$type]['long']." : </div>\n";
      $buffer .= "  <div class=\"formfield\">\n";
      $buffer .= "  <a href=\"#\" title=\"Rafraichir la liste des s&eacute;ances\" onclick=\"openInContents('$sel_id', 'edt.php', 'action=get_seances_as_options&id_uv=$uv->id&type=$type&semestre=$sem');\"><img src=\"/images/icons/16/reload.png\" class=\"icon\"/></a>\n";
      $buffer .= "    <select name=\"$sel_id\" id=\"$sel_id\">\n";
      $buffer .= "      <option value=\"none\">S&eacute;lectionnez votre s&eacute;ance</option>\n";
      foreach($groups as $group){
        $buffer .= "      <option value=\"".$group['id_groupe']."\" onclick=\"edt.disp_freq_choice('".$divid."', ".$group['freq'].", ".$uv->id.", ".$type.");\">"
                            .$_GROUP[$type]['long']." n°".$group['num_groupe']." du ".get_day($group['jour'])." de ".strftime("%H:%M", strtotime($group['debut']))." &agrave; ".strftime("%H:%M", strtotime($group['fin']))." en ".$group['salle']
                            ."</option>\n";
      }
      $buffer .= "      <option value=\"add\" style=\"font-weight: bold;\" onclick=\"edt.add_uv_seance(".$uv->id.", ".$type.", '".$sem."', '".$sel_id."');\">Ajouter une s&eacute;ance manquante...</option>\n";
      $buffer .= "    </select>\n";
      $buffer .= "    <span id=\"".$divid."\"></span>\n";
      $buffer .= "  </div>\n";
      $buffer .= "</div>\n\n";
    }
    else
      $buffer = null;

    return $buffer;
  }
}

class add_seance_box extends stdcontents
{
  public function __construct($iduv, $type=null, $semestre=SEMESTER_NOW,
    $data=array("id_groupe"=>null,
                "id_uv"=>null,
                "type"=>null,
                "num_groupe"=>null,
                "freq"=>1,
                "semestre"=>null,
                "debut"=>null,
                "fin"=>null,
                "jour"=>null,
                "salle"=>null))
  {
    global $site;
    global $_GROUP;

    if(!empty($data['id_groupe']))
      define("EDITMODE", true);
    else
      define("EDITMODE", false);

    $uv = new uv($site->db, $site->dbrw);
    $uv->load_by_id($iduv);
    if(!$uv->is_valid())
      throw new Exception("Object not found : UV ".$iduv);

    $this->title = "Ajout d'une séance de ".$uv->code;

    $frm = new form("seance_".$iduv, "uv_groupe.php?action=save");
    $frm->allow_only_one_usage();
    $frm->add_hidden("id_uv", $uv->id);

    $frm->add_hidden("id_groupe", $data['id_groupe']);
    if(EDITMODE)
      $frm->add_hidden("editmode", "1");

    if(isset($_REQUEST['mode']) && $_REQUEST['mode'] == 'popup')
      $frm->add_hidden("mode", "popup");

    /* type de seance C/TD/TP (on vire THE) */
    $avail_type = array();
    foreach($_GROUP as $grp => $desc)
      if($grp != GROUP_THE)
        $avail_type[$grp] = $desc['long'];
    $frm->add_select_field("type", "Type", $avail_type, $type, "", true);
    if($type)
      $frm->add_info("Il y a déjà ".count($uv->get_groups($type, $semestre))." séance(s) de ".$_GROUP[$type]['long']." enregistrées pour ".$semestre.".");

    /* semestre */
    $y = date('Y');
    $avail_sem = array();
    for($i = $y-2; $i <= $y; $i++){
      $avail_sem['P'.$i] = 'Printemps '.$i;
      $avail_sem['A'.$i] = 'Automne '.$i;
    }
    $frm->add_select_field("semestre", "Semestre", $avail_sem, $semestre, "", true);

    /* numéro du groupe */
    $frm->add_text_field("num", "N° du groupe", $data['num_groupe'], true, 2, true, true, "(Indiquez '1' pour les cours sans numéro.)");

    /* jour */
    $avail_jour = array(
      1 => "Lundi",
      2 => "Mardi",
      3 => "Mercredi",
      4 => "Jeudi",
      5 => "Vendredi",
      6 => "Samedi",
      7 => "Dimanche ?!",
    );
    $frm->add_select_field("jour", "Jour", $avail_jour, $data['jour']);

    /* heures */
    $min = array(0=>'00', 15=>'15', 30=>'30', 45=>'45');

      $deb = explode(":", $data['debut']);
      $fin = explode(":", $data['fin']);

    $subfrm = new subform("heures", "Heures : ");
    $subfrm->add_text_field("hdebut", "Début", $deb[0] ,false, 2, true);
    $subfrm->add_select_field("mdebut", ":", $min, $deb[1]);
    $subfrm->add_text_field("hfin", "Fin", $fin[0] ,false, 2, true);
    $subfrm->add_select_field("mfin", ":", $min, $fin[1]);
    $frm->add($subfrm, false, false, false, false, true);

    /* frequence */
    $frm->add_select_field("freq", "Fréquence", array(1=>"Toutes les semaines", 2=>"Une semaine sur deux"), $data['freq']);

    /* salle */
    $frm->add_text_field("salle", "Salle", $data['salle'], false, 8, false, true, "(ex: P108)");

    $frm->puts("<p>Tous les champs sont requis. Veuillez vérifier minutieusement
    les informations que vous avez entré.</p>");

    /* submit */
    if(EDITMODE)
      $label = "Modifier la séance";
    else
      $label = "+ Ajouter la séance";
    $frm->add_submit("save", $label);

    $this->buffer .= $frm->html_render();
  }
}

class uv_dept_table extends stdcontents
{
  public function __construct($uvlist)
  {
    $this->buffer = "";
    $this->buffer .= "<table class=\"uvlist\">\n";
    $this->buffer .= " <tr>\n";
    $i = 0;
    if(!empty($uvlist))
    foreach($uvlist as $uv)
    {
      $this->buffer .= "  <td><a href=\"./uv.php?id=".$uv['id_uv']."\">".$uv['code']."</a></td>\n";
      $i++;
      if($i == 15){
        $this->buffer .= "</tr><tr>\n";
        $i = 0;
      }
    }
    $this->buffer .= "\n </tr>\n</table>\n";
  }
}

function pedag_menu_box()
{
  global $_DPT;

  $cts = new contents("Pédagogie");

  $dpt = new itemlist("<a href=\"uv.php\" title=\"Toutes les UV\">Guide des UV</a>");
  foreach ($_DPT as $key=>$name)
    $dpt->add("<a href=\"uv.php?dept=".$key."\">".$name['short']."</a>");
  $cts->add($dpt, true);

  $outils = new itemlist("Outils", false, array("<a href=\"edt.php\" title=\"Gérer vos emploi du temps\">Emplois du temps</a>",
                                               /* "<a href=\"parcours.php\" title=\"Toutes les UV\"> Votre parcours </a>",*/
                                               /* "<a href=\"profils.php\" title=\"Toutes les UV\"> Profils types </a>",*/
                                                "<a href=\"cursus.php\" title=\"Cursus\">Filières, mineurs, ...</a>"));
  $cts->add($outils, true);

  return $cts;
}

function last_comments_box(&$db, $nb=5)
{
  $cts = new contents("Commentaires");

  $sql = new requete(&$db, "SELECT id_uv as id, id_commentaire, code, surnom_utbm
                            FROM pedag_uv_commentaire
                            NATURAL JOIN pedag_uv
                            NATURAL JOIN utl_etu_utbm
                            ORDER BY date  DESC
                            LIMIT ".$nb);

  $avis = new itemlist("Les $nb derniers commentaires");

  while( $row = $sql->get_row() )
    $avis->add("<a href=\"uv.php?view=commentaires&id_uv=".$row['id']."#cmt_".$row['id_commentaire']."\">".$row['code']."  par ".$row['surnom_utbm']."</a>");

  $cts->add($avis, true);

  return $cts;
}

?>
