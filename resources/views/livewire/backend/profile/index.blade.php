<?php

use Livewire\Volt\Component;
use App\Models\User;
use Mary\Traits\Toast;
use Livewire\WithFileUploads;
use Livewire\Attributes\Title;
use Illuminate\View\View;
use App\Enums\RolesEnum;

new class extends Component {
    use WithFileUploads, Toast;

    #[Title('Edit Profile')]
    #[Url]
    public $name;
    public $email;
    public $phone_no;
    public $password;
    public $image;
    public $user;
    public $config = ['aspectRatio' => 1];

    public function mount()
    {
        $this->user = User::findOrFail(auth()->id());
        $this->name = $this->user->name;
        $this->email = $this->user->email;
        $this->phone_no = $this->user->phone_no;
    }

    public function save(): void
    {
        $this->validate([
            'name' => 'nullable',
            'email' => 'nullable',
            'phone_no' => 'nullable',
            'password' => 'nullable',
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:1024',
        ]);

        $this->user->name = $this->name;
        $this->user->email = $this->email;
        $this->user->phone_no = $this->phone_no;
        $this->user->password = \Hash::make($this->password);

        if ($this->image) {
            if ($this->user->image) {
                Storage::disk('public')->delete(str_replace('/storage/', '', $this->user->image));
            }

            $url = $this->image->store('users', 'public');
            $this->user->image = "/storage/$url";
        }

        $this->user->save();
        $this->success('Profile updated.', redirectTo: route('admin.profile'));
    }

    public function delete()
    {
        if ($this->user->image) {
            Storage::disk('public')->delete(str_replace('/storage/', '', $this->user->image));
        }
        $this->user->delete();
        $this->success('Profile deleted.', redirectTo: route('admin.profile'));
    }
};
?>

@section('cdn')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.css" />
@endsection

<div>
    <div class="mb-4 text-sm breadcrumbs">
        <h1 class="mb-2 text-2xl font-bold">
            Edit Profile
        </h1>
        <ul>
            <li>
                <a href="{{ route('admin.index') }}" wire:navigate>
                    Dashboard
                </a>
            </li>
            <li>
                Edit Profile
            </li>
        </ul>
    </div>
    <hr class="mb-5">
    <div class="grid grid-cols-1 gap-6 mt-6 xl:grid-cols-2">
        <x-form wire:submit="save">
            <div class="flex justify-between gap-8">
                <div class="w-full">
                    <x-input label="Name" wire:model="name" />
                    <x-input label="Email" wire:model="email" :readonly="hasAuthRole(RolesEnum::Center->value)" />
                    <x-input label="Phone" wire:model="phone_no" />
                    <x-input label="Password" wire:model="password" type="password" />
                </div>
            </div>
            <x-file label="Avatar" wire:model="image" accept="image" crop-after-change :crop-config="$config">
                <div class="mt-1">
                    <img id="imagePreview" src="{{ $user->image ? asset($user->image) : 'https://placehold.co/300' }}"
                        class="h-40 rounded-lg" alt="Avatar">
                </div>
            </x-file>
            <x-slot:actions>
                <div class="flex justify-between w-full">
                    <x-button label="Update" class="btn-primary" type="submit" spinner="save" />
                </div>
            </x-slot:actions>
        </x-form>
    </div>
</div>
