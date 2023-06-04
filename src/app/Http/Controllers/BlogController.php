<?php

namespace App\Http\Controllers;

use App\Models\Blog;
use Illuminate\Http\Request;

class BlogController extends Controller
{
    public function getBlogs(){
        $blogs = Blog::all();
        return $blogs;

    }

    public function addBlog(Request $request){
        $blog = new Blog();
        $blog->title = $request->title;
        $blog->description = $request->description;
        $blog->authorid = $user->id;
        if($blog->save()){
            return response()->json([
                'status' => 'success',
                'message' => 'Blog added successfully'
            ], 200);
        }
        return response()->json([
            'status' => 'error',
            'message' => 'Error in adding blog'
        ], 404);
    }

    public function removeBlog($blogid){
        $blog = Blog::find($blogid);
        if($blog->delete()){
            return response()->json([
                'status' => 'success',
                'message' => 'Blog removed successfully'
            ], 200);
        }
        return response()->json([
            'status' => 'error',
            'message' => 'Error in removing blog'
        ], 404);
    }
}
