<?php

namespace Modules\MailboxIcons\Providers;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Factory;

define('MI_MODULE', 'mailboxicons');

class MailboxIconsServiceProvider extends ServiceProvider
{
    // in /storage/app/public/.
    const ICONS_FOLDER = 'mailboxes';
    const ICON_SIZE = 50;
    const ICON_QUALITY = 9; // png: 0-9

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
        // Add module's CSS file to the application layout.
        \Eventy::addFilter('stylesheets', function($styles) {
            $styles[] = \Module::getPublicPath(MI_MODULE).'/css/module.css';
            return $styles;
        });

        \Eventy::addAction('dash_card.before_mailbox_name', function($mailbox) {
            echo self::getMailboxIconHtml($mailbox);
        });
        
        \Eventy::addAction('menu.mailbox.before_name', function($mailbox) {
            echo self::getMailboxIconHtml($mailbox);
        });
        
        \Eventy::addAction('mailbox.update.before_mailbox_name', function($mailbox) {
            echo self::getMailboxIconHtml($mailbox);
        });

        \Eventy::addAction('mailbox.update.dropdown.before_mailbox_name', function($mailbox) {
            echo self::getMailboxIconHtml($mailbox);
        });

        \Eventy::addAction('mailbox.view.before_name', function($mailbox) {
            echo self::getMailboxIconHtml($mailbox);
        });

        \Eventy::addFilter('mailbox.has_img', function($result, $mailbox) {
            if ($result) {
                return $result;
            }
            return self::getMailboxIconUrl($mailbox);
        }, 20, 2);

        \Eventy::addAction('mailbox_card.before_name', function($mailbox) {
            echo self::getMailboxIconHtml($mailbox);
        });

        \Eventy::addAction('mailbox.update.before_name', function($mailbox, $errors) {
            echo \View::make('mailboxicons::partials/icon_field', [
                'mailbox' => $mailbox,
                'errors' => $errors,
                'icon_url' => self::getMailboxIconUrl($mailbox),
                'is_custom' => $mailbox->meta['mi']['custom'] ?? false,
            ])->render();
        }, 20, 2);

