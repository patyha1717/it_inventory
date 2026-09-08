<?php
/* Simple XLSX reader - lightweight */
class SimpleXLSX {
    public static function parse($filename) {
        return new SimpleXLSX($filename);
    }

    private $rows;

    public function __construct($filename) {
        $zip = new ZipArchive;
        if ($zip->open($filename) === true) {
            $xml = $zip->getFromName('xl/worksheets/sheet1.xml');
            $zip->close();
            $this->parseSheet($xml);
        }
    }

    private function parseSheet($xml) {
        $this->rows = [];
        $sx = simplexml_load_string($xml);
        foreach ($sx->sheetData->row as $row) {
            $r = [];
            foreach ($row->c as $c) {
                $value = (string)$c->v;
                $r[] = $value;
            }
            $this->rows[] = $r;
        }
    }

    public function rows() {
        return $this->rows;
    }
}
