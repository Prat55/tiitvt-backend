<?php

use App\Models\User;
use App\Models\Center;
use Mary\Traits\Toast;
use App\Enums\RolesEnum;
use Illuminate\Support\Str;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\{Layout, Title};

new class extends Component {
    use WithFileUploads, Toast;
    // Center model instance
    #[Title('Edit Center')]
    public $center;

    public string $name = '';
    public string $phone = '';
    public string $address = '';
    public string $state = '';
    public string $country = '';
    public string $email = '';
    public string $owner_name = '';
    public string $aadhar = '';
    public string $pan = '';

    // File uploads
    public $institute_logo;
    public $front_office_photo;
    public $back_office_photo;

    // Existing file paths
    public $existing_institute_logo;
    public $existing_front_office_photo;
    public $existing_back_office_photo;

    // Mount method to load center data
    public function mount($uid): void
    {
        $this->center = Center::whereUid($uid)->first();
        if (!$this->center) {
            $this->error('Center not found!', position: 'toast-bottom', redirect: route('admin.center.index'));
            return;
        }

        $this->loadCenterData();
    }

    // Load center data into form
    public function loadCenterData(): void
    {
        $this->name = $this->center->name;
        $this->phone = $this->center->phone;
        $this->address = $this->center->address;
        $this->state = $this->center->state;
        $this->country = $this->center->country;
        $this->email = $this->center->email;
        $this->owner_name = $this->center->owner_name;
        $this->aadhar = $this->center->aadhar;
        $this->pan = $this->center->pan;

        // Set existing file paths
        $this->existing_institute_logo = $this->center->institute_logo;
        $this->existing_front_office_photo = $this->center->front_office_photo;
        $this->existing_back_office_photo = $this->center->back_office_photo;
    }

    // Validation rules
    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:255|unique:centers,name,' . $this->center->id,
            'phone' => 'required|string|max:20',
            'address' => 'required|string|max:150',
            'state' => 'required|string|max:100',
            'country' => 'required|string|max:100',
            'email' => 'required|email|max:180|unique:users,email,' . $this->center->user_id,
            'owner_name' => 'required|string|max:100',
            'aadhar' => 'required|string|regex:/^[0-9]{12}$/|unique:centers,aadhar,' . $this->center->id,
            'pan' => 'required|string|max:10|min:10|regex:/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/|unique:centers,pan,' . $this->center->id,
            'institute_logo' => 'nullable|image|max:2048',
            'front_office_photo' => 'nullable|image|max:2048',
            'back_office_photo' => 'nullable|image|max:2048',
        ];
    }

    // Validation messages
    protected function messages(): array
    {
        return [
            'name.required' => 'Center name is required.',
            'name.max' => 'Center name cannot exceed 255 characters.',
            'name.unique' => 'Center name already exists.',
        ];
    }

    // Update center
    public function update(): void
    {
        $this->validate();

        try {
            // Update user information
            $user = $this->center->user;
            $user->update([
                'name' => $this->owner_name,
                'email' => $this->email,
                'phone' => $this->phone,
            ]);

            $data = [
                'name' => $this->name,
                'phone' => $this->phone,
                'address' => $this->address,
                'state' => $this->state,
                'country' => $this->country,
                'email' => $this->email,
                'owner_name' => $this->owner_name,
                'aadhar' => $this->aadhar,
                'pan' => $this->pan,
            ];

            // Handle file uploads
            if ($this->institute_logo) {
                // Delete old file if exists
                if ($this->existing_institute_logo) {
                    Storage::disk('public')->delete($this->existing_institute_logo);
                }
                $data['institute_logo'] = $this->institute_logo->store('centers/logos', 'public');
            }

            if ($this->front_office_photo) {
                // Delete old file if exists
                if ($this->existing_front_office_photo) {
                    Storage::disk('public')->delete($this->existing_front_office_photo);
                }
                $data['front_office_photo'] = $this->front_office_photo->store('centers/photos', 'public');
            }

            if ($this->back_office_photo) {
                // Delete old file if exists
                if ($this->existing_back_office_photo) {
                    Storage::disk('public')->delete($this->existing_back_office_photo);
                }
                $data['back_office_photo'] = $this->back_office_photo->store('centers/photos', 'public');
            }

            $this->center->update($data);

            $this->success('Center updated successfully!', position: 'toast-bottom');
            $this->redirect(route('admin.center.index'));
        } catch (\Exception $e) {
            $this->error('Failed to update center. Please try again.', position: 'toast-bottom');
        }
    }

    // Reset form to original values
    public function resetForm(): void
    {
        $this->loadCenterData();
        $this->resetValidation();
        $this->institute_logo = null;
        $this->front_office_photo = null;
        $this->back_office_photo = null;
        $this->success('Form reset to original values!', position: 'toast-bottom');
    }

    // Remove uploaded file
    public function removeFile($property): void
    {
        $this->$property = null;
        $this->success('File removed successfully!', position: 'toast-bottom');
    }

    // Delete existing file
    public function deleteExistingFile($property): void
    {
        $filePath = $this->{'existing_' . $property};
        if ($filePath && Storage::disk('public')->exists($filePath)) {
            Storage::disk('public')->delete($filePath);
            $this->center->update([$property => null]);
            $this->{'existing_' . $property} = null;
            $this->success('File deleted successfully!', position: 'toast-bottom');
        }
    }
}; ?>

