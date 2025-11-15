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
            throw new RuntimeException('Empty text payload returned from LLM response.');
        }

        try {
            return json_decode($text, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
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

            if (array_key_exists('response', $response)) {
                return static::extractText($response['response']);
            }

            if (array_key_exists('steps', $response)) {
                return static::extractText($response['steps']);
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

            if (property_exists($response, 'response')) {
                return static::extractText($response->response);
            }

            if (property_exists($response, 'steps')) {
                return static::extractText($response->steps);
            }

            if (method_exists($response, 'toArray')) {
                return static::extractText($response->toArray());
            }
        }

        if ($response instanceof \Traversable) {
            return static::extractText(iterator_to_array($response));
        }

        throw new RuntimeException('Unable to locate text field in LLM response.');
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

        return null;
    }
}
