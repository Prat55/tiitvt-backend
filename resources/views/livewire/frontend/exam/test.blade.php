<?php

use Livewire\Volt\Component;
use Livewire\Attributes\{Title, Layout};

new class extends Component {
    #[Layout('components.layouts.guest')]
    #[Title('Test Page')]
    public function mount()
    {
        \Log::channel('exam')->info('Test component mounted');
    }
}; ?>

<div class="min-h-screen bg-green-100 flex items-center justify-center">
    <div class="bg-white p-8 rounded-lg shadow-lg">
        <h1 class="text-2xl font-bold text-green-600 mb-4">Test Component Working!</h1>
        <p class="text-gray-600">This is a simple test component to verify Livewire is working.</p>
        <p class="text-sm text-gray-500 mt-2">Session ID: {{ session('exam_student_id', 'None') }}</p>
    </div>
</div>
