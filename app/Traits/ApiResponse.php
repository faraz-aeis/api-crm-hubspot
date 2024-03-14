<?php

/**
 * To API http response
 *
 * @category Trait
 * @author   Anoop Singh <asingh@aeis.com>
 * Date: 06-02-2024
 */

namespace App\Traits;

use Illuminate\Http\Response;

trait ApiResponse
{
    public function respondInternalError($message = 'Internal Error')
    {
        return response()->json(['status' => Response::HTTP_INTERNAL_SERVER_ERROR, 'message' => $message], 500);
    }

    public function respondNotFound($message = 'Not Found')
    {
        return response()->json(['status' => Response::HTTP_NOT_FOUND, 'message' => $message], 404);
    }

    public function respondSuccess($resouce, $message = 'OK')
    {
        $additionals = [
            'status' => Response::HTTP_OK,
            'message' => $message
        ];

        return $resouce->additional($additionals)
            ->response()
            ->setStatusCode(200);
    }

    public function respondSuccessWithData($data, $message = 'OK')
    {
        return response()->json(['data' => $data, 'status' => Response::HTTP_OK, 'message' => $message], 200);
    }
}
