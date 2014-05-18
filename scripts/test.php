#!/usr/bin/php
<?php
ini_set('display_errors', 1);
require_once realpath(__DIR__ . '/../app/app.inc.php');

$maxUserId = User::getDb()->sql('SELECT max(id) FROM user LIMIT 1', PDO::FETCH_COLUMN);
for ($i = $maxUserId + 1; $i <= $maxUserId + 100; $i ++) {
    $user = User::create(array(
        'email' => 'email' . $i . '@gmail.com',
    ));
    Transaction::transactionRefill($user, mt_rand(1, 100000));
    for ($j = 0; $j < 10; $j ++) {
        if ($user->data['balance'] < 1) {
            break;
        }
        Transaction::transactionNewOrder($user, mt_rand(1, $user->data['balance']));
    }
}
