<?php
/**
 * @author oleg
*/
class Transaction extends ActiveRecord
{
    /**
     * Имя секции настроек БД, в которой хранятся объекты данного типа
     *
     * @var string
     */
    public static $dbConfigSection = 'database3';

    /**
     * Транзакция 1. Пополнение счёта.
     *
     * @author oleg
     * @param User|int $user - пользователь, которому пополняется счёт
     * @param float $amount - сумма, на которую пополняется счёт
     * @return bool - true, если удалось выполнить транзакцию, иначе false
     */
    public static function refillTransaction($user, $amount)
    {
        // Проверка входных данных
        $user = User::checkOrFind($user);
        $amount = round($amount, 2);
        if ($amount <= 0) {
            throw new AppException('Сумма пополнения должна быть больше 0');
        }

        // Открытие транзакции
        $transaction = self::create(array(
            'type'      => TransactionType::ID_REFILL,
            'initiator' => Initiator::createInitiatorKey(),
            'amount'    => $amount,
        ));
        // Блокировка моделей
        if (! $locked = $transaction->_lock(array($user))) {
            return false;
        }

        // Проверка данных в транзакции (не требуется)
        $transaction->writeData('status', TransactionStatus::ID_CHECKED);

        // Совершение действий в транзакции

        // 1) Заносим проводку по бухгалтерскому счёту "Счет заказчиков"
        $transaction->_create('BookkeepingAccountEntry', array(
            'bookkeeping_account' => BookkeepingAccount::ID_CUSTOMERS_BALANCE,
            'amount'              => $amount,
            'class'               => 'User',
            'model'               => $user->key,
            'comment'             => 'Пополнение счёта'
        ), $amount);

        // 2) Изменяем баланс бухгалтерского счёта "Счет заказчиков"
        $transaction->_increment('BookkeepingAccount', BookkeepingAccount::ID_CUSTOMERS_BALANCE, 'balance', $amount);

        // 3) Заносим проводку по бухгалтерскому счёту "Обязательства платежных систем"
        $transaction->_create('BookkeepingAccountEntry', array(
            'bookkeeping_account' => BookkeepingAccount::ID_PAY_SYSTEM,
            'amount'              => $amount,
            'class'               => 'User',
            'model'               => $user->key,
            'comment'             => 'Платеж на пополнение счёта'
        ), $amount);

        // 4) Изменяем баланс бухгалтерского счёта "Обязательства платежных систем"
        $transaction->_increment('BookkeepingAccount', BookkeepingAccount::ID_PAY_SYSTEM, 'balance', $amount);

        // 5) Изменяем баланс у пользователя
        $transaction->_change($user, 'balance', $user->data['balance'] + $amount, $amount);

        //Снимаем блокировки
        $transaction->_unlock($locked);

        // Завершаем транзакцию
        $transaction->writeData('status', TransactionStatus::ID_COMPLETED);
        return true;
    }

