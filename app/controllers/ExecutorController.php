<?php
class ExecutorController extends ControllerBase
{
    const ORDERS_SHOW_LIMIT = 50;

    /**
     * Работа до вызова Action метода
     *
     * @author oleg
     * @param string $actionMethod - имя Action метода, который планируется вызвать
     * @return void
     */
    public function before($actionMethod)
    {
        parent::before($actionMethod);
        if (! ($user = $this->getLoginedUser()) || $user->data['role'] != UserRole::ID_EXECUTOR) {
            $this->redirect('/');
            exit();
        }
    }

    /**
     * Главная страница кабинета исполнителя
     *
     * @author oleg
     * @return void
     */
    public function indexAction($uri)
    {
        $sortBy = 'created';
        $sortDirection = 'ASC';
        $db = Order::getDb();
        $orders = $db->sql('SELECT id, created, executor_fee FROM ' . Db::name(Order::getTableName()) . '
WHERE status=' . OrderStatus::ID_NEW . '
ORDER BY ' . Db::name($sortBy) . ' ' . $sortDirection . ', id ' . $sortDirection . ' LIMIT ' . self::ORDERS_SHOW_LIMIT);
        $this->renderView($this->_getParams() + array(
            'orders'        => $orders,
            'sortBy'        => $sortBy,
            'sortDirection' => $sortDirection,
        ));
    }

    /**
     * подготовка данных для кабинета исполнителя
     *
     * @author oleg
     * @return array
     */
    private function _getParams()
    {
        $db = Order::getDb();
        $ordersExecuted = $db->sql('SELECT count(*) FROM ' . Db::name(Order::getTableName()) . '
WHERE executor=' . $db->prepare($this->getLoginedUser()->key) . '
&&status=' . OrderStatus::ID_EXECUTED . '
LIMIT 1', PDO::FETCH_COLUMN);
        return array(
            'loginedUser'  => $this->getLoginedUser(),
            'ordersExecuted' => $ordersExecuted,
        );
    }

    /**
     * AJAX запрос на получение очередной порции заказов
     *
     * @author oleg
     * @return void
     */
    public function updateAction($uri)
    {
        $this->isAjax = true;
        $sortBy = (isset($_POST['sortBy']) && $_POST['sortBy'] == 'executor_fee') ? 'executor_fee' : 'created';
        $sortDirection = (isset($_POST['sortDirection']) && $_POST['sortDirection'] == 'DESC') ? 'DESC' : 'ASC';
        $lastData = (isset($_POST['lastData']) && strlen($_POST['lastData']) ? mb_substr($_POST['lastData'], 0, 255) : null);
        $lastId = (isset($_POST['lastId']) && strlen($_POST['lastId']) ? floor($_POST['lastId']) : 0);
        $db = Order::getDb();
        $orders = array();
        if ($lastData) {
            $orders = $db->sql('SELECT id, created, executor_fee FROM ' . Db::name(Order::getTableName()) . '
WHERE status=' . OrderStatus::ID_NEW . '
&& ' . Db::name($sortBy) . ' = ' . $db->prepare($lastData) . '
&& id' . ($sortDirection == 'ASC' ? '>' : '<') . $db->prepare($lastId) . '
ORDER BY id ' . $sortDirection . ' LIMIT ' . self::ORDERS_SHOW_LIMIT);
        }
        if (count($orders) < self::ORDERS_SHOW_LIMIT) {
            $orders = array_merge($orders, $db->sql('SELECT id, created, executor_fee FROM ' . Db::name(Order::getTableName()) . '
WHERE status=' . OrderStatus::ID_NEW . '
' . (isset($lastData) ? '&& ' . Db::name($sortBy) . ($sortDirection == 'ASC' ? '>' : '<') . $db->prepare($lastData) : '') . '
ORDER BY ' . Db::name($sortBy) . ' ' . $sortDirection . ', id ' . $sortDirection . ' LIMIT ' . (self::ORDERS_SHOW_LIMIT - count($orders))));
        }
        if (! $orders) {
            echo json_encode(array('status' => 'noRecords'));
            return;
        }
        ob_start();
        foreach ($orders as $order) {
            $this->renderView(array('order' => $order), '/executor/partial/order');
        }
        $html = ob_get_clean();
        ob_end_clean();
        echo json_encode(array(
            'status'   => 'ok',
            'html'     => $html,
            'lastData' => $order[$sortBy],
            'lastId'   => $order['id'],
        ));
    }

    /**
     * выполнение заказа
     *
     * @author oleg
     * @return void
     */
    public function execorderAction($uri)
    {
        $this->isAjax = true;
        $orderId = array_shift(explode('/', substr($uri, 1)));
        if (! $orderId || ! $order = Order::find($orderId)) {
            return $this->renderView(array('message' => 'Ошибка передачи данных.'));
        }
        if ($order->data['status'] != OrderStatus::ID_NEW) {
            return $this->renderView(array('message' => 'Извините, заказ уже выполнил кто-то другой'));
        }
        if (! Transaction::transactionExecOrder($this->getLoginedUser(), $order)) {
            return $this->renderView(array('message' => 'Извините, выполнение заказа не удалось, возможно заказ уже выполнил кто-то другой.'));
        }
        $this->renderView(array(
            'message' => 'Заказ выполнен успешно. На ваш счёт зачислена плата за выполнение заказа.',
            'balance' => $this->getLoginedUser()->data['balance']
        ) + $this->_getParams());
    }

}
