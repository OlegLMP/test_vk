<?php
class ErrorController extends ControllerBase
{
    public function error404Action($uri)
    {
        http_response_code(404);
        echo "Сраница, на которую вы перешли, не существует";
    }
}
