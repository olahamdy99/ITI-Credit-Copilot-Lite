<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Services\Approval\ApprovalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Annotations as OA;

class ApprovalController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/assessments/{assessment}/approve",
     *     tags={"Approvals"},
     *     summary="Approve an assessment",
     *     description="Approves a pending assessment. Only a Credit Officer can approve, and the recommended amount must be within the officer authority limit.",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(
     *         name="assessment",
     *         in="path",
     *         required=true,
     *         description="Assessment ID",
     *
     *         @OA\Schema(
     *             type="integer",
     *             example=11
     *         )
     *     ),
     *
     *     @OA\RequestBody(
     *         required=false,
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(
     *                 property="comment",
     *                 type="string",
     *                 example="Approved within authority."
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Assessment approved successfully"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden or authority limit exceeded"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Assessment not found"
     *     ),
     *     @OA\Response(
     *         response=409,
     *         description="Invalid assessment status"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
    public function approve(
        Request $request,
        Assessment $assessment,
        ApprovalService $service
    ): JsonResponse {
        $validated = $request->validate([
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        $approval = $service->approve(
            assessment: $assessment,
            user: $request->user(),
            comment: $validated['comment'] ?? null
        );

        return response()->json([
            'message' => 'Assessment approved successfully.',
            'data' => $approval,
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/assessments/{assessment}/reject",
     *     tags={"Approvals"},
     *     summary="Reject an assessment",
     *     description="Rejects a pending assessment. Only a Credit Officer can perform this action.",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(
     *         name="assessment",
     *         in="path",
     *         required=true,
     *         description="Assessment ID",
     *
     *         @OA\Schema(
     *             type="integer",
     *             example=12
     *         )
     *     ),
     *
     *     @OA\RequestBody(
     *         required=false,
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(
     *                 property="comment",
     *                 type="string",
     *                 example="Rejected after credit review."
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Assessment rejected successfully"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Assessment not found"
     *     ),
     *     @OA\Response(
     *         response=409,
     *         description="Invalid assessment status"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
    public function reject(
        Request $request,
        Assessment $assessment,
        ApprovalService $service
    ): JsonResponse {
        $validated = $request->validate([
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        $approval = $service->reject(
            assessment: $assessment,
            user: $request->user(),
            comment: $validated['comment'] ?? null
        );

        return response()->json([
            'message' => 'Assessment rejected successfully.',
            'data' => $approval,
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/assessments/{assessment}/issue",
     *     tags={"Approvals"},
     *     summary="Issue an approved assessment",
     *     description="Marks an approved assessment as issued. The assessment must already be approved.",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(
     *         name="assessment",
     *         in="path",
     *         required=true,
     *         description="Assessment ID",
     *
     *         @OA\Schema(
     *             type="integer",
     *             example=11
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Assessment issued successfully"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Assessment not found"
     *     ),
     *     @OA\Response(
     *         response=409,
     *         description="Assessment must be approved before it can be issued"
     *     )
     * )
     */
    public function issue(
        Assessment $assessment,
        ApprovalService $service
    ): JsonResponse {
        $issued = $service->issue($assessment);

        return response()->json([
            'message' => 'Assessment issued successfully.',
            'data' => $issued,
        ]);
    }
}
