<?php

class TransactionType extends ActiveRecord
{
    /*
     * Id
    */
    const ID_ORDER_ADDING    = 1;
    const ID_ORDER_EXECUTING = 2;

    /**
     * Имя секции настроек БД, в которой хранятся объекты данного типа
     *
     * @var string
     */
    public static $dbConfigSection = 'database3';

}
