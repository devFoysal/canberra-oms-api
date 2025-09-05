<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Customer;
use App\Helpers\{
    FileHelper,
    ResponseHelper
};
use App\Http\Resources\Api\V1\Customer\{
   CustomerCollectionResource,
   CustomerResource,
};
use App\Http\Requests\Api\V1\Customer\{
    CreateCustomerRequest,
    EditCustomerRequest,
};

class CustomerController extends Controller
{

    public function index()
    {
        $customers = Customer::with('user:id,full_name,email,mobile_number')->with('user.salesRepresentative:id,user_id,territory')->withCount('orders')
            ->withMax('orders', 'created_at')
            ->orderBy('id', 'desc')
            ->get();
        return ResponseHelper::success(CustomerCollectionResource::collection($customers), 'Customers retrieved successfully');
    }

    public function store(CreateCustomerRequest $request)
    {
        try {
            // Handle cover image upload (new customer)
            // if ($request->hasFile('avatar')) {
            //     $customerData['avatar'] = FileHelper::uploadImages(
            //         $request->file('avatar'),
            //         'customers',
            //         ['optimize' => true]
            //     );
            // }

            // Merge remaining fields
            $customerData =  [
                'name' => $request->name,
                'mobile_number' => $request->mobile,
                'shop_name' => $request->shopName,
                'address' => $request->address,
            ];

            if((int) $request->assignSalesPerson > 0){
                $customerData['created_by_id'] = $request->assignSalesPerson;
            }else{
                $customerData['created_by_id'] = auth()->id();
            }

            $customer = Customer::create($customerData);

            return ResponseHelper::success(new CustomerResource($customer), 'Customer created successfully', 201);

        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage(), 500);
        }
    }

    public function show($id)
    {
        $customer = Customer::find($id);
        if (!$customer) return ResponseHelper::error('Customer not found', 404);
        return ResponseHelper::success(new CustomerResource($customer), 'Customer retrieved successfully');
    }

    public function update(EditCustomerRequest $request, $id)
    {
        try {
            $customer = Customer::find($id);
            if (!$customer) return ResponseHelper::error('Customer not found', 404);

            // Update thumbnail if provided (use updateImage)

            // Update cover image if provided (use updateImage)
            // if ($request->hasFile('avatar')) {
            //     $customerData['avatar'] = FileHelper::updateImage(
            //         $request->file('avatar'),
            //         $customer->avatar,
            //         'customers',
            //         ['optimize' => true]
            //     );
            // }

            // Merge remaining fields
            $customerData =  [
                'name' => $request->name,
                'mobile_number' => $request->mobile,
                'shop_name' => $request->shopName,
                'address' => $request->address,
            ];

            if((int) $request->assignSalesPerson > 0){
                $customerData['created_by_id'] = $request->assignSalesPerson;
            }else{
                $customerData['created_by_id'] = auth()->id();
            }

            $customer->update($customerData);

            return ResponseHelper::success(new CustomerResource($customer), 'Customer updated successfully');

        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage(), 500);
        }
    }

    public function destroy($id)
    {
        $customer = Customer::find($id);
        if (!$customer) return ResponseHelper::error('Customer not found', 404);

        $customer->delete();
        return ResponseHelper::success('Customer deleted successfully');
    }
}
