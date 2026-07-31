<form action="/admin/orders/create" method="post" id="create_form">
    {!! csrf_field() !!}
    <div class="panel-group">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h4>{{ trans('locale.Order status') }}</h4>
            </div>
            <div class="panel-body">
                <div class="form-group">
                    <div class="row">
                        @if($user->hasAccess(['orders.update']))
                            <div class="form-element col-sm-4">
                                <select name="status" class="form-control">
                                    @foreach($orders_statuses as $status)
                                        <option value="{{ $status->id }}">{{ $status->status }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-element col-sm-8 text-right">
                                <button type="submit" class="btn btn-primary">{{ trans('locale.Save changes') }}</button>
                                <a href="/admin/orders" class="btn btn-info">{{ trans('locale.Previous') }}</a>
                            </div>
                        @else
                            <div class="form-element col-sm-4">
                                <select name="status" class="form-control">
                                    @foreach($orders_statuses as $status)
                                        <option value="{{ $status->id }}">{{ $status->status }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="panel panel-default">
            <div class="table table-responsive">
                <table class="table table-hover">
                    <thead>
                    <tr class="success">
                        <td align="center">{{ trans('locale.SKU') }}</td>
                        <td>{{ trans('locale.Image') }}</td>
                        <td>{{ trans('locale.Title') }}</td>
                        <td>{{ trans('locale.Availability') }}</td>
                        <td>{{ trans('locale.Quantity') }}</td>
                        <td align="center">{{ trans('locale.Amount') }}</td>
                        <td align="center">{{ trans('locale.Action') }}</td>
                    </tr>
                    </thead>
                    <tbody id="products_table">
                    </tbody>
                    <tfoot>
                    <tr>
                        <td colspan="3">
                            @if($user->hasAccess(['orders.update']))
                            <button type="button" class="btn btn-primary" id="add_to_order">{{ trans('locale.Add product to order') }}</button>
                            @endif
                        </td>
                        <td></td>
                        <td class="right">{{ trans('locale.Total') }}:</td>
                        <td>0 {{ trans('locale.pcs') }}</td>
                        <td align="center">0 {{ Helper::getDefaultCurrencySymbol() }}</td>
                    </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <div class="panel panel-default">
            <div class="panel-heading">
                <h4>{{ trans('locale.Order information') }}</h4>
            </div>
            <div class="panel-body">
                <div class="row">
                    <div class="col-sm-6">
                        <div class="table table-responsive">
                            <table class="table table-hover">
                                <thead>
                                <tr>
                                    <td colspan="2" class="colspan">
                                        {{ trans('locale.Customer') }}
                                    </td>
                                </tr>
                                </thead>
                                <tr>
                                    <td>{{ trans('locale.Customer') }} *</td>
                                    <td>
                                        <input  class="form-control" type="text" name="user_name" value="">
                                        @if($errors->has('user_name'))
                                            <p class="warning" role="alert">{{ $errors->first('user_name',':message') }}</p>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td>{{ trans('locale.Phone') }} *</td>
                                    <td>
                                        <input  class="form-control" type="text" name="user_phone" value="">
                                        @if($errors->has('user_name'))
                                            <p class="warning" role="alert">{{ $errors->first('user_phone',':message') }}</p>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td>{{ trans('locale.Email') }} *</td>
                                    <td>
                                        <input  class="form-control" type="email" name="user_email" value="">
                                        @if($errors->has('user_name'))
                                            <p class="warning" role="alert">{{ $errors->first('user_email',':message') }}</p>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td>{{ trans('locale.Order comment') }}</td>
                                    <td>
                                        <textarea class="form-control" name="comment" cols="30" rows="5"></textarea>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="table table-responsive">
                            <table class="table table-hover">
                                <thead>
                                <tr>
                                    <td colspan="2" class="colspan">
                                        {{ trans('locale.Delivery and payment') }}
                                    </td>
                                </tr>
                                </thead>
                                <tr>
                                    <td>{{ trans('locale.Delivery method') }}</td>
                                    <td>
                                        <select id="js_delivery_method" class="form-control" name="delivery" autocomplete="off">
                                            <option value="pickup" selected>{{ trans('locale.Pickup') }}</option>
                                            <option value="newpost">{{ trans('locale.Nova Poshta') }}</option>
                                            <option value="justin">Justin</option>
                                            <option value="courier">{{ trans('locale.Courier delivery in Severodonetsk') }}</option>
                                            <option value="other">{{ trans('locale.Other delivery service') }}</option>
                                        </select>
                                    </td>
                                </tr>
                                <tr>
                                    <td>{{ trans('locale.Payment method') }}</td>
                                    <td>
                                        <select class="form-control" name="payment" autocomplete="off">
                                            <option value="cash" selected>{{ trans('locale.Prepayment from 50 GBP to the card (The rest COD)') }}</option>
                                            <option value="online">{{ trans('locale.Online payment') }}</option>
                                            <option value="card">{{ trans('locale.Payment to card') }}</option>
                                        </select>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="panel panel-default">
            <div class="panel-heading">
                <h4>{{ trans('locale.Settings') }}</h4>
            </div>
            <div class="panel-body">
                <div class="form-group">
                    <div class="row">
                        <label class="col-sm-2 text-right">{{ trans('locale.Notes') }}</label>
                        <div class="form-element col-sm-10">
                            <textarea name="notes" class="form-control" rows="6"></textarea>
                        </div>
                    </div>
                </div>
                @if($user->hasAccess(['orders.update']))
                <div class="form-group">
                    <div class="row">
                        <div class="col-sm-10 col-sm-push-2 text-left">
                            <button type="submit" class="btn btn-primary">{{ trans('locale.Save') }}</button>
                            <a href="/admin/orders" class="btn btn-info">{{ trans('locale.Back') }}</a>
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</form>
