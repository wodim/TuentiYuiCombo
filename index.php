<?php

$yuiPath = __DIR__ . '/.yui/';
require __DIR__ . '/TuentiYuiCombo.php';

$combo = TuentiYuiCombo::parse($_SERVER['QUERY_STRING'], $yuiPath);
$combo->render();