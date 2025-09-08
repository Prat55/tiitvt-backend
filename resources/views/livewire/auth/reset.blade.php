<?php

use App\Models\User;
use Mary\Traits\Toast;
use Livewire\Volt\Component;
use Livewire\Attributes\Title;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\Password;

new class extends Component {
    use Toast;
    #[Layout('components.layouts.auth')]
    #[Title('Reset Password | TIITVT')]
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
            $this->success('Password reset successfully. Now you can login with new password', redirectTo: route('login'));
            return;
        }

        $this->addError('email', __($status));
    }
};
?>

<div>
    <div class="mt-5 select-none">
        <h3 class="text-2xl font-semibold text-center">
            Reset Password
        </h3>

        <div class="mx-auto mt-10 md:w-96">
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
                    <x-button label="Reset Password" type="submit" icon="o-paper-airplane"
                        class="w-full btn-primary" />
                </div>
            </x-form>
        </div>
    </div>
</div>
