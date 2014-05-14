<?php
/**
 * @author oleg
 */
class Debuger
{
    /**
     * Массив из отладочных сообщений
     *
     * @var array
     */
    public static $messages = array();

    /**
     * Добавляет сообщение отладки
     *
     * @author oleg
     * @param string $text - текст сообщения
     * @return void
     */
    public static function message($text)
    {
        $count = count(self::$messages);
        if ($count > 1000) {
            return;
        }
        $time = str_replace(',', '.', microtime(true));
        if ($count == 1000) {
            $text = 'Debuger disabled because of messages count > 1000';
        }
        self::$messages[] = date('Y-m-d H:i:s', $time) . (str_pad(strstr($time, '.'), 5, '0')) . '| ' . $text;
    }

    /**
     * Вывод сообщений в виде HTML
     *
     * @author olegb
     * @return string
     */
    public static function output()
    {
        if (! Config::get('settings.debug')) {
            return '';
        }
        if (! self::$messages) {
            return '';
        }
        self::message('Finished in ' . F::getScriptTime());
        $code = '<a class="debug-link" href="javascript:;" onclick="e=document.getElementsByClassName(\'debug\')[0];e.style.display=(e.style.display==\'block\'?\'none\':\'block\');">Отладка</a>
<div class="debug" style="display:none;">';
        foreach (self::$messages as $message) {
            $code .= $message . "<br/>\n";
        }
        $code .= '</div>';
        return $code;
    }

}