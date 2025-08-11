<?php

use Livewire\Volt\Component;
use App\Models\{Center, Student, Course, Exam, ExamResult, Invoice, Certificate};
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

new class extends Component {
    public $user;
    
    // Statistics
    public $totalCenters = 0;
    public $activeCenters = 0;
    public $totalStudents = 0;
    public $activeStudents = 0;
    public $totalCourses = 0;
    public $totalExams = 0;
    public $totalRevenue = 0;
    public $pendingPayments = 0;
    public $totalCertificates = 0;
    
    // Recent activities
    public $recentEnrollments = [];
    public $recentExamResults = [];
    public $recentPayments = [];
    public $upcomingExams = [];
    
    // Charts data
    public $enrollmentChart = [];
    public $revenueChart = [];
    public $coursePopularityChart = [];
    
    public function mount()
    {
        $this->user = auth()->user();
        $this->loadStatistics();
        $this->loadRecentActivities();
        $this->loadChartData();
    }
    
    public function loadStatistics()
    {
        if ($this->user->isAdmin()) {
            // Admin statistics
            $this->totalCenters = Center::count();
            $this->activeCenters = Center::where('status', 'active')->count();
            $this->totalStudents = Student::count();
            $this->activeStudents = Student::where('status', 'active')->count();
            $this->totalCourses = Course::count();
            $this->totalExams = Exam::count();
            $this->totalRevenue = Invoice::where('status', 'paid')->sum('amount');
            $this->pendingPayments = Invoice::where('status', 'pending')->sum('amount');
            $this->totalCertificates = Certificate::count();
        } elseif ($this->user->isCenter()) {
            // Center statistics
            $center = $this->user->center;
            if ($center) {
                $this->totalStudents = $center->students()->count();
                $this->activeStudents = $center->students()->where('status', 'active')->count();
                $this->totalRevenue = Invoice::whereIn('student_id', $center->students->pluck('id'))
                    ->where('status', 'paid')
                    ->sum('amount');
                $this->pendingPayments = Invoice::whereIn('student_id', $center->students->pluck('id'))
                    ->where('status', 'pending')
                    ->sum('amount');
                $this->totalCertificates = Certificate::whereIn('student_id', $center->students->pluck('id'))->count();
            }
        } elseif ($this->user->isStudent()) {
            // Student statistics
            $student = $this->user->students()->first();
            if ($student) {
                $this->totalExams = ExamResult::where('student_id', $student->id)->count();
                $this->totalCertificates = Certificate::where('student_id', $student->id)->count();
                $this->totalRevenue = Invoice::where('student_id', $student->id)
                    ->where('status', 'paid')
                    ->sum('amount');
                $this->pendingPayments = Invoice::where('student_id', $student->id)
                    ->where('status', 'pending')
                    ->sum('amount');
            }
        }
    }
    
    public function loadRecentActivities()
    {
        if ($this->user->isAdmin()) {
            // Recent enrollments
            $this->recentEnrollments = Student::with(['center', 'course'])
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();
                
            // Recent exam results
            $this->recentExamResults = ExamResult::with(['student', 'exam'])
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();
                
            // Recent payments
            $this->recentPayments = Invoice::with('student')
                ->where('status', 'paid')
                ->orderBy('paid_at', 'desc')
                ->limit(5)
                ->get();
                
            // Upcoming exams
            $this->upcomingExams = Exam::where('is_active', true)
                ->with('course')
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();
        } elseif ($this->user->isCenter()) {
            $center = $this->user->center;
            if ($center) {
                $studentIds = $center->students->pluck('id');
                
                $this->recentEnrollments = $center->students()
                    ->with('course')
                    ->orderBy('created_at', 'desc')
                    ->limit(5)
                    ->get();
                    
                $this->recentExamResults = ExamResult::with(['student', 'exam'])
                    ->whereIn('student_id', $studentIds)
                    ->orderBy('created_at', 'desc')
                    ->limit(5)
                    ->get();
                    
                $this->recentPayments = Invoice::with('student')
                    ->whereIn('student_id', $studentIds)
                    ->where('status', 'paid')
                    ->orderBy('paid_at', 'desc')
                    ->limit(5)
                    ->get();
            }
        } elseif ($this->user->isStudent()) {
            $student = $this->user->students()->first();
            if ($student) {
                $this->recentExamResults = ExamResult::with('exam')
                    ->where('student_id', $student->id)
                    ->orderBy('created_at', 'desc')
                    ->limit(5)
                    ->get();
                    
                $this->upcomingExams = Exam::where('is_active', true)
                    ->where('course_id', $student->course_id)
                    ->with('course')
                    ->orderBy('created_at', 'desc')
                    ->limit(5)
                    ->get();
            }
        }
    }
    
    public function loadChartData()
    {
        if ($this->user->isAdmin()) {
            // Enrollment trend for last 6 months
            $enrollmentData = Student::selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, COUNT(*) as count')
                ->where('created_at', '>=', Carbon::now()->subMonths(6))
                ->groupBy('month')
                ->orderBy('month')
                ->get();
                
            $this->enrollmentChart = [
                'type' => 'line',
                'data' => [
                    'labels' => $enrollmentData->pluck('month')->map(fn($m) => Carbon::parse($m . '-01')->format('M Y'))->toArray(),
                    'datasets' => [[
                        'label' => 'New Enrollments',
                        'data' => $enrollmentData->pluck('count')->toArray(),
                        'borderColor' => 'rgb(59, 130, 246)',
                        'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                        'tension' => 0.3
                    ]]
                ]
            ];
            
            // Revenue trend for last 6 months
            $revenueData = Invoice::selectRaw('DATE_FORMAT(paid_at, "%Y-%m") as month, SUM(amount) as total')
                ->where('status', 'paid')
                ->where('paid_at', '>=', Carbon::now()->subMonths(6))
                ->groupBy('month')
                ->orderBy('month')
                ->get();
                
            $this->revenueChart = [
                'type' => 'bar',
                'data' => [
                    'labels' => $revenueData->pluck('month')->map(fn($m) => Carbon::parse($m . '-01')->format('M Y'))->toArray(),
                    'datasets' => [[
                        'label' => 'Revenue (₹)',
                        'data' => $revenueData->pluck('total')->toArray(),
                        'backgroundColor' => 'rgba(34, 197, 94, 0.8)',
                        'borderColor' => 'rgb(34, 197, 94)',
                        'borderWidth' => 1
                    ]]
                ]
            ];
            
            // Course popularity
            $courseData = Student::selectRaw('course_id, COUNT(*) as count')
                ->with('course:id,name')
                ->groupBy('course_id')
                ->orderBy('count', 'desc')
                ->limit(5)
                ->get();
                
            $this->coursePopularityChart = [
                'type' => 'doughnut',
                'data' => [
                    'labels' => $courseData->map(fn($d) => $d->course?->name ?? 'Unknown')->toArray(),
                    'datasets' => [[
                        'data' => $courseData->pluck('count')->toArray(),
                        'backgroundColor' => [
                            'rgba(59, 130, 246, 0.8)',
                            'rgba(147, 51, 234, 0.8)',
                            'rgba(236, 72, 153, 0.8)',
                            'rgba(251, 146, 60, 0.8)',
                            'rgba(34, 197, 94, 0.8)'
                        ]
                    ]]
                ]
            ];
        }
    }
}; ?>

