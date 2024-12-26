<?php

use Faker\Generator as Faker;

$factory->define(Modules\SavedReplies\Entities\SavedReply::class, function (Faker $faker) {
    return [
        'mailbox_id' => 1,
        'name' => $faker->name,
        'text' => $faker->text,
        'user_id' => 1
    ];
});
