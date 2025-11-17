<?php

namespace App\Http\Controllers;

use App\Services\ExpenseIngestionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ExpenseIngestionController extends Controller
{
    public function create()
    {
        // Minimal inline view to keep setup simple
        return response()->view('expenses.ingest');
    }

    public function store(Request $request, ExpenseIngestionService $service)
    {
        Log::info('ExpenseIngestionController: Store request received', [
            'all_files' => array_keys($request->allFiles()),
            'has_csv' => $request->hasFile('csv'),
            'content_type' => $request->header('Content-Type'),
        ]);

        // Match the input name from the blade template (name="csv")
        $validated = $request->validate([
            'csv' => 'required|file|mimetypes:text/plain,text/csv,application/csv,application/vnd.ms-excel|mimes:csv,txt|max:10240',
        ]);

        $file = $request->file('csv');
        if (! $file) {
            Log::warning('ExpenseIngestionController: csv file missing after validation');

            return response()->json(['error' => 'File upload failed'], 422);
        }
        $path = $file->getRealPath();

        $rows = $this->parseCsvToAssoc($path);

        // Ingest with rows only; context could be extended later
        $result = $service->ingest($rows);

        // Log full result as requested
        Log::info('Expense CSV ingestion result (full)', $result);

        return response()->json([
            'message' => 'CSV processed and result logged',
            'uploaded_filename' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'size_bytes' => $file->getSize(),
            'expenses_count' => is_array($result['expenses'] ?? null) ? count($result['expenses']) : 0,
            'errors' => $result['errors'] ?? [],
        ]);
    }

    /**
     * Convert CSV file to array of associative rows.
     */
    protected function parseCsvToAssoc(string $path): array
    {
        $handle = fopen($path, 'r');
        if ($handle === false) {
            return [];
        }

        $rows = [];
        $headers = null;

        while (($data = fgetcsv($handle)) !== false) {
            if ($headers === null) {
                // Trim BOM from first cell if present
                if (! empty($data)) {
                    $data[0] = preg_replace('/^\xEF\xBB\xBF/', '', $data[0]);
                }
                $headers = array_map(function ($h) {
                    $h = trim((string) $h);
                    // Normalize to snake_case-ish keys
                    $h = strtolower(preg_replace('/[^a-z0-9]+/i', '_', $h));

                    return trim($h, '_');
                }, $data);

                continue;
            }

            // Pad or slice to headers length
            if (count($data) < count($headers)) {
                $data = array_pad($data, count($headers), null);
            } elseif (count($data) > count($headers)) {
                $data = array_slice($data, 0, count($headers));
            }

            $rows[] = array_combine($headers, $data);
        }

        fclose($handle);

        return $rows;
    }

    // public function convert(Request $request)
    // {
    //     Log::info('ExcelToJsonController: Convert request received', [
    //         'has_file' => $request->hasFile('file'),
    //     ]);

    //     try {
    //         $request->validate([
    //             'file' => 'required|file|mimes:csv,xls,ods,csv|max:10240',
    //         ]);

    //         Log::info('ExcelToJsonController: File validation passed');

    //         // Increase execution time for large files
    //         set_time_limit(300); // 5 minutes
    //         ini_set('memory_limit', '512M');
    //         Log::info('ExcelToJsonController: Execution limits set', [
    //             'time_limit' => 300,
    //             'memory_limit' => '512M',
    //         ]);

    //         $file = $request->file('file');
    //         $originalName = $file->getClientOriginalName();
    //         $fileSize = $file->getSize();
    //         $mimeType = $file->getMimeType();
    //         $extension = $file->getClientOriginalExtension();

    //         Log::info('ExcelToJsonController: File details', [
    //             'name' => $originalName,
    //             'size' => $fileSize,
    //             'mime' => $mimeType,
    //             'extension' => $extension,
    //         ]);

    //         $realPath = $file->getRealPath();
    //         Log::info('ExcelToJsonController: Calling service', ['path' => $realPath]);

    //         $result = $this->excelService->convertToJson($realPath);

    //         Log::info('ExcelToJsonController: Service returned', [
    //             'success' => $result['success'] ?? false,
    //         ]);

    //         if (! $result['success']) {
    //             Log::error('ExcelToJsonController: Conversion failed', [
    //                 'error' => $result['error'] ?? 'Unknown error',
    //             ]);

    //             return back()->withErrors(['error' => $result['error']]);
    //         }

    //         $jsonSize = strlen($result['json'] ?? '');
    //         $sheetCount = count($result['data'] ?? []);

    //         Log::info('ExcelToJsonController: Conversion successful', [
    //             'json_size' => $jsonSize,
    //             'sheets' => $sheetCount,
    //         ]);

    //         // Store in session and redirect back
    //         return redirect()->route('excel-to-json')->with([
    //             'json' => $result['json'],
    //             'data' => $result['data'],
    //             'filename' => $originalName,
    //         ]);

    //     } catch (\Illuminate\Validation\ValidationException $e) {
    //         Log::error('ExcelToJsonController: Validation failed', [
    //             'errors' => $e->errors(),
    //         ]);
    //         throw $e;
    //     } catch (\Exception $e) {
    //         Log::error('ExcelToJsonController: Exception occurred', [
    //             'error' => $e->getMessage(),
    //             'trace' => $e->getTraceAsString(),
    //         ]);

    //         return back()->withErrors(['error' => 'Conversion failed: '.$e->getMessage()]);
    //     }
    // }
}
