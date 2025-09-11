<?php

use Livewire\Volt\Component;
use App\Models\User;
use App\Models\Center;
use Mary\Traits\Toast;
use Livewire\WithFileUploads;
use Livewire\Attributes\Title;
use Illuminate\View\View;
use App\Enums\RolesEnum;
use Illuminate\Support\Facades\Storage;

new class extends Component {
    use WithFileUploads, Toast;

    #[Title('Edit Profile')]
    #[Url]
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

    public function mount()
    {
        $this->user = User::findOrFail(auth()->id());
        $this->name = $this->user->name;
        $this->email = $this->user->email;
        $this->phone = $this->user->phone;

        // Load center data if user is a center
        if ($this->user->isCenter()) {
            $this->center = $this->user->center;
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
        $this->user->email = $this->email;
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
};
?>

@section('cdn')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.css" />
@endsection

<div>
    <!-- Header Section -->
    <div class="mb-3">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                    Profile Settings
                </h1>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                    Manage your account settings and preferences
                </p>
            </div>
            <div class="flex items-center space-x-2">
                <x-badge :value="$user->roles->first()->name ?? 'User'" class="badge-primary" />
                @if ($user->isCenter() && $center)
                    <x-badge :value="$center->status ?? 'Inactive'"
                        class="badge-{{ $center->status === 'active' ? 'success' : 'warning' }}" />
                @endif
            </div>
        </div>

        <!-- Breadcrumbs -->
        <div class="mt-2 text-sm breadcrumbs">
            <ul>
                <li>
                    <a href="{{ route('admin.index') }}" wire:navigate class="text-primary hover:text-primary-focus">
                        Dashboard
                    </a>
                </li>
                <li class="text-gray-500">Profile Settings</li>
            </ul>
        </div>
    </div>
    <hr class="mb-4">

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <!-- Profile Information Card -->
        <div class="lg:col-span-2">
            <x-card title="Personal Information" subtitle="Update your personal details">
                <x-form wire:submit="save">
                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                        <div>
                            <x-input label="Full Name" wire:model="name" placeholder="Enter your full name" required />
                        </div>
                        <div>
                            <x-input label="Phone Number" wire:model="phone" placeholder="Enter your phone number" />
                        </div>
                    </div>

                    <div class="mt-6">
                        <x-input label="Email Address" wire:model="email" type="email"
                            placeholder="Enter your email address" :readonly="hasAuthRole(RolesEnum::Center->value)" :disabled="hasAuthRole(RolesEnum::Center->value)" />
                        @if (hasAuthRole(RolesEnum::Center->value))
                            <x-alert class="mt-1 text-sm text-warning">
                                <x-icon name="o-exclamation-triangle" class="w-4 h-4" />
                                Email changes are restricted to administrators only
                            </x-alert>
                        @endif
                    </div>

                    <x-slot:actions>
                        <x-button label="Update Profile" class="btn-primary" type="submit" spinner="save"
                            icon="o-check" />
                    </x-slot:actions>
                </x-form>
            </x-card>
        </div>

        <!-- Profile Picture & Center Info -->
        <div class="space-y-6">
            <!-- Profile Picture Card -->
            <x-card title="Profile Picture" subtitle="Upload your avatar">
                <div class="w-full flex justify-center">
                    <x-file wire:model="image" accept="image" crop-after-change :crop-config="$config">
                        <div class="mt-4 text-center">
                            <img id="imagePreview"
                                src="{{ $user->image ? asset($user->image) : 'https://placehold.co/300' }}"
                                class="mx-auto h-32 w-32 rounded-full object-cover border-4 border-gray-200 dark:border-gray-700"
                                alt="Profile Picture">
                        </div>
                    </x-file>
                </div>
            </x-card>

            <!-- Center Information (for Center users) -->
            @if ($user->isCenter() && $center)
                <x-card title="Center Information" subtitle="Your center details">
                    <div class="space-y-4">
                        <div>
                            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Center Name</label>
                            <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ $center->name ?? 'N/A' }}</p>
                        </div>

                        <div>
                            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Center ID</label>
                            <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ $center->uid ?? 'N/A' }}</p>
                        </div>

                        <div>
                            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Address</label>
                            <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ $center->address ?? 'N/A' }}</p>
                        </div>

                        <div>
                            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Status</label>
                            <x-badge :value="$center->status ?? 'Inactive'"
                                class="badge-{{ $center->status === 'active' ? 'success' : 'warning' }}" />
                        </div>
                    </div>
                </x-card>
            @endif

            <!-- Account Actions Card -->
            <x-card title="Account Actions" subtitle="Manage your account">
                <div class="space-y-3">
                    <x-button label="Change Password" class="btn-outline btn-sm w-full" icon="o-key"
                        wire:click="openChangePasswordModal" />

                    @if ($user->isAdmin())
                        <x-button label="System Settings" class="btn-outline btn-sm w-full" icon="o-cog-6-tooth" />
                    @endif
                </div>
            </x-card>
        </div>
    </div>

    <!-- Change Password Modal -->
    <x-modal wire:model="showChangePasswordModal" title="Change Password" class="backdrop-blur" separator>
        <x-form wire:submit.prevent="changePassword">
            <div class="space-y-4">
                <x-input label="Current Password" wire:model.defer="currentPassword" type="password"
                    placeholder="Enter your current password" required />

                <x-input label="New Password" wire:model.defer="newPassword" type="password"
                    placeholder="Enter your new password" required />

                <x-input label="Confirm New Password" wire:model.defer="confirmPassword" type="password"
                    placeholder="Confirm your new password" required />

                <x-alert class="text-sm text-gray-500">
                    <x-icon name="o-information-circle" class="w-4 h-4" />
                    Password must be at least 8 characters long and different from your current password.
                </x-alert>
            </div>

            <x-slot:actions>
                <x-button label="Cancel" @click="$wire.showChangePasswordModal = false" />
                <x-button label="Change Password" class="btn-primary" type="submit" spinner="changePassword" />
            </x-slot:actions>
        </x-form>
    </x-modal>
</div>
