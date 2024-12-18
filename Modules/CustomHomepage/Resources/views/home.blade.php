@extends('layouts.app')

@section('guest_mode', 1)
@section('no_footer', 1)

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-8 col-md-offset-2">
            {!! $homepage_html !!}
        </div>
    </div>
</div>
@endsection
