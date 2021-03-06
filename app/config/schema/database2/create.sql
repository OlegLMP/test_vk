
#
# Заказы
#

DROP TABLE IF EXISTS `order`;
CREATE TABLE `order` (
 `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
 `status` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT "->order_status.id Статус заказа",
 `customer` INT UNSIGNED NOT NULL COMMENT "->user.id Заказчик",
 `total_cost` DECIMAL(18,2) NOT NULL COMMENT "Стоимость|Стоимость заказа",
 `commission` DECIMAL(18,2) NOT NULL COMMENT "Комиссия|Комиссия системы",
 `executor_fee` DECIMAL(18,2) NOT NULL COMMENT "Плата|Плата исполнителю за выполнение заказа",
 `executor` INT UNSIGNED COMMENT "->user.id Исполнитель",
 `locked` TINYINT UNSIGNED NOT NULL COMMENT "Флаг блокировки для транзакций",
 `transaction` INT UNSIGNED COMMENT "transaction->id Id транзакции, в которой создался заказ",
 `created` TIMESTAMP NOT NULL DEFAULT "0000-00-00 00:00:00" COMMENT "Создан",
 `updated` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT "Изменён",
 PRIMARY KEY (`id`),
 KEY customer_status_idx (`customer`, `status`),
 KEY executor_status_idx (`executor`, `status`),
 KEY status_executor_fee__id_idx (`status`, `executor_fee`, `id`),
 KEY status_created_id_idx (`status`, `created`, `id`)
) ENGINE=INNODB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `order_status`;
CREATE TABLE `order_status` (
  `id` TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255),
  PRIMARY KEY (`id`)
) ENGINE=INNODB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
