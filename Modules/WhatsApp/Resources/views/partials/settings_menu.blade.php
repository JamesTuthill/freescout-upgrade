<li @if (\Helper::isMenuSelected('whatsapp'))class="active"@endif><a href="{{ route('mailboxes.whatsapp.settings', ['mailbox_id'=>$mailbox->id]) }}"><i class="glyphicon glyphicon-erase"></i> {{ __('WhatsApp') }}</a></li>