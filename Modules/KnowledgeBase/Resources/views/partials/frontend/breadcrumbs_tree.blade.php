@if ($breadcrumb_category->kb_category_id)
@include('knowledgebase::partials/frontend/breadcrumbs_tree', ['breadcrumb_category' => \KbCategory::findCached($breadcrumb_category->kb_category_id)])
@endif
 <span class="text-help">></span> <a href="{{ $breadcrumb_category->urlFrontend($mailbox) }}">{{ $breadcrumb_category->name }}</a>
