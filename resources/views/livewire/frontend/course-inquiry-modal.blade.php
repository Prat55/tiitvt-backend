<?php

use App\Models\CourseInquiry;
use App\Models\Course;
use Livewire\Volt\Component;

new class extends Component {
    public int $courseId = 0;
    public string $courseName = '';

    // Form fields
    public string $name = '';
    public string $email = '';
    public string $phone = '';

    public bool $submitted = false;

    /**
     * Called from JS: enquireFor(courseId, courseName)
     * Just updates state — does NOT dispatch a browser event.
     * The Bootstrap modal is opened directly by JS in the parent page.
     */
    public function setCourse(int $courseId, string $courseName): void
    {
        $this->courseId = $courseId;
        $this->courseName = $courseName;
        $this->reset(['name', 'email', 'phone', 'submitted']);
    }

    public function submit(): void
    {
        $this->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|max:150',
            'phone' => 'required|string|max:20',
        ]);

        CourseInquiry::create([
            'course_id' => $this->courseId,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
        ]);

        $this->submitted = true;
        $this->reset(['name', 'email', 'phone']);
    }
}; ?>

{{-- Inner form only — modal shell lives in courses/index.blade.php --}}
<div>
    @if ($submitted)
        <div class="text-center py-3">
            <div style="font-size:48px; color:#138808;">&#10003;</div>
            <h5 style="color:#0b3d91; font-weight:700;" class="mt-2">Thank you!</h5>
            <p style="color:#5a6a7a;">Your enquiry has been submitted. We will get back to you shortly.</p>
            <button class="btn btn-sm mt-2" data-bs-dismiss="modal"
                style="background:#0b3d91; color:#fff; border-radius:4px; font-weight:600;">
                Close
            </button>
        </div>
    @else
        <form wire:submit.prevent="submit">
            <div class="mb-3">
                <label class="form-label fw-semibold" style="color:#0b3d91;">Full Name <span
                        class="text-danger">*</span></label>
                <input type="text" class="form-control @error('name') is-invalid @enderror" wire:model="name"
                    placeholder="Enter your full name">
                @error('name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold" style="color:#0b3d91;">Email Address <span
                        class="text-danger">*</span></label>
                <input type="email" class="form-control @error('email') is-invalid @enderror" wire:model="email"
                    placeholder="Enter your email">
                @error('email')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold" style="color:#0b3d91;">Phone Number <span
                        class="text-danger">*</span></label>
                <input type="tel" class="form-control @error('phone') is-invalid @enderror" wire:model="phone"
                    placeholder="Enter your phone number">
                @error('phone')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="d-grid mt-4">
                <button type="submit" class="btn fw-bold"
                    style="background:#FF9933; color:#fff; border-radius:4px; padding:12px; letter-spacing:0.5px; text-transform:uppercase;">
                    <span wire:loading.remove wire:target="submit">
                        <i class="fas fa-paper-plane me-2"></i> Submit Enquiry
                    </span>
                    <span wire:loading wire:target="submit">
                        <span class="spinner-border spinner-border-sm me-2"></span> Submitting...
                    </span>
                </button>
            </div>
        </form>
    @endif
</div>
