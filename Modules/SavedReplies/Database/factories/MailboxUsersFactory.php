<?php

use Faker\Generator as Faker;

$factory->define(App\MailboxUser::class, function (Faker $faker) {
    return [
        'mailbox_id' => $faker->randomNumber,
        'user_id' => $faker->randomNumber,
    ];
});
