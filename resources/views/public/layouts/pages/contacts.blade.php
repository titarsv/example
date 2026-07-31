@extends('public.layouts.main')
@section('page_vars')
    @include('public.layouts.microdata.open_graph', [
     'title' => $seo->meta_title,
     'description' => $seo->meta_description,
     'image' => '/images/logo.png'
     ])
@endsection

@section('content')
    <div class="section page-contacts">
        <div class="page-top">
            <div class="container">
                {!! Breadcrumbs::render('page', $page) !!}
                <h1 class="page-title">{{ $seo->name }}</h1>
            </div>
        </div>
        <div class="contacts-main">
            <div class="contacts-left">
                <form class="contacts-form ajax_form clear-styles" id="contacts-form"
                      data-error-title="Sending Error!"
                      data-error-message="Please try again later."
                      data-success-title="Thank you for contacting us."
                      data-success-message="Our manager will contact you shortly."
                      data-success-btn="Ok" novalidate>
                    <div class="form-row">
                        <div class="input-wrapper">
                            <label>Name</label>
                            <input class="input" type="text" name="name" placeholder="Your Name"
                                   data-title="Name"
                                   data-validate-required="Required field">
                        </div>
                        <div class="input-wrapper">
                            <label>Email address</label>
                            <input class="input" type="email" name="email" placeholder="client@mail.com"
                                   data-title="Email address"
                                   data-validate-required="Required field"
                                   data-validate-email="Incorrect email">
                        </div>
                    </div>
                    <div class="input-wrapper">
                        <label>Message</label>
                        <textarea class="textarea" data-title="Message" name="message"></textarea>
                    </div>
                    <button type="submit" class="btn"
                            data-sending="Sending..."
                            data-error="Error :("
                            data-sent="Sent">Submit</button>
                </form>
            </div>
            <div class="contacts-right">
                <img src="/images/contacts-bg.jpg" loading="lazy" alt="" class="contacts-bg">
                <span>Get in Touch</span>
                <img src="/images/logo-color.png" loading="lazy" alt="" class="contacts-logo">
            </div>
        </div>
    </div>
@endsection
