<?php
/**
 * @author oleg
 */
class Log extends ActiveRecord
{

    /**
     * Нужно ли логировать изменение данных модели
     *
     * @var bool
     */
    public static $log = false;

}