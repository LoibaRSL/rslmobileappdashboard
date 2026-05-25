<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>@yield('title', 'Admin')</title>
    <meta content="width=device-width, initial-scale=1" name="viewport" />
    @yield("styles")
    @include("shared.partials.head-css")
</head>
<body>
    <div class="wrapper">
        @include("shared.partials.topbar")
        @include("shared.partials.sidenav")

        <div class="content-page">
            <div class="container-fluid">
                @yield("content")
            </div>

            @include("shared.partials.footer")
        </div>

        @include("shared.partials.customizer")
    </div>

    @include("shared.partials.footer-scripts")
    @yield("scripts")
    @yield("page-scripts")
</body>
</html>
