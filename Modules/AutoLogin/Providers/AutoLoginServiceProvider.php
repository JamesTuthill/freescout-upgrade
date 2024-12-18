<?php

namespace Modules\AutoLogin\Providers;

use App\User;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Factory;

class AutoLoginServiceProvider extends ServiceProvider
{
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
        \Eventy::addFilter('email_notification.conv_url', function($url, $user) {
           return self::modifyUrl($url, $user);
        }, 20, 2);

        \Eventy::addFilter('email_notification.settings_url', function($url, $user) {
           return self::modifyUrl($url, $user);
        }, 20, 2);

        \Eventy::addFilter('email_notification.mailbox_url', function($url, $user) {
           return self::modifyUrl($url, $user);
        }, 20, 2);

        // 2FA after submitting send plain password to login form.
        // Here we allow to send as a password encrypted hashed password.
        \Eventy::addFilter('session_guard.validate_credentials', function($result, $user, $credentials) {
            if ($result) {
                return $result;
            }
            $decrypted_post_password = \Helper::decrypt($credentials['password']);
            if ($decrypted_post_password 
                && $decrypted_post_password != $credentials['password']
                && $decrypted_post_password == $user->getAuthPassword()
            ) {
                return true;
            } else {
                return false;
            }
        }, 20, 3);

        \Eventy::addaction('auth_middleware.handle', function($request, $guards, $next) {
            $user = \Auth::user();

            if ($user) {
                return;
            }

            if (empty($request->al)) {
                return;
            }

            $al_data = \Helper::decrypt($request->al);
            if ($al_data) {
                $al_data = json_decode($al_data, true);
            }

            if (empty($al_data) 
                || !is_array($al_data)
                || empty($al_data['id'])
                || empty($al_data['password'])
            ) {
                return;
            }

            $user = User::find($al_data['id']);

            if (!$user || $user->isDeleted() || $user->password != $al_data['password']) {
                return;
            }

            if (method_exists($user,'hasTwoFactorEnabled') && $user->hasTwoFactorEnabled()) {
                $view = view('twofactorauth::auth', [
                    'action'      => request()->fullUrl(),
                    'credentials' => [
                        'email' => $user->email,
                        // normally it is a plain password here.
                        'password' => encrypt($user->getAuthPassword()),
                    ],
                    'user'        => $user,
                    'error'       => false,
                    'remember'    => false,
                    'input'       => config('laraguard.input')
                ]);

                return response($view, 403)->throwResponse();
            } else {
                \Auth::loginUsingId($user->id, true);
                //return redirect(redirect()->intended()->getTargetUrl() ?: '/home')->throwResponse();
            }
        }, 20, 3);
    }

    public static function modifyUrl($url, $user)
    {
        if (strstr($url, '?')) {
            $url .= '&';
        } else {
            $url .= '?';
        }
        $url .= 'al='.urlencode(self::getAlHash($user));

        return $url;
    }

    public static function getAlHash($user)
    {
        return encrypt(json_encode([
            'id' => $user->id,
            'password' => $user->password,
        ]));
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
            __DIR__.'/../Config/config.php' => config_path('autologin.php'),
        ], 'config');
        $this->mergeConfigFrom(
            __DIR__.'/../Config/config.php', 'autologin'
        );
    }

    /**
     * Register views.
     *
     * @return void
     */
    public function registerViews()
    {
        $viewPath = resource_path('views/modules/autologin');

        $sourcePath = __DIR__.'/../Resources/views';

        $this->publishes([
            $sourcePath => $viewPath
        ],'views');

        $this->loadViewsFrom(array_merge(array_map(function ($path) {
            return $path . '/modules/autologin';
        }, \Config::get('view.paths')), [$sourcePath]), 'autologin');
    }

    /**
     * Register translations.
     *
     * @return void
     */
    public function registerTranslations()
    {
        $this->loadJsonTranslationsFrom(__DIR__ .'/../Resources/lang');
    }

    /**
     * Register an additional directory of factories.
     * @source https://github.com/sebastiaanluca/laravel-resource-flow/blob/develop/src/Modules/ModuleServiceProvider.php#L66
     */
    public function registerFactories()
    {
        if (! app()->environment('production')) {
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
