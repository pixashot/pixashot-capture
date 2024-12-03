<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class CaptureRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Convert string boolean values to actual booleans
        $booleanFields = ['full_page', 'dark_mode'];

        foreach ($booleanFields as $field) {
            if ($this->has($field)) {
                $value = $this->input($field);
                if (is_string($value)) {
                    $this->merge([
                        $field => filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)
                    ]);
                }
            }
        }

        // Normalize URL
        if ($url = $this->input('url')) {
            $this->merge([
                'url' => $this->normalizeUrl($url)
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
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
        ];
    }

    /**
     * Normalize the URL by ensuring it uses HTTPS
     */
    private function normalizeUrl(string $url): string
    {
        $url = trim($url);

        if (!Str::startsWith($url, ['http://', 'https://'])) {
            return 'https://' . $url;
        }

        if (Str::startsWith($url, 'http://')) {
            return 'https://' . Str::substr($url, 7);
        }

        return $url;
    }
}
