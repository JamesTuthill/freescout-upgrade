<?php

namespace Modules\FasterSearch\Providers;

use App\Attachment;
use App\Conversation;
use App\Follower;
use App\Thread;
use Spatie\Activitylog\Models\Activity;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Factory;

// Module alias
define('FS_MODULE_SECTION', 'meilisearch');

class FasterSearchServiceProvider extends ServiceProvider
{
    const INDEX_NAME = 'freescout';

    const INDEXING_TIME_LIMIT = 50; // seconds
    const INDEXING_BUNCH = 200;

    // https://docs.meilisearch.com/reference/api/settings.html#update-pagination-settings
    const DEFAULT_MAX_TOTAL_HITS = 1000;

    const API_METHOD_GET = 'GET';
    const API_METHOD_POST = 'POST';
    const API_METHOD_DELETE = 'DELETE';
    const API_METHOD_PUT = 'PUT';
    const API_METHOD_PATCH = 'PATCH';

    const LOG_NAME = 'meilisearch_errors';

    public static $indexable_thread_types = [
        Thread::TYPE_MESSAGE,
        Thread::TYPE_CUSTOMER,
        Thread::TYPE_NOTE,
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
        $this->registerCommands();
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        $this->hooks();
    }

    /**
     * Module hooks.
     */
    public function hooks()
    {
        // Add item to settings sections.
        \Eventy::addFilter('settings.sections', function($sections) {
            $sections[FS_MODULE_SECTION] = ['title' => 'Meilisearch', 'icon' => 'filter', 'order' => 500];

            return $sections;
        }, 40);

        // Section settings
        \Eventy::addFilter('settings.section_settings', function($settings, $section) {
           
            if ($section != FS_MODULE_SECTION) {
                return $settings;
            }
           
            $settings['fastersearch.url'] = config('fastersearch.url');
            $settings['fastersearch.api_key'] = config('fastersearch.api_key');
            $settings['fastersearch.max_total_hits'] = (int)\Option::get('fastersearch.max_total_hits', \FasterSearch::DEFAULT_MAX_TOTAL_HITS);

            return $settings;
        }, 20, 2);

        // Section parameters.
        \Eventy::addFilter('settings.section_params', function($params, $section) {
           
            if ($section != FS_MODULE_SECTION) {
                return $params;
            }

            $auth_error = '';
            $index_error = '';
            $health = '';
            $last_log_message = '';
            $indexed_threads = 0;
            $total_threads = 0;

            if (config('fastersearch.url')) {
                $health_response = self::apiCall('health', [], self::API_METHOD_GET);

                $health = json_encode($health_response);
            }

            // Get rooms and test API credentials.
            if (config('fastersearch.url') && config('fastersearch.api_key')) {
                // Check credentials.
                $test_response = self::apiCall('indexes', [], self::API_METHOD_GET);

                if (isset($test_response['results']) && is_array($test_response['results'])) {
                    
                    \Option::set('fastersearch.active', true);

                    // Check index.
                    if (!in_array(self::INDEX_NAME, array_column($test_response['results'], 'uid'))) {
                        // Try to create an index in Meilisearch.
                        $api_response = \FasterSearch::createIndex();

                        if (!empty($api_response['message']) ) {
                            $index_error = $api_response['message'];
                            if (!empty($api_response['link'])) {
                                $index_error .= ' ('.$api_response['link'].')';
                            }
                        }
                    } else {
                        \Option::set('fastersearch.index_created', true);
                    }

                    // Count documents.
                    if (\Option::get('fastersearch.index_created')) {
                        // This returns maxTotalHits.
                        // https://docs.meilisearch.com/reference/api/settings.html#update-pagination-settings
                        // $count_response = \FasterSearch::apiCall('indexes/'.self::INDEX_NAME.'/search', [
                        //     'limit' => 1,
                        // ]);

                        // if (isset($count_response['estimatedTotalHits'])) {
                        //     $indexed_threads = (int)$count_response['estimatedTotalHits'];
                        // }
                        $last_indexed_thread_id = self::getLastIndexedThreadId();
                        $indexed_threads = Thread::where('id', '<=', $last_indexed_thread_id)
                            ->whereIn('type', self::$indexable_thread_types)
                            ->count();
                    }
                    
                    // Count threads.
                    $total_threads = Thread::whereIn('type', self::$indexable_thread_types)->count();

                } else {
                    \Option::set('fastersearch.active', false);
                    \Option::set('fastersearch.index_created', false);

                    if (!empty($test_response['message'])) {
                        $auth_error = $test_response['message'];
                        if (!empty($test_response['link'])) {
                            $auth_error .= ' ('.$test_response['link'].')';
                        }
                    } else {
                        $auth_error = __('Unknown API error occurred.');
                    }
                }

            } elseif (\Option::get('fastersearch.active')) {
                \Option::set('fastersearch.active', false);
                \Option::set('fastersearch.index_created', false);
            }

            $last_log_message = Activity::where('log_name', self::LOG_NAME)
                ->orderBy('id', 'desc')
                ->first();

            $params['template_vars'] = [
                'auth_error'   => $auth_error,
                'index_error'  => $index_error,
                'indexed_threads'  => $indexed_threads,
                'total_threads'  => $total_threads,
                'health'       => $health,
                'last_log_message'  => $last_log_message,
            ];

            $params['settings'] = [
                'fastersearch.url' => [
                    'env' => 'FASTERSEARCH_URL',
                ],
                'fastersearch.api_key' => [
                    'env' => 'FASTERSEARCH_API_KEY',
                ],
                'fastersearch.max_total_hits' => [
                    'default' => self::DEFAULT_MAX_TOTAL_HITS
                ],
            ];

            return $params;
        }, 20, 2);

        // Settings view name.
        \Eventy::addFilter('settings.view', function($view, $section) {
            if ($section != FS_MODULE_SECTION) {
                return $view;
            } else {
                return 'fastersearch::settings';
            }
        }, 20, 2);

        // After saving settings.
        \Eventy::addFilter('settings.after_save', function($response, $request, $section, $settings) {

            if ($section != FS_MODULE_SECTION) {
                return $response;
            }

            // Set new value of maxTotalHits.
            if (\Option::get('fastersearch.index_created')) {
                \FasterSearch::setMaxTotalHits($request->settings['fastersearch.max_total_hits'] ?? 0);
            }

            return $response;
        }, 20, 4);

        // Rebuild index.
        \Eventy::addFilter('middleware.web.custom_handle.response', function ($prev, $request, $next) {
            
            $route_name = $request->route()->getName();

            if ($route_name == 'settings'
                && $request->get('fs_rebuild_index')
            ) {
                self::deleteIndex();
                self::createIndex();
                return redirect(route('settings', ['section' => 'meilisearch']));
            }

            return $prev;
        }, 10, 3);

        // Schedule background indexing
        \Eventy::addFilter('schedule', function($schedule) {
            $schedule->command('freescout:fastersearch-perform-indexing')->cron('* * * * *');

            return $schedule;
        });

        // Perform search.
        \Eventy::addFilter('search.conversations.perform', function($conversations, $q, $filters, $user) {

            if (!\FasterSearch::isSearchEnabled()) {
                return $conversations;
            }

            $page = request()->page ?? 1;

            // Filters.
            $filter = [];

            $full_text_filters = [];

            if (!empty($filters['mailbox'])) {
                // Check if the user has access to the mailbox.
                if ($user->hasAccessToMailbox($filters['mailbox'])) {
                    $mailbox_ids[] = $filters['mailbox'];
                } else {
                    unset($filters['mailbox']);
                    $mailbox_ids = $user->mailboxesIdsCanView();
                }
            } else {
                // Get IDs of mailboxes to which user has access
                $mailbox_ids = $user->mailboxesIdsCanView();
            }
            $filter[] = "mid IN [".implode(',', $mailbox_ids)."]";

            if (!empty($filters['assigned'])) {
                if ($filters['assigned'] == Conversation::USER_UNASSIGNED) {
                    $filters['assigned'] = null;
                }
                $filter[] = "uid = ".(int)$filters['assigned'];
            }
            if (!empty($filters['customer'])) {
                $filter[] = [
                    "cid = ".(int)$filters['customer'],
                    "by_cid = ".(int)$filters['customer'],
                ];
            }
            if (!empty($filters['status'])) {
                if (count($filters['status']) == 1) {
                    $filter[] = "status = ".(int)$filters['status'][0];
                } else {
                    $filter[] = "status IN [".implode(',', $filters['status'])."]";
                }
            }
            if (!empty($filters['state'])) {
                if (count($filters['state']) == 1) {
                    $filter[] = "state = ".(int)$filters['state'][0];
                } else {
                    $filter[] = "state IN [".implode(',', $filters['state'])."]";
                }
            }
            if (!empty($filters['subject'])) {
                $full_text_filters['subj'] = self::prepareStringFilter($filters['subject']);
            }
            if (!empty($filters['body'])) {
                $full_text_filters['body'] = self::prepareStringFilter($filters['body']);
            }
            if (!empty($filters['attachments'])) {
                $has_attachments = ($filters['attachments'] == 'yes' ? 1 : 0);
                $filter[] = "has_att = ".(int)$has_attachments;
            }
            if (!empty($filters['type'])) {
                $filter[] = "type = ".(int)$filters['type'];
            }
            if (!empty($filters['number'])) {
                $filter[] = (Conversation::numberFieldName() == 'id' ? 'conv_id' : 'number')." = ".(int)$filters['number'];
            }
            if (!empty($filters['id'])) {
                $filter[] = "conv_id = ".(int)$filters['id'];
            }
            if (!empty($filters['after'])) {
                $filter[] = "ca > ".strtotime($filters['after'].' 00:00:00 '.auth()->user()->timezone);
            }
            if (!empty($filters['before'])) {
                $filter[] = "ca <= ".strtotime($filters['before'].' 23:59:59 '.auth()->user()->timezone);
            }
            if (!empty($filters['attachment name'])) {
                $full_text_filters['att'] = self::prepareStringFilter($filters['attachment name']);
            }
            if (!empty($filters['tag'])) {
                $tag_name = \Modules\Tags\Entities\Tag::normalizeName($filters['tag']);
                if ($tag_name) {
                    $tag_id = \Modules\Tags\Entities\Tag::where('tags.name', $tag_name)
                        ->value('id');

                    if ($tag_id) {
                        $filter[] = "tags = ".$tag_id; // maybe IN is better
                    }
                }
            }
            if (!empty($filters['following'])) {
                if ($filters['following'] == 'yes') {
                    $filter[] = "flwrs = ".(int)auth()->user()->id;
                }
            }
            $has_cf = false;
            foreach ($filters as $filter_name => $filter_value) {
                if ($filter_name[0] == '#') {
                    $has_cf = true;
                    break;
                }
            }
            if ($has_cf && \Module::isActive('customfields')) {
                $custom_fields = \Modules\CustomFields\Entities\CustomField::getSearchCustomFields();
                foreach ($custom_fields as $custom_field) {
                    if (!empty($filters[$custom_field->name])) {
                        $filter[] = "cf.".$custom_field->id.' = "'.self::prepareStringFilter($filters[$custom_field->name]).'"';
                        //$full_text_filters['cf.'.$custom_field->id] = $filters[$custom_field->name];
                    }
                }
            }

            // Determine sorting.
            $sort = 'lr';
            $sorting = Conversation::getConvTableSorting();
            switch ($sorting['sort_by']) {
                case 'date':
                    // Last reply.
                    $sort = 'lr';
                    break;
                case 'number':
                    $sort = (Conversation::numberFieldName() == 'id' ? 'conv_id' : 'number');
                    break;
                case 'subject':
                    $sort = 'subj';
                    break;
            }
            $sort .= ':'.strtolower($sorting['order']);

            // Apply hooks.
            $filter = \Eventy::filter('fastersearch.filters', $filter, $filters);
            $full_text_filters = \Eventy::filter('fastersearch.full_text_filters', $full_text_filters, $filters);

            $q_is_set = !empty($q);
            $full_text_filters_count = count($full_text_filters);

            if (($full_text_filters_count === 1 && !$q_is_set) || $full_text_filters_count === 0) {
                // Perform regular Meilisearch search.
                // If full-text filers are not used or only one full-text filter is used and "q" is not set.
                $query = [
                    'q' => $q,
                    'page' => (int)$page,
                    'hitsPerPage' => Conversation::DEFAULT_LIST_SIZE,
                    'filter' => $filter,
                    'sort' => [$sort],
                    'attributesToRetrieve' => ['conv_id'],
                ];

                if ($full_text_filters_count === 1 && !$q_is_set) {
                    $query['q'] = current($full_text_filters);
                    $query['attributesToSearchOn'] = [key($full_text_filters)];
                }

                $api_response = \FasterSearch::apiCall('indexes/'.self::INDEX_NAME.'/search', $query);

                // Fall back to the standard search if something went wrong during the Meilisearch query.
                if (!isset($api_response['hits'])) {
                    return $conversations;
                }

                $conv_ids = array_column($api_response['hits'] ?? [], 'conv_id');
                $total_hits = $api_response['totalHits'] ?? 0;
            } else {
                // This part is used when the users sets specific filters in the search: Body, Subject, etc.
                // https://github.com/freescout-helpdesk/freescout/issues/3282
                // 
                // Perform multi search when full-text filters are used.
                // 1. Search using standard search: exact match on searchable fields
                // 2. Search on full-text attributes only (subject, body, etc)
                // 3. Intersect results 1 and 2.
                $queries = [];
                $max_limit = (int)\Option::get('fastersearch.max_total_hits', \FasterSearch::DEFAULT_MAX_TOTAL_HITS);

                if ($q_is_set) {
                    $queries[] = [
                        'indexUid' => self::INDEX_NAME,
                        'q' => $q,
                        'limit' => $max_limit,
                        'filter' => $filter,
                        'sort' => [$sort],
                        'attributesToRetrieve' => ['conv_id']
                    ];
                }

                foreach($full_text_filters as $filter_key => $filter_value) {
                    $queries[] = [
                        'indexUid' => self::INDEX_NAME,
                        'q' => $filter_value,
                        'limit' => $max_limit,
                        'filter' => $filter,
                        'sort' => [$sort],
                        'attributesToRetrieve' => ['conv_id'],
                        'attributesToSearchOn' => [$filter_key]
                    ];
                }

                $api_response = \FasterSearch::apiCall('multi-search', [
                    'queries' => $queries
                ]);

                // Fall back to the standard search if something went wrong during the Meilisearch query.
                if (!isset($api_response['results'])) {
                    return $conversations;
                }

                // Fall back to the standard search if
                // at least one result set reached the maxium hit size,
                // so there might be results missing in the final intersected result.
                // if (
                //     count(array_filter($api_response['results'], function($result) use($max_limit) {
                //         return !array_key_exists('estimatedTotalHits', $result) || $result['estimatedTotalHits'] === $max_limit;
                //     })) > 0
                // ) {
                //     return $conversations;
                // }

                $conv_ids_all = call_user_func_array('array_intersect',array_map(function($result) {
                    return array_column($result['hits'] ?? [],'conv_id');
                }, $api_response['results']));

                $total_hits = count($conv_ids_all);

                $conv_ids = array_slice($conv_ids_all, ($page - 1) * Conversation::DEFAULT_LIST_SIZE, Conversation::DEFAULT_LIST_SIZE);
            }

            // Get conversations from DB by conversation IDs.
            $conv_query = Conversation::whereIn('id', $conv_ids);
            $sorting = Conversation::getConvTableSorting();
            if ($sorting['sort_by'] == 'date') {
                $sorting['sort_by'] = 'last_reply_at';
            }
            $conv_query->orderBy($sorting['sort_by'], $sorting['order']);
            $conversations = new LengthAwarePaginator($conv_query->get(), $total_hits, Conversation::DEFAULT_LIST_SIZE);

            return $conversations;
        }, 20, 4);

        // New threads are indexed by cron.
        // \Eventy::addAction('thread.created', function($thread) {
        //     if (!in_array($thread->type, self::$indexable_thread_types)) {
        //         return;
        //     }
        //     self::enqueueThreads([$thread->id]);
        // });

        \Eventy::addAction('fastersearch.process_queues', function() {
            self::processEnqueuedThreads();
            self::processEnqueuedDeleteThreads();
        });

        \Eventy::addAction('thread.updated', function($thread) {
            if (!in_array($thread->type, self::$indexable_thread_types)) {
                return;
            }
            self::enqueueThreads([$thread->id]);
        });
        \Eventy::addAction('thread.deleting', function($thread) {
            if (!in_array($thread->type, self::$indexable_thread_types)) {
                return;
            }
            self::enqueueDeleteThreads([$thread->id]);
        });

        \Eventy::addAction('conversation.updated', function($conversation) {
            self::enqueueConvThreads($conversation->id);
        });

        \Eventy::addAction('conversation.deleting', function($conversation) {
            self::enqueueDeleteConvThreads($conversation->id);
        });

        \Eventy::addAction('conversations.before_delete_forever', function($conversation_ids) {
            $thread_ids = self::getIndexableConversationThreadIds($conversation_ids);
            self::enqueueDeleteThreads($thread_ids);
        });

        \Eventy::addAction('attachment.created', function($attachment) {
            if (!$attachment->thread_id) {
                return;
            }
            self::enqueueThreads([$attachment->thread_id]);
        });
        \Eventy::addAction('attachment.deleted', function($attachment) {
            if (!$attachment->thread_id) {
                return;
            }
            self::enqueueThreads([$attachment->thread_id]);
        });

        // \Eventy::addAction('attachment.group_deleting', function($attachments) {
        //     $thread_ids = [];
        //     foreach ($attachments as $attachment) {
        //         if ($attachment->thread_id) {
        //             $thread_ids[] = $attachment->thread_id;
        //         }
        //     }
        //     self::enqueueThreads($thread_ids);
        // });

        \Eventy::addAction('tag.attached', function($tag, $conversation_id) {
            // if (!\Module::isActive('tags')) {
            //     return;
            // }
            self::enqueueConvThreads($conversation_id);
            self::processQueuesImmediately();
        }, 20, 2);

        \Eventy::addAction('tag.detached', function($tag, $conversation_id) {
            // if (!\Module::isActive('tags')) {
            //     return;
            // }
            self::enqueueConvThreads($conversation_id);
            self::processQueuesImmediately();
        }, 20, 2);

        \Eventy::addAction('custom_field.value_updated', function($field, $conversation_id) {
            self::enqueueConvThreads($conversation_id);
        }, 20, 2);

        \Eventy::addAction('follower.created', function($follower) {
            self::enqueueConvThreads([$follower->conversation_id]);
            self::processQueuesImmediately();
        });

        \Eventy::addAction('follower.deleted', function($follower) {
            self::enqueueConvThreads([$follower->conversation_id]);
            self::processQueuesImmediately();
        });
    }

