<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FirebaseController extends Controller
{
    protected $database;

    public function __construct()
    {
        $this->middleware('auth:api');
        $this->database = app('firebase.database');
    }
    
    public function clientMakesRoom(Request $request){
        $user = Auth::user();
        if($user->usertype==1){
            $roomref = $this->database->getReference("rooms");
            $snapshot = $roomref->getSnapshot();
            if ($snapshot->hasChildren()) {
                $roomCount = count($snapshot->getValue());
            } else {
                $roomCount = 0;
            }
            $newRoomKey = $roomCount + 1;
            $newRoomRef = $roomref->getChild($newRoomKey);
            $roomData = [
                'clientid'=>$user->id,
                'businessid'=>intval($request->businessid),
                'date_created'=>now()
            ];
            $newRoomRef->set($roomData);
            return "success";
        }else{
            return "user is not a client";
        }
        
    }



    public function clientSendsMessage(Request $request){
        $user = Auth::user();
        if($user->usertype==1){
           
            
                $msgref = $this->database->getReference("messages");
                $snapshot = $msgref->getSnapshot();
                
                if ($snapshot->hasChildren()) {
                    $roomCount = count($snapshot->getValue());
                } else {
                    $roomCount = 0;
                }
                $newRoomKey = $roomCount + 1;
                $newRoomRef = $msgref->getChild($newRoomKey);
                $roomData = [
                    'roomid'=>intval($request->roomid),
                    'text'=>$request->text,
                    'sendertype'=>1,
                    'date_created'=>now()
                ];
                $newRoomRef->set($roomData);
                return "success";
        }else{
            return "logged in user isnt a client";
        }
        
    }


    public function businessSendsMessage(Request $request){
        $user = Auth::user();
        if($user->usertype==0){
            
                $msgref = $this->database->getReference("messages");
                $snapshot = $msgref->getSnapshot();
                
                if ($snapshot->hasChildren()) {
                    $roomCount = count($snapshot->getValue());
                } else {
                    $roomCount = 0;
                }
                $newRoomKey = $roomCount + 1;
        
                $newRoomRef = $msgref->getChild($newRoomKey);
                $roomData = [
                    'text'=>$request->text,
                    'roomid'=>intval($request->roomid),
                    'sendertype'=>0,
                    'date_created'=>now()
                ];
                $newRoomRef->set($roomData);
                return "success";

            
        }else{
            return "logged in user is not a business";
        }
       
    }
    public function getRoomsForBusiness(){
        $user = Auth::user();
        $query = $this->database->getReference('rooms')
        ->orderByChild('businessid')
        ->equalTo($user->id);
        $results = $query->getValue();
        return $results;
        
        
    }
    public function getRoomsForClient(){
        $user = Auth::user();
        $query = $this->database->getReference('rooms')
        ->orderByChild('clientid')
        ->equalTo($user->id);
        $results = $query->getValue();
        return $results;
        }
    public function getMessagesForClient($roomid){
        $user = Auth::user();
        $query = $this->database->getReference('messages')
        ->orderByChild('roomid')
        ->equalTo(intval($roomid));
        $results = $query->getValue();
        return $results;
    }
    public function getMessagesForBusiness($roomid){
        $query = $this->database->getReference('messages')
        ->orderByChild('roomid')
        ->equalTo(intval($roomid));
        $results = $query->getValue();
        return $results;

    }
}