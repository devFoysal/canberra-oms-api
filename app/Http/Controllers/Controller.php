<?php

namespace App\Http\Controllers;

/**
 * @OA\Info(
 *      version="1.0.0",
 *      title="Canberra oms API",
 *      description="Canberra Api description",
 *      @OA\Contact(
 *          email="foysal.km68@gmail.com"
 *      )
 * )
 *
 * @OA\PathItem(path="/api")
 *
 * @OA\Server(
 *      url="/api/v1",
 *      description="API server"
 * )
 */

abstract class Controller
{
     /**
     *
     * @OA\Server(
     *      url=L5_SWAGGER_CONST_HOST,
     *      description="Demo API Server"
     * )

     *
     * @OA\Tag(
     *     name="Waadaa Insure",
     *     description="API Endpoints of Waadaa Insure"
     * )
     */
}
