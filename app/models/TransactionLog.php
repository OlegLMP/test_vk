<?php

class TransactionLog extends ActiveRecord
{
    /**
     * Имя секции настроек БД, в которой хранятся объекты данного типа
     *
     * @var string
     */
    public static $dbConfigSection = 'database3';

    /**
     * Нужно ли логировать изменение данных модели
     *
     * @var bool
     */
    public static $log = false;
}
