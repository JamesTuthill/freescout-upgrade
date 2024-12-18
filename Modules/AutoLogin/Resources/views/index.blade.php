@extends('autologin::layouts.master')

@section('content')
    <h1>Hello World</h1>

    <p>
        This view is loaded from module: {!! config('autologin.name') !!}
    </p>
@stop
