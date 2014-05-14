<?php
require_once realpath(__DIR__ . '/../app/app.inc.php');

$handler = new WebHandler();
$handler->process(isset($_SERVER['REDIRECT_URL']) ? $_SERVER['REDIRECT_URL'] : '/');
