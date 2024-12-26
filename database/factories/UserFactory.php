<?php

use Faker\Generator as Faker;

/*
|--------------------------------------------------------------------------
| Model Factories
|--------------------------------------------------------------------------
|
| This directory should contain each of the model factory definitions for
| your application. Factories provide a convenient way to generate new
| model instances for testing / seeding your application's database.
|
*/

$factory->define(App\User::class, function (Faker $faker) {
    return [
        'first_name' => $faker->firstName,
        'last_name'  => $faker->lastName,
        'email'      => $faker->unique()->safeEmail,
        'job_title'  => $faker->jobTitle,
        'phone'      => $faker->phoneNumber,
        'password'   => '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm', // secret
    ];
});

$factory->state(App\User::class, 'admin', function ($faker) {
    return [
        'first_name' => 'Admin',
        'role' => 2 ,//ROLE 2 = ADMIN
    ];
});

$factory->state(App\User::class, 'not_admin', function ($faker) {
    return [
        'first_name' => 'Not_Admin',
        'role' => 3 ,//ROLE 2 = ADMIN
    ];
});

$factory->state(App\User::class, 'has_saved_reply_permission', function ($faker) {
    return [
        'permissions' => [3]
    ];
});

$factory->state(App\User::class, 'does_not_have_saved_reply_permission', function ($faker) {
    return [
        'permissions' => [99999]
    ];
});
