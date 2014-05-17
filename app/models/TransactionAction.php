<?php

class TransactionAction extends ActiveRecord
{
    /*
     * Id
    */
    const ID_LOCK      = 1;
    const ID_LOCKED    = 2;
    const ID_UNLOCK    = 3;
    const ID_UNLOCKED  = 4;
    const ID_CREATE    = 5;
    const ID_CREATED   = 6;
    const ID_CHANGE    = 7;
    const ID_CHANGED   = 8;
    const ID_INCREMENT = 9;
    const ID_INCREASED = 10;

    /**
     * Имя секции настроек БД, в которой хранятся объекты данного типа
     *
     * @var string
     */
    public static $dbConfigSection = 'database3';

}
