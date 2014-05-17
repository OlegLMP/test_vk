<?php
/**
 * ActiveRecord
 *
 * @author oleg
 */
abstract class ActiveRecord
{
    /**
     * Ключевое поле
     * Может переопределяться в потомках
     *
     * @var string
     */
    public static $keyField = 'id';

    /**
     * Имя секции настроек БД, в которой хранятся объекты данного типа
     * Может быть указано в потомке, либо берётся database1
     *
     * @var string
     */
    public static $dbConfigSection = 'database1';

    /**
     * Имя таблицы, в которой хранятся объекты данного типа
     * Может быть указано в потомке, либо берётся по названию класса
     *
     * @var string
     */
    public static $table;

    /**
     * Присутствует ли в таблице поле created
     *
     * @var bool
     */
    public static $fieldCreated = true;

    /**
     * Нужно ли логировать изменение данных модели
     *
     * @var bool
     */
    public static $log = true;

    /**
     * Значение ключа
     *
     * @var int|string
     */
    public $key;

    /**
     * Данные из базы
     *
     * @var array
     */
    public $data;

    /**
     * Установленные данные для записи в базу
     *
     * @var array
     */
    public $assignedData;

    /**
     * Имена полей
     *
     * @var string[class => [field => ["Имя", "Описание", "Значение по умолчанию", "Связанный класс"]]]
     */
    public static $fieldNames = array();

    // Защищаем от создания через new
    protected function __construct() {
    }

    /**
     * Конвертация объекта в строку (для вывода в консоли)
     *
     * @author olegb
     * @return string
     */
    public function __toString()
    {
        return get_called_class() . '[' . $this->key . ']';
    }

    /**
     * Создание экземпляра объекта
     *
     * @author olegb
     * @param array $createData - массив с данными, которые необходимо установить при создании объекта
     * @param bool $returnKeyOnly - если установлено в true, то в ответ вернётся только значение ключевого поля созданного объекта.
     *      Иначе данные созданного объекта читаются из базы и возвращается объект
     * @return ActiveRecord | int
     */
    public static function create($createData = null, $returnKeyOnly = null)
    {
        $class = get_called_class();
        $obj = new $class();
        $obj->createData($createData);
        if ($returnKeyOnly) {
            return $obj->key;
        }
        $result = static::readData($obj->key);
        $obj->importData($result);
        return $obj;
    }


    /**
     * Получение экземпляра объекта по ключевому полю
     *
     * @author olegb
     * @param int|string $key - значение ключевого поля
     * @return ActiveRecord|false
     */
    public static function find($key)
    {
        if (! isset($key)) {
            return false;
        }
        $result = static::readData($key);
        return static::fromArray($result);
    }

    /**
     * Получение экземпляра объекта по значению поля
     *
     * @author olegb
     * @param string $name - имя ключевого поля
     * @param int|string $value - значение ключевого поля
     * @return ActiveRecord|false
     */
    public static function findOneBy($name, $value)
    {
        $result = Db::get(static::$dbConfigSection)->sql('SELECT * FROM ' . Db::name(static::getTableName()) . ' WHERE ' . Db::name($name) . ' = ' . Db::get(static::$dbConfigSection)->prepare($value) . ' LIMIT 1');
        return static::fromArray($result);
    }

    /**
     * Получение экземпляра объекта по нескольким значениям полей
     *
     * @author oleg
     * @param array $data - данные, по которым осуществляется поиск
     * @param bool $returnKeyOnly - если установлено в true, то в ответ вернётся только значение ключевого поля найденного объекта.
     *      Иначе все данные объекта читаются из базы и возвращается объект
     * @return ActiveRecord|int|false
     */
    public function findOneByParams($data, $returnKeyOnly = null)
    {
        $whereSql = '';
        foreach ($data as $name => $value) {
            $whereSql .= ($whereSql ? ' && ' : '') . Db::name($name) . '=' . Db::get(static::$dbConfigSection)->prepare($value);
        }
        $result = Db::get(static::$dbConfigSection)->sql('SELECT ' . ($returnKeyOnly ? static::$keyField : '*') . ' FROM ' . Db::name(static::getTableName()) . ' WHERE ' . $whereSql . ' LIMIT 1');
        if ($returnKeyOnly) {
            return $result ? $result[static::$keyField] : false;
        }
        return static::fromArray($result);
    }

    /**
     * Получение экземпляра объекта по массиву данных
     *
     * @author olegb
     * @param array $data
     * @return ActiveRecord|false
     */
    public static function fromArray($data)
    {
        if (! $data) {
            return false;
        }
        $class = get_called_class();
        $obj = new $class();
        if (! $obj->importData($data)) {
            return false;
        }
        return $obj;
    }

    /**
     * Получение массива экземпляров объектов по массиву из массивов данных
     * $orders = Order::arrayFromArray($db->sql('SELECT * FROM `order`'));
     *
     * @author olegb
     * @param array[] $dataSets
     * @return ActiveRecord[]
     */
    public static function arrayFromArray($dataSets)
    {
        $result = array();
        foreach ($dataSets as $data) {
            $result[] = static::fromArray($data);
        }
        return $result;
    }

