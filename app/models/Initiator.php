<?php
/**
 * @author oleg
 */
class Initiator extends ActiveRecord
{
    /**
     * Присутствует ли в таблице поле created
     *
     * @var bool
     */
    public static $fieldCreated = false;

    /**
     * Нужно ли логировать изменение данных модели
     *
     * @var bool
     */
    public static $log = false;

    /**
     * Создание ключа инициатора
     *
     * @author oleg
     * @return int
     */
    public static function createInitiatorKey()
    {
        $createData = array();
        if (PHP_SAPI != 'cli' && $userKey = ControllerBase::getLoginedUserKey()) {
            $createData['user'] = $userKey;
        }
        $createData['ip'] = strval(F::getIp());
        $systemUser = '';
        if (! empty($_SERVER['USER'])) {
            $systemUser .= $_SERVER['USER'];
        }
        if (! empty($_SERVER['SUDO_USER'])) {
            $systemUser .= ' (' . $_SERVER['SUDO_USER'] . ' via sudo)';
        }
        $createData['system_user'] = $systemUser;
        if (! empty($_SERVER['REQUEST_URI'])) {
            $createData['url'] = $_SERVER['REQUEST_URI'];
        } elseif (! empty($_SERVER['PHP_SELF'])) {
            $createData['url'] = $_SERVER['PHP_SELF'];
        }
        if ($key = Initiator::findOneByParams($createData, true)) {
            return $key;
        }
        try {
            $key = Initiator::create($createData, true);
        } catch (Exception $e) {
        	// Видимо создался другим потоком, пробуем прочитать его
            $key = Initiator::findOneByParams($createData, true);
        }
        if (! $key) {
            throw new AppException('Не удалось создать Initiator');
        }
        return $key;
    }

}