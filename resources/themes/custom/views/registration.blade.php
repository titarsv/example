@extends('public.layouts.main')
@section('meta')
    <title>Регистрация | {{ env('APP_NAME') }}</title>
@endsection

@section('content')
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <h1 class="h3 mb-4 text-center">Регистрация</h1>

                @if($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0 ps-3">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="card">
                    <div class="card-body">
                        <form method="POST" action="{{ base_url('/register') }}">
                            @csrf
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Имя *</label>
                                    <input class="form-control" type="text" name="first_name" value="{{ old('first_name') }}" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Фамилия</label>
                                    <input class="form-control" type="text" name="last_name" value="{{ old('last_name') }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Email *</label>
                                    <input class="form-control" type="email" name="email" value="{{ old('email') }}" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Телефон *</label>
                                    <input class="form-control" type="tel" name="phone" value="{{ old('phone') }}" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Пароль *</label>
                                    <input class="form-control" type="password" name="password" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Повторите пароль *</label>
                                    <input class="form-control" type="password" name="password_confirmation" required>
                                </div>
                            </div>
                            <div class="form-check my-3">
                                <input type="checkbox" class="form-check-input" name="subscribe" id="subscribe" value="1">
                                <label class="form-check-label" for="subscribe">Получать новости и специальные предложения на email</label>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Зарегистрироваться</button>
                        </form>
                    </div>
                </div>

                <p class="text-center mt-3">
                    Уже есть аккаунт? <a href="{{ base_url('/login') }}">Войти</a>
                </p>
            </div>
        </div>
    </div>
@endsection
