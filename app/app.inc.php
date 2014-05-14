<?php

define('SCRIPT_START_TIME', microtime(true));

define('APPLICATION_ROOT', realpath(__DIR__ . '/../'));

// Настройка локали
setlocale(LC_CTYPE, "ru_RU.utf8");

set_include_path(implode(PATH_SEPARATOR, array(
        APPLICATION_ROOT . '/app/models',
        APPLICATION_ROOT . '/app/controllers',
        APPLICATION_ROOT . '/app/library',
        APPLICATION_ROOT . '/library',
        get_include_path(),
)));

spl_autoload_register(function ($class) {
    include str_replace(array('_', '\\'), '/', $class) . '.php';
});

Config::read(APPLICATION_ROOT . '/app/config/config.ini');

if (Config::get('settings.debug')) {
    Debuger::message('Started');
}

session_cache_expire(1440);