    /**
     * Транзакция 2. Размещение заказа.
     *
     * @author oleg
     * @param User|int $user - пользователь, заказчик
     * @param float $amount - стоимость заказа
     * @return bool - true, если удалось выполнить транзакцию, иначе false
     */
    public static function neworderTransaction($user, $amount)
    {
        // Проверка входных данных
        $user = User::checkOrFind($user);
        $amount = round($amount, 2);
        if ($amount <= 0) {
            throw new AppException('Стоимость заказа должна быть больше 0');
        }
        if ($user->data['balance'] < $amount) {
            throw new AppException('Недостаточно средств на балансе заказчика');
        }
        $commission = round($amount / 100 * Config::get('settings.commission'), 2);
        if ($commission < 0 || $commission > $amount) {
            throw new AppException('Коммиссия системы не может быть меньше 0 или больше стоимости заказа');
        }

        // Открытие транзакции
        $transaction = self::create(array(
            'type'      => TransactionType::ID_ORDER_ADDING,
            'initiator' => Initiator::createInitiatorKey(),
            'amount'    => $amount,
        ));
        // Блокировка моделей
        if (! $locked = $transaction->_lock(array($user))) {
            return false;
        }

        // Проверка данных в транзакции
        if ($user->data['balance'] < $amount) {
            $transaction->writeData('status', TransactionStatus::ID_CHECK_FAILED);
            //Снимаем блокировки
            $transaction->_unlock($locked);
            // Отменяем транзакцию
            $transaction->writeData('status', TransactionStatus::ID_CANCELED);
            return false;
        }
        $transaction->writeData('status', TransactionStatus::ID_CHECKED);

        // Совершение действий в транзакции

        // 1) Изменяем баланс у пользователя
        $transaction->_change($user, 'balance', $user->data['balance'] - $amount, - $amount);

        // 2) Заносим проводку по бухгалтерскому счёту "Счет заказчиков"
        $transaction->_create('BookkeepingAccountEntry', array(
            'bookkeeping_account' => BookkeepingAccount::ID_CUSTOMERS_BALANCE,
            'amount'              => - $amount,
            'class'               => 'User',
            'model'               => $user->key,
            'comment'             => 'Размещение заказа заказчиком'
        ), $amount);

        // 3) Изменяем баланс бухгалтерского счёта "Счет заказчиков"
        $transaction->_increment('BookkeepingAccount', BookkeepingAccount::ID_CUSTOMERS_BALANCE, 'balance', - $amount);

        // 4) Создаём заказ
        $order = $transaction->_create('Order', array(
            'customer'     => $user->key,
            'total_cost'   => $amount,
            'commission'   => $commission,
            'executor_fee' => $amount - $commission,
        ), $amount);

        // 5) Заносим проводку по бухгалтерскому счёту "Фонд оплаты заказов"
        $transaction->_create('BookkeepingAccountEntry', array(
            'bookkeeping_account' => BookkeepingAccount::ID_ORDERS_FUND,
            'amount'              => $amount,
            'class'               => 'Order',
            'model'               => $order->key,
            'comment'             => 'Размещение заказа заказчиком'
        ), $amount);

        // 6) Изменяем баланс бухгалтерского счёта "Фонд оплаты заказов"
        $transaction->_increment('BookkeepingAccount', BookkeepingAccount::ID_ORDERS_FUND, 'balance', $amount);

        //Снимаем блокировки
        $transaction->_unlock($locked);

        // Завершаем транзакцию
        $transaction->writeData('status', TransactionStatus::ID_COMPLETED);
        return true;
    }

    /**
     * Блокировка моделей
     * Модели блокируются всегда в алфавитном порядке по классу и ключевому полю во избежание перекрёстных блокировок
     * После блокировки модели перечитываются из базы для актуализации данных
     *
     * @author oleg
     * @param array $objects - массив объектов, которые нужно заблокировать
     * @return array Отсортированный массив заблокированных объектов, иначе false
     */
    private function _lock($objects)
    {
        $objects = (array) $objects;
        $sorted = array();
        foreach ($objects as $object) {
            $sorted[get_class($object) . $object->key] = $object;
        }
        ksort($sorted, SORT_STRING);
        $locked = array();
        foreach ($sorted as $object) {
            $class = get_class($object);
            TransactionLog::create(array(
                'transaction' => $this->key,
                'action'      => TransactionAction::ID_LOCK,
                'class'       => $class,
                'model'       => $object->key,
            ), true);
            if ($this->_lockObject($object)) {
                $object->reload();
                $locked[] = $object;
                TransactionLog::create(array(
                    'transaction' => $this->key,
                    'action'      => TransactionAction::ID_LOCKED,
                    'class'       => $class,
                    'model'       => $object->key,
                ), true);
            } else {
                $this->_unlock($locked);
                $this->writeData('status', TransactionStatus::ID_LOCK_FAILED);
                return false;
            }
        }
        $this->writeData('status', TransactionStatus::ID_LOCKED);
        return $locked;
    }

