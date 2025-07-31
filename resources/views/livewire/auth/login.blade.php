<?php

use Mary\Traits\Toast;
use Livewire\Volt\Component;
use Livewire\Attributes\Rule;
use Livewire\Attributes\Title;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\Auth;
use App\Services\SsoService;

new class extends Component {
    use Toast;
    #[Layout('components.layouts.auth')]
    #[Title('Login')]
    #[Rule('required|email|exists:users,email')]
    public string $email = '';
    #[Rule('required')]
    public string $password = '';

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

        if (auth()->attempt($credentials)) {
            request()->session()->regenerate();

            $user = auth()->user();
            $welcomeMessage = 'Welcome again ' . $user->name;
            $this->success($welcomeMessage, redirectTo: route('admin.index'));
        } else {
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

                <div class="flex items-center justify-end">
                    <a href="{{ route('password.request') }}">Forgot Password</a>
                </div>
                <x-button label="Login" type="submit" icon="o-paper-airplane" spinner="login"
                    class="w-full btn-primary" />
            </x-form>
        </div>
    </div>
</div>
