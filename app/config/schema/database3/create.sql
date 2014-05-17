
#
# Транзакции и логи транзакций
#

DROP TABLE IF EXISTS `transaction`;
CREATE TABLE `transaction` (
 `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
 `type` TINYINT UNSIGNED NOT NULL COMMENT "->transaction_type.id Тип транзакции",
 `status` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT "->transaction_status.id Статус транзакции",
 `initiator` INT UNSIGNED NOT NULL COMMENT "->initiator.id",
 `amount` DECIMAL(18,2) COMMENT "Сумма|Сумма, участвующая в транзакции, если такая есть",
 `created` TIMESTAMP NOT NULL DEFAULT "0000-00-00 00:00:00" COMMENT "Создан",
 `updated` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT "Изменён",
 PRIMARY KEY (`id`),
 KEY status_idx (`status`)
) ENGINE=INNODB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `transaction_type`;
CREATE TABLE `transaction_type` (
  `id` TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255),
  PRIMARY KEY (`id`)
) ENGINE=INNODB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `transaction_status`;
CREATE TABLE `transaction_status` (
  `id` TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255),
  PRIMARY KEY (`id`)
) ENGINE=INNODB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `transaction_log`;
CREATE TABLE `transaction_log` (
 `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
 `transaction` INT UNSIGNED NOT NULL COMMENT "->transaction.id Транзакция",
 `action` TINYINT UNSIGNED NOT NULL COMMENT "->transaction_action.id Действие",
 `class` VARCHAR(25) COMMENT "Класс модели, операция над которой проводится",
 `model` INT UNSIGNED COMMENT "Id модели, операция над которой проводится",
 `field` VARCHAR(255) COMMENT "Имя поля, операция над которым проводится",
 `amount` DECIMAL(18,2) COMMENT "Сумма|Сумма, участвующая в операции",
 `old_value` VARCHAR(255) COMMENT "Старое значение|Значение, которое было до операции",
 `new_value` VARCHAR(255) COMMENT "Новое значение|Значение, которое стало после операции",
 `created` TIMESTAMP NOT NULL DEFAULT "0000-00-00 00:00:00" COMMENT "Создан",
 `updated` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT "Изменён",
 PRIMARY KEY (`id`),
 KEY transaction_idx (`transaction`),
 KEY class_model_idx (`class`, `model`)
) ENGINE=INNODB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `transaction_action`;
CREATE TABLE `transaction_action` (
  `id` TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255),
  PRIMARY KEY (`id`)
) ENGINE=INNODB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

#
# Бухгалтерия
#

DROP TABLE IF EXISTS `bookkeeping_account`;
CREATE TABLE `bookkeeping_account` (
  `id` TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) COMMENT "Название|Название бухгалтерского счета",
  `is_asset` TINYINT UNSIGNED NOT NULL COMMENT "1 - Актив, 0 - Пассив",
  `balance` DECIMAL(18,2) NOT NULL COMMENT "Баланс|Сумма на бухгалтерском счету",
 `locked` TINYINT UNSIGNED NOT NULL COMMENT "Флаг блокировки для транзакций",
 `created` TIMESTAMP NOT NULL DEFAULT "0000-00-00 00:00:00" COMMENT "Создан",
 `updated` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT "Изменён",
  PRIMARY KEY (`id`)
) ENGINE=INNODB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `bookkeeping_account_entry`;
CREATE TABLE `bookkeeping_account_entry` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `bookkeeping_account` TINYINT UNSIGNED NOT NULL COMMENT "->bookkeeping_account.id Бухгалтерский счёт",
  `amount` DECIMAL(18,2) NOT NULL COMMENT "Сумма|Сумма проводки",
  `class` VARCHAR(25) COMMENT "Класс модели, с которым связана проводка",
  `model` INT UNSIGNED COMMENT "Id модели, с которой связана проводка",
  `comment` TEXT COMMENT "Комментарий",
 `created` TIMESTAMP NOT NULL DEFAULT "0000-00-00 00:00:00" COMMENT "Создан",
 `updated` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT "Изменён",
  PRIMARY KEY (`id`)
) ENGINE=INNODB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


