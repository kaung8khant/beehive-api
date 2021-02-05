<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Repositories\CategoryRepositoryInterface;

class CategoryController extends Controller
{
    private $_repository;

    public function __construct(CategoryRepositoryInterface $repository)
    {
        $this->_repository = $repository;
    }

    /**
     * @OA\Get(
     *      path="/api/categories",
     *      operationId="getCategoryList",
     *      tags={"Categories"},
     *      summary="Get list of categories",
     *      description="Returns list of categories",
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation")
     *       ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden"
     *      )
     *     )
     */
    public function index(Request $request)
    {
        return $this->_repository->getAll($request->query('page'), $request->query('per_page'));
    }

    /**
     * create a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        $request->validate([
            'name' => 'required',
        ]);
        $category = $this->_repository->create($request->all());
        return $category;
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Category  $id
     * @return \Illuminate\Http\Response
     */
    public function view($id)
    {
        return $this->_repository->getOne($id);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Category  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $category = $this->_repository->update($id, $request->all());
        return $category;
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Category  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $this->_repository->delete($id);
        return response()->json(null, 204);
    }
}
