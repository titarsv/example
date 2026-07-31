@foreach($items as $item)
    <li>
        <div class="checkbox">
            <input type="checkbox" class="menu-item-checkbox" id="edit-menu-category-{{$key}}-{{ $item->id }}" name="menu-item[-{{ $i }}][menu-item-object-id]" value="{{ $item->id }}"/>
            <label class="menu-item-title" for="edit-menu-category-{{$key}}-{{ $item->id }}">
                {{ $item->tree_name_preloaded ?? $item->tree_name }}
            </label>
        </div>
        <input type="hidden" class="menu-item-db-id" name="menu-item[-{{ $i }}][menu-item-db-id]" value="0"/>
        <input type="hidden" class="menu-item-object" name="menu-item[-{{ $i }}][menu-item-object]" value="category"/>
        <input type="hidden" class="menu-item-parent-id" name="menu-item[-{{ $i }}][menu-item-parent-id]" value="{{ $item->parent_id }}"/>
        <input type="hidden" class="menu-item-type" name="menu-item[-{{ $i }}][menu-item-type]" value="taxonomy"/>
        <input type="hidden" class="menu-item-title" name="menu-item[-{{ $i }}][menu-item-title]" value="{{ $item->tree_name_preloaded ?? $item->tree_name }}"/>
        <input type="hidden" class="menu-item-url" name="menu-item[-{{ $i }}][menu-item-url]" value="{{ $item->link() }}"/>
        <input type="hidden" class="menu-item-target" name="menu-item[-{{ $i }}][menu-item-target]" value=""/>
        <input type="hidden" class="menu-item-attr_title" name="menu-item[-{{ $i }}][menu-item-attr_title]" value=""/>
        <input type="hidden" class="menu-item-classes" name="menu-item[-{{ $i }}][menu-item-classes]" value=""/>
        <input type="hidden" class="menu-item-xfn" name="menu-item[-{{ $i }}][menu-item-xfn]" value=""/>
    </li>
    @php $i++; @endphp
@endforeach
