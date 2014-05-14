<?php

for ($i = 0; $i < 10; $i ++) {
    User::create(array(
        'role'  => $i % 2 ? UserRole::ID_CUSTOMER : UserRole::ID_EXECUTOR,
        'email' => 'email' . $i . '@gmail.com',
    ));
}