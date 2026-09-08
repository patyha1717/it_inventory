<?php
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config/env_loader.php';

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;

function generateHandoverDOCX($employee, $assets, $condition, $remarks)
{
    $company = env('COMPANY_NAME');
    $logo = env('COMPANY_LOGO');

    $phpWord = new PhpWord();
    $section = $phpWord->addSection();

    // Logo
    if (file_exists($_SERVER['DOCUMENT_ROOT'] . $logo)) {
        $section->addImage($_SERVER['DOCUMENT_ROOT'] . $logo, ['width' => 180]);
    }

    $section->addText("ASSET HANDOVER FORM", ['bold' => true, 'size' => 18]);
    $section->addTextBreak(1);

    $section->addText("Company Name: {$company}");
    $section->addText("Date: {$employee['handover_date']}");
    $section->addTextBreak(1);

    $section->addText("Employee Details", ['bold' => true, 'size' => 14]);

    foreach ($employee as $k => $v) {
        $section->addText(ucwords(str_replace("_", " ", $k)) . ": " . $v);
    }

    $section->addTextBreak(1);
    $section->addText("Assets Description", ['bold' => true, 'size' => 14]);

    $table = $section->addTable();

    $table->addRow();
    $table->addCell()->addText("R#");
    $table->addCell()->addText("Particulars");
    $table->addCell()->addText("Asset Code");
    $table->addCell()->addText("Serial No");
    $table->addCell()->addText("Remarks");

    $count = 1;
    foreach ($assets as $a) {
        $table->addRow();
        $table->addCell()->addText($count++);
        $table->addCell()->addText($a['item_details']);
        $table->addCell()->addText($a['asset_code']);
        $table->addCell()->addText($a['serial_no']);
        $table->addCell()->addText($a['remarks']);
    }

    $section->addTextBreak(1);
    $section->addText("Condition of Asset at Handover", ['bold' => true, 'size' => 14]);
    $section->addText("Condition: {$condition}");
    $section->addText("Remarks: {$remarks}");

    $filename = __DIR__ . "/handover_" . time() . ".docx";
    $writer = IOFactory::createWriter($phpWord, 'Word2007');
    $writer->save($filename);

    return $filename;
}
