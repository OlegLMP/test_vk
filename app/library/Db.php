<?php
/**
 * Db
 *
 * Класс для работы с базой данных
 *
 * @author oleg
 */
class Db {

    /**
     * Экземпляры классов для работы с разными БД
     *
     * @var Db[]
     */
    private static $_instances;

    /**
     * Секция конфига с параметрами подключения к базе
     *
     * @var string
     */
    private $_configSection;

    /**
     * Линк на соединения с базами данных
     *
     * @var PDO
     */
    private $_pdo;

    /**
     * Последний запрос к базе
     *
     * @var PDOStatement
     */
    protected $_statement;

    /**
     * Соединяется с базой
     *
     * @author oleg
     * @param string $configSection - секция конфига с параметрами подключения к базе
     * @return void
     */
    public function __construct($configSection)
    {
        $this->_configSection = $configSection;
    }

    /**
     * Соединяется с базой
     *
     * @author oleg
     * @param string $configSection - секция конфига с параметрами подключения к базе
     * @return void
     */
    public static function get($configSection)
    {
        if (! isset(self::$_instances[$configSection])) {
            self::$_instances[$configSection] = new self($configSection);
        }
        return self::$_instances[$configSection];
    }

    /**
     * Соединяется с базой
     *
     * @author oleg
     * @return PDO
     */
    public function connect()
    {
        if (isset($this->_pdo)) {
            return $this->_pdo;
        }
        $config = Config::get($this->_configSection);
        try {
            $this->_pdo = new PDO('mysql:host=' . $config['host'] . ';port=' . $config['port'] . ';dbname=' . $config['dbname'] . ';charset=UTF8', $config['username'], $config['password']);
            $this->_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $e) {
            $this->_mysqlException($e->getMessage(), __FILE__, __LINE__, '');
        }
        return $this->_pdo;
    }

    /**
     * Выполняет sql-запрос
     *
     * @author oleg
     * @param string $query - SQL-запрос
     * @param int $fetchStyle - PDO::FETCH_ASSOC | PDO::FETCH_COLUMN
     * @return
     *     array - ассоциативный массив выбранных данных для запросов SELECT.
     *     Либо int - кол-во затронутых строк для остальных запросов: INSERT/UPDATE/DELETE и т.д.
     */
    public function sql($query, $fetchStyle = PDO::FETCH_ASSOC)
    {
        if (! isset($fetchStyle)) {
            $fetchStyle = PDO::FETCH_ASSOC;
        }
        $pdo = $this->connect();
        if ($debug = Config::get('settings.debug')) {
            $startTime = microtime(true);
        }
        try {
            $query = trim($query);
            $this->_statement = $pdo->query($query);
            if ($debug) {
                Debuger::message($query . ' - ' . number_format(microtime(true) - $startTime, 4));
            }
            if (! strncasecmp($query, 'SELECT', 6) || ! strncasecmp($query, 'SHOW', 4)) {
                $result = $this->_statement->fetchAll($fetchStyle);
                if (preg_match('~LIMIT 1$~iu', $query)) {
                    return array_shift($result);
                } else {
                    return $result;
                }
            } else {
                return $this->getAffectedRows();
            }
        } catch(PDOException $e) {
            $this->_mysqlException($e->getMessage());
        }
    }

    /**
     * Возвращает кол-во затронутых строк последней командой sql
     *
     * @author oleg
     * @return int
     */
    public function getAffectedRows()
    {
        return $this->_statement->rowCount();
    }

    /**
     * Возвращает ID, сгенерированный колонкой с AUTO_INCREMENT последним запросом INSERT
     *
     * @author oleg
     * @return int
     */
    public function getInsertId()
    {
        return $this->_pdo->lastInsertId();
    }



