<?php
abstract class ControllerBase
{
    /**
     * Путь к папке с вьюшками
     *
     * @var string
     */
    public $viewsDir;

    /**
     * Массив с URL на текущий контроллер и вьюшку для вывода ссылок во вьюшке
     *
     * @var array
     */
    public $urls;

    /**
     * Массив со ссылками на JS скрипты, которые нужно подключить при выводе страницы
     *
     * @var array
     */
    public $jsScripts = array();

    /**
     * Флаг вывода Ajax. Если установлен в true, то шапка и подвал не выводятся
     *
     * @var array
     */
    public $isAjax = false;

    /**
     * Авторизованный пользователь
     *
     * @var User
     */
    public $loginedUser;

    /**
     * Конструктор
     *
     * @author oleg
     * @return void
     */
    public function __construct()
    {
        $this->viewsDir = realpath(__DIR__ . '/../views');
        $this->jsScripts[] = '/js/jquery-1.11.1.min.js';
        $this->jsScripts[] = '/js/layout.js';
        $this->jsScripts[] = '/js/field-check.js';
    }

    /**
     * Работа до вызова Action метода
     *
     * @author oleg
     * @param string $actionMethod - имя Action метода, который планируется вызвать
     * @return void
     */
    public function before($actionMethod)
    {
        $controller = substr(get_called_class(), 0, -10);
        $controller = lcfirst($controller);
        $this->urls = array(
            'controller' => '/' . $controller,
            'action'     => '/' . $controller . '/' . substr($actionMethod, 0, -6)
        );
        ob_start();
    }

    /**
     * Работа после вызова Action метода
     *
     * @author oleg
     * @return void
     */
    public function after()
    {
        if (! $this->isAjax) {
            $content = ob_get_clean();
            $this->renderView(array(
                'jsScripts'   => $this->jsScripts,
                'loginedUser' => $this->getLoginedUser(),
            ), '_layout/top');
            echo $content;
            $this->renderView(null, '_layout/bottom');
        }
    }

    /**
     * Вывод представления (вьюшки)
     *
     * @author oleg
     * @param array $params - параметры для отображения
     * @param string $path - путь/название. Если не задан, берётся controller/action
     * @return void
     */
    public function renderView($params = null, $path = null)
    {
        if (! isset($path)) {
            $path = $this->urls['action'];
        }
        $urls = $this->urls;
        include $this->viewsDir . (strpos($path, '/') === 0 ? '' : '/') . $path . '.phtml';
    }

    /**
     * Генерация хэша для CSRF защиты формы
     *
     * @author oleg
     * @param string $formName - имя формы. Необходимо задать, если нужны несколько разных токенов на странице
     * @return string - сгенерированный хэш
     */
    public static function generateFormHash($formName = '')
    {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        return $_SESSION['form_csrf_hash' . $formName] = base64_encode(mcrypt_create_iv(8, MCRYPT_DEV_URANDOM));
    }

    /**
     * Проверка хэша CSRF защиты формы
     *
     * @author oleg
     * @param string $formName - имя формы. Необходимо задать, если нужны несколько разных токенов на странице
     * @return string - HTML код, содержащий input типа hidden со значением хэша
     */
    public static function getCsrfProtection($formName = '')
    {
        return '<input type="hidden" name="hash" value="' . F::escape(self::generateFormHash($formName)) . '" />';
    }

    /**
     * Проверка хэша CSRF защиты формы
     *
     * @author oleg
     * @param $hash - переданный хэш
     * @param string $formName - имя формы, с которым генерился токен
     * @return bool - true при удачное проверке, иначе false
     */
    public static function checkFormHash($hash, $formName = '')
    {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        $key = 'form_csrf_hash' . $formName;
        return ! empty($_SESSION[$key]) && ! strcmp($_SESSION[$key], $hash);
    }

    /**
     * Внутренний редирект
     *
     * @author oleg
     * @param $url - относительный URL (без домена)
     * @return void
     */
    public static function redirect($url)
    {
        if (ob_get_level()) {
            ob_end_clean();
        }
        $scheme = $_SERVER['SERVER_PORT'] == 80 ? 'http' : 'https';
        $link = $scheme . '://' . $_SERVER['HTTP_HOST'] . (strpos($url, '/') === 0 ? '' : '/') . $url;
        header('Location: ' . $link);
        exit();
    }

    /**
     * Проверяет, авторизован ли пользователь
     *
     * @author oleg
     * @return int | null - id авторизованного пользователя либо null, если пользователь не авторизован
     */
    public static function getLoginedUserKey()
    {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        return ! empty($_SESSION['logined']) ? $_SESSION['logined'] : null;
    }

    /**
     * Возвращает модель авторизованного пользователя
     *
     * @author oleg
     * @return User | false
     */
    public function getLoginedUser()
    {
        if (! $key = $this->getLoginedUserKey()) {
            return null;
        }
        if (! isset($this->loginedUser)) {
            if (! $this->loginedUser = User::find($key)) {
                AuthController::logout();
            }
        }
        return $this->loginedUser;
    }

}
