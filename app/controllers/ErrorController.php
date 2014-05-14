<?php
class ErrorController extends ControllerBase
{
    public function error404Action($uri)
    {
        http_response_code(404);
        echo "404 - Not Found";
    }
}
