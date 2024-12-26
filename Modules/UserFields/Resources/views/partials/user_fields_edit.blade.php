@if (count($user_fields))
    @foreach ($user_fields as $user_field)
        <div class="form-group{{ $errors->has($user_field->getNameEncoded()) ? ' has-error' : '' }}">
            <label for="{{ $user_field->getNameEncoded() }}" class="col-sm-2 control-label">{{ $user_field->name }}@if ($user_field->required) <i class="required-asterisk"></i>@endif</label>

            <div class="col-sm-6">

                @if ($user_field->type == UserField::TYPE_DROPDOWN)
                    <select class="form-control input-sized @if (!$user_field->required && !$user_field->value) placeholdered @endif"
                            name="{{ $user_field->getNameEncoded() }}"  @if ($user_field->required) required @endif>
                        @if (!$user_field->required)
                            <option value="" @if (!$user_field->value) selected @endif>{{ __('(optional)') }}</option>
                        @else
                            <option value=""></option>
                        @endif
                        
                        @if (is_array($user_field->options))
                            @foreach($user_field->options as $option_key => $option_name)
                                <option value="{{ $option_key }}" {{ ($user_field->value == $option_key) ? 'selected' : '' }}>{{ $option_name }}</option>
                            @endforeach
                        @endif
                    </select>
                @elseif ($user_field->type == UserField::TYPE_MULTI_LINE)
                    <textarea class="form-control input-sized" name="{{ $user_field->getNameEncoded() }}" rows="2" @if ($user_field->required) required @else placeholder="{{ __('(optional)') }}" @endif @if ($user_field->required) required @endif>{{ old($user_field->getNameEncoded(), $user_field->value) }}</textarea>
                @elseif ($user_field->type == UserField::TYPE_SINGLE_LINE && !empty($user_field->options['autosuggest']))
                    <select class="form-control input-sized @if (!$user_field->required && !$user_field->value) placeholdered @endif  @if (!empty($user_field->options['autosuggest'])) uf-cf-autosuggest @endif"
                            name="{{ $user_field->getNameEncoded() }}"  @if ($user_field->required) required @endif>
                        @if (!$user_field->required)
                            <option value="" @if (!$user_field->value) selected @endif>{{ __('(optional)') }}</option>
                        @else
                            <option value=""></option>
                        @endif
                        
                        @if ($user_field->value)
                            <option value="{{ $user_field->value }}" selected="selected">{{ $user_field->value }}</option>
                        @endif
                    </select>
                @else
                    <input name="{{ $user_field->getNameEncoded() }}" class="form-control input-sized @if ($user_field->type == UserField::TYPE_DATE) uf-cf-type-date @endif" value="{{ $user_field->value }}"
                        @if ($user_field->type == UserField::TYPE_NUMBER)
                            type="number"
                        @else
                            type="text"
                        @endif

                        @if ($user_field->required) required @else placeholder="{{ __('(optional)') }}" @endif 
                    />
                @endif

                @include('partials/field_error', ['field'=>$user_field->getNameEncoded()])
            </div>
        </div>
    @endforeach
    @include('partials/include_datepicker')

    @section('javascript')
        @parent
        ufInitUserFields();
    @endsection
@endif