<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $student->tiitvt_reg_no }} - Student Results</title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        @media print {
            body * {
                visibility: hidden;
            }

            .no-print,
            .no-print * {
                visibility: hidden;
            }
        }

        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .card-hover {
            transition: all 0.3s ease;
        }

        .card-hover:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }

        .stat-card {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }

        .stat-card.success {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }

        .stat-card.warning {
            background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
        }

        .stat-card.info {
            background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
        }
    </style>
</head>

<body class="gradient-bg min-h-screen">
    <!-- Header -->
    <div class="bg-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <div
                        class="w-16 h-16 bg-gradient-to-r from-blue-500 to-purple-600 rounded-full flex items-center justify-center">
                        <i class="fas fa-user-graduate text-white text-2xl"></i>
                    </div>
                    <div>
                        <h1 class="text-3xl font-bold text-gray-800">Student Results Portal</h1>
                        <p class="text-gray-600">View your exam results and academic progress</p>
                    </div>
                </div>
                <div class="text-right">
                    <p class="text-sm text-gray-500">Registration Number</p>
                    <p class="text-lg font-semibold text-blue-600">{{ $student->tiitvt_reg_no }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Student Information Card -->
        <div class="bg-white rounded-2xl shadow-xl p-8 mb-8 card-hover">
            <div class="flex items-center space-x-6">
                <div
                    class="w-24 h-24 bg-gradient-to-r from-green-400 to-blue-500 rounded-full flex items-center justify-center">
                    <span class="text-white text-3xl font-bold">
                        {{ substr($student->first_name, 0, 1) }}{{ substr($student->surname ?? '', 0, 1) }}
                    </span>
                </div>
                <div class="flex-1">
                    <h2 class="text-3xl font-bold text-gray-800 mb-2">
                        {{ $student->first_name }}{{ $student->fathers_name ? ' ' . $student->fathers_name : '' }}{{ $student->surname ? ' ' . $student->surname : '' }}
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-gray-600">
                        <div class="flex items-center space-x-2">
                            <i class="fas fa-building text-blue-500"></i>
                            <span><strong>Center:</strong> {{ $student->center->name ?? 'N/A' }}</span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <i class="fas fa-envelope text-green-500"></i>
                            <span><strong>Email:</strong> {{ $student->email ?? 'N/A' }}</span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <i class="fas fa-phone text-purple-500"></i>
                            <span><strong>Phone:</strong> {{ $student->phone ?? 'N/A' }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="stat-card text-white rounded-2xl p-6 card-hover">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm opacity-90">Total Exams</p>
                        <p class="text-3xl font-bold">{{ $totalExams }}</p>
                    </div>
                    <i class="fas fa-clipboard-list text-4xl opacity-80"></i>
                </div>
            </div>

            <div class="stat-card success text-white rounded-2xl p-6 card-hover">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm opacity-90">Average Score</p>
                        <p class="text-3xl font-bold">{{ number_format($averagePercentage, 1) }}%</p>
                    </div>
                    <i class="fas fa-chart-line text-4xl opacity-80"></i>
                </div>
            </div>

            <div class="stat-card warning text-white rounded-2xl p-6 card-hover">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm opacity-90">Passed Exams</p>
                        <p class="text-3xl font-bold">{{ $passedExams }}</p>
                    </div>
                    <i class="fas fa-check-circle text-4xl opacity-80"></i>
                </div>
            </div>

            <div class="stat-card info text-white rounded-2xl p-6 card-hover">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm opacity-90">Success Rate</p>
                        <p class="text-3xl font-bold">
                            {{ $totalExams > 0 ? number_format(($passedExams / $totalExams) * 100, 1) : 0 }}%</p>
                    </div>
                    <i class="fas fa-trophy text-4xl opacity-80"></i>
                </div>
            </div>
        </div>

        <!-- Latest Result Highlight -->
        <div class="bg-white rounded-2xl shadow-xl p-8 mb-8 card-hover">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-2xl font-bold text-gray-800">Latest Exam Result</h3>
                <div class="flex items-center space-x-2">
                    <span
                        class="px-3 py-1 rounded-full text-sm font-semibold {{ $latestResult->percentage >= 50 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                        {{ $latestResult->percentage >= 50 ? 'PASSED' : 'FAILED' }}
                    </span>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="text-center p-6 bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl">
                    <h4 class="text-lg font-semibold text-gray-700 mb-2">Course</h4>
                    <p class="text-xl font-bold text-blue-600">{{ $latestResult->exam->course->name }}</p>
                </div>

                <div class="text-center p-6 bg-gradient-to-r from-green-50 to-emerald-50 rounded-xl">
                    <h4 class="text-lg font-semibold text-gray-700 mb-2">Score</h4>
                    <p class="text-3xl font-bold text-green-600">{{ number_format($latestResult->percentage, 1) }}%</p>
                </div>

                <div class="text-center p-6 bg-gradient-to-r from-purple-50 to-pink-50 rounded-xl">
                    <h4 class="text-lg font-semibold text-gray-700 mb-2">Grade</h4>
                    <p class="text-3xl font-bold text-purple-600">
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
                    </p>
                </div>
            </div>
        </div>

        <!-- All Exam Results -->
        <div class="bg-white rounded-2xl shadow-xl p-8">
            <h3 class="text-2xl font-bold text-gray-800 mb-6">All Exam Results</h3>

            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-gray-50">
                            <th class="px-6 py-4 text-left text-sm font-semibold text-gray-700">Course</th>
                            <th class="px-6 py-4 text-left text-sm font-semibold text-gray-700">Category</th>
                            <th class="px-6 py-4 text-center text-sm font-semibold text-gray-700">Score</th>
                            <th class="px-6 py-4 text-center text-sm font-semibold text-gray-700">Grade</th>
                            <th class="px-6 py-4 text-center text-sm font-semibold text-gray-700">Status</th>
                            <th class="px-6 py-4 text-center text-sm font-semibold text-gray-700">Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach ($examResults as $result)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="flex items-center space-x-3">
                                        <div
                                            class="w-10 h-10 bg-gradient-to-r from-blue-400 to-purple-500 rounded-full flex items-center justify-center">
                                            <i class="fas fa-book text-white text-sm"></i>
                                        </div>
                                        <div>
                                            <p class="font-semibold text-gray-800">{{ $result->exam->course->name }}
                                            </p>
                                            <p class="text-sm text-gray-500">{{ $result->exam->exam_id }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                                        {{ $result->category->name ?? 'N/A' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span
                                        class="text-xl font-bold {{ $result->percentage >= 50 ? 'text-green-600' : 'text-red-600' }}">
                                        {{ number_format($result->percentage, 1) }}%
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="text-xl font-bold text-gray-700">
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
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span
                                        class="px-3 py-1 rounded-full text-sm font-semibold {{ $result->percentage >= 50 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                        {{ $result->percentage >= 50 ? 'PASSED' : 'FAILED' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center text-sm text-gray-600">
                                    {{ $result->submitted_at ? $result->submitted_at->format('d M Y') : 'N/A' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Footer -->
        <div class="text-center mt-8 text-white">
            <p class="text-sm opacity-80">
                <i class="fas fa-shield-alt mr-1"></i>
                This is your personal academic record - keep it secure
            </p>
            <p class="text-xs mt-2 opacity-60">
                Generated on {{ now()->format('F d, Y \a\t g:i A') }}
            </p>
        </div>
    </div>
</body>

</html>
