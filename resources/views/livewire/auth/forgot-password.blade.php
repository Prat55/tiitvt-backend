<?php

use Mary\Traits\Toast;
use Livewire\Volt\Component;
use Livewire\Attributes\Rule;
use Livewire\Attributes\Title;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\Password;

new class extends Component { //  <-- Here is the `empty` layout
    use Toast;
    #[Layout('components.layouts.empty')]
    #[Title('Admin Forgot Password | Roposo Clout')]
    #[Rule('required|regex:/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/')]
    public string $email = '';

    public function mount()
    {
        if (auth()->user()) {
            return redirect('/');
        }
    }

    public function forgot_password()
    {
        $this->validate(
            [
                // 'email' => 'required|email|exists:users,email',
                //  'regex:/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/'
                'email' => ['required', 'regex:/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', 'exists:users,email'],
            ],
            [
                'email.exists' => 'The email address is not registered.',
                'email.regex' => 'Please provide valid email address',
            ],
        );

        $status = Password::sendResetLink(['email' => $this->email]);
        $status === Password::RESET_LINK_SENT ? $this->success('Reset link sent to your ' . $this->email) : $this->addError('email', __($status));
    }
};
?>
<div class="mx-auto max-w-120">
    <div class="text-end">
        <x-theme-toggle />
    </div>
    <div class="flex flex-col items-stretch p-2 md:p-8 lg:p-16">
        <div class="mx-auto w-50">
            <a href="/dashboards/ecommerce" data-discover="true">
                <img alt="Roposo Clout" class="block w-full dark:hidden"
                    src="{{ asset('frontend-assets/images/nav-long-dark.svg') }}">
                <img alt="Roposo Clout" class="hidden w-full dark:block"
                    src="{{ asset('frontend-assets/images/nav-long-white.svg') }}">
            </a>
        </div>

        <h3 class="mt-12 text-xl font-semibold text-center lg:mt-24">
            Forgot Password
        </h3>
        <h3 class="mt-2 mb-4 text-sm text-center text-base-content/70">
            Forgot your password? No problem. Just let us know your email address </br>
            and we will email you a password reset link that will allow you to
            choose a new one.
        </h3>
        <x-form wire:submit.prevent="forgot_password">
            <div class="mb-4">
                <x-input label="E-mail" icon="o-envelope" wire:model.live.debounce="email" />
            </div>
            <div class="mb-5 text-end">
                <a href="{{ route('login') }}">Back to Login</a>
            </div>
            <hr>
            <div class="mt-4">
                <x-button label="Send Reset Link" type="submit" icon="o-paper-airplane" class="w-full btn-primary" />
            </div>
        </x-form>
    </div>
</div>
