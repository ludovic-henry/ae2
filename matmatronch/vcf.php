<?php
$topdir = "../";
require_once($topdir. "include/site.inc.php");

$site = new site ();
if ( !$site->user->is_valid() )
{
   header("Location: 403.php?reason=session");
  exit();
}

if ( !$site->user->utbm && !$site->user->ae )
{
   header("Location: 403.php?reason=reservedutbm");
  exit();
}

$user = new utilisateur($site->db,$site->dbrw);
$user->load_by_id($_REQUEST["id_utilisateur"]);
if ( $user->id < 0 )
{
  $site->error_not_found("matmatronch");
  exit();
}
$user->load_all_extra();
header("Content-Type: text/x-vcard");
header('Content-Disposition: attachment; filename="'.addslashes(strtolower(utf8_enleve_accents($user->prenom)."_".utf8_enleve_accents($user->nom).".vcf")).'"');

$user->output_vcard();

?>
