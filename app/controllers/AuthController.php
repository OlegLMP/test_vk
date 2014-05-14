<?php
class AuthController extends ControllerBase
{
    /**
     * Флаг вывода Ajax. Если установлен в true, то шапка и подвал не выводятся
     *
     * @var array
     */
    public $isAjax = true;

    public function registerAction($uri)
    {
        $this->renderView();
    }
}
