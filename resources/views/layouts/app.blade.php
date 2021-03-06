<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}"_>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@section('title'){{ config('app.name', 'Laravel') }}@show</title>
    <meta name="description" content="@section('description') Sir Edgar is my own virtual assistant  @show">


    <!-- Styles -->
    <link href="{{ mix('css/app.css') }}" rel="stylesheet" type="text/css">
    <link href="{{ asset('css/react-day-picker.css') }}" rel="stylesheet" type="text/css">

    <style>
        .react-calendar-heatmap .color-scale-0 { fill: #ebedf0;}
        .react-calendar-heatmap .color-scale-1 { fill: #d6e685;}
        .react-calendar-heatmap .color-scale-2 { fill: #8cc665;}
        .react-calendar-heatmap .color-scale-3 { fill: #44a340;}
        .react-calendar-heatmap .color-scale-4 { fill: #1e6823;}
        .productivity-page-header {
            margin-top: 5px;
        }
        .finance-page-header {
            margin-top: 0px;
        }
        footer {
            position: fixed;
            height: 100px;
            bottom: 0;
            width: 100%;
        }

    </style>

</head>
<body style="background-color: #ffffff">
    <div id="app">
        <nav class="navbar navbar-default navbar-static-top">
            <div class="container">
                <div class="navbar-header">

                    <!-- Collapsed Hamburger -->
                    <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#app-navbar-collapse">
                        <span class="sr-only">Toggle Navigation</span>
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                    </button>

                    <!-- Branding Image -->
                    <a class="navbar-brand" href="{{ url('/') }}">
                        {{ config('app.name', 'Laravel') }}
                    </a>
                </div>

                <div class="collapse navbar-collapse" id="app-navbar-collapse">

                    <!-- Right Side Of Navbar -->
                    <ul class="nav navbar-nav navbar-right">
                        <!-- Authentication Links -->
                        @if (Auth::guest())
                            <li><a href="{{ route('login') }}">Login</a></li>
                            <li><a href="{{ route('register') }}">Register</a></li>
                        @else
                            <li class="dropdown">
                                <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false">
                                    {{ Auth::user()->name }} <span class="caret"></span>
                                </a>

                                <ul class="dropdown-menu" role="menu">
                                    <li><a href="{{ route('account') }}">Account</a></li>
                                    <li>
                                        <a href="{{ route('logout') }}"
                                            onclick="event.preventDefault();
                                                     document.getElementById('logout-form').submit();">
                                            Logout
                                        </a>

                                        <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                                            {{ csrf_field() }}
                                        </form>
                                    </li>
                                </ul>
                            </li>
                        @endif
                    </ul>
                </div>
            </div>
        </nav>

        @yield('content')
    </div>

    <!-- Scripts -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js" defer></script>

    <!-- Latest compiled and minified JavaScript -->
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous" defer></script>
    <script type="text/javascript">
        window._token = "{{ csrf_token() }}";
    </script>
    <script src="{{ mix('js/app.js') }}" defer></script>

</body>
<!--<footer>
    <hr>
    <div class="container">
        <div class="row">
            <div class="col-xs-2 col-xs-offset-10">
                <a href="{{ route('privacy.policy ') }}" class="text-right">Privacy policy</a>
            </div>
        </div>
    </div>
    <br>
</footer> -->
</html>
