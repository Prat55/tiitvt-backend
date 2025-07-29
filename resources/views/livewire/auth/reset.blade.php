<?php

use App\Models\User;
use Mary\Traits\Toast;
use Livewire\Volt\Component;
use Livewire\Attributes\Title;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\Password;

new class extends Component {
    use Toast;
    #[Layout('components.layouts.empty')]
    #[Title('Admin Reset Password | Roposo Clout')]
    public $token;
    public $email;
    public $password;
    public $password_confirmation;

    public function mount($token)
    {
        $this->token = $token;
        $this->email = request()->email;
    }

    public function resetPassword()
    {
        $this->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);

        $status = Password::reset(
            [
                'email' => $this->email,
                'password' => $this->password,
                'password_confirmation' => $this->password_confirmation,
                'token' => $this->token,
            ],
            function ($user, $password) {
                $user
                    ->forceFill([
                        'password' => bcrypt($password),
                    ])
                    ->save();
            },
        );

        if ($status === Password::PASSWORD_RESET) {
            $this->success('Password reset successfully. Now you can login with new password', redirectTo: route('admin.login'));
            return;
        }

        $this->addError('email', __($status));
    }
}; ?>

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
            Reset Password
        </h3>

        <x-form wire:submit.prevent='resetPassword'>
            <div class="mb-4">
                <x-input label="E-mail" readonly wire:model="email" icon="o-envelope" />
            </div>
            <div class="mb-4">
                <x-password label="Password" wire:model="password" />
            </div>
            <div class="mb-4">
                <x-password label="Confirm Password" wire:model="password_confirmation" />
            </div>
            <div class="mb-5 text-end">
                <a href="{{ route('login') }}">Go to Login</a>
            </div>
            <hr>
            <div class="mt-4">
                <x-button label="Reset Password" type="submit" icon="o-paper-airplane" class="w-full btn-primary" />
            </div>
        </x-form>
    </div>
</div>
