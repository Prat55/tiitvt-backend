<?php

use Carbon\Carbon;
use Livewire\Volt\Component;
use App\Models\Student;
use Livewire\Attributes\{Layout, Title, Url};

new class extends Component {
    #[Layout('components.layouts.guest')]
    #[Title('Certificate Verification')]
    #[Url(as: 'reg_no')]
    public $regNo = '';
    public $student = null;
    public $searched = false;

    public function mount()
    {
        if ($this->regNo) {
            $this->verify();
        }
    }

    public function exit()
    {
        $this->regNo = '';
        $this->student = null;
        $this->searched = false;
    }

    public function verify()
    {
        $this->validate([
            'regNo' => 'required|string',
        ]);

        $this->searched = true;

        $this->student = Student::select('id', 'tiitvt_reg_no', 'first_name', 'fathers_name', 'surname', 'center_id', 'enrollment_date', 'qualification', 'additional_qualification')
            ->where('tiitvt_reg_no', $this->regNo)
            ->with(['center', 'courses', 'examResults.exam.course', 'examResults.category', 'qrCode'])
            ->first();

        if ($this->student) {
            // Ensure QR code exists for secure download
            if (!$this->student->qrCode) {
                app(\App\Services\StudentQRService::class)->generateStudentQR($this->student);
                $this->student->load('qrCode');
            }

            // Track page visit
            trackPageVisit('certificate_verification', [
                'reg_no' => $this->regNo,
                'student_id' => $this->student->id,
            ]);
        }
    }

    /**
     * Calculate grade based on percentage
     */
    private function calculateGrade($percentage)
    {
        if ($percentage >= 90) {
            return 'A+';
        }
        if ($percentage >= 80) {
            return 'A';
        }
        if ($percentage >= 70) {
            return 'B+';
        }
        if ($percentage >= 60) {
            return 'B';
        }
        if ($percentage >= 50) {
            return 'C+';
        }
        if ($percentage >= 40) {
            return 'C';
        }
        return 'F';
    }

    public function getExamResults()
    {
        if (!$this->student) {
            return collect();
        }

        return $this->student->courses
            ->map(function ($course) {
                $allCourseResults = \App\Models\ExamResult::where('student_id', $this->student->id)
                    ->whereHas('exam', function ($query) use ($course) {
                        $query->where('course_id', $course->id);
                    })
                    ->orderBy('submitted_at', 'desc')
                    ->get();

                if ($allCourseResults->isEmpty()) {
                    return (object) [
                        'course' => $course,
                        'percentage' => $course->auto_certificate ? ($course->passing_percentage ?: 80) : null,
                        'is_passed' => $course->auto_certificate,
                        'can_generate_certificate' => $course->auto_certificate,
                        'has_results' => false,
                    ];
                }

                $categoryResults = $allCourseResults
                    ->groupBy('category_id')
                    ->map(function ($results) {
                        return $results->first();
                    })
                    ->values();

                $totalPoints = 0;
                $pointsEarned = 0;

                foreach ($categoryResults as $result) {
                    $totalPoints += $result->total_points ?: 100;
                    $pointsEarned += $result->points_earned ?: $result->score ?: 0;
                }

                $overallPercentage = $totalPoints > 0 ? ($pointsEarned / $totalPoints) * 100 : 0;
                $isPassed = $course->auto_certificate ? $overallPercentage >= ($course->passing_percentage ?: 80) : $overallPercentage >= 50;

                return (object) [
                    'course' => $course,
                    'percentage' => round($overallPercentage, 2),
                    'is_passed' => $isPassed,
                    'can_generate_certificate' => $course->auto_certificate,
                    'has_results' => true,
                ];
            })
            ->values();
    }
}; ?>