    public static function processQueuesImmediately()
    {
        \Helper::backgroundAction('fastersearch.process_queues', []);
    }

    public static function getIndexableConversationThreadIds($conversation_ids)
    {
        return Thread::whereIn('conversation_id', $conversation_ids)
                ->whereIn('type', self::$indexable_thread_types)
                ->pluck('id')
                ->toArray();
    }

    public static function enqueueThreads($thread_ids)
    {
        if (!count($thread_ids)) {
            return;
        }
        $index_queue = \Option::get('fastersearch.index_queue', [], true, false);
        $index_queue = array_merge($index_queue, $thread_ids);
        $index_queue = array_unique($index_queue);

        \Option::set('fastersearch.index_queue', $index_queue);
    }

    public static function enqueueDeleteThreads($thread_ids)
    {
        if (!count($thread_ids)) {
            return;
        }
        $index_queue = \Option::get('fastersearch.index_delete_queue', [], true, false);
        $index_queue = array_merge($index_queue, $thread_ids);
        $index_queue = array_unique($index_queue);

        \Option::set('fastersearch.index_delete_queue', $index_queue);
    }

    public static function enqueueConvThreads($conversation_id)
    {
        // Add threads o the indexing queue.
        $thread_ids = Thread::where('conversation_id', $conversation_id)
            ->whereIn('type', self::$indexable_thread_types)
            ->pluck('id')
            ->toArray();

        self::enqueueThreads($thread_ids);
    }

