<?php

namespace App\Utils;

use Symfony\Component\HttpFoundation\JsonResponse;

class ResponseUtils {

    public function errorResponse(string $message, int $statusCode = 400): JsonResponse {
        $response = new JsonResponse(
            array(
                'status' => $statusCode,
                'message' => $message
            )
        );
        $response->setStatusCode($statusCode);

        return $response;
    }

}