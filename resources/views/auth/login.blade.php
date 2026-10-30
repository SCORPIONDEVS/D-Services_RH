<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Entrar · D'SERVICE RH</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body class="login-body">
<form method="post" action="{{ url('/login') }}" class="login card">
    @csrf
    <div class="brand">D'SERVICE <small>RH &amp; DP</small></div>
    @error('email') <div class="flash bad">{{ $message }}</div> @enderror
    <label>E-mail<input type="email" name="email" value="{{ old('email') }}" required autofocus></label>
    <label>Senha<input type="password" name="password" required></label>
    <label class="check"><input type="checkbox" name="remember" value="1"> Manter conectado</label>
    <button class="btn primary">Entrar</button>
</form>
</body>
</html>
