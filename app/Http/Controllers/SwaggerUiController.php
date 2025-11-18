<?php

namespace App\Http\Controllers;

class SwaggerUiController extends Controller
{
    /**
     * Display Swagger UI using our custom API docs endpoint
     */
    public function index()
    {
        $apiDocsUrl = url('/api/docs');

        return view('swagger-ui', compact('apiDocsUrl'));
    }
}