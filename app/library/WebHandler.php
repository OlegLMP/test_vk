<?php

class WebHandler
{
    /**
     * Контроллер для вывода ошибок
     *
     * @var string
     */
    const ERROR_CONTROLLER_CLASS = 'ErrorController';

    /**
     * Папка, где лежат контроллеры
     *
     * @var string
     */
    public $controllersDir;

    /**
     * Конструктор
     *
     * @author oleg
     * @return void
     */
    public function __construct()
    {
        $this->controllersDir = realpath(__DIR__ . '/../controllers');
    }

    /**
     * Обрабатывает запрос по URI
     *
     * @author oleg
     * @param string $requestUri
     * @return void
     */
    public function process($requestUri)
    {
        $path = parse_url($requestUri, PHP_URL_PATH);
        $path = explode('/', $path);
        array_shift($path);
        if (($pathCount = count($path)) && ! strlen($path[$pathCount - 1])) {
            unset($path[$pathCount - 1]);
        }
        if (! count($path)) {
            $path = array('index', 'index');
        }

        $controller = array_shift($path);
        $controller = preg_replace('~[^a-z0-9\-]~i', '', $controller);
        $controllerClass = ucfirst($controller) . 'Controller';
        if (! file_exists($this->controllersDir . '/' . $controllerClass . '.php')) {
            $controllerClass = self::ERROR_CONTROLLER_CLASS;
            $path = array('error404');
        }
        if (! count($path)) {
            $path = array('index');
        }

        $action = array_shift($path);
        $action = preg_replace('~[^a-z0-9\-]~i', '', $action);
        $actionMethod = $action . 'Action';
        $controllerInstance = new $controllerClass();
        if (! is_callable(array($controllerInstance, $actionMethod))) {
            if ($controllerClass != self::ERROR_CONTROLLER_CLASS) {
                $controllerClass = self::ERROR_CONTROLLER_CLASS;
                $controllerInstance = new $controllerClass();
                $actionMethod = 'error404Action';
            }
        }

        $uri = '/' . implode('/', $path);
        $controllerInstance->before($actionMethod);
        $controllerInstance->$actionMethod($uri);
        $controllerInstance->after();
    }
}
