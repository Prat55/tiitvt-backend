<?php

use Livewire\Volt\Component;
use App\Models\User;
use App\Models\Center;
use App\Services\SessionManager;
use App\Services\TwoFactorAuthService;
use Mary\Traits\Toast;
use Livewire\WithFileUploads;
use Livewire\Attributes\Title;
use Illuminate\View\View;
use App\Enums\RolesEnum;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Url;

new class extends Component {
    use WithFileUploads, Toast;

    #[Title('Edit Profile')]
    #[Url]
    public $tabSelected = 'profile';
    public $name;
    public $email;
    public $phone;
    public $image;
    public $user;
    public $center;
    public $config = ['aspectRatio' => 1];

    // Change password modal properties
    public $showChangePasswordModal = false;
    public $currentPassword;
    public $newPassword;
    public $confirmPassword;

    // 2FA modal properties
    public $show2FAModal = false;
    public $show2FAVerificationModal = false;
    public $twoFAMethod = 'email'; // 'email' or 'authenticator'
    public $verificationCode = '';
    public $twoFACodeSent = false;
    public $twoFARemainingAttempts = 5;

    // Session properties
    public $sessions = [];
    public $currentSessionId;

    public function mount()
    {
        $this->user = User::findOrFail(auth()->id());
        $this->name = $this->user->name;
        $this->email = $this->user->email;
        $this->phone = $this->user->phone;
        $this->currentSessionId = session()->getId();

        // Load center data if user is a center
        if ($this->user->isCenter()) {
            $this->center = $this->user->center;
        }

        $this->loadSessions();
    }

    public function loadSessions()
    {
        $sessionManager = new SessionManager();
        $this->sessions = $sessionManager->loadUserSessions($this->user->id, $this->currentSessionId);
    }

    public function signOutOtherSessions()
    {
        $sessionManager = new SessionManager();

        if ($sessionManager->signOutOtherSessions($this->user->id, $this->currentSessionId)) {
            $this->loadSessions();
            $this->success('All other sessions have been signed out successfully!');
        } else {
            $this->error('An error occurred while signing out other sessions.');
        }
    }

    public function signOutSession($sessionId)
    {
        $sessionManager = new SessionManager();

        if ($sessionManager->signOutSession($sessionId, $this->user->id)) {
            $this->loadSessions();
            $this->success('Session signed out successfully!');
        } else {
            $this->error('An error occurred while signing out the session.');
        }
    }

    public function save(): void
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $this->user->id,
            'phone' => 'nullable|string|max:20',
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:1024',
        ]);

        $this->user->name = $this->name;
        if (!hasAuthRole(RolesEnum::Center->value)) {
            $this->user->email = $this->email;
        }
        $this->user->phone = $this->phone;

        if ($this->image) {
            if ($this->user->image) {
                Storage::disk('public')->delete(str_replace('/storage/', '', $this->user->image));
            }

            $url = $this->image->store('users', 'public');
            $this->user->image = "/storage/$url";
        }

        $this->user->save();
        $this->success('Profile updated successfully!', redirectTo: route('admin.profile'));
    }

    public function openChangePasswordModal()
    {
        $this->resetPasswordForm();
        $this->showChangePasswordModal = true;
    }

    public function resetPasswordForm()
    {
        $this->currentPassword = '';
        $this->newPassword = '';
        $this->confirmPassword = '';
    }

    public function changePassword()
    {
        $this->validate(
            [
                'currentPassword' => 'required|string',
                'newPassword' => 'required|string|min:8|different:currentPassword',
                'confirmPassword' => 'required|string|same:newPassword',
            ],
            [
                'currentPassword.required' => 'Current password is required.',
                'newPassword.required' => 'New password is required.',
                'newPassword.min' => 'New password must be at least 8 characters.',
                'newPassword.different' => 'New password must be different from current password.',
                'confirmPassword.required' => 'Please confirm your new password.',
                'confirmPassword.same' => 'Password confirmation does not match.',
            ],
        );

        // Verify current password
        if (!\Hash::check($this->currentPassword, $this->user->password)) {
            $this->addError('currentPassword', 'Current password is incorrect.');
            return;
        }

        // Update password
        $this->user->password = \Hash::make($this->newPassword);
        $this->user->save();

        $this->resetPasswordForm();
        $this->showChangePasswordModal = false;
        $this->success('Password changed successfully!');
    }

    public function setup2FA()
    {
        // Reset form
        $this->verificationCode = '';
        $this->twoFACodeSent = false;
        $this->twoFARemainingAttempts = 5;
        $this->twoFAMethod = 'email';
        $this->show2FAModal = true;
    }

    public function send2FACode()
    {
        $twoFactorService = new TwoFactorAuthService();

        if ($twoFactorService->sendCodeViaEmail($this->user)) {
            $this->twoFACodeSent = true;
            $this->success('Verification code sent to ' . $this->user->email);
        } else {
            $this->error('Failed to send verification code. Please try again.');
        }
    }

    public function verify2FACode()
    {
        if (empty($this->verificationCode)) {
            $this->addError('verificationCode', 'Please enter the verification code');
            return;
        }

        if (strlen($this->verificationCode) !== 6 || !ctype_digit($this->verificationCode)) {
            $this->addError('verificationCode', 'Code must be 6 digits');
            return;
        }

        $twoFactorService = new TwoFactorAuthService();

        // Check if user has exceeded max attempts
        if ($twoFactorService->hasExceededAttempts($this->user->id)) {
            $this->error('Too many failed attempts. Please try again later.');
            return;
        }

        if ($twoFactorService->verifyCode($this->user, $this->verificationCode)) {
            // Enable 2FA
            if ($twoFactorService->enable2FA($this->user, $this->twoFAMethod)) {
                // Refresh user model from database to reflect 2FA status
                $this->user = $this->user->fresh();
                $this->verificationCode = '';
                $this->twoFACodeSent = false;
                $this->show2FAModal = false;
                $this->success('Two-factor authentication enabled successfully!');
            } else {
                $this->error('Failed to enable 2FA. Please try again.');
            }
        } else {
            $this->verificationCode = '';
            $this->twoFARemainingAttempts = $twoFactorService->getRemainingAttempts($this->user->id);

            if ($this->twoFARemainingAttempts === 0) {
                $this->error('Too many failed attempts. Please try again later.');
                $this->twoFACodeSent = false;
            } else {
                $this->error("Invalid code. You have {$this->twoFARemainingAttempts} attempts remaining.");
            }
        }
    }

    public function disable2FA()
    {
        $twoFactorService = new TwoFactorAuthService();

        if ($twoFactorService->disable2FA($this->user)) {
            // Refresh user model from database to reflect 2FA status
            $this->user = $this->user->fresh();
            $this->success('Two-factor authentication disabled successfully!');
        } else {
            $this->error('Failed to disable 2FA. Please try again.');
        }
    }
};
?>

