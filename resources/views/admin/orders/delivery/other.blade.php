<tr class="delivery">
    <td>{{ trans('locale.Delivery service') }}</td>
    <td><input name="name" class="form-control" value="{{ !empty($name) ? $name : '' }}"></td>
</tr>
<tr class="delivery">
    <td>{{ trans('locale.Region') }}</td>
    <td><input name="region" class="form-control" value="{{ !empty($region) ? $region : '' }}"></td>
</tr>
<tr class="delivery">
    <td>{{ trans('locale.City') }}</td>
    <td><input name="city" class="form-control" value="{{ !empty($city) ? $city : '' }}"></td>
</tr>
<tr class="delivery">
    <td>{{ trans('locale.Post office branch') }}</td>
    <td><input name="warehouse" class="form-control" value="{{ !empty($warehouse) ? $warehouse : '' }}"></td>
</tr>
