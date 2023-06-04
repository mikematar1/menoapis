<?php

namespace App\Http\Controllers;

use App\Models\Image;
use DateTime;
use Google\Cloud\Storage\StorageClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ImageController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    public function addImagesForBusiness(Request $request)
    {
        $storage = new StorageClient([
            'projectId' => 'meno-a6fd9',
            'keyFilePath' => 'C:\Users\miche\Downloads\meno_folder\MENO\APIs\meno-a6fd9-firebase-adminsdk-dv2i6-bbf9790bcf.json'
        ]);

        $bucket = $storage->bucket('meno-a6fd9.appspot.com');
        $images = $request->business_images;
        for ($i = 0; $i < sizeof($images); $i++) {
            $base64Image = $images[$i];
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
                "imageurl" => $url
            ]);
        }

        return response()->json([
            'message' => 'Successfully added images!'
        ], 201);
    }
    public function editImagesForBusiness(Request $request)
    { //images removed / images addes
        $storage = new StorageClient([
            'projectId' => 'meno-a6fd9',
            'keyFilePath' => 'C:\Users\marc issa\Desktop\Meno\MENO\APIs\meno-a6fd9-firebase-adminsdk-dv2i6-bbf9790bcf.json'
        ]);
        $imagestoberemoved = $request->images_removed; //ids
        $imagestobeadded = $request->images_added; //base64
        $bucket = $storage->bucket('meno-a6fd9.appspot.com');
        if ($request->has("images_added")) {
            for ($i = 0; $i < sizeof($imagestobeadded); $i++) {
                $base64Image = $imagestobeadded[$i]["imageurl"];
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
                    $url = $image->image_url;
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

        $all_images = Image::where('businessid', '=', $request->businessid)->get();
        return response()->json([
            'images' => $all_images
        ]);
    }
    public function getImagesForBusiness($businessid)
    {
        $images = Image::where('businessid', '=', $businessid)->get();
        return $images;
    }


    public function addImagesForDeal(Request $request)
    {
        $storage = new StorageClient([
            'projectId' => 'meno-a6fd9',
            'keyFilePath' => 'C:\Users\miche\Downloads\meno_folder\MENO\APIs\meno-a6fd9-firebase-adminsdk-dv2i6-bbf9790bcf.json'
        ]);

        $bucket = $storage->bucket('meno-a6fd9.appspot.com');
        $images = $request->deal_images;
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
                "businessid" => $request->dealid,
                "imageurl" => $url
            ]);
        }

        return response()->json([
            'message' => 'Successfully added images!'
        ], 201);
    }
    public function editImagesForDeal(Request $request)
    { //images removed / images addes
        $storage = new StorageClient([
            'projectId' => 'meno-a6fd9',
            'keyFilePath' => 'C:\Users\miche\Downloads\meno_folder\MENO\APIs\meno-a6fd9-firebase-adminsdk-dv2i6-bbf9790bcf.json'
        ]);
        $imagestoberemoved = $request->images_removed; //ids
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
                    "businessid" => $request->dealid,
                    "imageurl" => $url
                ]);
            }
        }

        if ($request->has("images_removed")) {
            $images = $request->images_removed;
            $folder_name = "dealimages/";
            if (!empty($images)) {
                for ($i = 0; $i < sizeof($images); $i++) {
                    $imageid = $images[$i];
                    $image = Image::find($imageid);
                    $url = $image->image_url;
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

        $all_images = Image::where('businessid', '=', $request->dealid)->get();
        return response()->json([
            'images' => $all_images
        ]);
    }
    public function getImagesForDeal($dealid)
    {
        $images = Image::where('businessid', '=', $dealid)->get();
        return $images;
    }
}
