<?php

return [
    'name' => 'Saml',
    'enabled' => env('SAML_ENABLED', ''),
    'idp_entity_id' => env('SAML_IDP_ENTITY_ID', ''),
    'idp_signin_url' => env('SAML_IDP_SIGNIN_URL', ''),
    'idp_logout_url' => env('SAML_LOGOUT_URL', ''),
    'idp_cert' => env('SAML_IDP_CERT', ''),
    'mapping' => env('SAML_MAPPING', ''),
    'auto_create_users' => env('SAML_AUTO_CREATE_USERS', true),
    'force_saml_login' => env('SAML_FORCE_SAML_LOGIN', false),
    'strict' => env('SAML_STRICT', true),
    'debug' => env('SAML_DEBUG', false),
    'auth_context' => env('SAML_AUTH_CONTEXT', false),
];
