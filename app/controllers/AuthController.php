<?php
class AuthController extends ControllerBase
{
    const RESTRICT_NAME_MAX_LENGTH     = 100;
    const RESTRICT_NAME_REGEXP         = '^[a-zA_Zа-яА-Я \-\']+$';
    const RESTRICT_PASSWORD_MIN_LENGTH = 4;
    const RESTRICT_PASSWORD_MAX_LENGTH = 20;

    /**
     * Флаг вывода Ajax. Если установлен в true, то шапка и подвал не выводятся
     *
     * @var array
     */
    public $isAjax = true;

    /**
     * Загрузка формы регистрации в диалоговое окно
     *
     * @author oleg
     * @return void
     */
    public function registerAction()
    {
        $this->renderView();
    }

    /**
     * AJAX ответ на 1 шаг регистрации, загрузка 2 шага
     *
     * @author oleg
     * @return void
     */
    public function register2Action($uri)
    {
        if (! isset($_POST['hash']) || ! $this->checkFormHash($_POST['hash'])) {
            echo json_encode(array('status' => 'error', 'hash' => $this->generateFormHash(),
                'message' => '<b>Отправка не удалась, попробуйте ещё раз.</b><br/>
Возможно у Вашего браузера отключены Cookies.'));
            return;
        }
        if (! isset($_POST['first_name']) || ! strlen($_POST['first_name'])
            || ! isset($_POST['last_name']) || ! strlen($_POST['last_name'])
            || ! preg_match('~' . self::RESTRICT_NAME_REGEXP . '~u', $_POST['first_name'] . $_POST['last_name'])) {
            echo json_encode(array('status' => 'error', 'message' => '<b>Пожалуйста, укажите Ваше имя и фамилию.</b><br/>
В целях корректной обработки платежей, используйте настоящие имя и фамилию.'));
            return;
        }
        $len = max(mb_strlen($_POST['first_name']), mb_strlen($_POST['last_name']));
        if ($len > self::RESTRICT_NAME_MAX_LENGTH) {
            echo json_encode(array('status' => 'error', 'message' => '<b>Допускаются имена и фамилии не длинее ' . self::RESTRICT_NAME_MAX_LENGTH . ' символов</b><br/>
Введите Ваши имя и фамилию.'));
            return;
        }
        $this->_saveInSession('first_name', $_POST['first_name']);
        $this->_saveInSession('last_name', $_POST['last_name']);
        ob_start();
        $this->renderView();
        $content = ob_get_clean();
        ob_end_clean();
        echo json_encode(array('status' => 'ok', 'message' => '<b>Пожалуйста, укажите Ваш Email.</b><br/>
На данном этапе немедленного подтверждения адреса Email не потребуется.', 'action' => '/auth/register3', 'content' => $content));
    }

    /**
     * AJAX ответ на 2 шаг регистрации, загрузка 3 шага
     *
     * @author oleg
     * @return void
     */
    public function register3Action($uri)
    {
        if (! isset($_POST['hash']) || ! $this->checkFormHash($_POST['hash'])) {
            echo json_encode(array('status' => 'error', 'hash' => $this->generateFormHash(),
                'message' => '<b>Отправка не удалась, попробуйте ещё раз.</b><br/>
Возможно у Вашего браузера отключены Cookies.'));
            return;
        }
        if (! isset($_POST['email']) || ! strlen($_POST['email'])) {
            echo json_encode(array('status' => 'error', 'message' => '<b>Пожалуйста, укажите Ваш Email.</b><br/>
На данном этапе немедленного подтверждения адреса Email не потребуется.'));
            return;
        }
        if (! CheckController::validateEmailAddress($_POST['email'], $errorMessage)) {
            echo json_encode(array('status' => 'error', 'message' => '<b>' . $errorMessage . '</b><br/>
Укажите Email в правильном формате.'));
            return;
        }
        if (! CheckController::validateEmailNotExists($_POST['email'], $errorMessage)) {
            echo json_encode(array('status' => 'error', 'message' => '<b>' . $errorMessage . '</b><br/>
Для регистрации нужен ещё не занятый Email.'));
            return;
        }
        $this->_saveInSession('email', $_POST['email']);
        ob_start();
        $this->renderView();
        $content = ob_get_clean();
        ob_end_clean();
        echo json_encode(array('status' => 'ok', 'message' => '<b>Пожалуйста, выберите пароль.</b><br/>
Введите и запомните Ваш пароль для входа.', 'action' => '/auth/register4', 'content' => $content));
    }

    /**
     * AJAX ответ на 3 шаг регистрации, редирект
     *
     * @author oleg
     * @return void
     */
    public function register4Action($uri)
    {
        if (! isset($_POST['hash']) || ! $this->checkFormHash($_POST['hash'])) {
            echo json_encode(array('status' => 'error', 'hash' => $this->generateFormHash(),
                'message' => '<b>Отправка не удалась, попробуйте ещё раз.</b><br/>
Возможно у Вашего браузера отключены Cookies.'));
            return;
        }
        if (! isset($_POST['password']) || ! strlen($_POST['password'])) {
            echo json_encode(array('status' => 'error', 'message' => '<b>Пожалуйста, выберите пароль.</b><br/>
Введите и запомните Ваш пароль для входа.'));
            return;
        }
        $len = mb_strlen($_POST['password']);
        if ($len < self::RESTRICT_PASSWORD_MIN_LENGTH || $len > self::RESTRICT_PASSWORD_MAX_LENGTH) {
            echo json_encode(array('status' => 'error', 'message' => '<b>Длина пароля должна быть от ' . self::RESTRICT_PASSWORD_MIN_LENGTH . ' до ' . self::RESTRICT_PASSWORD_MAX_LENGTH . ' символов</b><br/>
Пожалуйста, выберите пароль подходящей длины.'));
            return;
        }
        if (! isset($_POST['passwordConfirm']) || strcmp($_POST['password'], $_POST['passwordConfirm'])) {
            echo json_encode(array('status' => 'error', 'message' => '<b>Повторите набор пароля</b><br/>
Введённые пароли различаются, попробуйте повторить ввод.'));
            return;
        }
        $regData = $this->_loadFromSession();
        if (! isset($regData['first_name']) || ! isset($regData['last_name']) || ! $regData['email'] || count($regData) != 3) {
            echo json_encode(array('status' => 'error', 'message' => '<b>Ошибка регистрации</b><br/>
Возможно у Вашего браузера отключены Cookies. Включите их и перезапустите браузер.'));
            return;
        }
        try {
            $user = User::create($regData);
        } catch (Exception $e) {
            echo json_encode(array('status' => 'error', 'message' => '<b>Ошибка регистрации</b><br/>
Возможно выбранный Вами Email уже занят, попробуйте пройти регистрацию с начала'));
            return;
        }
        $password_hash = password_hash($_POST['password'], PASSWORD_DEFAULT, array('cost' => 11));
        $user->writeData('password_hash', $password_hash);
        $this->_saveLogin($user);
        echo json_encode(array('status' => 'redirect', 'url' => '/customer'));
    }

    /**
     * Загрузка формы входа в диалоговое окно
     *
     * @author oleg
     * @return void
     */
    public function loginAction()
    {
        $this->renderView();
    }

    /**
     * Проверка логина и пароля, AJAX ответ либо редирект
     *
     * @author oleg
     * @return void
     */
    public function login2Action()
    {
        if (! isset($_POST['hash']) || ! $this->checkFormHash($_POST['hash'])) {
            echo json_encode(array('status' => 'error', 'hash' => $this->generateFormHash(),
                'message' => '<b>Отправка не удалась, попробуйте ещё раз.</b><br/>
Возможно у Вашего браузера отключены Cookies.'));
            return;
        }
        if (! isset($_POST['email']) || ! isset($_POST['password'])
            || (! $user = User::findOneBy('email', mb_substr($_POST['email'], 0, 255)))
            || ! password_verify(mb_substr($_POST['password'], 0, 255), $user->data['password_hash'])) {
            echo json_encode(array('status' => 'error', 'message' => '<b>Не удается войти.</b><br/>
Пожалуйста, проверьте правильность написания <b>логина</b> и <b>пароля</b>.<br/>
Проверьте <b>раскладку</b> клавиатуры и клавишу CAPS LOCK.'));
            return;
        }
        $this->_saveLogin($user);
        echo json_encode(array('status' => 'redirect', 'url' => $user->data['role'] == UserRole::ID_CUSTOMER ? '/customer' : '/executor'));
    }

    /**
     * Выход
     *
     * @author oleg
     * @return void
     */
    public function logoutAction()
    {
        if (! isset($_GET['hash']) || ! $this->checkFormHash($_GET['hash'], 'logout')) {
            $this->redirect('/?wrong_hash=1');
            return;
        }
        $this->_logout();
        $this->redirect('/');
    }

    /**
     * Запоминает регистрационный параметр в сессии
     *
     * @author oleg
     * @param string $name - Имя параметра
     * @param string $name - Значение параметра
     * @return void
     */
    private function _saveInSession($name, $val)
    {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        if (! is_array($_SESSION['reg_data'])) {
            $_SESSION['reg_data'] = array();
        }
        $_SESSION['reg_data'][$name] = $val;
    }

    /**
     * Достаёт массив запомненных регистрационных данных из сессии
     *
     * @author oleg
     * @return void
     */
    private function _loadFromSession()
    {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        return isset($_SESSION['reg_data']) ? $_SESSION['reg_data'] : array();
    }

    /**
     * Запоминает вошедшего пользователя в сессии
     *
     * @author oleg
     * @param @param User - авторизованный пользователь
     * @return void
     */
    private function _saveLogin($user)
    {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['logined'] = $user->data;
    }

    /**
     * Выход
     *
     * @author oleg
     * @return void
     */
    private function _logout()
    {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['logined'] = null;
    }

    /**
     * Проверяет, авторизован ли пользователь
     *
     * @author oleg
     * @return bool
     */
    public static function isLogined()
    {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        return ! empty($_SESSION['logined']);
    }

    /**
     * Возвращает данные авторизованного пользователя
     *
     * @author oleg
     * @return array | null
     */
    public static function getLoginedUser()
    {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        if (empty($_SESSION['logined'])) {
            return null;
        } else {
            return $_SESSION['logined'];
        }
    }

}
