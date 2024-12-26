<?php

return [
    'name' => 'ExtendedAttachments',
    'reminder_phrases' => env('EXTENDEDATTACHMENTS_REMINDER_PHRASES', base64_encode("attachment\nattaching\nattached\nattach")),
];
