<div class="form-group{{ $errors->has('icon') ? ' has-error' : '' }} margin-bottom-0">
    <label for="icon_url" class="col-sm-2 control-label">{{ __('Icon') }}</label>

    <div class="col-sm-6">
        <div class="controls">
            @if ($icon_url)
                <div id="mi-icon-container">
                    <p>
                        <input type="hidden" name="has_icon" value="1">
                        <img src="{{ $icon_url }}" alt="{{ __('Icon') }}" width="50" height="50" class="mi-icon">
                    </p>
                    @if ($is_custom)
                        <p>
                            <a href="#" id="mi-delete-icon">{{ __('Delete Icon') }}</a>
                        </p>
                    @endif
                </div>
            @endif

            <input type="file" name="icon">
            <p class="block-help">{{ __('Image will be re-sized to :dimensions. JPG, GIF, PNG accepted.', ['dimensions' => '50x50']) }}</p>
        </div>
        @include('partials/field_error', ['field'=>'icon'])
    </div>
</div>

@section('javascript')
    @parent
    $(document).ready(function(){
        $('#mi-delete-icon').click(function(e) {
            $('#mi-icon-container').remove();
            e.preventDefault();
        });
    });
@endsection