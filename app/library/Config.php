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
        }
        self::expand($prefix, $data);
    }

    /**
     * Разворачивает настройки для быстрого доступа по ключам
     *
     * @author olegb
     * @param string $filename - путь к конфиг-файлу
     * @return void
     */
    public static function expand($prefix, $data)
    {
        foreach ($data as $subPrefix => $subData) {
            $newPrefix = (strlen($prefix) ? $prefix . '.' : '') . $subPrefix;
            self::$_config[$newPrefix] = $subData;
            if (is_array($subData)) {
                self::expand($newPrefix, $subData);
            }
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
        return false;
    }

}