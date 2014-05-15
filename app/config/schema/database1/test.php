<?php

for ($i = 0; $i < 10; $i ++) {
    User::create(array(
        'email' => 'email' . $i . '@gmail.com',
    ));
}