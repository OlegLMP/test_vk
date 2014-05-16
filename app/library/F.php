<?php

class F
{
    /**
     * Вывод лога с форматированием
     *
     * @author olegb
     * @param string $msg - текст
     * @return void
     */
    public static function log($msg)
    {
        $msg = date('Y-m-d H:i:s') . ' ' . $msg . "\n";
        echo $msg;
    }

    /**
     * Возвращает Ip адрес клиента
     * Может использовать заголовок X-Real-IP для работы в связке с nginx
     *
     * @author olegb
     * @return string или false, если php запущен из командной строки
     */
    public static function getIp() {
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
            if (isset($headers['X-Real-IP'])) {
                return $headers['X-Real-IP'];
            }
        }
        return getenv('REMOTE_ADDR');
    }

    /**
     * Выдают время формирования страницы
     *
     * @author oleg
     * @return string
     */
    public static function getScriptTime()
    {
        return number_format(microtime(true) - SCRIPT_START_TIME, 2);
    }

    /**
     * Трансформирует имя поля БД к имени параметра
     *
     * @author oleg
     * @param string $field
     * @return string
     */
    public static function fieldToParam($field)
    {
        $field = strtolower($field);
        return preg_replace_callback('~_(\w)~',
            function ($matches) {
                return strtoupper($matches[1]);
            },
        $field);
    }

    /**
     * Трансформирует имя параметра к имени поля ДБ
     *
     * @author oleg
     * @param string $param
     * @return string
     */
    public static function paramToField($param)
    {
        return preg_replace_callback('~[A-Z]~',
                function ($matches) {
            return '_' . strtolower($matches[0]);
        },
        $param);
    }

    /**
     * Трансформирует имя класса к имени таблицы БД
     *
     * @author oleg
     * @param string $class
     * @return string
     */
    public static function classToTable($class)
    {
        return F::paramToField(lcfirst($class));
    }

    /**
     * Трансформирует имя класса к имени параметра
     *
     * @author oleg
     * @param string $class
     * @return string
     */
    public static function classToParam($class)
    {
        return lcfirst($class);
    }

    /**
     * Трансформирует имя таблицы БД к имени класса
     *
     * @author oleg
     * @param string $table
     * @return string
     */
    public static function tableToClass($table)
    {
        $table = str_replace('`', '', $table);
        return ucfirst(F::fieldToParam($table));
    }

    /**
     * Трансформирует имя таблицы БД к имени параметра
     *
     * @author oleg
     * @param string $table
     * @return string
     */
    public static function tableToParam($table)
    {
        $table = str_replace('`', '', $table);
        return F::fieldToParam($table);
    }

    /**
     * Трансформирует имя параметра к имени класса
     *
     * @author oleg
     * @param string $param
     * @return string
     */
    public static function paramToClass($param)
    {
        return ucfirst($param);
    }

    /**
     * Трансформирует имя поля БД к имени класса
     *
     * @author oleg
     * @param string $field
     * @return string
     */
    public static function fieldToClass($field)
    {
        return ucfirst(F::fieldToParam($field));
    }

    /**
     * Вывести размер в байтах в читаемом формате
     *
     * @author oleg
     * @param string $bytes
     * @param bool $bytes - выводить точно, в байтах
     * @return string
     */
    public static function formatBytes($bytes, $inBytes = false)
    {
        $bytes = preg_replace('~[^0-9\.]~', '', $bytes);
        if ($inBytes || bccomp($bytes, '1024') == -1)
            return number_format($bytes, 0) . ' B';
        elseif (bccomp($bytes, '1048576') == -1)
            return number_format(bcdiv($bytes, '1024', 2), 2) . ' KB';
        elseif (bccomp($bytes, '1073741824') == -1)
            return number_format(bcdiv($bytes, '1048576', 2), 2) . ' MB';
        elseif (bccomp($bytes, '1099511627776') == -1)
            return number_format(bcdiv($bytes, '1073741824', 2), 2) . ' GB';
        else
            return number_format(bcdiv($bytes, '1099511627776', 2), 2) . ' TB';
    }

    /**
     * Возвращает полное кол-во памяти, потребляемое процессом в системе
     *
     * @author olegb
     * @return int - занимаемая память в байтах. Или false в случае ошибки чтения
     */
    public static function memory_get_usage()
    {
        $data = file_get_contents('/proc/' . getmypid() . '/status');
        if (! preg_match('~VmRSS:\s*([0-9]+) kB~i', $data, $matches)) {
            return false;
        }
        $memory = $matches[1];
        if (preg_match('~VmSwap:\s*([0-9]+) kB~i', $data, $matches)) {
            $memory += $matches[1];
        }
        return $memory * 1024;
    }

    /**
     * Возвращает информацию об использовании оперативной памяти
     *
     * @author oleg
     * @return string
     */
    public static function memoryReport()
    {
        return 'MEMORY_PEAK = ' . F::formatBytes(memory_get_peak_usage()) . ",\n" .
            'MEMORY_NOW = ' . F::formatBytes(memory_get_usage()) . ",\n" .
            'MEMORY_REAL_NOW = ' . F::formatBytes(F::memory_get_usage()) . "\n";
    }

    /**
     * эскепирование вывода HTML. Текст и параметры, ограниченный двойными кавычками.
     *
     * @author oleg
     * @param string $text - текст, который требуется эскепировать
     * @return string
     */
    public static function escape($text)
    {
        return htmlspecialchars($text);
    }

}
