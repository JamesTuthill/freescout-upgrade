<li><a href="{{ route('gdpr.ajax_html', ['action' => 'delete_customer', 'param' => $customer->id ]) }}" data-trigger="modal" data-modal-title="{{ __('Delete Customer With Conversations') }}" data-modal-no-footer="true" data-modal-on-show="gdprInitDeleteCustomer">{{ __('Delete With Conversations') }}</a></li>