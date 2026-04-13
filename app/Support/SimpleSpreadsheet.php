<?php

namespace App\Support;

use Illuminate\Validation\ValidationException;
use SimpleXMLElement;
use ZipArchive;

class SimpleSpreadsheet
{
    public static function parseUploadedFile(string $path, string $extension): array
    {
        return strtolower($extension) === 'xlsx'
            ? self::parseXlsxFile($path)
            : self::parseCsvFile($path);
    }

    public static function buildTemplate(array $rows, string $format): string
    {
        if ($format === 'csv') {
            $stream = fopen('php://temp', 'r+');
            foreach ($rows as $row) {
                fputcsv($stream, $row);
            }
            rewind($stream);
            $contents = stream_get_contents($stream) ?: '';
            fclose($stream);

            return $contents;
        }

        $sheetXml = self::buildWorksheetXml($rows);
        $tempPath = tempnam(sys_get_temp_dir(), 'sheet_tpl_');
        $zip = new ZipArchive();
        $zip->open($tempPath, ZipArchive::OVERWRITE);
        $zip->addFromString('[Content_Types].xml', self::contentTypesXml());
        $zip->addFromString('_rels/.rels', self::rootRelsXml());
        $zip->addFromString('xl/workbook.xml', self::workbookXml());
        $zip->addFromString('xl/_rels/workbook.xml.rels', self::workbookRelsXml());
        $zip->addFromString('xl/styles.xml', self::stylesXml());
        $zip->addFromString('xl/worksheets/sheet1.xml', $sheetXml);
        $zip->close();

        $binary = file_get_contents($tempPath) ?: '';
        @unlink($tempPath);

        return $binary;
    }

    private static function parseCsvFile(string $path): array
    {
        $rows = [];
        $handle = fopen($path, 'rb');
        if ($handle === false) {
            throw ValidationException::withMessages(['import_file' => 'File CSV tidak bisa dibaca.']);
        }

        while (($row = fgetcsv($handle)) !== false) {
            $rows[] = array_map(fn ($value) => is_string($value) ? trim($value) : $value, $row);
        }

        fclose($handle);

        return $rows;
    }

    private static function parseXlsxFile(string $path): array
    {
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            throw ValidationException::withMessages(['import_file' => 'File XLSX tidak bisa dibuka.']);
        }

        $sharedStrings = self::readSharedStrings($zip);
        $sheetPath = self::resolveFirstWorksheetPath($zip);
        $sheetXml = $zip->getFromName($sheetPath);
        $zip->close();

        if ($sheetXml === false) {
            throw ValidationException::withMessages(['import_file' => 'Worksheet XLSX tidak ditemukan.']);
        }

        $xml = simplexml_load_string($sheetXml);
        if (!$xml instanceof SimpleXMLElement) {
            throw ValidationException::withMessages(['import_file' => 'Worksheet XLSX tidak valid.']);
        }

        $xml->registerXPathNamespace('main', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $rows = [];

        foreach ($xml->xpath('//main:sheetData/main:row') ?: [] as $rowNode) {
            $row = [];
            foreach ($rowNode->c as $cell) {
                $ref = (string) $cell['r'];
                $columnLetters = preg_replace('/\d+/', '', $ref);
                $columnIndex = self::columnLettersToIndex($columnLetters);
                $row[$columnIndex] = self::extractCellValue($cell, $sharedStrings);
            }

            if (!empty($row)) {
                ksort($row);
                $maxIndex = max(array_keys($row));
                $normalized = [];
                for ($i = 0; $i <= $maxIndex; $i++) {
                    $normalized[] = $row[$i] ?? '';
                }
                $rows[] = $normalized;
            }
        }

        return $rows;
    }

    private static function readSharedStrings(ZipArchive $zip): array
    {
        $xml = $zip->getFromName('xl/sharedStrings.xml');
        if ($xml === false) {
            return [];
        }

        $shared = simplexml_load_string($xml);
        if (!$shared instanceof SimpleXMLElement) {
            return [];
        }

        $shared->registerXPathNamespace('main', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');

        return collect($shared->xpath('//main:si') ?: [])
            ->map(function (SimpleXMLElement $node): string {
                $texts = $node->xpath('.//main:t') ?: [];

                return trim(collect($texts)->map(fn ($text) => (string) $text)->implode(''));
            })
            ->all();
    }

    private static function resolveFirstWorksheetPath(ZipArchive $zip): string
    {
        $relsXml = $zip->getFromName('xl/_rels/workbook.xml.rels');
        if ($relsXml !== false) {
            $rels = simplexml_load_string($relsXml);
            if ($rels instanceof SimpleXMLElement) {
                foreach ($rels->Relationship as $relationship) {
                    $type = (string) $relationship['Type'];
                    if (str_contains($type, '/worksheet')) {
                        return 'xl/' . ltrim((string) $relationship['Target'], '/');
                    }
                }
            }
        }

        return 'xl/worksheets/sheet1.xml';
    }

    private static function extractCellValue(SimpleXMLElement $cell, array $sharedStrings): string
    {
        $type = (string) $cell['t'];
        $value = isset($cell->v) ? (string) $cell->v : '';

        if ($type === 's') {
            return $sharedStrings[(int) $value] ?? '';
        }

        if ($type === 'inlineStr') {
            return trim((string) ($cell->is->t ?? ''));
        }

        return trim($value);
    }

    private static function columnLettersToIndex(string $letters): int
    {
        $letters = strtoupper($letters);
        $index = 0;
        $length = strlen($letters);

        for ($i = 0; $i < $length; $i++) {
            $index = ($index * 26) + (ord($letters[$i]) - 64);
        }

        return max($index - 1, 0);
    }

    private static function buildWorksheetXml(array $rows): string
    {
        $xmlRows = [];
        foreach ($rows as $rowIndex => $row) {
            $cells = [];
            foreach (array_values($row) as $colIndex => $value) {
                $cellRef = self::columnIndexToLetters($colIndex) . ($rowIndex + 1);
                $safeValue = htmlspecialchars((string) $value, ENT_XML1);
                $cells[] = "<c r=\"{$cellRef}\" t=\"inlineStr\"><is><t>{$safeValue}</t></is></c>";
            }
            $xmlRows[] = '<row r="' . ($rowIndex + 1) . '">' . implode('', $cells) . '</row>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<sheetData>' . implode('', $xmlRows) . '</sheetData>'
            . '</worksheet>';
    }

    private static function columnIndexToLetters(int $index): string
    {
        $index++;
        $letters = '';
        while ($index > 0) {
            $mod = ($index - 1) % 26;
            $letters = chr(65 + $mod) . $letters;
            $index = intdiv($index - 1, 26);
        }

        return $letters;
    }

    private static function contentTypesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . '</Types>';
    }

    private static function rootRelsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>';
    }

    private static function workbookXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets><sheet name="Sheet1" sheetId="1" r:id="rId1"/></sheets>'
            . '</workbook>';
    }

    private static function workbookRelsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            . '</Relationships>';
    }

    private static function stylesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<fonts count="1"><font><sz val="11"/><name val="Calibri"/></font></fonts>'
            . '<fills count="2"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill></fills>'
            . '<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/></cellXfs>'
            . '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
            . '</styleSheet>';
    }
}
