<?php

namespace App\Http\Controllers;

use App\Models\Blog;
use App\Models\Business;
use App\Models\BusinessRequest;
use App\Models\Category;
use App\Models\Client;
use App\Models\Employee;

use App\Models\Image;
use App\Models\Notification;
use App\Models\Package;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Google\Cloud\Storage\StorageClient;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
class EmployeesController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    public function getEmployees(){
        $user = Auth::user();
        $employees = User::join("employees","users.id",'=',"employees.user_id")->where("id","!=", $user->id)->paginate(14);
        return response()->json([
            'status' => 'success',
            'data' => $employees
        ], 200);

    }

    public function addEmployee(Request $request){
        $validator = Validator::make($request->all(), [
            'username' => 'required|string|min: 6|unique:users',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
            'number' => 'required|integer|min:8',
            'position' => 'required|string',
            'full_name' => 'required|string',
            'address'=>'required|string',
            'gender'=>'required|string',
            'dob'=>'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                "status"=> "error",
                "message"=>$validator->errors(),
            ]);
        }

        $user = User::create([
            'username' => $request->username,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'number' =>$request->number,
            'type'=> 2,
            'banned'=>false,
        ]);

        $employee = Employee::create([
            'user_id'=>$user->id,
            'position'=>$request->position,
            'full_name'=>$request->full_name,
            'address'=>$request->address,
            'gender'=>$request->gender,
            'dob'=>$request->dob
        ]);

        if($employee->wasRecentlyCreated && $user->wasRecentlyCreated){
            return response()->json([
                'status' => 'success',
                'user'=>$user,
                'user_details'=>$employee,
            ], 200);
        }
        return response()->json([
            'status' => 'error',
            'message' => 'Employee creation failed',
        ], 500);
    }

    public function editInformation(Request $request){
        $validator = Validator::make($request->all(), [
            'username' => 'string|min: 6|unique:users',
            'email' => 'string|email|max:255|unique:users',
            'password' => 'string|min:6',
            'number' => 'string|min:8',
            'position' => 'string',
            'full_name' => 'string',
            'address'=>'string',
            'gender'=>'string',
            'dob'=>'string',
        ]);

       if($validator->fails()){
            return response()->json([
                "status"=> "error",
                "message"=>$validator->errors(),
            ]);
       }

        $user_info = User::find($request->user_id);
        $employee = Employee::where("user_id",$user_info->id)->first();

        if($request->has("full_name")){
            $employee->full_name = $request->full_name;
        }
        if($request->has("position")){
            $employee->position=$request->position;
        }
        if($request->has("address")){
            $employee->address = $request->address;
        }
        if($request->has("gender")){
            $employee->gender = $request->gender;
        }
        if($request->has("dob")){
            $employee->dob = $request->dob;
        }
        if($request->has("username")){
            $user_info->username = $request->username;
        }
        if($request->has("password")){
            $user_info->password = Hash::make($request->password);
        }

        if($request->has("number")){
            $user_info->number = $request->number;
        }
        if($request->has("email")){
            $user_info->email = $request->email;
        }
        if($user_info->save() && $employee->save()){
            return response()->json([
                'status' => 'success',
                'user'=>$user_info,
                'user_details'=>$employee,
            ], 200);
        }
        return response()->json([
            'status' => 'error',
            'message' => 'Employee information update failed',
        ], 500);
    }

    public function search($usertype,$username){
        if($usertype==0){ //searching for businesses
            $users=Business::where("name",'LIKE','%'.$username.'%')->get();
        }else if($usertype==1){//searching for clients
            $users=Client::where('full_name','LIKE','%'.$username.'%')->get();
        }else if($usertype==2){//stressing for employees
            $users=Employee::where('full_name','Like','%'.$username.'%')->get();
        }
        return $users;
    }


    public function getBusinessStats($businessid){

    }

    public function banEmployee($user_id){
        $user = User::find($user_id);
        $employee = Employee::where("user_id",$user_id)->first();

        if($user->banned==false){
            $user->banned=true;
        }else{
            $user->banned=false;
        }
        if($user->save()){
            return response()->json([
                'status' => 'success',
                'user'=>$user,
                'user_details'=>$employee,
            ], 200);
        }else{
            return response()->json([
                'status' => 'error',
                'message' => 'Employee ban failed',
            ], 500);
        }
    }

    // Progress
    public function sendNotification(Request $request){
        $user = Auth::user();
        $notification = New Notification();
        $notification->title = $request->title;
        $notification->description= $request->description;
        $notification->employeeid = $user->id;
        $notification->save();
    }
    public function filter($position){

    }


}
