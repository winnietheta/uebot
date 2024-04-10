<?php

require_once __DIR__ . '/core.php';

$uebot = new Uebot();
$uebot->json_bot_file = 'weather_example.json';
$uebot->python_bot_dir = 'weather_bot';
$uebot->convert_json_to_python();
