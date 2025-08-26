<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Broadcast;

class BroadcastAuthController extends Controller
{
    public function authenticate(Request $request)
    {

        if (!$request->hasSession()) {
            $request->session()->start();
        }
        return Broadcast::auth($request);
    }
}