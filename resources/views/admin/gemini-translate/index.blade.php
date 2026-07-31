@extends('admin.layouts.contentLayoutMaster')
{{-- page title --}}
@section('title', trans('locale.AI Translation'))
{{-- vendor styles --}}
@section('vendor-styles')
@endsection

{{-- page styles --}}
@section('page-styles')
@endsection

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">{{ trans('locale.AI Translation') }}</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="/admin">{{ trans('locale.Dashboard') }}</a></li>
                    <li class="breadcrumb-item active">{{ trans('locale.AI Translation') }}</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            @if(!empty($stats))
                @php
                    $colors = [
                        'primary',
                        'success',
                        'warning',
                        'secondary',
                        'danger',
                        'info',
                    ];
                    $i = 0;
                @endphp
                <script src="/vendors/js/charts/apexcharts.min.js"></script>
                <script>
                    let colors = [
                        '#5A8DEE',
                        '#39DA8A',
                        '#FDAC41',
                        '#475F7B',
                        '#FF5B5C',
                        '#00CFDD',
                    ];
                    let currentStats = @json($stats);
                    let currentLanguages = @json($languages_names);
                </script>

                <div class="row widget-radial-charts">
                    @foreach($stats as $lang => $langStats)
                        <div class="col-md-{{ count($stats) == 2 ? 6 : 4 }}">
                            <div class="card">
                                <div class="card-content">
                                    <div class="card-body p-0">
                                        <div class="d-lg-flex justify-content-between">
                                            <div class="widget-card-details d-flex flex-column justify-content-between p-2">
                                                <div>
                                                    <h5 class="font-medium-2 font-weight-normal">{{ $languages_names[$lang] }}</h5>
                                                    <p class="text-muted total-strings-{{ $lang }}">{{ trans('locale.Total Strings') }}: {{ $langStats['total_strings'] }}</p>
                                                    <p class="text-muted translated-strings-{{ $lang }}">{{ trans('locale.Translated') }}: {{ $langStats['translated_strings'] }}</p>
                                                </div>
                                                <div class="btn-group mb-1">
                                                    <button class="btn btn-{{ $colors[$i%6] }} type-translate-btn"
                                                            data-type="all"
                                                            data-lang="{{ $lang }}">
                                                        <i class="fas fa-language"></i> {{ trans('locale.Translate') }}
                                                    </button>
                                                    <button type="button" class="btn btn-{{ $colors[$i%6] }} dropdown-toggle dropdown-toggle-split" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                        {{ trans('locale.All') }}
                                                    </button>
                                                    <div class="dropdown-menu">
                                                        <span class="dropdown-item type-dropdown-item" data-type="all" data-lang="{{ $lang }}">
                                                          {{ trans('locale.All') }}
                                                        </span>
                                                        @foreach($types as $type => $name)
                                                            <span class="dropdown-item type-dropdown-item" data-type="{{ $type }}" data-lang="{{ $lang }}">
                                                              {{ $name }}
                                                            </span>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            </div>
                                            <div id="radial-chart-{{ $lang }}"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <script>
                            var radialPrimaryoptions_{{ $lang }} = {
                                chart: {
                                    height: 250,
                                    type: "radialBar"
                                },
                                series: [{{ $langStats['progress_percentage'] }}],
                                colors: [colors[{{ $i%6 }}]],
                                plotOptions: {
                                    radialBar: {
                                        offsetY: -10,
                                        size: 70,
                                        hollow: {
                                            size: "70%"
                                        },
                                        dataLabels: {
                                            showOn: "always",
                                            name: {
                                                show: false
                                            },
                                            value: {
                                                colors: ['#304156'],
                                                fontSize: "20px",
                                                show: true,
                                                offsetY: 8,
                                                fontFamily: "Rubik"
                                            }
                                        }
                                    }
                                },
                                stroke: {
                                    lineCap: "round",
                                }
                            };
                            var radialPrimaryChart_{{ $lang }} = new ApexCharts(
                                document.querySelector("#radial-chart-{{ $lang }}"),
                                radialPrimaryoptions_{{ $lang }}
                            );

                            radialPrimaryChart_{{ $lang }}.render();
                        </script>
                        @php $i++ @endphp
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">{{ trans('locale.Translation Queue') }}</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-12">
                            <div id="queue-info" style="display: none;">
                                <div class="alert alert-info">
                                    <h5 class="white">{{ trans('locale.Queue Status') }}</h5>
                                    <div id="queue-stats"></div>
                                </div>
                            </div>

                            <div id="result-message" style="display: none;"></div>
                        </div>
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
    let refreshInterval;

    function updateQueueStats() {
        $.ajax({
            url: '/admin/gemini-translate/progress',
            method: 'GET',
            success: function(response) {
                updateQueueDisplay(response.queue_stats);
            },
            error: function() {
                console.error('Failed to fetch queue stats');
            }
        });
    }

    function updateQueueDisplay(queueStats) {
        const queueCard = $('#queue-info').closest('.card');

        if (queueStats.queue_size > 0) {
            // Show queue card and update stats
            queueCard.show();
            $('#queue-stats').html(`
                <div class="row">
                    <div class="col-md-4">
                        <strong>{{ trans('locale.Queue Size') }}:</strong> ${queueStats.queue_size}
                    </div>
                    <div class="col-md-4">
                        <strong>{{ trans('locale.Active Workers') }}:</strong> ${queueStats.active_workers}
                    </div>
                    <div class="col-md-4">
                        <strong>{{ trans('locale.Status') }}:</strong>
                        <span class="badge badge-${queueStats.status === 'processing' ? 'success' : 'secondary'}">
                            ${queueStats.status}
                        </span>
                    </div>
                </div>
            `);
            $('#queue-info').show();
        } else {
            // Hide entire queue card when no tasks
            queueCard.hide();
        }
    }

    {{--$('#generate-btn').click(function() {--}}
    {{--    const targetLang = $('#target_lang').val();--}}
    {{--    const limit = $('#limit').val();--}}
    {{--    const type = $('#translation_type').val();--}}

    {{--    if (!limit || limit < 1 || limit > 1000) {--}}
    {{--        alert('{{ trans('locale.Please enter a valid number between 1 and 1000') }}');--}}
    {{--        return;--}}
    {{--    }--}}

    {{--    $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> {{ trans('locale.Processing') }}...');--}}

    {{--    const url = type ? '/admin/gemini-translate/generate-type' : '/admin/gemini-translate/generate';--}}
    {{--    const data = {--}}
    {{--        target_lang: targetLang,--}}
    {{--        limit: limit--}}
    {{--    };--}}

    {{--    if (type) {--}}
    {{--        data.type = type;--}}
    {{--    }--}}

    {{--    $.ajax({--}}
    {{--        url: url,--}}
    {{--        method: 'POST',--}}
    {{--        data: data,--}}
    {{--        success: function(response) {--}}
    {{--            $('#result-message').html(`--}}
    {{--                <div class="alert alert-${response.success ? 'success' : 'warning'}">--}}
    {{--                    <h5><i class="fas fa-${response.success ? 'check' : 'exclamation'}-circle"></i> ${response.message}</h5>--}}
    {{--                    ${response.stats ? `--}}
    {{--                    <div class="row">--}}
    {{--                        <div class="col-md-3">--}}
    {{--                            <strong>{{ trans('locale.Total Processed') }}:</strong> ${response.stats.total_processed}--}}
    {{--                        </div>--}}
    {{--                        <div class="col-md-3">--}}
    {{--                            <strong>{{ trans('locale.Instant Processed') }}:</strong> ${response.stats.instant_processed}--}}
    {{--                        </div>--}}
    {{--                        <div class="col-md-3">--}}
    {{--                            <strong>{{ trans('locale.Errors') }}:</strong> ${response.stats.errors}--}}
    {{--                        </div>--}}
    {{--                        <div class="col-md-3">--}}
    {{--                            <strong>{{ trans('locale.Queue Size') }}:</strong> ${response.stats.queue_stats.queue_size}--}}
    {{--                        </div>--}}
    {{--                    </div>--}}
    {{--                    ` : ''}--}}
    {{--                    ${response.errors && response.errors.length > 0 ? `--}}
    {{--                    <div class="mt-2">--}}
    {{--                        <strong>{{ trans('locale.Errors') }}:</strong>--}}
    {{--                        <ul>--}}
    {{--                            ${response.errors.map(error => `<li>${error}</li>`).join('')}--}}
    {{--                        </ul>--}}
    {{--                    </div>--}}
    {{--                    ` : ''}--}}
    {{--                </div>--}}
    {{--            `).show();--}}

    {{--            updateQueueStats();--}}

    {{--            // Start auto-refresh if there are jobs in queue--}}
    {{--            if (response.stats && response.stats.queue_stats.queue_size > 0) {--}}
    {{--                startAutoRefresh();--}}
    {{--            }--}}

    {{--            // Refresh page after a short delay to show updated statistics--}}
    {{--            setTimeout(() => {--}}
    {{--                location.reload();--}}
    {{--            }, 2000);--}}
    {{--        },--}}
    {{--        error: function(xhr) {--}}
    {{--            $('#result-message').html(`--}}
    {{--                <div class="alert alert-danger">--}}
    {{--                    <h5><i class="fas fa-exclamation-circle"></i> {{ trans('locale.Error') }}</h5>--}}
    {{--                    ${xhr.responseJSON?.message || 'An error occurred while processing your request'}--}}
    {{--                </div>--}}
    {{--            `).show();--}}
    {{--        },--}}
    {{--        complete: function() {--}}
    {{--            $('#generate-btn').prop('disabled', false).html('<i class="fas fa-language"></i> {{ trans('locale.Generate Translations') }}');--}}
    {{--        }--}}
    {{--    });--}}
    {{--});--}}

    // Handle type-specific translation buttons
    $('.type-translate-btn').click(function(e) {
        e.preventDefault();

        const type = $(this).data('type');
        const lang = $(this).data('lang');

        $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> {{ trans('locale.Processing') }}...');

        $.ajax({
            url: '/admin/gemini-translate/generate-type',
            method: 'POST',
            data: {
                target_lang: lang,
                type: type
            },
            success: function(response) {
                $('#result-message').html(`
                    <div class="alert alert-${response.success ? 'success' : 'warning'}">
                        <h5><i class="fas fa-${response.success ? 'check' : 'exclamation'}-circle"></i> ${response.message}</h5>
                        ${response.stats ? `
                        <div class="row">
                            <div class="col-md-3">
                                <strong>{{ trans('locale.Total Processed') }}:</strong> ${response.stats.total_processed}
                            </div>
                            <div class="col-md-3">
                                <strong>{{ trans('locale.Instant Processed') }}:</strong> ${response.stats.instant_processed}
                            </div>
                            <div class="col-md-3">
                                <strong>{{ trans('locale.Errors') }}:</strong> ${response.stats.errors}
                            </div>
                            <div class="col-md-3">
                                <strong>{{ trans('locale.Queue Size') }}:</strong> ${response.stats.queue_stats.queue_size}
                            </div>
                        </div>
                        ` : ''}
                        ${response.errors && response.errors.length > 0 ? `
                        <div class="mt-2">
                            <strong>{{ trans('locale.Errors') }}:</strong>
                            <ul>
                                ${response.errors.map(error => `<li>${error}</li>`).join('')}
                            </ul>
                        </div>
                        ` : ''}
                    </div>
                `).show();

                updateQueueStats();

                // Start auto-refresh if there are jobs in queue
                if (response.stats && response.stats.queue_stats.queue_size > 0) {
                    startAutoRefresh();
                }

                // Refresh page after a short delay to show updated statistics
                // setTimeout(() => {
                //     location.reload();
                // }, 2000);
            },
            error: function(xhr) {
                $('#result-message').html(`
                    <div class="alert alert-danger">
                        <h5><i class="fas fa-exclamation-circle"></i> Error</h5>
                        ${xhr.responseJSON?.message || '{{ trans('locale.An error occurred while processing your request') }}'}
                    </div>
                `).show();
            },
            complete: function() {
                // Reset button after delay
                // setTimeout(() => {
                //     location.reload();
                // }, 2000);
            }
        });
    });

    $('#refresh-btn').click(function() {
        $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> {{ trans('locale.Refreshing') }}...');

        updateQueueStats();

        setTimeout(() => {
            $(this).prop('disabled', false).html('<i class="fas fa-sync-alt"></i> {{ trans('locale.Refresh') }}');
        }, 1000);
    });

    function startAutoRefresh() {
        if (refreshInterval) {
            clearInterval(refreshInterval);
        }

        refreshInterval = setInterval(updateQueueStats, 5000);
    }

    function stopAutoRefresh() {
        if (refreshInterval) {
            clearInterval(refreshInterval);
            refreshInterval = null;
        }
    }

    // Handle dropdown menu type selection
    $('.type-dropdown-item').click(function(e) {
        e.preventDefault();
        const lang = $(this).data('lang');
        const selectedType = $(this).data('type');

        // Update the dropdown button text
        const dropdownButton = $(this).closest('.btn-group').find('.dropdown-toggle-split');
        dropdownButton.text($(this).text());

        // Update the widget
        updateRadialChart(lang, selectedType);
    });

    function updateRadialChart(lang, type) {
        let percentage;
        let totalStrings;
        let translatedStrings;

        if (type === 'all') {
            percentage = currentStats[lang].progress_percentage;
            totalStrings = currentStats[lang].total_strings;
            translatedStrings = currentStats[lang].translated_strings;
        } else {
            percentage = currentStats[lang].type_stats[type].progress_percentage;
            totalStrings = currentStats[lang].type_stats[type].total_strings;
            translatedStrings = currentStats[lang].type_stats[type].translated_strings;
        }

        // Update chart data
        const chartVarName = `radialPrimaryChart_${lang}`;
        if (window[chartVarName]) {
            window[chartVarName].updateSeries([percentage]);
        }

        // Update text in widget
        $(`.total-strings-${lang}`).html(`{{ trans('locale.Total Strings') }}: ${totalStrings}`);
        $(`.translated-strings-${lang}`).html(`{{ trans('locale.Translated') }}: ${translatedStrings}`);

        // Update translate button
        const translateBtn = $(`.type-translate-btn[data-lang="${lang}"]`);
        translateBtn.attr('data-type', type);
    }

    // Initial load - hide queue card first, then update stats
    $('#queue-info').closest('.card').hide();
    updateQueueStats();
});
</script>
@endsection
