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

<body class="bg-base-200 min-h-screen">
    <div class="container mx-auto px-4 py-8 printable">
        <!-- Header Section -->
        <div class="flex justify-between items-start lg:items-center flex-col lg:flex-row mb-8 gap-4">
            <div>
                <h1 class="text-3xl font-bold text-base-content">
                    {{ isset($certificate) ? 'Certificate' : 'Student' }} Results Portal
                </h1>
                <div class="breadcrumbs text-sm">
                    <ul class="flex sm:flex-nowrap flex-wrap">
                        <li>
                            <span>{{ isset($certificate) ? 'Certificate' : 'Student' }} Results</span>
                        </li>
                        <li>
                            <span>{{ $student->tiitvt_reg_no }}</span>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="flex gap-3 justify-end sm:w-auto w-full">
                <button onclick="window.print()" class="btn btn-primary btn-outline">
                    <i class="fas fa-print mr-2"></i>
                    Print Results
                </button>
            </div>
        </div>

        <hr class="mb-8">

        <!-- Student Information Card -->
        <x-card class="mb-8" title="{{ isset($certificate) ? 'Student Information' : 'Student Information' }}">
            <x-slot:menu>
                <x-badge value="{{ isset($certificate) ? 'Certificate' : 'Student' }}" class="badge-success" />
            </x-slot:menu>

            <div class="flex flex-col items-center text-center space-y-4">
                <div class="avatar">
                    <div
                        class="w-24 h-24 rounded-lg bg-primary text-primary-content text-2xl font-bold flex items-center justify-center">
                        @if (isset($certificate))
                            {{ substr($student->full_name, 0, 1) }}{{ substr(explode(' ', $student->full_name)[1] ?? '', 0, 1) }}
                        @else
                            {{ substr($student->first_name, 0, 1) }}{{ substr($student->surname ?? '', 0, 1) }}
                        @endif
                    </div>
                </div>

                <div>
                    <h2 class="text-2xl font-bold">
                        @if (isset($certificate))
                            {{ $student->full_name }}
                        @else
                            {{ $student->first_name }}{{ $student->fathers_name ? ' ' . $student->fathers_name : '' }}{{ $student->surname ? ' ' . $student->surname : '' }}
                        @endif
                    </h2>
                    <p class="text-base-content/70">Registration: {{ $student->tiitvt_reg_no }}</p>
                </div>

                @if ($student->center)
                    <div class="flex items-center gap-2 text-sm text-base-content/70">
                        <x-icon name="o-building-office" class="w-4 h-4" />
                        <span>{{ $student->center->name }}</span>
                    </div>
                @endif

                @if (!isset($certificate))
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-base-content/70">
                        @if ($student->email)
                            <div class="flex items-center gap-2">
                                <x-icon name="o-envelope" class="w-4 h-4" />
                                <span>{{ $student->email }}</span>
                            </div>
                        @endif
                        @if ($student->phone)
                            <div class="flex items-center gap-2">
                                <x-icon name="o-phone" class="w-4 h-4" />
                                <span>{{ $student->phone }}</span>
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        </x-card>

        <!-- Latest Result/Certificate Information -->
        <x-card class="mb-8" title="{{ isset($certificate) ? 'Certificate Details' : 'Latest Exam Result' }}">
            <x-slot:menu>
                <x-badge value="{{ $latestResult->percentage >= 50 ? 'PASSED' : 'FAILED' }}"
                    class="{{ $latestResult->percentage >= 50 ? 'badge-success' : 'badge-error' }}" />
            </x-slot:menu>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="stat bg-base-100 rounded-lg p-6">
                    <div class="stat-title">{{ isset($certificate) ? 'Course' : 'Course' }}</div>
                    <div class="stat-value text-primary">
                        @if (isset($certificate))
                            {{ $certificate->course_name }}
                        @else
                            {{ $latestResult->exam->course->name }}
                        @endif
                    </div>
                </div>

                <div class="stat bg-base-100 rounded-lg p-6">
                    <div class="stat-title">Score</div>
                    <div class="stat-value text-secondary">{{ number_format($latestResult->percentage, 1) }}%</div>
                </div>

                <div class="stat bg-base-100 rounded-lg p-6">
                    <div class="stat-title">Grade</div>
                    <div class="stat-value text-accent">
                        @if (isset($certificate))
                            {{ $certificate->grade ?? 'N/A' }}
                        @else
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
                        @endif
                    </div>
                </div>
            </div>

            @if (isset($certificate) && $certificate->issued_on)
                <div class="mt-6 text-center">
                    <div class="inline-flex items-center gap-2 px-4 py-2 bg-base-100 rounded-lg">
                        <x-icon name="o-calendar" class="w-4 h-4" />
                        <span class="text-sm">Issued on: {{ $certificate->issued_on->format('F d, Y') }}</span>
                    </div>
                </div>
            @endif
        </x-card>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="stat bg-primary text-primary-content rounded-lg">
                <div class="stat-figure">
                    <x-icon name="o-academic-cap" class="w-8 h-8" />
                </div>
                <div class="stat-title text-primary-content/80">Total
                    {{ isset($certificate) ? 'Certificates' : 'Exams' }}</div>
                <div class="stat-value">{{ $totalExams }}</div>
            </div>

            <div class="stat bg-secondary text-secondary-content rounded-lg">
                <div class="stat-figure">
                    <x-icon name="o-chart-bar" class="w-8 h-8" />
                </div>
                <div class="stat-title text-secondary-content/80">Average Score</div>
                <div class="stat-value">{{ number_format($averagePercentage, 1) }}%</div>
            </div>

            <div class="stat bg-accent text-accent-content rounded-lg">
                <div class="stat-figure">
                    <x-icon name="o-check-circle" class="w-8 h-8" />
                </div>
                <div class="stat-title text-accent-content/80">Passed
                    {{ isset($certificate) ? 'Certificates' : 'Exams' }}</div>
                <div class="stat-value">{{ $passedExams }}</div>
            </div>

            <div class="stat bg-info text-info-content rounded-lg">
                <div class="stat-figure">
                    <x-icon name="o-trophy" class="w-8 h-8" />
                </div>
                <div class="stat-title text-info-content/80">Success Rate</div>
                <div class="stat-value">
                    {{ $totalExams > 0 ? number_format(($passedExams / $totalExams) * 100, 1) : 0 }}%</div>
            </div>
        </div>

        <!-- Results Table -->
        <x-card title="{{ isset($certificate) ? 'Certificate Details' : 'All Exam Results' }}">
            <x-slot:title>
                <div class="flex items-center gap-2">
                    <x-icon name="o-document-text" class="w-5 h-5" />
                    {{ isset($certificate) ? 'Certificate Information' : 'Exam Results' }}
                </div>
            </x-slot:title>

            <div class="overflow-x-auto">
                <table class="table table-zebra">
                    <thead>
                        <tr>
                            <th>{{ isset($certificate) ? 'Course' : 'Course' }}</th>
                            @if (!isset($certificate))
                                <th>Category</th>
                            @endif
                            <th>Score</th>
                            <th>Grade</th>
                            <th>Status</th>
                            <th>{{ isset($certificate) ? 'Issued Date' : 'Date' }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($examResults as $result)
                            <tr>
                                <td>
                                    <div class="flex items-center gap-3">
                                        <div class="avatar">
                                            <div
                                                class="w-10 h-10 rounded bg-primary text-primary-content flex items-center justify-center">
                                                <x-icon name="o-academic-cap" class="w-5 h-5" />
                                            </div>
                                        </div>
                                        <div>
                                            @if (isset($certificate))
                                                <div class="font-bold">{{ $result->course_name }}</div>
                                                <div class="text-sm opacity-50">Certificate</div>
                                            @else
                                                <div class="font-bold">{{ $result->exam->course->name }}</div>
                                                <div class="text-sm opacity-50">{{ $result->exam->exam_id }}</div>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                @if (!isset($certificate))
                                    <td>
                                        <x-badge value="{{ $result->category->name ?? 'N/A' }}"
                                            class="badge-primary" />
                                    </td>
                                @endif
                                <td>
                                    <div
                                        class="text-lg font-bold {{ $result->percentage >= 50 ? 'text-success' : 'text-error' }}">
                                        {{ number_format($result->percentage, 1) }}%
                                    </div>
                                </td>
                                <td>
                                    <div class="text-lg font-bold">
                                        @if (isset($certificate))
                                            {{ $result->grade ?? 'N/A' }}
                                        @else
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
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <x-badge value="{{ $result->percentage >= 50 ? 'PASSED' : 'FAILED' }}"
                                        class="{{ $result->percentage >= 50 ? 'badge-success' : 'badge-error' }}" />
                                </td>
                                <td>
                                    <div class="text-sm">
                                        @if (isset($certificate))
                                            {{ $result->issued_on ? $result->issued_on->format('M d, Y') : 'N/A' }}
                                        @else
                                            {{ $result->submitted_at ? $result->submitted_at->format('M d, Y') : 'N/A' }}
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-card>

        <!-- QR Code Section (if available) -->
        @if (isset($certificate) && $certificate->qr_code_path)
            <x-card class="mt-8" title="Certificate Verification">
                <x-slot:title>
                    <div class="flex items-center gap-2">
                        <x-icon name="o-qr-code" class="w-5 h-5" />
                        QR Code Verification
                    </div>
                </x-slot:title>

                <div class="text-center">
                    <div class="inline-block p-4 bg-base-100 rounded-lg">
                        <img src="{{ asset('storage/' . $certificate->qr_code_path) }}" alt="Certificate QR Code"
                            class="w-32 h-32 mx-auto">
                    </div>
                    <p class="text-sm text-base-content/70 mt-4">
                        <x-icon name="o-shield-check" class="w-4 h-4 inline mr-1" />
                        Scan this QR code to verify the authenticity of this certificate
                    </p>
                </div>
            </x-card>
        @endif

        <!-- Footer -->
        <div class="text-center mt-8 text-base-content/70">
            <p class="text-sm">
                <x-icon name="o-shield-check" class="w-4 h-4 inline mr-1" />
                This is your {{ isset($certificate) ? 'certificate' : 'academic' }} record - keep it secure
            </p>
            <p class="text-xs mt-2 opacity-60">
                Generated on {{ now()->format('F d, Y \a\t g:i A') }}
            </p>
        </div>
    </div>
</body>

</html>
