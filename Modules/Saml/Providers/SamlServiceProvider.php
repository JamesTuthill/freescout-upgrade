<?php

namespace Modules\Saml\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Factory;
use Illuminate\Support\Facades\Auth;
use OneLogin\Saml2\Auth as OneLogin_Saml2_Auth;

require_once __DIR__ . '/../vendor/autoload.php';

// https://samltest.id/
// https://github.com/onelogin/php-saml
class SamlServiceProvider extends ServiceProvider
{
    public static $mappable_fields = [
        'first_name',
        'last_name',
        'job_title',
        'phone',
    ];

    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Boot the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerConfig();
        $this->registerViews();
        $this->registerFactories();
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        $this->hooks();
    }

    /**
     * Module hooks.
     */
    public function hooks()
    {
        \Eventy::addFilter('settings.sections', function ($sections) {
            $sections['saml'] = ['title' => __('SAML 2.0'), 'icon' => 'user', 'order' => 250];

            return $sections;
        }, 15);

        // Section settings
        \Eventy::addFilter('settings.section_settings', function ($settings, $section) {

            if ($section != 'saml') {
                return $settings;
            }

            $settings = self::getSettings();

            return $settings;
        }, 20, 2);

        // Section parameters.
        \Eventy::addFilter('settings.section_params', function ($params, $section) {
            if ($section != 'saml') {
                return $params;
            }

            $params = [
                'template_vars' => [
                    
                ],
                'settings' => [
                    'saml.enabled' => [
                        'env' => 'SAML_ENABLED',
                    ],
                    'saml.idp_entity_id' => [
                        'env' => 'SAML_IDP_ENTITY_ID',
                    ],
                    'saml.idp_signin_url' => [
                        'env' => 'SAML_IDP_SIGNIN_URL',
                    ],
                    'saml.idp_logout_url' => [
                        'env' => 'SAML_LOGOUT_URL',
                    ],
                    'saml.idp_cert' => [
                        'env' => 'SAML_IDP_CERT',
                        'env_encode' => true,
                    ],
                    'saml.mapping' => [
                        'env' => 'SAML_MAPPING',
                        'env_encode' => true,
                    ],
                    'saml.auth_context' => [
                        'env' => 'SAML_AUTH_CONTEXT',
                    ],
                    'saml.auto_create_users' => [
                        'env' => 'SAML_AUTO_CREATE_USERS',
                    ],
                    'saml.force_saml_login' => [
                        'env' => 'SAML_FORCE_SAML_LOGIN',
                    ],
                    'saml.debug' => [
                        'env' => 'SAML_DEBUG',
                    ],
                    'saml.strict' => [
                        'env' => 'SAML_STRICT',
                    ],
                ]
            ];

            return $params;
        }, 20, 2);

        // Show SSO login button.
        \Eventy::addAction('login_form.after',  function () {
            if (config('saml.enabled')) {
                echo \View::make('saml::login_button', [])->render();
            }
        });

        // Settings view name
        \Eventy::addFilter('settings.view', function ($view, $section) {
            if ($section != 'saml') {
                return $view;
            } else {
                return 'saml::settings';
            }
        }, 20, 2);

        \Eventy::addFilter('middleware.web.custom_handle.response', function ($prev, $request, $next) {
            
            $settings = self::getSettings();

            $route_name = $request->route()->getName();

            if ($settings['saml.enabled']
                && $route_name == 'login'
                && !Auth::check()
                && (($settings['saml.force_saml_login'] && (int)$request->get('saml', -1) != 0) || (int)$request->get('saml'))
            ) {
                $auth = new OneLogin_Saml2_Auth(self::getSamlConfig());
                $sso_redirect_url = $auth->login(null, array(), false, false, true);

                \Saml::debug("Redirecting to IdP Signin URL");
                
                session()->put('saml.request_id', $auth->getLastRequestID());

                return redirect($sso_redirect_url);
            }

            if ($settings['saml.enabled'] && $route_name == 'logout') {
                if (!empty($settings['saml.idp_logout_url'])) {
                    // Auth0 shows an error: server_error: No active session(s) found matching LogoutRequest 
                    // Basic logout URL: https://manage.auth0.com/logout
                    // $auth = new OneLogin_Saml2_Auth(self::getSamlConfig());
                    // $auth->logout();
                    return redirect($settings['saml.idp_logout_url']);
                }
            }

            return $prev;
        }, 10, 3);
    }

    public static function getMappableFields()
    {
        return self::$mappable_fields;
    }

    public static function sanitizeIdpAttrName($attr_name)
    {
        return preg_replace("#.*/([^/]+)$#", '$1', $attr_name);
    }

    public static function getSettings()
    {
        $settings = [];

        $settings['saml.enabled'] = config('saml.enabled');
        $settings['saml.idp_entity_id'] = config('saml.idp_entity_id');
        $settings['saml.idp_signin_url'] = config('saml.idp_signin_url');
        $settings['saml.idp_logout_url'] = config('saml.idp_logout_url');
        $settings['saml.idp_cert'] = base64_decode(config('saml.idp_cert'));
        $settings['saml.mapping'] = base64_decode(config('saml.mapping'));
        $settings['saml.auth_context'] = config('saml.auth_context');
        $settings['saml.auto_create_users'] = config('saml.auto_create_users');
        $settings['saml.force_saml_login'] = config('saml.force_saml_login');
        $settings['saml.debug'] = config('saml.debug');
        $settings['saml.strict'] = config('saml.strict');

        if ($settings['saml.idp_cert']) {
            $settings['saml.idp_cert'] = preg_replace("/[\t\r\n]/", '', $settings['saml.idp_cert']);
        }

        return $settings;
    }

    public static function getSamlConfig()
    {
        $settings = self::getSettings();

        $config = array(
            // If 'strict' is True, then the PHP Toolkit will reject unsigned
            // or unencrypted messages if it expects them to be signed or encrypted.
            // Also it will reject the messages if the SAML standard is not strictly
            // followed: Destination, NameId, Conditions ... are validated too.
            'strict' => $settings['saml.strict'],

            // Enable debug mode (to print errors).
            'debug' => false,

            // Set a BaseURL to be used instead of try to guess
            // the BaseURL of the view that process the SAML Message.
            // Ex http://sp.example.com/
            //    http://example.com/sp/
            'baseurl' => null,

            // Service Provider Data that we are deploying.
            'sp' => array(
                // Identifier of the SP entity  (must be a URI)
                'entityId' => route('saml.sp_metadata', ['secret' => \Saml::getSecret()]),
                // Specifies info about where and how the <AuthnResponse> message MUST be
                // returned to the requester, in this case our SP.
                'assertionConsumerService' => array(
                    // URL Location where the <Response> from the IdP will be returned
                    'url' => route('saml.acs', ['secret' => \Saml::getSecret()]),
                    // SAML protocol binding to be used when returning the <Response>
                    // message. OneLogin Toolkit supports this endpoint for the
                    // HTTP-POST binding only.
                    'binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
                ),
                // If you need to specify requested attributes, set a
                // attributeConsumingService. nameFormat, attributeValue and
                // friendlyName can be omitted
                "attributeConsumingService" => array(
                    "serviceName" => "Freescout",
                    "serviceDescription" => "Helpdesk",
                    "requestedAttributes" => array(
                        array(
                            "name" => "",
                            "isRequired" => false,
                            "nameFormat" => "",
                            "friendlyName" => "",
                            "attributeValue" => array()
                        )
                    )
                ),
                // Specifies info about where and how the <Logout Response> message MUST be
                // returned to the requester, in this case our SP.
                'singleLogoutService' => array(
                    // URL Location where the <Response> from the IdP will be returned
                    'url' => route('saml.single_logout', ['secret' => \Saml::getSecret()]),
                    // SAML protocol binding to be used when returning the <Response>
                    // message. OneLogin Toolkit supports the HTTP-Redirect binding
                    // only for this endpoint.
                    'binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
                ),
                // Specifies the constraints on the name identifier to be used to
                // represent the requested subject.
                // Take a look on lib/Saml2/Constants.php to see the NameIdFormat supported.
                'NameIDFormat' => 'urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress',
                // Usually x509cert and privateKey of the SP are provided by files placed at
                // the certs folder. But we can also provide them with the following parameters
                'x509cert' => '',
                'privateKey' => '',
            ),

            // Identity Provider Data that we want connected with our SP.
            'idp' => array(
                // Identifier of the IdP entity  (must be a URI)
                'entityId' => $settings['saml.idp_entity_id'],
                // SSO endpoint info of the IdP. (Authentication Request protocol)
                'singleSignOnService' => array(
                    // URL Target of the IdP where the Authentication Request Message
                    // will be sent.
                    'url' => $settings['saml.idp_signin_url'],
                    // SAML protocol binding to be used when returning the <Response>
                    // message. OneLogin Toolkit supports the HTTP-Redirect binding
                    // only for this endpoint.
                    'binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
                ),
                // SLO endpoint info of the IdP.
                'singleLogoutService' => array(
                    // URL Location of the IdP where SLO Request will be sent.
                    'url' => $settings['saml.idp_logout_url'],
                    // URL location of the IdP where the SP will send the SLO Response (ResponseLocation)
                    // if not set, url for the SLO Request will be used
                    'responseUrl' => '',
                    // SAML protocol binding to be used when returning the <Response>
                    // message. OneLogin Toolkit supports the HTTP-Redirect binding
                    // only for this endpoint.
                    'binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
                ),
                // Public x509 certificate of the IdP
                'x509cert' => $settings['saml.idp_cert'],
            ),
        );

        if ($settings['saml.auth_context']) {
            $auth_context = explode(',', $settings['saml.auth_context']);
            foreach ($auth_context as $i => $value) {
                $auth_context[$i] = trim($value);
            }
            if (count($auth_context)) {
                $config['security']['requestedAuthnContext'] = $auth_context;
            }
            // 'security' => array(
            //     // Authentication context.
            //     // Set to false and no AuthContext will be sent in the AuthNRequest,
            //     // Set true or don't present this parameter and you will get an AuthContext 'exact' 'urn:oasis:names:tc:SAML:2.0:ac:classes:PasswordProtectedTransport'
            //     // Set an array with the possible auth context values: array('urn:oasis:names:tc:SAML:2.0:ac:classes:Password', 'urn:oasis:names:tc:SAML:2.0:ac:classes:X509'),
            //     'requestedAuthnContext' => array( 'urn:oasis:names:tc:SAML:2.0:ac:classes:X509' ),
            // ),
        }

        return $config;
    }

    public static function getSecret()
    {
        return crc32(config('app.key').'saml');
    }

    public static function debug($text, $params = [])
    {
        if (!config('saml.debug')) {
            return;
        }
        \Saml::log('DEBUG - '. $text, $params);
    }

    public static function log($text, $params = [])
    {
        if ($params) {
            $text .= ' '.json_encode($params);
        }
        \Helper::log('saml', stripslashes(str_replace("\n", '\n', $text)));
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerTranslations();
    }

    /**
     * Register config.
     *
     * @return void
     */
    protected function registerConfig()
    {
        $this->publishes([
            __DIR__ . '/../Config/config.php' => config_path('saml.php'),
        ], 'config');
        $this->mergeConfigFrom(
            __DIR__ . '/../Config/config.php', 'saml'
        );
    }

    /**
     * Register views.
     *
     * @return void
     */
    public function registerViews()
    {
        $viewPath = resource_path('views/modules/saml');

        $sourcePath = __DIR__ . '/../Resources/views';

        $this->publishes([
            $sourcePath => $viewPath
        ], 'views');

        $this->loadViewsFrom(array_merge(array_map(function ($path) {
            return $path . '/modules/saml';
        }, \Config::get('view.paths')), [$sourcePath]), 'saml');
    }

    /**
     * Register translations.
     *
     * @return void
     */
    public function registerTranslations()
    {
        $this->loadJsonTranslationsFrom(__DIR__ . '/../Resources/lang');
    }

    /**
     * Register an additional directory of factories.
     * @source https://github.com/sebastiaanluca/laravel-resource-flow/blob/develop/src/Modules/ModuleServiceProvider.php#L66
     */
    public function registerFactories()
    {
        if (!app()->environment('production')) {
            app(Factory::class)->load(__DIR__ . '/../Database/factories');
        }
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [];
    }
}
