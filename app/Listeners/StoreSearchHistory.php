<?php

namespace App\Listeners;

use App\Events\KeywordSearched;
use App\Models\SearchHistory;

class StoreSearchHistory
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  KeywordSearched  $event
     * @return void
     */
    public function handle(KeywordSearched $event)
    {
        if ($event->customerId && $event->deviceId) {
            $history = SearchHistory::where('keyword', $event->keyword)
                ->where('customer_id', $event->customerId)
                ->where('device_id', $event->deviceId)
                ->where('type', $event->type)
                ->first();
        } else if ($event->customerId && !$event->deviceId) {
            $history = SearchHistory::where('keyword', $event->keyword)
                ->where('customer_id', $event->customerId)
                ->whereNull('device_id')
                ->where('type', $event->type)
                ->first();
        } else if (!$event->customerId && $event->deviceId) {
            $history = SearchHistory::where('keyword', $event->keyword)
                ->whereNull('customer_id')
                ->where('device_id', $event->deviceId)
                ->where('type', $event->type)
                ->first();
        }

        if ($event->customerId || $event->deviceId) {
            if ($history) {
                $history->hit_count += 1;
                $history->save();
            } else {
                SearchHistory::create([
                    'customer_id' => $event->customerId,
                    'device_id' => $event->deviceId,
                    'keyword' => $event->keyword,
                    'type' => $event->type,
                ]);
            }
        }
    }
}
