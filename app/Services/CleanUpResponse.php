<?php

namespace App\Services;

use Illuminate\Support\Collection;
use JsonException;
use RuntimeException;

class CleanUpResponse
{
    /**
     * Extract the JSON payload stored inside the text portion of a Prism response.
     *
     * @throws RuntimeException when no text payload can be found or when its contents are not valid JSON.
     */
    public static function extractJsonPayload(mixed $response): array
    {
        $text = static::extractText($response);

        if ($text === '') {
            // As a fallback, try to locate any valid JSON string embedded anywhere in the response structure.
            $json = static::searchForJsonString($response);
            if ($json !== null) {
                try {
                    return json_decode($json, true, 512, JSON_THROW_ON_ERROR);
                } catch (JsonException $exception) {
                    throw new RuntimeException('LLM response text is not valid JSON.', 0, $exception);
                }
            }

            throw new RuntimeException('Empty text payload returned from LLM response.');
        }

        try {
            return json_decode($text, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            // Attempt salvage: look for a valid JSON object within the provided text (handles code fences and embedded prose)
            $salvaged = static::searchForJsonString($text);
            if ($salvaged !== null) {
                try {
                    return json_decode($salvaged, true, 512, JSON_THROW_ON_ERROR);
                } catch (JsonException $e) {
                    // fallthrough to original error below
                }
            }
            throw new RuntimeException('LLM response text is not valid JSON.', 0, $exception);
        }
    }

    protected static function extractText(mixed $response): string
    {
        if (is_string($response)) {
            return trim($response);
        }

        if ($response instanceof Collection) {
            return static::extractText($response->all());
        }

        if (is_array($response)) {
            if (array_key_exists('text', $response)) {
                return trim((string) $response['text']);
            }

            // Common LLM/Chat response shapes
            if (array_key_exists('content', $response) && is_string($response['content'])) {
                return trim($response['content']);
            }

            if (array_key_exists('output', $response)) {
                // output could be a string or array/object with text/content
                $out = static::extractText($response['output']);
                if ($out !== '') {
                    return $out;
                }
            }

            if (array_key_exists('response', $response)) {
                return static::extractText($response['response']);
            }

            if (array_key_exists('steps', $response)) {
                return static::extractText($response['steps']);
            }

            // OpenAI-like shape: choices => [ [message => [content => ...]] ]
            if (array_key_exists('choices', $response) && is_iterable($response['choices'])) {
                foreach ($response['choices'] as $choice) {
                    $text = static::tryExtractFromCandidate($choice);
                    if ($text !== null && $text !== '') {
                        return $text;
                    }
                    if (is_array($choice) && array_key_exists('message', $choice)) {
                        $text = static::extractText($choice['message']);
                        if ($text !== '') {
                            return $text;
                        }
                    }
                    if (is_array($choice) && array_key_exists('delta', $choice)) {
                        $text = static::extractText($choice['delta']);
                        if ($text !== '') {
                            return $text;
                        }
                    }
                }
            }

            // Prism-like additional content array with text
            if (array_key_exists('additionalContent', $response) && is_iterable($response['additionalContent'])) {
                foreach ($response['additionalContent'] as $item) {
                    $text = static::tryExtractFromCandidate($item);
                    if ($text !== null && $text !== '') {
                        return $text;
                    }
                }
            }

            foreach ($response as $value) {
                $text = static::tryExtractFromCandidate($value);
                if ($text !== null) {
                    return $text;
                }
            }
        }

        if (is_object($response)) {
            if (property_exists($response, 'text')) {
                return trim((string) $response->text);
            }

            if (property_exists($response, 'content') && is_string($response->content)) {
                return trim((string) $response->content);
            }

            if (property_exists($response, 'output')) {
                $out = static::extractText($response->output);
                if ($out !== '') {
                    return $out;
                }
            }

            if (property_exists($response, 'response')) {
                return static::extractText($response->response);
            }

            if (property_exists($response, 'steps')) {
                return static::extractText($response->steps);
            }

            if (property_exists($response, 'choices')) {
                /** @var mixed $choices */
                $choices = $response->choices;
                if (is_iterable($choices)) {
                    foreach ($choices as $choice) {
                        $text = static::tryExtractFromCandidate($choice);
                        if ($text !== null && $text !== '') {
                            return $text;
                        }
                    }
                }
            }

            if (method_exists($response, 'toArray')) {
                return static::extractText($response->toArray());
            }
        }

        if ($response instanceof \Traversable) {
            return static::extractText(iterator_to_array($response));
        }

        // Return empty string to allow higher-level logic to attempt JSON salvage from the full structure.
        return '';
    }

    protected static function tryExtractFromCandidate(mixed $candidate): ?string
    {
        if ($candidate instanceof Collection) {
            return static::extractText($candidate->all());
        }

        if (is_array($candidate) || $candidate instanceof \Traversable) {
            if ($candidate instanceof \Traversable) {
                $candidate = iterator_to_array($candidate);
            }

            if (array_key_exists('text', $candidate)) {
                return trim((string) $candidate['text']);
            }

            if (array_key_exists('content', $candidate) && is_string($candidate['content'])) {
                return trim((string) $candidate['content']);
            }

            if (array_key_exists('message', $candidate)) {
                $text = static::extractText($candidate['message']);
                if ($text !== '') {
                    return $text;
                }
            }

            if (array_key_exists('output', $candidate)) {
                $text = static::extractText($candidate['output']);
                if ($text !== '') {
                    return $text;
                }
            }

            foreach ($candidate as $value) {
                $text = static::tryExtractFromCandidate($value);
                if ($text !== null) {
                    return $text;
                }
            }
        }

        if (is_object($candidate) && property_exists($candidate, 'text')) {
            return trim((string) $candidate->text);
        }

        if (is_object($candidate) && property_exists($candidate, 'content') && is_string($candidate->content)) {
            return trim((string) $candidate->content);
        }

        return null;
    }

    /**
     * Recursively search the response for any string that looks like JSON and validate it.
     * This also strips common code fences and extracts the outermost JSON object if surrounded by prose.
     */
    protected static function searchForJsonString(mixed $response): ?string
    {
        // Helper to normalize a candidate string and validate as JSON object
        $try = static function (string $s): ?string {
            $s = trim($s);
            if ($s === '') {
                return null;
            }

            // Strip markdown code fences if present
            if (str_starts_with($s, '```')) {
                // remove first fence line and possible language hint
                $s = preg_replace('/^```[a-zA-Z0-9]*\n?/m', '', $s, 1) ?? $s;
                // remove trailing fence
                $s = preg_replace('/\n?```\s*$/m', '', $s, 1) ?? $s;
                $s = trim($s);
            }

            // Extract substring from first '{' to last '}' to isolate JSON object
            $first = strpos($s, '{');
            $last = strrpos($s, '}');
            if ($first !== false && $last !== false && $last > $first) {
                $candidate = substr($s, $first, $last - $first + 1);
                try {
                    json_decode($candidate, true, 512, JSON_THROW_ON_ERROR);

                    return trim($candidate);
                } catch (JsonException) {
                    // fallthrough and try full string below
                }
            }

            // Try entire string as-is
            try {
                json_decode($s, true, 512, JSON_THROW_ON_ERROR);

                return $s;
            } catch (JsonException) {
                return null;
            }
        };

        if (is_string($response)) {
            return $try($response);
        }

        if ($response instanceof Collection) {
            return static::searchForJsonString($response->all());
        }

        if (is_array($response)) {
            foreach ($response as $key => $value) {
                if (is_string($value)) {
                    $found = $try($value);
                    if ($found !== null) {
                        return $found;
                    }
                } else {
                    $found = static::searchForJsonString($value);
                    if ($found !== null) {
                        return $found;
                    }
                }
            }

            return null;
        }

        if (is_object($response)) {
            // Try common text-bearing properties first
            foreach (['text', 'content', 'output', 'response', 'message'] as $prop) {
                if (property_exists($response, $prop)) {
                    $found = static::searchForJsonString($response->{$prop});
                    if ($found !== null) {
                        return $found;
                    }
                }
            }

            if (method_exists($response, 'toArray')) {
                return static::searchForJsonString($response->toArray());
            }

            if ($response instanceof \Traversable) {
                return static::searchForJsonString(iterator_to_array($response));
            }
        }

        if ($response instanceof \Traversable) {
            return static::searchForJsonString(iterator_to_array($response));
        }

        return null;
    }
}
