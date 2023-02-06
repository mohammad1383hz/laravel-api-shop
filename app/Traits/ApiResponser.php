<?php

namespace App\Traits ;

trait ApiResponser
{

        public function succesresponse($data,$massage= null,$code = 200){
            return response()->json([
                'statuse'=>$code,
                'data'=>$data,
                'massage'=>$massage
            ]);
        }
        public function errorresponse($massage= null,$code ){
            return response()->json([
                'statuse'=>$code,
                
                'massage'=>$massage
            ]);
        }
}