    /**
     * Перечитывает данные из базы
     *
     * @author olegb
     * @return ActiveRecord
     */
    public function reload()
    {
        if (! isset($this->key)) {
            return;
        }
        $result = static::readData($this->key);
        static::fromArray($result);
        return $this;
    }

    /**
     * Получение мени таблицы, в которой хранятся объекты данного типа
     *
     * @author olegb
     * @return string
     */
    public static function getTableName()
    {
        if (static::$table) {
            return static::$table;
        }
        return F::classToTable(get_called_class());
    }

    /**
     * Получает данные объекта из базы по ключевому полю
     *
     * @author oleg
     * @param int|string $key - значение ключевого поля
     * @return array
     */
    public static function readData($key)
    {
        return Db::get(static::$dbConfigSection)->sql('SELECT * FROM ' . Db::name(static::getTableName()) . ' WHERE ' . Db::name(static::$keyField) . ' = ' . Db::get(static::$dbConfigSection)->prepare($key));
    }

    /**
     * Заполняет внутренние переменные объекта из массива
     *
     * @author oleg
     * @param array $data
     * @return bool - true при успехе, false при неудаче
     */
    public function importData($data)
    {
        // Если в функцию передан массив из массивов данных, то берём первый
        if (isset($data[0])) {
            $data = $data[0];
        }
        if (! $this->data = $data) {
            return false;
        }
        $this->key = $this->data[static::$keyField];
        return true;
    }

    /**
     * Регистрирует объект в базе
     *
     * @author oleg
     * @param array $data - дополнительные данные, которые необходимо установить сразу после регистрации
     * @return void
     */
    public function createData($data = null)
    {
        if (! isset($this->key) && isset($data[static::$keyField])) {
            $this->key = $data[static::$keyField];
            unset($data[static::$keyField]);
        }
        $setSql = Db::name(static::$keyField) . ' = ' . Db::get(static::$dbConfigSection)->prepare($this->key);
        if ($data) {
            foreach ($data as $name => $value) {
                $setSql .= ',' . Db::name($name) . '=' . Db::get(static::$dbConfigSection)->prepare($value);
            }
        }
        if (static::$fieldCreated) {
            $setSql .= ', created=NOW()';
        }
        Db::get(static::$dbConfigSection)->sql('INSERT INTO ' . Db::name(static::getTableName()) . ' SET ' . $setSql);
        if (! isset($this->key)) {
            $this->key = Db::get(static::$dbConfigSection)->getInsertId();
        }
        $this->insertLog($setSql);
    }