    public static function enqueueDeleteConvThreads($conversation_id)
    {
        // Add threads o the indexing queue.
        $thread_ids = Thread::where('conversation_id', $conversation_id)
            ->whereIn('type', self::$indexable_thread_types)
            ->pluck('id')
            ->toArray();

        self::enqueueDeleteThreads($thread_ids);
    }

    public static function isSearchEnabled()
    {
        return \Option::get('fastersearch.active') && \Option::get('fastersearch.index_created');
    }

    public static function prepareStringFilter($value)
    {
        return addcslashes(trim($value ?? ''), '"');
    }

    public static function setMaxTotalHits($max_total_hits = 0)
    {
        $max_total_hits = $max_total_hits ?: \Option::get('fastersearch.max_total_hits', \FasterSearch::DEFAULT_MAX_TOTAL_HITS);

        // // https://docs.meilisearch.com/reference/api/settings.html#update-pagination-settings
        \FasterSearch::apiCall('indexes/'.\FasterSearch::INDEX_NAME.'/settings/pagination', [
            'maxTotalHits' => (int)$max_total_hits
        ], self::API_METHOD_PATCH);
    }

    public static function getLastIndexedThreadId()
    {
        // This approach is not reliable as any time new thread can be added to the index.
        // $api_response = \FasterSearch::apiCall('indexes/'.self::INDEX_NAME.'/search', [
        //     'limit' => 1,
        //     'sort' => [
        //         'id:desc'
        //     ],
        //     'attributesToRetrieve' => [
        //         'id'
        //     ]
        // ]);

        // return (int)($api_response['hits'][0]['id'] ?? 0);
        
        return \Option::get('fastersearch.last_thread_id', 0);
    }

