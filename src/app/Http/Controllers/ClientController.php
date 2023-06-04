<?php

namespace App\Http\Controllers;

use App\Models\Business;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Client;
use App\Models\Deal;
use Google\Cloud\Storage\StorageClient;
use App\Models\Image;
use App\Models\PointsItem;
use Illuminate\Support\Facades\Hash;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class ClientController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    public function getClients()
    { // url should be getusers?page=(pagenumber)
        $users = User::join('clients', 'users.id', '=', 'clients.user_id')->paginate(10);
        if ($users) {
            return response()->json([
                'status' => 'success',
                'data' => $users
            ], 200);
        }
        return response()->json([
            'status' => 'error',
            'message' => 'No users found'
        ], 404);
    }

    public function getClientsCount()
    {
        $count = Client::count();
        return response()->json([
            'count' => $count
        ], 200);
    }

    public function reviewBusiness(Request $request)
    { // businessid/rating/comment
        $user = Auth::user();
        DB::table('clientreviewbusiness')->insert([
            'business_id' => $request->businessid,
            'client_id' => $user->id,
            'rating' => $request->rating,
            'comment' => $request->comment
        ]);
    }


    public function getInformation()
    {
        $user = Auth::user();
        $client = User::join('clients', 'clients.user_id', '=', 'users.id')->where("users.id", "=", $user->id)->first();
        $savings = DB::table("client_redeems_deal")
            ->where("client_id", "=", $client->user_id)
            ->where("status", "=", 1)
            ->sum("saved");
        return response()->json([
            "client" => $client,
            "savings" => $savings
        ]);
    }



    public function editInformation(Request $request)
    {
        $user = Auth::user();
        $client = Client::where("user_id", $user->id)->first();
        $user_info = User::find($user->id);
        if ($request->has("full_name")) {
            $client->full_name = $request->full_name;
        }
        if ($request->has("gender")) {
            $client->gender = $request->gender;
        }
        if ($request->has("dob")) {
            $client->dob = $request->dob;
        }
        if ($request->has("username")) {
            $user_info->username = $request->username;
        }
        if ($request->has("password")) {
            $user_info->password = Hash::make($request->password);
        }

        if ($request->has("number")) {
            $user_info->number = $request->number;
        }
        if ($request->has("email")) {
            $user_info->email = $request->email;
        }
        if ($user_info->save() && $client->save()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Client information updated successful',
            ], 200);
        }
        return response()->json([
            'status' => 'error',
            'message' => 'Client information update failed',
        ], 500);
    }

    public function businessSearch($search)
    { //by name
        $businesses = Business::where('name', 'LIKE', '%' . $search . '%')->get();
        return response()->json($businesses);
    }
    public function favnofavBusiness($businessid)
    {
        $user = Auth::user();
        $favorite = DB::table('clientfavoritebusiness')
            ->where('client_id', $user->id)
            ->where('business_id', $businessid)
            ->exists();
        if ($favorite) {
            DB::table('clientfavoritebusiness')
                ->where('client_id', $user->id)
                ->where('business_id', $businessid)
                ->delete();
            return "favorite deleted";
        } else {
            DB::table('clientfavoritebusiness')->insert([
                "business_id" => $businessid,
                "client_id" => $user->id
            ]);
            return "Favorite added";
        }
    }
    public function getFavBusinesses()
    {
        $user = Auth::user();
        $businesses = DB::table('clientfavoritebusiness')
            ->join('businesses', 'businesses.user_id', '=', 'clientfavoritebusiness.business_id')
            ->join('users', 'users.id', '=', 'businesses.user_id')
            ->where('client_id', $user->id)
            ->get();
        foreach($businesses as $business){
            $reviews = DB::table("clientreviewbusiness")->where("business_id","=",$business->user_id)->get();
            $business->reviews = $reviews;
        }
        return response()->json($businesses);
    }
    public function getClient($clientid)
    {
        $client = Client::find($clientid)->join("users", "users.id", "=", "clients.user_id")->first();
        return $client;
    }
    public function getClientAndDeal($dealid, $userid, $redeemid)
    {
        $client = $this->getClient($userid);
        $deal = Deal::find($dealid);
        $redeem_object = DB::table("client_redeems_deal")->where("id", "=", $redeemid)->first();
        DB::table("client_redeems_deal")->where("id", "=", $redeemid)->update([
            "status" => 1
        ]);
        return response()->json([
            "client" => $client,
            "deal" => $deal,
            "redeem object" => $redeem_object
        ]);
    }

    public function redeemDeal($dealid)
    {
        $user = Auth::user();

        $rowCount = DB::table('client_redeems_deal')->count();
        $newid = $rowCount + 1;


        $deal = Deal::find($dealid);
        $saved = $deal->old_price - $deal->new_price;
        DB::table("client_redeems_deal")->insert([
            "client_id" => $user->id,
            "deal_id" => $dealid,
            "status" => 0,
            "saved" => $saved
        ]);
        return response()->json([
            "redeemid"=>$newid
        ]);
    }
    public function getBusinessDeals($businessid)
    {
        $user = User::find($businessid);

        if ($user->usertype == 0) {
            $deals = Deal::where("business_id", "=", $businessid)->get();
            $dealarray = array();
            for ($i = 0; $i < sizeof($deals); $i++) {
                $deal = $deals[$i];
                $end_date = $deal->expiry_date;
                $today = date("Y-m-d");
                if ($today > $end_date) {
                    $images = Image::where("dealid", "=", $deal->id)->get();
                    $deal->images = $images;
                    array_push($dealarray, $deal);
                }
            }
            return json_encode($dealarray);
        } else {
            return "Fails";
        }
    }
    public function getReviews()
    {
        $user = Auth::user();
        $reviews = DB::table("userreviewBusiness")->where("clientid", "=", $user->id)->get();
        return $reviews;
    }
    public function getTransactions()
    {
        $user = Auth::user();
        $redeems = DB::table("client_redeems_deal")->where("client_id", "=", $user->id)->get();
        return $redeems;
    }
    public function getPointsItemsPerBusiness()
    {
        $output = array();
        $user = Auth::user();
        $pointsperbusiness = array();
        $points = DB::table("client_has_points")
            ->where("client_id", "=", $user->id)
            ->get();
        foreach ($points as $point) {
            $pointsperbusiness[$point->business_id] = $point->points;
        }
        $items = PointsItem::select("business_id")->groupBy("business_id")->get();
        foreach ($items as $item) {
            $businessid = $item["business_id"];
            $businessname = Business::find($businessid)->first()->name;
            $pointitems = PointsItem::where("business_id", "=", $businessid)->get();
            $temparray = array();
            $temparray["points"] = $pointsperbusiness[$businessid];
            $temparray["items"] = $pointitems;
            $output[$businessname] = $temparray;
        }
        return $output;
    }
}
