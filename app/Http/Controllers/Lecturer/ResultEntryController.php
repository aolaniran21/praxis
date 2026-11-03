<?php

namespace App\Http\Controllers\Lecturer;

use App\Http\Controllers\Controller;
use App\Http\Requests\SaveResultDraftRequest;
use App\Models\CourseRegistration;
use App\Models\Result;
use App\Services\ResultManagementService;
use Illuminate\Http\RedirectResponse;

class ResultEntryController extends Controller
{
    public function store(
        SaveResultDraftRequest $request,
        CourseRegistration $registration,
        ResultManagementService $results,
    ): RedirectResponse {
        $this->authorize('view', $registration);

        $results->saveDraft(
            $request->user(),
            $registration,
            (int) $request->validated('ca_score'),
            (int) $request->validated('exam_score'),
        );

        return back()->with('status', 'Result draft saved.');
    }

    public function submit(Result $result, ResultManagementService $results): RedirectResponse
    {
        $this->authorize('submit', $result);
        $results->submit(request()->user(), $result);

        return back()->with('status', 'Result submitted for departmental review.');
    }
}