    public static function performIndexing()
    {
        if (!\Option::get('fastersearch.index_created')) {
            return;
        }
        
        $now = time();

        // Index new threads.
        $last_indexed_thread_id = self::getLastIndexedThreadId();

        // Push news threads into Meilisearch index.
        do {
            $threads = Thread::where('id', '>', $last_indexed_thread_id)
                ->whereIn('type', self::$indexable_thread_types)
                ->limit(self::INDEXING_BUNCH)
                ->orderBy('id')
                ->get();
            $docs = self::getThreadsDocuments($threads);

            if (!$docs) {
                break;
            }

            $result = \FasterSearch::indexDocuments($docs);

            // Do not continue if some error occurred.
            if (!$result) {
                break;
            }

            $last_indexed_thread_id = $docs[count($docs)-1]['id'];
            \Option::set('fastersearch.last_thread_id', $last_indexed_thread_id);

            // Do not spend on this more than limit.
            if (time() - $now > self::INDEXING_TIME_LIMIT) {
                break;
            }
        } while(count($threads));
        
        // We need to do it here, as in do...while() it may not be set because of "break".
        \Option::set('fastersearch.last_thread_id', $last_indexed_thread_id);

        // Add/Update threads in the index.
        self::processEnqueuedThreads();
        
        // Delete deleted threads from the index.
        self::processEnqueuedDeleteThreads();
    }

