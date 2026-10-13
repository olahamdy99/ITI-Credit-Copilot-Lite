<?php

namespace App\Exceptions;

use App\Exceptions\Approval\AuthorityLimitExceededException;
use App\Exceptions\Approval\InvalidAssessmentStatusException;
use App\Exceptions\Approval\UnauthorizedApproverException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
        $this->renderable(function (
            AuthorityLimitExceededException $e
        ): JsonResponse {
            return response()->json([
                'message' => $e->getMessage(),
                'error' => 'AUTHORITY_LIMIT_EXCEEDED',
            ], 403);
        });

        $this->renderable(function (
            UnauthorizedApproverException $e
        ): JsonResponse {
            return response()->json([
                'message' => $e->getMessage(),
                'error' => 'UNAUTHORIZED_APPROVER',
            ], 403);
        });

        $this->renderable(function (
            InvalidAssessmentStatusException $e
        ): JsonResponse {
            return response()->json([
                'message' => $e->getMessage(),
                'error' => 'INVALID_ASSESSMENT_STATUS',
            ], 409);
        });
    }
}
