<?php
class CustomerController extends ControllerBase
{
    const RESTRICT_REFILL_MIN = 1;
    const RESTRICT_REFILL_MAX = 100000;
    const RESTRICT_ORDER_COST_MIN = 1;
    const RESTRICT_ORDER_COST_MAX = 100000;

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
        if (! ($user = $this->getLoginedUser()) || $user->data['role'] != UserRole::ID_CUSTOMER) {
            $this->redirect('/');
            exit();
        }
    }

    /**
     * Главная страница кабинета заказчика
     *
     * @author oleg
     * @return void
     */
    public function indexAction($uri)
    {
        $this->renderView($this->_getParams());
    }

    /**
     * подготовка данных для кабинета заказчика
     *
     * @author oleg
     * @return array
     */
    private function _getParams()
    {
        $db = Db::get(Order::$dbConfigSection);
        $ordersInWork = $db->sql('SELECT count(*) FROM ' . Db::name('order') . '
WHERE customer=' . $db->prepare($this->getLoginedUser()->key) . '
&&status=' . OrderStatus::ID_NEW . '
LIMIT 1', PDO::FETCH_COLUMN);
        $ordersExecuted = $db->sql('SELECT count(*) FROM ' . Db::name('order') . '
WHERE customer=' . $db->prepare($this->getLoginedUser()->key) . '
&&status=' . OrderStatus::ID_EXECUTED . '
LIMIT 1', PDO::FETCH_COLUMN);
        return array(
            'loginedUser'  => $this->getLoginedUser(),
            'ordersInWork' => $ordersInWork,
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
        if (! Transaction::transactionRefill($this->getLoginedUser(), $amount)) {
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
        if (! Transaction::transactionNewOrder($this->getLoginedUser(), $amount)) {
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
