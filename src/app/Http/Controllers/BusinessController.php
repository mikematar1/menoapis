<?php

namespace App\Http\Controllers;

use App\Models\Deal;
use App\Models\Business;
use App\Models\Chat;
use App\Models\Employee;
use App\Models\Image;
use App\Models\Message;
use App\Models\Package;
use App\Models\Room;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

use DateTime;
use Google\Cloud\Storage\StorageClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

use Kreait\Firebase\Factory;

class BusinessController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    public function getEmployeeBusinesses()
    {
        $user = Auth::user();
        $employee = Employee::where("user_id", $user->id)->first();
        if ($employee->position == "admin") {
            $businesses = User::join("businesses", 'businesses.user_id', '=', 'users.id')->get();
            return $businesses;
        } else {
            $businesses = User::join('businesses', 'businesses.user_id', '=', 'users.id')
                ->where('businesses.employeeid', '=', $user->id)
                ->get();
            return $businesses;
        }
    }

    public function getInformation()
    {
        $user = Auth::user();
        $business = Business::where("user_id", $user->id)->first();
        $images = Image::where("businessid", "=", $user->id)->get();
        if ($user && $business) {
            return response()->json([
                'status' => 'success',
                'user' => $user,
                'user_details' => $business,
                'images' => $images
            ]);
        }
        return response()->json([
            'status' => 'error',
            'message' => 'No user found',
        ], 404);
    }

    public function addBusiness(Request $request)
    {
        $employee = Auth::user();

        $validation = Validator::make($request->all(), [
            'username' => 'required|string|max:255|unique:users',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
            'number' => 'required|int|min:8',
            'name' => 'required|string|max:255',
            'location' => 'required|string|max:255',
            'description' => 'required|string|max:255',
        ]);
        if ($validation->fails()) {
            return response()->json([
                "status" => "error",
                "message" => $validation->errors(),
            ]);
        }
        $storage = new StorageClient([
            'projectId' => 'meno-a6fd9',
            'keyFilePath' => 'C:\Users\marc issa\Desktop\Meno\MENO\APIs\meno-a6fd9-firebase-adminsdk-dv2i6-bbf9790bcf.json'

        ]);

        $bucket = $storage->bucket('meno-a6fd9.appspot.com');
        $base64Image = $request->profile;
        $imageData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $base64Image));
        $filename = uniqid() . '.png';
        $folderPath = 'busimages/';
        $object = $bucket->upload($imageData, [
            'name' => $folderPath . $filename
        ]);

        $url = $object->signedUrl(new \DateTime('+100 years'));

        $user = User::create([
            'username' => $request->username,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'number' => $request->number,
            'type' => 0,
            'banned' => false
        ]);
        $business = Business::create([
            'user_id' => $user->id,
            'name' => $request->name,
            'location' => $request->location,
            'opening_hours' => '00:00:00',
            'closing_hours' => '00:00:00',
            'description' => $request->description,
            'logo_url' => $url,
            'category_id' => $request->categoryid,
            'employee_id' => $employee->id,
            'fb_link' => $request->fblink,
            'ig_link' => $request->iglink,
            'tiktok_link' => $request->tiktoklink,
            'menu_link' => $request->menulink,
            'wallet' => 0
        ]);

        if ($request->has("business_images")) {
            $imagearray = array();
            $images = $request->business_images;
            for ($i = 0; $i < sizeof($images); $i++) {
                $base64Image = $images[$i]["imageurl"];
                $imageData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $base64Image));
                $filename = uniqid() . '.png';
                $folderPath = 'busimages/';
                $object = $bucket->upload($imageData, [
                    'name' => $folderPath . $filename
                ]);
                $url = $object->signedUrl(new \DateTime('tomorrow'));
                $image = Image::create([
                    "businessid" => $user->id,
                    "dealid" => 0,
                    "imageurl" => $url
                ]);
                array_push($imagearray, $image);
            }
            if ($user->save() && $business->save()) {
                return response()->json([
                    'status' => 'success',
                    'user' => $user,
                    'business' => $business,
                    'images' => $imagearray,
                    '$user' => $user,

                ], 201);
            }
        }
        if ($user->save() && $business->save()) {
            return response()->json([
                'status' => 'success',
                'user' => $user,
                'business' => $business,
                '$user' => $user,

            ], 201);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'Business not created'
        ], 500);
    }

    public function removeBusiness($userid)
    {
        $business = Business::where("user_id", '=', $userid);
        $user = User::find($userid);
        $storage = new StorageClient([
            'projectId' => 'meno-a6fd9',
            'keyFilePath' => env('FIREBASE_PRIVATE_KEY')
        ]);
        $bucket = $storage->bucket('meno-a6fd9.appspot.com');
        $imageurl = $business->logo_url;
        $folderPath = "busimages/";
        if (preg_match('/\/([\w-]+\.(png|jpg|jpeg|gif))/', $imageurl, $matches)) {
            $filename = $matches[1];
            $overallpath = $folderPath . $filename;
            $object = $bucket->object($overallpath);
            $object->delete();
        }
        $images = Image::where('businessid', $user->id)->get();
        if (!empty($images)) {
            for ($i = 0; $i < sizeof($images); $i++) {
                $image = $images[$i];
                $imageurl = $image->imageurl;
                if (preg_match('/\/([\w-]+\.(png|jpg|jpeg|gif))/', $imageurl, $matches)) {
                    $filename = $matches[1];
                    $overallpath = $folderPath . $filename;
                    $object = $bucket->object($overallpath);
                    $object->delete();
                }
            }
        }
        if ($business->delete() && $user->delete()) {
            $images = Image::where("businessid", "=", $userid);
            if (!empty($images)) {
                $folder_name = "busimages/";
                $storage = new StorageClient([
                    'projectId' => 'meno-a6fd9',
                    'keyFilePath' => 'C:\Users\marc issa\Desktop\Meno\MENO\APIs\meno-a6fd9-firebase-adminsdk-dv2i6-bbf9790bcf.json'
                ]);
                $bucket = $storage->bucket('meno-a6fd9.appspot.com');
                for ($i = 0; $i < sizeof($images); $i++) {
                    $imageid = $images[$i];
                    $image = Image::find($imageid);
                    $url = $image->imageurl;
                    if (preg_match('/([\w]+.(png|jpg|jpeg|gif))/', $url, $matches)) {
                        $filename = $matches[1];
                        $overallpath = $folder_name . $filename;
                        $object = $bucket->object($overallpath);
                        $object->delete();
                        $image->delete();
                    }
                }
            }
            return response()->json([
                'status' => 'success',
                'message' => 'Business deleted successfully'
            ], 200);
        }
        return response()->json([
            'status' => 'error',
            'message' => 'Business not deleted'
        ], 500);
    }


    public function employeeEditInformation(Request $request)
    {
        $user = User::find($request->businessid);
        $business = Business::find($request->businessid);

        $validator = Validator::make($request->all(), [
            'username' => 'string|min: 6|unique:users',
            'email' => 'string|email|max:255|unique:users',
            'password' => 'string|min:6',
            'number' => 'integer|min:8',
            'name' => 'string|max:255',
            'location' => 'string|max:255',
            'description' => 'string|max:255',
            'fb_link' => 'string|max:255',
            'ig_link' => 'string|max:255',
            'tiktok_link' => 'string|max:255',
            'menu_link' => 'string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json([
                "status" => "error",
                "message" => $validator->errors(),
            ]);
        }
        if ($user->type == 0) {
            if ($request->has("username")) {
                $user->username = $request->username;
            }
            if ($request->has("name")) {
                $business->name = $request->name;
            }
            if ($request->has("location")) {
                $business->location = $request->location;
            }
            if ($request->has("number")) {
                $user->number = $request->number;
            }
            if ($request->has("email")) {
                $user->email = $request->email;
            }
            if ($request->has("password")) {
                $user->password = Hash::make($request->password);
            }
            if ($request->has("logo_url")) {
                $base64image = $request->logo_url;
                $storage = new StorageClient([
                    'projectId' => 'meno-a6fd9',
                    'keyFilePath' => env('FIREBASE_PRIVATE_KEY')
                ]);
                $bucket = $storage->bucket('meno-a6fd9.appspot.com');
                $imageData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $base64image));
                $filename = uniqid() . '.png';
                $folderPath = 'busimages/';
                $object = $bucket->upload($imageData, [
                    'name' => $folderPath . $filename
                ]);
                $expiration = new DateTime('+100 years');
                $url = $object->signedUrl($expiration);
                $imageurl = $business->logo_url;
                if (preg_match('/\/([\w-]+\.(png|jpg|jpeg|gif))/', $imageurl, $matches)) {
                    $filename = $matches[1];
                    $overallpath = $folderPath . $filename;
                    $object = $bucket->object($overallpath);
                    $object->delete();
                }
                $business->logo_url = $url;
            }
            if ($request->has("category_id")) {
                $business->category_id = $request->category_id;
            }
            if ($request->has("employee_id")) {
                $business->employee_id = $request->employeeid;
            }
            if ($request->has("fb_link")) {
                $business->fb_link = $request->fb_link;
            }
            if ($request->has("ig_link")) {
                $business->ig_link = $request->ig_link;
            }
            if ($request->has("tiktok_link")) {
                $business->tiktok_link = $request->tiktok_link;
            }
            if ($request->has("menu_link")) {
                $business->menu_link = $request->menu_link;
            }
            if ($request->has("wallet")) {
                $business->wallet = $request->wallet;
            }
        }

        if ($user->type == 0) {
            if ($request->has("password")) {
                $business->password = $request->password;
            }
            if ($request->has("opening_hours")) {
                $business->opening_hours = $request->opening_hours;
            }
            if ($request->has("closing_hours")) {
                $business->closing_hours = $request->closing_hours;
            }
            if ($request->has("description")) {
                $business->description = $request->description;
            }
            if ($request->has("fb_link")) {
                $business->fblink = $request->fblink;
            }
            if ($request->has("ig_link")) {
                $business->iglink = $request->iglink;
            }
            if ($request->has("tiktok_link")) {
                $business->tiktoklink = $request->tiktoklink;
            }
            if ($request->has("menu_link")) {
                $business->menulink = $request->menulink;
            }
        }
        $storage = new StorageClient([
            'projectId' => 'meno-a6fd9',
            'keyFilePath' => 'C:\Users\marc issa\Desktop\Meno\MENO\APIs\meno-a6fd9-firebase-adminsdk-dv2i6-bbf9790bcf.json'
        ]);

        $bucket = $storage->bucket('meno-a6fd9.appspot.com');
        if ($request->has("images_added")) {
            $imagestobeadded = $request->images_added; //base64
            for ($i = 0; $i < sizeof($imagestobeadded); $i++) {
                $base64Image = $imagestobeadded[$i]['imageurl'];
                $imageData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $base64Image));
                $filename = uniqid() . '.png';
                $folderPath = 'busimages/';
                $object = $bucket->upload($imageData, [
                    'name' => $folderPath . $filename
                ]);
                $expiration = new DateTime('+100 years');
                $url = $object->signedUrl($expiration);
                $image = Image::create([
                    "businessid" => $request->businessid,
                    "dealid" => 0,
                    "imageurl" => $url
                ]);
            }
        }

        if ($request->has("images_removed")) {
            $images = $request->images_removed;
            $folder_name = "busimages/";
            if (!empty($images)) {
                for ($i = 0; $i < sizeof($images); $i++) {
                    $imageid = $images[$i];
                    $image = Image::find($imageid);
                    $url = $image->imageurl;
                    if (preg_match('/([\w]+.(png|jpg|jpeg|gif))/', $url, $matches)) {
                        $filename = $matches[1];
                        $overallpath = $folder_name . $filename;
                        $object = $bucket->object($overallpath);
                        $object->delete();
                        $image->delete();
                    }
                }
            }
        }
        if ($business->save() && $user->save()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Business updated successfully',
                'business' => $business,
                'user' => $user,
                'images' => Image::where('businessid', $user->id)->get()

            ], 200);
        }
        return response()->json([
            'status' => 'error',
            'message' => 'Business not updated'
        ], 500);
    }
    public function editInformation(Request $request)
    {
        $user = Auth::user();
        $business = Business::find($user->id);
        if ($request->has("description")) {
            $business->description = $request->description;
        }
        if ($request->has("opening_hours")) {
            $business->opening_hours = $request->opening_hours;
        }
        if ($request->has("closing_hours")) {
            $business->closing_hours = $request->closing_hours;
        }
        if ($business->save()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Business updated successfully',
                'business' => $business,
                'user' => $user,
                'images' => Image::where('businessid', $user->id)->get()
            ], 200);
        }
        return response()->json([
            'status' => 'error',
            'message' => 'Business not updated'
        ], 500);
    }

    public function getBusinessesCount()
    {
        $count =  Business::count();
        return response()->json([
            'count' => $count
        ], 200);
    }

    public function getBusinessesPerCategory($categoryid)
    {
        $businesses = Business::join("categories", 'categories.id', '=', 'businesses.category_id')
            ->where('categories.id', '=', $categoryid)
            ->get();
        return $businesses;
    }
    public function getAllBusinesses()
    {
        $businesses = Business::join("users", "users.id", '=', 'businesses.user_id')->paginate(9);
        for ($i = 0; $i < sizeof($businesses); $i++) {
            $images = Image::where('businessid', $businesses[$i]['id'])->get();
            $businesses[$i]['images'] = $images;
        }
        return response()->json([
            'data' => $businesses
        ], 200);
    }

    public function getStatistics()
    {
        //totals views and clicks
    }






    public function addDeal(Request $request)
    {
        $user = Auth::user();
        $deal = new Deal();
        $deal->title = $request->title;
        $deal->description = $request->description;
        $deal->new_price = $request->new_price;
        $deal->old_price = $request->old_price;
        $deal->expiry_date = $request->expiry_date;
        $deal->business_id = $user->id;
        $deal->views = 0;
        $deal->featured = null;
        $deal->currency = $request->currency;
        $deal->date_featured = null;
        $deal->save();
        $storage = new StorageClient([
            'projectId' => 'meno-a6fd9',
            'keyFilePath' => 'C:\Users\marc issa\Desktop\Meno\MENO\APIs\meno-a6fd9-firebase-adminsdk-dv2i6-bbf9790bcf.json'
        ]);
        $bucket = $storage->bucket('meno-a6fd9.appspot.com');
        $images = $request->images;
        $imagearray = array();
        for ($i = 0; $i < sizeof($images); $i++) {
            $base64Image = $images[$i];
            $imageData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $base64Image));
            $filename = uniqid() . '.png';
            $folderPath = 'dealimages/';
            $object = $bucket->upload($imageData, [
                'name' => $folderPath . $filename
            ]);
            $expiration = new DateTime('+100 years');
            $url = $object->signedUrl($expiration);
            $image = Image::create([
                "dealid" => $deal->id,
                "businessid" => 0,
                "imageurl" => $url
            ]);
            array_push($imagearray, $image);
        }
        return response()->json([
            'deal' => $deal,
            'images' => $imagearray

        ]);
    }
    public function removeDeal($dealid)
    {

        Deal::where("id", $dealid)->delete();
        return "Deal removed successfuly";
    }
    public function editDeal(Request $request)
    {
        $business = Auth::user();
        $deal = Deal::find($request->id);
        if ($request->has("title")) {
            $deal->title = $request->title;
        }
        if ($request->has("description")) {
            $deal->description = $request->description;
        }
        if ($request->has("new_price")) {
            $deal->new_price = $request->new_price;
        }
        if ($request->has("old_price")) {
            $deal->old_price = $request->old_price;
        }
        if ($request->has("expiry_date")) {
            $deal->expiry_date = $request->expiry_date;
        }
        if ($request->has("currency")) {
            $deal->currency = $request->currency;
        }
        $deal->save();
        $storage = new StorageClient([
            'projectId' => 'meno-a6fd9',
            'keyFilePath' => 'C:\Users\marc issa\Desktop\Meno\MENO\APIs\meno-a6fd9-firebase-adminsdk-dv2i6-bbf9790bcf.json'
        ]);
        $imagestobeadded = $request->images_added; //base64
        $bucket = $storage->bucket('meno-a6fd9.appspot.com');
        if ($request->has("images_added")) {
            for ($i = 0; $i < sizeof($imagestobeadded); $i++) {
                $base64Image = $imagestobeadded[$i]['imageurl'];
                $imageData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $base64Image));
                $filename = uniqid() . '.png';
                $folderPath = 'dealimages/';
                $object = $bucket->upload($imageData, [
                    'name' => $folderPath . $filename
                ]);
                $expiration = new DateTime('+100 years');
                $url = $object->signedUrl($expiration);
                $image = Image::create([
                    "dealid" => $deal->id,
                    "businessid" => 0,
                    "imageurl" => $url
                ]);
            }
        }

        if ($request->has("images_removed")) {
            $images = $request->images_removed;
            $folder_name = "dealimages/";
            if (!empty($images)) {
                for ($i = 0; $i < sizeof($images); $i++) {
                    $imageid = $images[$i]["id"];
                    $image = Image::find($imageid);
                    $url = $image->imageurl;
                    if (preg_match('/([\w]+.(png|jpg|jpeg|gif))/', $url, $matches)) {
                        $filename = $matches[1];
                        $overallpath = $folder_name . $filename;
                        $object = $bucket->object($overallpath);
                        $object->delete();
                        $image->delete();
                    }
                }
            }
        }
    }
    public function getDeals()
    {
        $user = Auth::user();
        if ($user->usertype == 0) {
            $deals = Deal::where("business_id", "=", 2)->get();
            $dealarray = array();
            for ($i = 0; $i < sizeof($deals); $i++) {
                $deal = $deals[$i];
                $end_date = $deal->expiry_date;
                $today = date("Y-m-d");

                if ($today <= $end_date) {
                    $images = Image::where("dealid", "=", $deal->id)->get();
                    $deal->images = $images;
                    array_push($dealarray,$deal);
                }
            }
            return json_encode($dealarray);
        } else {
            return "Fails";
        }
    }
    public function getFeaturedDeals()
    {
        $user = Auth::user();
        $deals = Deal::where("business_id", "=", $user->id)->get();
        $today = date("Y-m-d");
        $dealarray = array();
        foreach ($deals as $deal) {
            $end_date = $deal->expiry_date;
            if ($today < $end_date) {
                if ($deal->featured && $deal->date_featured) {
                    if ($deal->date_featured < $deal->featured) {
                        $images = Image::where("dealid", "=", $deal->id)->get();
                        $deal->images = $images;
                        array_push($dealarray, $deal);
                    }
                }
            }
        }
        return $dealarray;
    }


    public function scanQrCode(Request $request)
    {
    }
    public function addToWallet(Request $request)
    { //request contains amount
        $user = Auth::user();
        $business = Business::where("user_id", $user->id)->first();
        $business->wallet = $business->wallet + $request->amount;

        if ($business->save()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Wallet updated successfully'
            ], 200);
        }
        return response()->json([
            'status' => 'error',
            'message' => 'Wallet not updated'
        ], 500);
    }

    public function buyPackage($packageid)
    {
        $package = Package::find($packageid);
        $user = Auth::user();
        $business = Business::where("user_id", $user->id)->first();
        $business->wallet = $business->wallet - $package->price;
        DB::table('businesshaspackage')->insert([
            'business_id' => $user->id,
            'package_id' => $packageid,
            'duration' => $package->duration,
            'quantity' => $package->quantity
        ]);
        $package->sales_count = $package->sales_count + 1;
        $package->save();
        $business->save();
        if ($business->save() && $package->save()) {
            return response()->json([
                'status' => "success",
                "message" => "package is bought"
            ]);
        }
    }
    public function getCurrentPackages()
    {
        $user = Auth::user();

        $packages = DB::table('businesshaspackage')->join('packages', 'packages.id', '=', 'businesshaspackage.package_id')
            ->where('businesshaspackage.business_id', '=', $user->id)
            ->where('businesshaspackage.quantity', '>', 0)
            ->select()
            ->get();
        return $packages;
    }
    public function featureDeal(Request $request)
    { //dealid/packageid
        $user = Auth::user();
        $today = date("Y-m-d");
        $newDate = date("Y-m-d", strtotime($today . $request->duration));
        $package = Package::find($request->packageid);
        $deal = Deal::find($request->dealid);
        $deal->featured = $newDate;
        $deal->date_featured = date("Y-m-d");
        if ($deal->save()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Deal featured'
            ], 200);
        }
        return response()->json([
            'status' => 'error',
            'message' => 'Deal not featured'
        ], 500);
    }
    public function getReviews()
    {
        $user = Auth::user();
        $reviews = DB::table('clientreviewbusiness')->where('businessid', $user->id)->get();
        return $reviews;
    }
    public function search($username)
    {
    }
    public function filter($categoryid)
    {
    }
    public function completeRedeem($redeemid)
    {
        DB::table("client_redeems_deal")->where("id", "=", $redeemid)->update([
            "status" => 1
        ]);
    }
    public function giveClientPoints(Request $request)
    {
        $user = Auth::user();
        $exists = DB::table('client_has_points')
            ->where('client_id', '=', $request->client_id)
            ->where('business_id', '=', $user->id)
            ->exists();

        if ($exists) {
            $points = DB::table('client_has_points')
                ->where('client_id', '=', $request->client_id)
                ->where('business_id', '=', $user->id)
                ->select("points")
                ->first();
            DB::table('client_has_points')
                ->where('client_id', '=', $request->client_id)
                ->where('business_id', '=', $user->id)
                ->update([
                    "points" => $points + $request->points
                ]);
        } else {
            DB::table("client_has_points")->insert([
                "business_id" => $user->id,
                "client_id" => $request->client_id,
                "points" => $request->points
            ]);
        }
    }
}
