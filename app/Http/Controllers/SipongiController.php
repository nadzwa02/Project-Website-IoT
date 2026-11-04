<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;

class SipongiController extends Controller
{
    public function hotspots()
    {
        $url = 'https://opsroom.sipongidata.my.id/api/opsroom/indoHotspot?wilayah=IN&filterperiode=false&from=&to=&late=24&satelit%5B%5D=NASA-MODIS&satelit%5B%5D=NASA-SNPP&satelit%5B%5D=NASA-NOAA20&confidence%5B%5D=low&confidence%5B%5D=medium&confidence%5B%5D=high&provinsi=&kabkota=';

        try {
            $response = Http::timeout(20)->get($url);

            if ($response->failed()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal mengambil data SIPONGI',
                ], 502);
            }

            return response()->json([
                'success' => true,
                'data' => $response->json(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak dapat terhubung ke SIPONGI',
            ], 500);
        }
    }
}