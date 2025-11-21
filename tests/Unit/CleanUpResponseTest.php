<?php

use App\Services\CleanUpResponse;

describe('CleanUpResponse::extractJsonPayload', function () {
    it('decodes JSON with escaped newlines from a quoted string', function () {
        // The entire JSON object is returned as a string with escaped quotes and newlines
        $input = '"{\\"message\\":\\"Hello\\nWorld\\"}"';
        $result = CleanUpResponse::extractJsonPayload($input);

        expect($result)->toBeArray();
        expect($result)->toHaveKey('message');
        expect($result['message'])->toBe("Hello\nWorld");
    });

    it('decodes JSON inside code fences', function () {
        $input = <<<'MD'
```json
{"a": 1, "b": "ok"}
```
MD;
        $result = CleanUpResponse::extractJsonPayload($input);

        expect($result)->toBeArray();
        expect($result['a'])->toBe(1);
        expect($result['b'])->toBe('ok');
    });

    it('finds JSON within surrounding prose and entities', function () {
        $input = 'Here is the payload: &quot;{"x":"y"}&quot; thanks.';
        $result = CleanUpResponse::extractJsonPayload($input);

        expect($result)->toBeArray();
        expect($result['x'])->toBe('y');
    });
});
