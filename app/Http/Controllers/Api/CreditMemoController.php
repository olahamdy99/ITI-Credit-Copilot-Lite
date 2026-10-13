<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Services\CreditMemo\CreditMemoService;
use Illuminate\Http\JsonResponse;
use OpenApi\Annotations as OA;

class CreditMemoController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/assessments/{assessment}/memo",
     *     tags={"Credit Memos"},
     *     summary="Generate grounded credit memo",
     *     description="Generates a grounded credit memo from the assessment, rule results, and policy citations.",
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
     *         response=201,
     *         description="Credit memo generated successfully"
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
     *         response=500,
     *         description="Credit memo generation failed"
     *     )
     * )
     */
    public function generate(
        Assessment $assessment,
        CreditMemoService $service
    ): JsonResponse {
        $memo = $service->generate($assessment);

        return response()->json([
            'message' => 'Credit memo generated successfully.',
            'data' => $memo,
        ], 201);
    }
}
