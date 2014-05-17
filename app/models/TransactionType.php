<?php

class TransactionType extends ActiveRecord
{
    /*
     * Id
    */
    const ID_REFILL          = 1;
    const ID_ORDER_ADDING    = 2;
    const ID_ORDER_EXECUTING = 3;

    /**
     * Имя секции настроек БД, в которой хранятся объекты данного типа
     *
     * @var string
     */
    public static $dbConfigSection = 'database3';

}
