<?php

namespace App\Http\Controllers;

use App\Models\Package;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PackageController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    public function getPackages(){
        $packages = Package::all();
        if($packages->count() > 0){
            return response()->json([
                'status' => 'success',
                'data' => $packages
            ], 200);
        }
        return response()->json([
            'status' => 'error',
            'message' => 'No packages found'
        ], 500);
    }

    public function addPackage(Request $request){
        $validation = Validator::make($request->all(), [
            'title' => 'required|string',
            'description' => 'required|string',
            'duration' => 'required|string',
            'price' => 'required|integer',
            'quantity' => 'required|integer',
        ]);

        if ($validation->fails()) {
            return response()->json([
                 "status"=> "error",
                 "message"=>$validation->errors(),
             ]);
         }

        $package = new Package();
        $package->title=$request->title;
        $package->description = $request->description;
        $package->duration=$request->duration;
        $package->price = $request->price;
        $package->quantity = $request->quantity;
        $package->sales_count = 0;

        if($package->save()){
            return response()->json([
                'status' => 'success',
                'data' => $package
            ], 200);
        }
        return response()->json([
            'status' => 'error',
            'message' => 'Error in adding package'
        ], 500);
    }

    public function updatePackage(Request $request){
        $package = Package::find($request->packageid);
        if($request->has('title')){
            $package->title = $request->title;
        }
        if($request->has('price')){
            $package->price = $request->price;
        }
        if($request->has('duration')){
            $package->duration = $request->duration;
        }
        if($request->has('quantity')){
            $package->quantity = $request->quantity;
        }
        if($request->has('description')){
            $package->description = $request->description;
        }
        if($package->save()){
            return response()->json([
                'status' => 'success',
                'data' => $package
            ], 200);
        }
        return response()->json([
            'status' => 'error',
            'message' => 'Error in updating package'
        ], 500);

    }

    public function removePackage($package_id){
        $package = Package::find($package_id);
        $businesses=DB::table("businesshaspackage")->where("businesshaspackage.package_id",'=',$package_id)->get();
        if(empty($businesses)){
            if($package->delete()){
                return response()->json([
                    'status' => 'success',
                    'message' => 'Package removed successfully'
                ], 200);
            }
            return response()->json([
                'status' => 'error',
                'message' => 'Error in removing package'
            ], 500);
        }else{
            return response()->json([
                'status' => 'error',
                'message' => 'There are active deals for this package'
            ], 500);
        }

    }


}
