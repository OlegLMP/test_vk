<?php
/**
 * @author oleg
*/
class BookkeepingAccount extends ActiveRecord
{
    /*
     * Id
    */
    const ID_PAY_SYSTEM        = 1;
    const ID_CUSTOMERS_BALANCE = 2;
    const ID_EXECUTORS_BALANCE = 3;
    const ID_ORDERS_FUND       = 4;
    const ID_PROFIT            = 5;


    /**
     * Имя секции настроек БД, в которой хранятся объекты данного типа
     *
     * @var string
     */
    public static $dbConfigSection = 'database3';

}