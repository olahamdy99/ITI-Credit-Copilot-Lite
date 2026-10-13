<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LoanApplication;
use App\Services\Underwriting\UnderwritingAssessmentService;
use Illuminate\Http\JsonResponse;
use OpenApi\Annotations as OA;

class AssessmentController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/applications/{application}/assess",
     *     tags={"Assessments"},
     *     summary="Run underwriting assessment",
     *     description="Runs the underwriting assessment for a loan application and returns the calculated decision, rule results, and policy citations.",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(
     *         name="application",
     *         in="path",
     *         required=true,
     *         description="Loan application ID",
     *
     *         @OA\Schema(
     *             type="integer",
     *             example=7
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Assessment completed successfully"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="User is not authorized to run the assessment"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Loan application not found"
     *     )
     * )
     */
    public function assess(
        LoanApplication $application,
        UnderwritingAssessmentService $service
    ): JsonResponse {
        $assessment = $service->assess($application);

        return response()->json([
            'message' => 'Assessment completed successfully.',
            'data' => $assessment,
        ], 201);
    }
}
