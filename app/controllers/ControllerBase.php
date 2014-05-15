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
            ob_end_clean();
            $this->renderView('_layout/top', array('jsScripts' => $this->jsScripts));
            echo $content;
            $this->renderView('_layout/bottom');
        }
    }

    /**
     * Вывод представления (вьюшки)
     *
     * @author oleg
     * @param string $path - путь/название. Если не задан, берётся controller/action
     * @param array $params - параметры для отображения
     * @return void
     */
    public function renderView($path = null, $params = array())
    {
        if (! isset($path)) {
            $path = $this->urls['action'];
        }
        $urls = $this->urls;
        include $this->viewsDir . (strpos($path, '/') === 0 ? '' : '/') . $path . '.phtml';
    }
}
