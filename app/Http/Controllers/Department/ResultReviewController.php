<?php

namespace App\Http\Controllers\Department;

use App\Enums\ResultStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\RejectResultRequest;
use App\Http\Requests\RequestResultCorrection;
use App\Models\Result;
use App\Services\ResultManagementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ResultReviewController extends Controller
{
    public function index(Request $request): View
    {
        $departmentId = $request->user()->department_id;
        abort_unless($departmentId, 403, 'A department has not been assigned to this account.');

        $results = Result::query()
            ->with([
                'courseRegistration.student.user',
                'courseRegistration.course',
                'courseRegistration.semester.academicSession',
                'auditLogs.actor',
            ])
            ->whereIn('status', [
                ResultStatus::Submitted->value,
                ResultStatus::Approved->value,
                ResultStatus::Published->value,
            ])
            ->whereHas('courseRegistration.course', fn ($query) => $query
                ->where('department_id', $departmentId))
            ->latest('updated_at')
            ->paginate(50)
            ->withQueryString();

        return view('department.results.index', compact('results'));
    }

    public function approve(Result $result, ResultManagementService $results): RedirectResponse
    {
        $this->authorize('review', $result);
        $results->approve(request()->user(), $result);

        return back()->with('status', 'Result approved.');
    }

    public function reject(
        RejectResultRequest $request,
        Result $result,
        ResultManagementService $results,
    ): RedirectResponse {
        $this->authorize('review', $result);
        $results->reject($request->user(), $result, $request->validated('feedback'));

        return back()->with('status', 'Result returned to the lecturer with feedback.');
    }

    public function publish(Result $result, ResultManagementService $results): RedirectResponse
    {
        $this->authorize('publish', $result);
        $results->publish(request()->user(), $result);

        return back()->with('status', 'Approved result published to the student.');
    }

    public function requestCorrection(
        RequestResultCorrection $request,
        Result $result,
        ResultManagementService $results,
    ): RedirectResponse {
        $this->authorize('requestCorrection', $result);
        $results->requestCorrection($request->user(), $result, $request->validated('reason'));

        return back()->with('status', 'Result reopened as a draft. The lecturer must correct and resubmit it for approval.');
    }
}
