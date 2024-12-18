<?php

return [
    'name' => 'SpamFilter',
    'auto' => env('SPAMFILTER_AUTO', true),
    'subject' => env('SPAMFILTER_SUBJECT', ''),
    'body' => env('SPAMFILTER_BODY', ''),
    'sender' => env('SPAMFILTER_SENDER', ''),
];
