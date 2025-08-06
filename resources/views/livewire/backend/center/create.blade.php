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

    // Form properties
    #[Title('Create Center')]
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

    // Validation rules
    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:255|unique:centers,name',
            'phone' => 'required|string|max:20',
            'address' => 'required|string|max:150',
            'state' => 'required|string|max:100',
            'country' => 'required|string|max:100',
            'email' => 'required|email|max:180|unique:users,email',
            'owner_name' => 'required|string|max:100',
            'aadhar' => 'required|string|regex:/^[0-9]{12}$/|unique:centers,aadhar',
            'pan' => 'required|string|max:10|min:10|regex:/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/|unique:centers,pan',
            'institute_logo' => 'required|image|max:2048',
            'front_office_photo' => 'required|image|max:2048',
            'back_office_photo' => 'required|image|max:2048',
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

    // Save center
    public function save(): void
    {
        $this->validate();

        try {
            $user = User::where('email', $this->email)->first();

            if (!$user) {
                $user = User::create([
                    'name' => $this->owner_name,
                    'email' => $this->email,
                    'phone' => $this->phone,
                    'password' => bcrypt(Str::random(10)),
                ]);

                $user->assignRole(RolesEnum::Center->value);
            }

            $data = [
                'user_id' => $user->id,
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

            if ($this->institute_logo) {
                $data['institute_logo'] = $this->institute_logo->store('centers/logos', 'public');
            }

            if ($this->front_office_photo) {
                $data['front_office_photo'] = $this->front_office_photo->store('centers/photos', 'public');
            }

            if ($this->back_office_photo) {
                $data['back_office_photo'] = $this->back_office_photo->store('centers/photos', 'public');
            }

            Center::create($data);

            $this->success('Center created successfully!', position: 'toast-bottom');
            $this->redirect(route('admin.center.index'));
        } catch (\Exception $e) {
            $this->error('Failed to create center. Please try again.', position: 'toast-bottom');
        }
    }

    // Reset form
    public function resetForm(): void
    {
        $this->reset();
        $this->resetValidation();
        $this->success('Form reset successfully!', position: 'toast-bottom');
    }

    // Remove uploaded file
    public function removeFile($property): void
    {
        $this->$property = null;
        $this->success('File removed successfully!', position: 'toast-bottom');
    }

    public function rendering()
    {
        if ($this->pan) {
            $this->pan = strtoupper($this->pan);
        }
    }
}; ?>

<div>
    <!-- Header -->
    <div class="flex justify-between items-start lg:items-center flex-col lg:flex-row mt-3 mb-5 gap-2">
        <div>
            <h1 class="text-2xl font-bold">
                Create Center
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
                        Create Center
                    </li>
                </ul>
            </div>
        </div>
        <div class="flex gap-3">
            <x-button label="Reset Form" icon="o-arrow-path" class="btn-outline" wire:click="resetForm" responsive />
            <x-button label="Back to Centers" icon="o-arrow-left" class="btn-primary btn-outline"
                link="{{ route('admin.center.index') }}" responsive />
        </div>
    </div>

    <hr class="mb-5">

    <!-- Form -->
    <x-card shadow>
        <form wire:submit="save" class="space-y-6">
            <!-- Basic Information -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="md:col-span-2">
                    <h3 class="text-lg font-semibold text-primary">Basic Information</h3>
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
                    <h3 class="text-lg font-semibold text-primary">Owner Information</h3>
                </div>

                <x-input label="Owner Name" wire:model="owner_name" placeholder="Enter owner name" icon="o-user" />

                <x-input label="Aadhar Number" wire:model="aadhar" placeholder="Enter Aadhar number"
                    icon="o-identification" inputmode="numeric" />

                <x-input label="PAN Number" wire:model.live.debounce.350ms="pan" placeholder="Enter PAN number"
                    icon="o-credit-card" maxlength="10" />
            </div>

            <!-- File Uploads -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="md:col-span-2">
                    <h3 class="text-lg font-semibold text-primary">Images & Documents</h3>
                </div>

                <!-- Institute Logo -->
                <div class="space-y-2">
                    <label class="label">
                        <span class="label-text font-medium">Institute Logo <span
                                class="text-xs text-warning">(300x300)</span></span>
                    </label>

                    <x-file wire:model="institute_logo" accept="image/*" placeholder="Upload institute logo"
                        icon="o-photo">
                        <img src="https://placehold.co/300x300?text=Logo" alt="Institute Logo"
                            class="w-32 h-32 object-cover rounded-lg">
                    </x-file>
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
                <x-button label="Create" icon="o-plus" class="btn-primary btn-sm btn-soft" type="submit"
                    spinner="save" />
            </div>
        </form>
    </x-card>
</div>
