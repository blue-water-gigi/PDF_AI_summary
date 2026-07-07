<?php

namespace App\Http\Controllers;

use App\Events\LimitReached;
use App\Exceptions\Summarizer\PdfSummarizerException;
use App\Exceptions\Summarizer\UsageAvailabilityException;
use App\Http\Requests\Summarization\SummarizePdfRequest;
use App\Repositories\PdfSummaryRepository;
use App\Services\Summarization\PdfSummarizerService;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response;

class PdfController extends Controller
{
    public function __construct(
        private readonly PdfSummarizerService $summarizer,
        private readonly PdfSummaryRepository $summaryRepository,
    )
    {
    }

    /**
     * @throws PdfSummarizerException
     * @throws UsageAvailabilityException
     */
    public function summarize(SummarizePdfRequest $request): Response
    {
        $user = Auth::user();

        $result = $this->summarizer->summarize(
            $user,
            $request->file('pdf'),
            $request->input('summary_type', 'standard')
        );

        if ($user->isLimitReached()) {
            LimitReached::dispatch($user, $user->subscription);
        }

        return response()->json(
            $result->toArray(),
            Response::HTTP_OK
        );
    }

    public function index(): InertiaResponse
    {
        $user = Auth::user();

        $summaries = $this->summaryRepository->findAllByUserId($user->id);

        return Inertia::render('dashboard/history', [
            'summaries' => $summaries,
        ]);
    }
}
