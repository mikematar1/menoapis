<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Google\Cloud\Storage\StorageClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CategoryController extends Controller
{
    public function getCategories()
    {
        $categories = Category::all();
        if ($categories->count() > 0) {
            return response()->json([
                "status" => "success",
                "data" => $categories
            ]);
        }
        return response()->json([
            "status" => "error",
            "message" => "No categories found"
        ]);
    }

    public function getCategoriesWithCount()
    {
        $results = DB::table('categories')
            ->leftJoin('businesses', 'categories.id', '=', 'businesses.category_id')
            ->select('categories.id', 'categories.name', DB::raw('count(businesses.user_id) as business_count'), 'categories.logo_url')
            ->groupBy('categories.id', 'categories.name', 'categories.logo_url')
            ->get();

        if ($results->count() > 0) {
            return response()->json([
                "status" => "success",
                "data" => $results
            ]);
        }
        return response()->json([
            "status" => "error",
            "message" => "No categories found"
        ]);
    }

    public function addCategory(Request $request)
    { #name and logourl
        $storage = new StorageClient([
            'projectId' => 'meno-a6fd9',
            'keyFilePath' => 'C:\Users\marc issa\Desktop\Meno\MENO\APIs\meno-a6fd9-firebase-adminsdk-dv2i6-bbf9790bcf.json'
        ]);

        $bucket = $storage->bucket('meno-a6fd9.appspot.com');
        $base64Image = $request->logourl;
        $imageData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $base64Image));
        $filename = uniqid() . '.png';
        $folderPath = 'catimages/';
        $object = $bucket->upload($imageData, [
            'name' => $folderPath . $filename
        ]);
        $url = $object->signedUrl(new \DateTime('+100 years'));
        $category = new Category();
        $category->name = $request->name;
        $category->logo_url = $url;
        if ($category->save()) {
            return response()->json([
                "status" => "success",
                "data" => $category
            ]);
        }
        return response()->json([
            "status" => "error",
            "message" => "Error in adding category"
        ]);
    }
    public function removeCategory($categoryid)
    {
        $category = Category::find($categoryid);
        if ($category->delete()) {
            return response()->json([
                "status" => "success",
                "message" => "Category deleted successfully"
            ]);
        }
        return response()->json([
            "status" => "error",
            "message" => "Error in deleting category"
        ]);
    }
    public function updateCategory(Request $request)
    { //request should have id and new_name
        $category = Category::where("id", $request->id)->exists();
        if ($category) {
            if ($category->update(["name" => $request->new_name])) {
                return response()->json([
                    "status" => "success",
                    "message" => "Category updated successfully"
                ]);
            }
            return response()->json([
                "status" => "error",
                "message" => "Error in updating category"
            ]);
        }
        return response()->json([
            "status" => "error",
            "message" => "Category not found"
        ]);
    }
}
