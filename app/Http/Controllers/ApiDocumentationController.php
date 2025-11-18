<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class ApiDocumentationController extends Controller
{
    /**
     * Serve the API documentation JSON
     */
    public function apiDocs()
    {
        $path = storage_path('api-docs/api-docs.json');

        if (!File::exists($path)) {
            return response()->json([
                'error' => 'API documentation not found. Please generate it first.',
                'message' => 'Run: php artisan l5-swagger:generate'
            ], 404);
        }

        $content = File::get($path);
        $data = json_decode($content, true);

        // Update server URL to match current request
        if (isset($data['servers']) && !empty($data['servers'])) {
            $data['servers'][0]['url'] = config('app.url');
        }

        // Update documentation URL to our custom endpoint
        $data['servers'][] = [
            'url' => config('app.url'),
            'description' => 'API Documentation Server'
        ];

        // Ensure security schemes are properly set
        if (!isset($data['components']['securitySchemes'])) {
            $data['components']['securitySchemes'] = [
                'sanctum' => [
                    'type' => 'apiKey',
                    'description' => 'Enter token in format (Bearer <token>)',
                    'name' => 'Authorization',
                    'in' => 'header'
                ]
            ];
        }

        return response()->json($data);
    }
}