<?
$topdir = "../";

include($topdir."include/lib/barcode.inc.php");

$Barcode = $_REQUEST["barcode"];

$barcode_size_x = 200;
$barcode_size_y = 100;
$barcode_xres = 2;

$code = new C128AObject ($barcode_size_x, $barcode_size_y, BCS_ALIGN_CENTER | BCS_IMAGE_PNG | BCS_DRAW_TEXT, $Barcode);
$code->DrawObject($barcode_xres);
$code->FlushObject();

?>
