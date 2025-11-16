<?php

namespace App\Http\Controllers;

use App\Services\ExcelToJsonService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class ExcelToJsonController extends Controller
{
    public function __construct(
        private ExcelToJsonService $excelService
    ) {}

    public function index(Request $request)
    {
        Log::info('ExcelToJsonController: Index page accessed');
        
        // Get data from session (if redirected from convert)
        $json = $request->session()->get('json');
        $data = $request->session()->get('data');
        $filename = $request->session()->get('filename');
        
        if ($json || $data) {
            Log::info('ExcelToJsonController: Found session data', [
                'has_json' => !empty($json),
                'has_data' => !empty($data),
                'filename' => $filename,
            ]);
        }
        
        return Inertia::render('excel-to-json', [
            'json' => $json,
            'data' => $data,
            'filename' => $filename,
        ]);
    }

    public function convert(Request $request)
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
                'memory_limit' => '512M'
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

            $realPath = $file->getRealPath();
            Log::info('ExcelToJsonController: Calling service', ['path' => $realPath]);
            
            $result = $this->excelService->convertToJson($realPath);
            
            Log::info('ExcelToJsonController: Service returned', [
                'success' => $result['success'] ?? false,
            ]);

            if (!$result['success']) {
                Log::error('ExcelToJsonController: Conversion failed', [
                    'error' => $result['error'] ?? 'Unknown error',
                ]);
                return back()->withErrors(['error' => $result['error']]);
            }

            $jsonSize = strlen($result['json'] ?? '');
            $sheetCount = count($result['data'] ?? []);
            
            Log::info('ExcelToJsonController: Conversion successful', [
                'json_size' => $jsonSize,
                'sheets' => $sheetCount,
            ]);

            // Store in session and redirect back
            return redirect()->route('excel-to-json')->with([
                'json' => $result['json'],
                'data' => $result['data'],
                'filename' => $originalName,
            ]);

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
            return back()->withErrors(['error' => 'Conversion failed: ' . $e->getMessage()]);
        }
    }
}

