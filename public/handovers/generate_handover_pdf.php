<?php
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config/env_loader.php';

use Mpdf\Mpdf;

function generateHandoverPDF($employee, $assets, $condition, $remarks)
{
    /* TEMP DIR */
    $tempDir = __DIR__ . "/tmp";
    if (!file_exists($tempDir)) mkdir($tempDir, 0777, true);

    /* MPDF CONFIG */
    $mpdf = new Mpdf([
        'mode' => 'utf-8',
        'format' => 'A4',
        'tempDir' => $tempDir,
        'margin_top' => 16,
        'margin_left' => 16,
        'margin_right' => 16,
        'margin_bottom' => 30   // space for footer + signature
    ]);

    /* WATERMARK */
    $mpdf->SetWatermarkText("Arukus Technologies", 0.05);
    $mpdf->showWatermarkText = true;

    /* LOGO */
    $logoPath = $_SERVER['DOCUMENT_ROOT'] . "/itms.arukustech.com/public/assets/logo/mail_logo.png";
    if (file_exists($logoPath)) {
        $logo64 = base64_encode(file_get_contents($logoPath));
        $logoHTML = "<img src='data:image/png;base64,$logo64' style='width:260px;'>";
    } else {
        $logoHTML = "<h1>Arukus Technologies</h1>";
    }

    /* BORDER START */
    $html = "
    <div style='border:1px solid #000; padding:20px; height:97%;'>

        <div style='text-align:center; margin-top:-10px;'>
            $logoHTML
        </div>

        <h2 style='text-align:center; margin:4px 0 0 0;'>Arukus Technologies</h2>
        <h3 style='text-align:center; margin:0 0 10px 0;'>ASSET HAND OVER FORM</h3>

        <table width='100%' style='font-size:12px; line-height:16px;'>
            <tr>
                <td><b>Registered Office Address:</b> Arukus Technologies</td>
                <td style='text-align:right;'><b>Date:</b> {$employee['handover_date']}</td>
            </tr>
            <tr>
                <td colspan='2'>
                    Merlin Infinite, Ofiice #901, DN-51, Salt Lake Sector V,<br>
                    West Bengal, India Kol-700091<br>
                    <b>Website:</b> https://arukustech.com//
                </td>
            </tr>
        </table>

        <hr style='margin:8px 0;'>
    ";

    /* EMPLOYEE DETAILS */
    $html .= "
        <table width='100%' border='1' cellpadding='6' cellspacing='0' style='font-size:12px; border-collapse:collapse;'>
            <tr>
                <td><b>Name Of Employee:</b> {$employee['name']}</td>
                <td><b>Location:</b> {$employee['location']}</td>
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

        <table width='100%' border='1' cellpadding='6' cellspacing='0'
            style='border-collapse:collapse; font-size:12px; text-align:center;'>
            <tr>
                <th>SR#</th>
                <th>Particulars</th>
                <th>Quantity</th>
                <th>Asset Code</th>
                <th>Serial No</th>
                <th>Remarks</th>
            </tr>";

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

    $html .= "</table>";

    /* DECLARATION (NOT AT THIS POSITION - We move to bottom using CSS absolute) */
    $declaration = "
        <h3 style='font-size:14px;'>ACKNOWLEDGEMENT AND DECLARATION BY EMPLOYEE</h3>

        <p style='font-size:12px; text-align:justify; line-height:18px;'>
            I, Ms./Mr. <b>{$employee['name']}</b> hereby acknowledge that I have received the assets.
            I understand that these assets belong to Arukus Technologies and are under my possession
            for carrying out my official duties. I assure that I will take care of the company assets to the
            best possible extent and return them in proper condition when required.
        </p>
    ";

    /* SPACE SO CONTENT DOESN'T OVERLAP BOTTOM */
    $html .= "<div style='height:240px;'></div>";

    /* SIGNATURE + DECLARATION FIXED AT BOTTOM */
    $html .= "
        <div style='position:absolute; bottom:70px; left:30px; right:30px;'>
            $declaration

            <br><br>
             <br>
	    <br>
            <table width='100%' style='font-size:13px;'>
                <tr>
                    <td><b>Employee Signature:</b> ____________________________</td>
                    <td><b>Date:</b> ____________________________</td>
                </tr>
            </table>
        </div>

    </div> <!-- border end -->
    ";

    /* FOOTER */
    $mpdf->SetHTMLFooter("
        <div style='text-align:center; font-size:10px; color:#777;'>
            This is a system generated document and does not require physical signature.
        </div>
    ");

    $mpdf->WriteHTML($html);

    /* SAVE */
    $folder = __DIR__ . "/generated";
    if (!file_exists($folder)) mkdir($folder, 0777, true);

    $file = $folder . "/handover_" . time() . ".pdf";
    $mpdf->Output($file, 'F');

    return $file;
}
