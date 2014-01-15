<?php

class comment_contents extends stdcontents
{

  function comment_contents (&$comment, $user_id, $is_user_moderator)
  {
    global $topdir, $wwwtopdir;

    if ( !$is_user_moderator && $comment["modere_commentaire"] )
      return false;

    $is_user_comment = ( $comment["id_commentateur"] == $user_id );


    $this->buffer .= "<div class=\"trombicomment".( $is_user_comment ? " mycomment" : "" )."\">\n";

    $this->buffer .= "\t<a name=\"c".$comment["id_commentaire"]."\"></a>\n";
    if ( $user_id == $comment["id_commentateur"] )
      $this->buffer .= "\t<a name=\"mycomment\"></a>\n";

    $this->buffer .= "\t<p class=\"date\">".$this->human_date(strtotime($comment["date_commentaire"]))."</p>\n";

    $this->buffer .= "\t<p class=\"actions\">";
    $separator = false;

    if ( $is_user_comment || $is_user_moderator )
    {
      $this->buffer .= "<a href=\"?page=edit&amp;id_commentaire=".$comment["id_commentaire"]."\">Editer</a>";
      $separator = true;
    }

    if ( $is_user_comment )
    {
      $this->buffer .= " | <a href=\"?page=del&amp;id_commentaire=".$comment["id_commentaire"]."\">Supprimer</a>";
      $separator = true;
    }

    if ( $is_user_moderator )
    {
      //la variable "m" ne sert qu'à fournir une adresse différente pour que le navigateur recharge bien la page
      $this->buffer .= ($separator ? " | " : "") . "<a href=\"?action=moderate&amp;id_commentaire=".$comment["id_commentaire"]."&amp;id_utilisateur=".$comment["id_commente"].($comment["modere_commentaire"] ? "" : "&amp;m")."#c".$comment["id_commentaire"]."\">".($comment["modere_commentaire"] ? "Restaurer" : "Modérer")."</a>";
    }

    $this->buffer .= "</p>\n";

    $this->buffer .= "\t<div class=\"author\">\n";
    $this->buffer .= "\t\t<p class=\"tuname\"><a href=\"?id_utilisateur=".$comment["id_commentateur"]."\">" . (
        (isset($comment["alias_utl"]) && $comment["alias_utl"] != "") ?
        $comment["alias_utl"] :
        $comment["prenom_utl"]." ".$comment["nom_utl"]
      )."</a></p>\n";

    if (file_exists($wwwtopdir."data/matmatronch/".$comment['id_commentateur'].".identity.jpg"))
    {
      $img = $wwwtopdir."data/matmatronch/".$comment['id_commentateur'].".identity.jpg";
      $this->buffer .= "\t\t<p class=\"tuimg\"><img src=\"".htmlentities($img,ENT_NOQUOTES,"UTF-8")."\" /></p>";
    }
    $this->buffer .= "\t</div>\n";

    $this->buffer .= "\t<div class=\"commentcontent\">\n";
    $this->buffer .= doku2xhtml($comment['commentaire']);
    $this->buffer .= "\t</div>\n";

    $this->buffer .= "\t<div class=\"clearboth\"></div>\n";
    $this->buffer .= "</div>\n";
  }

  function human_date ( $timestamp )
  {
    if ( date("d/m/Y",$timestamp) == date("d/m/Y",time()) )
      return "Aujourd'hui ".date("H:i",$timestamp);

    if ( date("d/m/Y",$timestamp) == date("d/m/Y",time()-86400 ) )
      return "Hier ".date("H:i",$timestamp);

    return date("d/m/Y H:i",$timestamp);
  }

}

?>
