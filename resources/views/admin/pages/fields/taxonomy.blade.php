<div class="row">
    <div class="col-sm-12">
        <select class="form-control"
                name="fields[all]{{ !empty($parent) ? $parent.'['.(isset($iterator) ? $iterator : 0).']' : '' }}[{{ $field->slug }}]"
                data-prefix="fields[all]"
                autocomplete="off"
                data-name="{{ $field->slug }}">
            <option value=""></option>
            @foreach($categories as $category)
                <option value="{{ $category->id }}"{{ isset($field->value) && $field->value == $category->id ? ' selected' : '' }}>{{ $category->name }}</option>
            @endforeach
        </select>
    </div>
</div>