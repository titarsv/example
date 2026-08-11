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
        <h1 class="h3 mb-4">{{ $seo->name }}</h1>

        <div class="row g-4">
            <div class="col-lg-6">
                <form class="ajax_form" action="{{ base_url('/sendmail') }}" method="POST"
                      data-error-title="Ошибка отправки!"
                      data-success-title="Спасибо за обращение!"
                      data-success-message="Наш менеджер свяжется с вами в ближайшее время.">
                    @csrf
                    <input type="hidden" name="form" value="Contact form">
                    <div class="mb-3">
                        <label class="form-label">Имя</label>
                        <input class="form-control" type="text" name="name" placeholder="Ваше имя" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input class="form-control" type="email" name="email" placeholder="client@mail.com" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Сообщение</label>
                        <textarea class="form-control" name="message" rows="5" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Отправить</button>
                </form>
            </div>
            <div class="col-lg-6">
                <img src="/images/contacts-bg.jpg" loading="lazy" alt="" class="img-fluid rounded">
            </div>
        </div>
    </div>
@endsection
