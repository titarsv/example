@extends('layouts.fullLayoutMaster', ['pageConfigs' => ['bodyCustomClass'=> 'bg-full-screen-image']])
{{-- page title --}}
@section('title','Maintenance')

@section('content')
    <!-- maintenance start -->
    <section class="row flexbox-container">
        <div class="col-xl-7 col-md-8 col-12">
            <div class="card bg-transparent shadow-none">
                <div class="card-content">
                    <div class="card-body text-center bg-transparent miscellaneous">
                        <img src="{{asset('images/pages/maintenance-2.png')}}" class="img-fluid" alt="under maintenance"
                             width="400">
                        <h1 class="error-title my-1">Under Maintenance!</h1>
                        <p class="px-2">
                            {message}
                        </p>
                        <a href="{{asset('/')}}" class="btn btn-primary round glow mt-2">BACK TO HOME</a>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- maintenance end -->
@endsection
