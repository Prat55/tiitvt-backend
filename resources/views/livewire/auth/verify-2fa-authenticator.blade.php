<?php

use Livewire\Volt\Component;
use App\Services\TwoFactorAuthService;
use Mary\Traits\Toast;
use Livewire\Attributes\Title;
use Livewire\Attributes\Layout;

new class extends Component {
    use Toast;
    #[Layout('components.layouts.auth')]
    #[Title('Verify Two-Factor Authentication - Authenticator')]
    public $code = '';
    public $remainingAttempts = 5;

    public function mount()
    {
        $twoFactorService = new TwoFactorAuthService();
        $user = $twoFactorService->getPending2FAUser();

        if (!$user) {
            // Try to get authenticated user
            $user = auth()->user();
            if ($user && !$twoFactorService->is2FAEnabled($user)) {
                return redirect()->route('admin.index');
            }
        }

        $this->remainingAttempts = $twoFactorService->getRemainingAttempts($user->id);
    }

    public function verifyCode()
    {
        if (empty($this->code)) {
            $this->addError('code', 'Please enter the verification code');
            return;
        }

        if (strlen($this->code) !== 6 || !ctype_digit($this->code)) {
            $this->addError('code', 'Code must be 6 digits');
            return;
        }

        $twoFactorService = new TwoFactorAuthService();
        $user = $twoFactorService->getPending2FAUser() ?? auth()->user();

        if (!$user) {
            $this->error('User not found');
            return redirect()->route('login');
        }

        // Check if user has exceeded max attempts
        if ($twoFactorService->hasExceededAttempts($user->id)) {
            $this->error('Too many failed attempts. Please try again later.');
            return;
        }

        if ($twoFactorService->verifyCode($user, $this->code)) {
            // Mark as verified in session
            session()->put('2fa_verified_at', now()->timestamp);

            // Clear pending 2FA session if it exists
            $twoFactorService->clearPending2FASession();

            $this->success('Two-factor authentication verified successfully!');
            return redirect()->route('admin.index');
        } else {
            $this->code = '';
            $this->remainingAttempts = $twoFactorService->getRemainingAttempts($user->id);

            if ($this->remainingAttempts === 0) {
                $this->error('Too many failed attempts. Please try again later.');
            } else {
                $this->error("Invalid code. You have {$this->remainingAttempts} attempts remaining.");
            }
        }
    }
};
?>

<div class="min-h-screen bg-base-100 flex items-center justify-center px-4">
    <div class="w-full max-w-md">
        <!-- Card -->
        <div class="rounded-lg shadow-lg border border-base-200 p-8">
            <!-- Header -->
            <div class="text-center mb-8">
                <div class="inline-block p-3 bg-base-100 rounded-full mb-4">
                    <x-icon name="o-qr-code" class="w-8 h-8 text-base-900" />
                </div>
                <h1 class="text-3xl font-bold text-base-900">Authenticator Verification</h1>
                <p class="mt-2 text-sm text-base-600">
                    Enter the 6-digit code from your authenticator app
                </p>
            </div>

            <!-- Verify Code Section -->
            <div class="space-y-6">
                <!-- Code Input -->
                <div>
                    <x-input label="Verification Code" wire:model="code" type="text" placeholder="000000"
                        inputmode="numeric" maxlength="6"
                        class="text-center text-2xl font-mono tracking-widest focus:border-base-400" />
                    @error('code')
                        <span class="text-error text-sm mt-2 block">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Code Info -->
                <div class="p-3 bg-base-100 rounded-lg border border-base-200">
                    <p class="text-xs text-base-700">
                        Enter the 6-digit code from your Google Authenticator or similar authenticator app. The code
                        changes every 30 seconds.
                    </p>
                </div>

                <!-- Attempts Warning -->
                @if ($remainingAttempts <= 2)
                    <div class="p-3 bg-warning/10 border border-warning rounded-lg">
                        <p class="text-xs text-warning">
                            <strong>Warning:</strong> You have {{ $remainingAttempts }}
                            attempt{{ $remainingAttempts !== 1 ? 's' : '' }} remaining.
                        </p>
                    </div>
                @endif

                <!-- Verify Button -->
                <x-button label="Verify Code" class="btn-neutral w-full" icon="o-check-circle" wire:click="verifyCode"
                    :disabled="empty($code)" />

                <!-- Back Link -->
                <div class="text-center">
                    <a href="{{ route('login') }}" class="text-sm text-base-600 hover:text-base-900 underline">
                        Back to Login
                    </a>
                </div>
            </div>
        </div>

        <!-- Info Box -->
        <div class="mt-6 p-4 bg-base-50 border border-base-200 rounded-lg">
            <p class="text-xs text-base-600">
                <strong>Security Tip:</strong> Never share your verification code with anyone. Our support team will
                never ask for it.
            </p>
        </div>
    </div>
</div>
