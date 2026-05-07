<!DOCTYPE html>

<html @yield("html_attribute")>

<head>
    @include("shared.partials/title-meta")

    @yield("styles")

    @include("shared.partials/head-css")
</head>

<body>
    <div class="wrapper">

        @include("shared.partials/topbar")

        @include("shared.partials/sidenav")

        <div class="content-page">
            <div class="container-fluid">

                @yield("content")

            </div>

            @include("shared.partials/footer")

        </div>

        @include("shared.partials/customizer")

    </div>

    @yield("scripts")

</body>

</html>
