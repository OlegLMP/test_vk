<?php
/**
 * Config
 *
 * Класс для хранения настроек приложения
 *
 * @author olegb
 */
class Config {

    /**
     * Настройки приложения
     *
     * @var array
     */
    private static $_config = array();

    /**
     * Читает настройки из файла
     *
     * @author olegb
     * @param string $filename - путь к конфиг-файлу
     * @return void
     */
    public static function read($filename, $prefix = null)
    {
        $data = parse_ini_file($filename, true);
        if (isset($prefix)) {
            self::$_config[$prefix] = $data;
        } else {
            self::$_config = $data;
        }
    }

    /**
     * Возвращает настройки приложения
     *
     * @author olegb
     * @param string $path - путь к настройке. Разделитель - "." Например: database.username
     *     Если указан, возвращается соответствующая настройка или ветвь.
     * @return mixed
     */
    public static function get($path = null)
    {
        if (! isset($path)) {
            return self::$_config;
        }
        if (isset(self::$_config[$path])) {
            return self::$_config[$path];
        }
        while (($p = strpos($path, '.')) !== false) {
            $prefix = substr($path, 0, $p);
            $path = substr($path, $p + 1);
            if (isset(self::$_config[$path])) {
                return self::$_config[$path];
            }
        }
        return false;
    }

}