@section('cdn')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.css" />
@endsection

<div>
    <!-- Header Section -->
    <div class="mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-base-900">
                    Account Settings
                </h1>
                <p class="mt-2 text-sm text-base-600">
                    Manage your account settings and security preferences
                </p>
            </div>
            <div class="flex items-center space-x-2">
                <x-badge :value="$user->roles->first()->name ?? 'User'" class="badge-secondary capitalize" />
                @if ($user->isCenter() && $center)
                    <x-badge :value="$center->status ?? 'Inactive'"
                        class="badge-{{ $center->status === 'active' ? 'success' : 'warning' }}" />
                @endif
            </div>
        </div>

        <!-- Breadcrumbs -->
        <div class="mt-3 text-sm breadcrumbs">
            <ul>
                <li>
                    <a href="{{ route('admin.index') }}" wire:navigate class="text-base-700 hover:text-base-900">
                        Dashboard
                    </a>
                </li>
                <li class="text-base-500">Account Settings</li>
            </ul>
        </div>
    </div>

    <div class="bg-base-200 p-2">
        <!-- Tabs Section -->
        <x-tabs wire:model="tabSelected" active-class="bg-primary rounded !text-white p-2"
            label-class="font-semibold text-base-700" label-div-class="rounded-lg w-fit p-2 flex gap-1">
            <!-- Profile Tab -->
            <x-tab name="profile" label="Profile">
                <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                    <!-- Personal Information Card -->
                    <div class="lg:col-span-2">
                        <x-card title="Personal Information" subtitle="Update your personal details"
                            class="border border-base-200 bg-base-50">
                            <x-form wire:submit="save">
                                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                                    <div>
                                        <x-input label="Full Name" wire:model="name" placeholder="Enter your full name"
                                            class="focus:border-base-300" required />
                                    </div>
                                    <div>
                                        <x-input label="Phone Number" wire:model="phone"
                                            placeholder="Enter your phone number" class="focus:border-base-300" />
                                    </div>
                                </div>

                                <div class="mt-6">
                                    <x-input label="Email Address" wire:model="email" type="email"
                                        placeholder="Enter your email address" :readonly="hasAuthRole(RolesEnum::Center->value)" :disabled="hasAuthRole(RolesEnum::Center->value)"
                                        class="focus:border-base-300" />
                                    @if (hasAuthRole(RolesEnum::Center->value))
                                        <x-alert class="mt-2 text-sm text-base-600 bg-base-100 border border-base-200">
                                            <x-icon name="o-exclamation-triangle" class="w-4 h-4" />
                                            Email changes are restricted to administrators only
                                        </x-alert>
                                    @endif
                                </div>

                                <x-slot:actions>
                                    <x-button label="Update Profile" class="btn-neutral" type="submit" spinner="save"
                                        icon="o-check" />
                                </x-slot:actions>
                            </x-form>
                        </x-card>
                    </div>

                    <!-- Profile Picture & Center Info -->
                    <div class="space-y-6">
                        <!-- Profile Picture Card -->
                        <x-card title="Profile Picture" subtitle="Upload your avatar"
                            class="border border-base-200 bg-base-50">
                            <div class="w-full flex justify-center">
                                <x-file wire:model="image" accept="image" crop-after-change :crop-config="$config">
                                    <div class="mt-4 text-center">
                                        <img id="imagePreview"
                                            src="{{ $user->image ? asset($user->image) : 'https://placehold.co/300?text=Profile' }}"
                                            class="mx-auto h-32 w-32 rounded-full object-cover border-4 border-base-300"
                                            alt="Profile Picture">
                                    </div>
                                </x-file>
                            </div>
                        </x-card>

                        <!-- Center Information (for Center users) -->
                        @if ($user->isCenter() && $center)
                            <x-card title="Center Information" subtitle="Your center details"
                                class="border border-base-200 bg-base-50">
                                <div class="space-y-4">
                                    <div>
                                        <label class="text-sm font-medium text-base-700">Center Name</label>
                                        <p class="mt-1 text-sm text-base-900">{{ $center->name ?? 'N/A' }}</p>
                                    </div>

                                    <div>
                                        <label class="text-sm font-medium text-base-700">Center ID</label>
                                        <p class="mt-1 text-sm text-base-900">{{ $center->uid ?? 'N/A' }}</p>
                                    </div>

                                    <div>
                                        <label class="text-sm font-medium text-base-700">Address</label>
                                        <p class="mt-1 text-sm text-base-900">{{ $center->address ?? 'N/A' }}</p>
                                    </div>

                                    <div>
                                        <label class="text-sm font-medium text-base-700">Status</label>
                                        <x-badge :value="$center->status ?? 'Inactive'"
                                            class="badge-{{ $center->status === 'active' ? 'success' : 'warning' }}" />
                                    </div>
                                </div>
                            </x-card>
                        @endif
                    </div>
                </div>
            </x-tab>

            <!-- Security Tab -->
            <x-tab name="security" label="Security">
                <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                    <!-- Password Reset Card -->
                    <x-card title="Password & Authentication" subtitle="Manage your login security"
                        class="border border-base-200 bg-base-50">
                        <div class="space-y-6">
                            <!-- Change Password Section -->
                            <div>
                                <div class="flex items-start justify-between">
                                    <div>
                                        <h3 class="text-lg font-semibold text-base-900">Password</h3>
                                        <p class="mt-1 text-sm text-base-600">
                                            Update your password regularly to keep your account secure
                                        </p>
                                    </div>
                                </div>
                                <div class="mt-4">
                                    <x-button label="Change Password" class="btn-outline btn-sm" icon="o-key"
                                        wire:click="openChangePasswordModal" />
                                </div>
                            </div>

                            <div class="divider divider-neutral my-4"></div>

                            <!-- Session Management Section -->
                            <div>
                                <h3 class="text-lg font-semibold text-base-900">Active Sessions</h3>
                                <p class="mt-1 text-sm text-base-600">
                                    Manage and sign out from other devices
                                </p>

                                <!-- Sessions List -->
                                <div class="mt-4 space-y-3">
                                    @forelse($sessions as $session)
                                        <div
                                            class="p-4 rounded-lg border border-base-200 bg-base-200 hover:border-base-300 transition-colors">
                                            <div class="flex items-start justify-between">
                                                <div class="flex items-start space-x-3 flex-1">
                                                    <div
                                                        class="flex-shrink-0 w-10 h-10 bg-base-200 rounded-full flex items-center justify-center">
                                                        <x-icon name="o-globe-alt" class="w-5 h-5 text-base-700" />
                                                    </div>
                                                    <div class="flex-1">
                                                        <div class="flex items-center gap-2">
                                                            <p class="font-medium text-base-900">
                                                                {{ $session['browser'] }} on {{ $session['device'] }}
                                                            </p>
                                                            @if ($session['isCurrent'])
                                                                <x-badge value="Current Session"
                                                                    class="badge-sm badge-primary" />
                                                            @endif
                                                        </div>
                                                        <p class="text-xs text-base-600 mt-1">
                                                            IP: {{ $session['ip'] }} • {{ $session['lastActivity'] }}
                                                        </p>
                                                    </div>
                                                </div>
                                                @if (!$session['isCurrent'])
                                                    <div class="flex-shrink-0 ml-4">
                                                        <x-button label="Sign Out" class="btn-xs btn-outline"
                                                            icon="o-x-mark"
                                                            wire:click="signOutSession('{{ $session['id'] }}')" />
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    @empty
                                        <p class="text-sm text-base-600">No active sessions found.</p>
                                    @endforelse
                                </div>

                                <!-- Sign Out All Button -->
                                @if (count($sessions) > 1)
                                    <div class="mt-6">
                                        <x-button label="Sign Out All Other Sessions" class="btn-outline btn-sm"
                                            icon="o-arrow-left-on-rectangle" wire:click="signOutOtherSessions" />
                                    </div>
                                @endif
                            </div>
                        </div>
                    </x-card>

                    <!-- Two-Factor Authentication Card -->
                    <x-card title="Two-Factor Authentication" subtitle="Add an extra layer of security"
                        class="border border-base-200 bg-base-50">
                        <div class="space-y-4">
                            <!-- Status -->
                            <div class="p-4 bg-base-200 rounded-lg border border-base-200">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-sm font-medium text-base-900">Two-Factor Authentication</p>
                                        <p class="mt-1 text-xs text-base-600">Status: <span
                                                class="font-semibold text-base-700">
                                                @if ($user->two_factor_enabled)
                                                    <span class="text-success">Enabled</span>
                                                @else
                                                    <span class="text-base-700">Disabled</span>
                                                @endif
                                            </span></p>
                                    </div>
                                    <div>
                                        @if ($user->two_factor_enabled)
                                            <x-icon name="o-check-circle" class="w-8 h-8 text-success" />
                                        @else
                                            <x-icon name="o-lock-closed" class="w-8 h-8 text-base-300" />
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <!-- Enable/Disable Buttons -->
                            @if ($user->two_factor_enabled)
                                <div class="p-3 bg-success/10 border border-success rounded-lg">
                                    <p class="text-xs text-success font-semibold">
                                        <x-icon name="o-check-circle" class="w-4 h-4 inline" />
                                        2FA is active. You'll need to provide a verification code on login.
                                    </p>
                                </div>
                                <x-button label="Disable Two-Factor Authentication"
                                    class="btn-outline btn-sm w-full btn-error" icon="o-no-symbol"
                                    wire:click="disable2FA" />
                            @else
                                <div class="space-y-3">
                                    <p class="text-sm font-medium text-base-900">Choose an authentication method:</p>

                                    <!-- Email Method -->
                                    <div class="p-4 rounded-lg bg-base-200 cursor-pointer transition-colors">
                                        <div class="flex items-start space-x-3">
                                            <input type="radio" wire:model="twoFAMethod" value="email"
                                                class="mt-1 radio radio-sm" />
                                            <div class="flex-1">
                                                <p class="font-medium text-base-900">Email</p>
                                                <p class="text-sm text-base-600">Receive verification codes via email
                                                </p>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Authenticator Method -->
                                    <div class="p-4 rounded-lg bg-base-200 cursor-pointer transition-colors">
                                        <div class="flex items-start space-x-3">
                                            <input type="radio" wire:model="twoFAMethod" value="authenticator"
                                                class="mt-1 radio radio-sm" disabled />
                                            <div class="flex-1">
                                                <p class="font-medium text-base-900">Authenticator App <span
                                                        class="text-xs text-base-600 badge badge-info badge-soft">Coming
                                                        Soon</span></p>
                                                <p class="text-sm text-base-600">Use Google Authenticator or similar
                                                    apps</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Setup Button -->
                                <x-button label="Setup Two-Factor Authentication" class="btn-neutral btn-sm w-full"
                                    icon="o-shield-exclamation" wire:click="setup2FA" />
                            @endif

                            <!-- Info Alert -->
                            <div class="p-3 bg-base-100 rounded-lg border border-base-200">
                                <p class="text-xs text-base-700">
                                    <strong>Tip:</strong> Two-factor authentication significantly improves your
                                    account
                                    security by requiring an additional verification code during login.
                                </p>
                            </div>
                        </div>
                    </x-card>
                </div>
            </x-tab>
        </x-tabs>
    </div>

    <!-- Change Password Modal -->
    <x-modal wire:model="showChangePasswordModal" title="Change Password" class="backdrop-blur" separator>
        <x-form wire:submit.prevent="changePassword">
            <div class="space-y-4">
                <x-password label="Current Password" wire:model.defer="currentPassword"
                    placeholder="Enter your current password" required right icon="fas.lock" />

                <x-password label="New Password" wire:model.defer="newPassword" placeholder="Enter your new password"
                    required right icon="fas.lock" />

                <x-password label="Confirm New Password" wire:model.defer="confirmPassword"
                    placeholder="Confirm your new password" required right icon="fas.lock" />

                <x-alert class="text-sm text-base-600 bg-base-100 border border-base-200">
                    <x-icon name="o-information-circle" class="w-4 h-4" />
                    Password must be at least 8 characters long and different from your current password.
                </x-alert>
            </div>

            <x-slot:actions>
                <x-button label="Cancel" @click="$wire.showChangePasswordModal = false" />
                <x-button label="Change Password" class="btn-neutral" type="submit" spinner="changePassword" />
            </x-slot:actions>
        </x-form>
    </x-modal>

    <!-- 2FA Setup Modal -->
    <x-modal wire:model="show2FAModal" title="Setup Two-Factor Authentication" class="backdrop-blur" separator>
        @if (!$twoFACodeSent)
            <!-- Method Selection and Code Request -->
            <div class="space-y-6">
                <p class="text-sm text-base-600">
                    @if ($twoFAMethod === 'email')
                        We'll send a 6-digit verification code to <strong>{{ $user->email }}</strong>.
                    @else
                        Scan this QR code with an authenticator app like Google Authenticator.
                    @endif
                </p>

                @if ($twoFAMethod === 'authenticator')
                    <div class="flex justify-center p-4 bg-base-100 rounded-lg border border-base-200">
                        <div class="w-48 h-48 bg-white rounded flex items-center justify-center">
                            <x-icon name="o-qr-code" class="w-32 h-32 text-base-300" />
                        </div>
                    </div>
                    <p class="text-xs text-base-600 text-center">Or enter this key manually:</p>
                    <div class="text-center p-3 bg-base-100 rounded-lg border border-base-200">
                        <code class="text-base-900 font-mono font-bold text-sm">JBSWY3DPEBLW64TMMQ</code>
                    </div>
                @endif

                <x-alert class="text-sm text-base-600 bg-base-100 border border-base-200">
                    <x-icon name="o-information-circle" class="w-4 h-4" />
                    Make sure you have saved your backup codes in a safe place.
                </x-alert>
            </div>

            <x-slot:actions>
                <x-button label="Cancel" @click="$wire.show2FAModal = false" />
                <x-button label="Send Code" class="btn-neutral" wire:click="send2FACode" />
            </x-slot:actions>
        @else
            <!-- Code Verification -->
            <div class="space-y-6">
                <div>
                    <p class="text-sm text-base-600 mb-4">
                        Enter the 6-digit code sent to your email to complete the setup.
                    </p>

                    <x-input label="Verification Code" wire:model.live="verificationCode" type="text"
                        placeholder="000000" inputmode="numeric" maxlength="6"
                        class="text-center text-2xl font-mono tracking-widest" />

                    @if ($twoFARemainingAttempts <= 2 && $twoFARemainingAttempts > 0)
                        <div class="p-3 bg-warning/10 border border-warning rounded-lg mt-3">
                            <p class="text-xs text-warning">
                                <strong>Warning:</strong> You have {{ $twoFARemainingAttempts }}
                                attempt{{ $twoFARemainingAttempts !== 1 ? 's' : '' }} remaining.
                            </p>
                        </div>
                    @endif
                </div>

                <x-alert class="text-sm text-base-600 bg-base-100 border border-base-200">
                    <x-icon name="o-information-circle" class="w-4 h-4" />
                    The code will expire in 10 minutes.
                </x-alert>
            </div>

            <x-slot:actions>
                <x-button label="Back" @click="$set('twoFACodeSent', false)" />
                <x-button label="Verify & Enable 2FA" class="btn-neutral" wire:click="verify2FACode"
                    :disabled="strlen($verificationCode) !== 6" />
            </x-slot:actions>
        @endif
    </x-modal>
</div>