<div class="min-h-screen bg-base-300 py-4 px-2 sm:py-8 sm:px-4 lg:px-8">
    <div class="max-w-4xl mx-auto">
        <!-- Search Card -->
        <div class="bg-base-100 shadow-xl rounded-xl sm:rounded-2xl overflow-hidden relative mb-4 sm:mb-8">
            <div class="absolute top-2 right-2 sm:top-4 sm:right-4 flex gap-1 sm:gap-2">
                <x-theme-toggle class="w-8 h-8 sm:w-12 sm:h-12 btn-xs sm:btn-sm text-white" lightTheme="light"
                    darkTheme="dark" />
            </div>

            <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-4 py-4 sm:px-8 sm:py-6 text-center">
                <div
                    class="mx-auto flex items-center justify-center h-12 w-12 sm:h-16 sm:w-16 rounded-full bg-white/20 mb-3 sm:mb-4">
                    <x-icon name="o-shield-check" class="h-6 w-6 sm:h-8 sm:w-8 text-white" />
                </div>
                <h1 class="text-xl sm:text-2xl lg:text-3xl font-bold text-white mb-2">
                    Certificate Verification
                </h1>
                <p class="text-blue-100 text-sm sm:text-base">
                    Verify authenticity by Registration Number
                </p>
            </div>

            <div class="p-6">
                <x-form wire:submit="verify" class="max-w-md mx-auto">
                    <x-input label="Registration Number" wire:model="regNo" placeholder="e.g. TIITVT/ATC/1/1"
                        icon="o-magnifying-glass" hint="Enter the registration number printed on the certificate"
                        inline />
                    <div class="flex gap-2 mt-2">
                        <x-button type="submit" label="Verify Certificate" class="btn-primary flex-1"
                            spinner="verify" />
                        @if ($searched || $regNo)
                            <x-button label="Clear" icon="o-x-mark" wire:click="exit" class="btn-ghost" />
                        @endif
                    </div>
                </x-form>
            </div>
        </div>

        @if ($searched)
            @if ($student)
                <!-- Student Information -->
                <div class="space-y-4 sm:space-y-8 animate-in fade-in duration-500">
                    <x-card class="bg-base-200">
                        <div class="flex flex-col sm:flex-row items-center space-y-4 sm:space-y-0 sm:space-x-6">
                            <div class="flex-shrink-0">
                                <div
                                    class="h-16 w-16 sm:h-20 sm:w-20 rounded-full bg-gradient-to-r from-green-500 to-emerald-500 flex items-center justify-center">
                                    <x-icon name="o-user" class="h-8 w-8 sm:h-10 sm:w-10 text-white" />
                                </div>
                            </div>
                            <div class="flex-1 text-center sm:text-left">
                                <h2 class="text-2xl sm:text-3xl font-bold mb-1">{{ $student->full_name }}</h2>
                                <p class="text-lg sm:text-xl mb-2">{{ $student->tiitvt_reg_no }}</p>
                                <div
                                    class="flex flex-col sm:flex-row items-center sm:space-x-4 space-y-2 sm:space-y-0 text-sm">
                                    <span class="flex items-center">
                                        <x-icon name="o-building-office" class="h-4 w-4 mr-1" />
                                        {{ $student->center->name ?? 'N/A' }}
                                    </span>
                                    @if ($student->enrollment_date)
                                        <span class="flex items-center">
                                            <x-icon name="o-calendar" class="h-4 w-4 mr-1" />
                                            Enrolled: {{ $student->enrollment_date->format('d M Y') }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </x-card>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-6">
                        <x-card title="Personal Information" class="bg-base-200">
                            <div class="space-y-3 py-2 border-b border-base-300 flex justify-between">
                                <span class="text-xs sm:text-sm font-medium">Full Name</span>
                                <span class="text-xs sm:text-sm font-semibold">{{ $student->full_name }}</span>
                            </div>
                            <div class="space-y-3 py-2 flex justify-between">
                                <span class="text-xs sm:text-sm font-medium">Registration No</span>
                                <span class="text-xs sm:text-sm font-semibold">{{ $student->tiitvt_reg_no }}</span>
                            </div>
                        </x-card>

                        <x-card title="Academic Information" class="bg-base-200">
                            <div class="space-y-3 py-2 border-b border-base-300 flex justify-between">
                                <span class="text-xs sm:text-sm font-medium">Center</span>
                                <span
                                    class="text-xs sm:text-sm font-semibold">{{ $student->center->name ?? 'N/A' }}</span>
                            </div>
                            @if ($student->qualification)
                                <div class="space-y-3 py-2 flex justify-between">
                                    <span class="text-xs sm:text-sm font-medium">Qualification</span>
                                    <span class="text-xs sm:text-sm font-semibold">{{ $student->qualification }}</span>
                                </div>
                            @endif
                        </x-card>
                    </div>

                    @php $examResults = $this->getExamResults(); @endphp
                    @if ($examResults->isNotEmpty())
                        <x-card title="Exam Results & Certificates" class="bg-base-200">
                            <div class="overflow-x-auto">
                                <table class="table w-full">
                                    <thead>
                                        <tr>
                                            <th>Course</th>
                                            <th>Percentage</th>
                                            <th>Status</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($examResults as $result)
                                            <tr>
                                                <td>{{ $result->course->name }}</td>
                                                <td>
                                                    @if ($result->has_results || ($result->course->auto_certificate && $result->percentage))
                                                        {{ $result->percentage }}%
                                                    @else
                                                        <span class="text-gray-400">N/A</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if ($result->has_results)
                                                        @if ($result->is_passed)
                                                            <span class="badge badge-success h-fit">Passed</span>
                                                        @else
                                                            <span class="badge badge-error h-fit">Failed</span>
                                                        @endif
                                                    @elseif ($result->course->auto_certificate)
                                                        <span class="badge badge-success h-fit">Passed</span>
                                                    @else
                                                        <span class="badge badge-warning h-fit">Pending</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if ($result->can_generate_certificate)
                                                        <x-button label="Download" icon="o-arrow-down-tray"
                                                            class="btn-sm btn-primary"
                                                            link="{{ route('certificate.public.download', ['token' => $student->qrCode->qr_token, 'courseId' => $result->course->id]) }}"
                                                            external />
                                                    @else
                                                        <span class="text-xs text-gray-500 italic">Manual cert
                                                            only</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </x-card>
                    @endif
                </div>
            @else
                <div class="bg-base-100 shadow-xl rounded-xl p-8 text-center animate-in zoom-in duration-300">
                    <x-icon name="o-exclamation-circle" class="h-16 w-16 mx-auto mb-4 text-error" />
                    <h3 class="text-xl font-bold mb-2">Verification Failed</h3>
                    <p class="text-gray-500">No student found with registration number: <span
                            class="font-mono font-bold">{{ $regNo }}</span></p>
                    <p class="text-sm mt-4">Please check the registration number and try again.</p>
                </div>
            @endif
        @endif

        <!-- Footer -->
        <div class="text-center text-xs sm:text-sm mt-4 sm:mt-8 text-gray-500">
            <p class="flex items-center justify-center space-x-2">
                <x-icon name="o-shield-check" class="h-3 w-3 sm:h-4 sm:w-4" />
                <span>Secure Certificate Verification System</span>
            </p>
            <p class="mt-1">&copy; {{ date('Y') }} {{ getWebsiteName() }}</p>
        </div>
    </div>
</div>
