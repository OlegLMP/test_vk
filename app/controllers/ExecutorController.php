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
        $db = Db::get(Order::$dbConfigSection);
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
        $db = Db::get(Order::$dbConfigSection);
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
     * подготовка данных для кабинета исполнителя
     *
     * @author oleg
     * @return array
     */
    private function _getParams()
    {
        $db = Db::get(Order::$dbConfigSection);
        $ordersExecuted = $db->sql('SELECT count(*) FROM ' . Db::name('order') . '
WHERE executor=' . $db->prepare($this->getLoginedUser()->key) . '
&&status=' . OrderStatus::ID_EXECUTED . '
LIMIT 1', PDO::FETCH_COLUMN);
        return array(
            'loginedUser'  => $this->getLoginedUser(),
            'ordersExecuted' => $ordersExecuted,
        );
    }

    /**
     * Диалоговое окно пополнения счёта
     *
     * @author oleg
     * @return void
     */
    public function refillAction($uri)
    {
        $this->isAjax = true;
        $this->renderView();
    }

    /**
     * AJAX ответ на пополнение счёта, редирект
     *
     * @author oleg
     * @return void
     */
    public function refill2Action($uri)
    {
        $this->isAjax = true;
        if (! isset($_POST['hash']) || ! $this->checkFormHash($_POST['hash'])) {
            echo json_encode(array('status' => 'error', 'hash' => $this->generateFormHash(),
                'message' => '<b>Отправка не удалась, попробуйте ещё раз.</b><br/>
Возможно у Вашего браузера отключены Cookies.'));
            return;
        }
        if (! isset($_POST['amount']) || ! strlen($_POST['amount'])) {
            echo json_encode(array('status' => 'error', 'message' => '<b>Пожалуйста, введите сумму.</b><br/>
Выберите и введите сумму, которую вы хотите положить на Ваш счёт в системе УСВАЗ.'));
            return;
        }
        $amount = str_replace(' ', '', $_POST['amount']);
        $amount = round($amount, 2);
        if ($amount < self::RESTRICT_REFILL_MIN || $amount > self::RESTRICT_REFILL_MAX) {
            echo json_encode(array('status' => 'error', 'message' => '<b>Сумма может должна быть от ' . self::RESTRICT_REFILL_MIN . ' до ' . self::RESTRICT_REFILL_MAX . '</b><br/>
Пожалуйста, введите подходящую сумму.'));
            return;
        }
        if (! Transaction::refillTransaction($this->getLoginedUser(), $amount)) {
            echo json_encode(array('status' => 'error', 'message' => '<b>Не удалось выполнить операцию.</b><br/>
Обратитесь в техподдержку.'));
            return;
        }
        echo json_encode(array('status' => 'redirect', 'url' => '/customer'));
    }

    /**
     * Диалоговое окно размещения заказа
     *
     * @author oleg
     * @return void
     */
    public function neworderAction($uri)
    {
        $this->isAjax = true;
        $this->renderView();
    }

    /**
     * AJAX ответ на размещение заказа, редирект
     *
     * @author oleg
     * @return void
     */
    public function neworder2Action($uri)
    {
        $this->isAjax = true;
        if (! isset($_POST['hash']) || ! $this->checkFormHash($_POST['hash'])) {
            echo json_encode(array('status' => 'error', 'hash' => $this->generateFormHash(),
                'message' => '<b>Отправка не удалась, попробуйте ещё раз.</b><br/>
Возможно у Вашего браузера отключены Cookies.'));
            return;
        }
        if (! isset($_POST['amount']) || ! strlen($_POST['amount'])) {
            echo json_encode(array('status' => 'error', 'message' => '<b>Пожалуйста, введите сумму.</b><br/>
Выберите и введите сумму, которую вы хотите заплатить за выполнение вашего заказа.'));
            return;
        }
        $amount = str_replace(' ', '', $_POST['amount']);
        $amount = round($amount, 2);
        if ($amount < self::RESTRICT_ORDER_COST_MIN || $amount > self::RESTRICT_ORDER_COST_MAX) {
            echo json_encode(array('status' => 'error', 'message' => '<b>Сумма может должна быть от ' . self::RESTRICT_ORDER_COST_MIN . ' до ' . self::RESTRICT_ORDER_COST_MAX . '</b><br/>
Пожалуйста, введите подходящую сумму.'));
            return;
        }
        if ($amount > $this->getLoginedUser()->data['balance']) {
            echo json_encode(array('status' => 'error', 'message' => '<b>На Вашем счёте не достаточно средств</b><br/>
Пожалуйста, <a href="#dialog" name="modal" url="/customer/refill">пополните</a> ваш счёт либо введите меньшую сумму.'));
            return;
        }
        if (! Transaction::neworderTransaction($this->getLoginedUser(), $amount)) {
            echo json_encode(array('status' => 'error', 'message' => '<b>Не удалось выполнить операцию.</b><br/>
Обратитесь в техподдержку.'));
            return;
        }
        echo json_encode(array('status' => 'redirect', 'url' => '/customer/wait'));
    }

    /**
     * Страница успешного размещения заказа
     *
     * @author oleg
     * @return void
     */
    public function waitAction($uri)
    {
        $this->renderView($this->_getParams());
    }
}
