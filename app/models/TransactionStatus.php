<?php

class TransactionStatus extends ActiveRecord
{
    /*
     * Id
    */
    const ID_STARTED  = 1;
    const ID_FINISHED = 2;

    /**
     * Имя секции настроек БД, в которой хранятся объекты данного типа
     *
     * @var string
     */
    public static $dbConfigSection = 'database3';

}
