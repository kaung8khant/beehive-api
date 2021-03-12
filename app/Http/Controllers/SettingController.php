<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Setting;

class SettingController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
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
     * Display the specified resource.
     *
     * @param  \App\Models\Setting  $setting
     * @return \Illuminate\Http\Response
     */

     /**
     * @OA\Get(
     *      path="/api/v2/admin/settings/{key}",
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
    public function show($key)
    {
        return Setting::where('key', $key)->firstOrFail();
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
/**
     * @OA\Put(
     *      path="/api/v2/admin/settings",
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
