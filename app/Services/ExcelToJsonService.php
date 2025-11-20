<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Knackline\ExcelTo\ExcelTo;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ExcelToJsonService
{
    /**
     * Convert spreadsheet file to JSON
     *
     * Supported formats (via PhpSpreadsheet):
     * - XLSX (Excel 2007+)
     * - XLS (Excel 97-2003)
     * - ODS (OpenDocument Spreadsheet)
     * - CSV (Comma Separated Values)
     * - HTML (HTML tables)
     *
     * @param  string  $filePath  Path to the spreadsheet file
     * @return array{success: bool, json?: string, data?: array, error?: string}
     */
    public function convertToJson(string $filePath): array
    {
        try {
            Log::info('ExcelToJsonService: Starting conversion', ['file' => $filePath]);

            if (! file_exists($filePath)) {
                Log::error('ExcelToJsonService: File not found', ['file' => $filePath]);

                return ['success' => false, 'error' => 'File not found'];
            }

            Log::info('ExcelToJsonService: File exists, identifying format');

            // Identify file format
            $fileType = IOFactory::identify($filePath);
            Log::info('ExcelToJsonService: File format identified', ['type' => $fileType]);

            // Use PhpSpreadsheet directly with readDataOnly to skip formulas (MUCH FASTER)
            Log::info('ExcelToJsonService: Creating reader', ['type' => $fileType]);
            $reader = IOFactory::createReader($fileType);

            Log::info('ExcelToJsonService: Setting readDataOnly to true');
            $reader->setReadDataOnly(true); // Critical: Only read data, skip formula calculations

            Log::info('ExcelToJsonService: Loading spreadsheet');
            $spreadsheet = $reader->load($filePath);
            Log::info('ExcelToJsonService: Spreadsheet loaded successfully');

            // Convert to array manually (faster than using the library twice)
            $arrayData = [];
            $sheetCount = $spreadsheet->getSheetCount();
            Log::info('ExcelToJsonService: Processing sheets', ['count' => $sheetCount]);

            foreach ($spreadsheet->getAllSheets() as $index => $sheet) {
                $sheetName = $sheet->getTitle();
                Log::info('ExcelToJsonService: Processing sheet', [
                    'index' => $index + 1,
                    'name' => $sheetName,
                    'total' => $sheetCount,
                ]);

                $sheetData = $sheet->toArray();
                $rowCount = count($sheetData);
                Log::info('ExcelToJsonService: Sheet converted to array', [
                    'sheet' => $sheetName,
                    'rows' => $rowCount,
                ]);

                $arrayData[$sheetName] = $sheetData;
            }

            Log::info('ExcelToJsonService: All sheets processed', ['sheets' => count($arrayData)]);

            // Convert array to JSON
            Log::info('ExcelToJsonService: Converting to JSON');
            $jsonData = json_encode($arrayData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

            if ($jsonData === false) {
                $error = json_last_error_msg();
                Log::error('ExcelToJsonService: JSON encoding failed', ['error' => $error]);

                return [
                    'success' => false,
                    'error' => 'JSON encoding failed: '.$error,
                ];
            }

            $jsonSize = strlen($jsonData);
            Log::info('ExcelToJsonService: Conversion complete', [
                'json_size' => $jsonSize,
                'sheets' => count($arrayData),
            ]);

            return [
                'success' => true,
                'json' => $jsonData,
                'data' => $arrayData,
            ];

        } catch (\Exception $e) {
            Log::error('ExcelToJsonService: Exception occurred', [
                'file' => $filePath,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Convert Excel file to Laravel Collection
     *
     * @param  string  $filePath  Path to the Excel file
     * @return array{success: bool, collection?: \Illuminate\Support\Collection, error?: string}
     */
    public function convertToCollection(string $filePath): array
    {
        try {
            if (! file_exists($filePath)) {
                return ['success' => false, 'error' => 'File not found'];
            }

            $collection = ExcelTo::collection($filePath);

            return [
                'success' => true,
                'collection' => $collection,
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Convert Excel file to PHP Array
     *
     * @param  string  $filePath  Path to the Excel file
     * @return array{success: bool, data?: array, error?: string}
     */
    public function convertToArray(string $filePath): array
    {
        try {
            if (! file_exists($filePath)) {
                return ['success' => false, 'error' => 'File not found'];
            }

            $arrayData = ExcelTo::array($filePath);

            return [
                'success' => true,
                'data' => $arrayData,
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}
