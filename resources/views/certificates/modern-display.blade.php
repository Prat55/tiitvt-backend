<!DOCTYPE html
    PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>{{ $certificate->reg_no }} - {{ $certificate->student_name }}</title>
    <meta name="author" content="TIITVT" />

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Certificate Styles -->
    <style type="text/css">
        * {
            margin: 0;
            padding: 0;
            text-indent: 0;
        }

        p {
            color: black;
            font-family: "Arial", sans-serif;
            font-style: normal;
            font-weight: 600;
            text-decoration: none;
            font-size: 14.5pt;
            margin: 0pt;
        }

        .s1 {
            color: black;
            font-family: "Arial", sans-serif;
            font-style: normal;
            font-weight: 600;
            text-decoration: none;
            font-size: 11pt;
            vertical-align: 7pt;
        }

        .s2 {
            color: black;
            font-family: "Arial", sans-serif;
            font-style: normal;
            font-weight: 600;
            text-decoration: none;
            font-size: 11pt;
        }

        .s3 {
            color: black;
            font-family: "Arial", sans-serif;
            font-style: normal;
            font-weight: 600;
            text-decoration: none;
            font-size: 12pt;
            vertical-align: -1pt;
        }

        .s4 {
            color: black;
            font-family: "Arial", sans-serif;
            font-style: normal;
            font-weight: 600;
            text-decoration: none;
            font-size: 10.5pt;
        }

        .s5 {
            color: black;
            font-family: "Arial", sans-serif;
            font-style: normal;
            font-weight: 600;
            text-decoration: none;
            font-size: 10.5pt;
        }

        .s6 {
            color: black;
            font-family: "Arial", sans-serif;
            font-style: normal;
            font-weight: 600;
            text-decoration: none;
            font-size: 12pt;
        }

        .s7 {
            color: black;
            font-family: "Arial", sans-serif;
            font-style: normal;
            font-weight: 600;
            text-decoration: none;
            font-size: 10.5pt;
            vertical-align: 1pt;
        }

        table,
        tbody {
            vertical-align: top;
            overflow: visible;
        }

        table {
            border: 2pt solid #000;
            border-collapse: collapse;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 50px;
            margin-top: -20px;
        }

        .left-info {
            display: flex;
            flex-direction: column;
            margin-left: 80px;
            margin-top: -5px;
        }

        .certificate-number {
            font-size: 11pt;
            font-family: "Arial", sans-serif;
            font-weight: 600;
        }

        .date {
            font-size: 11pt;
            font-weight: 600;
            font-family: "Arial", sans-serif;
        }

        .qr-code {
            /* width: 80px;
            height: 80px; */
            /* border: 2px solid #000; */
            display: flex;
            align-items: center;
            justify-content: end;
            font-size: 8pt;
            text-align: center;
            background: #f9f9f9;
            font-family: "Arial", sans-serif;
            margin-right: 10px;
        }

        /* Print Styles */
        @media print {
            .no-print {
                display: none !important;
            }

            body {
                background: white !important;
                margin: 0 !important;
                padding: 0 !important;
            }

            .certificate-container {
                margin: 0 !important;
                padding: 0 !important;
                box-shadow: none !important;
            }

            .bg-white {
                background: white !important;
                box-shadow: none !important;
            }

            .shadow-2xl {
                box-shadow: none !important;
            }

            * {
                box-shadow: none !important;
                text-shadow: none !important;
            }

            .qr-code {
                /* margin-left: 100px !important; */
                margin-top: -8px !important;
            }
        }
    </style>
</head>

