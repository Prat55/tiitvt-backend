<?php

use Mary\Traits\Toast;
use App\Models\WebsiteSetting;
use App\Services\WebsiteSettingsService;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Livewire\Volt\Component;
use Livewire\Attributes\Title;
use Livewire\WithFileUploads;

new class extends Component {
    use Toast, WithFileUploads;

    // General Settings
    #[Title('Website Settings')]
    public $website_name;
    public $meta_title;
    public $meta_keywords;
    public $meta_description;
    public $meta_author;

    // Image Settings
    public $logo;
    public $logo_dark;
    public $favicon;
    public $qr_code_image;

    // Contact Settings
    public $primary_email;
    public $secondary_email;
    public $primary_phone;
    public $secondary_phone;
    public $address;

    // Social Media Settings
    public $facebook_url;
    public $twitter_url;
    public $instagram_url;
    public $linkedin_url;

    // Modal states
    public $showGeneralModal = false;
    public $showImageModal = false;
    public $showContactModal = false;
    public $showSocialModal = false;

    public $settings;

    public $cropConfig = [
        'aspectRatio' => 1,
    ];

    public $cropConfigLogo = [
        'aspectRatio' => 16 / 9,
    ];

    public function mount(): void
    {
        $this->loadSettings();
    }

    public function loadSettings(): void
    {
        $this->settings = WebsiteSetting::first();

        if ($this->settings) {
            $this->website_name = $this->settings->website_name;
            $this->logo = $this->settings->logo;
            $this->logo_dark = $this->settings->logo_dark;
            $this->favicon = $this->settings->favicon;
            $this->qr_code_image = $this->settings->qr_code_image;
            $this->meta_title = $this->settings->meta_title;
            $this->meta_keywords = $this->settings->meta_keywords;
            $this->meta_description = $this->settings->meta_description;
            $this->meta_author = $this->settings->meta_author;
            $this->primary_email = $this->settings->primary_email;
            $this->secondary_email = $this->settings->secondary_email;
            $this->primary_phone = $this->settings->primary_phone;
            $this->secondary_phone = $this->settings->secondary_phone;
            $this->address = $this->settings->address;
            $this->facebook_url = $this->settings->facebook_url;
            $this->twitter_url = $this->settings->twitter_url;
            $this->instagram_url = $this->settings->instagram_url;
            $this->linkedin_url = $this->settings->linkedin_url;
        }
    }

    public function openGeneralModal(): void
    {
        $this->showGeneralModal = true;
    }

    public function openImageModal(): void
    {
        $this->showImageModal = true;
    }

    public function openContactModal(): void
    {
        $this->showContactModal = true;
    }

    public function openSocialModal(): void
    {
        $this->showSocialModal = true;
    }

    public function updateGeneralSettings(): void
    {
        $this->validate([
            'website_name' => 'required|string|max:255',
            'meta_title' => 'required|string|max:255',
            'meta_keywords' => 'nullable|string|max:500',
            'meta_description' => 'nullable|string|max:500',
            'meta_author' => 'nullable|string|max:255',
        ]);

        $this->updateSettings([
            'website_name' => $this->website_name,
            'meta_title' => $this->meta_title,
            'meta_keywords' => $this->meta_keywords,
            'meta_description' => $this->meta_description,
            'meta_author' => $this->meta_author,
        ]);

        $this->showGeneralModal = false;
        $this->success('General settings updated successfully!', position: 'toast-bottom');
    }

    public function updateImageSettings(): void
    {
        $this->validate([
            'logo' => 'nullable|image|max:2048',
            'logo_dark' => 'nullable|image|max:2048',
            'favicon' => 'nullable|image|max:1024',
            'qr_code_image' => 'nullable|image|max:2048',
        ]);

        $data = [];

        if ($this->logo) {
            $data['logo'] = $this->uploadFile($this->logo, 'logo');
        }

        if ($this->logo_dark) {
            $data['logo_dark'] = $this->uploadFile($this->logo_dark, 'logo_dark');
        }

        if ($this->favicon) {
            $data['favicon'] = $this->uploadFile($this->favicon, 'favicon');
        }

        if ($this->qr_code_image) {
            $data['qr_code_image'] = $this->uploadFile($this->qr_code_image, 'qr_code');
        }

        if (!empty($data)) {
            $this->updateSettings($data);
            $this->loadSettings();

            // Clear website settings cache
            app(WebsiteSettingsService::class)->clearCache();
        }

        $this->showImageModal = false;
        $this->success('Images updated successfully!', position: 'toast-bottom');
    }

    private function uploadFile($file, $type): string
    {
        $filename = $type . '_' . time() . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs('website-images', $filename, 'public');
        return $path;
    }

    public function updateContactSettings(): void
    {
        $this->validate([
            'primary_email' => 'required|email|max:255',
            'secondary_email' => 'nullable|email|max:255',
            'primary_phone' => 'required|string|max:20',
            'secondary_phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:1000',
        ]);

        $this->updateSettings([
            'primary_email' => $this->primary_email,
            'secondary_email' => $this->secondary_email,
            'primary_phone' => $this->primary_phone,
            'secondary_phone' => $this->secondary_phone,
            'address' => $this->address,
        ]);

        $this->showContactModal = false;
        $this->success('Contact settings updated successfully!', position: 'toast-bottom');
    }

    public function updateSocialSettings(): void
    {
        $this->validate([
            'facebook_url' => 'nullable|url|max:255',
            'twitter_url' => 'nullable|url|max:255',
            'instagram_url' => 'nullable|url|max:255',
            'linkedin_url' => 'nullable|url|max:255',
        ]);

        $this->updateSettings([
            'facebook_url' => $this->facebook_url,
            'twitter_url' => $this->twitter_url,
            'instagram_url' => $this->instagram_url,
            'linkedin_url' => $this->linkedin_url,
        ]);

        $this->showSocialModal = false;
        $this->success('Social media settings updated successfully!', position: 'toast-bottom');
    }

    private function updateSettings(array $data): void
    {
        if ($this->settings) {
            $this->settings->update($data);
        } else {
            WebsiteSetting::create($data);
            $this->loadSettings();
        }
    }
}; ?>
@section('cdn')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.css" />
@endsection
<div>
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold">Website Settings</h1>
            <p class="text-gray-600 mt-1 dark:text-gray-400">Manage your website configuration and information</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- General Settings Card -->
        <div
            class="bg-base-200 rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow dark:border-gray-700">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center space-x-3">
                    <div class="p-2 bg-blue-100 rounded-lg">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold">General Settings</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Basic website information</p>
                    </div>
                </div>
                <x-button icon="o-pencil" class="btn-primary btn-outline btn-sm" wire:click="openGeneralModal" />
            </div>

            <div class="space-y-3">
                <div>
                    <span class="text-sm font-medium text-gray-500">Website Name</span>
                    <p class="text-sm truncate">{{ $website_name ?: 'Not set' }}</p>
                </div>
                <div>
                    <span class="text-sm font-medium text-gray-500">Meta Title</span>
                    <p class="text-sm truncate">{{ $meta_title ?: 'Not set' }}</p>
                </div>
                <div>
                    <span class="text-sm font-medium text-gray-500">Meta Author</span>
                    <p class="text-sm truncate">{{ $meta_author ?: 'Not set' }}</p>
                </div>
            </div>
        </div>

        <!-- Image Settings Card -->
        <div
            class="bg-base-200 rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow dark:border-gray-700">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center space-x-3">
                    <div class="p-2 bg-orange-100 rounded-lg">
                        <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z">
                            </path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold">Images & Branding</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Logo, favicon & QR code</p>
                    </div>
                </div>
                <x-button icon="o-pencil" class="btn-primary btn-outline btn-sm" wire:click="openImageModal" />
            </div>

            <div class="space-y-3">
                <div class="flex items-center space-x-3">
                    <div class="w-8 h-8 bg-gray-200 rounded flex items-center justify-center">
                        @if ($logo)
                            <img src="{{ Storage::url($logo) }}" alt="Logo"
                                class="w-full h-full object-contain rounded">
                        @else
                            <svg class="w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 24 24">
                                <path
                                    d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z" />
                            </svg>
                        @endif
                    </div>
                    <div class="flex-1">
                        <span class="text-sm font-medium text-gray-500">Logo</span>
                        <p class="text-sm truncate">{{ $logo ? 'Uploaded' : 'Not set' }}</p>
                    </div>
                </div>
                <div class="flex items-center space-x-3">
                    <div class="w-8 h-8 bg-gray-200 rounded flex items-center justify-center">
                        @if ($logo_dark)
                            <img src="{{ Storage::url($logo_dark) }}" alt="Dark Logo"
                                class="w-full h-full object-contain rounded">
                        @else
                            <svg class="w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 24 24">
                                <path
                                    d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z" />
                            </svg>
                        @endif
                    </div>
                    <div class="flex-1">
                        <span class="text-sm font-medium text-gray-500">Dark Logo</span>
                        <p class="text-sm truncate">{{ $logo_dark ? 'Uploaded' : 'Not set' }}</p>
                    </div>
                </div>
                <div class="flex items-center space-x-3">
                    <div class="w-8 h-8 bg-gray-200 rounded flex items-center justify-center">
                        @if ($favicon)
                            <img src="{{ Storage::url($favicon) }}" alt="Favicon"
                                class="w-full h-full object-contain rounded">
                        @else
                            <svg class="w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 24 24">
                                <path
                                    d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z" />
                            </svg>
                        @endif
                    </div>
                    <div class="flex-1">
                        <span class="text-sm font-medium text-gray-500">Favicon</span>
                        <p class="text-sm truncate">{{ $favicon ? 'Uploaded' : 'Not set' }}</p>
                    </div>
                </div>
                <div class="flex items-center space-x-3">
                    <div class="w-8 h-8 bg-gray-200 rounded flex items-center justify-center">
                        @if ($qr_code_image)
                            <img src="{{ Storage::url($qr_code_image) }}" alt="QR Code Image"
                                class="w-full h-full object-contain rounded">
                        @else
                            <svg class="w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 24 24">
                                <path
                                    d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z" />
                            </svg>
                        @endif
                    </div>
                    <div class="flex-1">
                        <span class="text-sm font-medium text-gray-500">QR Code Image</span>
                        <p class="text-sm truncate">{{ $qr_code_image ? 'Uploaded' : 'Not set' }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Contact Settings Card -->
        <div
            class="bg-base-200 rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow dark:border-gray-700">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center space-x-3">
                    <div class="p-2 bg-green-100 rounded-lg">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z">
                            </path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold">Contact Information</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Email, phone & address</p>
                    </div>
                </div>
                <x-button icon="o-pencil" class="btn-primary btn-outline btn-sm" wire:click="openContactModal" />
            </div>

            <div class="space-y-3">
                <div>
                    <span class="text-sm font-medium text-gray-500">Primary Email</span>
                    <p class="text-sm truncate">{{ $primary_email ?: 'Not set' }}</p>
                </div>
                <div>
                    <span class="text-sm font-medium text-gray-500">Primary Phone</span>
                    <p class="text-sm truncate">{{ $primary_phone ?: 'Not set' }}</p>
                </div>
                <div>
                    <span class="text-sm font-medium text-gray-500">Address</span>
                    <p class="text-sm truncate">{{ $address ?: 'Not set' }}</p>
                </div>
            </div>
        </div>

        <!-- Social Media Settings Card -->
        <div
            class="bg-base-200 rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow dark:border-gray-700">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center space-x-3">
                    <div class="p-2 bg-purple-100 rounded-lg">
                        <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M7 4V2a1 1 0 011-1h8a1 1 0 011 1v2m-9 0h10m-10 0a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V6a2 2 0 00-2-2M9 12l2 2 4-4">
                            </path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold">Social Media</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Social media links</p>
                    </div>
                </div>
                <x-button icon="o-pencil" class="btn-primary btn-outline btn-sm" wire:click="openSocialModal" />
            </div>

            <div class="space-y-3">
                <div class="flex items-center space-x-2">
                    <x-icon name="fab.facebook" class="w-4 h-4 text-blue-600" />
                    <div class="flex-1">
                        <span class="text-sm font-medium text-gray-500">Facebook</span>
                        <p class="text-sm truncate">{{ $facebook_url ?: 'Not set' }}</p>
                    </div>
                </div>
                <div class="flex items-center space-x-2">
                    <x-icon name="fab.x-twitter" class="w-4 h-4 text-blue-500" />
                    <div class="flex-1">
                        <span class="text-sm font-medium text-gray-500">Twitter</span>
                        <p class="text-sm truncate">{{ $twitter_url ?: 'Not set' }}</p>
                    </div>
                </div>
                <div class="flex items-center space-x-2">
                    <x-icon name="fab.instagram" class="w-4 h-4 text-pink-600" />
                    <div class="flex-1">
                        <span class="text-sm font-medium text-gray-500">Instagram</span>
                        <p class="text-sm truncate">{{ $instagram_url ?: 'Not set' }}</p>
                    </div>
                </div>
                <div class="flex items-center space-x-2">
                    <x-icon name="fab.linkedin" class="w-4 h-4 text-blue-700" />
                    <div class="flex-1">
                        <span class="text-sm font-medium text-gray-500">LinkedIn</span>
                        <p class="text-sm truncate">{{ $linkedin_url ?: 'Not set' }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Image Settings Modal -->
    <x-modal wire:model="showImageModal" title="Upload Images" class="backdrop-blur" separator>
        <x-form wire:submit.prevent="updateImageSettings">
            <div class="space-y-4 grid grid-cols-2 gap-4">
                <x-file label="Website Logo" wire:model.defer="logo" accept="image/*" crop-after-change
                    :crop-config="$cropConfigLogo">
                    <img src="{{ $settings->logo ? asset($settings->logo) : 'https://placehold.co/200x50' }}"
                        alt="Website Logo" class="h-30 object-cover rounded-lg">
                </x-file>

                <x-file label="Dark Theme Logo" wire:model.defer="logo_dark" accept="image/*" crop-after-change
                    :crop-config="$cropConfigLogo">
                    <img src="{{ $settings->logo_dark ? asset($settings->logo_dark) : 'https://placehold.co/200x50' }}"
                        alt="Dark Theme Logo" class="h-30 object-cover rounded-lg">
                </x-file>

                <x-file label="Favicon" wire:model.defer="favicon" accept="image/*" crop-after-change
                    :crop-config="$cropConfig">
                    <img src="{{ $settings->favicon ? asset($settings->favicon) : 'https://placehold.co/300' }}"
                        alt="Favicon" class="w-32 h-32 object-cover rounded-lg">
                </x-file>

                <x-file label="QR Code Image" wire:model.defer="qr_code_image" accept="image/*" crop-after-change
                    :crop-config="$cropConfig">
                    <img src="{{ $settings->qr_code_image ? asset($settings->qr_code_image) : 'https://placehold.co/300' }}"
                        alt="QR Code Image" class="w-32 h-32 object-cover rounded-lg">
                </x-file>

                <div class="text-sm text-gray-500 mt-4 col-span-2">
                    <p><strong>Recommended sizes:</strong></p>
                    <ul class="list-disc ml-4 mt-2">
                        <li>Logo: 200x50 pixels (SVG or PNG)</li>
                        <li>Dark Logo: Same as logo, dark theme version</li>
                        <li>Favicon: 32x32 pixels (ICO or PNG)</li>
                        <li>QR Code: 300x300 pixels (PNG or JPG)</li>
                    </ul>
                </div>
            </div>

            <x-slot:actions>
                <x-button label="Cancel" @click="$wire.showImageModal = false" />
                <x-button label="Upload Images" class="btn-primary" type="submit" spinner="updateImageSettings" />
            </x-slot:actions>
        </x-form>
    </x-modal>

    <!-- General Settings Modal -->
    <x-modal wire:model="showGeneralModal" title="Edit General Settings" class="backdrop-blur" separator>
        <x-form wire:submit.prevent="updateGeneralSettings">
            <div class="space-y-4">
                <x-input label="Website Name" wire:model.defer="website_name" placeholder="Enter website name" />
                <x-input label="Meta Title" wire:model.defer="meta_title" placeholder="Enter meta title" />
                <x-textarea label="Meta Keywords" wire:model.defer="meta_keywords"
                    placeholder="Enter meta keywords (comma separated)" />
                <x-textarea label="Meta Description" wire:model.defer="meta_description"
                    placeholder="Enter meta description" />
                <x-input label="Meta Author" wire:model.defer="meta_author" placeholder="Enter meta author" />
            </div>

            <x-slot:actions>
                <x-button label="Cancel" @click="$wire.showGeneralModal = false" />
                <x-button label="Update" class="btn-primary" type="submit" spinner="updateGeneralSettings" />
            </x-slot:actions>
        </x-form>
    </x-modal>

    <!-- Contact Settings Modal -->
    <x-modal wire:model="showContactModal" title="Edit Contact Information" class="backdrop-blur" separator>
        <x-form wire:submit.prevent="updateContactSettings">
            <div class="space-y-4">
                <x-input label="Primary Email" wire:model.defer="primary_email" placeholder="Enter primary email"
                    type="email" />
                <x-input label="Secondary Email" wire:model.defer="secondary_email"
                    placeholder="Enter secondary email" type="email" />
                <x-input label="Primary Phone" wire:model.defer="primary_phone" placeholder="Enter primary phone" />
                <x-input label="Secondary Phone" wire:model.defer="secondary_phone"
                    placeholder="Enter secondary phone" />
                <x-textarea label="Address" wire:model.defer="address" placeholder="Enter full address" />
            </div>

            <x-slot:actions>
                <x-button label="Cancel" @click="$wire.showContactModal = false" />
                <x-button label="Update" class="btn-primary" type="submit" spinner="updateContactSettings" />
            </x-slot:actions>
        </x-form>
    </x-modal>

    <!-- Social Media Settings Modal -->
    <x-modal wire:model="showSocialModal" title="Edit Social Media Links" class="backdrop-blur" separator>
        <x-form wire:submit.prevent="updateSocialSettings">
            <div class="space-y-4">
                <x-input label="Facebook URL" wire:model.defer="facebook_url"
                    placeholder="https://facebook.com/yourpage" />
                <x-input label="Twitter URL" wire:model.defer="twitter_url"
                    placeholder="https://twitter.com/yourpage" />
                <x-input label="Instagram URL" wire:model.defer="instagram_url"
                    placeholder="https://instagram.com/yourpage" />
                <x-input label="LinkedIn URL" wire:model.defer="linkedin_url"
                    placeholder="https://linkedin.com/company/yourcompany" />
            </div>

            <x-slot:actions>
                <x-button label="Cancel" @click="$wire.showSocialModal = false" />
                <x-button label="Update" class="btn-primary" type="submit" spinner="updateSocialSettings" />
            </x-slot:actions>
        </x-form>
    </x-modal>
</div>
