<?php

class OrderStatus extends ActiveRecord
{
    /*
     * Id
    */
    const ID_NEW      = 1;
    const ID_EXECUTED = 2;

    /**
     * Имя секции настроек БД, в которой хранятся объекты данного типа
     *
     * @var string
     */
    public static $dbConfigSection = 'database2';

}
