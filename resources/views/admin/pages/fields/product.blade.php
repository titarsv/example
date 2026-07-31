<div class="row">
    <div class="col-sm-12">
        <select class="form-control"
                name="fields[all]{{ !empty($parent) ? $parent.'['.(isset($iterator) ? $iterator : 0).']' : '' }}[{{ $field->slug }}]"
                data-prefix="fields[all]"
                autocomplete="off"
                data-name="{{ $field->slug }}">
            <option value=""></option>
            @foreach($products as $product)
                <option value="{{ $product->id }}"{{ isset($field->value) && $field->value == $product->id ? ' selected' : '' }}>{{ $product->name }}</option>
            @endforeach
        </select>
    </div>
</div>