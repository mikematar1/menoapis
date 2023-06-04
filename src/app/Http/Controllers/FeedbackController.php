<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Feedback;
use App\Models\Testimonial;

class FeedbackController extends Controller
{
    public function getFeedbacks(){
        $feedbacks = Feedback::all();
        if($feedbacks){
            return response()->json([
                'status' => 'success',
                'data' => $feedbacks
            ], 200);
        }
        return response()->json([
            'status' => 'error',
            'message' => 'No feedbacks found'
        ], 404);
    }

    public function getTestimonials(){
        $testimonials = Testimonial::all();
        if ($testimonials) {
            return response()->json([
                'status' => 'success',
                'data' => $testimonials
            ], 200);
        }
        return response()->json([
            'status' => 'error',
            'message' => 'No testimonials found'
        ], 404);
    }

    public function makeFeedbackTestimonial($feedbackid){
        $feedback = Feedback::find($feedbackid);
        $testimonial = new Testimonial();
        $testimonial->senderid = $feedback->senderid;
        $testimonial->rating = $feedback->rating;
        $testimonial->comment = $feedback->comment;
        if($testimonial->save() && $feedback->delete()){
            return response()->json([
                'status' => 'success',
                'message' => 'Feedback converted to testimonial successfully'
            ], 200);
        }
        return response()->json([
            'status' => 'error',
            'message' => 'Error in converting feedback to testimonial'
        ], 404);
    }

    public function removeTestimonial($testimonialid){
        $testimonial = Testimonial::find($testimonialid);
        if($testimonial->delete()){
            return response()->json([
                'status' => 'success',
                'message' => 'Testimonial removed successfully'
            ], 200);
        }
        return response()->json([
            'status' => 'error',
            'message' => 'Error in removing testimonial'
        ], 404);
    }
}