    public static function processEnqueuedThreads()
    {
        $index_queue = \Option::get('fastersearch.index_queue', [], true, false);
        
        if (count($index_queue)) {
            
            \Option::set('fastersearch.index_queue', []);

            if (!\FasterSearch::isSearchEnabled()) {
                return;
            }

            for ($bunch = 0; $bunch <= floor(count($index_queue) / self::INDEXING_BUNCH); $bunch++) {
                $thread_ids_bunch = array_slice($index_queue, $bunch*self::INDEXING_BUNCH, self::INDEXING_BUNCH);

                $threads = Thread::whereIn('id', $thread_ids_bunch)
                    ->whereIn('type', self::$indexable_thread_types)
                    ->get();
                $docs = self::getThreadsDocuments($threads);

                $result = \FasterSearch::indexDocuments($docs);

                // Do not continue if some error occurred.
                if (!$result) {
                    // Add remaining threads back to the queue.
                    self::enqueueThreads(array_slice($index_queue, $bunch*self::INDEXING_BUNCH));
                    break;
                }
            }
        }
    }

    public static function processEnqueuedDeleteThreads()
    {
        $index_queue = \Option::get('fastersearch.index_delete_queue', [], true, false);

        if (count($index_queue)) {

            \Option::set('fastersearch.index_delete_queue', []);

            if (!\FasterSearch::isSearchEnabled()) {
                return;
            }

            for ($bunch = 0; $bunch <= floor(count($index_queue) / self::INDEXING_BUNCH); $bunch++) {
                $thread_ids_bunch = array_slice($index_queue, $bunch*self::INDEXING_BUNCH, self::INDEXING_BUNCH);
                $result = \FasterSearch::indexDeleteDocuments($thread_ids_bunch);

                // Do not continue if some error occurred.
                if (!$result) {
                                        // Add remaining threads back to the queue.
                    self::enqueueDeleteThreads(array_slice($index_queue, $bunch*self::INDEXING_BUNCH));
                    break;
                }
            }
        }
    }