    /**
     * Выбросить ошибку MYSQL
     *
     * @author olegb
     * @param string $file - файл, в котором произошла ошибка
     * @param int $line - номер строки, на которой произошла ошибка
     * @param string $query - запрос
     * @return void
     */
    private function _mysqlException($message = 'unknown message', $file = null, $line = null, $query = null)
    {
        $trace = debug_backtrace();
        if (! isset($file) && isset($trace[1]['file'])) {
            $file = $trace[1]['file'];
        }
        if (! isset($line) && isset($trace[1]['line'])) {
            $line = $trace[1]['line'];
        }
        if (! isset($query) && isset($trace[1]['args'][0])) {
            $query = $trace[1]['args'][0];
        }
        throw new Exception("ОШИБКА MYSQL " . date('Y-m-d H:i:s') .
                ", ФАЙЛ: " . ($file ? $file : 'не известен') .
                ", СТРОКА: " . ($line ? $line : 'не известна') .
                ",\nОТВЕТ MYSQL: " . $message .
                ", БАЗА: " . $this->_configSection .
                (is_string($query) ? ",\nЗАПРОС: " . $query : ''));
    }

    /**
     * Подготавливает данные значения для запроса к базе
     *
     * @author oleg
     * @param null|bool|int|float|string $value
     * @return string
     */
    public function prepare($value)
    {
        if (! isset($value)) {
            return 'NULL';
        }
        if (is_bool($value)) {
            return (int) $value;
        }
        if (is_int($value) || is_float($value)) {
            return $value;
        }
        $this->connect();
        try {
            return $this->_pdo->quote($value);
        } catch(Exception $e) {
            $this->_mysqlException($e->getMessage());
        }
    }

    /**
     * Подготавливает данные имени поля/таблицы для запроса к базе
     *
     * @author oleg
     * @param string $name
     * @return string
     */
    public static function name($name)
    {
        return '`' . $name . '`';
    }

    /**
     * Выполняет иснструкции из SQL файла
     *
     * @author oleg
     * @param string $sqlFile - путь к SQL-файлу
     * @return void
     */
    public function execFile($sqlFile)
    {
        $config = Config::get($this->_configSection);

        if (! file_exists($sqlFile)) {
            throw new AppException('SQL файл не найден: ' . $sqlFile);
        }

        exec('mysql -h ' . escapeshellarg($config['host']) . ' -P ' . escapeshellarg($config['port']) .
            ' -u ' . escapeshellarg($config['username']) . ' --password=' . escapeshellarg($config['password']) .
            ' --default-character-set=utf8 ' . escapeshellarg($config['dbname']) . ' < ' . escapeshellarg($sqlFile));
    }

    /**
     * Заполняет файлы имён полей моделей, описаний, связей
     *
     * @author oleg
     * @return string
     */
    public function fillFieldNames()
    {
        $tables = $this->sql('SHOW TABLES');
        foreach ($tables as $table) {
            $table = array_pop($table);
            $class = F::tableToClass($table);
            $columns = $this->sql('SHOW FULL COLUMNS FROM `' . $table . '`');
            $names = array();
            foreach ($columns as $column) {
                $names[$column['Field']] = array();
                if (preg_match('~(^| )([^\-].+)~', $column['Comment'], $matches)) {
                    $names[$column['Field']] = explode('|', $matches[2]);
                } elseif ($column['Field'] == 'created') {
                    $names[$column['Field']][0] = 'Создано';
                } elseif ($column['Field'] == 'updated') {
                    $names[$column['Field']][0] = 'Изменено';
                }
                if (strlen($column['Default'])) {
                    $names[$column['Field']][2] = $column['Default'];
                }
                if (preg_match('~->(?:(\w+)\.)?(\w+)\.(\w+)~', $column['Comment'], $matches)) {
                    $names[$column['Field']][3] = F::tableToClass($matches[2]);
                }
            }
            file_put_contents(APPLICATION_ROOT . '/app/models/field_names/' . $class . '.json', json_encode($names));
        }
    }

}