<?php

namespace App\Modules\Delivery\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Delivery\Contracts\GeocoderInterface;
use App\Modules\Delivery\Requests\GeocodeRequest;

class AddressController extends Controller
{
    public function __construct(
        private GeocoderInterface $geocoder,
    ) {}

    public function geocode(GeocodeRequest $request)
    {
        $point = $this->geocoder->geocode($request->validated('address'));

        return response()->json([
            'lat' => $point->lat,
            'lng' => $point->lng,
        ]);
    }
}
