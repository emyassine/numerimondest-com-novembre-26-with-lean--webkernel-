<main class="mx-auto flex min-h-[70vh] max-w-md items-center px-4 py-12">
    <x-webkernel-platform::section heading="Sign in to Offerings" icon="heroicon-o-lock-closed" class="w-full">
        <form method="post" action="/admin/offerings/login" class="space-y-5">
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
            <x-webkernel-platform::button type="submit" color="primary">Sign in</x-webkernel-platform::button>
        </form>
    </x-webkernel-platform::section>
</main>
