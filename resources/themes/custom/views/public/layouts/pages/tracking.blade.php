@extends('public.layouts.main')
@section('page_vars')
    @include('public.layouts.microdata.open_graph', [
     'title' => $seo->meta_title,
     'description' => $seo->meta_description,
     'image' => theme_asset('images/favicon.png')
     ])
@endsection

@section('content')
    <div class="container py-4">
        <div class="mb-3">{!! Breadcrumbs::render('page', $page) !!}</div>
        <h1 class="h3 mb-3">Отследить заказ</h1>

        <div class="row">
            <div class="col-lg-6">
                <p class="text-muted">{{ $seo->description }}</p>
                <form id="js_track_order">
                    <div class="mb-3">
                        <label class="form-label" for="orderid">Номер заказа</label>
                        <input class="form-control" id="orderid" name="order_id" type="text" placeholder="1679" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="trackEmail">Email заказа</label>
                        <input class="form-control" id="trackEmail" type="email" name="email" placeholder="client@mail.com" required>
                    </div>
                    <div class="alert alert-danger d-none js-track-error"></div>
                    <button type="submit" class="btn btn-primary">Отследить</button>
                </form>
            </div>
        </div>

        <div class="js-track-result mt-4"></div>
    </div>
@endsection
