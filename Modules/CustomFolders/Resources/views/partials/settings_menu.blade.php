<li @if (Route::is('mailboxes.custom_folders'))class="active"@endif><a href="{{ route('mailboxes.custom_folders', ['id'=>$mailbox->id]) }}"><i class="glyphicon glyphicon-folder-close"></i> {{ __('Custom Folders') }}</a></li>