<div>
    {{-- Header --}}
    <div class="mb-6">
        <h1 class="text-3xl font-bold">Dashboard</h1>
        <p class="text-gray-600 dark:text-gray-400 mt-1">
            Welcome back, {{ auth()->user()->name }}!
        </p>
    </div>

    {{-- Statistics Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        @if($user->isAdmin())
            {{-- Centers Card --}}
            <x-card shadow>
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Total Centers</p>
                        <p class="text-3xl font-bold mt-1">{{ number_format($totalCenters) }}</p>
                        <p class="text-sm text-success mt-2">
                            <x-icon name="o-arrow-trending-up" class="w-4 h-4 inline" />
                            {{ $activeCenters }} active
                        </p>
                    </div>
                    <div class="p-4 bg-primary/10 rounded-full">
                        <x-icon name="o-building-office" class="w-8 h-8 text-primary" />
                    </div>
                </div>
            </x-card>

            {{-- Students Card --}}
            <x-card shadow>
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Total Students</p>
                        <p class="text-3xl font-bold mt-1">{{ number_format($totalStudents) }}</p>
                        <p class="text-sm text-success mt-2">
                            <x-icon name="o-user-group" class="w-4 h-4 inline" />
                            {{ $activeStudents }} active
                        </p>
                    </div>
                    <div class="p-4 bg-secondary/10 rounded-full">
                        <x-icon name="o-academic-cap" class="w-8 h-8 text-secondary" />
                    </div>
                </div>
            </x-card>

            {{-- Revenue Card --}}
            <x-card shadow>
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Total Revenue</p>
                        <p class="text-3xl font-bold mt-1">₹{{ number_format($totalRevenue) }}</p>
                        <p class="text-sm text-warning mt-2">
                            <x-icon name="o-clock" class="w-4 h-4 inline" />
                            ₹{{ number_format($pendingPayments) }} pending
                        </p>
                    </div>
                    <div class="p-4 bg-success/10 rounded-full">
                        <x-icon name="o-currency-rupee" class="w-8 h-8 text-success" />
                    </div>
                </div>
            </x-card>

            {{-- Courses & Exams Card --}}
            <x-card shadow>
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Courses & Exams</p>
                        <p class="text-3xl font-bold mt-1">{{ $totalCourses }}</p>
                        <p class="text-sm text-info mt-2">
                            <x-icon name="o-document-text" class="w-4 h-4 inline" />
                            {{ $totalExams }} exams
                        </p>
                    </div>
                    <div class="p-4 bg-info/10 rounded-full">
                        <x-icon name="o-book-open" class="w-8 h-8 text-info" />
                    </div>
                </div>
            </x-card>
        @elseif($user->isCenter())
            {{-- Students Card --}}
            <x-card shadow>
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Your Students</p>
                        <p class="text-3xl font-bold mt-1">{{ number_format($totalStudents) }}</p>
                        <p class="text-sm text-success mt-2">
                            <x-icon name="o-user-group" class="w-4 h-4 inline" />
                            {{ $activeStudents }} active
                        </p>
                    </div>
                    <div class="p-4 bg-primary/10 rounded-full">
                        <x-icon name="o-academic-cap" class="w-8 h-8 text-primary" />
                    </div>
                </div>
            </x-card>

            {{-- Revenue Card --}}
            <x-card shadow>
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Revenue</p>
                        <p class="text-3xl font-bold mt-1">₹{{ number_format($totalRevenue) }}</p>
                        <p class="text-sm text-warning mt-2">
                            <x-icon name="o-clock" class="w-4 h-4 inline" />
                            ₹{{ number_format($pendingPayments) }} pending
                        </p>
                    </div>
                    <div class="p-4 bg-success/10 rounded-full">
                        <x-icon name="o-currency-rupee" class="w-8 h-8 text-success" />
                    </div>
                </div>
            </x-card>

            {{-- Certificates Card --}}
            <x-card shadow>
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Certificates Issued</p>
                        <p class="text-3xl font-bold mt-1">{{ number_format($totalCertificates) }}</p>
                    </div>
                    <div class="p-4 bg-info/10 rounded-full">
                        <x-icon name="o-document-check" class="w-8 h-8 text-info" />
                    </div>
                </div>
            </x-card>
        @elseif($user->isStudent())
            {{-- Exams Taken Card --}}
            <x-card shadow>
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Exams Taken</p>
                        <p class="text-3xl font-bold mt-1">{{ number_format($totalExams) }}</p>
                    </div>
                    <div class="p-4 bg-primary/10 rounded-full">
                        <x-icon name="o-clipboard-document-check" class="w-8 h-8 text-primary" />
                    </div>
                </div>
            </x-card>

            {{-- Certificates Card --}}
            <x-card shadow>
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Certificates Earned</p>
                        <p class="text-3xl font-bold mt-1">{{ number_format($totalCertificates) }}</p>
                    </div>
                    <div class="p-4 bg-success/10 rounded-full">
                        <x-icon name="o-document-check" class="w-8 h-8 text-success" />
                    </div>
                </div>
            </x-card>

            {{-- Payments Card --}}
            <x-card shadow>
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Total Paid</p>
                        <p class="text-3xl font-bold mt-1">₹{{ number_format($totalRevenue) }}</p>
                        <p class="text-sm text-warning mt-2">
                            <x-icon name="o-clock" class="w-4 h-4 inline" />
                            ₹{{ number_format($pendingPayments) }} pending
                        </p>
                    </div>
                    <div class="p-4 bg-info/10 rounded-full">
                        <x-icon name="o-currency-rupee" class="w-8 h-8 text-info" />
                    </div>
                </div>
            </x-card>
        @endif
    </div>

    {{-- Charts Section (Admin Only) --}}
    @if($user->isAdmin() && (!empty($enrollmentChart) || !empty($revenueChart) || !empty($coursePopularityChart)))
        <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-6 mb-8">
            {{-- Enrollment Trend --}}
            @if(!empty($enrollmentChart))
                <x-card title="Enrollment Trend" shadow>
                    <div class="h-64">
                        <canvas id="enrollmentChart"></canvas>
                    </div>
                </x-card>
            @endif

            {{-- Revenue Trend --}}
            @if(!empty($revenueChart))
                <x-card title="Revenue Trend" shadow>
                    <div class="h-64">
                        <canvas id="revenueChart"></canvas>
                    </div>
                </x-card>
            @endif

            {{-- Course Popularity --}}
            @if(!empty($coursePopularityChart))
                <x-card title="Popular Courses" shadow>
                    <div class="h-64 flex items-center justify-center">
                        <canvas id="coursePopularityChart"></canvas>
                    </div>
                </x-card>
            @endif
        </div>
    @endif

    {{-- Recent Activities --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        {{-- Recent Enrollments (Admin & Center) --}}
        @if(($user->isAdmin() || $user->isCenter()) && count($recentEnrollments) > 0)
            <x-card title="Recent Enrollments" shadow>
                <x-table :headers="[
                    ['key' => 'student', 'label' => 'Student'],
                    ['key' => 'course', 'label' => 'Course'],
                    ['key' => 'date', 'label' => 'Date']
                ]" :rows="$recentEnrollments">
                    @scope('cell_student', $enrollment)
                        <div>
                            <p class="font-semibold">{{ $enrollment->first_name }} {{ $enrollment->surname }}</p>
                            @if($user->isAdmin())
                                <p class="text-sm text-gray-500">{{ $enrollment->center->name ?? 'N/A' }}</p>
                            @endif
                        </div>
                    @endscope
                    @scope('cell_course', $enrollment)
                        <span class="badge badge-primary badge-sm">{{ $enrollment->course->name ?? 'N/A' }}</span>
                    @endscope
                    @scope('cell_date', $enrollment)
                        <span class="text-sm">{{ $enrollment->created_at->format('d M Y') }}</span>
                    @endscope
                </x-table>
            </x-card>
        @endif

        {{-- Recent Exam Results --}}
        @if(count($recentExamResults) > 0)
            <x-card title="Recent Exam Results" shadow>
                <x-table :headers="[
                    ['key' => 'student', 'label' => 'Student'],
                    ['key' => 'exam', 'label' => 'Exam'],
                    ['key' => 'score', 'label' => 'Score'],
                    ['key' => 'status', 'label' => 'Status']
                ]" :rows="$recentExamResults">
                    @scope('cell_student', $result)
                        <span class="font-semibold">{{ $result->student->first_name ?? '' }} {{ $result->student->surname ?? '' }}</span>
                    @endscope
                    @scope('cell_exam', $result)
                        <span class="text-sm">{{ $result->exam->title ?? 'N/A' }}</span>
                    @endscope
                    @scope('cell_score', $result)
                        <span class="font-bold">{{ $result->score ?? 0 }}%</span>
                    @endscope
                    @scope('cell_status', $result)
                        <x-badge 
                            :value="$result->result_status ?? 'pending'" 
                            :class="match($result->result_status) {
                                'passed' => 'badge-success',
                                'failed' => 'badge-error',
                                default => 'badge-warning'
                            }"
                        />
                    @endscope
                </x-table>
            </x-card>
        @endif

        {{-- Recent Payments (Admin & Center) --}}
        @if(($user->isAdmin() || $user->isCenter()) && count($recentPayments) > 0)
            <x-card title="Recent Payments" shadow>
                <x-table :headers="[
                    ['key' => 'invoice', 'label' => 'Invoice'],
                    ['key' => 'student', 'label' => 'Student'],
                    ['key' => 'amount', 'label' => 'Amount'],
                    ['key' => 'date', 'label' => 'Date']
                ]" :rows="$recentPayments">
                    @scope('cell_invoice', $payment)
                        <span class="font-mono text-sm">#{{ $payment->invoice_number }}</span>
                    @endscope
                    @scope('cell_student', $payment)
                        <span class="font-semibold">{{ $payment->student->first_name ?? '' }} {{ $payment->student->surname ?? '' }}</span>
                    @endscope
                    @scope('cell_amount', $payment)
                        <span class="font-bold text-success">₹{{ number_format($payment->amount) }}</span>
                    @endscope
                    @scope('cell_date', $payment)
                        <span class="text-sm">{{ $payment->paid_at ? Carbon\Carbon::parse($payment->paid_at)->format('d M Y') : 'N/A' }}</span>
                    @endscope
                </x-table>
            </x-card>
        @endif

        {{-- Upcoming Exams (Admin & Student) --}}
        @if(($user->isAdmin() || $user->isStudent()) && count($upcomingExams) > 0)
            <x-card title="Active Exams" shadow>
                <x-table :headers="[
                    ['key' => 'title', 'label' => 'Exam'],
                    ['key' => 'course', 'label' => 'Course'],
                    ['key' => 'duration', 'label' => 'Duration'],
                    ['key' => 'status', 'label' => 'Status']
                ]" :rows="$upcomingExams">
                    @scope('cell_title', $exam)
                        <span class="font-semibold">{{ $exam->title }}</span>
                    @endscope
                    @scope('cell_course', $exam)
                        <span class="badge badge-info badge-sm">{{ $exam->course->name ?? 'N/A' }}</span>
                    @endscope
                    @scope('cell_duration', $exam)
                        <span class="text-sm">{{ $exam->duration }} mins</span>
                    @endscope
                    @scope('cell_status', $exam)
                        <x-badge value="Active" class="badge-success" />
                    @endscope
                </x-table>
            </x-card>
        @endif
    </div>

    {{-- Quick Actions --}}
    <x-card title="Quick Actions" shadow>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            @if($user->isAdmin())
                <x-button label="Add Center" icon="o-plus" link="{{ route('admin.center.create') }}" class="btn-primary" responsive />
                <x-button label="Add Student" icon="o-user-plus" link="{{ route('admin.student.create') }}" class="btn-secondary" responsive />
                <x-button label="Add Course" icon="o-book-open" link="{{ route('admin.course.create') }}" class="btn-accent" responsive />
                <x-button label="View Reports" icon="o-chart-bar" link="{{ route('admin.reports') }}" class="btn-info" responsive />
            @elseif($user->isCenter())
                <x-button label="Add Student" icon="o-user-plus" link="{{ route('center.student.create') }}" class="btn-primary" responsive />
                <x-button label="View Students" icon="o-user-group" link="{{ route('center.student.index') }}" class="btn-secondary" responsive />
                <x-button label="Invoices" icon="o-document-text" link="{{ route('center.invoice.index') }}" class="btn-accent" responsive />
                <x-button label="Profile" icon="o-user" link="{{ route('center.profile') }}" class="btn-info" responsive />
            @elseif($user->isStudent())
                <x-button label="My Courses" icon="o-book-open" link="{{ route('student.course.index') }}" class="btn-primary" responsive />
                <x-button label="Take Exam" icon="o-clipboard-document-check" link="{{ route('student.exam.index') }}" class="btn-secondary" responsive />
                <x-button label="Results" icon="o-chart-bar" link="{{ route('student.result.index') }}" class="btn-accent" responsive />
                <x-button label="Certificates" icon="o-document-check" link="{{ route('student.certificate.index') }}" class="btn-info" responsive />
            @endif
        </div>
    </x-card>
</div>

{{-- Chart.js Script --}}
@if($user->isAdmin())
    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Enrollment Chart
            @if(!empty($enrollmentChart))
                const enrollmentCtx = document.getElementById('enrollmentChart').getContext('2d');
                new Chart(enrollmentCtx, @json($enrollmentChart));
            @endif
            
            // Revenue Chart
            @if(!empty($revenueChart))
                const revenueCtx = document.getElementById('revenueChart').getContext('2d');
                new Chart(revenueCtx, @json($revenueChart));
            @endif
            
            // Course Popularity Chart
            @if(!empty($coursePopularityChart))
                const courseCtx = document.getElementById('coursePopularityChart').getContext('2d');
                new Chart(courseCtx, @json($coursePopularityChart));
            @endif
        });
    </script>
    @endpush
@endif
