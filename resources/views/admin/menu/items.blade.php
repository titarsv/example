@foreach($items as $item)
    <li id="menu-item-{{ $item->id }}" class="menu-item menu-item-depth-{{ $item->depth }} menu-item-page menu-item-edit-inactive">
        <div class="menu-item-bar">
            <div class="menu-item-handle">
                <span class="item-title">
                    <span class="menu-item-title">{!! !empty($item->name) ? $item->name : '&nbsp;' !!}</span>
                    <span class="is-submenu" style="display: none;">{{ trans('locale.menus.child element') }}</span>
                </span>
                <span class="item-controls">
                    <span class="item-type">{{ $item->type == 'page' ? trans('locale.menus.Page') : ($item->type == 'category' ? trans('locale.menus.Product category') : ($item->type == 'group' ? trans('locale.menus.Link group') : trans('locale.menus.Arbitrary link'))) }}</span>
                    <a class="item-edit" id="edit-{{ $item->id }}" href="/ajax/edit-menu-item?menu-item={{ $item->id }}#menu-item-settings-{{ $item->id }}" aria-label="{{ trans('locale.menus.Edit menu item') }}">
                        <span class="screen-reader-text">{{ trans('locale.menus.Change') }}</span>
                    </a>
                </span>
            </div>
        </div>
        <div class="menu-item-settings wp-clearfix" id="menu-item-settings-{{ $item->id }}">
            @if($item->type == 'custom')
                <p class="field-url description description-wide">
                    <label for="edit-menu-item-url-{{ $item->id }}">
                        URL<br>
                        <input type="text" id="edit-menu-item-url-{{ $item->id }}" class="form-control form-control-sm widefat code edit-menu-item-url" name="menu-item-url[{{ $item->id }}]" value="{{ $item->value }}" autocomplete="off">
                    </label>
                </p>
            @endif
            <p class="description description-wide js_langs">
                @if(count($locales_names) > 1)
                    @foreach($locales_names as $locale => $locale_name)
                        <label for="edit-menu-item-title-{{ $locale }}-{{ $item->id }}" class="js_lang lng_{{ $locale }}{{ $locale == $main_lang ? ' active_lang' : '' }}">
                            {{ $item->type == 'group' ? trans('locale.menus.Group name') : trans('locale.menus.Link text') }} ({{ $locale_name }})<br/>
                            <input type="text" id="edit-menu-item-title-{{ $locale }}-{{ $item->id }}" class="form-control form-control-sm widefat edit-menu-item-title" name="menu-item-title_{{ $locale }}[{{ $item->id }}]" value="{{ $item->localize($locale, 'name') }}" autocomplete="off"/>
                        </label>
                    @endforeach
                @else
                    <label for="edit-menu-item-title-{{ $item->id }}">
                        {{ $item->type == 'group' ? trans('locale.menus.Group name') : trans('locale.menus.Link text') }}<br/>
                        <input type="text" id="edit-menu-item-title-{{ $item->id }}" class="form-control form-control-sm widefat edit-menu-item-title" name="menu-item-title[{{ $item->id }}]" value="{{ $item->name }}" autocomplete="off"/>
                    </label>
                @endif
            </p>
            @if($item->type == 'category')
                <p class="field-link-target description checkbox">
                    <input type="checkbox" id="edit-menu-item-with-children-{{ $item->id }}" value="_blank" name="menu-item-with-children[{{ $item->id }}]"{{ !empty($item->with_children) ? ' checked' : '' }} autocomplete="off"/>
                    <label for="edit-menu-item-with-children-{{ $item->id }}">
                        {{ trans('locale.menus.Pull up child categories') }}
                    </label>
                </p>
            @endif
            @if($item->type != 'group')
                <p class="field-title-attribute field-attr-title description description-wide js_langs">
                    @if(count($locales_names) > 1)
                        @foreach($locales_names as $locale => $locale_name)
                            <label for="edit-menu-item-target-{{ $locale }}-{{ $item->id }}" class="js_lang lng_{{ $locale }}{{ $locale == $main_lang ? ' active_lang' : '' }}">
                                {{ trans('locale.menus.Title attribute') }} ({{ $locale_name }})<br/>
                                <input type="text" id="edit-menu-item-attr-title-{{ $locale }}-{{ $item->id }}" class="form-control form-control-sm widefat edit-menu-item-title" name="menu-item-attr-title_{{ $locale }}[{{ $item->id }}]" value="{{ $item->localize($locale, 'title') }}" autocomplete="off"/>
                            </label>
                        @endforeach
                    @else
                        <label for="edit-menu-item-target-{{ $item->id }}">
                            {{ trans('locale.menus.Title attribute') }}<br/>
                            <input type="text" id="edit-menu-item-attr-title-{{ $item->id }}" class="form-control form-control-sm widefat edit-menu-item-title" name="menu-item-attr-title[{{ $item->id }}]" value="{{ $item->title }}" autocomplete="off"/>
                        </label>
                    @endif
                </p>
                <div class="field-link-target description checkbox" style="width: 100%;">
                    <input type="checkbox" id="edit-menu-item-target-{{ $item->id }}" value="_blank" name="menu-item-target[{{ $item->id }}]"{{ !empty($item->blank) ? ' checked' : '' }} autocomplete="off"/>
                    <label for="edit-menu-item-target-{{ $item->id }}">
                        {{ trans('locale.menus.Open in a new tab') }}
                    </label>
                </div>
            @endif
            <p class="field-css-classes description description-thin">
                <label for="edit-menu-item-classes-{{ $item->id }}">
                    {{ trans('locale.menus.CSS classes') }}<br/>
                    <input type="text" id="edit-menu-item-classes-{{ $item->id }}" class="form-control form-control-sm widefat code edit-menu-item-classes" name="menu-item-classes[{{ $item->id }}]" value="{{ $item->class }}" autocomplete="off"/>
                </label>
            </p>
            @if($item->type != 'group')
                <p class="field-xfn description description-thin">
                    <label for="edit-menu-item-xfn-{{ $item->id }}">
                        {{ trans('locale.menus.Link Relationship (XFN)') }}<br/>
                        <input type="text" id="edit-menu-item-xfn-{{ $item->id }}" class="form-control form-control-sm widefat code edit-menu-item-xfn" name="menu-item-xfn[{{ $item->id }}]" value="{{ $item->xfn }}" autocomplete="off"/>
                    </label>
                </p>
            @endif

            <p class="field-image image image-wide">
                <label for="edit-menu-item-image-{{ $item->id }}">{{ trans('locale.Image') }}</label>
                <br/>
            </p>
            @include('admin.layouts.form.image', [
             'key' => 'menu-item-image['.$item->id.']',
             'image' => $item->image
            ])

            <p class="field-description description description-wide js_langs">
                @if(count($locales_names) > 1)
                    @foreach($locales_names as $locale => $locale_name)
                        <label for="edit-menu-item-description-{{ $locale }}-{{ $item->id }}" class="js_lang lng_{{ $locale }}{{ $locale == $main_lang ? ' active_lang' : '' }}">
                            {{ trans('locale.Description') }} ({{ $locale_name }})<br/>
                            <textarea id="edit-menu-item-description-{{ $locale }}-{{ $item->id }}" class="form-control form-control-sm widefat edit-menu-item-description" rows="5" cols="20" name="menu-item-description_{{ $locale }}[{{ $item->id }}]" autocomplete="off">{{ $item->localize($locale, 'description') }}</textarea>
                            <span class="description">{{ trans('locale.menus.The description will be displayed in the menu if the current theme supports it') }}</span>
                        </label>
                    @endforeach
                @else
                    <label for="edit-menu-item-description-{{ $item->id }}">
                        {{ trans('locale.Description') }}<br/>
                        <textarea id="edit-menu-item-description-{{ $item->id }}" class="form-control form-control-sm widefat edit-menu-item-description" rows="5" cols="20" name="menu-item-description[{{ $item->id }}]" autocomplete="off">{{ $item->description }}</textarea>
                        <span class="description">{{ trans('locale.menus.The description will be displayed in the menu if the current theme supports it') }}</span>
                    </label>
                @endif
            </p>

            <fieldset class="field-move hide-if-no-js description description-wide">
                <span class="field-move-visual-label" aria-hidden="true">{{ trans('locale.menus.Move') }}</span>
                <button type="button" class="button-link menus-move menus-move-up" data-dir="up">{{ trans('locale.menus.Higher') }}</button>
                <button type="button" class="button-link menus-move menus-move-down" data-dir="down">{{ trans('locale.menus.Below') }}</button>
                <button type="button" class="button-link menus-move menus-move-left" data-dir="left"></button>
                <button type="button" class="button-link menus-move menus-move-right" data-dir="right"></button>
                <button type="button" class="button-link menus-move menus-move-top" data-dir="top">{{ trans('locale.menus.To the top') }}</button>
            </fieldset>

            <div class="menu-item-actions description-wide submitbox">
                @if(in_array($item->type, ['page', 'category']))
                    <p class="link-to-original">{{ trans('locale.menus.Original') }}: <a href="{{ $item->orig ? $item->orig->link() : '#' }}" target="_blank">{{ $item->orig ? $item->orig->name : '' }}</a></p>
                @endif
                <a class="item-delete submitdelete deletion" id="delete-{{ $item->id }}" href="/ajax/delete-menu-item?menu-item={{ $item->id }}">{{ trans('locale.Delete') }}</a>
                <span class="meta-sep hide-if-no-js"> | </span>
                <a class="item-cancel submitcancel hide-if-no-js" id="cancel-{{ $item->id }}" href="/ajax/edit-menu-item?menu-item={{ $item->id }}#menu-item-settings-{{ $item->id }}">{{ trans('locale.menus.Cancel') }}</a>
            </div>

            <input class="menu-item-data-db-id" type="hidden" name="menu-item-db-id[{{ $item->id }}]" value="{{ $item->id }}"/>
            <input class="menu-item-data-object-id" type="hidden" name="menu-item-object-id[{{ $item->id }}]" value="{{ in_array($item->type, ['page', 'category']) ? $item->value : '' }}"/>
            <input class="menu-item-data-object" type="hidden" name="menu-item-object[{{ $item->id }}]" value="{{ $item->type }}"/>
            <input class="menu-item-data-parent-id" type="hidden" name="menu-item-parent-id[{{ $item->id }}]" value="{{ !empty($item->parent_id) ? $item->parent_id : 0 }}"/>
            <input class="menu-item-data-position" type="hidden" name="menu-item-position[{{ $item->id }}]" value="{{ $item->position }}"/>
            <input class="menu-item-data-type" type="hidden" name="menu-item-type[{{ $item->id }}]" value="{{ $item->type == 'page' ? 'post_type' : ($item->type == 'category' ? 'taxonomy' : $item->type) }}"/>
        </div>
        <ul class="menu-item-transport"></ul>
    </li>
@endforeach