    /**
     * Записывает изменение данных в лог
     *
     * @author oleg
     * @param string $setSql
     * @return void
     */
    public function insertLog($setSql)
    {
        if (static::$log && Db::get(static::$dbConfigSection)->getAffectedRows()) {
            $db = Db::get(Log::$dbConfigSection);
            $db->sql('INSERT log SET initiator=' . Db::get(static::$dbConfigSection)->prepare(Initiator::createInitiatorKey()) . ',
class=' . $db->prepare(get_called_class()) . ',
model=' . $db->prepare($this->key) . ',
data=' . $db->prepare($setSql));
        }
    }

    /**
     * Заносит данные в базу
     *
     * @author oleg
     * @param string $name
     * @param string $value
     * @param bool $check - выполнять запрос на обновлние только, если новые данные отличаются от текущих
     * @return void
     */
    public function writeData($name, $value, $check = true)
    {
        $this->writeDataArray([$name => $value], $check);
    }

    /**
     * Заносит данные в базу из массива
     *
     * @author oleg
     * @param array $data
     * @param bool $check - выполнять запрос на обновление только, если новые данные отличаются от текущих
     * @return void
     */
    public function writeDataArray($data, $check = true)
    {
        $this->assignedData = array();
        foreach ($data as $name => $value) {
            if ($check
                    && $this->data[$name] == $value
                    && isset($this->data[$name]) == isset($value)) {
                continue;
            }
            $this->assignedData[$name] = $value;
            $this->data[$name] = $value;
        }
        if ($this->assignedData) {
            $setSql = '';
            foreach ($this->assignedData as $name => $value) {
                $setSql .= ($setSql ? ',' : '') . Db::name($name) . '=' . Db::get(static::$dbConfigSection)->prepare($value);
            }
            Db::get(static::$dbConfigSection)->sql('UPDATE ' . Db::name(static::getTableName()) . ' SET ' . $setSql . ' WHERE ' . Db::name(static::$keyField) . ' = ' . Db::get(static::$dbConfigSection)->prepare($this->key));
            $this->insertLog($setSql);
        }
    }

    /**
     * Загружает данные по именам полей
     *
     * @author oleg
     * @return void
     */
    public static function loadFieldNames()
    {
        $class = get_called_class();
        if (! isset(static::$fieldNames[$class]) && file_exists($fileName = APPLICATION_ROOT . '/app/models/field_names/' . $class . '.json')) {
            static::$fieldNames[$class] = json_decode(file_get_contents($fileName), true);
        }
    }

    /**
     * Возвращает список параметров модели
     *
     * @author oleg
     * @return string[]
     */
    public static function getFields()
    {
        $class = get_called_class();
        static::loadFieldNames();
        return array_keys($class::$fieldNames[$class]);
    }

    /**
     * Возвращает русское название параметра модели по имени параметра
     *
     * @author oleg
     * @param string $field - имя поля БД
     * @return string
     */
    public static function getFieldName($field)
    {
        $class = get_called_class();
        static::loadFieldNames();
        return isset($class::$fieldNames[$class][$field][0]) ? $class::$fieldNames[$class][$field][0] : $field;
    }

    /**
     * Возвращает русское описание параметра модели по имени параметра
     *
     * @author oleg
     * @param string $field - имя поля БД
     * @return string
     */
    public static function getFieldDescription($field)
    {
        $class = get_called_class();
        static::loadFieldNames();
        return isset($class::$fieldNames[$class][$field][1]) ? $class::$fieldNames[$class][$field][1] : '';
    }

    /**
     * Возвращает значение по умолчанию параметра модели по имени параметра
     *
     * @author oleg
     * @param string $field - имя поля БД
     * @return string|null
     */
    public static function getFieldDefault($field)
    {
        $class = get_called_class();
        static::loadFieldNames();
        $default = isset($class::$fieldNames[$class][$field][2]) ? $class::$fieldNames[$class][$field][2] : null;
        if (! strcmp($default, 'CURRENT_TIMESTAMP')) {
            return null;
        }
        return $default;
    }

    /**
     * Возвращает значения по умолчанию
     *
     * @author oleg
     * @return string|null
     */
    public static function getDefaults()
    {
        $result = array();
        foreach (static::getFields() as $field) {
            $result[$field] = static::getFieldDefault($field);
        }
        return $result;
    }

    /**
     * Возвращает имя связанного класса параметра модели по имени параметра
     *
     * @author oleg
     * @param string $field - имя поля БД
     * @return string|null
     */
    public static function getFieldRelatedClass($field)
    {
        $class = get_called_class();
        static::loadFieldNames();
        return isset($class::$fieldNames[$class][$field][3]) ? $class::$fieldNames[$class][$field][3] : null;
    }

    /**
     * Возвращает связанный объект (его id находится в нашей таблице)
     *
     * @author oleg
     * @param string $field - имя поля БД, по которому привязан объект
     * @param bool $refresh - Перечитать объект из базы
     * @return Shop
     */
    public function getRelatedObject($field, $refresh = null)
    {
        $param = F::fieldToParam($field);
        $class = $this->getFieldRelatedClass($field);
        if (! $this->data[$field]) {
            return false;
        }
        if (! isset($this->$param) || $refresh) {
            $this->$param = $class::find($this->data[$field]);
        }
        return $this->$param;
    }

    /**
     * Возвращает связанные объекты (в их таблице содержится наш id)
     *
     * @author oleg
     * @param string $class
     * @param bool $refresh - Перечитать объект из базы
     * @return Shop
     */
    public function getRelatedObjects($class, $refresh = null)
    {
        $param = F::classToParam($class) . 's';
        $field = static::getTableName();
        if (! isset($this->$param) || $refresh) {
            $this->$param = array();
            $result = Db::get($class::$dbConfigSection)->sql('SELECT * FROM ' . Db::name($class::getTableName()) . ' WHERE ' . Db::name($field) . ' = ' . Db::get($class::$dbConfigSection)->prepare($this->key));
            foreach ($result as $data) {
                $this->{$param}[] = $class::fromArray($data);
            }
        }
        return $this->$param;
    }

    /**
     * Проверяет объект или находит объект по ID
     *
     * @author oleg
     * @param ActiveRecord|int $object
     * @return ActiveRecord
     * @throws AppException
     */
    public static function checkOrFind($object)
    {
        $class = get_called_class();
        if (! is_a($object, $class) && ! $object = $class::find($object)) {
            throw new AppException($class . ' not found');
        }
        return $object;
    }

    /**
     * Возвращает массив из id объектов
     *
     * @author oleg
     * @param ActiveRecord[] $objects
     * @return int[]
     */
    public static function getIds($objects)
    {
        $result = array();
        foreach ($objects as $object) {
            $result[] = $object->key;
        }
        return $result;
    }

    /**
     * Возвращает массив с данными объекта
     *
     * @author oleg
     * @param string|string[] $filter - названия полей
     * @return string[]
     */
    public function getData($filter = null)
    {
        if (! isset($filter)) {
            return $this->data;
        }
        $filter = (array) $filter;
        return array_intersect_key($this->data, array_flip($filter));
    }
}