<?php

use App\Models\Exam;
use Illuminate\View\View;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Livewire\Attributes\{Title, Url};

new class extends Component {
    use WithPagination;
    #[Title('All Exams')]
    public $headers;
    #[Url]
    public string $search = '';

    public $sortBy = ['column' => 'name', 'direction' => 'asc'];
    // boot
    public function boot(): void
    {
        $this->headers = [['key' => 'id', 'label' => '#', 'class' => 'w-1'], ['key' => 'name', 'label' => 'Exam Name', 'class' => 'w-48'], ['key' => 'description', 'label' => 'Description', 'class' => 'w-48'], ['key' => 'status', 'label' => 'Status', 'class' => 'w-32']];
    }

    public function rendering(View $view): void
    {
        $view->exams = Exam::orderBy(...array_values($this->sortBy))
            ->whereAny(['name', 'description'], 'like', "%$this->search%")
            ->paginate(20);
        $view->title('All Exams');
    }
};
?>

<div>
    <div class="flex justify-between items-start lg:items-center flex-col lg:flex-row mt-3 mb-5 gap-2">
        <div>
            <h1 class="text-2xl font-bold">
                All Exams
            </h1>
            <div class="breadcrumbs text-sm">
                <ul class="flex">
                    <li>
                        <a href="{{ route('admin.index') }}" wire:navigate>
                            Dashboard
                        </a>
                    </li>
                    <li>
                        All Exams
                    </li>
                </ul>
            </div>
        </div>
        <div class="flex gap-3">
            <x-input placeholder="Search exams, name, description..." icon="o-magnifying-glass"
                wire:model.live.debounce="search" />
            <x-button label="Add Exam" icon="o-plus" class="btn-primary inline-flex" responsive
                link="{{ route('admin.exam.create') }}" />
        </div>
    </div>
    <hr class="mb-5">
    <x-table :headers="$headers" :rows="$exams" with-pagination :sort-by="$sortBy">
        @scope('cell_name', $exam)
            <div class="flex items-center gap-2">
                <span class="badge badge-xs {{ $exam->status === 'active' ? 'badge-success' : 'badge-error' }}">
                    {{ $exam->status === 'active' ? 'Active' : 'Inactive' }}
                </span>
                <span class="font-medium">{{ $exam->name }}</span>
            </div>
        @endscope
        @scope('cell_description', $exam)
            <span class="text-sm">{{ $exam->description }}</span>
        @endscope
        @scope('cell_status', $exam)
            <span class="badge badge-xs {{ $exam->status === 'active' ? 'badge-success' : 'badge-error' }}">
                {{ $exam->status === 'active' ? 'Active' : 'Inactive' }}
            </span>
        @endscope
        @scope('actions', $exam)
            <div class="flex gap-1">
                <x-button icon="o-eye" link="{{ route('admin.exam.show', $exam->uid) }}" class="btn-xs btn-ghost"
                    title="View Details" />
                <x-button icon="o-pencil" link="{{ route('admin.exam.edit', $exam->uid) }}" class="btn-xs btn-ghost"
                    title="Edit Exam" />
            </div>
        @endscope
        <x-slot:empty>
            <x-empty icon="o-no-symbol" message="No exams found" />
        </x-slot>
    </x-table>
</div>
