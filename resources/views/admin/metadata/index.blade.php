@extends('admin.layouts.contentLayoutMaster')

@section('title', trans('locale.Metadata Generation'))

@section('content')
    <h1 class="pages-title">{{ trans('locale.Metadata Generation') }}</h1>
    
    <div class="card">
        <div class="card-content">
            <div class="card-body">
                <!-- Statistics Section -->
                <div class="row mb-2">
                    <div class="col-12">
                        <div class="alert alert-info" role="alert">
                            <h5 class="alert-heading">{{ trans('locale.Statistics') }}</h5>
                            <div class="row">
                                <div class="col-md-3">
                                    <strong>{{ trans('locale.Total Images') }}:</strong> {{ $stats['total_images'] }}
                                </div>
                                <div class="col-md-3">
                                    <strong>{{ trans('locale.Processed Images') }}:</strong> {{ $stats['processed_images'] }}
                                </div>
                                <div class="col-md-3">
                                    <strong>{{ trans('locale.Pending Images') }}:</strong> {{ $stats['pending_images'] }}
                                </div>
                                <div class="col-md-3">
                                    <strong>{{ trans('locale.Progress') }}:</strong> {{ $stats['progress_percentage'] }}%
                                </div>
                            </div>
                            <div class="progress mt-2">
                                <div class="progress-bar" role="progressbar" 
                                     style="width: {{ $stats['progress_percentage'] }}%"
                                     aria-valuenow="{{ $stats['progress_percentage'] }}" 
                                     aria-valuemin="0" 
                                     aria-valuemax="100">
                                    {{ $stats['progress_percentage'] }}%
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Control Panel -->
                <div class="row">
                    <div class="col-12">
                        <div class="form-group">
                            <label for="limit">{{ trans('locale.Images to Process') }}:</label>
                            <div class="input-group">
                                <input type="number" 
                                       class="form-control" 
                                       id="limit" 
                                       name="limit" 
                                       value="100" 
                                       min="1" 
                                       max="1000"
                                       placeholder="{{ trans('locale.Enter number of images') }}">
                                <div class="input-group-append">
                                    <button type="button" 
                                            class="btn btn-primary generate-btn"
                                            id="generateBtn">
                                        <span class="spinner-border spinner-border-sm hidden" role="status" aria-hidden="true"></span>
                                        {{ trans('locale.Generate Metadata') }}
                                    </button>
                                </div>
                            </div>
                            <small class="form-text text-muted">
                                {{ trans('locale.Maximum 1000 images per batch') }}
                            </small>
                        </div>
                    </div>
                </div>

                <!-- Queue Status -->
                <div class="row mt-2">
                    <div class="col-12">
                        <div class="alert alert-secondary" id="queueStatus">
                            <h6>{{ trans('locale.Queue Status') }}</h6>
                            <div class="row">
                                <div class="col-md-4">
                                    <strong>{{ trans('locale.Queue Size') }}:</strong> 
                                    <span id="queueSize">-</span>
                                </div>
                                <div class="col-md-4">
                                    <strong>{{ trans('locale.Active Workers') }}:</strong> 
                                    <span id="activeWorkers">-</span>
                                </div>
                                <div class="col-md-4">
                                    <strong>{{ trans('locale.Status') }}:</strong> 
                                    <span id="queueStatusText" class="badge badge-secondary">-</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Results Section -->
                <div class="row mt-2" id="resultsSection" style="display: none;">
                    <div class="col-12">
                        <div class="alert alert-success" id="successAlert" style="display: none;">
                            <h6>{{ trans('locale.Success') }}</h6>
                            <div id="successMessage"></div>
                        </div>
                        <div class="alert alert-danger" id="errorAlert" style="display: none;">
                            <h6>{{ trans('locale.Errors') }}</h6>
                            <div id="errorMessage"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('page-scripts')
<script>
$(document).ready(function() {
    // Функция обновления статистики
    function updateStats() {
        $.get('/admin/metadata/progress', function(data) {
            if (data.stats) {
                $('.alert-info .col-md-3:nth-child(1) strong').next().text(data.stats.total_images);
                $('.alert-info .col-md-3:nth-child(2) strong').next().text(data.stats.processed_images);
                $('.alert-info .col-md-3:nth-child(3) strong').next().text(data.stats.pending_images);
                $('.alert-info .col-md-3:nth-child(4) strong').next().text(data.stats.progress_percentage + '%');
                
                $('.progress-bar').css('width', data.stats.progress_percentage + '%')
                    .attr('aria-valuenow', data.stats.progress_percentage)
                    .text(data.stats.progress_percentage + '%');
            }
            
            if (data.queue_stats) {
                $('#queueSize').text(data.queue_stats.queue_size);
                $('#activeWorkers').text(data.queue_stats.active_workers);
                
                var statusBadge = $('#queueStatusText');
                statusBadge.removeClass('badge-success badge-warning badge-secondary badge-danger')
                    .addClass('badge-' + getStatusBadgeClass(data.queue_stats.status))
                    .text(getStatusText(data.queue_stats.status));
            }
        });
    }
    
    function getStatusBadgeClass(status) {
        switch(status) {
            case 'processing': return 'warning';
            case 'idle': return 'success';
            case 'unknown': return 'secondary';
            default: return 'danger';
        }
    }
    
    function getStatusText(status) {
        switch(status) {
            case 'processing': return '{{ trans('locale.Processing') }}';
            case 'idle': return '{{ trans('locale.Idle') }}';
            case 'unknown': return '{{ trans('locale.Unknown') }}';
            default: return status;
        }
    }
    
    // Обработчик нажатия кнопки генерации
    $('#generateBtn').click(function() {
        var $btn = $(this);
        var $spinner = $btn.find('.spinner-border');
        var limit = $('#limit').val();
        
        if (!limit || limit < 1) {
            alert('{{ trans('locale.Please enter a valid number') }}');
            return;
        }
        
        $btn.prop('disabled', true);
        $spinner.removeClass('hidden');
        $('#resultsSection').hide();
        
        $.post('/admin/metadata/generate', {limit: limit}, function(data) {
            $btn.prop('disabled', false);
            $spinner.addClass('hidden');
            
            if (data.success) {
                $('#successAlert').show().find('#successMessage').text(data.message);
                $('#errorAlert').hide();
            } else {
                $('#errorAlert').show().find('#errorMessage').text(data.message);
                $('#successAlert').hide();
            }
            
            $('#resultsSection').show();
            
            // Обновляем статистику после выполнения
            setTimeout(updateStats, 2000);
        }).fail(function() {
            $btn.prop('disabled', false);
            $spinner.addClass('hidden');
            $('#errorAlert').show().find('#errorMessage').text('{{ trans('locale.Request failed') }}');
            $('#successAlert').hide();
            $('#resultsSection').show();
        });
    });
    
    // Обновляем статистику каждые 5 секунд
    setInterval(updateStats, 5000);
    
    // Первоначальная загрузка статистики
    updateStats();
});
</script>
@endsection