    /**
     * Блокировка модели
     * Делается 5 попыток с периодичностью 0.1 - 0.4 секунд между попытками
     *
     * @author oleg
     * @param array $object - объект, который нужно заблокировать
     * @return bool - true, если удалось выполнить блокировку, иначе false
     */
    private function _lockObject($object)
    {
        $class = get_class($object);
        for ($i = 1; $i <= 5; $i ++) {
            $db = Db::get($class::$dbConfigSection);
            if ($db->sql('UPDATE ' . Db::name($class::getTableName()) . ' SET locked = 1
WHERE ' . Db::name($class::$keyField) . '=' . $db->prepare($object->key) . '
&& locked = 0')) {
                return true;
            }
            if ($i <= 4) {
                usleep($i * 100000);
            }
        }
        return false;
    }


    /**
     * Снятие блокировки с моделей
     *
     * @author oleg
     * @param array $objects - массив объектов, с которых нужно снять блокировку
     * @return void
     */
    private function _unlock($objects)
    {
        $objects = (array) $objects;
        foreach ($objects as $object) {
            $class = get_class($object);
            TransactionLog::create(array(
                'transaction' => $this->key,
                'action'      => TransactionAction::ID_UNLOCK,
                'class'       => $class,
                'model'       => $object->key,
                ), true);
            $db = Db::get($class::$dbConfigSection);
            if ($db->sql('UPDATE ' . Db::name($class::getTableName()) . ' SET locked = 0
WHERE ' . Db::name($class::$keyField) . '=' . $db->prepare($object->key))) {
            TransactionLog::create(array(
                'transaction' => $this->key,
                'action'      => TransactionAction::ID_UNLOCKED,
                'class'       => $class,
                'model'       => $object->key,
            ), true);
            }
        }
    }

    /**
     * Создание модели
     *
     * @author oleg
     * @param string $class - класс создаваемой модели
     * @param array $objects - массив данных для установки у создаваемой модели
     * @param float $amount - сумма, участвующая в операции для отображения в журнале транзакций
     * @return ActiveRecord - созданная модель
     */
    private function _create($class, $data, $amount)
    {
        TransactionLog::create(array(
            'transaction' => $this->key,
            'action'      => TransactionAction::ID_CREATE,
            'class'       => $class,
            'amount'      => $amount,
        ), true);
        $model = $class::create($data);
        TransactionLog::create(array(
            'transaction' => $this->key,
            'action'      => TransactionAction::ID_CREATED,
            'class'       => $class,
            'model'       => $model->key,
            'amount'      => $amount,
        ), true);
        return $model;
    }

    /**
     * Инкремент поля модели без блокировки модели
     *
     * @author oleg
     * @param string $class - класс модели
     * @param int $key - значение ключевого поля модели
     * @param string $field - поле, которое будет инкрементировано
     * @param float $amount - сумма, на которую будет выполнено инкрементирование.
     *      Отрицательная сумма эквивалентна декрементированию
     * @return void
     */
    private function _increment($class, $key, $field, $amount)
    {
        TransactionLog::create(array(
            'transaction' => $this->key,
            'action'      => TransactionAction::ID_INCREMENT,
            'class'       => $class,
            'model'       => $key,
            'field'       => $field,
            'amount'      => $amount,
        ), true);
        $db = Db::get($class::$dbConfigSection);
        $db->sql('UPDATE ' . Db::name($class::getTableName()) . '
SET ' . Db::name($field) . ' = ' . Db::name($field) . ' + (' . floatval($amount) . ')
WHERE ' . Db::name($class::$keyField) . '=' . $db->prepare($key));
        TransactionLog::create(array(
            'transaction' => $this->key,
            'action'      => TransactionAction::ID_INCREASED,
            'class'       => $class,
            'model'       => $key,
            'field'       => $field,
            'amount'      => $amount,
        ), true);
    }

    /**
     * Изменение поля модели без блокировки модели
     *
     * @author oleg
     * @param string $class - класс модели
     * @param string $field - поле, которое будет инкрементировано
     * @param string $newValue - новое значение, которое будет установлено
     * @param float $amount - сумма, участвующая в операции для отображения в журнале транзакций
     * @return void
     */
    private function _change($model, $field, $newValue, $amount = null)
    {
        $oldValue = $model->data[$field];
        TransactionLog::create(array(
            'transaction' => $this->key,
            'action'      => TransactionAction::ID_CHANGE,
            'class'       => get_class($model),
            'model'       => $model->key,
            'field'       => $field,
            'amount'      => $amount,
            'old_value'   => $oldValue,
            'new_value'   => $newValue,
        ), true);
        $model->writeData($field, $newValue);
        TransactionLog::create(array(
            'transaction' => $this->key,
            'action'      => TransactionAction::ID_CHANGED,
            'class'       => get_class($model),
            'model'       => $model->key,
            'field'       => $field,
            'amount'      => $amount,
            'old_value'   => $oldValue,
            'new_value'   => $newValue,
        ), true);
    }
}