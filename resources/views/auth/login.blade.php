<x-guest-layout>
    <div class="mb-8">
        <h2 class="text-2xl font-semibold text-slate-900">Welcome back</h2>
        <p class="mt-1 text-sm text-slate-500">Sign in to your DMS account</p>
    </div>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1.5 w-full rounded-lg border-gray-200 focus:border-indigo-500 focus:ring-indigo-500" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password" :value="__('Password')" />

            <div class="relative">
                <x-text-input
                    id="password"
                    class="block mt-1.5 w-full rounded-lg border-gray-200 focus:border-indigo-500 focus:ring-indigo-500 pr-10"
                    type="password"
                    name="password"
                    required
                    autocomplete="current-password"
                />

                <button
                    type="button"
                    onclick="togglePassword()"
                    class="absolute inset-y-0 right-0 mt-1.5 flex items-center px-3 text-gray-400 hover:text-gray-600"
                    aria-label="Show password"
                    title="Show password"
                >
                    <svg id="eye-show" xmlns="http://www.w3.org/2000/svg"
                        class="h-5 w-5"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>

                    <svg id="eye-hide" xmlns="http://www.w3.org/2000/svg"
                        class="h-5 w-5 hidden"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 3l18 18" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10.584 10.587a2 2 0 102.829 2.828" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9.88 5.09A10.94 10.94 0 0112 5c4.477 0 8.268 2.943 9.542 7a10.97 10.97 0 01-4.043 5.087M6.228 6.228A10.97 10.97 0 002.458 12c1.274 4.057 5.065 7 9.542 7a10.97 10.97 0 004.043-.913" />
                    </svg>
                </button>
            </div>

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="flex items-center justify-between">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" name="remember">
                <span class="ms-2 text-sm text-slate-600">{{ __('Remember me') }}</span>
            </label>

            @if (Route::has('password.request'))
                <a class="text-sm text-indigo-600 hover:text-indigo-700 font-medium" href="{{ route('password.request') }}">
                    {{ __('Forgot password?') }}
                </a>
            @endif
        </div>

        <button type="submit" class="w-full inline-flex justify-center items-center px-4 py-2.5 bg-indigo-600 border border-transparent rounded-lg font-semibold text-sm text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition">
            {{ __('Sign in') }}
        </button>
    </form>

    <div class="mt-8 pt-6 border-t border-gray-100">
        <p class="text-xs text-slate-400 text-center">Demo: superadmin@dms.test / password</p>
    </div>
</x-guest-layout>

<script>
    function togglePassword() {
        const password = document.getElementById('password');
        const showEye = document.getElementById('eye-show');
        const hideEye = document.getElementById('eye-hide');
        const button = showEye.closest('button');

        if (password.type === 'password') {
            password.type = 'text';

            showEye.classList.add('hidden');
            hideEye.classList.remove('hidden');

            button.setAttribute('aria-label', 'Hide password');
            button.setAttribute('title', 'Hide password');
        } else {
            password.type = 'password';

            showEye.classList.remove('hidden');
            hideEye.classList.add('hidden');

            button.setAttribute('aria-label', 'Show password');
            button.setAttribute('title', 'Show password');
        }
    }
</script>