    public static function indexDocuments($docs)
    {
        $api_response = \FasterSearch::apiCall('indexes/'.self::INDEX_NAME.'/documents', $docs);
        
        return !empty($api_response['taskUid']);
    }

    public static function indexDeleteDocuments($thread_ids)
    {
        $api_response = \FasterSearch::apiCall('indexes/'.self::INDEX_NAME.'/documents/delete-batch', $thread_ids);
        return !empty($api_response['taskUid']);
    }

    public static function getThreadsDocuments($threads)
    {
        $docs = [];

        $thread_ids = $threads->pluck('id');
        $conv_ids = $threads->pluck('conversation_id');

        // Preload attachments.
        $attachments = Attachment::select(['thread_id', 'file_name'])
            ->whereIn('thread_id', $thread_ids)
            ->get();

        // Preload tags.
        $tags = [];
        if (\Module::isActive('tags')) {
            $tags = \Modules\Tags\Entities\ConversationTag::select(['conversation_id', 'tag_id'])
                ->whereIn('conversation_id', $conv_ids)
                ->get();
        }
        // Custom fields.
        $custom_fields = [];
        if (\Module::isActive('customfields')) {
            $custom_fields = \Modules\CustomFields\Entities\ConversationCustomField::select(['conversation_id', 'custom_field_id', 'value'])
                ->whereIn('conversation_id', $conv_ids)
                ->get();
        }
        // Followers.
        $followers = Follower::select(['conversation_id', 'user_id'])
            ->whereIn('conversation_id', $conv_ids)
            ->get();

        foreach ($threads as $thread) {
            $conv = $thread->conversation;
            if (!$conv) {
                continue;
            }
            $customer = $conv->customer;

            $doc = [
                // Thread.
                'id' => (int)$thread->id,

                'body' => strip_tags($thread->body ?? ''),
                'from' => $thread->from.'',
                'to' => $thread->to.'',
                'cc' => $thread->cc.'',
                'bcc' => $thread->bcc.'',

                'by_cid' => (int)$thread->created_by_customer_id,

                // Conversation.
                'conv_id' => (int)$conv->id,
                'number' => (int)$conv->number,
                'subj' => $conv->subject.'',
                'c_email' => $conv->customer_email.'',
                'c_name' => ($customer ? $customer->getFullName() : '').'',

                'mid' => (int)$conv->mailbox_id,
                'uid' => (int)$conv->user_id,
                'cid' => (int)$conv->customer_id,
                'status' => (int)$conv->status,
                'state' => (int)$conv->state,
                'has_att' => (int)(bool)$conv->has_attachments,
                'type' => (int)$conv->type,
                'ca' => (int)$conv->created_at->timestamp,
                'lr' => (int)($conv->last_reply_at ? $conv->last_reply_at->timestamp : 0),
            ];

            foreach ($attachments as $attachment) {
                if ($attachment['thread_id'] == $thread->id) {
                    $doc['att'] = $doc['att'] ?? [];
                    $doc['att'][] = $attachment->file_name;
                }
            }
            foreach ($tags as $tag) {
                if ($tag->conversation_id == $thread->conversation_id) {
                    $doc['tags'] = $doc['tags'] ?? [];
                    $doc['tags'][] = $tag->tag_id;
                }
            }
            foreach ($custom_fields as $custom_field) {
                if ($custom_field->conversation_id == $thread->conversation_id) {
                    $doc['cf'] = $doc['cf'] ?? [];
                    $doc['cf'][$custom_field->custom_field_id] = $custom_field->value;
                }
            }
            foreach ($followers as $follower) {
                if ($follower->conversation_id == $thread->conversation_id) {
                    $doc['flwrs'] = $doc['flwrs'] ?? [];
                    $doc['flwrs'][] = $follower->user_id;
                }
            }
            
            $doc = \Eventy::filter('fastersearch.thread_doc', $doc, $thread, $conv);

            $docs[] = $doc;
        }

        return $docs;
    }

