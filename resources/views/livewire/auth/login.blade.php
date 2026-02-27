<?php

use Mary\Traits\Toast;
use Illuminate\Support\Str;
use Livewire\Volt\Component;
use Livewire\Attributes\{Rule, Title, Layout};
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\{Auth, RateLimiter};

new class extends Component {
    use Toast;
    #[Layout('components.layouts.auth')]
    #[Title('Login')]
    #[Rule('required|email|exists:users,email')]
    public string $email = '';
    #[Rule('required')]
    public string $password = '';

    public bool $remember = false;

    public function mount()
    {
        // It is logged in
        if (auth()->user()) {
            $this->success('You are already logged in.', redirectTo: route('admin.index'));
        }
    }

    public function login()
    {
        $credentials = $this->validate();

        $key = Str::lower($this->email) . '|' . request()->ip();

        // Check if too many attempts
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);

            $minutes = floor($seconds / 60);
            $remainingSeconds = $seconds % 60;

            $time = '';

            if ($minutes > 0) {
                $time .= $minutes . ' minute' . ($minutes > 1 ? 's' : '');
            }

            if ($remainingSeconds > 0) {
                if ($minutes > 0) {
                    $time .= ' and ';
                }
                $time .= $remainingSeconds . ' second' . ($remainingSeconds > 1 ? 's' : '');
            }

            throw ValidationException::withMessages([
                'email' => "Too many login attempts. Please try again in {$time}.",
            ]);
        }

        if (auth()->attempt($credentials, $this->remember)) {
            RateLimiter::clear($key); // Reset attempts on success

            request()->session()->regenerate();

            $user = auth()->user();
            $welcomeMessage = 'Welcome again ' . $user->name;

            $this->success($welcomeMessage, redirectTo: route('admin.index'));
        } else {
            RateLimiter::hit($key, 300); // 300 seconds = 5 minutes

            $this->addError('email', 'The provided credentials do not match our records.');
        }
    }
};
?>
<div>
    <div class="mt-3 select-none">
        <h3 class="text-4xl font-semibold text-center">
            Login
        </h3>
        <div class="mx-auto mt-10 md:w-96">
            <x-form wire:submit="login" no-seperator>
                <x-input label="E-mail" wire:model="email" icon="o-envelope" />
                <x-password label="Password" icon="fas.lock" wire:model="password" right />

                <div class="flex items-center justify-between">
                    <x-checkbox label="Remember Me" wire:model="remember" class="text-primary" />
                    <a href="{{ route('password.request') }}">Forgot Password</a>
                </div>
                <x-button label="Login" type="submit" icon="o-paper-airplane" spinner="login"
                    class="w-full btn-primary" />
            </x-form>
        </div>
    </div>
</div>
