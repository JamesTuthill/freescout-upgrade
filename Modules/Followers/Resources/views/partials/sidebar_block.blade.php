<div class="conv-sidebar-block followers-block" data-auth_user_name="{{ Auth::user()->getFullName() }}">
    <div class="panel-group accordion accordion-empty">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h4 class="panel-title">
                    <a data-toggle="collapse" href=".collapse-followers">{{ __("Followers") }} 
                        <b class="caret"></b>
                    </a>
                </h4>
            </div>
            <div class="collapse-followers panel-collapse collapse in">
                <div class="panel-body">
                    <div class="sidebar-block-header2"><strong>{{ __("Followers") }}</strong> (<a data-toggle="collapse" href=".collapse-followers">{{ __('close') }}</a>)</div>
                    <ul class="sidebar-block-list followers-list">
                        @foreach ($followers as $follower)
                            <li>
                                <a href="#" data-user_id="{{ $follower->id }}" class="help-link followers-item followers-item-{{ $follower->id }} @if ($follower->subscribed) followers-subscribed @endif @if ($follower->subscribed && !$follower->added_by_user_id && $follower->id != auth()->user()->id) followers-self @endif"><i class="glyphicon glyphicon-bell"></i> {{ $follower->getFullName() }}</a>
                            </li>
                        @endforeach
                    </ul>
                    @if ($show_add) 
                        <a href="{{ route('conversations.followers.ajax_html', ['action' => 'add', 'conversation_id' => $conversation->id]) }}" class="sidebar-block-link link-blue followers-add-trigger" data-trigger="modal" ddata-modal-title="{{ __('Add Subscribers') }}" data-modal-no-footer="true" data-modal-on-show="initFollowersAdd">{{ __("Add Followers") }}</a>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>