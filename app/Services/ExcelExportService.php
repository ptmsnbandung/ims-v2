<?php

namespace App\Services;

use Exception;
use ZipArchive;

class ExcelExportService
{
    /**
     * Generate native .xlsx (OpenXML) file with professional formatting without external dependencies.
     *
     * @param string $title
     * @param string $subtitle
     * @param array $headers
     * @param array $widths
     * @param callable $chunkCallback function(callable $writeRow)
     * @return string Absolute path to generated temporary .xlsx file
     * @throws Exception
     */
    public static function generateXlsx(string $title, string $subtitle, array $headers, array $widths, callable $chunkCallback): string
    {
        $tmpSheet = tempnam(sys_get_temp_dir(), 'xlsx_sheet_');
        $fh = fopen($tmpSheet, 'w');
        if (!$fh) {
            throw new Exception("Gagal membuat temporary worksheet file.");
        }

        // Columns definition
        $colsXml = '';
        if (!empty($widths)) {
            $colsXml .= '<cols>';
            $colIdx = 1;
            foreach ($widths as $w) {
                $colsXml .= '<col min="' . $colIdx . '" max="' . $colIdx . '" width="' . $w . '" customWidth="1"/>';
                $colIdx++;
            }
            $colsXml .= '</cols>';
        }

        fwrite($fh, '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n");
        fwrite($fh, '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">' . "\n");
        // Freeze pane below header (row 4)
        fwrite($fh, '  <sheetViews><sheetView tabSelected="1" workbookViewId="0"><pane ySplit="4" topLeftCell="A5" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>' . "\n");
        fwrite($fh, '  ' . $colsXml . "\n");
        fwrite($fh, '  <sheetData>' . "\n");

        $rIdx = 1;

        // Row 1: Document Title (Dark Corporate Slate, 14pt Bold)
        fwrite($fh, '<row r="' . $rIdx . '" ht="26" customHeight="1"><c r="A' . $rIdx . '" s="1" t="inlineStr"><is><t>' . htmlspecialchars($title, ENT_XML1, 'UTF-8') . '</t></is></c></row>' . "\n");
        $rIdx++;

        // Row 2: Subtitle & Export Meta (Muted Gray, 9.5pt)
        fwrite($fh, '<row r="' . $rIdx . '" ht="18" customHeight="1"><c r="A' . $rIdx . '" s="2" t="inlineStr"><is><t>' . htmlspecialchars($subtitle, ENT_XML1, 'UTF-8') . '</t></is></c></row>' . "\n");
        $rIdx++;

        // Row 3: Spacer
        fwrite($fh, '<row r="' . $rIdx . '" ht="8" customHeight="1"></row>' . "\n");
        $rIdx++;

        // Row 4: Table Header (Corporate Slate Fill #1E293B, White Bold Text)
        fwrite($fh, '<row r="' . $rIdx . '" ht="28" customHeight="1">');
        $cIdx = 0;
        foreach ($headers as $h) {
            $colLetter = self::colLetter($cIdx);
            fwrite($fh, '<c r="' . $colLetter . $rIdx . '" s="3" t="inlineStr"><is><t>' . htmlspecialchars($h, ENT_XML1, 'UTF-8') . '</t></is></c>');
            $cIdx++;
        }
        fwrite($fh, '</row>' . "\n");
        $rIdx++;

        // Row 5+: Data Rows via Streaming Callback
        $rowNum = 0;
        $chunkCallback(function (array $row) use ($fh, &$rIdx, &$rowNum) {
            $isZebra = ($rowNum % 2 === 1);
            fwrite($fh, '<row r="' . $rIdx . '" ht="22" customHeight="1">');
            $cIdx = 0;
            foreach ($row as $cell) {
                $colLetter = ExcelExportService::colLetter($cIdx);
                $val = is_null($cell) ? '' : (string)$cell;

                // Alignment determination: Numbers, IDs, Dates, Status to Center
                $isCenter = in_array($cIdx, [0, 1, 3, 4, 5, 8, 9, 12, 16, 18, 19]);
                $style = $isZebra ? ($isCenter ? 7 : 6) : ($isCenter ? 5 : 4);

                // Status Column (Index 16): Badge Styling
                if ($cIdx === 16) {
                    if (stripos($val, 'Aktif') !== false || stripos($val, 'Berlangganan') !== false) {
                        $style = 8; // Emerald badge
                    } elseif (stripos($val, 'Isolir') !== false || stripos($val, 'Suspend') !== false) {
                        $style = 9; // Rose badge
                    } elseif (stripos($val, 'Pending') !== false || stripos($val, 'Survey') !== false || stripos($val, 'Instalasi') !== false) {
                        $style = 10; // Amber badge
                    }
                }

                fwrite($fh, '<c r="' . $colLetter . $rIdx . '" s="' . $style . '" t="inlineStr"><is><t>' . htmlspecialchars($val, ENT_XML1, 'UTF-8') . '</t></is></c>');
                $cIdx++;
            }
            fwrite($fh, '</row>' . "\n");
            $rIdx++;
            $rowNum++;
        });

        fwrite($fh, '  </sheetData>' . "\n");
        fwrite($fh, '</worksheet>');
        fclose($fh);

        // Package into valid OpenXML .xlsx ZIP container
        $tmpZip = tempnam(sys_get_temp_dir(), 'xlsx_zip_') . '.xlsx';
        $zip = new ZipArchive();
        if ($zip->open($tmpZip, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            @unlink($tmpSheet);
            throw new Exception("Gagal membuat arsip XLSX.");
        }

        // 1. [Content_Types].xml
        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
  <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
  <Default Extension="xml" ContentType="application/xml"/>
  <Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
  <Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
  <Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>
</Types>');

        // 2. _rels/.rels
        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
</Relationships>');

        // 3. xl/_rels/workbook.xml.rels
        $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
  <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>
</Relationships>');

        // 4. xl/workbook.xml
        $zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
  <sheets>
    <sheet name="Data Pelanggan" sheetId="1" r:id="rId1"/>
  </sheets>
</workbook>');

        // 5. xl/styles.xml
        $zip->addFromString('xl/styles.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
  <fonts count="7">
    <!-- 0: Default Data Font -->
    <font><sz val="10"/><name val="Segoe UI"/><color rgb="FF334155"/></font>
    <!-- 1: Title Font -->
    <font><b/><sz val="14"/><name val="Segoe UI"/><color rgb="FF0F172A"/></font>
    <!-- 2: Subtitle Font -->
    <font><sz val="9.5"/><name val="Segoe UI"/><color rgb="FF64748B"/></font>
    <!-- 3: Table Header Font -->
    <font><b/><sz val="10"/><name val="Segoe UI"/><color rgb="FFFFFFFF"/></font>
    <!-- 4: Status Active Font -->
    <font><b/><sz val="9.5"/><name val="Segoe UI"/><color rgb="FF166534"/></font>
    <!-- 5: Status Suspend Font -->
    <font><b/><sz val="9.5"/><name val="Segoe UI"/><color rgb="FF991B1B"/></font>
    <!-- 6: Status Pending Font -->
    <font><b/><sz val="9.5"/><name val="Segoe UI"/><color rgb="FF854D0E"/></font>
  </fonts>
  <fills count="7">
    <fill><patternFill patternType="none"/></fill>
    <fill><patternFill patternType="gray125"/></fill>
    <!-- 2: Table Header Fill (Corporate Dark Slate) -->
    <fill><patternFill patternType="solid"><fgColor rgb="FF1E293B"/></patternFill></fill>
    <!-- 3: Zebra Alternate Light Fill -->
    <fill><patternFill patternType="solid"><fgColor rgb="FFF8FAFC"/></patternFill></fill>
    <!-- 4: Status Active Fill (Soft Emerald) -->
    <fill><patternFill patternType="solid"><fgColor rgb="FFDCFCE7"/></patternFill></fill>
    <!-- 5: Status Suspend Fill (Soft Rose) -->
    <fill><patternFill patternType="solid"><fgColor rgb="FFFEE2E2"/></patternFill></fill>
    <!-- 6: Status Pending Fill (Soft Amber) -->
    <fill><patternFill patternType="solid"><fgColor rgb="FFFEF3C7"/></patternFill></fill>
  </fills>
  <borders count="2">
    <!-- 0: None -->
    <border><left/><right/><top/><bottom/><diagonal/></border>
    <!-- 1: Thin Border -->
    <border>
      <left style="thin"><color rgb="FFE2E8F0"/></left>
      <right style="thin"><color rgb="FFE2E8F0"/></right>
      <top style="thin"><color rgb="FFE2E8F0"/></top>
      <bottom style="thin"><color rgb="FFE2E8F0"/></bottom>
    </border>
  </borders>
  <cellStyleXfs count="1">
    <xf numFmtId="0" fontId="0" fillId="0" borderId="0"/>
  </cellStyleXfs>
  <cellXfs count="11">
    <!-- 0: Default -->
    <xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>
    <!-- 1: Title -->
    <xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1"><alignment vertical="center"/></xf>
    <!-- 2: Subtitle -->
    <xf numFmtId="0" fontId="2" fillId="0" borderId="0" xfId="0" applyFont="1"><alignment vertical="center"/></xf>
    <!-- 3: Table Header -->
    <xf numFmtId="0" fontId="3" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>
    <!-- 4: Data Left Normal -->
    <xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1"><alignment horizontal="left" vertical="center"/></xf>
    <!-- 5: Data Center Normal -->
    <xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1"><alignment horizontal="center" vertical="center"/></xf>
    <!-- 6: Data Left Zebra -->
    <xf numFmtId="0" fontId="0" fillId="3" borderId="1" xfId="0" applyFill="1" applyBorder="1"><alignment horizontal="left" vertical="center"/></xf>
    <!-- 7: Data Center Zebra -->
    <xf numFmtId="0" fontId="0" fillId="3" borderId="1" xfId="0" applyFill="1" applyBorder="1"><alignment horizontal="center" vertical="center"/></xf>
    <!-- 8: Status Active Badge -->
    <xf numFmtId="0" fontId="4" fillId="4" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1"><alignment horizontal="center" vertical="center"/></xf>
    <!-- 9: Status Suspend Badge -->
    <xf numFmtId="0" fontId="5" fillId="5" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1"><alignment horizontal="center" vertical="center"/></xf>
    <!-- 10: Status Pending Badge -->
    <xf numFmtId="0" fontId="6" fillId="6" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1"><alignment horizontal="center" vertical="center"/></xf>
  </cellXfs>
</styleSheet>');

        // 6. xl/worksheets/sheet1.xml
        $zip->addFile($tmpSheet, 'xl/worksheets/sheet1.xml');
        $zip->close();
        @unlink($tmpSheet);

        return $tmpZip;
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
