<?php

namespace App\Support\Roster;

use DOMDocument;
use RuntimeException;
use ZipArchive;

/**
 * Membaca file sumber Dapodik/Excel secara langsung tanpa dependensi PhpSpreadsheet,
 * karena PhpSpreadsheet tidak terpasang di project ini.
 *
 * Dua format yang didukung:
 *  - .xls  : SpreadsheetML (Workbook XML, tag <Worksheet>/<Row>/<Cell>)
 *  - .xlsx : OpenXML (zip berisi sharedStrings.xml + worksheets/sheet1.xml)
 */
class SourceFileReader
{
    /**
     * Baca file .xls SpreadsheetML menjadi [namaSheet => rows[]] dengan kolom 1-based.
     *
     * @return array<string, array<int, array<int, string|null>>>
     */
    public static function xls(string $path): array
    {
        $doc = new DOMDocument();
        if (! $doc->load($path)) {
            throw new RuntimeException("Gagal membaca file .xls: {$path}");
        }

        $sheets = [];

        foreach ($doc->getElementsByTagName('Worksheet') as $worksheet) {
            $name = $worksheet->getAttribute('ss:Name');
            $rows = [];

            foreach ($worksheet->getElementsByTagName('Row') as $row) {
                $cells = [];
                $cursor = 1;

                foreach ($row->getElementsByTagName('Cell') as $cell) {
                    // Sel bisa sparse, posisi sebenarnya disimpan di ss:Index.
                    $index = $cell->getAttribute('ss:Index');
                    if ($index !== '') {
                        $cursor = (int) $index;
                    }

                    $value = null;
                    foreach ($cell->childNodes as $child) {
                        if ($child->nodeName === 'Data') {
                            $value = trim($child->nodeValue);
                            break;
                        }
                    }

                    $cells[$cursor] = $value;
                    $cursor++;
                }

                $rows[] = $cells;
            }

            $sheets[$name] = $rows;
        }

        return $sheets;
    }

    /**
     * Baca sheet pertama file .xlsx menjadi [kolomA => nilai] per baris.
     *
     * @return array<int, array<string, string>>
     */
    public static function xlsx(string $path): array
    {
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            throw new RuntimeException("Gagal membuka file .xlsx: {$path}");
        }

        try {
            $shared = self::sharedStrings($zip);
            $rows = self::firstSheetRows($zip, $shared);
        } finally {
            $zip->close();
        }

        return $rows;
    }

    /**
     * @return array<int, string>
     */
    private static function sharedStrings(ZipArchive $zip): array
    {
        $index = $zip->locateName('xl/sharedStrings.xml');
        if ($index === false) {
            return [];
        }

        $doc = new DOMDocument();
        $doc->loadXML($zip->getFromName('xl/sharedStrings.xml'));

        $strings = [];
        foreach ($doc->getElementsByTagName('si') as $item) {
            $text = '';
            foreach ($item->getElementsByTagName('t') as $part) {
                $text .= $part->nodeValue;
            }
            $strings[] = trim($text);
        }

        return $strings;
    }

    /**
     * @param  array<int, string>  $shared
     * @return array<int, array<string, string>>
     */
    private static function firstSheetRows(ZipArchive $zip, array $shared): array
    {
        $name = $zip->locateName('xl/worksheets/sheet1.xml');
        if ($name === false) {
            throw new RuntimeException('File .xlsx tidak memiliki sheet1.xml');
        }

        $doc = new DOMDocument();
        $doc->loadXML($zip->getFromName('xl/worksheets/sheet1.xml'));

        $rows = [];

        foreach ($doc->getElementsByTagName('row') as $row) {
            $cells = [];

            foreach ($row->getElementsByTagName('c') as $cell) {
                $column = preg_replace('/\d+/', '', $cell->getAttribute('r'));
                $type = $cell->getAttribute('t');

                $raw = null;
                foreach ($cell->getElementsByTagName('v') as $value) {
                    $raw = $value->nodeValue;
                    break;
                }

                if ($type === 's') {
                    $text = $shared[(int) $raw] ?? '';
                } elseif ($type === 'inlineStr') {
                    $text = '';
                    foreach ($cell->getElementsByTagName('t') as $value) {
                        $text .= $value->nodeValue;
                    }
                } else {
                    $text = (string) $raw;
                }

                $cells[$column] = trim($text);
            }

            if ($cells !== []) {
                $rows[] = $cells;
            }
        }

        return $rows;
    }
}