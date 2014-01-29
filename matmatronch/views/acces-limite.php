<?php

$topdir = __DIR__ . "/../../";

require_once __DIR__ . "/../../include/site.inc.php";

$site->start_page("matmatronch", "MatMaTronch");

$cts = new contents("Accès limité");
$cts->add_paragraph("L'accès au matmatronch est réservée aux membres de l'utbm et aux cotisants AE.");
$site->add_contents($cts);

$site->end_page();