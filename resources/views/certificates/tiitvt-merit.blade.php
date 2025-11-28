<!DOCTYPE html
    PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>{{ $certificate->tiitvt_reg_no ?? '-' }} - {{ $student->full_name ?? '-' }}</title>
    <meta name="author" content="{{ $websiteSettings->getWebsiteName() }}" />
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
        }

        .left-info {
            display: flex;
            flex-direction: column;
            margin-left: 120px;
            margin-top: 20px;
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
            width: 80px;
            height: 80px;
            border: 2px solid #000;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 8pt;
            text-align: center;
            background: #f9f9f9;
            font-family: "Arial", sans-serif;
            margin-right: 100px;
        }
    </style>
</head>

<body>
    <div class="header">
        <div class="left-info">
            <div class="certificate-number" style="font-weight: 600;">{{ $certificate->tiitvt_reg_no ?? '-' }}</div>
            <div class="date" style="margin-top: 10px;font-weight: 600;">
                {{ $certificate->issued_on->format('d/m/Y') ?? now()->format('d/m/Y') }}</div>
        </div>
        @if (!empty($qrDataUri))
            <div class="qr-code">
                <img src="{{ $qrDataUri }}" alt="QR Code" style="width: 80px; height: 80px;">
            </div>
        @elseif (!empty($qrUrl))
            <div class="qr-code">
                <img src="{{ $qrUrl }}" alt="QR Code" style="width: 80px; height: 80px;">
            </div>
        @endif
    </div>

    <p style="text-indent: 0pt;text-align: left;margin-top: 250px"><br /></p>
    <p style="text-indent: 0pt;text-align: center;">{{ $student->full_name ?? '-' }}</p>
    <p style="padding-top: 8pt;text-indent: 0pt;text-align: center;"><br /></p>
    <p style="text-indent: 0pt;text-align: center;">
        {{ $student->course->name ?? '-' }}</p>
    <p style="padding-top: 6pt;padding-left: 150pt;text-indent: 0pt;text-align: left;">
        <span>{{ $student->percentage ?? '-' }}</span>

        <span style="margin-left: 320px;">{{ $student->grade ?? '-' }}</span>
    </p>

    <p style="padding-top: 7pt;text-indent: 0pt;text-align: left;"><br /></p>
    <p style="padding-left: 9pt;text-indent: 0pt;text-align: center;">
        {{ $certificate->center_name ?? '-' }}</p>
    <p style="padding-top: 4pt;text-indent: 0pt;text-align: left;"><br /></p>
    <div style="padding: 0 50px;margin-top: 20px;">
        <table style="border-collapse:collapse;margin-left:4pt" cellspacing="0">
            <tr style="height:26pt">
                <td
                    style="width:50pt;border-top-style:solid;border-top-width:2pt;border-left-style:solid;border-left-width:2pt;border-bottom-style:solid;border-bottom-width:2pt;border-right-style:solid;border-right-width:2pt">
                    <p class="s1" style="padding-left: 12pt;text-indent: 0pt;line-height: 16pt;text-align: left;">
                        SR.
                    </p>
                    <p class="s2" style="padding-left: 12pt;text-indent: 0pt;line-height: 6pt;text-align: left;">
                        NO.
                    </p>
                </td>
                <td
                    style="width:250pt;border-top-style:solid;border-top-width:2pt;border-left-style:solid;border-left-width:2pt;border-bottom-style:solid;border-bottom-width:2pt;border-right-style:solid;border-right-width:2pt">
                    <p class="s1" style="text-indent: 0pt;line-height: 16pt;text-align: center;padding-top: 8pt;">
                        <span class="s2">SUBJECTS</span>
                    </p>
                </td>
                <td
                    style="width:72pt;border-top-style:solid;border-top-width:2pt;border-left-style:solid;border-left-width:2pt;border-bottom-style:solid;border-bottom-width:2pt;border-right-style:solid;border-right-width:2pt">
                    <p class="s2" style="padding-left: 15pt;text-indent: -9pt;line-height: 13pt;text-align: left;">
                        MAXIMUM
                        MARKS</p>
                </td>
                <td
                    style="width:72pt;border-top-style:solid;border-top-width:2pt;border-left-style:solid;border-left-width:2pt;border-bottom-style:solid;border-bottom-width:2pt;border-right-style:solid;border-right-width:2pt">
                    <p class="s2"
                        style="padding-left: 7pt;padding-right: 6pt;text-indent: 8pt;line-height: 13pt;text-align: left;">
                        MARKS OBTAINED</p>
                </td>
                <td
                    style="width:62pt;border-top-style:solid;border-top-width:2pt;border-left-style:solid;border-left-width:2pt;border-bottom-style:solid;border-bottom-width:2pt;border-right-style:solid;border-right-width:2pt">
                    <p class="s2" style="padding-top: 4pt;padding-left: 4pt;text-indent: 0pt;text-align: center;">
                        RESULTS
                    </p>
                </td>
            </tr>

            @php
                // Prefer external-provided subjects if present in $student->examResult->data
                $externalSubjects = $student->examResult->data['subjects'] ?? null;
                $usingExternal = is_array($externalSubjects) && count($externalSubjects) > 0;
                $categories = $usingExternal ? collect($externalSubjects) : $certificate->course->categories()->get();
            @endphp
            @foreach ($categories as $key => $category)
                @php
                    $isFirst = $key === 0;
                    $isLast = $key === count($categories) - 1;
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
                            <span
                                class="s4">{{ $usingExternal ? $category['name'] ?? 'Subject' : $category->name }}</span>
                        </p>
                    </td>
                    <td style="width:72pt;{{ $borderStyle }}">
                        <p class="s5"
                            style="padding-top: 1pt;padding-left: 1pt;text-indent: 0pt;text-align: center;">
                            {{ $usingExternal ? $category['maximum'] ?? '100' : $student->examResult->data['category_results'][$category->id]['total_marks'] ?? '100' }}
                        </p>
                    </td>
                    <td style="width:72pt;{{ $borderStyle }}">
                        <p class="s5"
                            style="padding-top: 1pt;padding-left: 2pt;padding-right: 3pt;text-indent: 0pt;text-align: center;">
                            {{ $usingExternal ? $category['obtained'] ?? '80' : $student->examResult->data['category_results'][$category->id]['marks'] ?? '80' }}
                        </p>
                    </td>
                    <td style="width:62pt;{{ $borderStyle }}">
                        <p class="s5"
                            style="padding-top: 1pt;padding-left: 6pt;text-indent: 0pt;text-align: center;">
                            {{ $usingExternal ? strtoupper($category['result'] ?? 'PASS') : $student->examResult->data['category_results'][$category->id]['result'] ?? 'PASS' }}
                        </p>
                    </td>
                </tr>
            @endforeach
            <tr style="height:24pt">
                <td
                    style="width:20pt;border-top-style:solid;border-top-width:2pt;border-left-style:solid;border-left-width:2pt;border-bottom-style:solid;border-bottom-width:2pt;border-right-style:solid;border-right-width:2pt">

                </td>
                <td
                    style="width:272pt;border-top-style:solid;border-top-width:2pt;border-left-style:solid;border-left-width:2pt;border-bottom-style:solid;border-bottom-width:2pt;border-right-style:solid;border-right-width:2pt">
                    <p class="s2" style="padding-top: 5pt;text-indent: 0pt;text-align: center;">
                        TOTAL MARKS
                    </p>
                </td>
                <td
                    style="width:72pt;border-top-style:solid;border-top-width:2pt;border-left-style:solid;border-left-width:2pt;border-bottom-style:solid;border-bottom-width:2pt;border-right-style:solid;border-right-width:2pt">
                    <p class="s2" style="padding-top: 5pt;padding-left: 1pt;text-indent: 0pt;text-align: center;">
                        {{ $student->examResult->data['total_marks'] ?? ($usingExternal ? array_sum(array_map(fn($s) => (float) ($s['maximum'] ?? 0), $externalSubjects)) : '700') }}
                    </p>
                </td>
                <td
                    style="width:72pt;border-top-style:solid;border-top-width:2pt;border-left-style:solid;border-left-width:2pt;border-bottom-style:solid;border-bottom-width:2pt;border-right-style:solid;border-right-width:2pt">
                    <p class="s2"
                        style="padding-top: 5pt;padding-left: 4pt;padding-right: 3pt;text-indent: 0pt;text-align: center;">
                        {{ $student->examResult->data['total_marks_obtained'] ?? ($usingExternal ? array_sum(array_map(fn($s) => (float) ($s['obtained'] ?? 0), $externalSubjects)) : '566') }}
                    </p>
                </td>
                <td
                    style="width:62pt;border-top-style:solid;border-top-width:2pt;border-left-style:solid;border-left-width:2pt;border-bottom-style:solid;border-bottom-width:2pt;border-right-style:solid;border-right-width:2pt">
                    <p class="s2" style="padding-top: 5pt;padding-left: 4pt;text-indent: 0pt;text-align: center;">
                        {{ $student->examResult->data['total_result'] ?? ($usingExternal ? (collect($externalSubjects)->contains(fn($s) => strtoupper($s['result'] ?? 'PASS') !== 'PASS') ? 'FAIL' : 'PASS') : 'PASS') }}
                    </p>
                </td>
            </tr>
        </table>
    </div>
</body>

</html>
