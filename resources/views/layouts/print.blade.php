<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Print Document')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @page {
            size: A4;
            margin: 10mm 15mm;
        }

        @media print {
            body {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            .no-print {
                display: none !important;
            }
            .page-break {
                page-break-before: always;
            }
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #000;
            background: #fff;
        }

        .print-container {
            max-width: 210mm;
            margin: 0 auto;
            padding: 10px;
        }

        /* Table styling for print */
        .print-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
        }

        .print-table th,
        .print-table td {
            border: 1px solid #000;
            padding: 6px 8px;
            vertical-align: middle;
        }

        .print-table th {
            background-color: #f0f0f0 !important;
            font-weight: bold;
            text-transform: uppercase;
        }

        .print-table .text-end {
            text-align: right;
        }

        .print-table .text-center {
            text-align: center;
        }

        /* Summary table */
        .summary-table {
            border-collapse: collapse;
        }

        .summary-table td {
            padding: 4px 8px;
            border: 1px solid #000;
        }

        /* Footer sections */
        .signature-box {
            border-top: 1px solid #000;
            padding-top: 5px;
            margin-top: 40px;
            text-align: center;
            min-width: 150px;
        }

        /* Invoice box */
        .invoice-box {
            border: 2px solid #000;
            padding: 10px;
        }

        .invoice-box-title {
            font-size: 20px;
            font-weight: bold;
            text-align: center;
            margin-bottom: 10px;
        }

        /* Print button */
        .print-button {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
        }

        @media print {
            .print-button {
                display: none;
            }
        }
    </style>
    @stack('styles')
</head>
<body>
    <button class="btn btn-primary print-button no-print" onclick="window.print()">
        <i class="ti ti-printer"></i> Print
    </button>

    <div class="print-container">
        @yield('content')
    </div>

    @stack('scripts')
</body>
</html>
