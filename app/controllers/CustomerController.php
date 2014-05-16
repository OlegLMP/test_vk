<?php
class CustomerController extends ControllerBase
{
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
        if (! AuthController::isLogined() || AuthController::getLoginedUser()['role'] != UserRole::ID_CUSTOMER) {
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
        $this->renderView();
    }
}
