<?php

use Livewire\Volt\Component;
use App\Models\ExternalCertificate;
use Illuminate\Support\Str;

new class extends Component {
    public $search = '';
    public $headers = [['key' => 'reg_no', 'label' => 'Reg No', 'class' => 'w-48'], ['key' => 'student_name', 'label' => 'Student', 'class' => 'w-64'], ['key' => 'course_name', 'label' => 'Course', 'class' => 'w-64'], ['key' => 'issued_on', 'label' => 'Issued On', 'class' => 'w-40'], ['key' => 'actions', 'label' => 'Actions', 'class' => 'w-32']];
    public $sortBy = ['column' => 'created_at', 'direction' => 'desc'];

    public function with()
    {
        $query = ExternalCertificate::query();
        if (!empty($this->search)) {
            $query->whereAny(['reg_no', 'student_name', 'course_name'], 'like', "%{$this->search}%");
        }
        $certificates = $query->orderBy($this->sortBy['column'], $this->sortBy['direction'])->paginate(10);

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

    <x-table :headers="$headers" :rows="$certificates" :sort-by="$sortBy" with-pagination>
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

        @scope('cell_actions', $certificate)
            <a class="link link-primary" href="{{ route('certificate.external.show', $certificate->id) }}"
                target="_blank">Show</a>
        @endscope
    </x-table>
</div>
