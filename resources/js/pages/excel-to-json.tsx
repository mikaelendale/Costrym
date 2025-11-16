import { useState, useEffect } from 'react';
import { Head, router, usePage } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Upload, FileSpreadsheet, Download, Copy, Check, LoaderCircle } from 'lucide-react';
import { toast } from 'sonner';

interface ExcelToJsonProps {
    json?: string;
    data?: any;
    filename?: string;
    errors?: {
        error?: string;
    };
}

export default function ExcelToJson() {
    const { props } = usePage<ExcelToJsonProps>();
    const [file, setFile] = useState<File | null>(null);
    const [isUploading, setIsUploading] = useState(false);
    const [copied, setCopied] = useState(false);

    // Debug: Log props when they change
    useEffect(() => {
        if (props.json) {
            console.log('JSON received:', props.json.substring(0, 100) + '...');
            console.log('Data received:', props.data);
            console.log('Filename:', props.filename);
        }
    }, [props.json, props.data]);

    const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        const selectedFile = e.target.files?.[0];
        if (selectedFile) {
            setFile(selectedFile);
        }
    };

    const handleConvert = (e: React.FormEvent) => {
        e.preventDefault();
        
        if (!file) {
            toast.error('Please select a file first');
            return;
        }

        setIsUploading(true);
        
        router.post(route('excel-to-json.convert'), {
            file: file,
        }, {
            forceFormData: true,
            onSuccess: () => {
                setIsUploading(false);
                toast.success('Excel file converted to JSON successfully!');
            },
            onError: (errors) => {
                setIsUploading(false);
                toast.error(errors.error || 'Conversion failed');
            },
        });
    };

    const downloadJson = () => {
        // Use json if available, otherwise convert data to JSON
        const jsonToDownload = props.json || (props.data ? JSON.stringify(props.data, null, 2) : null);
        
        if (!jsonToDownload) {
            console.error('No JSON data available for download');
            toast.error('No JSON data available');
            return;
        }

        console.log('Downloading JSON, size:', jsonToDownload.length);
        
        try {
            const blob = new Blob([jsonToDownload], { type: 'application/json' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `${props.filename?.replace(/\.[^/.]+$/, '') || 'converted'}.json`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
            toast.success('JSON file downloaded!');
            console.log('Download triggered successfully');
        } catch (error) {
            console.error('Download error:', error);
            toast.error('Failed to download file');
        }
    };

    const copyToClipboard = () => {
        const jsonToCopy = props.json || (props.data ? JSON.stringify(props.data, null, 2) : null);
        
        if (!jsonToCopy) {
            toast.error('No JSON data to copy');
            return;
        }
        
        navigator.clipboard.writeText(jsonToCopy);
        setCopied(true);
        toast.success('JSON copied to clipboard!');
        setTimeout(() => setCopied(false), 2000);
    };

    return (
        <>
            <Head title="Excel to JSON Converter" />

            <div className="min-h-screen bg-gray-50 dark:bg-gray-900 py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                        <div className="p-6 text-gray-900 dark:text-gray-100">
                            <div className="mb-6">
                                <h2 className="text-2xl font-bold mb-2">📊 Excel to JSON Converter</h2>
                                <p className="text-gray-600 dark:text-gray-400">
                                    Convert spreadsheet files to JSON format. Supports multiple sheets.
                                </p>
                                <p className="text-sm text-gray-500 dark:text-gray-500 mt-1">
                                    Supported formats: <strong>XLSX, XLS, ODS, CSV</strong>
                                </p>
                            </div>

                            {/* Upload Form */}
                            <form onSubmit={handleConvert} className="mb-6">
                                <div className="mb-4">
                                    <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Select Excel File
                                    </label>
                                    <div className="flex items-center gap-4">
                                        <label className="flex flex-col items-center justify-center w-full h-32 border-2 border-gray-300 border-dashed rounded-lg cursor-pointer bg-gray-50 hover:bg-gray-100 dark:border-gray-600 dark:bg-gray-700 dark:hover:bg-gray-600 transition-colors">
                                            <div className="flex flex-col items-center justify-center pt-5 pb-6">
                                                <Upload className="w-8 h-8 mb-2 text-gray-500 dark:text-gray-400" />
                                                <p className="mb-2 text-sm text-gray-500 dark:text-gray-400">
                                                    <span className="font-semibold">Click to upload</span> or drag and drop
                                                </p>
                                                <p className="text-xs text-gray-500 dark:text-gray-400">XLSX, XLS, ODS, CSV (MAX. 10MB)</p>
                                            </div>
                                            <input
                                                type="file"
                                                accept=".xlsx,.xls,.ods,.csv"
                                                onChange={handleFileChange}
                                                className="hidden"
                                            />
                                        </label>
                                    </div>
                                    {file && (
                                        <div className="mt-2 flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                                            <FileSpreadsheet className="w-4 h-4" />
                                            <span className="font-medium">{file.name}</span>
                                            <span className="text-gray-500">({(file.size / 1024).toFixed(2)} KB)</span>
                                        </div>
                                    )}
                                </div>

                                <Button
                                    type="submit"
                                    disabled={!file || isUploading}
                                    className="w-full"
                                >
                                    {isUploading ? (
                                        <>
                                            <LoaderCircle className="w-4 h-4 mr-2 animate-spin" />
                                            Converting...
                                        </>
                                    ) : (
                                        <>
                                            <Upload className="w-4 h-4 mr-2" />
                                            Convert to JSON
                                        </>
                                    )}
                                </Button>
                            </form>

                            {/* Error Display */}
                            {props.errors?.error && (
                                <div className="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg dark:bg-red-900/20 dark:border-red-800">
                                    <p className="text-sm text-red-800 dark:text-red-200">
                                        <strong>Error:</strong> {props.errors.error}
                                    </p>
                                </div>
                            )}

                            {/* Debug Info */}
                            {process.env.NODE_ENV === 'development' && (
                                <div className="mb-4 p-2 bg-gray-100 dark:bg-gray-700 rounded text-xs">
                                    <strong>Debug:</strong> JSON exists: {props.json ? 'Yes' : 'No'} | 
                                    Data exists: {props.data ? 'Yes' : 'No'} | 
                                    Filename: {props.filename || 'None'}
                                </div>
                            )}

                            {/* Result Display */}
                            {(props.json || props.data) && (
                                <div className="mt-6">
                                    <div className="mb-4 flex items-center justify-between">
                                        <h3 className="text-lg font-semibold text-green-600 dark:text-green-400">
                                            ✅ Conversion Complete
                                        </h3>
                                        <div className="flex gap-2">
                                            <Button
                                                onClick={copyToClipboard}
                                                variant="outline"
                                                size="sm"
                                            >
                                                {copied ? (
                                                    <>
                                                        <Check className="w-4 h-4 mr-2" />
                                                        Copied!
                                                    </>
                                                ) : (
                                                    <>
                                                        <Copy className="w-4 h-4 mr-2" />
                                                        Copy
                                                    </>
                                                )}
                                            </Button>
                                            <Button
                                                onClick={downloadJson}
                                                variant="outline"
                                                size="sm"
                                                className="bg-green-600 hover:bg-green-700 text-white"
                                            >
                                                <Download className="w-4 h-4 mr-2" />
                                                Download JSON
                                            </Button>
                                        </div>
                                    </div>

                                    {/* Sheet Info */}
                                    {props.data && typeof props.data === 'object' && (
                                        <div className="mb-4 p-3 bg-blue-50 border border-blue-200 rounded-lg dark:bg-blue-900/20 dark:border-blue-800">
                                            <p className="text-sm font-medium text-blue-800 dark:text-blue-200 mb-2">
                                                📊 Sheets Found: {Object.keys(props.data).length}
                                            </p>
                                            <ul className="text-sm text-blue-700 dark:text-blue-300 space-y-1">
                                                {Object.keys(props.data).map((sheetName, idx) => {
                                                    const sheetData = props.data[sheetName];
                                                    const rowCount = Array.isArray(sheetData) ? sheetData.length : 0;
                                                    return (
                                                        <li key={idx}>
                                                            • {sheetName} ({rowCount} rows)
                                                        </li>
                                                    );
                                                })}
                                            </ul>
                                        </div>
                                    )}

                                    {/* JSON Preview */}
                                    <div className="rounded-lg border border-gray-300 bg-gray-50 p-4 dark:bg-gray-900 dark:border-gray-700">
                                        <pre className="max-h-96 overflow-auto text-sm text-gray-800 dark:text-gray-200 whitespace-pre-wrap font-mono">
                                            {props.json || JSON.stringify(props.data, null, 2)}
                                        </pre>
                                    </div>
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}

