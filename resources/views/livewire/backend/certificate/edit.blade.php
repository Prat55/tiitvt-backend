<?php

use Livewire\Volt\Component;
use App\Models\{ExternalCertificate, Center};
use App\Enums\RolesEnum;
use App\Services\CertificateService;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Mary\Traits\Toast;
use Illuminate\View\View;

new class extends Component {
    use Toast;

    public ExternalCertificate $certificate;
    public string $reg_no = '';
    public string $course_name = '';
    public string $student_name = '';
    public string $grade = '';
    public string $percentage = '';
    public array $data = [];
    public array $subjects = [['name' => '', 'maximum' => '', 'obtained' => '', 'result' => 'PASS']];
    public int $center_id = 0;
    public string $issued_on = '';

    public function mount(ExternalCertificate $certificate): void
    {
        $this->certificate = $certificate;
        $this->reg_no = $certificate->reg_no;
        $this->course_name = $certificate->course_name;
        $this->student_name = $certificate->student_name;
        $this->grade = $certificate->grade ?? '';
        $this->percentage = $certificate->percentage ? (string) $certificate->percentage : '';
        $this->center_id = $certificate->center_id;
        $this->issued_on = $certificate->issued_on ? $certificate->issued_on->format('Y-m-d') : now()->format('Y-m-d');

        // Load subjects data
        if ($certificate->data && isset($certificate->data['subjects'])) {
            $this->subjects = $certificate->data['subjects'];
        } else {
            $this->subjects = [['name' => '', 'maximum' => '', 'obtained' => '', 'result' => 'PASS']];
        }

        // Load other data
        $this->data = $certificate->data ?? [];
    }

    public function rules()
    {
        return [
            'reg_no' => 'required|string|max:100|unique:external_certificates,reg_no,' . $this->certificate->id,
            'course_name' => 'required|string|max:150',
            'student_name' => 'required|string|max:150',
            'grade' => 'nullable|string|max:5',
            'percentage' => 'nullable|numeric|min:0|max:100',
            'center_id' => 'required|integer|exists:centers,id',
            'issued_on' => 'required|date',
            'data' => 'nullable|array',
            'subjects' => 'required|array|min:1',
            'subjects.*.name' => 'required|string|max:150',
            'subjects.*.maximum' => 'required|numeric|min:0',
            'subjects.*.obtained' => 'required|numeric|min:0',
            'subjects.*.result' => 'required|string|max:10',
        ];
    }

    public function addSubjectRow()
    {
        $this->subjects[] = ['name' => '', 'maximum' => '', 'obtained' => '', 'result' => 'PASS'];
    }

    public function removeSubjectRow($index)
    {
        unset($this->subjects[$index]);
        $this->subjects = array_values($this->subjects);
    }

    public function update()
    {
        $this->validate();

        // Calculate totals and overall result
        $totalMaximum = 0;
        $totalObtained = 0;
        $overallResult = 'PASS';
        foreach ($this->subjects as $row) {
            $totalMaximum += (float) ($row['maximum'] !== '' ? $row['maximum'] : 0);
            $totalObtained += (float) ($row['obtained'] !== '' ? $row['obtained'] : 0);
            if (strtoupper((string) $row['result']) !== 'PASS') {
                $overallResult = 'FAIL';
            }
        }

        $data = $this->data ?: [];
        $data['subjects'] = $this->subjects;
        $data['total_marks'] = $totalMaximum;
        $data['total_marks_obtained'] = $totalObtained;
        $data['total_result'] = $overallResult;

        // Update certificate
        $this->certificate->update([
            'reg_no' => $this->reg_no,
            'course_name' => $this->course_name,
            'student_name' => $this->student_name,
            'grade' => $this->grade ?: null,
            'percentage' => $this->percentage !== '' ? (float) $this->percentage : null,
            'center_id' => $this->center_id,
            'issued_on' => $this->issued_on,
            'data' => $data ?: null,
        ]);

        // Regenerate QR code with updated information
        $certificateService = app(CertificateService::class);
        $qrPath = $certificateService->generateCertificateQRCodeWithLogo($this->certificate->qr_token, $this->certificate->id);
        $this->certificate->update(['qr_code_path' => $qrPath]);

        $this->success('Certificate updated successfully!', position: 'toast-bottom');
        $this->redirect(route('admin.certificate.index'));
    }

    public function regenerateQRCode()
    {
        try {
            $certificateService = app(CertificateService::class);
            $qrPath = $certificateService->generateCertificateQRCodeWithLogo($this->certificate->qr_token, $this->certificate->id);
            $this->certificate->update(['qr_code_path' => $qrPath]);

            $this->success('QR Code regenerated successfully with current logo!', position: 'toast-bottom');
        } catch (\Exception $e) {
            $this->error('Failed to regenerate QR code. Please try again.', position: 'toast-bottom');
        }
    }

    public function rendering(View $view)
    {
        $view->centers = Center::active()
            ->latest()
            ->get(['id', 'name']);
    }
}; ?>
<div>
    <!-- Header -->
    <div class="flex justify-between items-start lg:items-center flex-col lg:flex-row mt-3 mb-5 gap-2">
        <div>
            <h1 class="text-2xl font-bold">Edit Certificate</h1>
            <div class="breadcrumbs text-sm">
                <ul class="flex">
                    <li>
                        <a href="{{ route('admin.index') }}" wire:navigate>Dashboard</a>
                    </li>
                    <li>
                        <a href="{{ route('admin.certificate.index') }}" wire:navigate>Certificates</a>
                    </li>
                    <li>Edit - {{ $certificate->reg_no }}</li>
                </ul>
            </div>
        </div>
        <div class="flex gap-3">
            <x-button label="Regenerate QR" icon="o-arrow-path" class="btn-secondary btn-outline"
                wire:click="regenerateQRCode" spinner="regenerateQRCode" />
            <x-button label="Back to Certificates" icon="o-arrow-left" class="btn-primary btn-outline"
                link="{{ route('admin.certificate.index') }}" responsive />
        </div>
    </div>

    <hr class="mb-5">

    <!-- Form Card -->
    <x-card shadow>
        <form wire:submit.prevent="update" class="space-y-6">
            <!-- Basic Information -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="md:col-span-2">
                    <h3 class="text-lg font-semibold text-primary">Basic Information</h3>
                </div>

                @role(RolesEnum::Admin->value)
                    <x-choices-offline label="Center" wire:model.live="center_id" placeholder="Select center"
                        icon="o-building-office" :options="$centers" single searchable clearable />
                @endrole

                <x-input label="Registration No" wire:model="reg_no" placeholder="Enter registration number"
                    icon="o-identification" />

                <x-input label="Course Name" wire:model="course_name" placeholder="Enter course name"
                    icon="o-book-open" />

                <x-input label="Student Name" wire:model="student_name" placeholder="Enter student full name"
                    icon="o-user" />

                <x-input label="Grade" wire:model="grade" placeholder="e.g. A" icon="o-academic-cap" />

                <x-input label="Percentage" wire:model="percentage" type="number" step="0.01" min="0"
                    max="100" placeholder="e.g. 88.50" icon="o-chart-bar" />

                <x-input label="Issued Date" wire:model="issued_on" type="date" icon="o-calendar" />
            </div>

            <!-- Subjects -->
            <div class="space-y-4">
                <h3 class="text-lg font-semibold text-primary">Subjects</h3>
                <div class="space-y-3">
                    @foreach ($subjects as $i => $row)
                        <div class="grid items-end grid-cols-12 gap-2">
                            <div class="col-span-4">
                                <x-input label="Subject Name" wire:model="subjects.{{ $i }}.name"
                                    placeholder="e.g. HTML & CSS" icon="o-pencil-square" />
                            </div>
                            <div class="col-span-2">
                                <x-input label="Maximum" type="number" min="0" step="1"
                                    wire:model="subjects.{{ $i }}.maximum" placeholder="e.g. 100"
                                    icon="o-hashtag" />
                            </div>
                            <div class="col-span-2">
                                <x-input label="Obtained" type="number" min="0" step="1"
                                    wire:model="subjects.{{ $i }}.obtained" placeholder="e.g. 80"
                                    icon="o-hashtag" />
                            </div>
                            <div class="col-span-2">
                                <x-select label="Result" :options="[['name' => 'PASS', 'id' => 'PASS'], ['name' => 'FAIL', 'id' => 'FAIL']]"
                                    wire:model="subjects.{{ $i }}.result" />
                            </div>
                            <div class="col-span-2 flex gap-2">
                                <button type="button" class="btn btn-outline btn-sm"
                                    wire:click="addSubjectRow">Add</button>
                                @if (count($subjects) > 1)
                                    <button type="button" class="btn btn-error btn-sm"
                                        wire:click="removeSubjectRow({{ $i }})">Remove</button>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Certificate Preview -->
            <div class="space-y-4">
                <h3 class="text-lg font-semibold text-primary">Certificate Preview</h3>
                <div class="bg-base-200 rounded-lg p-4">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h4 class="font-semibold text-content">{{ $student_name ?: 'Student Name' }}</h4>
                            <p class="text-sm text-content">{{ $course_name ?: 'Course Name' }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm text-content">Reg No: {{ $reg_no ?: 'REG-XXX' }}</p>
                            <p class="text-sm text-content">
                                {{ $issued_on ? \Carbon\Carbon::parse($issued_on)->format('d M Y') : 'Date' }}</p>
                        </div>
                    </div>

                    @if ($grade || $percentage)
                        <div class="flex gap-4 mb-4">
                            @if ($grade)
                                <div class="text-center">
                                    <p class="text-xs text-gray-500">Grade</p>
                                    <span
                                        class="inline-flex items-center px-2 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                                        {{ $grade }}
                                    </span>
                                </div>
                            @endif
                            @if ($percentage)
                                <div class="text-center">
                                    <p class="text-xs text-gray-500">Percentage</p>
                                    <span
                                        class="inline-flex items-center px-2 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                                        {{ number_format((float) $percentage, 1) }}%
                                    </span>
                                </div>
                            @endif
                        </div>
                    @endif

                    <div class="text-center">
                        <a href="{{ route('admin.certificate.show', $certificate->id) }}" target="_blank"
                            class="btn btn-primary btn-sm">
                            <i class="fas fa-eye mr-1"></i>
                            View Certificate
                        </a>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex justify-end gap-3 pt-6 border-t">
                <x-button label="Cancel" icon="o-x-mark" class="btn-error btn-soft btn-sm"
                    link="{{ route('admin.certificate.index') }}" />
                <x-button label="Update Certificate" icon="o-check" class="btn-primary btn-sm btn-soft"
                    type="submit" spinner="update" />
            </div>
        </form>
    </x-card>
</div>
