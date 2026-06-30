<?php

namespace App\Http\Controllers;

use App\Models\PdfSummary;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Smalot\PdfParser\Parser;
use Symfony\Component\HttpFoundation\Response;

class PdfController extends Controller
{
    public function summarize(Request $request): Response
    {
        $request->validate([
            'pdf' => ['required', 'file', 'mimetypes:application/pdf', 'max:20480'],
            'summary_type' => ['nullable', 'string'],
        ]);

        $user = Auth::user();

        if (!$user?->canSummarizePdf()) {
            return response()->json([
                'message' => 'You have reached the PDFs usage limit for current month.'
            ]);
        }

        try {
            $file = $request->file('pdf');
            $path = $file->storeAs('pdfs', $file->hashName());

            $parser = new Parser();

            $pdf = $parser->parseFile(Storage::path($path));

            $text = trim($pdf->getText());

            if (empty($text)) {
                Storage::delete($path);
                return response()->json([
                    'message' => 'Unable to extract text from PDF file.'
                ]);
            }

            // check API key
            $apiKey = config('services.openrouter.key');
            if (empty($apiKey)) {
                Log::error('OpenRouter API key is not set.');
                Storage::delete($path);
                return response()->json([
                    'message' => 'Server error. Please try again later.'
                ], 500);
            }

            // get summary type from request
            $summaryType = $request->input(['summary_type'], 'standard');

            //define the prompts for different summary types
            $prompts = config('prompt');

            $systemPrompt = $prompts['system'];
            $userPrompt = $prompts[$summaryType];

            $payload = [
                'models' =>
                    [
                        "google/gemma-4-31b-it",
                        "openai/gpt-oss-20b"
                    ],
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => $systemPrompt,
                    ],
                    [
                        'role' => 'user',
                        'content' => $userPrompt,
                    ],
                    [
                        'role' => 'user',
                        'content' => $text,
                    ]
                ]
            ];

            $response = Http::timeout(60)->withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'HTTP-Reffer' => config('app.url'),
                'X-OpenRouter-Title' => config('app.name', 'PDF Summarizer'),
                'content-type' => 'application/json',
            ])->post('https://openrouter.ai/api/v1/chat/completions', $payload);

            if ($response->failed()) {
                $errorBody = $response->body();
                $statusCode = $response->status();

                Log::error('OpenRouter API Error,', [
                    'errorBody' => $errorBody,
                    'status' => $statusCode,
                ]);

                Storage::delete($path);

                $error = is_array($response->json())
                    ? $response->json()['error']['message']
                    : 'Failed to generate summary. Please try again later.';

                return response()->json([
                    'message' => $error,
                ], $statusCode);
            }

            $data = response()->json([]);

            if (!isset($data['choices'][0]['message']['content'])) {
                Log::error('Unexpected API response structure.', [
                    'data' => $data
                ]);

                Storage::delete($path);

                return response()->json([
                    'message' => 'Unexpected API response structure. Please try again later.',
                ], 500);
            }

            $summaryPayload = $data['choices'][0]['message']['content'];


            //save to DB
            $pdfSummary = PdfSummary::query()->updateOrCreate([
                'user_id' => $user->id,
                'filename' => $file->hashName(),
                'summary' => $summaryPayload,
                'file_size' => $file->getSize(),
            ]);

            //increase pdf_count for user
            $user->increasePdfCount();

            return response()->json([
                'summary' => $summaryPayload,
                'id' => $pdfSummary->id

            ]);

        } catch (\Throwable $th) {
            Log::error('PDF Summarizer error.' . $th->getMessage(), [
                'trace' => $th->getTrace(),
                'file' => $th->getFile(),
                'line' => $th->getLine(),
                'message' => $th->getMessage(),
            ]);

            if (isset($path)) {
                Storage::delete($path);
            }

            return response()->json([
                'message' => 'An error occurred. Please try again later.',
            ]);
        }
    }
}
