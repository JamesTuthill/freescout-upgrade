@if (count($categories))
@php
$list_open = false;
@endphp
@foreach($categories as $category)
@if (!$category->expand || (empty($category->categories) && !$category->getArticlesCount(true)) || !empty($no_expand))
@if (!$list_open)
@php
$list_open = true;
@endphp
<div class="kb-category-panels">
    @endif
    <a href="{{ $category->urlFrontend($mailbox) }}" class="kb-category-panel">
        @if (isset($category->icon_file))
            <div class="kb-category-panel-icon">
                <img src="/img/{{ $category->icon_file }}" class="" width="50" height="50" alt="{{ $category->name }} Icon">
            </div>
        @endif
        <div class="kb-category-name">
            <div class="kb-category-panel-title">{{ $category->name }}</div>
            @if ($category->description)
            <div class="kb-category-panel-descr">{{ $category->description }}</div>
            @endif
            <div class="kb-category-panel-info"></div>
        </div>
    </a>
    @if ($loop->last && $list_open)
    @php
    $list_open = false;
    @endphp
</div>
@endif
@else
@if ($list_open)
@php
$list_open = false;
@endphp
</div>
@endif
<div class="kb-sub-heading">{{ $category->name }}</div>
@if ($category->description)
<div class="kb-sub-heading-descr">{{ $category->description }}</div>
@endif
@if (count($category->categories))
@include('knowledgebase::partials/frontend/category_panels', ['categories' => $category->categories, 'no_expand' => true])
@else
<div class="margin-top">
    @include('knowledgebase::partials/frontend/articles', ['articles' => $category->articles_published, 'category_id' => $category->id])
</div>
@endif
@endif
@endforeach
@endif
