
#
# Транзакции и логи транзакций
#

DROP TABLE IF EXISTS `transaction`;
CREATE TABLE `transaction` (
 `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
 `type` TINYINT UNSIGNED NOT NULL COMMENT "->transaction_type.id Тип транзакции",
 `status` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT "->transaction_status.id Статус транзакции",
 `initiator` INT UNSIGNED NOT NULL COMMENT "->initiator.id",
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
 `step` TINYINT UNSIGNED NOT NULL COMMENT "Номер шага в транзакции",
 `comment` VARCHAR(255) COMMENT "Комментарий к шагу в транзакции",
 `class` VARCHAR(25) COMMENT "Класс модели, с операцией над которой связан данный шаг транзакции, если применимо",
 `model` INT UNSIGNED COMMENT "Id модели, с операцией над которой связан данный шаг транзакции, если применимо",
 `created` TIMESTAMP NOT NULL DEFAULT "0000-00-00 00:00:00" COMMENT "Создан",
 `updated` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT "Изменён",
 PRIMARY KEY (`id`),
 KEY transaction_step_idx (`transaction`, `step`),
 KEY class_model_idx (`class`, `model`)
) ENGINE=INNODB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
