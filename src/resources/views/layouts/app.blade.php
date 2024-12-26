<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>勤怠管理</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="{{ asset('css/style.css') }}" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.9.1/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" defer></script>
</head>

<body>
    <header class="auth-header w-auto" style="background-color: black;">
        <nav class="navbar navbar-expand-lg navbar-dark">
            <div class="container">
                <!-- ロゴ -->
                <a class="navbar-brand" href="#">
                    <img src="{{ asset('svg/logo.svg') }}" alt="Logo" class="CoachTech_White">
                </a>

                <!-- トグルボタン -->
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
                    aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <!-- ナビゲーション -->
                <div class="collapse navbar-collapse" id="navbarNav">
                    @auth
                        <ul class="navbar-nav ms-auto d-flex flex-row">
                            @if (auth()->user()->hasRole('staff'))
                                <li class="nav-item"><a class="nav-link me-3" href="{{ route('attendance.show') }}">勤怠</a>
                                </li>
                                <li class="nav-item"><a class="nav-link me-3"
                                        href="{{ route('attendance.index') }}">勤怠一覧</a>
                                </li>
                                <li class="nav-item"><a class="nav-link me-3" href="{{ route('request.index') }}">申請</a>
                                </li>
                                <li class="nav-item"><a class="nav-link" href="{{ route('logout') }}">ログアウト</a></li>
                            @elseif (auth()->user()->hasRole('admin'))
                                <li class="nav-item"><a class="nav-link me-3"
                                        href="{{ route('admin.staffAttendance.list') }}">勤怠一覧</a></li>
                                <li class="nav-item"><a class="nav-link me-3"
                                        href="{{ route('admin.staff.list') }}">スタッフ一覧</a>
                                </li>
                                <li class="nav-item"><a class="nav-link me-3"
                                        href="{{ route('admin.requests.index') }}">申請一覧</a>
                                </li>
                                <li class="nav-item"><a class="nav-link" href="{{ route('admin.logout') }}">ログアウト</a></li>
                            @endif
                        </ul>
                    @endauth
                </div>
            </div>
        </nav>
    </header>


    <main>
        @yield('content')
    </main>
</body>

</html>
