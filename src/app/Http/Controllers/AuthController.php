<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\Business;
use App\Models\Client;
use App\Models\Employee;
use App\Models\User;
use BaconQrCode\Common\ErrorCorrectionLevel;
use Google\Cloud\Storage\StorageClient;
use Illuminate\Support\Facades\Validator;
use BaconQrCode\Encoder\Encoder;
use Illuminate\Support\Facades\DB;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class AuthController extends Controller
{

    public function login(Request $request) // email/password/usertype
    {
        $validation = Validator::make($request->all(), [
            'email' => 'required|string|email',
            'password' => 'required|string',
            'type' => 'required|int',
        ]);

        if ($validation->fails()) {
            return response()->json([
                "status" => "error",
                "message" => $validation->errors(),
            ]);
        }

        $credentials = $request->only('email', 'password');

        $token = Auth::attempt($credentials);

        if (!$token) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized',
            ], 401);
        }

        $user = Auth::user();
        $user_info = User::find($user->id);


        if ($user_info->banned == false) {
            if ($request->type == 0) { //business
                $user_details = Business::where("user_id", $user->id)->get();
            } else if ($request->type == 1) { //client
                $user_details = Client::where("user_id", $user->id)->first();
                $savings = DB::table("client_redeems_deal")
                    ->where("client_id", "=", $user->id)
                    ->where("status", "=", 1)
                    ->sum("saved");
                $user_details->savings = $savings;
            } else if ($request->type == 2) { //employee
                $user_details = Employee::where("user_id", $user->id)->get();
            } else {
                $user_details = null;
            }
            return response()->json([
                'status' => 'success',
                'user' => $user,
                'user_details' => $user_details,
                'authorisation' => [
                    'token' => $token,
                    'type' => 'bearer',
                ]
            ]);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'Banned',
            ], 401);
        }
    }

    public function register(Request $request)
    { //username/password/email/number/usertype/and the info of the user
        $validation = Validator::make($request->all(), [
            'username' => 'required|string|min:6|unique:users',
            'email' => 'required|string|email|unique:users',
            'password' => 'required|string',
            'type' => 'required|int',
            'number' => 'required|int|min:8',
        ]);

        if ($validation->fails()) {
            return response()->json([
                "status" => "error",
                "message" => $validation->errors(),
            ]);
        }

        $user = User::create([
            'username' => $request->username,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'number' => $request->number,
            'type' => $request->type,
            'banned' => false,
        ]);

        $token = Auth::login($user);
        if ($request->type == 1) { //user is a client

            $storage = new StorageClient([
                'projectId' => 'meno-a6fd9',
                'keyFilePath' => 'C:\Users\marc issa\Desktop\Meno\MENO\APIs\meno-a6fd9-firebase-adminsdk-dv2i6-bbf9790bcf.json'
            ]);

            $bucket = $storage->bucket('meno-a6fd9.appspot.com');
            $qrcodestring = "http://127.0.0.1:8000/api/qrcode/getclient/$user->id";
            $qrCode = QrCode::format('svg')->size(200)->generate($qrcodestring);
            // Convert image to base64
            $base64Image = base64_encode($qrCode);
            $imageData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $base64Image));
            $filename = uniqid() . '.svg';
            $folderPath = 'qrcodes/';
            $object = $bucket->upload($imageData, [
                'name' => $folderPath . $filename
            ]);

            $url = $object->signedUrl(new \DateTime('+100 years'));
            $client = Client::create([
                'user_id' => $user->id,
                'full_name' => '_',
                'dob' => '_',
                'gender' => '_',
                'qrcodeurl' => $url,
            ]);
            return response()->json([
                'status' => 'success',
                'message' => 'User created successfully',
                'user' => $user,
                'user_details' => $client,
                'qrcode' => $base64Image,
                'authorisation' => [
                    'token' => $token,
                    'type' => 'bearer',
                ]
            ]);
        } else if ($request->type == 2) { //user is an employee
            $employee = Employee::create([
                'user_id' => $user->id,
                'position' => $request->position,
                'full_name' => $request->full_name,
                'address' => $request->address,
                'gender' => $request->gender,
                'dob' => $request->dob
            ]);

            if ($employee->save()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'User created successfully',
                    'user' => $user,
                    'user_details' => $employee,
                    'authorisation' => [
                        'token' => $token,
                        'type' => 'bearer',
                    ]
                ]);
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'User not created',
                ]);
            }
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'User type not found',
            ]);
        }
    }

    public function logout()
    {
        Auth::logout();
        return response()->json([
            'status' => 'success',
            'message' => 'User logged out successfully'
        ]);
    }

    public function refresh()
    {
        return response()->json([
            'status' => 'success',
            'user' => Auth::user(),
            'authorisation' => [
                'token' => Auth::refresh(),
                'type' => 'bearer',
            ]
        ]);
    }
}
