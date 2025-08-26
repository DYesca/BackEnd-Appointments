<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class UserRoleController extends Controller
{
    public function client()
    {
        return response()->json(['message' => 'Bienvenido Usuario Cliente.']);
    }

    public function provider()
    {
        return response()->json(['message' => 'Bienvenido Usurio Proveedor de Servicios.']);
    }

    public function admin()
    {
        return response()->json(['message' => 'Bienvenido usuario admin.']);
    }
}
