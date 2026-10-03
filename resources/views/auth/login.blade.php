<x-guest-layout>

    <div class="avit-login-header">
        <h1 class="avit-login-title">
            Welcome back
        </h1>

        <p class="avit-login-subtitle">
            Sign in to your account
        </p>
    </div>


    <x-auth-session-status
        class="avit-status"
        :status="session('status')"
    />


    <form
        method="POST"
        action="{{ route('login') }}"
        class="avit-form"
    >

        @csrf


        {{-- Email --}}
        <div class="avit-field">

            <div class="avit-label-row">
                <label
                    for="email"
                    class="avit-label"
                >
                    Email
                </label>
            </div>

            <input
                id="email"
                name="email"
                type="email"
                value="{{ old('email') }}"
                required
                autofocus
                autocomplete="username"
                placeholder="you@company.com"
                class="avit-input @error('email') avit-input-error @enderror"
            >

            @error('email')
                <p class="avit-error">
                    {{ $message }}
                </p>
            @enderror

        </div>


        {{-- Password --}}
        <div class="avit-field">

            <div class="avit-label-row">
                <label
                    for="password"
                    class="avit-label"
                >
                    Password
                </label>
            </div>

            <div class="avit-input-wrap">

                <input
                    id="password"
                    name="password"
                    type="password"
                    required
                    autocomplete="current-password"
                    placeholder="Enter your password"
                    class="avit-input avit-password-input @error('password') avit-input-error @enderror"
                >

                <button
                    type="button"
                    class="avit-password-toggle"
                    onclick="togglePassword()"
                    aria-label="Show password"
                    title="Show password"
                >

                    {{-- Show --}}
                    <svg
                        id="eye-show"
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="1.8"
                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                        />

                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="1.8"
                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                        />
                    </svg>


                    {{-- Hide --}}
                    <svg
                        id="eye-hide"
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        class="hidden"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="1.8"
                            d="M3 3l18 18"
                        />

                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="1.8"
                            d="M10.584 10.587a2 2 0 102.829 2.828"
                        />

                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="1.8"
                            d="M9.88 5.09A10.94 10.94 0 0112 5c4.477 0 8.268 2.943 9.542 7a10.97 10.97 0 01-4.043 5.087M6.228 6.228A10.97 10.97 0 012.458 12c1.274 4.057 5.065 7 9.542 7a10.97 10.97 0 004.043-.913"
                        />
                    </svg>

                </button>

            </div>

            @error('password')
                <p class="avit-error">
                    {{ $message }}
                </p>
            @enderror

        </div>


        {{-- Remember / Forgot --}}
        <div class="avit-form-options">

            <label
                for="remember_me"
                class="avit-remember"
            >

                <input
                    id="remember_me"
                    type="checkbox"
                    name="remember"
                >

                <span>
                    {{ __('Remember me') }}
                </span>

            </label>


            @if (Route::has('password.request'))

                <a
                    href="{{ route('password.request') }}"
                    class="avit-forgot"
                >
                    {{ __('Forgot password?') }}
                </a>

            @endif

        </div>


        {{-- Submit --}}
        <button
            type="submit"
            class="avit-submit"
        >
            {{ __('Sign in') }}
        </button>

    </form>


    {{-- Existing demo information preserved --}}
    <div class="avit-demo">
        <p>
            Demo: superadmin@dms.test / password
        </p>
    </div>


</x-guest-layout>


<script>
    function togglePassword() {
        const password = document.getElementById('password');
        const showEye = document.getElementById('eye-show');
        const hideEye = document.getElementById('eye-hide');
        const button = document.querySelector('.avit-password-toggle');

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