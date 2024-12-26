@extends('knowledgebase::layouts.portal')

@if ($category)
@section('title', $category->name)
@else
@section('title', \Kb::getKbName($mailbox))
@endif

@section('content')

@if (!empty($category))

<div class="row kb-frontend-wrapper">
    <div class="hidden-xs hidden-sm hidden-md hidden-lg" id="kb-mobie-nav-overlay"></div>
    <div class="hidden-xs hidden-sm hidden-md hidden-lg" id="kb-category-nav-mobile">
        <div class="kb-category-nav-item">
            <a href="#" id="kb-category-nav-close" class="text-right"><img src="/img/close.svg" class="" width="14" height="14" alt="Close Icon"></a>
        </div>
        @include('knowledgebase::partials/frontend/category_nav', ['categories' => \KbCategory::getTree($mailbox->id), 'selected_category_id' => $category->id, 'target_elm' => 'kb-category-nav-mobile'])
    </div>
    <div id="kb-breadcrumbs-and-nav-container" class="col-sm-3 col-md-2">
        <button type="button" id="kb-category-nav-toggle" class="navbar-toggle collapsed visible-xs">
            <span class="sr-only">Toggle Navigation</span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
        </button>
        @include('knowledgebase::partials/frontend/breadcrumbs')
        <div class="hidden-xs" id="kb-category-nav">
            @include('knowledgebase::partials/frontend/category_nav', ['categories' => \KbCategory::getTree($mailbox->id), 'selected_category_id' => $category->id])
        </div>
    </div>
    <div class="col-sm-9 col-md-10 kb-category-content-column">
        <div class="kb-category-content">
            <h1 class="kb-title">{{ $category->name }}</h1>
            @if ($category->description)
            <p class="text-help">{{ $category->description }}</p>
            @endif
            <div class="margin-top">
                @include('knowledgebase::partials/frontend/category_panels', ['categories' => $subcategories])
            </div>
            <div class="margin-top">
                @include('knowledgebase::partials/frontend/articles', ['articles' => $articles])
            </div>
        </div>
    </div>
</div>
@else
@include('knowledgebase::partials/frontend/unavailable')
@endif

@endsection
