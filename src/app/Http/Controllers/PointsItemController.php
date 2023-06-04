<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\PointsItem;
use DateTime;
use Google\Cloud\Storage\StorageClient;
use Illuminate\Http\Request;

class PointsItemController extends Controller
{
    public function add(Request $request){
        $base64image = $request->image;
        $storage = new StorageClient([
            'projectId' => 'meno-a6fd9',
            'keyFilePath' => 'C:\Users\miche\Desktop\Projects\MENO\APIs\meno-a6fd9-firebase-adminsdk-dv2i6-bbf9790bcf.json'
        ]);

        $bucket = $storage->bucket('meno-a6fd9.appspot.com');
        $imageData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $base64image));
        $filename = uniqid() . '.png';
        $folderPath = 'pointsimages/';
        $object = $bucket->upload($imageData, [
        'name' => $folderPath . $filename
        ]);
        $expiration = new DateTime('+100 years');
        $url = $object->signedUrl($expiration);

        PointsItem::create([
            "business_id"=>$request->business_id,
            "item_name"=>$request->item_name,
            "item_description"=>$request->item_description,
            "item_imageurl"=>$url,
            "item_points"=>$request->item_points

        ]);
    }
    public function edit(Request $request){
        $item = PointsItem::find($request->itemid);
        if($request->has("item_name")){
            $item->item_name=$request->item_name;
        }
        if($request->has("item_description")){
            $item->item_description = $request->item_description;
        }
        if($request->has("item_points")){
            $item->item_points = $request->item_points;
        }
        if($request->has("image")){
            $base64image = $request->image;
            $storage = new StorageClient([
                'projectId' => 'meno-a6fd9',
                'keyFilePath' => 'C:\Users\miche\Downloads\meno_folder\MENO\APIs\meno-a6fd9-firebase-adminsdk-dv2i6-bbf9790bcf.json'
            ]);

            $bucket = $storage->bucket('meno-a6fd9.appspot.com');
            $imageData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $base64image));
            $filename = uniqid() . '.png';
            $folderPath = 'pointsimages/';
            $object = $bucket->upload($imageData, [
            'name' => $folderPath . $filename
            ]);
            $expiration = new DateTime('+100 years');
            $url = $object->signedUrl($expiration);
            $oldurl = $item->item_imageurl;
            if (preg_match('/([\w]+.(png|jpg|jpeg|gif))/', $oldurl, $matches)) {
                $filename = $matches[1];
                $overallpath = $folderPath.$filename;
                $object = $bucket->object($overallpath);
                $object->delete();
                $item->item_imageurl = $url;
            }
            $item->save();
        }

    }
    public function remove($itemid){
        PointsItem::find($itemid)->delete();
    }
}
