<tr class="delivery">
    <td>{{ trans('locale.Street') }}</td>
    <td><input name="street" class="form-control" value="{{ !empty($street) ? $street : '' }}"></td>
</tr>
<tr class="delivery">
    <td>{{ trans('locale.House') }}</td>
    <td><input name="house" class="form-control" value="{{ !empty($house) ? $house : '' }}"></td>
</tr>
<tr class="delivery">
    <td>{{ trans('locale.Apartment') }}</td>
    <td><input name="apartment" class="form-control" value="{{ !empty($apartment) ? $apartment : '' }}"></td>
</tr>
