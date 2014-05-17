<?php

class TransactionStatus extends ActiveRecord
{
    /*
     * Id
    */
    const ID_STARTED       = 1;
    const ID_LOCK_FAILED   = 2;
    const ID_LOCKED        = 3;
    const ID_CHECK_FAILED  = 4;
    const ID_CHECKED       = 5;
    const ID_COMPLETED     = 6;
    const ID_CANCELED      = 7;

    /**
     * Имя секции настроек БД, в которой хранятся объекты данного типа
     *
     * @var string
     */
    public static $dbConfigSection = 'database3';

}
