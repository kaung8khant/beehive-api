<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    /**
     * @OA\Get(
     *      path="/api/v2/admin/settings",
     *      operationId="getSettingLists",
     *      tags={"Settings"},
     *      summary="Get list of settings",
     *      description="Returns list of settings",
     *      @OA\Parameter(
     *          name="page",
     *          description="Current Page",
     *          required=false,
     *          in="query",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation"
     *      ),
     *      security={
     *          {"bearerAuth": {}}
     *      }
     *)
     */
    public function index()
    {
        return Setting::all();
    }

    /**
     * @OA\Get(
     *      path="/api/v2/{path}/settings/{key}",
     *      operationId="showSetting",
     *      tags={"Settings"},
     *      summary="Get One Setting",
     *      description="Returns a requested setting",
     *      @OA\Parameter(
     *          name="key",
     *          description="Key of a requested setting",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="string"
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation"
     *      ),
     *      security={
     *          {"bearerAuth": {}}
     *      }
     *)
     */
    public function show($groupName)
    {
        return Setting::where('group_name', $groupName)->firstOrFail();
    }

    /**
     * @OA\Put(
     *      path="/api/v2/admin/settings/update",
     *      operationId="updateSetting",
     *      tags={"Settings"},
     *      summary="Update a Setting",
     *      description="Update a requested setting",
     *      @OA\RequestBody(
     *          required=true,
     *          description="New setting data to be updated.",
     *          @OA\MediaType(
     *              mediaType="applications/json",
     *              @OA\Schema(ref="#/components/schemas/Setting")
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation"
     *      ),
     *      security={
     *          {"bearerAuth": {}}
     *      }
     *)
     */
    public function updateSetting(Request $request)
    {
        $validatedData = $request->validate([
            '*.key' => 'required|exists:App\Models\Setting,key',
            '*.value' => 'required|string',
            '*.data_type' => 'required|in:string,integer,decimal',
        ]);

        foreach ($validatedData as $data) {
            Setting::where('key', $data['key'])->update([
                'key' => $data['key'],
                'value' => $data['value'],
                'data_type' => $data['data_type'],
            ]);
        }

        return response()->json('Successfully updated.', 200);
    }
}
