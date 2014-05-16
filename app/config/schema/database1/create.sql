
#
# Пользователи
#

DROP TABLE IF EXISTS `user`;
CREATE TABLE `user` (
 `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
 `role` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT "->user_role.id Роль|В какой роли выступает: заказчик или исполнитель",
 `email` varchar(50) NOT NULL COMMENT "E-Mail",
 `first_name` VARCHAR(255) NOT NULL COMMENT "Имя",
 `last_name` VARCHAR(255) NOT NULL COMMENT "Фамилия",
 `password_hash` VARCHAR(255) COMMENT "Хэш пароля, он же соль",
 `created` TIMESTAMP NOT NULL DEFAULT "0000-00-00 00:00:00" COMMENT "Создан",
 `updated` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT "Изменён",
 PRIMARY KEY (`id`),
 UNIQUE KEY email_idx (`email`)
) ENGINE=INNODB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `user_role`;
CREATE TABLE `user_role` (
  `id` TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255),
  PRIMARY KEY (`id`)
) ENGINE=INNODB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

#
# Логи
#

DROP TABLE IF EXISTS `log`;
CREATE TABLE `log` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `created` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `initiator` INT UNSIGNED NOT NULL COMMENT "->initiator.id",
  `class` VARCHAR(25) NOT NULL COMMENT "Класс модели",
  `model` INT UNSIGNED NOT NULL COMMENT "Id модели",
  `data` MEDIUMTEXT NOT NULL COMMENT "Установленные данные в виде SQL-выражения",
  PRIMARY KEY (`id`),
  KEY class_model_idx(`class`, `model`)
) ENGINE=INNODB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `initiator`;
CREATE TABLE `initiator` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user` INT UNSIGNED NOT NULL COMMENT "->user.id",
  `ip` VARCHAR(39) NOT NULL COMMENT "IP адрес. Например 127.0.0.1 или 2001:0db8:11a3:09d7:1f34:8a2e:07a0:765d",
  `system_user` VARCHAR(50) NOT NULL COMMENT "Системный пользователь. Например letmeprint (oleg via sudo)",
  `url` VARCHAR(255) NOT NULL COMMENT "URL скрипта",
  `created` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY user_ip_system_user_url_cron_idx(`user`,`ip`,`system_user`,`url`)
) ENGINE=INNODB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

