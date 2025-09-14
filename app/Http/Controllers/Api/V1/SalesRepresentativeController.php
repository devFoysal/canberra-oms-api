<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{
    User,
    SalesRepresentative
};
use App\Helpers\{
    FileHelper,
    ResponseHelper
};
use App\Http\Resources\Api\V1\SalesRepresentative\{
    SalesRepresentativeCollectionResource,
    SalesRepresentativeResource,
};
use App\Http\Requests\Api\V1\SalesRepresentative\{
    CreateSalesRepresentativeRequest,
    EditSalesRepresentativeRequest,
};

use DB;

class SalesRepresentativeController extends Controller
{

    public function index()
    {
        $salesRepresentatives = User::with('salesRepresentative')->orderBy('id', 'desc')->get();
        return ResponseHelper::success(SalesRepresentativeCollectionResource::collection($salesRepresentatives), 'Categories retrieved successfully');
    }

    public function store(CreateSalesRepresentativeRequest $request)
    {

        DB::beginTransaction();

        try {
            $userData = [
                'full_name' => $request->fullName,
                'email' => $request->email,
                'mobile_number' => $request->mobileNumber,
                'password' => bcrypt($request->mobileNumber ?? 'password'),
            ];

            $user = User::create($userData);

            $salesRepresentative = null;

            $salesRepresentativeData = [
                'employee_code' => generate_employee_code(),
                'territory' => $request->territory
            ];

            $salesRepresentative = $user->salesRepresentative()->create($salesRepresentativeData);

            DB::commit();

            $user->load('salesRepresentative');

            // Check if sales representative was created
            return ResponseHelper::success(new SalesRepresentativeResource($user), 'Sales Representative created successfully', 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseHelper::error($e->getMessage(), 500);
        }
    }

    public function show($id)
    {
        $salesRepresentative = User::where('id', $id)->with('salesRepresentative')->first();

        if (!$salesRepresentative) return ResponseHelper::error('Sales representative not found', 404);

        return ResponseHelper::success(new SalesRepresentativeResource($salesRepresentative), 'Sales representative retrieved successfully');
    }

    public function update(EditSalesRepresentativeRequest $request, $id)
    {

        $user = User::find($id);

        if (!$user) return ResponseHelper::error('Sales Representative not found', 404);


        DB::beginTransaction();

        try {
            // Update user basic info
            $user->update([
                'full_name'     => $request->fullName,
                'email'         => $request->email,
                'mobile_number' => $request->mobileNumber,
            ]);

            // Always ensure sales representative exists
            $salesRepresentative = $user->salesRepresentative()->updateOrCreate(
                ['user_id' => $id],
                [
                    'employee_code' => $user->salesRepresentative->employee_code ?? generate_employee_code(),
                    'territory'     => $request->territory
                ]
            );

            DB::commit();

            $user->load('salesRepresentative');

            return ResponseHelper::success(
                new SalesRepresentativeResource($user),
                'Sales Representative updated successfully',
                201
            );

        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseHelper::error($e->getMessage(), 500);
        }
    }

    public function destroy($id)
    {
        $user = User::find($id);
        if (!$user) return ResponseHelper::error('Sales Representative not found', 404);

        $user->status = "banned";
        $user->update();
        return ResponseHelper::success('SalesRepresentative deleted successfully');
    }
}
