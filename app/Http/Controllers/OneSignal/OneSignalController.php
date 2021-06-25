<?php

namespace App\Http\Controllers\OneSignal;

use App\Helpers\OneSignalHelper;
use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\CustomerDevice;
use App\Models\UserDevice;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Ladumor\OneSignal\OneSignal;

class OneSignalController extends Controller
{
    use ResponseHelper;

    // public function registerAdminDevice(Request $request)
    // {
    //     $validator = OneSignalHelper::validateDevice($request);
    //     if ($validator->fails()) {
    //         return $this->generateResponse($validator->errors()->first(), 422, true);
    //     }

    //     $responseData = OneSignalHelper::registerDevice($request);

    //     if ($responseData['success'] === true) {
    //         try {
    //             $userDevice = UserDevice::create([
    //                 'user_id' => Auth::guard('users')->user()->id,
    //                 'player_id' => $responseData['id'],
    //             ]);
    //         } catch (QueryException $e) {
    //             return $this->generateResponse('Device already registered', 409, true);
    //         }

    //         return $this->generateResponse($userDevice->load('user'), 200);
    //     }

    //     return $this->generateResponse('Something went wrong', 406, true);
    // }

    // public function registerCustomerDevice(Request $request)
    // {
    //     $validator = OneSignalHelper::validateDevice($request);
    //     if ($validator->fails()) {
    //         return $this->generateResponse($validator->errors()->first(), 422, true);
    //     }

    //     $responseData = OneSignalHelper::registerDevice($request);

    //     if ($responseData['success'] === true) {
    //         try {
    //             $customerDevice = CustomerDevice::create([
    //                 'customer_id' => Auth::guard('customers')->user()->id,
    //                 'player_id' => $responseData['id'],
    //             ]);
    //         } catch (QueryException $e) {
    //             return $this->generateResponse('Device already registered', 409, true);
    //         }

    //         return $this->generateResponse($customerDevice->load('customer'), 200);
    //     }

    //     return $this->generateResponse('Something went wrong', 406, true);
    // }

    // public function sendPushNotification(Request $request)
    // {
    //     $validator = OneSignalHelper::validateUsers($request);
    //     if ($validator->fails()) {
    //         return $this->generateResponse($validator->errors()->first(), 422, true);
    //     }

    //     if ($request->type) {
    //         $playerIds = OneSignalHelper::getPlayerIdsByType($request->type, $request->slugs);
    //         if (!$playerIds) {
    //             return $this->generateResponse('The type must be customer, admin or vendor.', 406, true);
    //         }
    //     } else {
    //         $playerIds = OneSignalHelper::getPlayerIdsByGroup($request->group_slug);
    //         if (!$playerIds) {
    //             return $this->generateResponse('There is no customer in this group.', 406, true);
    //         }
    //     }

    //     $fields['include_player_ids'] = $playerIds;
    //     $fields['data'] = ['slug' => "slug"];
    //     $message = $request->message;

    //     if ($request->url) {
    //         $fields['url'] = $request->url;
    //     }

    //     try {
    //         $response = OneSignal::sendPush($fields, $message);
    //     } catch (\Exception $e) {
    //         throw new \Exception('Division by zero.');
    //     }

    //     if (isset($response['errors'])) {
    //         return $this->generateResponse('The user did not subscribe to beehive.', 422, true);
    //     }

    //     return $this->generateResponse('Successfully sent push notification.', 200, true);
    // }

    public function registerAdminPlayerID($playerId, Request $request)
    {
        $validator = OneSignalHelper::validatePlayerID($request);
        $request['user_id'] = Auth::guard('users')->user()->id;

        if ($validator->fails()) {
            return $this->generateResponse($validator->errors()->first(), 422, true);
        }

        try {
            $userDevice = UserDevice::create([
                'user_id' => $request['user_id'],
                'player_id' => $playerId,
            ]);
        } catch (QueryException $e) {
            return $this->generateResponse('Device already registered', 409, true);
        }

        return $this->generateResponse($userDevice->load('user'), 200);
    }

    public function sendAdmins(Request $request)
    {
        $validator = OneSignalHelper::validateVendorsAndAdmins($request);
        if ($validator->fails()) {
            return $this->generateResponse($validator->errors()->first(), 422, true);
        }

        $appId = config('one-signal.admin_app_id');
        $fields = OneSignalHelper::prepareNotification($request, $appId);

        $response = OneSignalHelper::sendPush($fields, 'admin');
        if (isset($response['errors'])) {
            return $this->generateResponse('One of the users did not subscribe to beehive.', 422, true);
        }

        return $this->generateResponse('Successfully sent push notification.', 200, true);
    }

    public function sendVendors(Request $request)
    {
        $validator = OneSignalHelper::validateVendorsAndAdmins($request);
        if ($validator->fails()) {
            return $this->generateResponse($validator->errors()->first(), 422, true);
        }

        $appId = config('one-signal.vendor_app_id');
        $fields = OneSignalHelper::prepareNotification($request, $appId);

        $response = OneSignalHelper::sendPush($fields, 'vendor');
        if (isset($response['errors'])) {
            return $this->generateResponse('One of the users did not subscribe to beehive.', 422, true);
        }

        return $this->generateResponse('Successfully sent push notification.', 200, true);
    }
}
