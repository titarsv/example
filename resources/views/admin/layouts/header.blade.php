<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    {{--<meta name="viewport"--}}
    {{--content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">--}}
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Панель администратора</title>
    <link rel="shortcut icon" href="/images/favicon.ico" type="image/x-icon">

    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css">
    <link rel="stylesheet" href="/js/chosen/chosen.min.css">
    <link rel="stylesheet" href="/css/admin.min.css">
    <link rel="stylesheet" href="/css/font-awesome.min.css">
    <link rel="stylesheet" href="/js/larchik/jScrollPane/jquery.jscrollpane.css">
    <link rel="stylesheet" href="/js/larchik/magnific-popup/magnific-popup.css">

    <script src="/js/libs/jquery.min.js"></script>
    <script src="/js/chosen/chosen.jquery.min.js"></script>
    <script src="/js/libs/bootstrap.min.js"></script>
    <script src="/js/larchik/jScrollPane/jquery.mousewheel.js"></script>
    <script src="/js/larchik/jScrollPane/jquery.jscrollpane.min.js"></script>
    <script src="/js/larchik/magnific-popup/jquery.magnific-popup.min.js"></script>
    <script src="/js/admin/sweetalert2.all.min.js"></script>
    <script src="/js/larchik/jquery/jquery-ui.min.js"></script>
    {{--<script src="/js/larchik/jquery/ui/sortable.min.js"></script>--}}
    <script id="sourcecode">
        $(function(){
            var settings = {
                showArrows: false,
                autoReinitialise: true,
                contentWidth: '0px'
            };
            var pane = $('.scroll-pane');
            pane.jScrollPane(settings);
        });
    </script>
    <style>
        .jspVerticalBar{
            background: transparent;
            width: 5px;
        }
        .jspHorizontalBar{
            background: transparent;
            height: 5px;
        }
        .jspTrack{
            background: transparent;
        }
        .jspDrag{
            background: rgba(255, 255, 255, 0.5);
            border-radius: 3px;
        }
        #full-page-container > .jspContainer > .jspVerticalBar{
            width: 10px;
        }
        #full-page-container > .jspContainer > .jspVerticalBar > .jspTrack > .jspDrag{
            background: rgba(0, 0, 0, 0.5);
            border-radius: 5px;
        }
    </style>
</head>
