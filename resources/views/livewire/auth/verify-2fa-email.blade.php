<?php

use Livewire\Volt\Component;
use App\Services\TwoFactorAuthService;
use Mary\Traits\Toast;
use Livewire\Attributes\Title;
use Livewire\Attributes\Layout;

new class extends Component {
    use Toast;
    #[Layout('components.layouts.auth')]
    #[Title('Verify Two-Factor Authentication - Email')]
    public $code = '';
    public $codeSent = false;
    public $remainingAttempts = 5;
    public $countdownSeconds = 0;

    public function mount()
    {
        \Log::info('2FA Email Component: Mount called', [
            'auth_user' => auth()->check() ? auth()->id() : 'none',
        ]);

        $twoFactorService = new TwoFactorAuthService();
        $user = $twoFactorService->getPending2FAUser();

        if (!$user) {
            // Try to get authenticated user
            $user = auth()->user();
            \Log::info('2FA Email Component: Got auth user', [
                'user_id' => $user ? $user->id : 'null',
                'email' => $user ? $user->email : 'null',
                '2fa_enabled' => $user ? $user->two_factor_enabled : 'null',
            ]);

            if ($user && !$twoFactorService->is2FAEnabled($user)) {
                \Log::info('2FA Email Component: 2FA not enabled, redirecting to admin');
                return redirect()->route('admin.index');
            }
        }

        if ($user) {
            $this->remainingAttempts = $twoFactorService->getRemainingAttempts($user->id);
        }
    }

    public function sendCode()
    {
        \Log::info('sendCode method started');

        $twoFactorService = new TwoFactorAuthService();
        $user = $twoFactorService->getPending2FAUser() ?? auth()->user();

        \Log::info('User found in sendCode', ['user_id' => $user?->id]);

        if (!$user) {
            \Log::error('No user found in sendCode');
            $this->error('User not found');
            return;
        }

        try {
            \Log::info('About to send code via email', ['user_id' => $user->id, 'email' => $user->email]);

            if ($twoFactorService->sendCodeViaEmail($user)) {
                \Log::info('Email sent successfully');
                $this->codeSent = true;
                $this->countdownSeconds = 60;
                $this->success('Verification code sent to ' . $user->email);
            } else {
                \Log::error('sendCodeViaEmail returned false');
                $this->error('Failed to send verification code');
            }
        } catch (\Exception $e) {
            \Log::error('Exception in sendCode: ' . $e->getMessage(), ['exception' => (string) $e]);
            $this->error('Error sending code: ' . $e->getMessage());
        }
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

<div class="flex items-center justify-center px-4">
    <div class="w-full max-w-md">
        <!-- Card -->
        <div class="rounded-lg p-8">
            <!-- Header -->
            <div class="text-center mb-8">
                <div class="inline-block p-3 bg-base-100 rounded-full mb-4">
                    <x-icon name="o-envelope" class="w-8 h-8 text-base-900" />
                </div>
                <h1 class="text-3xl font-bold text-base-900">Email Verification</h1>
                <p class="mt-2 text-sm text-base-600">
                    Enter the 6-digit code sent to your email
                </p>
            </div>

            @if (!$codeSent)
                <!-- Send Code Section -->
                <div class="space-y-4">
                    <p class="text-center text-sm text-base-600 mb-6">
                        We'll send a verification code to your registered email address.
                    </p>

                    <button type="button" wire:click="sendCode" class="btn btn-neutral w-full">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                            stroke="currentColor" class="size-5">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.97l-7.5 4.5a2.25 2.25 0 0 1-2.36 0l-7.5-4.5A2.25 2.25 0 0 1 .75 6.75" />
                        </svg>
                        <span wire:loading.remove>Send Verification Code</span>
                        <span wire:loading>Sending...</span>
                    </button>
                </div>
            @else
                <!-- Verify Code Section -->
                <div class="space-y-6">
                    <!-- Code Input -->
                    <div>
                        <x-input label="Verification Code" wire:model.live="code" type="text" placeholder="000000"
                            inputmode="numeric" maxlength="6"
                            class="text-center text-2xl font-mono tracking-widest focus:border-base-400" />
                    </div>

                    <!-- Code Info -->
                    <div class="p-3 bg-base-100 rounded-lg border border-base-200">
                        <p class="text-xs text-base-700">
                            The code will expire in 10 minutes. Check your spam folder if you don't see the email.
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
                    <x-button label="Verify Code" class="btn-neutral w-full" icon="o-check-circle"
                        wire:click="verifyCode" :disabled="empty($code)" />

                    <!-- Resend Code -->
                    <div class="text-center">
                        <p class="text-xs text-base-600 mb-2">Didn't receive the code? <span wire:click="sendCode"
                                class="cursor-pointer text-primary">Resend Code</span></p>
                    </div>
                </div>
            @endif
        </div>

        <!-- Info Box -->
        <div class="mt-2 p-4">
            <p class="text-xs text-base-600">
                <strong>Security Tip:</strong> Never share your verification code with anyone. Our support team will
                never ask for it.
            </p>
        </div>
    </div>
</div>