        \Eventy::addFilter('mailbox.settings_validator', function($validator, $mailbox, $request) {
            if ($request->hasFile('icon')) {
                // Upload icon.
                $uploaded_file = $request->file('icon');

                $resized_image = \Helper::resizeImage(
                    $uploaded_file->getRealPath(),
                    $uploaded_file->getMimeType(),
                    self::ICON_SIZE,
                    self::ICON_SIZE
                );

                if (!$resized_image) {
                    $validator->errors()->add('icon', __('Error occured processing the image - make sure that PHP GD extension is enabled.'));
                    return $validator;
                }

                $icon_key = self::generateMailboxIconKey($mailbox);
                $file_name = $icon_key.'.png';
                $icon_path = Storage::path(self::ICONS_FOLDER.DIRECTORY_SEPARATOR.$file_name);

                $dest_dir = pathinfo($icon_path, PATHINFO_DIRNAME);
                if (!file_exists($dest_dir)) {
                    \File::makeDirectory($dest_dir, 0755);
                    if (!file_exists($dest_dir)) {
                        $validator->errors()->add('icon', __('Could not create folder: %folder%', ['folder' => '/storage/app/public/'.self::ICONS_FOLDER]));
                        return $validator;
                    }
                }

                try {
                    imagepng($resized_image, $icon_path, self::ICON_QUALITY);
                } catch (\Exception $e) {
                    \Helper::logException($e, 'Mailbox Icons Module');
                    $validator->errors()->add('icon', __('Could not save icon - check App Logs.'));
                    return $validator;
                }

                // Delete previous icon.
                if (!empty($mailbox->meta['mi']['icon'])) {
                    $prev_icon_path = Storage::path(self::ICONS_FOLDER.DIRECTORY_SEPARATOR.$mailbox->meta['mi']['icon']);
                    if (file_exists($prev_icon_path)) {
                        Storage::delete($prev_icon_path);
                    }
                }

                $meta = $mailbox->getMeta('mi', []);
                $meta['icon'] = $file_name;
                $meta['custom'] = true;
                $mailbox->setMetaParam('mi', $meta);

            } elseif (empty($request->has_icon)) {
                // Delete custom icon.
                $mailbox->setMeta('mi', []);
                // This will generate a standard icon.
                self::getMailboxIconUrl($mailbox);
            }

            return $validator;
        }, 20, 3);
    }

    public static function getMailboxIconHtml($mailbox)
    {
        $icon_url = self::getMailboxIconUrl($mailbox);
        if ($icon_url) {
            return '<img src="'.$icon_url.'" class="mi-icon" />';
        }
        return '';
    }

    public static function generateMailboxIconKey($mailbox)
    {
        $seed = time();

        // Virtual mailbox.
        if ($mailbox->id < 0) {
            $seed = '';
        }
        return substr(md5($mailbox->id.config('app.key').$seed), 0, 8);
    }

    /**
     * Get or generate the icon for a mailbox.
     */
    public static function getMailboxIconUrl($mailbox)
    {
        $meta = $mailbox->getMeta('mi');

        if ($mailbox->id > 0) {
            $file_name = ($meta['icon'] ?? '');
        } else {
            // Virtual mailbox.
            $file_name = self::generateMailboxIconKey($mailbox).'.png';
        }
        $icon_path = Storage::path(self::ICONS_FOLDER.DIRECTORY_SEPARATOR.$file_name);

        if (!empty($meta['icon']) || ($file_name && file_exists($icon_path))) {
            return Storage::url(self::ICONS_FOLDER.DIRECTORY_SEPARATOR.$file_name);
        } else {
            // Generate the icon.
            $icon_key = self::generateMailboxIconKey($mailbox);
            $file_name = $icon_key.'.png';
            $icon_path = Storage::path(self::ICONS_FOLDER.DIRECTORY_SEPARATOR.$file_name);

            $dest_dir = pathinfo($icon_path, PATHINFO_DIRNAME);
            if (!file_exists($dest_dir)) {
                \File::makeDirectory($dest_dir, 0755);
                if (!file_exists($dest_dir)) {
                    \Log::error('[Mailbox Icons Module] Could create folder: '.$dest_dir);
                }
            }

            $icon_hash = md5('fs_'.$mailbox->id.config('app.key'));
            $gravatar_url = 'https://www.gravatar.com/avatar/'.$icon_hash.'?s='.self::ICON_SIZE.'&d=identicon&r=g';
            \Helper::downloadRemoteFile($gravatar_url, $icon_path);

            if (!file_exists($icon_path)) {
                // Alternative way.
                \Log::error('[Mailbox Icons Module] Could not download Gravatar image: '.$gravatar_url);
                return '';
            } else {
                if ($mailbox->id > 0) {
                    $meta['icon'] = $file_name;
                    if (isset($meta['custom'])) {
                        unset($meta['custom']);
                    }
                    $mailbox->setMetaParam('mi', $meta, true);
                }
                return Storage::url(self::ICONS_FOLDER.DIRECTORY_SEPARATOR.$file_name);
            }
        }
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
            __DIR__.'/../Config/config.php' => config_path('mailboxicons.php'),
        ], 'config');
        $this->mergeConfigFrom(
            __DIR__.'/../Config/config.php', 'mailboxicons'
        );
    }

    /**
     * Register views.
     *
     * @return void
     */
    public function registerViews()
    {
        $viewPath = resource_path('views/modules/mailboxicons');

        $sourcePath = __DIR__.'/../Resources/views';

        $this->publishes([
            $sourcePath => $viewPath
        ],'views');

        $this->loadViewsFrom(array_merge(array_map(function ($path) {
            return $path . '/modules/mailboxicons';
        }, \Config::get('view.paths')), [$sourcePath]), 'mailboxicons');
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
