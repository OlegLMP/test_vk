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
     * Конструктор
     *
     * @author oleg
     * @return void
     */
    public function __construct()
    {
        $this->viewsDir = realpath(__DIR__ . '/../views');
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
        $this->urls = (object) array(
            'controller' => '/' . $controller,
            'action'     => '/' . $controller . '/' . substr($actionMethod, 0, -6)
        );
        ob_start();
        $this->renderView('_layout/top');
    }

    /**
     * Работа после вызова Action метода
     *
     * @author oleg
     * @return void
     */
    public function after()
    {
        $this->renderView('_layout/bottom');
    }

    /**
     * Вывод предствления (вьюшки)
     *
     * @author oleg
     * @param string $name - путь/название
     * @param array $params - параметры для отображения
     * @return void
     */
    public function renderView($name, $params = array())
    {
        $urls = $this->urls;
        include $this->viewsDir . '/' . $name . '.phtml';
    }
}
