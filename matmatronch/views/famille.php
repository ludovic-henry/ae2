<?php

$genealogie->generate_filiation_utl($user_id, $site->db);
$genealogie->generate();
$genealogie->destroy();