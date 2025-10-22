<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $student->tiitvt_reg_no }} - {{ isset($certificate) ? 'Certificate' : 'Student' }} Results</title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- DaisyUI -->
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.4.19/dist/full.min.css" rel="stylesheet" type="text/css" />

    <style>
        @media print {
            body * {
                visibility: hidden;
            }

            .printable,
            .printable * {
                visibility: visible;
            }

            .no-print,
            .no-print * {
                visibility: hidden;
            }
        }
    </style>
</head>

<body class="min-h-screen bg-base-300 py-4 px-2 sm:py-8 sm:px-4 lg:px-8">
    <div class="max-w-4xl mx-auto printable">
        <!-- Header Card -->
        <div class="bg-base-100 shadow-xl rounded-xl sm:rounded-2xl overflow-hidden relative mb-4 sm:mb-8">
            <div class="absolute top-2 right-2 sm:top-4 sm:right-4 flex gap-1 sm:gap-2">
                <button onclick="window.print()"
                    class="w-8 h-8 sm:w-12 sm:h-12 btn-xs sm:btn-sm btn-ghost btn-circle text-white no-print">
                    <i class="fas fa-print"></i>
                </button>
            </div>

            <!-- Header -->
            <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-4 py-4 sm:px-8 sm:py-6">
                <div class="text-center">
                    <div
                        class="mx-auto flex items-center justify-center h-12 w-12 sm:h-16 sm:w-16 rounded-full bg-white/20 mb-3 sm:mb-4">
                        <x-icon name="o-{{ isset($certificate) ? 'academic-cap' : 'user' }}"
                            class="h-6 w-6 sm:h-8 sm:w-8 text-white" />
                    </div>
                    <h1 class="text-xl sm:text-2xl lg:text-3xl font-bold text-white mb-2">
                        {{ isset($certificate) ? 'Certificate' : 'Student' }} Results Portal
                    </h1>
                    <p class="text-blue-100 text-sm sm:text-base lg:text-lg">
                        TIITVT - Technical Institute for IT & Vocational Training
                    </p>
                </div>
            </div>

            <div class="p-4 sm:p-8">
                <!-- Student Profile Header -->
                <x-card class="bg-base-200 mb-4 sm:mb-8">
                    <div class="flex flex-col sm:flex-row items-center space-y-4 sm:space-y-0 sm:space-x-6">
                        <div class="flex-shrink-0">
                            <div
                                class="h-16 w-16 sm:h-20 sm:w-20 rounded-full bg-gradient-to-r from-green-500 to-emerald-500 flex items-center justify-center">
                                <x-icon name="o-user" class="h-8 w-8 sm:h-10 sm:w-10 text-white" />
                            </div>
                        </div>
                        <div class="flex-1 text-center sm:text-left">
                            <h2 class="text-2xl sm:text-3xl font-bold mb-1">
                                @if (isset($certificate))
                                    {{ $student->full_name }}
                                @else
                                    {{ $student->first_name }}
                                    @if ($student->fathers_name)
                                        {{ $student->fathers_name }}
                                    @endif
                                    @if ($student->surname)
                                        {{ $student->surname }}
                                    @endif
                                @endif
                            </h2>
                            <p class="text-lg sm:text-xl mb-2">{{ $student->tiitvt_reg_no }}</p>
                            <div
                                class="flex flex-col sm:flex-row items-center sm:space-x-4 space-y-2 sm:space-y-0 text-sm">
                                <span class="flex items-center">
                                    <x-icon name="o-building-office" class="h-4 w-4 mr-1" />
                                    {{ $student->center->name ?? 'N/A' }}
                                </span>
                                @if (isset($certificate) && $certificate->issued_on)
                                    <span class="flex items-center">
                                        <x-icon name="o-calendar" class="h-4 w-4 mr-1" />
                                        Issued: {{ $certificate->issued_on->format('d M Y') }}
                                    </span>
                                @elseif(!isset($certificate) && $student->enrollment_date)
                                    <span class="flex items-center">
                                        <x-icon name="o-calendar" class="h-4 w-4 mr-1" />
                                        Enrolled:
                                        {{ \Carbon\Carbon::parse($student->enrollment_date)->format('d M Y') }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                </x-card>

                <!-- Information Grid -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-6 mb-4 sm:mb-8">
                    <!-- Personal Information -->
                    <x-card title="Personal Information" class="bg-base-200">
                        <div class="space-y-3 sm:space-y-4">
                            <div class="flex justify-between items-center py-2 border-b border-base-300">
                                <span class="text-xs sm:text-sm font-medium">Full Name</span>
                                <span class="text-xs sm:text-sm font-semibold text-right">
                                    @if (isset($certificate))
                                        {{ $student->full_name }}
                                    @else
                                        {{ $student->first_name }}
                                        @if ($student->fathers_name)
                                            {{ $student->fathers_name }}
                                        @endif
                                        @if ($student->surname)
                                            {{ $student->surname }}
                                        @endif
                                    @endif
                                </span>
                            </div>
                            <div class="flex justify-between items-center py-2 border-b border-base-100">
                                <span class="text-xs sm:text-sm font-medium">Registration Number</span>
                                <span class="text-xs sm:text-sm font-semibold">{{ $student->tiitvt_reg_no }}</span>
                            </div>
                            @if (!isset($certificate))
                                @if ($student->email)
                                    <div class="flex justify-between items-center py-2 border-b border-base-100">
                                        <span class="text-xs sm:text-sm font-medium">Email</span>
                                        <span class="text-xs sm:text-sm font-semibold">{{ $student->email }}</span>
                                    </div>
                                @endif
                                @if ($student->phone)
                                    <div class="flex justify-between items-center py-2 border-b border-base-100">
                                        <span class="text-xs sm:text-sm font-medium">Phone</span>
                                        <span class="text-xs sm:text-sm font-semibold">{{ $student->phone }}</span>
                                    </div>
                                @endif
                            @endif
                        </div>
                    </x-card>

                    <!-- Academic/Certificate Information -->
                    <x-card title="{{ isset($certificate) ? 'Certificate Information' : 'Academic Information' }}"
                        class="bg-base-200">
                        <div class="space-y-3 sm:space-y-4">
                            <div class="flex justify-between items-center py-2 border-b border-base-100">
                                <span class="text-xs sm:text-sm font-medium">Center</span>
                                <span
                                    class="text-xs sm:text-sm font-semibold">{{ $student->center->name ?? 'N/A' }}</span>
                            </div>
                            @if (isset($certificate))
                                <div class="flex justify-between items-center py-2 border-b border-base-100">
                                    <span class="text-xs sm:text-sm font-medium">Course</span>
                                    <span
                                        class="text-xs sm:text-sm font-semibold">{{ $certificate->course_name }}</span>
                                </div>
                                @if ($certificate->issued_on)
                                    <div class="flex justify-between items-center py-2 border-b border-base-100">
                                        <span class="text-xs sm:text-sm font-medium">Issued Date</span>
                                        <span
                                            class="text-xs sm:text-sm font-semibold">{{ $certificate->issued_on->format('d M Y') }}</span>
                                    </div>
                                @endif
                            @else
                                @if ($student->enrollment_date)
                                    <div class="flex justify-between items-center py-2 border-b border-base-100">
                                        <span class="text-xs sm:text-sm font-medium">Enrollment Date</span>
                                        <span
                                            class="text-xs sm:text-sm font-semibold">{{ \Carbon\Carbon::parse($student->enrollment_date)->format('d M Y') }}</span>
                                    </div>
                                @endif
                            @endif
                        </div>
                    </x-card>
                </div>

                <!-- Statistics Cards -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 mb-4 sm:mb-8">
                    <div class="stat bg-primary text-primary-content rounded-lg">
                        <div class="stat-figure">
                            <x-icon name="o-{{ isset($certificate) ? 'academic-cap' : 'clipboard-document-list' }}"
                                class="h-6 w-6" />
                        </div>
                        <div class="stat-title text-primary-content/80 text-xs">Total
                            {{ isset($certificate) ? 'Certificates' : 'Exams' }}</div>
                        <div class="stat-value text-lg sm:text-xl">{{ $totalExams }}</div>
                    </div>

                    <div class="stat bg-secondary text-secondary-content rounded-lg">
                        <div class="stat-figure">
                            <x-icon name="o-chart-bar" class="h-6 w-6" />
                        </div>
                        <div class="stat-title text-secondary-content/80 text-xs">Average Score</div>
                        <div class="stat-value text-lg sm:text-xl">{{ number_format($averagePercentage, 1) }}%</div>
                    </div>

                    <div class="stat bg-accent text-accent-content rounded-lg">
                        <div class="stat-figure">
                            <x-icon name="o-check-circle" class="h-6 w-6" />
                        </div>
                        <div class="stat-title text-accent-content/80 text-xs">Passed</div>
                        <div class="stat-value text-lg sm:text-xl">{{ $passedExams }}</div>
                    </div>

                    <div class="stat bg-info text-info-content rounded-lg">
                        <div class="stat-figure">
                            <x-icon name="o-trophy" class="h-6 w-6" />
                        </div>
                        <div class="stat-title text-info-content/80 text-xs">Success Rate</div>
                        <div class="stat-value text-lg sm:text-xl">
                            {{ $totalExams > 0 ? number_format(($passedExams / $totalExams) * 100, 1) : 0 }}%</div>
                    </div>
                </div>

                <!-- Latest Result/Certificate Highlight -->
                <x-card class="bg-base-200 mb-4 sm:mb-8">
                    <x-slot:title>
                        <div class="flex items-center gap-2">
                            <x-icon name="o-{{ isset($certificate) ? 'academic-cap' : 'chart-bar' }}"
                                class="h-5 w-5" />
                            {{ isset($certificate) ? 'Certificate Details' : 'Latest Exam Result' }}
                        </div>
                    </x-slot:title>
                    <x-slot:menu>
                        <x-badge value="{{ $latestResult->percentage >= 50 ? 'PASSED' : 'FAILED' }}"
                            class="{{ $latestResult->percentage >= 50 ? 'badge-success' : 'badge-error' }}" />
                    </x-slot:menu>

                    @if (isset($certificate) && $certificate->data && isset($certificate->data['subjects']))
                        <!-- Certificate Subject-wise Breakdown -->
                        <div class="space-y-4">
                            <div class="bg-base-100 rounded-lg p-4 sm:p-6">
                                <div class="flex justify-between items-center mb-4">
                                    <h4 class="text-lg sm:text-xl font-bold">{{ $certificate->course_name }}</h4>
                                    <div class="text-right">
                                        <div
                                            class="text-2xl sm:text-3xl font-bold {{ $latestResult->percentage >= 50 ? 'text-success' : 'text-error' }}">
                                            {{ number_format($latestResult->percentage, 1) }}%
                                        </div>
                                        <div class="text-base sm:text-lg font-semibold text-accent">
                                            {{ $certificate->grade ?? 'N/A' }}
                                        </div>
                                    </div>
                                </div>

                                <!-- Subject-wise Results -->
                                <div class="space-y-3">
                                    <h5 class="text-xs sm:text-sm font-semibold uppercase tracking-wide opacity-70">
                                        Subject-wise Results</h5>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 sm:gap-3">
                                        @foreach ($certificate->data['subjects'] as $subject)
                                            @php
                                                $subjectPercentage =
                                                    $subject['maximum'] > 0
                                                        ? ($subject['obtained'] / $subject['maximum']) * 100
                                                        : 0;
                                            @endphp
                                            <div
                                                class="flex justify-between items-center bg-base-300 rounded-lg px-3 py-2 sm:px-4 sm:py-3">
                                                <div>
                                                    <span
                                                        class="text-xs sm:text-sm font-medium">{{ $subject['name'] }}</span>
                                                </div>
                                                <div class="text-right">
                                                    <span class="text-xs sm:text-sm font-semibold">
                                                        {{ $subject['obtained'] }}/{{ $subject['maximum'] }}
                                                    </span>
                                                    <span
                                                        class="text-xs ml-2 {{ strtoupper($subject['result']) === 'PASS' ? 'text-success' : 'text-error' }}">
                                                        {{ strtoupper($subject['result']) }}
                                                    </span>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>

                                    @if (isset($certificate->data['total_marks']) && isset($certificate->data['total_marks_obtained']))
                                        <div class="mt-4 pt-3 border-t border-base-300">
                                            <div class="flex justify-between items-center">
                                                <span class="text-sm font-semibold">Total Marks</span>
                                                <span class="text-sm font-bold">
                                                    {{ $certificate->data['total_marks_obtained'] }}/{{ $certificate->data['total_marks'] }}
                                                </span>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @else
                        <!-- Regular Student Exam Results -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 sm:gap-6">
                            <div class="bg-base-100 rounded-lg p-4 sm:p-6 text-center">
                                <div class="text-sm sm:text-base font-medium mb-2">Course</div>
                                <div class="text-lg sm:text-xl font-bold text-primary">
                                    {{ $latestResult->exam->course->name }}
                                </div>
                            </div>

                            <div class="bg-base-100 rounded-lg p-4 sm:p-6 text-center">
                                <div class="text-sm sm:text-base font-medium mb-2">Score</div>
                                <div
                                    class="text-2xl sm:text-3xl font-bold {{ $latestResult->percentage >= 50 ? 'text-success' : 'text-error' }}">
                                    {{ number_format($latestResult->percentage, 1) }}%
                                </div>
                            </div>

                            <div class="bg-base-100 rounded-lg p-4 sm:p-6 text-center">
                                <div class="text-sm sm:text-base font-medium mb-2">Grade</div>
                                <div class="text-2xl sm:text-3xl font-bold text-accent">
                                    @if ($latestResult->percentage >= 90)
                                        A+
                                    @elseif($latestResult->percentage >= 80)
                                        A
                                    @elseif($latestResult->percentage >= 70)
                                        B
                                    @elseif($latestResult->percentage >= 60)
                                        C
                                    @elseif($latestResult->percentage >= 50)
                                        D
                                    @else
                                        F
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endif
                </x-card>

                @if (!isset($certificate))
                    <!-- Results Table -->
                    <x-card title="All Exam Results" class="bg-base-200">
                        <x-slot:title>
                            <div class="flex items-center gap-2">
                                <x-icon name="o-document-text" class="h-5 w-5" />
                                Exam Results
                            </div>
                        </x-slot:title>

                        <div class="space-y-4 sm:space-y-6">
                            @foreach ($examResults as $result)
                                <div class="bg-base-100 rounded-xl p-4 sm:p-6">
                                    <div
                                        class="flex flex-col sm:flex-row sm:justify-between sm:items-start mb-4 space-y-3 sm:space-y-0">
                                        <div class="flex-1">
                                            <div class="flex items-center space-x-3 mb-2">
                                                <div
                                                    class="h-8 w-8 sm:h-10 sm:w-10 rounded-lg bg-primary flex items-center justify-center">
                                                    <x-icon name="o-book-open"
                                                        class="h-4 w-4 sm:h-5 sm:w-5 text-white" />
                                                </div>
                                                <div>
                                                    <h4 class="text-lg sm:text-xl font-bold">
                                                        {{ $result->exam->course->name }}</h4>
                                                    <p class="text-sm sm:text-base">{{ $result->exam->exam_id }}</p>
                                                </div>
                                            </div>
                                            @if ($result->category)
                                                <p class="text-xs sm:text-sm opacity-70">{{ $result->category->name }}
                                                </p>
                                            @endif
                                        </div>
                                        <div class="text-center sm:text-right">
                                            <div
                                                class="text-2xl sm:text-3xl font-bold mb-1 {{ $result->percentage >= 50 ? 'text-success' : 'text-error' }}">
                                                {{ number_format($result->percentage, 1) }}%
                                            </div>
                                            <div class="text-base sm:text-lg font-semibold mb-1">
                                                @if ($result->percentage >= 90)
                                                    A+
                                                @elseif($result->percentage >= 80)
                                                    A
                                                @elseif($result->percentage >= 70)
                                                    B
                                                @elseif($result->percentage >= 60)
                                                    C
                                                @elseif($result->percentage >= 50)
                                                    D
                                                @else
                                                    F
                                                @endif
                                            </div>
                                            <x-badge value="{{ $result->percentage >= 50 ? 'PASSED' : 'FAILED' }}"
                                                class="{{ $result->percentage >= 50 ? 'badge-success' : 'badge-error' }} badge-sm" />
                                        </div>
                                    </div>

                                    <div class="flex justify-between items-center text-sm">
                                        <span class="opacity-70">
                                            {{ $result->submitted_at ? $result->submitted_at->format('d M Y') : 'N/A' }}
                                        </span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </x-card>
                @endif

                <!-- QR Code Section (if available) -->
                @if (isset($certificate) && $certificate->qr_code_path)
                    <x-card class="bg-base-200 mt-4 sm:mt-8">
                        <x-slot:title>
                            <div class="flex items-center gap-2">
                                <x-icon name="o-qr-code" class="h-5 w-5" />
                                QR Code Verification
                            </div>
                        </x-slot:title>

                        <div class="text-center">
                            <div class="inline-block p-4 bg-base-100 rounded-lg">
                                <img src="{{ asset('storage/' . $certificate->qr_code_path) }}"
                                    alt="Certificate QR Code" class="w-32 h-32 mx-auto">
                            </div>
                            <p class="text-sm opacity-70 mt-4">
                                <x-icon name="o-shield-check" class="h-4 w-4 inline mr-1" />
                                Scan this QR code to verify the authenticity of this certificate
                            </p>
                        </div>
                    </x-card>
                @endif
            </div>
        </div>

        <!-- Footer -->
        <div class="text-center text-xs sm:text-sm mt-4 sm:mt-8">
            <p class="flex items-center justify-center space-x-2">
                <x-icon name="o-shield-check" class="h-3 w-3 sm:h-4 sm:w-4" />
                <span>Secure {{ isset($certificate) ? 'Certificate' : 'Student' }} Verification System</span>
            </p>
            <p class="mt-1">Generated on {{ now()->format('F d, Y \a\t g:i A') }}</p>
        </div>
    </div>
</body>

</html>
