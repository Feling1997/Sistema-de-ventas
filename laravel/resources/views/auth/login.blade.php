<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ingresar</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body {
            align-items: center;
            background: #f3f4f6;
            color: #111827;
            display: flex;
            font-size: 14px;
            min-height: 100vh;
        }

        .login-card {
            background: #fff;
            border: 1px solid rgba(17, 24, 39, .1);
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(15, 23, 42, .08);
            margin: auto;
            max-width: 380px;
            padding: 1.25rem;
            width: calc(100% - 2rem);
        }

        .brand-mark {
            align-items: center;
            background: #111827;
            border-radius: 10px;
            color: #fff;
            display: inline-flex;
            height: 42px;
            justify-content: center;
            margin-bottom: .75rem;
            width: 42px;
        }

        .form-control {
            min-height: 38px;
        }

        .btn-primary {
            min-height: 38px;
        }
    </style>
</head>
<body>
    <main class="login-card">
        <div class="text-center mb-3">
            <span class="brand-mark"><i class="bi bi-shop"></i></span>
            <h1 class="h4 mb-1">Sistema de Ventas</h1>
            <p class="text-muted mb-0">Ingrese para continuar</p>
        </div>

        <form method="post" action="{{ route('login.store') }}">
            @csrf

            <div class="mb-3">
                <label class="form-label" for="usuario">Usuario</label>
                <input class="form-control" id="usuario" name="usuario" type="text" value="{{ old('usuario') }}" autocomplete="username" autofocus>
                @error('usuario')
                    <p class="text-danger small mb-0 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-3">
                <label class="form-label" for="clave">Clave</label>
                <input class="form-control" id="clave" name="clave" type="password" autocomplete="current-password">
                @error('clave')
                    <p class="text-danger small mb-0 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="form-check mb-3">
                <input class="form-check-input" id="recordarme" name="recordarme" type="checkbox" value="1" @checked(old('recordarme'))>
                <label class="form-check-label" for="recordarme">Recordarme</label>
            </div>

            <button class="btn btn-primary w-100" type="submit">Ingresar</button>
        </form>
    </main>
</body>
</html>
