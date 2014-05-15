<?php
/**
 * Контроллер для ajax проверок
 */
class CheckController extends ControllerBase
{
    /**
     * Флаг вывода Ajax. Если установлен в true, то шапка и подвал не выводятся
     *
     * @var array
     */
    public $isAjax = true;

    /**
     * Результат валидации
     *
     * @var bool
     */
    public $result = false;

    /**
     * Сообщение об ошибке валидации
     *
     * @var string
     */
    public $errorMessage = '';

    /**
     * Работа после вызова Action метода
     *
     * @author oleg
     * @return void
     */
    public function after() {
        echo json_encode(array(
            'result'  => (bool) $this->result,
            'message' => $this->errorMessage,
        ));
    }

    /**
     * Проверка
     *
     * @author olegb
     * @return void
     */
    public function checkAction()
    {
        if (! isset($_GET['val']) || empty ($_GET['params'])) {
            return;
        }
        $val = $_GET['val'];
        $params = $_GET['params'];
        $params = json_decode($params);
        if (! is_array($params)) {
            return;
        }
        foreach ($params as $validParams) {
            if (! is_array($validParams) || empty($validParams[0]) || empty($validParams[1])) {
                continue;
            }
            if ($validParams[0] == 'form') {
                $method = 'validate' . $validParams[1];
                if (method_exists($this, $method)) {
                    if (! $this->result = $this->$method($val, $this->errorMessage)) {
                        return;
                    }
                }
            }
        }
    }

    /**
     * Проверка Email на корректность
     *
     * @author olegb
     * @param $val - проверяемое значение
     * @param &string $errorMessage - для возврата сообщения об ошибке
     * @return bool - true при удачной проверке, иначе false
     */
    public static function validateEmailAddress($val, & $errorMessage = null)
    {
        if ($val !== filter_var($val, FILTER_VALIDATE_EMAIL)) {
            $errorMessage = 'Укажите верный Email';
            return false;
        }
        return true;
    }

    /**
     * Проверка, свободен ли Email
     *
     * @author olegb
     * @param $val - проверяемое значение
     * @param &string $errorMessage - для возврата сообщения об ошибке
     * @return bool - true при удачной проверке, иначе false
     */
    public static function validateEmailNotExists($val, & $errorMessage = null)
    {
        if (User::findOneBy('email', $val)) {
            $errorMessage = 'Этот Email уже занят';
            return false;
        }
        return true;
    }

}
