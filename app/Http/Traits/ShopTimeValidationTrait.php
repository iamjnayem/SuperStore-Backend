<?php

namespace App\Http\Traits;

use App\Models\StoreSettings;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use function Symfony\Component\Translation\t;

trait ShopTimeValidationTrait
{

    public function isValidTime($merchantId): bool
    {

        $getStoreInfo = StoreSettings::select('pickup_and_dine_in_times')
            ->where('user_id',$merchantId)
            ->first();

        if (!$getStoreInfo){
            return false;
        }

        $shopTimes = (array)json_decode($getStoreInfo->pickup_and_dine_in_times);

        return $this->checkStoreStartAndEndTime($shopTimes);

        //dd(Carbon::now()->format('l'));
    }

    public function checkStoreStartAndEndTime($shopTimes=[]): bool
    {
        //$times = (array)json_decode($shopTimes);

        foreach ($shopTimes as $i => $time){

            if ($i == Carbon::now()->format('l')){

                $currentTime = Carbon::now()->format('Gis.u');
                $startTime = Carbon::parse($time->start_time)->format('Gis.u');
                $endTime = Carbon::parse($time->end_time)->format('Gis.u');

                if ( $startTime <= $currentTime &&  $endTime >= $currentTime){
                    return true;
                }
            }

        }

        return false;
    }

}