<div>
    <!-- Header -->
    <div class="flex justify-between items-start lg:items-center flex-col lg:flex-row mt-3 mb-5 gap-2">
        <div>
            <h1 class="text-2xl font-bold">
                Edit Center: {{ $center->name }}
            </h1>
            <div class="breadcrumbs text-sm">
                <ul class="flex">
                    <li>
                        <a href="{{ route('admin.index') }}" wire:navigate>
                            Dashboard
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('admin.center.index') }}" wire:navigate>
                            Centers
                        </a>
                    </li>
                    <li>
                        Edit Center
                    </li>
                </ul>
            </div>
        </div>
        <div class="flex gap-3">
            <x-button label="Reset Form" icon="o-arrow-path" class="btn-outline" wire:click="resetForm" />
            <x-button label="Back to Centers" icon="o-arrow-left" class="btn-primary btn-outline"
                link="{{ route('admin.center.index') }}" />
        </div>
    </div>

    <hr class="mb-5">

    <!-- Form -->
    <x-card shadow>
        <form wire:submit="update" class="space-y-6">
            <!-- Basic Information -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="md:col-span-2">
                    <h3 class="text-lg font-semibold mb-4 text-primary">Basic Information</h3>
                </div>

                <x-input label="Center Name" wire:model="name" placeholder="Enter center name"
                    icon="o-building-office" />

                <x-input label="Phone Number" wire:model="phone" placeholder="Enter phone number" icon="o-phone" />

                <x-input label="Street Address" wire:model="address" placeholder="Enter complete address"
                    icon="o-map-pin" />

                <x-input label="State" wire:model="state" placeholder="Enter state" icon="o-map" />

                <x-input label="Country" wire:model="country" placeholder="Enter country" icon="o-flag" />

                <x-input label="Email" wire:model="email" placeholder="Enter email address" icon="o-envelope"
                    type="email" />
            </div>

            <!-- Owner Information -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="md:col-span-2">
                    <h3 class="text-lg font-semibold mb-4 text-primary">Owner Information</h3>
                </div>

                <x-input label="Owner Name" wire:model="owner_name" placeholder="Enter owner name" icon="o-user" />

                <x-input label="Aadhar Number" wire:model="aadhar" placeholder="Enter Aadhar number"
                    icon="o-identification" />

                <x-input label="PAN Number" wire:model="pan" placeholder="Enter PAN number" icon="o-credit-card"
                    maxlength="10" />
            </div>

            <!-- File Uploads -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="md:col-span-2">
                    <h3 class="text-lg font-semibold mb-4 text-primary">Images & Documents</h3>
                </div>

                <!-- Institute Logo -->
                <div class="space-y-2">
                    <label class="label">
                        <span class="label-text font-medium">Institute Logo <span
                                class="text-xs text-warning">(300x300)</span></span>
                    </label>

                    @if ($institute_logo)
                        <div class="relative">
                            <img src="{{ $institute_logo->temporaryUrl() }}" alt="Preview"
                                class="w-32 h-32 object-cover rounded-lg">
                            <x-button icon="o-x-mark" class="btn-circle btn-sm btn-error absolute -top-2 -right-2"
                                wire:click="removeFile('institute_logo')" />
                        </div>
                    @elseif ($existing_institute_logo)
                        <div class="relative">
                            <img src="{{ asset('storage/' . $existing_institute_logo) }}" alt="Current Logo"
                                class="w-32 h-32 object-cover rounded-lg">
                            <x-button icon="o-trash" class="btn-circle btn-sm btn-error absolute -top-2 -right-2"
                                wire:click="deleteExistingFile('institute_logo')" />
                        </div>
                    @else
                        <x-file wire:model="institute_logo" accept="image/*" placeholder="Upload institute logo"
                            icon="o-photo">
                            <img src="https://placehold.co/300x300?text=Logo" alt="Institute Logo"
                                class="w-32 h-32 object-cover rounded-lg">
                        </x-file>
                    @endif
                </div>

                <div class="space-y-2">
                    <!-- Front Office Photo -->
                    <div>
                        <label class="label">
                            <span class="label-text font-medium">Front Office Photo</span>
                        </label>
                        @if ($front_office_photo)
                            <div class="relative">
                                <img src="{{ $front_office_photo->temporaryUrl() }}" alt="Preview"
                                    class="w-80 h-full object-cover rounded-lg">
                                <x-button icon="o-x-mark" class="btn-circle btn-sm btn-error absolute -top-2 -right-2"
                                    wire:click="removeFile('front_office_photo')" />
                            </div>
                        @elseif ($existing_front_office_photo)
                            <div class="relative">
                                <img src="{{ asset('storage/' . $existing_front_office_photo) }}"
                                    alt="Current Front Office" class="w-80 h-full object-cover rounded-lg">
                                <x-button icon="o-trash" class="btn-circle btn-sm btn-error absolute -top-2 -right-2"
                                    wire:click="deleteExistingFile('front_office_photo')" />
                            </div>
                        @else
                            <x-file wire:model="front_office_photo" accept="image/*"
                                placeholder="Upload front office photo" icon="o-photo" />
                        @endif
                    </div>

                    <!-- Back Office Photo -->
                    <div class="mt-4">
                        <label class="label">
                            <span class="label-text font-medium">Back Office Photo</span>
                        </label>
                        @if ($back_office_photo)
                            <div class="relative">
                                <img src="{{ $back_office_photo->temporaryUrl() }}" alt="Preview"
                                    class="w-80 h-full object-cover rounded-lg">
                                <x-button icon="o-x-mark" class="btn-circle btn-sm btn-error absolute -top-2 -right-2"
                                    wire:click="removeFile('back_office_photo')" />
                            </div>
                        @elseif ($existing_back_office_photo)
                            <div class="relative">
                                <img src="{{ asset('storage/' . $existing_back_office_photo) }}"
                                    alt="Current Back Office" class="w-80 h-full object-cover rounded-lg">
                                <x-button icon="o-trash" class="btn-circle btn-sm btn-error absolute -top-2 -right-2"
                                    wire:click="deleteExistingFile('back_office_photo')" />
                            </div>
                        @else
                            <x-file wire:model="back_office_photo" accept="image/*"
                                placeholder="Upload back office photo" icon="o-photo" />
                        @endif
                    </div>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="flex justify-end gap-3 pt-6 border-t">
                <x-button label="Cancel" icon="o-x-mark" class="btn-error btn-soft btn-sm"
                    link="{{ route('admin.center.index') }}" />
                <x-button label="Update Center" icon="o-check" class="btn-primary btn-sm btn-soft" type="submit"
                    spinner="update" />
            </div>
        </form>
    </x-card>
</div>
