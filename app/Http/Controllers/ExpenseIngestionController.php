<?php

namespace App\Http\Controllers;

use App\Services\CompanyProfileService;
use App\Services\ExcelToJsonService;
use App\Services\ExpenseIngestionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ExpenseIngestionController extends Controller
{
    public function __construct(
        private ExcelToJsonService $excelService,
        private ExpenseIngestionService $ingestionService,
        private CompanyProfileService $companyProfileService
    ) {}

    public function create()
    {
        // Minimal inline view to keep setup simple
        return response()->view('expenses.ingest');
    }

    public function store(Request $request)
    {
        Log::info('ExcelToJsonController: Convert request received', [
            'has_file' => $request->hasFile('file'),
        ]);

        try {
            $request->validate([
                'file' => 'required|file|mimes:xlsx,xls,ods,csv|max:10240',
            ]);

            Log::info('ExcelToJsonController: File validation passed');

            // Increase execution time for large files
            set_time_limit(300); // 5 minutes
            ini_set('memory_limit', '512M');
            Log::info('ExcelToJsonController: Execution limits set', [
                'time_limit' => 300,
                'memory_limit' => '512M',
            ]);

            $file = $request->file('file');
            $originalName = $file->getClientOriginalName();
            $fileSize = $file->getSize();
            $mimeType = $file->getMimeType();
            $extension = $file->getClientOriginalExtension();

            Log::info('ExcelToJsonController: File details', [
                'name' => $originalName,
                'size' => $fileSize,
                'mime' => $mimeType,
                'extension' => $extension,
            ]);

            // Convert immediately (avoid temp-file lifetime issues) and then queue AI ingestion per 50 rows
            $tmpPath = $file->getRealPath();
            Log::info('ExcelToJsonController: Calling service for immediate conversion', [
                'path' => $tmpPath,
            ]);

            $result = $this->excelService->convertToJson($tmpPath);

            if (! ($result['success'] ?? false)) {
                Log::error('ExcelToJsonController: Conversion failed', [
                    'error' => $result['error'] ?? 'unknown',
                ]);

                return back()->withErrors(['error' => 'Conversion failed: '.($result['error'] ?? 'unknown')]);
            }

            $data = $result['data'] ?? [];
            Log::info('ExcelToJsonController: Conversion succeeded', [
                'data' => $data,
            ]);

            $this->companyProfileService->createCompanyProfile($data);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('ExcelToJsonController: Validation failed', [
                'errors' => $e->errors(),
            ]);
            throw $e;
        } catch (\Exception $e) {
            Log::error('ExcelToJsonController: Exception occurred', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->withErrors(['error' => 'Conversion failed: '.$e->getMessage()]);
        }

        // Optional UX: redirect back with a success message
        return back()->with('status', 'Your file has been processed and queued for AI ingestion in batches.');
    }
}
