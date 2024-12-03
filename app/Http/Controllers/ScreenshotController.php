<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ScreenshotController extends Controller
{
    /**
     * Capture a screenshot of the specified URL
     */
    public function capture(Request $request)
    {
        try {
            // Normalize URL
            $url = $this->normalizeUrl($request->get('url'));
            $params = array_merge($request->all(), ['url' => $url]);

            // Validate parameters
            $validator = Validator::make($params, [
                'url' => ['required', 'url'],
                'format' => ['sometimes', 'in:png,jpeg,webp'],
                'window_width' => ['sometimes', 'integer', 'min:320', 'max:3840'],
                'window_height' => ['sometimes', 'integer', 'min:320', 'max:3840'],
                'full_page' => ['sometimes', 'boolean'],
                'dark_mode' => ['sometimes', 'boolean'],
                'image_quality' => ['sometimes', 'integer', 'min:1', 'max:100'],
                'pixel_density' => ['sometimes', 'numeric', 'min:1', 'max:3'],
                'wait_for_timeout' => ['sometimes', 'integer', 'min:0', 'max:30000'],
                'wait_for_network' => ['sometimes', 'in:idle,mostly_idle'],
            ]);

            if ($validator->fails()) {
                $errors = collect($validator->errors()->messages())
                    ->map(fn($messages, $field) => "{$field}: {$messages[0]}")
                    ->join(', ');

                return response()->json(['error' => $errors], 400);
            }

            // Make request to Cloud Run with stream option
            $response = Http::withToken(config('pixashot.auth_token'))
                ->withOptions(['stream' => true])
                ->post(config('pixashot.endpoint') . '/capture', $validator->validated());

            if ($response->status() !== 200) {
                // For error responses, we need to get the full body to read the error message
                $body = $response->body();
                $errorMessage = json_decode($body, true)['message'] ?? 'Unknown error occurred';
                return response()->json(['error' => $errorMessage], $response->status());
            }

            // Get the format for the content type
            $format = $params['format'] ?? 'png';

            // Create a streaming response
            return response()->stream(
                function () use ($response) {
                    if ($stream = $response->toPsrResponse()->getBody()) {
                        while (!$stream->eof()) {
                            echo $stream->read(1024);
                        }
                    }
                },
                200,
                [
                    'Content-Type' => "image/{$format}",
                    'Cache-Control' => sprintf(
                        'public, max-age=%d, stale-while-revalidate=%d',
                        config('pixashot.cache.max_age'),
                        config('pixashot.cache.stale_while_revalidate')
                    ),
                    'X-Content-Type-Options' => 'nosniff',
                    'Content-Length' => $response->header('Content-Length'),
                ]
            );

        } catch (\Exception $e) {
            Log::error('Screenshot capture error:', ['error' => $e->getMessage()]);
            return response()->json(
                ['error' => 'An error occurred while processing your request'],
                500
            );
        }
    }

    /**
     * Normalize the URL by ensuring it uses HTTPS
     */
    private function normalizeUrl(string $url): string
    {
        $url = trim($url);

        if (!str_starts_with($url, 'http://') && !str_starts_with($url, 'https://')) {
            return 'https://' . $url;
        }

        if (str_starts_with($url, 'http://')) {
            return 'https://' . substr($url, 7);
        }

        return $url;
    }
}
