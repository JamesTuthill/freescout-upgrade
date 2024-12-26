@if (count($saved_replies))

    <div class="panel-group accordion saved-replies-tree panel-tree @if ($saved_replies[0]->parent_saved_reply_id) panel-nested @endif">
        @foreach ($saved_replies as $saved_reply)
            <div class="panel panel-default panel-sortable" id="saved-reply-{{ $saved_reply->id }}" data-saved-reply-id="{{ $saved_reply->id }}">
                <div class="panel-heading">
                    <span class="handle"><i class="glyphicon glyphicon-menu-hamburger"></i></span>
                    <h4 class="panel-title">
                        <a data-toggle="collapse" data-parent="#accordion" href="#collapse-{{ $saved_reply->id }}">
                            <span>@if ($saved_reply->saved_replies)<small class="glyphicon glyphicon-list-alt text-help"></small> @endif{{ '' }}@if ($saved_reply->global)<small class="glyphicon glyphicon-globe text-success"></small> @endif<span>{{ $saved_reply->name }}</span></span>
                        </a>
                    </h4>
                </div>
                <div id="collapse-{{ $saved_reply->id }}" class="panel-collapse collapse">
                    <div class="panel-body">
                        <form class="form-horizontal" method="POST" action="">

                            <div class="form-group">
                                <label class="col-md-1 control-label">{{ __('Name') }}</label>

                                <div class="col-md-11">
                                    <input class="form-control" name="name" maxlength="75" value="{{ $saved_reply->name }}" />
                                </div>
                            </div>

                            <div class="form-group @if (!empty($saved_reply->saved_replies)) hidden @endif">
                                <label class="col-md-1 control-label">{{ __('Reply') }}</label>

                                <div class="col-md-11">
                                    <textarea class="form-control saved-reply-text" name="text" rows="8">{{ $saved_reply->text }}</textarea>
                                </div>
                            </div>

                            @if (empty($saved_reply->saved_replies))
                                <div>
                                    <div class="col-md-11 col-md-offset-1">
                                        <div class="thread-attachments sr-attachments-upload attachments-upload form-group" @if (empty($saved_reply->attachments)) style="display:none" @else style="display:block" @endif>
                                            @if (!empty($saved_reply->attachments))
                                                @foreach ($saved_reply->getAttachments(true) as $attachment)
                                                    <input type="hidden" name="attachments_all[]" value="{{ encrypt($attachment->id) }}">
                                                    <input type="hidden" name="attachments[]" value="{{ encrypt($attachment->id) }}" class="atachment-upload-{{ $attachment->id }}">
                                                @endforeach
                                            @endif
                                            <ul>
                                                @if (!empty($saved_reply->attachments))
                                                    @foreach ($saved_reply->getAttachments(true) as $attachment)
                                                        <li data-attachment-id="{{ $attachment->id }}" data-mime="{{ $attachment->mime_type }}" class="atachment-upload-{{ $attachment->id }} attachment-loaded">
                                                            <a href="{{ $attachment->url() }}" class="attachment-link break-words" target="_blank">{{ $attachment->file_name }}</a>
                                                            <span class="text-help">({{ $attachment->getSizeName() }})</span>
                                                            <a href="{{ $attachment->url() }}" download><i class="glyphicon glyphicon-download-alt small"></i></a> 
                                                            <i class="glyphicon glyphicon-remove"></i>
                                                        </li>
                                                    @endforeach
                                                @endif
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            <div class="form-group @if (empty($saved_reply->saved_replies)) hidden @endif">
                                <label class="col-md-1 control-label">{{ __('Reply') }}</label>

                                <div class="col-md-11">
                                    <label class="control-label text-help">
                                        {{ __('The text is hidden as this saved reply contains nested saved replies') }}
                                    </label>
                                </div>
                            </div>

                            @if (count($categories) > 1)
                                <div class="form-group">
                                    <label class="col-md-1 control-label">{{ __('Category') }}</label>
                                    @php
                                        $categories_hash = \SavedReply::savedRepliesListHash($categories);
                                    @endphp
                                    <div class="col-md-11">
                                        <select class="form-control" name="parent_saved_reply_id">
                                            <option value=""></option>
                                            @foreach($categories as $category)
                                                @if ($category->id != $saved_reply->id && !$saved_reply->isChild($category->id, $categories, $categories_hash))
                                                    @if (!$category->id)
                                                        <option disabled >——————————</option>
                                                    @else
                                                        <option value="{{ $category->id }}" @if ($category->id == $saved_reply->parent_saved_reply_id) selected @endif>{{ $category->name }}</option>
                                                    @endif
                                                @endif
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            @endif

                            <div class="form-group">
                                <label class="col-md-1 control-label">{{ __('Global') }}</label>

                                <div class="col-md-11">
                                    <div class="controls">
                                        <div class="onoffswitch-wrap">
                                            <div class="onoffswitch">
                                                <input type="checkbox" name="global" value="1" id="global_{{ $saved_reply->id }}" class="onoffswitch-checkbox" @if ($saved_reply->global) checked="checked" @endif>
                                                <label class="onoffswitch-label" for="global_{{ $saved_reply->id }}"></label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group margin-top margin-bottom-10">
                                <div class="col-md-11 col-md-offset-1">
                                    <button type="button" class="btn btn-primary saved-reply-save" data-saved_reply_id="{{ $saved_reply->id }}" data-loading-text="{{ __('Saving') }}…">{{ __('Save Reply') }}</button> 
                                    <a href="#" class="btn btn-link text-danger sr-delete-trigger" data-loading-text="{{ __('Deleting') }}…" data-saved_reply_id="{{ $saved_reply->id }}">{{ __('Delete') }}</a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                @if (!empty($saved_reply->saved_replies))
                    @include('savedreplies::partials/saved_replies_tree', ['saved_replies' => $saved_reply->saved_replies])
                @endif
            </div>
        @endforeach
    </div>

@endif