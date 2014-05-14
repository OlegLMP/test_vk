<?php
require_once realpath(__DIR__ . '/../app/app.inc.php');

$handler = new WebHandler();
$handler->process(isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/');
