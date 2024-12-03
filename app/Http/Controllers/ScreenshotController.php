<?php

namespace App\Http\Controllers;

use App\Http\Requests\CaptureRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ScreenshotController extends Controller
{
    /**
     * Capture a screenshot of the specified URL
     */
    public function capture(CaptureRequest $request)
    {
        try {
            // Make request to Cloud Run with stream option
            $response = Http::withToken(config('pixashot.auth_token'))
                ->withOptions(['stream' => true])
                ->post(config('pixashot.endpoint') . '/capture', $request->validated());

            if ($response->status() !== 200) {
                // For error responses, we need to get the full body to read the error message
                $body = $response->body();
                $errorMessage = json_decode($body, true)['message'] ?? 'Unknown error occurred';
                return response()->json(['error' => $errorMessage], $response->status());
            }

            // Get the format for the content type
            $format = $request->input('format', 'png');

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
}
