INSERT `transaction_type` (id, name) VALUES 
(1, 'Пополнение счёта'),
(2, 'Размещение заказа заказчиком'),
(3, 'Исполнение заказа исполнителем');

INSERT `transaction_status` (id, name) VALUES 
(1, 'Начата'),
(2, 'Блокировка не удалась'),
(3, 'Блокировка выполнена'),
(4, 'Проверка не удалась'),
(5, 'Отменена после неудачной проверки'),
(6, 'Проверка пройдена'),
(7, 'Завершена');


INSERT `transaction_action` (id, name) VALUES 
(1, 'Блокировка запланирована'),
(2, 'Блокировка установлена'),
(3, 'Снятие блокировки запланировано'),
(4, 'Блокировка снята'),
(5, 'Создание запланировано'),
(6, 'Создание завершено'),
(7, 'Изменение запланировано'),
(8, 'Изменение выполнено'),
(9, 'Инкрементирование без блокировки запланировано'),
(10, 'Инкрементирование без блокировки выполнено');

INSERT `bookkeeping_account` (id, name, is_asset) VALUES 
(1, 'Обязательства платежных систем', 1),
(2, 'Счет заказчиков', 0),
(3, 'Счет исполнителей', 0),
(4, 'Фонд оплаты заказов', 0),
(5, 'Прибыль', 0);
