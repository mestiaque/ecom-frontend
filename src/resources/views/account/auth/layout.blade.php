@extends('efront::layouts.app')

@section('content')
    <section class="ef-auth">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-8 col-lg-5">
                    <div class="fcard ef-auth-card">
                        <div class="text-center mb-4">
                            <div class="ef-auth-icon"><i class="@yield('auth_icon', 'fas fa-user')"></i></div>
                            <h2 class="ef-auth-title">@yield('auth_title')</h2>
                            <p class="text-muted mb-0">@yield('auth_subtitle')</p>
                        </div>
                        @yield('auth_form')
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
