<?php

namespace App\Helpers;

use Illuminate\Pagination\LengthAwarePaginator;

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
    public static function success($data = null, string $message = 'Success', int $status = 200)
    {
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
