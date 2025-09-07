<?php

use Mary\Traits\Toast;
use Livewire\Volt\Component;
use Livewire\Attributes\Rule;
use Livewire\Attributes\Title;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\Password;

new class extends Component { //  <-- Here is the `empty` layout
    use Toast;
    #[Layout('components.layouts.auth')]
    #[Title('Forgot Password | TIITVT')]
    #[Rule('required')]
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
                'email' => ['required', 'exists:users,email'],
            ],
            [
                'email.exists' => 'The email address is not registered.',
            ],
        );

        $status = Password::sendResetLink(['email' => $this->email]);
        $status === Password::RESET_LINK_SENT ? $this->success('Reset link sent to your ' . $this->email) : $this->addError('email', __($status));
    }
};
?>
<div>
    <div class="mt-5 select-none">
        <h3 class="text-2xl font-semibold text-center">
            Forgot Password
        </h3>
        <h3 class="mt-2 mb-4 text-sm text-center text-base-content/70">
            Forgot your password? No problem. Just let us know your email address </br>
            and we will email you a password reset link that will allow you to
            choose a new one.
        </h3>
        <div class="mx-auto mt-10 md:w-96">
            <x-form wire:submit.prevent="forgot_password">
                <div class="mb-4">
                    <x-input label="E-mail" icon="o-envelope" wire:model.live.debounce="email" />
                </div>
                <div class="mb-5 text-end">
                    <a href="{{ route('login') }}">Back to Login</a>
                </div>
                <hr>
                <div class="mt-4">
                    <x-button label="Send Reset Link" type="submit" icon="o-paper-airplane"
                        class="w-full btn-primary" />
                </div>
            </x-form>
        </div>
    </div>
</div>
