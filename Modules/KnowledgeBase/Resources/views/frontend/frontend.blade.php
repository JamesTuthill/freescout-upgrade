@extends('knowledgebase::layouts.portal')

@section('title', \Kb::getKbName($mailbox))

@section('content')

	@if (count($categories))
		@include('knowledgebase::partials/frontend/category_panels', ['categories' => $categories])
	@elseif (count($articles))
		@include('knowledgebase::partials/frontend/articles', ['articles' => $articles])
	@else
		@include('partials/empty', ['icon' => 'book'])
	@endif

@endsection
