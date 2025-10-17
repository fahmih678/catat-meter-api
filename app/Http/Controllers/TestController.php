<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class TestController extends Controller
{
    /**
     * Test basic response
     */
    public function basicTest()
    {
        return response()->json([
            'status' => 'success',
            'message' => 'Basic test response',
            'timestamp' => now()->toISOString(),
            'server_info' => [
                'php_version' => PHP_VERSION,
                'memory_limit' => ini_get('memory_limit'),
                'max_execution_time' => ini_get('max_execution_time'),
                'output_buffering' => ini_get('output_buffering'),
            ]
        ]);
    }

    /**
     * Test large response
     */
    public function largeTest()
    {
        $data = [];
        for ($i = 0; $i < 1000; $i++) {
            $data[] = [
                'id' => $i,
                'name' => 'Test Item ' . $i,
                'description' => str_repeat('This is a test description for item ' . $i . '. ', 10),
                'created_at' => now()->subDays(rand(1, 365))->toISOString(),
                'metadata' => [
                    'category' => 'category_' . ($i % 10),
                    'status' => $i % 2 === 0 ? 'active' : 'inactive',
                    'priority' => rand(1, 5),
                    'tags' => array_map(fn($n) => 'tag_' . $n, range(1, rand(3, 8)))
                ]
            ];
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Large response test',
            'total_items' => count($data),
            'data_size_estimate' => strlen(json_encode($data)) . ' bytes',
            'data' => $data
        ]);
    }

    /**
     * Test chunked response
     */
    public function chunkedTest()
    {
        return response()->stream(function () {
            echo json_encode(['status' => 'success', 'message' => 'Starting chunked response']) . "\n";
            flush();

            for ($i = 1; $i <= 10; $i++) {
                sleep(1);
                echo json_encode(['chunk' => $i, 'data' => 'Chunk data ' . $i]) . "\n";
                flush();
            }

            echo json_encode(['status' => 'completed', 'message' => 'Chunked response finished']) . "\n";
        }, 200, [
            'Content-Type' => 'application/json',
            'Transfer-Encoding' => 'chunked',
            'Connection' => 'close'
        ]);
    }
}
