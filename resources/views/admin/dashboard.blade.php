@include('admin.layouts.header')
@extends('admin.layouts.main')
@section('title')
    {{ trans('locale.Admin Panel') }}
@endsection
@section('content')

    <div class="content-title">
        <div class="row">
            <div class="col-sm-12">
                <h1>{{ trans('locale.Admin Panel') }}</h1>
            </div>
        </div>
    </div>

    <div class="panel-group">
        @if(!empty($token))
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h4>{{ trans('locale.Google Analytics') }}</h4>
                </div>
                <div class="panel-body dashboard">
                    <div class="row">
                        <div class="col-xs-12">

                            <!-- Step 1: Create the containing elements. -->

                            <div id="embed-api-auth-container"></div>
                            <div class="Dashboard Dashboard--full">
                                <header class="Dashboard-header">
                                    <div class="Titles">
                                        <h1 class="Titles-main" id="view-name">{{ trans('locale.Select a View') }}</h1>
                                        <div class="Titles-sub">{{ trans('locale.Comparing sessions from') }}
                                            <b id="from-dates">{{ trans('locale.last week') }}</b>
                                            {{ trans('locale.to') }} <b id="to-dates">{{ trans('locale.this week') }}</b>
                                        </div>
                                    </div>
                                    <div id="view-selector-container"></div>
                                </header>

                                <ul class="FlexGrid">
                                    <li class="FlexGrid-item">
                                        <div id="data-chart-1-container"></div>
                                        <div id="date-range-selector-1-container"></div>

                                    </li>
                                    <li class="FlexGrid-item">
                                        <div id="data-chart-2-container"></div>
                                        <div id="date-range-selector-2-container"></div>
                                    </li>
                                </ul>
                            </div>

                            <!-- Step 2: Load the library. -->

                            <script>
                                (function(w,d,s,g,js,fjs){
                                    g=w.gapi||(w.gapi={});g.analytics={q:[],ready:function(cb){this.q.push(cb)}};
                                    js=d.createElement(s);fjs=d.getElementsByTagName(s)[0];
                                    js.src='https://apis.google.com/js/platform.js';
                                    fjs.parentNode.insertBefore(js,fjs);js.onload=function(){g.load('analytics')};
                                }(window,document,'script'));
                            </script>

                            <script type='text/javascript' src='https://www.gstatic.com/charts/loader.js'></script>
                            <script type='text/javascript'>
                                google.charts.load('current', {
                                    'packages': ['geochart'],
                                    // Note: you will need to get a mapsApiKey for your project.
                                    // See: https://developers.google.com/chart/interactive/docs/basic_load_libs#load-settings
                                    'mapsApiKey': 'AIzaSyBj7XDClRGcxA9xTV3KPIwyijuHODynh4w'
                                });
                            </script>

                            {{--<link href="https://ga-dev-tools.appspot.com/public/css/index.css" rel="stylesheet">--}}
                            <link href="/public/css/larchik/analytics.css" rel="stylesheet">

                            <!-- Include the ViewSelector2 component script. -->
                            <script src="/public/js/larchik/analytics/embed-api/components/view-selector2.js"></script>

                            <!-- Include the DateRangeSelector component script. -->
                            <script src="/public/js/larchik/analytics/embed-api/components/date-range-selector.js"></script>

                            <script>
                                // {{ trans('locale.Metrics') }}: https://developers.google.com/analytics/devguides/reporting/core/dimsmets#cats=traffic_sources,session
                                gapi.analytics.ready(function() {

                                    // var CLIENT_ID = '468291400130-cu453f6ovurjioobd90kl2vflf9jed82.apps.googleusercontent.com';
                                    //
                                    // gapi.analytics.auth.authorize({
                                    //     container: 'embed-api-auth-container',
                                    //     clientid: CLIENT_ID,
                                    // });

                                    // gapi.analytics.auth.on('success', function(response) {
                                    //     $('#embed-api-auth-container').remove();
                                    // });

                                    gapi.analytics.auth.authorize({
                                        'serverAuth': {
                                            'access_token': '{{ $token }}'
                                        }
                                    });

                                    /**
                                     * Store a set of common DataChart config options since they're shared by
                                     * both of the charts we're about to make.
                                     */
                                    var commonConfig1 = {
                                        query: {
                                            'dimensions': 'ga:browser',
                                            'metrics': 'ga:sessions',
                                            'sort': '-ga:sessions',
                                            'max-results': '13'
                                        },
                                        chart: {
                                            type: 'TABLE',
                                            container: 'main-chart-container',
                                            options: {
                                                width: '100%'
                                            }
                                        }
                                    };

                                    var commonConfig2 = {
                                        query: {
                                            'dimensions': 'ga:country',
                                            'metrics': 'ga:sessions',
                                            'start-date': '30daysAgo',
                                            'end-date': 'yesterday',
                                        },
                                        chart: {
                                            container: 'chart-1-container',
                                            type: 'GEO',
                                            options: {
                                                width: '100%',
                                                //region: '142',
                                                displayMode: 'markers'
                                            }
                                        }
                                        // query: {
                                        //     metrics: 'ga:sessions',
                                        //     dimensions: 'ga:region'
                                        // },
                                        // chart: {
                                        //     container: 'chart-1-container',
                                        //     type: 'PIE',
                                        //     options: {
                                        //         width: '100%',
                                        //         pieHole: 4/9
                                        //     }
                                        // }
                                        // chart: {
                                        //     type: 'LINE',
                                        //     options: {
                                        //         width: '100%'
                                        //     }
                                        // }
                                    };


                                    /**
                                     * Query params representing the first chart's date range.
                                     */
                                    var dateRange1 = {
                                        'start-date': '14daysAgo',
                                        'end-date': '8daysAgo'
                                    };


                                    /**
                                     * Query params representing the second chart's date range.
                                     */
                                    var dateRange2 = {
                                        'start-date': '7daysAgo',
                                        'end-date': 'yesterday'
                                    };


                                    /**
                                     * Create a new ViewSelector2 instance to be rendered inside of an
                                     * element with the id "view-selector-container".
                                     */
                                    var viewSelector = new gapi.analytics.ext.ViewSelector2({
                                        container: 'view-selector-container',
                                    }).execute();


                                    /**
                                     * Create a new DateRangeSelector instance to be rendered inside of an
                                     * element with the id "date-range-selector-1-container", set its date range
                                     * and then render it to the page.
                                     */
                                    var dateRangeSelector1 = new gapi.analytics.ext.DateRangeSelector({
                                        container: 'date-range-selector-1-container'
                                    })
                                        .set(dateRange1)
                                        .execute();


                                    /**
                                     * Create a new DateRangeSelector instance to be rendered inside of an
                                     * element with the id "date-range-selector-2-container", set its date range
                                     * and then render it to the page.
                                     */
                                    var dateRangeSelector2 = new gapi.analytics.ext.DateRangeSelector({
                                        container: 'date-range-selector-2-container'
                                    })
                                        .set(dateRange2)
                                        .execute();


                                    /**
                                     * Create a new DataChart instance with the given query parameters
                                     * and Google chart options. It will be rendered inside an element
                                     * with the id "data-chart-1-container".
                                     */
                                    var dataChart1 = new gapi.analytics.googleCharts.DataChart(commonConfig1)
                                        .set({query: dateRange1})
                                        .set({chart: {container: 'data-chart-1-container'}});


                                    /**
                                     * Create a new DataChart instance with the given query parameters
                                     * and Google chart options. It will be rendered inside an element
                                     * with the id "data-chart-2-container".
                                     */
                                    var dataChart2 = new gapi.analytics.googleCharts.DataChart(commonConfig2)
                                        .set({query: dateRange2})
                                        .set({chart: {container: 'data-chart-2-container'}});


                                    /**
                                     * Register a handler to run whenever the user changes the view.
                                     * The handler will update both dataCharts as well as updating the title
                                     * of the dashboard.
                                     */
                                    viewSelector.on('viewChange', function(data) {
                                        dataChart1.set({query: {ids: data.ids}}).execute();
                                        dataChart2.set({query: {ids: data.ids}}).execute();

                                        var title = document.getElementById('view-name');
                                        title.textContent = data.property.name + ' (' + data.view.name + ')';
                                    });


                                    /**
                                     * Register a handler to run whenever the user changes the date range from
                                     * the first datepicker. The handler will update the first dataChart
                                     * instance as well as change the dashboard subtitle to reflect the range.
                                     */
                                    dateRangeSelector1.on('change', function(data) {
                                        dataChart1.set({query: data}).execute();

                                        // Update the "from" dates text.
                                        var datefield = document.getElementById('from-dates');
                                        datefield.textContent = data['start-date'] + '&mdash;' + data['end-date'];
                                    });

                                    var mainChartRowClickListener;

                                    dataChart1.on('success', function(response) {

                                        var chart = response.chart;
                                        var dataTable = response.dataTable;

                                        // Store a reference to this listener so it can be cleaned up later.
                                        mainChartRowClickListener = google.visualization.events
                                            .addListener(chart, 'select', function(event) {

                                                // When you unselect a row, the "select" event still fires
                                                // but the selection is empty. Ignore that case.
                                                if (!chart.getSelection().length) return;

                                                var row =  chart.getSelection()[0].row;
                                                var browser =  dataTable.getValue(row, 0);
                                                var options = {
                                                    query: {
                                                        filters: 'ga:browser==' + browser
                                                    },
                                                    chart: {
                                                        options: {
                                                            title: browser
                                                        }
                                                    }
                                                };

                                                dataChart2.set(options).execute();
                                            });
                                    });


                                    /**
                                     * Register a handler to run whenever the user changes the date range from
                                     * the second datepicker. The handler will update the second dataChart
                                     * instance as well as change the dashboard subtitle to reflect the range.
                                     */
                                    dateRangeSelector2.on('change', function(data) {
                                        dataChart2.set({query: data}).execute();

                                        // Update the "to" dates text.
                                        var datefield = document.getElementById('to-dates');
                                        datefield.textContent = data['start-date'] + '&mdash;' + data['end-date'];
                                    });


                                    $(window).resize(function(){
                                        dataChart1.execute();
                                        dataChart2.execute();
                                    });
                                });
                            </script>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
@endsection
