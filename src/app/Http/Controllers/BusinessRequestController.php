<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\BusinessRequest;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class BusinessRequestController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    public function addBusinessRequest(Request $request){
        $validation = Validator::make($request->all(), [
            'email' => 'required|string|email|max:255|unique:users',
            'name' => 'required|string|min:2',
            'number' => 'required|string|min:8',
            'business_name' => 'required|string|min:2',

        ]);

        if ($validation->fails()) {
            return response()->json([
                 "status"=> "error",
                 "message"=>$validation->errors(),
             ]);
         }

        $business_req = new BusinessRequest();
        $business_req->employee_id = 0;
        $business_req->name=$request->name;
        $business_req->email=$request->email;
        $business_req->number=$request->number;
        $business_req->business_name=$request->business_name;

        if($business_req->save()){
            return response()->json([
                'status' => 'success',
                'data' => $business_req
            ], 200);
        }else{
            return response()->json([
                'status' => 'error',
                'data' => 'Error in adding business request'
            ], 200);
        }
    }

    public function verifyBusiness(Request $request){
        $validation = Validator::make($request->all(), [
            'email' => 'required|string|email|max:255|unique:users',
            'name' => 'required|string|min:2',
            'phone' => 'required|string|min:8',
            'business_name' => 'required|string|min:2',

        ]);

        if ($validation->fails()) {
            return response()->json([
                 "status"=> "error",
                 "message"=>$validation->errors(),
             ]);
        }

        $business_request = BusinessRequest::find($request->business_request_id);

        $user = User::create([
            'username' => $business_request->name,
            'email' => $business_request->email,
            'password' => Hash::make($request->password),
            'number' =>$business_request->number,
            'usertype'=> $request->usertype,
        ]);
        $business = Business::create([
            'user_id'=>$user->id,
            'name'=>$business_request->businessname,
            'location'=>$request->location,
            'contact_info'=>$request->contact_info,
            'opening_hours'=>$request->opening_hours,
            'description'=>$request->description,
            'logourl'=>$request->logourl,
            'categoryid'=>$request->categoryid,
            'employeeid'=>$business_request->employeeid,
            'fblink'=>$request->fblink,
            'iglink'=>$request->iglink,
            'status'=>0,
            'wallet'=>0
        ]);
        if($user->wasRecentlyCreated && $business->wasRecentlyCreated){
            $business_request->delete();
            return response()->json([
                'status' => 'success',
                'data' => $business
            ], 200);
        }
        return response()->json([
            'status' => 'error',
            'data' => 'Error in adding business'
        ], 200);
    }

    public function assignBusiness(Request $request){ //request has employeeid and businessrequestid

        $business = BusinessRequest::find($request->request_id);
        $business->employee_id = $request->employee_id;
        $business->save();
    }
    public function getBusinessesForReview(){
        $user = Auth::user();
        if($user->usertype==2){
            $employee = Employee::where("user_id",$user->id)->first();
            if($employee->position=="admin"){
                $businesses = BusinessRequest::all();
                return $businesses;
            }
        }
    }

    public function getBusinessesUnderReview(){
        $user = Auth::user();
        if($user->usertype==2){
            $businesses = BusinessRequest::where("employeeid",$user->id)->get();
            return $businesses;

        }
    }
}