    /*
    public static function encodeAttName($name)
    {
        return substr(md5(mb_strtolower(trim($name))), 0, 8);
    }
    */

    public static function createIndex()
    {
        $index_response = \FasterSearch::apiCall('indexes', [
            'uid' => \FasterSearch::INDEX_NAME,
            'primaryKey' => 'id',
        ]);

        if (!empty($index_response['indexUid'])) {
            sleep(1);
            
            // This is needed for sorting by thread id.
            // https://docs.meilisearch.com/reference/api/settings.html#update-searchable-attributes
            // \FasterSearch::apiCall('indexes/'.\FasterSearch::INDEX_NAME.'/settings/sortable-attributes', [
            //     'id', // thread_id
            //     'lr', // last_reply_at
            // ], self::API_METHOD_PUT);

            // // https://docs.meilisearch.com/reference/api/settings.html#update-pagination-settings
            // \FasterSearch::setMaxTotalHits();

            // \FasterSearch::apiCall('indexes/'.\FasterSearch::INDEX_NAME.'/settings', [
            //     'distinctAttribute' => 'conv_id'
            // ], self::API_METHOD_PATCH);

            // // https://docs.meilisearch.com/reference/api/settings.html#update-searchable-attributes
            // \FasterSearch::apiCall('indexes/'.\FasterSearch::INDEX_NAME.'/settings/searchable-attributes', [
            //     'conv_id',
            //     'number',
            //     'subj',
            //     'body',
            //     'c_email',
            //     'c_name',
            //     'from',
            //     'to',
            //     'cc',
            //     'bcc',
            // ], self::API_METHOD_PUT);

            $index_settings = [
                'sortableAttributes' => [
                    'id', // thread_id
                    'lr', // last_reply_at
                    'number',
                    'subj',
                    'conv_id', // needs to be added due line 369
                ],
                'searchableAttributes' => [
                    'conv_id',
                    'number',
                    'subj',
                    'body',
                    'c_email',
                    'c_name',
                    'from',
                    'to',
                    'cc',
                    'bcc',
                    'att',
                    //'cf',
                ],
                'filterableAttributes' => [
                    'uid',
                    'cid',
                    'by_cid',
                    'mid',
                    'status',
                    'state',
                    //'subj',
                    'has_att',
                    'type',
                    //'body',
                    'number',
                    'conv_id',
                    'ca',
                    // 'att',
                    'tags',
                    'cf',
                    'flwrs',
                ],
                'distinctAttribute' => 'conv_id',
                'pagination' => [
                    'maxTotalHits' => (int)\Option::get('fastersearch.max_total_hits', \FasterSearch::DEFAULT_MAX_TOTAL_HITS)
                ]
            ];

            $index_settings = \Eventy::filter('fastersearch.index_settings', $index_settings);

            \FasterSearch::apiCall('indexes/'.\FasterSearch::INDEX_NAME.'/settings', $index_settings, self::API_METHOD_PATCH);
        }

        if (!empty($index_response['indexUid'])) {
            // Success.
            \Option::set('fastersearch.index_created', true);
        } else {
            // Error.
            \Option::set('fastersearch.index_created', false);
            \Option::set('fastersearch.last_thread_id', 0);
        }

        return $index_response;
    }

