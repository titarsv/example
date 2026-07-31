<form action="" method="post" id="js_genetate_ttn_form" class="genetate_ttn_form">
    {!! csrf_field() !!}
    <input type="hidden" name="order_id" value="{{ $order->id }}">
    <div class="row">
        <div class="col-xs-12">
            <label for="payer" class="control-label">{{ trans('locale.Payer') }}</label>
            <select class="form-control" id="payer" name="payer">
                <option value="0"{{ $order->total_price >= 8000 ? ' selected' : '' }}>{{ trans('locale.Sender') }}</option>
                <option value="1"{{ $order->total_price < 8000 ? ' selected' : '' }}>{{ trans('locale.Recipient') }}</option>
            </select>
        </div>
    </div>
    <div class="row">
        <div class="col-xs-6">
            <label for="last_name" class="control-label">{{ trans('locale.Last name') }}</label>
            <input class="form-control" id="last_name" name="last_name" value="{{ $order->last_name }}" type="text">
        </div>
        <div class="col-xs-6">
            <label for="first_name" class="control-label">{{ trans('locale.First name') }}</label>
            <input class="form-control" id="first_name" name="first_name" value="{{ $order->first_name }}" type="text">
        </div>
    </div>
    <div class="row">
        <div class="col-xs-6">
            <label for="patronymic" class="control-label">{{ trans('locale.Middle name') }}</label>
            <input class="form-control" id="patronymic" name="patronymic" value="{{ $order->patronymic }}" type="text">
        </div>
        <div class="col-xs-6">
            <label for="phone" class="control-label">{{ trans('locale.Phone') }}</label>
            <input class="form-control" id="phone" name="phone" value="{{ str_replace(['-', '(', ')', ' '], '', $order->phone) }}" type="text">
        </div>
    </div>
    <div class="row hidden">
        <div class="col-xs-12">
            <label for="service_type" class="control-label">{{ trans('locale.Receiving method') }}</label>
            <select class="form-control" id="service_type" name="service_type">
                <option value="WarehouseWarehouse">{{ trans('locale.At the branch') }}</option>
            </select>
        </div>
    </div>
    @if(empty($city_id))
        <div class="row">
            <div class="col-xs-12">
                <label for="region" class="control-label">{{ trans('locale.Sending region') }}</label>
                <select class="form-control" id="region" name="region">
                    <option value="">-- {{ trans('locale.Not selected') }} --</option>
                    @foreach($regions as $region)
                        <option value="{{ $region->id }}">{{ $region->name_ru }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    @endif
    @if(empty($warehouse_id))
    <div class="row">
        <div class="col-xs-12">
            <label for="city" class="control-label">{{ trans('locale.Sending city') }}</label>
            <select class="form-control" id="city" name="city">
                <option value="">-- {{ trans('locale.Not selected') }} --</option>
                @foreach($cities as $city)
                    <option value="{{ $city->city_id }}"{{ $city_id == $city->city_id ? ' selected' : '' }}>{{ $city->name_ru }}</option>
                @endforeach
            </select>
        </div>
    </div>
    @endif
    <div class="row">
        <div class="col-xs-12">
            <label for="warehouse" class="control-label">{{ trans('locale.Sending place') }}</label>
            <select class="form-control" id="warehouse" name="warehouse">
                <option value="">-- {{ trans('locale.Not selected') }} --</option>
                @foreach($warehouses as $wid => $warehouse)
                    <option value="{{ $warehouse['branch'] }}"{{ $warehouse_id == $wid ? ' selected' : '' }}>{{ $warehouse['name'] }}</option>
                @endforeach
            </select>
        </div>
    </div>
    @if(!empty($destination['warehouse']))
    <div class="row">
        <div class="col-xs-12">
            <label for="destination_warehouse" class="control-label">{{ trans('locale.Receiving place') }}</label>
            <select class="form-control" id="destination_warehouse" name="destination_warehouse">
                <option value="">-- {{ trans('locale.Not selected') }} --</option>
                @foreach($destination['warehouse']['options'] as $option)
                    <option value="{{ $option->branch }}"{{ $option->id == $destination['warehouse']['selected'] ? ' selected' : '' }}>{{ $option->address_ru }}</option>
                @endforeach
            </select>
        </div>
    </div>
    @endif
    <div class="row">
        <div class="col-xs-12">
            <label for="cargo_type">{{ trans('locale.Parcel type') }}</label>
            <select class="form-control" id="cargo_type" name="cargo_type" autocomplete="off">
                <option value="Parcel">{{ trans('locale.Parcel') }}</option>
                <option value="Cargo">{{ trans('locale.Cargo') }}</option>
                <option value="TiresWheels">{{ trans('locale.Tires-wheels') }}</option>
                <option value="Pallet">{{ trans('locale.Pallets') }}</option>
            </select>
        </div>
    </div>
    <div class="row">
        <div class="col-xs-6">
            <label for="cost" class="control-label">{{ trans('locale.Estimated cost') }}</label>
            <input class="form-control" id="cost" name="cost" value="{{ $order->total_price }}" type="text">
        </div>
        <div class="col-xs-6">
            <label for="date_time" class="control-label">{{ trans('locale.Shipping date') }}</label>
            <input class="form-control" id="date_time" name="date_time" value="{{ date('d.m.Y', time() + 86400) }}" type="text">
        </div>
    </div>
    <div class="row">
        <div class="col-xs-12">
            <label for="description" class="control-label">{{ trans('locale.Full description') }}</label>
            <textarea class="form-control" name="description" id="description" cols="30" rows="3">{{ trans('locale.Order on the site') }} {{ env('APP_URL') }} №{{ $order->id }}</textarea>
        </div>
    </div>
    <div class="row">
        <div class="col-xs-12">
            <div class="checkbox">
                <label for="backward_delivery" style="float: left;"><input id="backward_delivery" name="backward_delivery" value="1" type="checkbox"> {{ trans('locale.Order return delivery') }}</label>
            </div>
        </div>
    </div>
    <div class="row hidden" id="redelivery">
        <div class="col-xs-6">
            <label for="redelivery_string" class="control-label">{{ trans('locale.Return delivery amount') }}</label>
            <input class="form-control" id="redelivery_string" name="redelivery_string" value="{{ $order->total_price }}" type="text">
        </div>
        <div class="col-xs-6">
            <label for="payer_type" class="control-label">{{ trans('locale.Payer for return delivery') }}</label>
            <select class="form-control" id="payer_type" name="payer_type">
                <option value="0"{{ $order->total_price >= 8000 ? ' selected' : '' }}>{{ trans('locale.Sender') }}</option>
                <option value="1"{{ $order->total_price < 8000 ? ' selected' : '' }}>{{ trans('locale.Recipient') }}</option>
            </select>
        </div>
    </div>
    <div class="row js_np_place">
        <div class="col-sm-8">
            <div class="row">
                <div class="col-sm-3">
                    <label for="place_0_weight" class="control-label">{{ trans('locale.Weight, kg') }}</label>
                    <input class="form-control" id="place_0_weight" name="place[0][weight]" value="1" type="text">
                </div>
                <div class="col-sm-3">
                    <label for="place_0_volumetric_length" class="control-label">{{ trans('locale.Length, cm') }}</label>
                    <input class="form-control js_volumetric_length" id="place_0_volumetric_length" name="place[0][volumetric_length]" value="5" type="text">
                </div>
                <div class="col-sm-3">
                    <label for="place_0_volumetric_width" class="control-label">{{ trans('locale.Width, cm') }}</label>
                    <input class="form-control js_volumetric_width" id="place_0_volumetric_width" name="place[0][volumetric_width]" value="5" type="text">
                </div>
                <div class="col-sm-3">
                    <label for="place_0_volumetric_height" class="control-label">{{ trans('locale.Height, cm') }}</label>
                    <input class="form-control js_volumetric_height" id="place_0_volumetric_height" name="place[0][volumetric_height]" value="5" type="text">
                </div>
            </div>
        </div>
        <div class="col-sm-3">
            <label for="place_0_volume_general">{{ trans('locale.Volumetric weight') }}</label>
            <input class="form-control js_place_result" id="place_0_volume_general" name="place[0][volume_general]" value="0.1" type="text" readonly>
        </div>
    </div>
    <div class="row">
        <div class="col-xs-12">
            <span id="js_add_delivery_place" data-iterator="1">+ {{ trans('locale.Add place') }}</span>
        </div>
    </div>
</form>
