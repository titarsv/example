@extends('layouts.contentLayoutMaster')
{{-- page title --}}
@section('title', trans('User Profile'))
{{-- page styles --}}
@section('page-styles')
<link rel="stylesheet" type="text/css" href="{{asset('css/pages/page-users.css')}}">
@endsection
@section('content')
<!-- users view start -->
<section class="users-view">
  <!-- users view media object start -->
  <div class="row">
    <div class="col-12 col-sm-7">
      <div class="media mb-2">
        <a class="mr-1" href="#">
          <img src="{{asset('images/portrait/small/avatar-s-26.jpg')}}" alt="users view avatar"
            class="users-avatar-shadow rounded-circle" height="64" width="64">
        </a>
        <div class="media-body pt-25">
          <h4 class="media-heading"><span class="users-view-name">{{ $user->name }} </span><span
              class="text-muted font-medium-1"> - </span><span
              class="users-view-username text-muted font-medium-1">{{ $user->email }}</span></h4>
          <span>{{ trans('locale.ID') }}:</span>
          <span class="users-view-id">{{ $user->id }}</span>
        </div>
      </div>
    </div>
    <div class="col-12 col-sm-5 px-0 d-flex justify-content-end align-items-center px-1 mb-2">
      <a href="{{asset('admin/users/orders/'.$user->id)}}" class="btn btn-sm mr-25 border">{{ trans('Orders') }}</a>
      @if(module_active('wishlist'))
      <a href="{{asset('admin/users/wishlist/'.$user->id)}}" class="btn btn-sm mr-25 border">{{ trans('Favorites') }}</a>
      @endif
      <a href="{{asset('admin/users/edit/'.$user->id)}}" class="btn btn-sm btn-primary">{{ trans('Edit') }}</a>
    </div>
  </div>
  <!-- users view media object ends -->
  <!-- users view card data start -->
  <div class="card">
    <div class="card-content">
      <div class="card-body">
        <div class="row">
          <div class="col-12 col-md-4">
            <table class="table table-borderless">
              <tbody>
                <tr>
                  <td>{{ trans('Role') }}:</td>
                  <td class="users-view-role">{{ $user->role }}</td>
                </tr>
                <tr>
                  <td>{{ trans('Registered') }}:</td>
                  <td>{{ $user->created_at->format('d/m/Y') }}</td>
                </tr>
                <tr>
                  <td>{{ trans('Last activity') }}:</td>
                  <td class="users-view-latest-activity">{{ !empty($user->activity) ? $user->activity->format('d/m/Y') : $user->created_at->format('d/m/Y') }}</td>
                </tr>
                <tr>
                  <td>{{ trans('Verified') }}:</td>
                  <td class="users-view-verified">
                    @if($user->verified)
                      <span class="badge badge-light-success users-view-status">{{ trans('Yes') }}</span>
                    @else
                      <span class="badge badge-light-danger users-view-status">{{ trans('No') }}</span>
                    @endif
                  </td>
                </tr>
                <tr>
                  <td>{{ trans('Status') }}:</td>
                  <td>
                    @if($user->status)
                      <span class="badge badge-light-success users-view-status">{{ trans('Activ') }}</span>
                    @else
                      <span class="badge badge-light-danger users-view-status">{{ trans('Ban') }}</span>
                    @endif
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <div class="col-12 col-md-8">
            <div class="table-responsive">
              <table class="table mb-0">
                <thead>
                  <tr>
                    <th>{{ trans('Modules') }}</th>
                    <th>{{ trans('View') }}</th>
                    <th>{{ trans('Update') }}</th>
                    <th>{{ trans('Create') }}</th>
                    <th>{{ trans('Delete') }}</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($modules as $module)
                  <tr>
                    <td>{{ $module->name }}</td>
                    @foreach(['read', 'write', 'create', 'delete'] as $type)
                    <td>{{ isset($module->permissions->{$type}) ? ($module->permissions->{$type} ? trans('Yes') : trans('No')) : '' }}</td>
                    @endforeach
                  </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <!-- users view card data ends -->
  <!-- users view card details start -->
  <div class="card">
    <div class="card-content">
      <div class="card-body">
        <div class="row bg-primary bg-lighten-5 rounded mb-2 mx-25 text-center text-lg-left">
          <div class="col-12 col-sm-4 p-2">
            <h6 class="text-primary mb-0">{{ trans('Views') }}: <span class="font-large-1 align-middle">{{ $user->views }}</span></h6>
          </div>
          <div class="col-12 col-sm-4 p-2">
            <h6 class="text-primary mb-0">{{ trans('Orders') }}: <span class="font-large-1 align-middle">{{ $user->orders_count }}</span></h6>
          </div>
          <div class="col-12 col-sm-4 p-2">
            <h6 class="text-primary mb-0">{{ trans('Comments') }}: <span class="font-large-1 align-middle">{{ $user->reviews_count + $user->shopreviews_count }}</span></h6>
          </div>
        </div>
        <div class="col-12">
          <table class="table table-borderless">
            <tbody>
              <tr>
                <td>{{ trans('Name') }}:</td>
                <td class="users-view-name">{{ $user->name }}</td>
              </tr>
              <tr>
                <td>{{ trans('Email') }}:</td>
                <td class="users-view-email">{{ $user->email }}</td>
              </tr>
              @if(!empty($user->company))
              <tr>
                <td>{{ trans('Company') }}:</td>
                <td>{{ $user->company }}</td>
              </tr>
              @endif
            </tbody>
          </table>
          @if(!empty($user->socials))
          <h5 class="mb-1"><i class="bx bx-link"></i> {{ trans('Social networks and messengers') }}</h5>
          <table class="table table-borderless">
            <tbody>
              @foreach($user->socials as $social => $link)
              <tr>
                <td>{{ $social }}:</td>
                <td><a href="{{ $link }}" target="_blank">{{ $link }}</a></td>
              </tr>
              @endforeach
            </tbody>
          </table>
          @endif
          <h5 class="mb-1"><i class="bx bx-info-circle"></i> {{ trans('Personal data') }}</h5>
          <table class="table table-borderless mb-0">
            <tbody>
              @if(!empty($user->user_birth))
              <tr>
                <td>{{ trans('Birthday') }}:</td>
                <td>{{ date('d/m/Y', strtotime($user->user_birth)) }}</td>
              </tr>
              @endif
              @if(!empty($user->city))
              <tr>
                <td>{{ trans('City') }}:</td>
                <td>{{ $user->city }}</td>
              </tr>
              @endif
              <tr>
                <td>{{ trans('Language') }}:</td>
                <td>{{ $user->language_name }}</td>
              </tr>
              @if(!empty($user->phone))
              <tr>
                <td>{{ trans('locale.Phone') }}:</td>
                <td>{{ $user->phone }}</td>
              </tr>
              @endif
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
  <!-- users view card details ends -->

</section>
<!-- users view ends -->
@endsection
{{-- page scripts --}}
@section('page-scripts')
<script src="{{asset('js/admin/users.js')}}"></script>
@endsection
