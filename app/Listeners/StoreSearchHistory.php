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
                ->first();
        } else if ($event->customerId && !$event->deviceId) {
            $history = SearchHistory::where('keyword', $event->keyword)
                ->where('customer_id', $event->customerId)
                ->whereNull('device_id')
                ->first();
        } else if (!$event->customerId && $event->deviceId) {
            $history = SearchHistory::where('keyword', $event->keyword)
                ->where('device_id', $event->deviceId)
                ->whereNull('customer_id')
                ->first();
        }

        if ($event->customerId || $event->deviceId) {
            if ($history) {
                $history->hit_count += 1;
                $history->save();
            } else {
                $this->createSearchHistory($event->customerId, $event->deviceId, $event->keyword);
            }
        }
    }

    private function createSearchHistory($customerId, $deviceId, $keyword)
    {
        SearchHistory::create([
            'customer_id' => $customerId,
            'device_id' => $deviceId,
            'keyword' => $keyword,
        ]);
    }
}
