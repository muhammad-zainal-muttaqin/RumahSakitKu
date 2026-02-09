<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Base controller for API responses.
 * 
 * Provides standardized response methods for all API controllers.
 */
abstract class BaseController extends Controller
{
    /**
     * Return a success response.
     *
     * @param mixed $data Response data
     * @param string|null $message Success message
     * @param int $code HTTP status code
     * @return JsonResponse
     */
    protected function successResponse(mixed $data = null, ?string $message = null, int $code = 200): JsonResponse
    {
        $response = [
            'success' => true,
            'code' => $code,
        ];

        if ($message !== null) {
            $response['message'] = $message;
        }

        if ($data !== null) {
            $response['data'] = $data;
        }

        return response()->json($response, $code);
    }

    /**
     * Return an error response.
     *
     * @param string $message Error message
     * @param int $code HTTP status code
     * @param array|null $errors Detailed errors
     * @return JsonResponse
     */
    protected function errorResponse(string $message, int $code = 400, ?array $errors = null): JsonResponse
    {
        $response = [
            'success' => false,
            'code' => $code,
            'message' => $message,
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $code);
    }

    /**
     * Return a paginated response.
     *
     * @param LengthAwarePaginator $data Paginated data
     * @param string|null $message Success message
     * @return JsonResponse
     */
    protected function paginateResponse(LengthAwarePaginator $data, ?string $message = null): JsonResponse
    {
        $response = [
            'success' => true,
            'code' => 200,
            'data' => $data->items(),
            'pagination' => [
                'current_page' => $data->currentPage(),
                'last_page' => $data->lastPage(),
                'per_page' => $data->perPage(),
                'total' => $data->total(),
                'from' => $data->firstItem(),
                'to' => $data->lastItem(),
                'has_more_pages' => $data->hasMorePages(),
            ],
        ];

        if ($message !== null) {
            $response['message'] = $message;
        }

        return response()->json($response);
    }

    /**
     * Return a created response (201).
     *
     * @param mixed $data Response data
     * @param string|null $message Success message
     * @return JsonResponse
     */
    protected function createdResponse(mixed $data = null, ?string $message = 'Resource created successfully'): JsonResponse
    {
        return $this->successResponse($data, $message, 201);
    }

    /**
     * Return a no content response (204).
     *
     * @return JsonResponse
     */
    protected function noContentResponse(): JsonResponse
    {
        return response()->json(null, 204);
    }

    /**
     * Return a validation error response (422).
     *
     * @param array $errors Validation errors
     * @param string $message Error message
     * @return JsonResponse
     */
    protected function validationErrorResponse(array $errors, string $message = 'Validation failed'): JsonResponse
    {
        return $this->errorResponse($message, 422, $errors);
    }

    /**
     * Return an unauthorized response (401).
     *
     * @param string $message Error message
     * @return JsonResponse
     */
    protected function unauthorizedResponse(string $message = 'Unauthorized'): JsonResponse
    {
        return $this->errorResponse($message, 401);
    }

    /**
     * Return a forbidden response (403).
     *
     * @param string $message Error message
     * @return JsonResponse
     */
    protected function forbiddenResponse(string $message = 'Forbidden'): JsonResponse
    {
        return $this->errorResponse($message, 403);
    }

    /**
     * Return a not found response (404).
     *
     * @param string $message Error message
     * @return JsonResponse
     */
    protected function notFoundResponse(string $message = 'Resource not found'): JsonResponse
    {
        return $this->errorResponse($message, 404);
    }
}
