#!/usr/bin/php
<?php
ini_set('display_errors', 1);
require_once realpath(__DIR__ . '/../app/app.inc.php');

for ($i = 0; $i < 10; $i ++) {
    $user = User::create(array(
        'email' => 'email' . $i . '@gmail.com',
    ));
    Transaction::refillTransaction($user, mt_rand(1, 20000));
    for ($j = 0; $j < 10; $j ++) {
        if ($user->data['balance'] < 1) {
            break;
        }
        Transaction::neworderTransaction($user, mt_rand(1, $user->data['balance']));
    }
}
