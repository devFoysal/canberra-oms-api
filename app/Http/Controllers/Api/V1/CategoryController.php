<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Category;
use App\Helpers\{
    FileHelper,
    ResponseHelper
};
use App\Http\Resources\Api\V1\Category\{
    CategoryCollectionResource,
    CategoryResource,
};
use App\Http\Requests\Api\V1\Category\{
    CreateCategoryRequest,
    EditCategoryRequest,
};

/**
 * @OA\Schema(
 *     schema="Category",
 *     type="object",
 *     title="Category",
 *     required={"id","name","price"},
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="Sample Category"),
 *     @OA\Property(property="price", type="number", format="float", example=199.99),
 *     @OA\Property(property="description", type="string", example="This is a sample product."),
 *     @OA\Property(property="image_url", type="string", format="url", nullable=true, example="https://example.com/image.jpg"),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2025-09-02T05:14:07.000000Z")
 * )
 */
class CategoryController extends Controller
{
    /**
     * List all categories
     *
     * @OA\Get(
     *     path="/categories",
     *     summary="Get list of categories",
     *     tags={"Category"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of categories",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Categories retrieved successfully"),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Category"))
     *         )
     *     )
     * )
     */
    public function index()
    {
        $categories = Category::orderBy('id', 'desc')->get();
        return ResponseHelper::success(CategoryCollectionResource::collection($categories), 'Categories retrieved successfully');
    }

    /**
     * Create a new product
     *
     * @OA\Post(
     *     path="/categories",
     *     summary="Create a product",
     *     tags={"Category"},
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"name","price"},
     *                 @OA\Property(property="name", type="string", example="Sample Category"),
     *                 @OA\Property(property="price", type="number", example=199.99),
     *                 @OA\Property(property="description", type="string", example="Category description"),
     *                 @OA\Property(property="image", type="string", format="binary", nullable=true)
     *             )
     *         )
     *     ),
     *     @OA\Response(response=201, description="Category created successfully")
     * )
     */
    public function store(CreateCategoryRequest $request)
    {
        try {
            $categoryData = $request->validated();

            // Merge remaining fields
            $categoryData =  [
                'name' => $request->name,
                'category_id' => $request->categoryId,
                'status' => $request->status,
            ];

            $category = Category::create($categoryData);

            return ResponseHelper::success(new CategoryResource($category), 'Category created successfully', 201);

        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage(), 500);
        }
    }

    /**
     * Get a single product
     *
     * @OA\Get(
     *     path="/categories/{id}",
     *     summary="Get a product by ID",
     *     tags={"Category"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Category retrieved successfully"),
     *     @OA\Response(response=404, description="Category not found")
     * )
     */
    public function show($id)
    {
        $category = Category::find($id);
        if (!$category) return ResponseHelper::error('Category not found', 404);
        return ResponseHelper::success(new CategoryResource($category), 'Category retrieved successfully');
    }

    /**
     * Update a product
     *
     * @OA\Patch(
     *     path="/categories/{id}",
     *     summary="Update a product",
     *     tags={"Category"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(property="name", type="string"),
     *                 @OA\Property(property="price", type="number"),
     *                 @OA\Property(property="description", type="string"),
     *                 @OA\Property(property="image", type="string", format="binary")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=200, description="Category updated successfully"),
     *     @OA\Response(response=404, description="Category not found")
     * )
     */
    public function update(EditCategoryRequest $request, $id)
    {
        $category = Category::find($id);

        if (!$category) return ResponseHelper::error('Category not found', 404);


        try {
            $categoryData = $request->validated();

            // Merge remaining fields
            $categoryData =  [
                'name' => $request->name,
                'category_id' => $request->categoryId,
                'status' => $request->status,
            ];

            $category = $category->update($categoryData);

            return ResponseHelper::success(new CategoryResource($category), 'Category updated successfully', 201);

        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage(), 500);
        }
    }

    /**
     * Delete a product
     *
     * @OA\Delete(
     *     path="/categories/{id}",
     *     summary="Delete a product",
     *     tags={"Category"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Category deleted successfully"),
     *     @OA\Response(response=404, description="Category not found")
     * )
     */
    public function destroy($id)
    {
        $category = Category::find($id);
        if (!$category) return ResponseHelper::error('Category not found', 404);

        $category->delete();
        return ResponseHelper::success('Category deleted successfully');
    }
}
