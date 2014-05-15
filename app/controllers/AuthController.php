<?php
class AuthController extends ControllerBase
{
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
        if (! isset($_POST['firstname']) || ! strlen($_POST['firstname'])
            || ! isset($_POST['lastname']) || ! strlen($_POST['lastname'])) {
            echo json_encode(array('status' => 'error', 'message' => '<b>Пожалуйста, укажите Ваше имя и фамилию.</b><br/>
В целях корректной обработки платежей, используйте настоящие имя и фамилию.'));
        return;
        }
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
        if (! isset($_POST['email']) || ! strlen($_POST['email'])) {
            echo json_encode(array('status' => 'error', 'message' => '<b>Пожалуйста, укажите Ваш Email.</b><br/>
На данном этапе немедленного подтверждения адреса Email не потребуется.'));
            return;
        }
        if ($_POST['email'] !== filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
            echo json_encode(array('status' => 'error', 'message' => '<b>Пожалуйста, укажите верный Email.</b><br/>
Требуется указать Email в правильном формате.'));
            return;
        }
    }
}
