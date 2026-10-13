<?php

use App\Http\Controllers\Api\ApprovalController;
use App\Http\Controllers\Api\AssessmentController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CreditMemoController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [
    AuthController::class,
    'login',
]);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [
        AuthController::class,
        'me',
    ]);

    Route::post('/logout', [
        AuthController::class,
        'logout',
    ]);

    Route::post(
        '/applications/{application}/assess',
        [
            AssessmentController::class,
            'assess',
        ]
    )->middleware(
        'role:loan_officer,credit_officer'
    );

    Route::post(
        '/assessments/{assessment}/memo',
        [
            CreditMemoController::class,
            'generate',
        ]
    )->middleware(
        'role:loan_officer,credit_officer'
    );

    Route::post(
        '/assessments/{assessment}/approve',
        [
            ApprovalController::class,
            'approve',
        ]
    )->middleware('role:credit_officer');

    Route::post(
        '/assessments/{assessment}/reject',
        [
            ApprovalController::class,
            'reject',
        ]
    )->middleware('role:credit_officer');

    Route::post(
        '/assessments/{assessment}/issue',
        [
            ApprovalController::class,
            'issue',
        ]
    )->middleware('role:credit_officer');
});
