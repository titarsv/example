<div class="row">
    <div class="col-sm-12">
        <select class="form-control"
                name="fields[all]{{ !empty($parent) ? $parent.'['.(isset($iterator) ? $iterator : 0).']' : '' }}[{{ $field->slug }}]"
                data-prefix="fields[all]"
                autocomplete="off"
                data-name="{{ $field->slug }}">
            @foreach(explode(PHP_EOL, $field->choices) as $choice)
                @php
                    $choice_arr = explode(' : ', $choice);
                @endphp
                <option value="{{ $choice_arr[0] }}"
                        @if(isset($field->value) && $field->value == $choice_arr[0])
                        selected
                        @endif
                >{{ $choice_arr[1] }}</option>
            @endforeach
        </select>
    </div>
</div>