    public static function deleteIndex()
    {
        \FasterSearch::apiCall('indexes/'.\FasterSearch::INDEX_NAME.'/documents', [], self::API_METHOD_DELETE);
        \Option::set('fastersearch.index_created', false);
        \Option::set('fastersearch.last_thread_id', 0);
        return \FasterSearch::apiCall('indexes/'.\FasterSearch::INDEX_NAME, [], self::API_METHOD_DELETE);
    }

    // https://docs.meilisearch.com/reference/api/
    public static function apiCall($method, $params = [], $http_method = self::API_METHOD_POST)
    {
        $response = [

        ];

        $api_url = rtrim(config('fastersearch.url'), '/').'/'.$method;
        if (($http_method == self::API_METHOD_GET || $http_method == self::API_METHOD_DELETE)
            && !empty($params)
        ) {
            $api_url .= '?'.http_build_query($params);
        }

        try {
            $ch = curl_init($api_url);

            $headers = [
                'Content-Type: application/json',
                'Authorization: Bearer '.config('fastersearch.api_key'),
            ];

            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $http_method);
            if ($http_method == self::API_METHOD_POST 
                || $http_method == self::API_METHOD_PUT
                || $http_method == self::API_METHOD_PATCH
            ) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
            }
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            \Helper::setCurlDefaultOptions($ch);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

            $json_response = curl_exec($ch);

            $response = json_decode($json_response, true);

            $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            if (curl_errno($ch)) {
                throw new \Exception(curl_error($ch), 1);
            }

            curl_close($ch);

            if (empty($response) && $status != 204) {
                throw new \Exception(__('Empty API response. Check your Meilisearch credentials. HTTP status code: :status', ['status' => $status]), 1);
            } elseif ($status == 204) {
                return [
                    'status' => 'success',
                ];
            } elseif (!empty($response['message'])) {
                $msg = 'Meilisearch API error: '.$response['message'];
                if (!empty($response['link'])) {
                    $msg .= ' ('.$response['link'].')';
                }
                \FasterSearch::log($msg);
            }

        } catch (\Exception $e) {
            $msg = 'Meilisearch API error: '.$e->getMessage().'; Response: '.json_encode($response).'; Method: '.$method.'; Parameters: '.json_encode($params);
            \FasterSearch::log($msg);

            return [
                'status' => 'error',
                'message' => __('API error:').' '.$e->getMessage()
            ];
        }
        
        return $response;
    }

    public static function log($msg)
    {
        \Helper::log(self::LOG_NAME, $msg);
        \Log::error($msg);
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
            __DIR__.'/../Config/config.php' => config_path('fastersearch.php'),
        ], 'config');
        $this->mergeConfigFrom(
            __DIR__.'/../Config/config.php', 'fastersearch'
        );
    }

    /**
     * Register views.
     *
     * @return void
     */
    public function registerViews()
    {
        $viewPath = resource_path('views/modules/fastersearch');

        $sourcePath = __DIR__.'/../Resources/views';

        $this->publishes([
            $sourcePath => $viewPath
        ],'views');

        $this->loadViewsFrom(array_merge(array_map(function ($path) {
            return $path . '/modules/fastersearch';
        }, \Config::get('view.paths')), [$sourcePath]), 'fastersearch');
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
     * https://github.com/nWidart/laravel-modules/issues/626
     * https://github.com/nWidart/laravel-modules/issues/418#issuecomment-342887911
     * @return [type] [description]
     */
    public function registerCommands()
    {
        $this->commands([
            \Modules\FasterSearch\Console\PerformIndexing::class
        ]);
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
