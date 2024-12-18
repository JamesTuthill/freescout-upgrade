@if (count($articles))
<div class="kb-articles text-larger">
    @foreach($articles as $article)
    <a href="{{ $article->urlFrontend($mailbox, $category_id ?? null) }}"><img src="/img/doc.svg" class="" width="20" height="20" alt="Document Icon"> &nbsp;{{ $article->title }}</a>
    @endforeach
</div>
@endif
