<?php

use Livewire\Volt\Component;
use App\Models\ExternalCertificate;
use Illuminate\Support\Str;
use Mary\Traits\Toast;
use Illuminate\Support\Facades\Storage;

new class extends Component {
    use Toast;
    public $search = '';
    public $headers = [['key' => 'reg_no', 'label' => 'Reg No', 'class' => 'w-48'], ['key' => 'student_name', 'label' => 'Student', 'class' => 'w-64'], ['key' => 'course_name', 'label' => 'Course', 'class' => 'w-64'], ['key' => 'issued_on', 'label' => 'Issued On', 'class' => 'w-20']];
    public $sortBy = ['column' => 'id', 'direction' => 'desc'];
    public $perPage = 20;

    public function with()
    {
        $query = ExternalCertificate::query();

        // Filter by center if user is a center
        $centerId = getUserCenterId();
        if ($centerId) {
            $query->where('center_id', $centerId);
        }

        if (!empty($this->search)) {
            $query->whereAny(['reg_no', 'student_name', 'course_name'], 'like', "%{$this->search}%");
        }
        $certificates = $query->orderBy($this->sortBy['column'], $this->sortBy['direction'])->paginate($this->perPage);

        return ['certificates' => $certificates];
    }

    public function sort($column)
    {
        if ($this->sortBy['column'] === $column) {
            $this->sortBy['direction'] = $this->sortBy['direction'] === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = ['column' => $column, 'direction' => 'asc'];
        }
    }

    public function deleteCertificate($certificateId)
    {
        $centerId = getUserCenterId();

        try {
            $certificate = ExternalCertificate::findOrFail($certificateId);

            // Authorization check: Centers can only delete their own certificates
            if ($centerId && $certificate->center_id !== $centerId) {
                $this->error('Unauthorized action.');
                return;
            }

            // Delete QR code file if exists
            if ($certificate->qr_code_path && Storage::disk('public')->exists($certificate->qr_code_path)) {
                Storage::disk('public')->delete($certificate->qr_code_path);
            }

            // Delete PDF file if exists
            if ($certificate->pdf_path && Storage::disk('public')->exists($certificate->pdf_path)) {
                Storage::disk('public')->delete($certificate->pdf_path);
            }

            // Delete certificate record
            $certificate->delete();

            $this->success('Certificate deleted successfully!', position: 'toast-bottom');
        } catch (\Exception $e) {
            $this->error('Failed to delete certificate. Please try again.', position: 'toast-bottom');
        }
    }
}; ?>

<div>
    <div class="flex justify-between items-start lg:items-center flex-col lg:flex-row mt-3 mb-5 gap-2">
        <div>
            <h1 class="text-2xl font-bold">
                External Certificates
            </h1>
            <div class="breadcrumbs text-sm">
                <ul class="flex">
                    <li>
                        <a href="{{ route('admin.index') }}" wire:navigate>
                            Dashboard
                        </a>
                    </li>
                    <li>
                        External Certificates
                    </li>
                </ul>
            </div>
        </div>
        <div class="flex gap-3">
            <x-input placeholder="Search centers, phone, email, owner..." icon="o-magnifying-glass"
                wire:model.live.debounce="search" />
            <x-button label="Add Certificate" icon="o-plus" class="btn-primary inline-flex" responsive
                link="{{ route('admin.certificate.create') }}" />
        </div>
    </div>
    <hr class="mb-5">

    <x-table :headers="$headers" :rows="$certificates" :sort-by="$sortBy" with-pagination per-page="perPage" :per-page-values="[20, 50, 100]">
        @scope('cell_reg_no', $certificate)
            <span class="font-mono">{{ $certificate->reg_no }}</span>
        @endscope

        @scope('cell_student_name', $certificate)
            {{ $certificate->student_name }}
        @endscope

        @scope('cell_course_name', $certificate)
            {{ $certificate->course_name }}
        @endscope

        @scope('cell_issued_on', $certificate)
            {{ optional($certificate->issued_on)->format('d M Y') ?? '—' }}
        @endscope

        @scope('actions', $certificate)
            <div class="flex gap-2 items-center">
                <x-button icon="o-eye" link="{{ route('admin.certificate.show', $certificate->id) }}"
                    class="btn-xs btn-ghost" title="View Certificate" external />
                <x-button icon="o-pencil" link="{{ route('admin.certificate.edit', $certificate->id) }}"
                    class="btn-xs btn-ghost" title="Edit" />
                <x-button icon="o-trash" class="btn-xs btn-ghost text-error"
                    wire:click="deleteCertificate({{ $certificate->id }})"
                    wire:confirm="Are you sure you want to delete this certificate?" title="Delete" />
            </div>
        @endscope
    </x-table>
</div>
