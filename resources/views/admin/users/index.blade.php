@extends('layouts.contentLayoutMaster')
{{-- page title --}}
@section('title', trans('locale.Users'))
{{-- vendor styles --}}
@section('vendor-styles')
<link rel="stylesheet" type="text/css" href="{{asset('vendors/css/tables/datatable/datatables.min.css')}}">
@endsection
{{-- page styles --}}
@section('page-styles')
<link rel="stylesheet" type="text/css" href="{{asset('css/pages/page-users.css')}}">
@endsection
@section('content')
<h1 class="pages-title">{{ trans('locale.Users') }}</h1>
<section class="users-list-wrapper">
  <div class="users-list-filter px-1">
    <form>
      <div class="row border rounded py-2 mb-2">
        <div class="col-12 col-sm-6 col-lg-3">
          <label for="users-list-verified">{{ trans('locale.Verifieds') }}</label>
          <fieldset class="form-group">
            <select class="form-control" id="users-list-verified">
                <option value="">{{ trans('locale.All') }}</option>
                <option value="1">{{ trans('locale.Yes') }}</option>
                <option value="0">{{ trans('locale.No') }}</option>
            </select>
          </fieldset>
        </div>
        <div class="col-12 col-sm-6 col-lg-3">
          <label for="users-list-role">{{ trans('locale.Role') }}</label>
          <fieldset class="form-group">
            <select name="role" class="form-control" id="users-list-role">
              <option value="">{{ trans('locale.All') }}</option>
              @foreach($roles as $role)
              <option value="{{ $role->id }}">{{ $role->name }}</option>
              @endforeach
            </select>
          </fieldset>
        </div>
        <div class="col-12 col-sm-6 col-lg-3">
          <label for="users-list-status">{{ trans('locale.Status') }}</label>
          <fieldset class="form-group">
            <select class="form-control" id="users-list-status">
              <option value="">{{ trans('locale.All') }}</option>
              <option value="1">{{ trans('locale.Active') }}</option>
              <option value="0">{{ trans('locale.Banned') }}</option>
            </select>
          </fieldset>
        </div>
        <div class="col-12 col-sm-6 col-lg-3 d-flex align-items-center">
          <button type="reset" class="btn btn-primary btn-block glow users-list-clear mb-0">{{ trans('locale.Clear') }}</button>
        </div>
      </div>
    </form>
  </div>
  <div class="users-list-table">
    <div class="card">
      <div class="card-content">
        <div class="card-body">
          <div class="table-responsive">
            <table id="users-list-datatable" class="table">
              <thead>
                <tr>
                    <th>ID</th>
                    <th>{{ trans('locale.Name') }}</th>
                    <th>Email</th>
                    <th>{{ trans('locale.Was on the site') }}</th>
                    <th>{{ trans('locale.Verified') }}</th>
                    <th>{{ trans('locale.Role') }}</th>
                    <th>{{ trans('locale.Status') }}</th>
                    <th>{{ trans('locale.Actions') }}</th>
                </tr>
              </thead>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
@endsection

{{-- vendor scripts --}}
@section('vendor-scripts')
<script src="{{asset('vendors/js/tables/datatable/datatables.min.js')}}"></script>
<script src="{{asset('vendors/js/tables/datatable/dataTables.bootstrap4.min.js')}}"></script>
@endsection

{{-- page scripts --}}
@section('page-scripts')
    <script>window.localization = {!! $localization !!}</script>
    <script src="{{asset('js/admin/users.js')}}"></script>
@endsection
