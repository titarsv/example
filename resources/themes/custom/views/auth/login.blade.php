@extends('public.layouts.main')
@section('meta')
    <title>Вход | {{ env('APP_NAME') }}</title>
@endsection

@section('content')
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <h1 class="h3 mb-4 text-center">Вход</h1>

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
                        <form method="POST" action="/login">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input class="form-control" type="email" name="email" value="{{ old('email') }}" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Пароль</label>
                                <input class="form-control" type="password" name="password" required>
                            </div>
                            <div class="form-check mb-3">
                                <input type="checkbox" class="form-check-input" name="remember_me" id="remember_me" value="1">
                                <label class="form-check-label" for="remember_me">Запомнить меня</label>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Войти</button>
                        </form>
                    </div>
                </div>

                <p class="text-center mt-3">
                    Нет аккаунта? <a href="{{ base_url('/register') }}">Зарегистрироваться</a>
                </p>
            </div>
        </div>
    </div>
@endsection
