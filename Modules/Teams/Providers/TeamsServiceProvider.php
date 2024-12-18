<?php

namespace Modules\Teams\Providers;

use App\Conversation;
use App\Folder;
use App\Subscription;
use App\User;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Factory;

// Module alias
define('TEAMS_MODULE', 'teams');

class TeamsServiceProvider extends ServiceProvider
{
    const TEAM_USER_EMAIL_PREFIX = 'fsteam';
    const TEAM_USER_LAST_NAME    = 'Team';

    const FOLDER_TYPE = 185; // max 255

    const DEFAULT_ICON = 'th-large';

    public static $member_ids_cache = [];

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
            $styles[] = \Module::getPublicPath(TEAMS_MODULE).'/css/module.css';
            return $styles;
        });

        // // Add module's JS file to the application layout.
        \Eventy::addFilter('javascripts', function($javascripts) {
            $javascripts[] = \Module::getPublicPath(TEAMS_MODULE).'/js/laroute.js';
            $javascripts[] = \Module::getPublicPath(TEAMS_MODULE).'/js/module.js';
            return $javascripts;
        });
        
        // Add item to the menu
        \Eventy::addAction('menu.manage.after_mailboxes', function() {
            if (self::canManageTeams()) {
                ?>
                    <li class="<?php echo \Helper::menuSelectedHtml('teams') ?>"><a href="<?php echo route('teams.teams') ?>"><?php echo __('Teams') ?></a></li>
                <?php
            }
        }, 100);

        // Select main menu item.
        \Eventy::addFilter('menu.selected', function($menu) {
            $menu['manage']['teams'] = [
                'teams.teams'
            ];

            return $menu;
        });

        // Add Teams to the Assignee list.
        \Eventy::addFilter('mailbox.users_assignable', function($users, $mailbox, $cache) {
            return self::addTeamsToMailboxUsers($users, $mailbox, $cache);
        }, 20, 3);

        // For mentions.
        \Eventy::addFilter('mailbox.users_having_access', function($users, $mailbox, $cache) {
            return self::addTeamsToMailboxUsers($users, $mailbox, $cache);
        }, 20, 3);

        // Adjust Team user full name.
        \Eventy::addFilter('user.full_name', function($full_name, $user) {
            if (self::isTeam($user)) {
                return $user->first_name.' ('.__('Team').')';
            }

            return $full_name;
        }, 20, 2);

        // Team user has access to a mailbox.
        \Eventy::addFilter('mailbox.user_has_access', function($has_access, $mailbox, $user) {
            if (self::isTeam($user)) {
                return $user->mailboxes->contains($mailbox);
            }

            return $has_access;
        }, 20, 3);

        \Eventy::addFilter('subscription.subscriptions', function($subscriptions, $conversation, $events, $thread) {

            // If conversation is assigned to a Team, return Team's members as subscribers.
            if ($conversation->user_id && self::isTeam($conversation->user)) {
                $check_events = [
                    Subscription::EVENT_CONVERSATION_ASSIGNED_TO_ME,
                    Subscription::EVENT_CUSTOMER_REPLIED_TO_MY,
                    Subscription::EVENT_USER_REPLIED_TO_MY
                ];
                foreach ($check_events as $i => $event_id) {
                    if (!in_array($event_id, $events)) {
                        unset($check_events[$i]);
                    }
                }
                if (!count($check_events)) {
                    return $subscriptions;
                }
                $member_ids = self::getMemberIds($conversation->user);
                $member_subscriptions = Subscription::whereIn('user_id', $member_ids)
                    ->whereIn('event', $check_events)
                    ->get();

                if (count($member_subscriptions)) {
                    $subscriptions = $subscriptions->merge($member_subscriptions);
                }
            }

            // If team is mentioned add team members to subscribers.
            if (\Module::isActive('mentions')) {
                $mentioned_user_ids = \Mentions::getMentionedUsers($thread->body);
                $mentioned_users = User::whereIn('id', $mentioned_user_ids)->get();
                foreach ($mentioned_users as $mentioned_user) {
                    if (\Team::isTeam($mentioned_user)) {
                        $member_ids = \Team::getMemberIds($mentioned_user);
                        $member_subscriptions = Subscription::whereIn('user_id', $member_ids)
                            ->whereIn('event', [\Mentions::EVENT_I_AM_MENTIONED])
                            ->get();

                        if (count($member_subscriptions)) {
                            $subscriptions = $subscriptions->merge($member_subscriptions);
                        }
                    }
                }
            }

            return $subscriptions;
        }, 20, 4);

        // Otherwise Mentions module removes mentioned Team members.
        \Eventy::addFilter('subscription.filter_out', function($filter_out, $subscription, $thread) {
            if (!\Module::isActive('mentions')) {
                return $filter_out;
            }
            if ($subscription->event != \Mentions::EVENT_I_AM_MENTIONED) {
                return $filter_out;
            }
            if (!$filter_out) {
                return $filter_out;
            }
            $mentioned_team_member_ids = self::getMembersByUserIds(\Mentions::getMentionedUsers($thread->body));
            if (in_array($subscription->user_id, $mentioned_team_member_ids)) {
                return false;
            } else {
                return $filter_out;
            }
        }, 40, 3);

        // If a Team is an Assignee it's members are also assignees
        \Eventy::addFilter('subscription.is_user_assignee', function($is_assignee, $subscription, $conversation) {
            if ($conversation->user_id && self::isTeam($conversation->user)) {
                if (in_array($subscription->user_id, self::getMemberIds($conversation->user))) {
                    return true;
                }
            }

            return $is_assignee;
        }, 20, 3);

        \Eventy::addFilter('conversation.is_user_assignee', function($is_assignee, $conversation, $user_id) {
            if ($is_assignee) {
                return $is_assignee;
            }
            
            if ($conversation->user_id && self::isTeam($conversation->user)) {
                if (in_array($user_id, self::getMemberIds($conversation->user))) {
                    return true;
                }
            }

            return $is_assignee;
        }, 20, 3);

        // \Eventy::addFilter('mailbox.folders', function($folders) {
        //     $folders = $folders->sortBy(function($folder) {
        //         if ($folder->type != self::FOLDER_TYPE) {
        //             return $folder->type;
        //         }
        //         if (!empty($folder->meta['order'])) {
        //             return (int)$folder->meta['order']+1000;
        //         } else {
        //             return 0;
        //         }
        //     });
        // }, 20, 3);

        \Eventy::addFilter('mailbox.folders.public_types', function($list) {
            if (!(int)config('teams.hide_folders')) {
                $list[] = self::FOLDER_TYPE;
            }

            return $list;
        }, 20, 1);

         \Eventy::addFilter('folder.type_icon', function($icon, $folder) {
             if ($folder->type == self::FOLDER_TYPE) {
                 return $folder->meta['icon'] ?? self::DEFAULT_ICON;
             }

             return $icon;
         }, 20, 2);

        \Eventy::addFilter('folder.type_name', function($name, $folder) {
            if ($folder->type == self::FOLDER_TYPE) {
                if (!empty($folder->user_id)) {
                    $team = User::find($folder->user_id);
                    if ($team) {
                        return $team->first_name;
                    }
                }
            }

            return $name;
        }, 20, 2);

        \Eventy::addFilter('folder.conversations_query', function($query, $folder, $user_id) {
            if ($folder->type != self::FOLDER_TYPE) {
                return $query;
            }

            $query = Conversation::select('conversations.*')
                ->where('mailbox_id', $folder->mailbox_id)
                ->where('state', Conversation::STATE_PUBLISHED)
                ->whereIn('status', [Conversation::STATUS_ACTIVE, Conversation::STATUS_PENDING])
                ->where('user_id', $folder->user_id ?? 0);

            return $query;
        }, 20, 3);

        \Eventy::addFilter('folder.conversations_order_by', function($order_by, $folder_type) {
            if ($folder_type != self::FOLDER_TYPE) {
                return $order_by;
            }
            $order_by[] = ['status' => 'asc'];
            $order_by[] = ['last_reply_at' => 'desc'];

            return $order_by;
        }, 20, 2);

        \Eventy::addFilter('conversation.is_in_folder_allowed', function($is_allowed, $folder) {
            if ($folder->type != self::FOLDER_TYPE) {
                return $is_allowed;
            }
            // todo.
            return true;
        }, 20, 2);

        \Eventy::addFilter('conversation.get_nearby_query', function($query, $conversation, $mode, $folder) {
            if ($folder->type != self::FOLDER_TYPE) {
                return $query;
            }

            $query = Conversation::select('conversations.*')
                ->where('conversations.mailbox_id', $conversation->mailbox_id)
                ->where('conversations.id', '<>', $conversation->id)
                ->where('user_id', $folder->user_id ?? 0);

            return $query;
        }, 20, 4);

        \Eventy::addFilter('folder.update_counters', function($updated, $folder) {
            if ($folder->type != self::FOLDER_TYPE) {
                return $updated;
            }

            return self::setFolderCounters($folder);
        }, 20, 2);

        \Eventy::addAction('mailbox.permissions.before_access_settings', function($mailbox, $mailbox_users, $managers) {
            
            $teams = self::getTeams();
            $first = true;

            foreach ($teams as $team) {
                if ($team->mailboxes->pluck('id')->contains($mailbox->id)) {
                    $members = \Team::getMembers($team);
                    if (!count($members)) {
                        continue;
                    }
                    ?>
                        <?php if ($first): ?>
                            <div class="col-xs-12 margin-top">
                                <h3><?php echo __('Teams') ?></h3>
                            </div>
                        <?php endif ?>
                        <div class="col-xs-12">
                            <strong><i class="text-help glyphicon glyphicon-<?php echo \Team::getIcon($team) ?>"></i> <?php echo htmlspecialchars($team->first_name) ?></strong>
                            <div class="margin-bottom-5">
                            <?php foreach ($members as $i => $member): ?>
                                <?php
                                    $member_class = 'text-warning';
                                    foreach ($mailbox_users as $mailbox_user) {
                                        if ($mailbox_user->id == $member->id) {
                                            $member_class = '';
                                            break;
                                        }
                                    }
                                ?>
                                <span class="<?php echo $member_class ?>"><?php echo htmlspecialchars($member->first_name); ?></span><?php if ($i != count($members)-1): ?>, <?php endif ?>
                            <?php endforeach ?>
                            </div>
                        </div>
                    <?php
                    $first = false;
                }
            }
            
        }, 20, 3);

        \Eventy::addAction('user.deleted', function($deleted_user, $by_user) {
            if (\Team::isTeam($deleted_user)) {
                return;
            }
            $teams = \Team::getTeams();
            foreach ($teams as $team) {
                $member_ids = \Team::getMemberIds($team);
                if (in_array($deleted_user->id, $member_ids)) {
                    foreach ($member_ids as $i => $member_id) {
                        if ($member_id == $deleted_user->id) {
                            unset($member_ids[$i]);
                            break;
                        }
                    }
                    \Team::setMembers($team, $member_ids);
                    $team->save();
                }
            }
        }, 20, 2);

        \Eventy::addFilter('search.assignees', function($assignees, $user, $mailboxes) {
            if (!count($mailboxes)) {
                return $assignees;
            }
            $teams = self::getTeams(true);
            if (!count($teams)) {
                return $assignees;
            }
            $teams_with_access = User::select(['users.*'])
                ->whereIn('users.id', $teams->pluck('id'))
                ->join('mailbox_user', function ($join) use ($mailboxes) {
                    $join->on('mailbox_user.user_id', '=', 'users.id');
                    $join->whereIn('mailbox_user.mailbox_id', $mailboxes->pluck('id'));
                })
                ->get();

            $teams_with_access = $teams_with_access->sortBy('first_name');

            return $assignees->merge($teams_with_access);
        }, 20, 3);

        \Eventy::addFilter('workflow.is_user_valid', function($is_valid, $user, $workflow) {
            if (!self::isTeam($user)) {
                return $is_valid;
            } else {
                return true;
            }
        }, 20, 3);

        \Eventy::addFilter('user.non_deleted_condition', function($condition, $extended) {
            if (!$extended) {
                return $condition;
            }
            return User::where(function ($query) {
                $query->where('status', '!=', User::STATUS_DELETED)
                    ->orWhere('users.email', 'like', self::TEAM_USER_EMAIL_PREFIX.'%@example.org');
            });
        }, 20, 2);

        // Unpack Teams into users.
        \Eventy::addFilter('users.unpack', function($users) {

            $teams_users = [];

            foreach ($users as $i => $user) {
                if (self::isTeam($user)) {
                    $teams_users = array_merge($teams_users, self::getMembers($user)->all());
                    unset($users[$i]);
                }
            }

            if (count($teams_users)) {
                $users = array_merge($users, $teams_users);
                // Remove duplicates.
                $user_ids = [];
                foreach ($users as $i => $user) {
                    if (in_array($user->id, $user_ids)) {
                        unset($users[$i]);
                    } else {
                        $user_ids[] = $user->id;
                    }
                }
            }

            return $users;
        });

        // Add Teams when saving mailbox Permissions.
        \Eventy::addFilter('mailbox.permission_users', function($user_ids, $mailbox_id) {

            if (!is_array($user_ids)) {
                return $user_ids;
            }

            $team_ids = self::getMailboxTeams($mailbox_id)->pluck('id')->toArray();

            return array_merge($user_ids, $team_ids);
        }, 20, 2);

        // When "Show only assigned conversations" is enabled for a user,
        // take user's teams into account.
        \Eventy::addFilter('folder.only_assigned_condition', function($condition_applied, $query_conversations, $user_id) {

            $user_team_ids = self::getUserTeamIds($user_id);
            if (count($user_team_ids)) {
                $user_team_ids[] = $user_id;
                $query_conversations->whereIn('user_id', $user_team_ids);
                return true;
            }

            return false;
        }, 20, 3);
    }

    public static function getMembersByUserIds($user_ids)
    {
        $member_ids = [];

        $cache_key = md5(json_encode($user_ids));

        if (isset(self::$member_ids_cache[$cache_key])) {
            return self::$member_ids_cache[$cache_key];
        }

        $users = User::whereIn('id', $user_ids)->get();
        foreach ($users as $user) {
            if (\Team::isTeam($user)) {
                $member_ids = array_merge($member_ids, \Team::getMemberIds($user));
            }
        }

        self::$member_ids_cache[$cache_key] = $member_ids;

        return $member_ids;
    }

    public static function addTeamsToMailboxUsers($users, $mailbox, $cache)
    {
        $teams = self::getMailboxTeams($mailbox->id, $cache);

        $teams = $teams->sortBy('first_name');

        $users = $users->merge($teams);

        return $users;
    }

    public static function getMailboxTeams($mailbox_id, $cache = false)
    {
        $teams = self::getTeams(true);

        $teams_with_access = User::whereIn('users.id', $teams->pluck('id'))
            ->join('mailbox_user', function ($join) use ($mailbox_id) {
                $join->on('mailbox_user.user_id', '=', 'users.id');
                $join->where('mailbox_user.mailbox_id', $mailbox_id);
            })
            ->remember(\Helper::cacheTime($cache))
            ->pluck('users.id')
            ->toArray();

        foreach ($teams as $i => $team) {
            if (!in_array($team->id, $teams_with_access)) {
                unset($teams[$i]);
            }
        }

        return $teams;
    }

    public static function getUserTeamIds($user_id)
    {
        $team_ids = [];
        $teams = self::getTeams(true);

        foreach ($teams as $team) {
            if (in_array($user_id, self::getMemberIds($team))) {
                $team_ids[] = $team->id;
            }
        }

        return $team_ids;
    }

    public static function isTeam($user)
    {
        if (!$user || !$user->isDeleted() || !preg_match("/^".self::TEAM_USER_EMAIL_PREFIX."/", $user->email)) {
            return false;
        } else {
            return true;
        }
    }

    public static function isDeleted($team)
    {
        return strstr($team->email, User::EMAIL_DELETED_SUFFIX);
    }

    public static function generateEmail()
    {
        return self::TEAM_USER_EMAIL_PREFIX.'-'.crc32(str_random(32)).'@example.org';
    }

    /**
     * Query teams.
     */
    public static function getTeams($cache = false)
    {
        $teams = User::where('status', User::STATUS_DELETED)
            ->where('email', 'like', self::TEAM_USER_EMAIL_PREFIX.'%')
            ->where('last_name', self::TEAM_USER_LAST_NAME)
            ->remember(\Helper::cacheTime($cache))
            ->get();

        // Remove deleted teams.
        foreach ($teams as $i => $team) {
            if (self::isDeleted($team)) {
                unset($teams[$i]);
            }
        }

        return $teams->sortBy('first_name');
    }

    public static function getMembersCount($team)
    {
        return count(self::getMemberIds($team));
    }

    public static function setMembers($team, $member_ids)
    {
        $team->emails = json_encode($member_ids);

        return $team;
    }

    public static function getMemberIds($team)
    {
        if ($team->emails) {
            return \Helper::jsonToArray($team->emails) ?? [];
        } else {
            return [];
        }
    }

    public static function getMembers($team)
    {
        $ids = self::getMemberIds($team);
        if ($ids) {
            return User::nonDeleted()->whereIn('id', $ids)->get();
        } else {
            return collect([]);
        }
    }

    public static function getIcon($team)
    {
        return $team->photo_url ?: self::DEFAULT_ICON;
    }

    public static function setFolderCounters($folder)
    {
        $query = Conversation::where('conversations.mailbox_id', $folder->mailbox_id)
            ->where('conversations.state', Conversation::STATE_PUBLISHED)
            ->where('user_id', $folder->user_id ?? 0);

        $active_query = clone $query;
        $folder->active_count = $active_query
            ->where('conversations.status', Conversation::STATUS_ACTIVE)
            ->count();

        $total_query = clone $query;

        $folder->total_count = $total_query->count();

        $folder->save();

        return true;
    }

    public static function deleteFolders($team)
    {
        Folder::where('type', self::FOLDER_TYPE)
            ->where('user_id', $team->id)
            ->delete();
    }

    public static function canManageTeams($user = null)
    {
        if (!$user) {
            $user = auth()->user();
        }

        return $user->isAdmin();
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
            __DIR__.'/../Config/config.php' => config_path('teams.php'),
        ], 'config');
        $this->mergeConfigFrom(
            __DIR__.'/../Config/config.php', 'teams'
        );
    }

    /**
     * Register views.
     *
     * @return void
     */
    public function registerViews()
    {
        $viewPath = resource_path('views/modules/teams');

        $sourcePath = __DIR__.'/../Resources/views';

        $this->publishes([
            $sourcePath => $viewPath
        ],'views');

        $this->loadViewsFrom(array_merge(array_map(function ($path) {
            return $path . '/modules/teams';
        }, \Config::get('view.paths')), [$sourcePath]), 'teams');
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
