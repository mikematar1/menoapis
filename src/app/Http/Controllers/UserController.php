<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

use App\Models\User;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    public function banUnbanUser($clientid){
        $user = User::find($clientid);
        if($user->banned){
            $user->banned=false;
            if($user->save()){
                return response()->json([
                    'status' => 'success',
                    'message' => 'User unbanned successfully',
                    'banned' => $user->banned
                ], 200);
            }
            return response()->json([
                'status' => 'error',
                'message' => 'User unbanning failed'
            ], 500);
        }

        $user->banned=true;
        if($user->save()){
            return response()->json([
                'status' => 'success',
                'message' => 'User banned successfully',
                'banned' => $user->banned
            ], 200);
        }
        return response()->json([
            'status' => 'error',
            'message' => 'User banning failed'
        ], 500);

    }
}
