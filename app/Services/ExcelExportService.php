<?php

namespace App\Services;

use Exception;
use ZipArchive;

class ExcelExportService
{
    /**
     * Generate native .xlsx (OpenXML) file with standard editable worksheet structure.
     *
     * @param string $title
     * @param string $subtitle
     * @param array $headers
     * @param array $widths
     * @param callable $chunkCallback function(callable $writeRow)
     * @return string Absolute path to generated temporary .xlsx file
     * @throws Exception
     */
    public static function generateXlsx(string $title, string $subtitle, array $headers, array $widths, callable $chunkCallback, ?string $statsLine = null): string
    {
        return self::generateMultiSheetXlsx([
            [
                'name' => 'Data Pelanggan',
                'title' => $title,
                'subtitle' => $subtitle,
                'stats' => $statsLine,
                'callback' => $chunkCallback,
            ],
        ], $headers, $widths);
    }

    /**
     * Generate multi-sheet native .xlsx (OpenXML) file compliant with ECMA-376.
     *
     * @param array $sheets Array of ['name' => string, 'title' => string, 'subtitle' => string, 'stats' => ?string, 'callback' => callable]
     * @param array $headers
     * @param array $widths
     * @return string Absolute path to generated temporary .xlsx file
     * @throws Exception
     */
    public static function generateMultiSheetXlsx(array $sheets, array $headers, array $widths): string
    {
        if (empty($sheets)) {
            throw new Exception("Minimal 1 sheet harus didefinisikan.");
        }

        $colsXml = '';
        if (!empty($widths)) {
            $colsXml .= '<cols>';
            foreach (array_values($widths) as $i => $w) {
                $colNum = $i + 1;
                $colsXml .= '<col min="' . $colNum . '" max="' . $colNum . '" width="' . $w . '" customWidth="1"/>';
            }
            $colsXml .= '</cols>';
        }

        $tmpSheets = [];
        $tmpBodies = [];
        $usedNames = [];
        $sharedStringsMap = [];
        $sharedStringsList = [];
        $sharedStringRefs = 0;

        $internString = function (string $text) use (&$sharedStringsMap, &$sharedStringsList, &$sharedStringRefs): int {
            $clean = self::cleanXmlString($text);
            $sharedStringRefs++;
            if (isset($sharedStringsMap[$clean])) {
                return $sharedStringsMap[$clean];
            }
            $idx = count($sharedStringsList);
            $sharedStringsMap[$clean] = $idx;
            $sharedStringsList[] = $clean;
            return $idx;
        };

        try {
            foreach ($sheets as $idx => $sheet) {
                $sheetNum = $idx + 1;
                $sheetName = self::sanitizeSheetName((string)($sheet['name'] ?? ''), $sheetNum, $usedNames);
                $title = (string)($sheet['title'] ?? '');
                $subtitle = (string)($sheet['subtitle'] ?? '');
                $statsLine = trim((string)($sheet['stats'] ?? ''));
                $chunkCallback = $sheet['callback'];

                $tmpBody = tempnam(sys_get_temp_dir(), 'xlsx_body_');
                $tmpSheet = tempnam(sys_get_temp_dir(), 'xlsx_sheet_');
                if ($tmpBody === false || $tmpSheet === false) {
                    throw new Exception("Gagal membuat temporary worksheet file.");
                }
                $tmpBodies[] = $tmpBody;

                $bfh = fopen($tmpBody, 'w');
                if (!$bfh) {
                    throw new Exception("Gagal membuka temporary worksheet stream.");
                }

                $maxColCount = max(1, count($headers));
                $rIdx = 1;

                fwrite($bfh, '<row r="' . $rIdx . '" ht="26" customHeight="1"><c r="A' . $rIdx . '" s="1" t="s"><v>' . $internString($title) . '</v></c></row>' . "\n");
                $rIdx++;

                fwrite($bfh, '<row r="' . $rIdx . '" ht="18" customHeight="1"><c r="A' . $rIdx . '" s="2" t="s"><v>' . $internString($subtitle) . '</v></c></row>' . "\n");
                $rIdx++;

                if ($statsLine !== '') {
                    fwrite($bfh, '<row r="' . $rIdx . '" ht="22" customHeight="1"><c r="A' . $rIdx . '" s="11" t="s"><v>' . $internString($statsLine) . '</v></c></row>' . "\n");
                } else {
                    fwrite($bfh, '<row r="' . $rIdx . '" ht="8" customHeight="1"/>' . "\n");
                }
                $rIdx++;

                fwrite($bfh, '<row r="' . $rIdx . '" ht="28" customHeight="1">');
                foreach (array_values($headers) as $cIdx => $h) {
                    $colLetter = self::colLetter($cIdx);
                    fwrite($bfh, '<c r="' . $colLetter . $rIdx . '" s="3" t="s"><v>' . $internString((string)$h) . '</v></c>');
                }
                fwrite($bfh, '</row>' . "\n");
                $rIdx++;

                $rowNum = 0;
                $chunkCallback(function (array $row) use ($bfh, &$rIdx, &$rowNum, &$maxColCount, $internString) {
                    $isZebra = ($rowNum % 2 === 1);
                    fwrite($bfh, '<row r="' . $rIdx . '" ht="22" customHeight="1">');
                    $cIdx = 0;
                    foreach ($row as $cell) {
                        $colLetter = self::colLetter($cIdx);
                        $isCenter = in_array($cIdx, [0, 1, 3, 4, 5, 8, 9, 10, 13, 17, 19, 20], true);
                        $style = $isZebra ? ($isCenter ? 7 : 6) : ($isCenter ? 5 : 4);

                        $valStr = is_null($cell) ? '' : (string)$cell;
                        if ($cIdx === 17) {
                            if (stripos($valStr, 'Aktif') !== false || stripos($valStr, 'Berlangganan') !== false) {
                                $style = 8;
                            } elseif (stripos($valStr, 'Isolir') !== false || stripos($valStr, 'Suspend') !== false) {
                                $style = 9;
                            } elseif (stripos($valStr, 'Pending') !== false || stripos($valStr, 'Survey') !== false || stripos($valStr, 'Instalasi') !== false) {
                                $style = 10;
                            }
                        }

                        $isNumericCell = is_int($cell) || is_float($cell) || ($cIdx === 0 && is_numeric($cell));
                        if ($valStr === '') {
                            fwrite($bfh, '<c r="' . $colLetter . $rIdx . '" s="' . $style . '"/>');
                        } elseif ($isNumericCell) {
                            fwrite($bfh, '<c r="' . $colLetter . $rIdx . '" s="' . $style . '"><v>' . $valStr . '</v></c>');
                        } else {
                            fwrite($bfh, '<c r="' . $colLetter . $rIdx . '" s="' . $style . '" t="s"><v>' . $internString($valStr) . '</v></c>');
                        }
                        $cIdx++;
                    }
                    if ($cIdx > $maxColCount) {
                        $maxColCount = $cIdx;
                    }
                    fwrite($bfh, '</row>' . "\n");
                    $rIdx++;
                    $rowNum++;
                });

                fclose($bfh);

                $lastRow = max(1, $rIdx - 1);
                $lastColLetter = self::colLetter(max(0, $maxColCount - 1));
                $tabSelected = ($sheetNum === 1) ? ' tabSelected="1"' : '';

                $fh = fopen($tmpSheet, 'w');
                $brh = fopen($tmpBody, 'r');
                if (!$fh || !$brh) {
                    if ($fh) {
                        fclose($fh);
                    }
                    if ($brh) {
                        fclose($brh);
                    }
                    throw new Exception("Gagal merangkai worksheet file.");
                }

                fwrite($fh, '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n");
                fwrite($fh, '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">' . "\n");
                fwrite($fh, '  <dimension ref="A1:' . $lastColLetter . $lastRow . '"/>' . "\n");
                fwrite($fh, '  <sheetViews><sheetView' . $tabSelected . ' workbookViewId="0"><selection activeCell="A1" sqref="A1"/></sheetView></sheetViews>' . "\n");
                fwrite($fh, '  <sheetFormatPr defaultRowHeight="20"/>' . "\n");
                if ($colsXml !== '') {
                    fwrite($fh, '  ' . $colsXml . "\n");
                }
                fwrite($fh, '  <sheetData>' . "\n");
                stream_copy_to_stream($brh, $fh);
                fwrite($fh, '  </sheetData>' . "\n");
                fwrite($fh, '</worksheet>');

                fclose($brh);
                fclose($fh);
                @unlink($tmpBody);

                $tmpSheets[] = [
                    'num' => $sheetNum,
                    'name' => $sheetName,
                    'file' => $tmpSheet,
                ];
            }

            $tmpZipBase = tempnam(sys_get_temp_dir(), 'xlsx_zip_');
            $tmpZip = $tmpZipBase . '.xlsx';
            @unlink($tmpZipBase);

            $zip = new ZipArchive();
            if ($zip->open($tmpZip, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new Exception("Gagal membuat arsip XLSX.");
            }

            $sheetOverrides = '';
            $workbookRels = '';
            $workbookSheets = '';
            foreach ($tmpSheets as $s) {
                $n = $s['num'];
                $sheetOverrides .= "\n" . '  <Override PartName="/xl/worksheets/sheet' . $n . '.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
                $workbookRels .= "\n" . '  <Relationship Id="rId' . $n . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet' . $n . '.xml"/>';
                $workbookSheets .= "\n" . '    <sheet name="' . htmlspecialchars($s['name'], ENT_XML1 | ENT_QUOTES, 'UTF-8') . '" sheetId="' . $n . '" r:id="rId' . $n . '"/>';
            }
            $stylesRelId = count($tmpSheets) + 1;
            $sharedStringsRelId = count($tmpSheets) + 2;

            $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
  <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
  <Default Extension="xml" ContentType="application/xml"/>
  <Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>' . $sheetOverrides . '
  <Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>
  <Override PartName="/xl/sharedStrings.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sharedStrings+xml"/>
  <Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>
  <Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>
</Types>');

            $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
  <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>
  <Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/>
</Relationships>');

            $nowIso = gmdate('Y-m-d\TH:i:s\Z');
            $zip->addFromString('docProps/core.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:dcmitype="http://purl.org/dc/dcmitype/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
  <dc:creator>IMS</dc:creator>
  <cp:lastModifiedBy>IMS</cp:lastModifiedBy>
  <dcterms:created xsi:type="dcterms:W3CDTF">' . $nowIso . '</dcterms:created>
  <dcterms:modified xsi:type="dcterms:W3CDTF">' . $nowIso . '</dcterms:modified>
</cp:coreProperties>');

            $zip->addFromString('docProps/app.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties" xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes">
  <Application>Microsoft Excel</Application>
  <DocSecurity>0</DocSecurity>
  <ScaleCrop>false</ScaleCrop>
  <LinksUpToDate>false</LinksUpToDate>
  <SharedDoc>false</SharedDoc>
  <HyperlinksChanged>false</HyperlinksChanged>
  <AppVersion>16.0300</AppVersion>
</Properties>');

            $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' . $workbookRels . '
  <Relationship Id="rId' . $stylesRelId . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>
  <Relationship Id="rId' . $sharedStringsRelId . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/sharedStrings" Target="sharedStrings.xml"/>
</Relationships>');

            $zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
  <bookViews>
    <workbookView xWindow="0" yWindow="0" windowWidth="24000" windowHeight="15000" activeTab="0"/>
  </bookViews>
  <sheets>' . $workbookSheets . '
  </sheets>
</workbook>');

            $zip->addFromString('xl/styles.xml', self::buildStylesXml());

            $tmpSst = tempnam(sys_get_temp_dir(), 'xlsx_sst_');
            if ($tmpSst === false) {
                throw new Exception("Gagal membuat temporary sharedStrings file.");
            }
            $tmpBodies[] = $tmpSst;
            $sfh = fopen($tmpSst, 'w');
            if (!$sfh) {
                throw new Exception("Gagal membuka temporary sharedStrings stream.");
            }
            fwrite($sfh, '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n");
            fwrite($sfh, '<sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" count="' . $sharedStringRefs . '" uniqueCount="' . count($sharedStringsList) . '">' . "\n");
            foreach ($sharedStringsList as $str) {
                $escaped = htmlspecialchars($str, ENT_XML1 | ENT_QUOTES, 'UTF-8');
                $preserve = preg_match('/^\s|\s$/u', $str) ? ' xml:space="preserve"' : '';
                fwrite($sfh, '  <si><t' . $preserve . '>' . $escaped . '</t></si>' . "\n");
            }
            fwrite($sfh, '</sst>');
            fclose($sfh);
            $zip->addFile($tmpSst, 'xl/sharedStrings.xml');

            foreach ($tmpSheets as $s) {
                $zip->addFile($s['file'], 'xl/worksheets/sheet' . $s['num'] . '.xml');
            }
            $zip->close();

            return $tmpZip;
        } finally {
            foreach ($tmpSheets as $s) {
                @unlink($s['file']);
            }
            foreach ($tmpBodies as $b) {
                @unlink($b);
            }
        }
    }

    /**
     * Build OOXML stylesheet with integer font sizes, proper element order, and complete style tables.
     */
    protected static function buildStylesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
  <fonts count="8">
    <font><sz val="10"/><color rgb="FF334155"/><name val="Calibri"/><family val="2"/></font>
    <font><b/><sz val="14"/><color rgb="FF0F172A"/><name val="Calibri"/><family val="2"/></font>
    <font><sz val="10"/><color rgb="FF64748B"/><name val="Calibri"/><family val="2"/></font>
    <font><b/><sz val="10"/><color rgb="FFFFFFFF"/><name val="Calibri"/><family val="2"/></font>
    <font><b/><sz val="10"/><color rgb="FF166534"/><name val="Calibri"/><family val="2"/></font>
    <font><b/><sz val="10"/><color rgb="FF991B1B"/><name val="Calibri"/><family val="2"/></font>
    <font><b/><sz val="10"/><color rgb="FF854D0E"/><name val="Calibri"/><family val="2"/></font>
    <font><b/><sz val="11"/><color rgb="FF047857"/><name val="Calibri"/><family val="2"/></font>
  </fonts>
  <fills count="7">
    <fill><patternFill patternType="none"/></fill>
    <fill><patternFill patternType="gray125"/></fill>
    <fill><patternFill patternType="solid"><fgColor rgb="FF1E293B"/></patternFill></fill>
    <fill><patternFill patternType="solid"><fgColor rgb="FFF8FAFC"/></patternFill></fill>
    <fill><patternFill patternType="solid"><fgColor rgb="FFDCFCE7"/></patternFill></fill>
    <fill><patternFill patternType="solid"><fgColor rgb="FFFEE2E2"/></patternFill></fill>
    <fill><patternFill patternType="solid"><fgColor rgb="FFFEF3C7"/></patternFill></fill>
  </fills>
  <borders count="2">
    <border><left/><right/><top/><bottom/><diagonal/></border>
    <border>
      <left style="thin"><color rgb="FFE2E8F0"/></left>
      <right style="thin"><color rgb="FFE2E8F0"/></right>
      <top style="thin"><color rgb="FFE2E8F0"/></top>
      <bottom style="thin"><color rgb="FFE2E8F0"/></bottom>
      <diagonal/>
    </border>
  </borders>
  <cellStyleXfs count="1">
    <xf numFmtId="0" fontId="0" fillId="0" borderId="0"/>
  </cellStyleXfs>
  <cellXfs count="12">
    <xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>
    <xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1" applyAlignment="1"><alignment vertical="center"/></xf>
    <xf numFmtId="0" fontId="2" fillId="0" borderId="0" xfId="0" applyFont="1" applyAlignment="1"><alignment vertical="center"/></xf>
    <xf numFmtId="0" fontId="3" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>
    <xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1" applyAlignment="1"><alignment horizontal="left" vertical="center"/></xf>
    <xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>
    <xf numFmtId="0" fontId="0" fillId="3" borderId="1" xfId="0" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="left" vertical="center"/></xf>
    <xf numFmtId="0" fontId="0" fillId="3" borderId="1" xfId="0" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>
    <xf numFmtId="0" fontId="4" fillId="4" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>
    <xf numFmtId="0" fontId="5" fillId="5" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>
    <xf numFmtId="0" fontId="6" fillId="6" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>
    <xf numFmtId="0" fontId="7" fillId="0" borderId="0" xfId="0" applyFont="1" applyAlignment="1"><alignment vertical="center"/></xf>
  </cellXfs>
  <cellStyles count="1">
    <cellStyle name="Normal" xfId="0" builtinId="0"/>
  </cellStyles>
  <dxfs count="0"/>
  <tableStyles count="0" defaultTableStyle="TableStyleMedium2" defaultPivotStyle="PivotStyleLight16"/>
</styleSheet>';
    }

    /**
     * Normalize to valid UTF-8 and strip XML 1.0 illegal control characters.
     */
    protected static function cleanXmlString(string $value): string
    {
        if (!mb_check_encoding($value, 'UTF-8')) {
            $value = mb_convert_encoding($value, 'UTF-8', 'UTF-8');
        }
        $cleaned = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value);
        if ($cleaned === null) {
            $cleaned = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $value);
        }
        return $cleaned ?? '';
    }

    /**
     * Sanitize an Excel worksheet tab name to conform to OOXML rules (<= 31 chars, unique, no invalid chars).
     */
    public static function sanitizeSheetName(string $name, int $sheetNum, array &$usedNames): string
    {
        $clean = preg_replace('/[\\\\\\/?*:\\[\\]]/', ' ', trim($name));
        $clean = trim(preg_replace('/\s+/', ' ', $clean));
        if ($clean === '') {
            $clean = "Sheet {$sheetNum}";
        }

        $base = mb_substr($clean, 0, 31);
        $candidate = $base;
        $counter = 2;
        while (in_array(mb_strtolower($candidate), $usedNames, true)) {
            $suffix = " ({$counter})";
            $candidate = mb_substr($base, 0, 31 - mb_strlen($suffix)) . $suffix;
            $counter++;
        }

        $usedNames[] = mb_strtolower($candidate);
        return $candidate;
    }

    /**
     * Convert 0-indexed column integer to Excel column letter (0 => A, 27 => AB, etc)
     */
    public static function colLetter(int $index): string
    {
        $letter = '';
        while ($index >= 0) {
            $letter = chr($index % 26 + 65) . $letter;
            $index = intval($index / 26) - 1;
        }
        return $letter;
    }
}
