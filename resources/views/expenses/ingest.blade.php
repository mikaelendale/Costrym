<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Expense CSV Ingestion</title>
    <style>
        body { font-family: system-ui, -apple-system, Segoe UI, Roboto, Ubuntu, Cantarell, Noto Sans, Helvetica, Arial, "Apple Color Emoji", "Segoe UI Emoji"; margin: 2rem; }
        .card { max-width: 600px; padding: 1.5rem; border: 1px solid #e5e7eb; border-radius: 0.5rem; }
        label { display:block; margin-top: 1rem; font-weight: 600; }
        input[type="text"], input[type="file"] { width: 100%; padding: 0.5rem; margin-top: 0.25rem; }
        button { margin-top: 1rem; padding: 0.5rem 1rem; background:#111827; color: white; border:none; border-radius: 0.375rem; cursor: pointer; }
    </style>
</head>
<body>
    <div class="card">
        <h1>Upload Expense CSV</h1>
        <form action="{{ route('expenses.ingest.upload') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <label for="file">CSV file</label>
            <input id="file" type="file" name="file">

            <button type="submit">Upload & Ingest</button>
        </form>
       
    </div>
</body>
</html>
