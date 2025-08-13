<?php

use Carbon\Carbon;
use Livewire\Volt\Component;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\Models\{Center, Student, Course, Exam, ExamResult, Invoice, Certificate};

new class extends Component {
    public $user;
    public $isLoading = true;

    // Statistics - will be loaded lazily
    public $statistics = [];

    // Recent activities - will be loaded on demand
    public $recentActivities = [];

    // Charts data - will be loaded automatically
    public $chartsData = [];

    // Mary Charts data
    public $enrollmentChart = [];
    public $revenueChart = [];
    public $coursePopularityChart = [];
    public $studentDistributionChart = [];

    public function mount()
    {
        $this->user = auth()->user();
        $this->loadEssentialData();
        $this->loadChartsData();
    }

    public function loadEssentialData()
    {
        // Load only essential data initially
        $this->statistics = $this->getCachedStatistics();
        $this->isLoading = false;
    }

    public function loadRecentActivities()
    {
        if (empty($this->recentActivities)) {
            $this->recentActivities = $this->getCachedRecentActivities();
        }
    }

    public function loadChartsData()
    {
        if ($this->user->isAdmin()) {
            $this->chartsData = $this->getCachedChartsData();
            $this->prepareMaryCharts();
        }
    }

    private function prepareMaryCharts()
    {
        // Enrollment Trend Chart (Bar Chart)
        $this->enrollmentChart = [
            'type' => 'bar',
            'data' => [
                'labels' => $this->chartsData['enrollment']['data']['labels'] ?? [],
                'datasets' => [
                    [
                        'label' => 'New Enrollments',
                        'data' => $this->chartsData['enrollment']['data']['datasets'][0]['data'] ?? [],
                        'backgroundColor' => 'rgba(59, 130, 246, 0.8)',
                        'borderColor' => 'rgb(59, 130, 246)',
                        'borderWidth' => 2,
                        'borderRadius' => 8,
                        'borderSkipped' => false,
                    ],
                ],
            ],
            'options' => [
                'responsive' => true,
                'maintainAspectRatio' => false,
                'plugins' => [
                    'legend' => [
                        'display' => true,
                        'position' => 'top',
                    ],
                    'title' => [
                        'display' => true,
                        'text' => 'Current Year Monthly Enrollment Trend',
                        'font' => [
                            'size' => 16,
                            'weight' => 'bold',
                        ],
                    ],
                ],
                'scales' => [
                    'y' => [
                        'beginAtZero' => true,
                        'grid' => [
                            'color' => 'rgba(0, 0, 0, 0.1)',
                        ],
                    ],
                    'x' => [
                        'grid' => [
                            'color' => 'rgba(0, 0, 0, 0.1)',
                        ],
                    ],
                ],
            ],
        ];

        // Revenue Trend Chart (Bar Chart)
        $this->revenueChart = [
            'type' => 'bar',
            'data' => [
                'labels' => $this->chartsData['revenue']['data']['labels'] ?? [],
                'datasets' => [
                    [
                        'label' => 'Revenue (₹)',
                        'data' => $this->chartsData['revenue']['data']['datasets'][0]['data'] ?? [],
                        'backgroundColor' => 'rgba(34, 197, 94, 0.8)',
                        'borderColor' => 'rgb(34, 197, 94)',
                        'borderWidth' => 2,
                        'borderRadius' => 8,
                        'borderSkipped' => false,
                    ],
                ],
            ],
            'options' => [
                'responsive' => true,
                'maintainAspectRatio' => false,
                'plugins' => [
                    'legend' => [
                        'display' => true,
                        'position' => 'top',
                    ],
                    'title' => [
                        'display' => true,
                        'text' => 'Current Year Monthly Revenue Trend',
                        'font' => [
                            'size' => 16,
                            'weight' => 'bold',
                        ],
                    ],
                ],
                'scales' => [
                    'y' => [
                        'beginAtZero' => true,
                        'grid' => [
                            'color' => 'rgba(0, 0, 0, 0.1)',
                        ],
                    ],
                    'x' => [
                        'grid' => [
                            'color' => 'rgba(0, 0, 0, 0.1)',
                        ],
                    ],
                ],
            ],
        ];

        // Course Popularity Chart (Pie Chart)
        $this->coursePopularityChart = [
            'type' => 'pie',
            'data' => [
                'labels' => $this->chartsData['course_popularity']['data']['labels'] ?? [],
                'datasets' => [
                    [
                        'data' => $this->chartsData['course_popularity']['data']['datasets'][0]['data'] ?? [],
                        'backgroundColor' => ['rgba(59, 130, 246, 0.8)', 'rgba(147, 51, 234, 0.8)', 'rgba(236, 72, 153, 0.8)', 'rgba(251, 146, 60, 0.8)', 'rgba(34, 197, 94, 0.8)', 'rgba(239, 68, 68, 0.8)', 'rgba(16, 185, 129, 0.8)', 'rgba(245, 158, 11, 0.8)'],
                        'borderColor' => ['rgb(59, 130, 246)', 'rgb(147, 51, 234)', 'rgb(236, 72, 153)', 'rgb(251, 146, 60)', 'rgb(34, 197, 94)', 'rgb(239, 68, 68)', 'rgb(16, 185, 129)', 'rgb(245, 158, 11)'],
                        'borderWidth' => 2,
                        'hoverOffset' => 4,
                    ],
                ],
            ],
            'options' => [
                'responsive' => true,
                'maintainAspectRatio' => false,
                'plugins' => [
                    'legend' => [
                        'display' => true,
                        'position' => 'right',
                        'labels' => [
                            'padding' => 20,
                            'usePointStyle' => true,
                            'pointStyle' => 'circle',
                        ],
                    ],
                    'title' => [
                        'display' => true,
                        'text' => 'Course Popularity',
                        'font' => [
                            'size' => 16,
                            'weight' => 'bold',
                        ],
                    ],
                ],
            ],
        ];

        // Student Distribution Chart (Pie Chart)
        $this->studentDistributionChart = [
            'type' => 'pie',
            'data' => [
                'labels' => ['Active Students', 'Completed Courses', 'Certified'],
                'datasets' => [
                    [
                        'data' => [$this->statistics['total_students'] ?? 0, $this->statistics['total_courses'] ?? 0, $this->statistics['total_certificates'] ?? 0],
                        'backgroundColor' => ['rgba(59, 130, 246, 0.8)', 'rgba(34, 197, 94, 0.8)', 'rgba(147, 51, 234, 0.8)'],
                        'borderColor' => ['rgb(59, 130, 246)', 'rgb(34, 197, 94)', 'rgb(147, 51, 234)'],
                        'borderWidth' => 2,
                        'hoverOffset' => 4,
                    ],
                ],
            ],
            'options' => [
                'responsive' => true,
                'maintainAspectRatio' => false,
                'plugins' => [
                    'legend' => [
                        'display' => true,
                        'position' => 'right',
                        'labels' => [
                            'padding' => 20,
                            'usePointStyle' => true,
                            'pointStyle' => 'circle',
                        ],
                    ],
                    'title' => [
                        'display' => true,
                        'text' => 'Student Distribution',
                        'font' => [
                            'size' => 16,
                            'weight' => 'bold',
                        ],
                    ],
                ],
            ],
        ];
    }

    private function getCachedStatistics()
    {
        $cacheKey = "dashboard_stats_{$this->user->id}_{$this->user->role}";

        return Cache::remember($cacheKey, 300, function () {
            // Cache for 5 minutes
            return $this->calculateStatistics();
        });
    }

    private function calculateStatistics()
    {
        if ($this->user->isAdmin()) {
            return $this->getAdminStatistics();
        } elseif ($this->user->isCenter()) {
            return $this->getCenterStatistics();
        } elseif ($this->user->isStudent()) {
            return $this->getStudentStatistics();
        }

        return [];
    }

    private function getAdminStatistics()
    {
        // Single optimized query for all counts
        $counts = DB::select("
            SELECT
                (SELECT COUNT(*) FROM centers) as total_centers,
                (SELECT COUNT(*) FROM students) as total_students,
                (SELECT COUNT(*) FROM courses) as total_courses,
                (SELECT COUNT(*) FROM exams) as total_exams,
                (SELECT COUNT(*) FROM certificates) as total_certificates,
                (SELECT COUNT(*) FROM students WHERE YEAR(created_at) = YEAR(CURDATE())) as current_year_enrollments
        ")[0];

        // Single query for revenue data
        $revenue = DB::select("
            SELECT
                COALESCE(SUM(CASE WHEN status = 'paid' THEN amount ELSE 0 END), 0) as total_revenue,
                COALESCE(SUM(CASE WHEN status = 'pending' THEN amount ELSE 0 END), 0) as pending_payments
            FROM invoices
        ")[0];

        return [
            'total_centers' => $counts->total_centers,
            'total_students' => $counts->total_students,
            'total_courses' => $counts->total_courses,
            'total_exams' => $counts->total_exams,
            'total_revenue' => $revenue->total_revenue,
            'pending_payments' => $revenue->pending_payments,
            'total_certificates' => $counts->total_certificates,
            'current_year_enrollments' => $counts->current_year_enrollments,
        ];
    }

    private function getCenterStatistics()
    {
        $center = $this->user->center;
        if (!$center) {
            return [];
        }

        // Single optimized query for center statistics
        $stats = DB::select(
            "
            SELECT
                COUNT(s.id) as total_students,
                COALESCE(SUM(CASE WHEN i.status = 'paid' THEN i.amount ELSE 0 END), 0) as total_revenue,
                COALESCE(SUM(CASE WHEN i.status = 'pending' THEN i.amount ELSE 0 END), 0) as pending_payments,
                (SELECT COUNT(*) FROM certificates WHERE student_id IN (SELECT id FROM students WHERE center_id = ?)) as total_certificates,
                (SELECT COUNT(*) FROM students WHERE center_id = ? AND YEAR(created_at) = YEAR(CURDATE())) as current_year_enrollments
            FROM students s
            LEFT JOIN invoices i ON s.id = i.student_id
            WHERE s.center_id = ?
        ",
            [$center->id, $center->id, $center->id],
        )[0];

        return [
            'total_students' => $stats->total_students,
            'total_revenue' => $stats->total_revenue,
            'pending_payments' => $stats->pending_payments,
            'total_certificates' => $stats->total_certificates,
            'current_year_enrollments' => $stats->current_year_enrollments,
        ];
    }

    private function getStudentStatistics()
    {
        $student = $this->user->students()->first();
        if (!$student) {
            return [];
        }

        // Single optimized query for student statistics
        $stats = DB::select(
            "
            SELECT
                COUNT(er.id) as total_exams,
                COUNT(c.id) as total_certificates,
                COALESCE(SUM(CASE WHEN i.status = 'paid' THEN i.amount ELSE 0 END), 0) as total_revenue,
                COALESCE(SUM(CASE WHEN i.status = 'pending' THEN i.amount ELSE 0 END), 0) as pending_payments
            FROM students s
            LEFT JOIN exam_results er ON s.id = er.student_id
            LEFT JOIN certificates c ON s.id = c.student_id
            LEFT JOIN invoices i ON s.id = i.student_id
            WHERE s.id = ?
        ",
            [$student->id],
        )[0];

        return [
            'total_exams' => $stats->total_exams,
            'total_certificates' => $stats->total_certificates,
            'total_revenue' => $stats->total_revenue,
            'pending_payments' => $stats->pending_payments,
        ];
    }

    private function getCachedRecentActivities()
    {
        $cacheKey = "dashboard_activities_{$this->user->id}_{$this->user->role}";

        return Cache::remember($cacheKey, 180, function () {
            // Cache for 3 minutes
            return $this->loadRecentActivitiesData();
        });
    }

    private function loadRecentActivitiesData()
    {
        $activities = [];

        if ($this->user->isAdmin()) {
            $activities = $this->getAdminRecentActivities();
        } elseif ($this->user->isCenter()) {
            $activities = $this->getCenterRecentActivities();
        } elseif ($this->user->isStudent()) {
            $activities = $this->getStudentRecentActivities();
        }

        return $activities;
    }

    private function getAdminRecentActivities()
    {
        // Optimized queries with proper eager loading and limits
        return [
            'enrollments' => Student::select('id', 'first_name', 'surname', 'created_at', 'center_id', 'course_id')
                ->with(['center:id,name', 'course:id,name'])
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get(),

            'exam_results' => ExamResult::select('id', 'student_id', 'exam_id', 'score', 'result_status', 'created_at')
                ->with(['student:id,first_name,surname', 'exam:id,title'])
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get(),

            'payments' => Invoice::select('id', 'invoice_number', 'student_id', 'amount', 'paid_at')->with('student:id,first_name,surname')->where('status', 'paid')->orderBy('paid_at', 'desc')->limit(5)->get(),

            'upcoming_exams' => Exam::select('id', 'title', 'course_id', 'duration')->with('course:id,name')->where('is_active', true)->orderBy('created_at', 'desc')->limit(5)->get(),
        ];
    }

    private function getCenterRecentActivities()
    {
        $center = $this->user->center;
        if (!$center) {
            return [];
        }

        $studentIds = $center->students()->pluck('id');

        return [
            'enrollments' => Student::select('id', 'first_name', 'surname', 'created_at', 'course_id')->with('course:id,name')->where('center_id', $center->id)->orderBy('created_at', 'desc')->limit(5)->get(),

            'exam_results' => ExamResult::select('id', 'student_id', 'exam_id', 'score', 'result_status', 'created_at')
                ->with(['student:id,first_name,surname', 'exam:id,title'])
                ->whereIn('student_id', $studentIds)
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get(),

            'payments' => Invoice::select('id', 'invoice_number', 'student_id', 'amount', 'paid_at')->with('student:id,first_name,surname')->whereIn('student_id', $studentIds)->where('status', 'paid')->orderBy('paid_at', 'desc')->limit(5)->get(),
        ];
    }

    private function getStudentRecentActivities()
    {
        $student = $this->user->students()->first();
        if (!$student) {
            return [];
        }

        return [
            'exam_results' => ExamResult::select('id', 'exam_id', 'score', 'result_status', 'created_at')->with('exam:id,title')->where('student_id', $student->id)->orderBy('created_at', 'desc')->limit(5)->get(),

            'upcoming_exams' => Exam::select('id', 'title', 'course_id', 'duration')->with('course:id,name')->where('is_active', true)->where('course_id', $student->course_id)->orderBy('created_at', 'desc')->limit(5)->get(),
        ];
    }

    private function getCachedChartsData()
    {
        $cacheKey = "dashboard_charts_{$this->user->id}";

        return Cache::remember($cacheKey, 600, function () {
            // Cache for 10 minutes
            return $this->calculateChartsData();
        });
    }

    private function calculateChartsData()
    {
        if (!$this->user->isAdmin()) {
            return [];
        }

        // Single optimized query for enrollment trend - Current Year
        $enrollmentData = DB::select("
            SELECT
                DATE_FORMAT(created_at, '%Y-%m') as month,
                COUNT(*) as count
            FROM students
            WHERE YEAR(created_at) = YEAR(CURDATE())
            GROUP BY month
            ORDER BY month
        ");

        // Single optimized query for revenue trend - Current Year
        $revenueData = DB::select("
            SELECT
                DATE_FORMAT(paid_at, '%Y-%m') as month,
                SUM(amount) as total
            FROM invoices
            WHERE status = 'paid' AND YEAR(paid_at) = YEAR(CURDATE())
            GROUP BY month
            ORDER BY month
        ");

        // Single optimized query for course popularity
        $courseData = DB::select("
            SELECT
                c.name as course_name,
                COUNT(s.id) as student_count
            FROM courses c
            LEFT JOIN students s ON c.id = s.course_id
            GROUP BY c.id, c.name
            ORDER BY student_count DESC
            LIMIT 5
        ");

        return [
            'enrollment' => $this->formatEnrollmentChart($enrollmentData),
            'revenue' => $this->formatRevenueChart($revenueData),
            'course_popularity' => $this->formatCoursePopularityChart($courseData),
        ];
    }

    private function formatEnrollmentChart($data)
    {
        // Create a complete year array with all months
        $currentYear = Carbon::now()->year;
        $allMonths = [];
        $monthlyData = [];

        for ($month = 1; $month <= 12; $month++) {
            $monthKey = sprintf('%04d-%02d', $currentYear, $month);
            $allMonths[] = Carbon::createFromDate($currentYear, $month, 1)->format('M Y');

            // Find data for this month or set to 0
            $monthData = collect($data)->firstWhere('month', $monthKey);
            $monthlyData[] = $monthData ? $monthData->count : 0;
        }

        return [
            'type' => 'line',
            'data' => [
                'labels' => $allMonths,
                'datasets' => [
                    [
                        'label' => 'New Enrollments',
                        'data' => $monthlyData,
                        'borderColor' => 'rgb(59, 130, 246)',
                        'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                        'tension' => 0.3,
                    ],
                ],
            ],
        ];
    }

    private function formatRevenueChart($data)
    {
        // Create a complete year array with all months
        $currentYear = Carbon::now()->year;
        $allMonths = [];
        $monthlyData = [];

        for ($month = 1; $month <= 12; $month++) {
            $monthKey = sprintf('%04d-%02d', $currentYear, $month);
            $allMonths[] = Carbon::createFromDate($currentYear, $month, 1)->format('M Y');

            // Find data for this month or set to 0
            $monthData = collect($data)->firstWhere('month', $monthKey);
            $monthlyData[] = $monthData ? $monthData->total : 0;
        }

        return [
            'type' => 'bar',
            'data' => [
                'labels' => $allMonths,
                'datasets' => [
                    [
                        'label' => 'Revenue (₹)',
                        'data' => $monthlyData,
                        'backgroundColor' => 'rgba(34, 197, 94, 0.8)',
                        'borderColor' => 'rgb(34, 197, 94)',
                        'borderWidth' => 1,
                    ],
                ],
            ],
        ];
    }

    private function formatCoursePopularityChart($data)
    {
        return [
            'type' => 'doughnut',
            'data' => [
                'labels' => collect($data)->pluck('course_name')->toArray(),
                'datasets' => [
                    [
                        'data' => collect($data)->pluck('student_count')->toArray(),
                        'backgroundColor' => ['rgba(59, 130, 246, 0.8)', 'rgba(147, 51, 234, 0.8)', 'rgba(236, 72, 153, 0.8)', 'rgba(251, 146, 60, 0.8)', 'rgba(34, 197, 94, 0.8)'],
                    ],
                ],
            ],
        ];
    }

    public function refreshData()
    {
        // Clear cache and reload data
        Cache::forget("dashboard_stats_{$this->user->id}_{$this->user->role}");
        Cache::forget("dashboard_activities_{$this->user->id}_{$this->user->role}");
        Cache::forget("dashboard_charts_{$this->user->id}");

        $this->statistics = $this->getCachedStatistics();
        $this->recentActivities = [];
        $this->chartsData = [];
    }
}; ?>
@section('cdn')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
@endsection
<div>
    {{-- Header --}}
    <div class="mb-6">
        <h1 class="text-3xl font-bold">Dashboard</h1>
        <p class="text-gray-600 dark:text-gray-400 mt-1">
            Welcome back, {{ auth()->user()->name }}!
        </p>
    </div>

    @if ($isLoading)
        {{-- Loading State --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            @for ($i = 0; $i < 4; $i++)
                <div class="animate-pulse">
                    <div class="h-24 bg-gray-200 rounded-lg"></div>
                </div>
            @endfor
        </div>
    @else
        {{-- Quick Actions --}}
        <x-card title="Quick Actions" shadow class="mb-6">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                @if ($user->isAdmin())
                    <x-button label="Add Center" icon="o-plus" link="{{ route('admin.center.create') }}"
                        class="btn-primary" responsive />
                    <x-button label="Add Student" icon="o-user-plus" link="{{ route('admin.student.create') }}"
                        class="btn-secondary" responsive />
                    <x-button label="Add Course" icon="o-book-open" link="{{ route('admin.course.create') }}"
                        class="btn-accent" responsive />
                    <x-button label="Category" icon="o-tag" link="{{ route('admin.category.index') }}"
                        class="btn-info" responsive />
                @elseif($user->isCenter())
                    <x-button label="Add Student" icon="o-user-plus" link="{{ route('center.student.create') }}"
                        class="btn-primary" responsive />
                    <x-button label="View Students" icon="o-user-group" link="{{ route('center.student.index') }}"
                        class="btn-secondary" responsive />
                    <x-button label="Invoices" icon="o-document-text" link="{{ route('center.invoice.index') }}"
                        class="btn-accent" responsive />
                    <x-button label="Profile" icon="o-user" link="{{ route('center.profile') }}" class="btn-info"
                        responsive />
                @elseif($user->isStudent())
                    <x-button label="My Courses" icon="o-book-open" link="{{ route('student.course.index') }}"
                        class="btn-primary" responsive />
                    <x-button label="Take Exam" icon="o-clipboard-document-check"
                        link="{{ route('student.exam.index') }}" class="btn-secondary" responsive />
                    <x-button label="Results" icon="o-chart-bar" link="{{ route('student.result.index') }}"
                        class="btn-accent" responsive />
                    <x-button label="Certificates" icon="o-document-check"
                        link="{{ route('student.certificate.index') }}" class="btn-info" responsive />
                @endif
            </div>
        </x-card>

        {{-- Statistics Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6 mb-8">
            @if ($user->isAdmin())
                {{-- Centers Card --}}
                <x-card shadow>
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Total Centers</p>
                            <p class="text-3xl font-bold mt-1">{{ number_format($statistics['total_centers'] ?? 0) }}
                            </p>
                            <p class="text-sm text-success mt-2">
                                <x-icon name="o-arrow-trending-up" class="w-4 h-4 inline" />
                                {{ $statistics['total_centers'] ?? 0 }} active
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
                            <p class="text-3xl font-bold mt-1">{{ number_format($statistics['total_students'] ?? 0) }}
                            </p>
                            <p class="text-sm text-success mt-2">
                                <x-icon name="o-user-group" class="w-4 h-4 inline" />
                                {{ $statistics['total_students'] ?? 0 }} active
                            </p>
                        </div>
                        <div class="p-4 bg-secondary/10 rounded-full">
                            <x-icon name="o-academic-cap" class="w-8 h-8 text-secondary" />
                        </div>
                    </div>
                </x-card>

                {{-- Current Year Enrollments Card --}}
                <x-card shadow>
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Current Year Enrollments</p>
                            <p class="text-3xl font-bold mt-1">
                                {{ number_format($statistics['current_year_enrollments'] ?? 0) }}
                            </p>
                            <p class="text-sm text-info mt-2">
                                <x-icon name="o-calendar" class="w-4 h-4 inline" />
                                {{ Carbon::now()->year }} enrollments
                            </p>
                        </div>
                        <div class="p-4 bg-info/10 rounded-full">
                            <x-icon name="o-calendar" class="w-8 h-8 text-info" />
                        </div>
                    </div>
                </x-card>

                {{-- Revenue Card --}}
                <x-card shadow>
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Total Revenue</p>
                            <p class="text-3xl font-bold mt-1">₹{{ number_format($statistics['total_revenue'] ?? 0) }}
                            </p>
                            <p class="text-sm text-warning mt-2">
                                <x-icon name="o-clock" class="w-4 h-4 inline" />
                                ₹{{ number_format($statistics['pending_payments'] ?? 0) }} pending
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
                            <p class="text-3xl font-bold mt-1">{{ $statistics['total_courses'] ?? 0 }}</p>
                            <p class="text-sm text-info mt-2">
                                <x-icon name="o-document-text" class="w-4 h-4 inline" />
                                {{ $statistics['total_exams'] ?? 0 }} exams
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
                            <p class="text-3xl font-bold mt-1">{{ number_format($statistics['total_students'] ?? 0) }}
                            </p>
                            <p class="text-sm text-success mt-2">
                                <x-icon name="o-user-group" class="w-4 h-4 inline" />
                                {{ $statistics['total_students'] ?? 0 }} active
                            </p>
                        </div>
                        <div class="p-4 bg-primary/10 rounded-full">
                            <x-icon name="o-academic-cap" class="w-8 h-8 text-primary" />
                        </div>
                    </div>
                </x-card>

                {{-- Current Year Enrollments Card --}}
                <x-card shadow>
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Current Year Enrollments</p>
                            <p class="text-3xl font-bold mt-1">
                                {{ number_format($statistics['current_year_enrollments'] ?? 0) }}
                            </p>
                            <p class="text-sm text-info mt-2">
                                <x-icon name="o-calendar" class="w-4 h-4 inline" />
                                {{ Carbon::now()->year }} enrollments
                            </p>
                        </div>
                        <div class="p-4 bg-info/10 rounded-full">
                            <x-icon name="o-academic-cap" class="w-8 h-8 text-info" />
                        </div>
                    </div>
                </x-card>

                {{-- Revenue Card --}}
                <x-card shadow>
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Revenue</p>
                            <p class="text-3xl font-bold mt-1">₹{{ number_format($statistics['total_revenue'] ?? 0) }}
                            </p>
                            <p class="text-sm text-warning mt-2">
                                <x-icon name="o-clock" class="w-4 h-4 inline" />
                                ₹{{ number_format($statistics['pending_payments'] ?? 0) }} pending
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
                            <p class="text-3xl font-bold mt-1">
                                {{ number_format($statistics['total_certificates'] ?? 0) }}</p>
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
                            <p class="text-3xl font-bold mt-1">{{ number_format($statistics['total_exams'] ?? 0) }}
                            </p>
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
                            <p class="text-3xl font-bold mt-1">
                                {{ number_format($statistics['total_certificates'] ?? 0) }}</p>
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
                            <p class="text-3xl font-bold mt-1">₹{{ number_format($statistics['total_revenue'] ?? 0) }}
                            </p>
                            <p class="text-sm text-warning mt-2">
                                <x-icon name="o-clock" class="w-4 h-4 inline" />
                                ₹{{ number_format($statistics['pending_payments'] ?? 0) }} pending
                            </p>
                        </div>
                        <div class="p-4 bg-info/10 rounded-full">
                            <x-icon name="o-currency-rupee" class="w-8 h-8 text-info" />
                        </div>
                    </div>
                </x-card>
            @endif
        </div>

        {{-- Charts Section (Admin Only) - Auto Loaded --}}
        @if ($user->isAdmin() && !empty($chartsData))
            {{-- Enrollment Trend Chart --}}
            <x-card title="Current Year Enrollment Trend" shadow
                class="bg-gradient-to-br from-blue-50 to-indigo-100 dark:from-blue-900/20 dark:to-indigo-900/20 mb-6">
                <div class="h-80 p-4">
                    <x-chart wire:model="enrollmentChart" class="h-full w-full" />
                </div>
            </x-card>

            <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-6 mb-8">
                {{-- Revenue Trend Chart --}}
                <x-card title="Current Year Revenue Trend" shadow
                    class="bg-gradient-to-br from-green-50 to-emerald-100 dark:from-green-900/20 dark:to-emerald-900/20">
                    <div class="h-80 p-4">
                        <x-chart wire:model="revenueChart" class="h-full w-full" />
                    </div>
                </x-card>

                {{-- Course Popularity Chart --}}
                <x-card title="Courses" shadow
                    class="bg-gradient-to-br from-purple-50 to-pink-100 dark:from-purple-900/20 dark:to-pink-900/20">
                    <div class="h-80 p-4">
                        <x-chart wire:model="coursePopularityChart" />
                    </div>
                </x-card>

                {{-- Student Distribution Chart --}}
                <x-card title="Students" shadow
                    class="bg-gradient-to-br from-orange-50 to-yellow-100 dark:from-orange-900/20 dark:to-yellow-900/20">
                    <div class="h-80 p-4">
                        <x-chart wire:model="studentDistributionChart" />
                    </div>
                </x-card>
            </div>
        @endif

        @if (!empty($recentActivities))
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                {{-- Recent Enrollments (Admin & Center) --}}
                @if (
                    ($user->isAdmin() || $user->isCenter()) &&
                        isset($recentActivities['enrollments']) &&
                        count($recentActivities['enrollments']) > 0)
                    <x-card title="Recent Enrollments" shadow>
                        <x-table :headers="[
                            ['key' => 'student', 'label' => 'Student'],
                            ['key' => 'course', 'label' => 'Course'],
                            ['key' => 'date', 'label' => 'Date'],
                        ]" :rows="$recentActivities['enrollments']">
                            @scope('cell_student', $enrollment)
                                <div>
                                    <p class="font-semibold">{{ $enrollment->first_name }} {{ $enrollment->surname }}</p>
                                    @if ($user->isAdmin())
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
                @if (isset($recentActivities['exam_results']) && count($recentActivities['exam_results']) > 0)
                    <x-card title="Recent Exam Results" shadow>
                        <x-table :headers="[
                            ['key' => 'student', 'label' => 'Student'],
                            ['key' => 'exam', 'label' => 'Exam'],
                            ['key' => 'score', 'label' => 'Score'],
                            ['key' => 'status', 'label' => 'Status'],
                        ]" :rows="$recentActivities['exam_results']">
                            @scope('cell_student', $result)
                                <span class="font-semibold">{{ $result->student->first_name ?? '' }}
                                    {{ $result->student->surname ?? '' }}</span>
                            @endscope
                            @scope('cell_exam', $result)
                                <span class="text-sm">{{ $result->exam->title ?? 'N/A' }}</span>
                            @endscope
                            @scope('cell_score', $result)
                                <span class="font-bold">{{ $result->score ?? 0 }}%</span>
                            @endscope
                            @scope('cell_status', $result)
                                <x-badge :value="$result->result_status ?? 'pending'" :class="match ($result->result_status) {
                                    'passed' => 'badge-success',
                                    'failed' => 'badge-error',
                                    default => 'badge-warning',
                                }" />
                            @endscope
                        </x-table>
                    </x-card>
                @endif

                {{-- Recent Payments (Admin & Center) --}}
                @if (
                    ($user->isAdmin() || $user->isCenter()) &&
                        isset($recentActivities['payments']) &&
                        count($recentActivities['payments']) > 0)
                    <x-card title="Recent Payments" shadow>
                        <x-table :headers="[
                            ['key' => 'invoice', 'label' => 'Invoice'],
                            ['key' => 'student', 'label' => 'Student'],
                            ['key' => 'amount', 'label' => 'Amount'],
                            ['key' => 'date', 'label' => 'Date'],
                        ]" :rows="$recentActivities['payments']">
                            @scope('cell_invoice', $payment)
                                <span class="font-mono text-sm">#{{ $payment->invoice_number }}</span>
                            @endscope
                            @scope('cell_student', $payment)
                                <span class="font-semibold">{{ $payment->student->first_name ?? '' }}
                                    {{ $payment->student->surname ?? '' }}</span>
                            @endscope
                            @scope('cell_amount', $payment)
                                <span class="font-bold text-success">₹{{ number_format($payment->amount) }}</span>
                            @endscope
                            @scope('cell_date', $payment)
                                <span
                                    class="text-sm">{{ $payment->paid_at ? Carbon::parse($payment->paid_at)->format('d M Y') : 'N/A' }}</span>
                            @endscope
                        </x-table>
                    </x-card>
                @endif

                {{-- Upcoming Exams (Admin & Student) --}}
                @if (
                    ($user->isAdmin() || $user->isStudent()) &&
                        isset($recentActivities['upcoming_exams']) &&
                        count($recentActivities['upcoming_exams']) > 0)
                    <x-card title="Active Exams" shadow>
                        <x-table :headers="[
                            ['key' => 'title', 'label' => 'Exam'],
                            ['key' => 'course', 'label' => 'Course'],
                            ['key' => 'duration', 'label' => 'Duration'],
                            ['key' => 'status', 'label' => 'Status'],
                        ]" :rows="$recentActivities['upcoming_exams']">
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
        @endif


    @endif
</div>
