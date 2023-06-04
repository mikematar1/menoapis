<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Deal;
use App\Models\Image;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use stdClass;

class DealController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }
    public function getDeals()
    {
        $user=Auth::user();
        $output = Deal::where("expiry_date", ">=", date("Y-m-d"))->paginate(10);
        $dealarray = array();
        for ($i = 0; $i < sizeof($output); $i++) {
            $temp = Deal::find($output[$i]['id']);
            $temp->views = $temp->views + 1;
            $temp->save();
            $favorited=DB::table("client_redeems_deal")->where("client_id","=",$user->id)->where("business_id","=",$temp->business_id)->exists();
            $images = Image::where("dealid","=",$temp->id)->get();
            $business=Business::where("user_id","=",$temp->business_id)->join("users","users.id","=","businesses.user_id")->first();
            $businessimages=Image::where("businessid","=",$temp->business_id)->get();
            $temp->images=$images;
            $temp->business_images = $businessimages;
            $ratingsum = DB::table('clientreviewbusiness')->where("business_id", "=", $temp->business_id)->sum("rating");
            $ratingnumber = DB::table('clientreviewbusiness')->where("business_id", "=", $temp->business_id)->count("*");
            $temp->business = $business;
            $temp->sum_of_ratings = $ratingsum;
            $temp->number_of_ratings = $ratingnumber;
            $temp->isfavorited = $favorited;
            array_push($dealarray, $temp);
        }
        return $dealarray;
    }

    public function getBusinessDeals($businessid)
    {
        $deals = Deal::where("business_id", "=", $businessid)->where("expiry_date", ">=", date("Y-m-d"))->get();
        $dealarray = array();
        foreach ($deals as $deal) {
            $images = Image::where("dealid", "=", $deal->id)->get();
            $temp = $deal;
            $temp->views = $temp->views + 1;
            $temp->save();
            $temp->images = $images;
            array_push($dealarray, $temp);
        }
        return $dealarray;
    }
    public function getDealsCount()
    {
        $count = Deal::count();
        return response()->json([
            'count' => $count
        ], 200);
    }
    public function getDealsPerCategory($categoryid)
    {
        $deals = Deal::join("businesses", "deals.business_id", "=", "businesses.id")
            ->where("businesses.category_id", "=", $categoryid)
            ->get();
        $dealarray = array();
        for ($i = 0; $i < sizeof($deals); $i++) {
            $temp = Deal::find($deals[$i]['id']);
            $end_date = $temp->expiry_date;
            $today = date("Y-m-d");
            if ($today < $end_date) {
                $images = Image::where("dealid", "=", $temp->id)->get();
                $total = $temp;
                $total->images = $images;
                array_push($dealarray, $total);
                $temp->views = $temp->views + 1;
                $temp->save();
            }
        }
        return $dealarray;
    }
    public function getFeaturedDeals()
    {
        $deals = Deal::all();
        $today = date("Y-m-d");
        $dealarray = array();
        foreach ($deals as $deal) {
            $end_date = $deal->expiry_date;
            if ($today < $end_date) {
                if ($deal->featured && $deal->date_featured) {
                    if ($deal->date_featured < $deal->featured) {
                        $images = Image::where("dealid", "=", $deal->id)->get();
                        $temp = $deal;
                        $deal->images = $images;
                        array_push($dealarray, $deal);
                        $temp->views = $temp->views + 1;
                        $temp->save();
                    }
                }
            }
        }
        return $dealarray;
    }
    public function dealSearch(Request $request)
    {
        $byname = Deal::where('title', 'LIKE', '%' . $request->keywords . '%')->get();
        $bydescription = Deal::where('description', 'LIKE', '%' . $request->keywords . '%')->get();
        $bycategory = Deal::join("businesses", "businesses.user_id", "=", "deals.businessid")
            ->join("categories", "categories.id", "=", "businesses.category_id")
            ->where("categories.name", 'LIKE', '%' . $request->keywords . '%')
            ->select("deals.*")
            ->get();
        $output = array();
        for ($i = 0; $i < sizeof($byname); $i++) {
            $temp = Deal::find($byname[$i]['id']);
            $end_date = $temp->expiry_date;
            $today = date("Y-m-d");
            if ($today < $end_date) {
                $temp->views = $temp->views + 1;
                $temp->save();
                $images = Image::where("dealid", "=", $temp->id)->get();
                $business = Business::find($temp->business_id);
                $temp->images = $images;
                $temp->business = $business;
                array_push($output, $temp);
            }
        }
        for ($i = 0; $i < sizeof($bydescription); $i++) {
            $temp = Deal::find($bydescription[$i]['id']);
            $end_date = $temp->expiry_date;
            $today = date("Y-m-d");
            if ($today < $end_date) {
                $temp->views = $temp->views + 1;
                $temp->save();
                $images = Image::where("dealid", "=", $temp->id)->get();
                $business = Business::find($temp->business_id);
                $temp->images = $images;
                $temp->business = $business;
                array_push($output, $temp);
            }
        }
        for ($i = 0; $i < sizeof($bycategory); $i++) {
            $temp = Deal::find($bycategory[$i]['id']);
            $end_date = $temp->expiry_date;
            $today = date("Y-m-d");
            if ($today < $end_date) {
                $temp->views = $temp->views + 1;
                $temp->save();
                $images = Image::where("dealid", "=", $temp->id)->get();
                $business = Business::find($temp->business_id);
                $temp->images = $images;
                $temp->business = $business;
                array_push($output, $temp);
            }
        }
        return $output;
    }
}
