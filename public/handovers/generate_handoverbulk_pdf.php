<?php
// DEBUG ENABLED
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config/env_loader.php';

use Mpdf\Mpdf;

function generateHandoverbulkPDF($employee, $assets, $condition, $remarks)
{
    /* TEMP DIR */
    $tempDir = __DIR__ . "/tmp";
    if (!file_exists($tempDir)) {
        mkdir($tempDir, 0777, true);
    }

    /* MPDF CONFIG */
    $mpdf = new Mpdf([
        'mode' => 'utf-8',
        'format' => 'A4',
        'margin_top' => 58,     // Increased to prevent overlapping on Page 2+
        'margin_bottom' => 55,  // Space for footer declaration
        'margin_left' => 10,
        'margin_right' => 10,
        'tempDir' => $tempDir,
    ]);

    // Apply the border to the page body globally
    $mpdf->SetDefaultBodyCSS('border', '1px solid #000');
    $mpdf->SetDefaultBodyCSS('padding', '2px');

    $mpdf->WriteHTML('
    <style>
    @page {
        border: 2px solid #000;
    }
    table {
        border-collapse: collapse;
        width: 100%;
    }
    thead { display: table-header-group; }
    tfoot { display: table-footer-group; }
    tr { page-break-inside: avoid; }
    td, th {
        word-break: break-word;
        font-size: 11px;
    }
    .main-container {
        padding: 15px;
        font-family: sans-serif;
    }
    </style>
    ', \Mpdf\HTMLParserMode::HEADER_CSS);

    $mpdf->SetWatermarkText("Arukus Technologies", 0.05);
    $mpdf->showWatermarkText = true;

    /* LOGO */
    $logoPath = $_SERVER['DOCUMENT_ROOT'] . "/itms.arukustech.com/public/assets/logo/mail_logo.png";
    $logoHTML = file_exists($logoPath)
        ? "<img src='data:image/png;base64," . base64_encode(file_get_contents($logoPath)) . "' style='width:230px;'>"
        : "<h2>Arukus Technologies</h2>";

    /* =========================
       FIXED HEADER (ALL PAGES)
       ========================= */
    $headerHtml = "
    <div style='font-family:sans-serif; margin:0px 10px; height: 180px;'>
        <div style='text-align:center;'>
            $logoHTML
        </div>
        <h2 style='text-align:center; margin:5px 0 0;'>Arukus Technologies</h2>
        <h3 style='text-align:center; margin:0 0 8px;'>ASSET HAND OVER FORM</h3>
        <table width='100%' style='font-size:12px;'>
            <tr>
                <td>
                    <b>Registered Office Address:</b> Arukus Technologies<br>
                    Merlin Infinite, Ofiice #901, DN-51, Salt Lake Sector V,<br> West Bengal, India Kol-700091<br>
                    <b>Website:</b> https://arukustech.com/
                </td>
                <td style='text-align:right; vertical-align:top;'>
                    <b>Date:</b> {$employee['handover_date']}
                </td>
            </tr>
        </table>
        <hr style='margin:5px 0;'>
    </div>";

    $mpdf->SetHTMLHeader($headerHtml);

    /* =========================
       FIXED FOOTER (ALL PAGES)
       ========================= */
    $footerHtml = "
    <div style='padding-top:8px; margin:0px 10px; font-family:sans-serif; font-size:11px;'>
        <h3 style='font-size:13px; margin:0 0 5px;'>
            ACKNOWLEDGEMENT AND DECLARATION BY EMPLOYEE<br>
        </h3>
        <p style='text-align:justify; line-height:1.4; margin:0;'>
            I, Ms./Mr. <b>{$employee['name']}</b> hereby acknowledge that I have received the assets.
            I understand that these assets belong to Arukus Technologies and are under my
            possession for carrying out my official duties. I assure that I will take care of the
            company assets and return them in proper condition when required.
        </p>
        <br>
        <table width='100%' style='font-size:12px;'>
            <tr>
                <td width='60%'><b>Employee Signature:</b> ________________________</td>
                <td width='40%' style='text-align:right;'><b>Date:</b> ________________________</td>
                <br>
            </tr>
        </table>
        <div style='text-align:center; font-size:9px; color:#555; margin-top:6px;'>
            This is a system generated document and does not require physical signature.<br>
            Page {PAGENO} of {nbpg}
        </div>
    </div>";

    $mpdf->SetHTMLFooter($footerHtml);

    /* =========================
       MAIN BODY CONTENT
       ========================= */
    $html = "
    <div class='main-container'>
        <table width='100%' border='1' cellpadding='6' cellspacing='0' style='font-size:12px; border-collapse:collapse;'>
            <tr>
                <td width='50%'><b>Name Of Employee:</b> {$employee['name']}</td>
                <td width='50%'><b>Location:</b> {$employee['location']}</td>
            </tr>
            <tr>
                <td><b>Employee Code:</b> {$employee['code']}</td>
                <td><b>Handover Date:</b> {$employee['handover_date']}</td>
            </tr>
            <tr>
                <td><b>Department:</b> {$employee['department']}</td>
                <td><b>Handover By:</b> {$employee['handover_by']}</td>
            </tr>
        </table>

        <br>
        <b>Assets Description:</b><br><br>

        <table width='100%' border='1' cellpadding='6' cellspacing='0' style='border-collapse:collapse; font-size:11px; text-align:center;'>
            <thead>
                <tr style='background:#f2f2f2;'>
                    <th width='6%'>SR#</th>
                    <th>Particulars</th>
                    <th width='6%'>Quantity</th>
                    <th>Asset Code</th>
                    <th>Serial No</th>
                    <th>Remarks</th>

                </tr>
            </thead>
            <tbody>";

    $sr = 1;
    foreach ($assets as $a) {
        $html .= "
            <tr>
                <td>$sr</td>
                <td>{$a['item_details']}</td>
                <td>1</td>
                <td>{$a['asset_code']}</td>
                <td>{$a['serial_no']}</td>
                <td>{$a['remarks']}</td>
            </tr>";
        $sr++;
    }

    $html .= "
            </tbody>
        </table>
    </div>";

    $mpdf->WriteHTML($html);

    /* SAVE TO SEPARATE FOLDER */
    $folder = __DIR__ . "/generated";
    if (!file_exists($folder)) {
        mkdir($folder, 0777, true);
    }

    $file = $folder . "/handover_" . time() . ".pdf";
    $mpdf->Output($file, 'F');

    return $file;
}