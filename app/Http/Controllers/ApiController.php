<?php

namespace App\Http\Controllers;

use App\Models\Cid;

class ApiController extends Controller
{
    /** Autopreenche a descrição do CID-10 na tela de atestados. */
    public function cid(string $code)
    {
        $cid = Cid::where('code', strtoupper(trim($code)))->first();

        return response()->json(['found' => (bool) $cid, 'description' => $cid?->description]);
    }
}