<body class="bg-gradient-to-br from-amber-50 to-orange-100 min-h-screen">
    <!-- Header - Hidden when printing -->
    <div class="bg-white shadow-lg no-print">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <div
                        class="w-12 h-12 bg-gradient-to-r from-amber-500 to-orange-600 rounded-lg flex items-center justify-center">
                        <i class="fas fa-certificate text-white text-xl"></i>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-800">Certificate Verification</h1>
                        <p class="text-gray-600">Official Certificate Verification System</p>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="text-right">
                        <p class="text-sm text-gray-500">Verified Certificate</p>
                        <p class="text-lg font-semibold text-green-600">
                            <i class="fas fa-check-circle mr-1"></i>
                            Authentic
                        </p>
                    </div>
                    <button onclick="window.print()"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg shadow-lg transition-colors duration-200 flex items-center space-x-2">
                        <i class="fas fa-print"></i>
                        <span>Print Certificate</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Certificate Container -->
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8 certificate-container">
        <!-- Certificate Content - Same structure as tiitvt-merit-external -->
        <div class="bg-white shadow-2xl rounded-lg overflow-hidden">
            <div class="p-8">
                <!-- Certificate Header -->
                <div class="header">
                    <div class="left-info">
                        <div class="certificate-number" style="font-weight: 600;">{{ $certificate->reg_no }}</div>
                        <div class="date" style="margin-top: 6px;font-weight: 600;">
                            {{ $certificate->issued_on->format('d/m/Y') ?? now()->format('d/m/Y') }}
                        </div>
                    </div>
                    @if (!empty($qrDataUri))
                        <div class="qr-code">
                            <img src="{{ $qrDataUri }}" alt="QR Code" style="width: 100px; height: 100px;">
                        </div>
                    @endif
                </div>

                <!-- Student Name -->
                <p style="text-indent: 0pt;text-align: left;margin-top: 220px"><br /></p>
                <p style="text-indent: 0pt;text-align: center;">{{ $certificate->student_name }}</p>
                <p style="padding-top: 8pt;text-indent: 0pt;text-align: center;"><br /></p>

                <!-- Course Name -->
                <p style="text-indent: 0pt;text-align: center;">{{ $certificate->course_name }}</p>

                <!-- Percentage and Grade -->
                <p style="padding-top: 4pt;padding-left: 100pt;text-indent: 0pt;text-align: left;">
                    <span>{{ $certificate->percentage ? number_format($certificate->percentage, 2) : '88.50' }}</span>
                    <span style="margin-left: 330px;">{{ $certificate->grade ?? 'A' }}</span>
                </p>

                <p style="padding-top: 5pt;text-indent: 0pt;text-align: left;"><br /></p>

                <!-- Center Name -->
                <p style="padding-left: 9pt;text-indent: 0pt;text-align: center;">{{ $certificate->center->name ?? '' }}
                </p>
                <p style="padding-top: 4pt;text-indent: 0pt;text-align: left;"><br /></p>

                <!-- Subjects Table -->
                <div style="padding: 0 50px;margin-top: 20px;">
                    <table style="border-collapse:collapse;margin-left:4pt" cellspacing="0">
                        <tr style="height:26pt">
                            <td
                                style="width:50pt;border-top-style:solid;border-top-width:2pt;border-left-style:solid;border-left-width:2pt;border-bottom-style:solid;border-bottom-width:2pt;border-right-style:solid;border-right-width:2pt">
                                <p class="s1"
                                    style="padding-left: 12pt;text-indent: 0pt;line-height: 16pt;text-align: left;">SR.
                                </p>
                                <p class="s2"
                                    style="padding-left: 12pt;text-indent: 0pt;line-height: 6pt;text-align: left;">NO.
                                </p>
                            </td>
                            <td
                                style="width:250pt;border-top-style:solid;border-top-width:2pt;border-left-style:solid;border-left-width:2pt;border-bottom-style:solid;border-bottom-width:2pt;border-right-style:solid;border-right-width:2pt">
                                <p class="s1"
                                    style="text-indent: 0pt;line-height: 16pt;text-align: center;padding-top: 8pt;">
                                    <span class="s2">SUBJECTS</span>
                                </p>
                            </td>
                            <td
                                style="width:72pt;border-top-style:solid;border-top-width:2pt;border-left-style:solid;border-left-width:2pt;border-bottom-style:solid;border-bottom-width:2pt;border-right-style:solid;border-right-width:2pt">
                                <p class="s2"
                                    style="padding-left: 15pt;text-indent: -9pt;line-height: 13pt;text-align: left;">
                                    MAXIMUM MARKS</p>
                            </td>
                            <td
                                style="width:72pt;border-top-style:solid;border-top-width:2pt;border-left-style:solid;border-left-width:2pt;border-bottom-style:solid;border-bottom-width:2pt;border-right-style:solid;border-right-width:2pt">
                                <p class="s2"
                                    style="padding-left: 7pt;padding-right: 6pt;text-indent: 8pt;line-height: 13pt;text-align: left;">
                                    MARKS OBTAINED</p>
                            </td>
                            <td
                                style="width:62pt;border-top-style:solid;border-top-width:2pt;border-left-style:solid;border-left-width:2pt;border-bottom-style:solid;border-bottom-width:2pt;border-right-style:solid;border-right-width:2pt">
                                <p class="s2"
                                    style="padding-top: 4pt;padding-left: 4pt;text-indent: 0pt;text-align: center;">
                                    RESULTS</p>
                            </td>
                        </tr>

                        @if (isset($certificate->data['subjects']) && count($certificate->data['subjects']) > 0)
                            @foreach ($certificate->data['subjects'] as $key => $subject)
                                @php
                                    $isFirst = $key === 0;
                                    $isLast = $key === count($certificate->data['subjects']) - 1;
                                    $borderStyle = '';
                                    if ($isFirst) {
                                        $borderStyle =
                                            'border-top-style:solid;border-top-width:2pt;border-left-style:solid;border-left-width:2pt;border-right-style:solid;border-right-width:2pt';
                                    } elseif ($isLast) {
                                        $borderStyle =
                                            'border-bottom-style:solid;border-bottom-width:2pt;border-left-style:solid;border-left-width:2pt;border-right-style:solid;border-right-width:2pt';
                                    } else {
                                        $borderStyle =
                                            'border-left-style:solid;border-left-width:2pt;border-right-style:solid;border-right-width:2pt';
                                    }
                                @endphp
                                <tr style="height:18pt">
                                    <td style="width:20pt;{{ $borderStyle }}">
                                        <p class="s3"
                                            style="padding-left: 14pt;text-indent: 0pt;line-height: 17pt;text-align: left;">
                                            {{ $key + 1 }}.
                                        </p>
                                    </td>
                                    <td style="width:250pt;{{ $borderStyle }}">
                                        <p class="s3"
                                            style="padding-left: 14pt;text-indent: 0pt;line-height: 17pt;text-align: left;">
                                            <span class="s4">{{ $subject['name'] ?? 'Subject' }}</span>
                                        </p>
                                    </td>
                                    <td style="width:72pt;{{ $borderStyle }}">
                                        <p class="s5"
                                            style="padding-top: 1pt;padding-left: 1pt;text-indent: 0pt;text-align: center;">
                                            {{ $subject['maximum'] ?? '100' }}
                                        </p>
                                    </td>
                                    <td style="width:72pt;{{ $borderStyle }}">
                                        <p class="s5"
                                            style="padding-top: 1pt;padding-left: 2pt;padding-right: 3pt;text-indent: 0pt;text-align: center;">
                                            {{ $subject['obtained'] ?? '80' }}
                                        </p>
                                    </td>
                                    <td style="width:62pt;{{ $borderStyle }}">
                                        <p class="s5"
                                            style="padding-top: 1pt;padding-left: 6pt;text-indent: 0pt;text-align: center;">
                                            {{ strtoupper($subject['result'] ?? 'PASS') }}
                                        </p>
                                    </td>
                                </tr>
                            @endforeach
                        @endif

                        <!-- Total Row -->
                        <tr style="height:24pt">
                            <td
                                style="width:20pt;border-top-style:solid;border-top-width:2pt;border-left-style:solid;border-left-width:2pt;border-bottom-style:solid;border-bottom-width:2pt;border-right-style:solid;border-right-width:2pt">
                            </td>
                            <td
                                style="width:272pt;border-top-style:solid;border-top-width:2pt;border-left-style:solid;border-left-width:2pt;border-bottom-style:solid;border-bottom-width:2pt;border-right-style:solid;border-right-width:2pt">
                                <p class="s2" style="padding-top: 5pt;text-indent: 0pt;text-align: center;">TOTAL
                                    MARKS</p>
                            </td>
                            <td
                                style="width:72pt;border-top-style:solid;border-top-width:2pt;border-left-style:solid;border-left-width:2pt;border-bottom-style:solid;border-bottom-width:2pt;border-right-style:solid;border-right-width:2pt">
                                <p class="s2"
                                    style="padding-top: 5pt;padding-left: 1pt;text-indent: 0pt;text-align: center;">
                                    {{ $certificate->data['total_marks'] ?? '700' }}
                                </p>
                            </td>
                            <td
                                style="width:72pt;border-top-style:solid;border-top-width:2pt;border-left-style:solid;border-left-width:2pt;border-bottom-style:solid;border-bottom-width:2pt;border-right-style:solid;border-right-width:2pt">
                                <p class="s2"
                                    style="padding-top: 5pt;padding-left: 4pt;padding-right: 3pt;text-indent: 0pt;text-align: center;">
                                    {{ $certificate->data['total_marks_obtained'] ?? '566' }}
                                </p>
                            </td>
                            <td
                                style="width:62pt;border-top-style:solid;border-top-width:2pt;border-left-style:solid;border-left-width:2pt;border-bottom-style:solid;border-bottom-width:2pt;border-right-style:solid;border-right-width:2pt">
                                <p class="s2"
                                    style="padding-top: 5pt;padding-left: 4pt;text-indent: 0pt;text-align: center;">
                                    {{ $certificate->data['total_result'] ?? 'PASS' }}
                                </p>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <!-- Footer - Hidden when printing -->
        <div class="text-center mt-8 text-gray-600 no-print">
            <p class="text-sm">
                <i class="fas fa-shield-alt mr-1"></i>
                This certificate is digitally verified and authentic
            </p>
            <p class="text-xs mt-2">
                Generated on {{ now()->format('F d, Y \a\t g:i A') }}
            </p>
        </div>
    </div>
</body>

</html>
