<!doctype html>
<html lang="en" data-theme="nm-studio" data-mode="light" class="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>Sign in · Numerimondes Admin</title>
    <link rel="stylesheet" href="{{ \Webkernel\Getters\GetAsset::url('numerimondes/website', 'app/resources/assets/dependable-ui/dependable-ui.css') }}">
    <link rel="stylesheet" href="{{ \Webkernel\Getters\GetAsset::url('numerimondes/website', 'app/resources/css/dependable-ui.themes.css') }}">
    <link rel="stylesheet" href="{{ \Webkernel\Getters\GetAsset::url('numerimondes/website', 'app/resources/css/numerimondes-silky.css') }}">
    <style>
        html,body{min-height:100%;margin:0}body{min-height:100vh;display:grid;place-items:center;padding:24px;box-sizing:border-box;background:#f1f5f9}.nm-admin-login{width:min(100%,420px)}.nm-admin-login__brand{display:block;width:52px;height:52px;margin:0 auto 24px}.nm-admin-login__title{margin:0 0 24px;text-align:center;font:400 12px/1.4 system-ui,sans-serif;letter-spacing:.16em;text-transform:uppercase;color:#475569}.nm-admin-login form{display:grid;gap:20px}.nm-admin-login label{display:block;margin-bottom:8px;font-size:.875rem}.nm-admin-login__submit{width:100%}
    </style>
</head>
<body>
<main class="nm-admin-login" data-csrf-token="{{ $csrfToken }}">
    <img class="nm-admin-login__brand" src="{{ simple_site('studio', 'brand/numerimondes-logo-alone-lightmode.png') }}" alt="Numerimondes">
    <p class="nm-admin-login__title">Administration</p>
    <x-webkernel-platform::section heading="Sign in" icon="heroicon-o-lock-closed">
        <form method="post" action="/admin/login">
            <input type="hidden" name="_csrf" value="{{ $csrfToken }}">
            <div>
                <label for="admin-username" class="mb-2 block font-medium">Username</label>
                <x-webkernel-platform::input.wrapper>
                    <x-webkernel-platform::input id="admin-username" name="username" type="text" autocomplete="username" required autofocus></x-webkernel-platform::input>
                </x-webkernel-platform::input.wrapper>
            </div>
            <div>
                <label for="admin-password" class="mb-2 block font-medium">Password</label>
                <x-webkernel-platform::input.wrapper>
                    <x-webkernel-platform::input id="admin-password" name="password" type="password" autocomplete="current-password" required></x-webkernel-platform::input>
                </x-webkernel-platform::input.wrapper>
            </div>
            <x-webkernel-platform::button type="submit" color="primary" class="nm-admin-login__submit">Sign in</x-webkernel-platform::button>
        </form>
    </x-webkernel-platform::section>
</main>
</body>
</html>
