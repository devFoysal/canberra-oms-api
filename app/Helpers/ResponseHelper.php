<?php

namespace App\Helpers;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ResponseHelper
{
    /**
     * Success response
     *
     * @param mixed $data
     * @param string $message
     * @param int $status
     * @return \Illuminate\Http\JsonResponse
     */
    public static function success($data = null, string $message = 'Success', int $status = 200, array $extra = [])
    {

        // If Laravel Resource Collection or JsonResource → add meta directly
        if ($data instanceof JsonResource || $data instanceof ResourceCollection) {
            return $data->additional([
                'status'  => 'success',
                'message' => $message,
                'extra' => $extra
            ])->response()->setStatusCode($status);
        }

        $response = [
            'status'  => 'success',
            'message' => $message,
        ];

        if ($data instanceof LengthAwarePaginator) {
            $response['data'] = $data->items();
            $response['pagination'] = [
                'total'        => $data->total(),
                'per_page'     => $data->perPage(),
                'current_page' => $data->currentPage(),
                'last_page'    => $data->lastPage(),
            ];
        } else {
            $response['data'] = $data;
        }

        if (!empty($extra)) {
            $response = array_merge($response, $extra);
        }

        return response()->json($response, $status);
    }

    /**
     * Error response
     *
     * @param string $message
     * @param int $status
     * @param mixed $errors
     * @return \Illuminate\Http\JsonResponse
     */
    public static function error(string $message = 'Something went wrong', int $status = 400, $errors = null)
    {
        $response = [
            'status'  => 'error',
            'message' => $message,
        ];

        if ($errors) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $status);
    }
}
