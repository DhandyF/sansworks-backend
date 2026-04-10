<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SuccessResponseResource extends JsonResource
{
    /**
     * The data to be wrapped in the response.
     */
    private mixed $data;
    private string $message;
    private int $statusCode;

    /**
     * Create a new resource instance.
     */
    public function __construct($data, string $message = 'Success', int $statusCode = 200)
    {
        parent::__construct($data);
        $this->data = $data;
        $this->message = $message;
        $this->statusCode = $statusCode;
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'success' => true,
            'message' => $this->message,
            'data' => $this->data,
        ];
    }

    /**
     * Customize the response for a request.
     */
    public function toResponse($request)
    {
        return parent::toResponse($request)->setStatusCode($this->statusCode);
    }
}

/**
 * Helper function for consistent success responses.
 *
 * @param mixed $data
 * @param string $message
 * @param int $statusCode
 * @return SuccessResponseResource
 */
function successResponse($data, string $message = 'Success', int $statusCode = 200): SuccessResponseResource
{
    return new SuccessResponseResource($data, $message, $statusCode);
}
