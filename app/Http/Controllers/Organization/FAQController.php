<?php

namespace App\Http\Controllers\Organization;

use App\Http\Controllers\Controller;
use App\Models\FAQ;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FAQController extends Controller
{
    //create faq
    public function createFaq(Request $request)
    {
        $validator = Validator::make($request->all(),[
            'question'=>'required|string|max:255',
            'answer'=>'required|string',
        ]);
        if ($validator->fails()) {
            return response()->json(['status'=>false, 'message'=> $validator->errors()],422);
        }
        $faq = FAQ::create([
            'question'=>$request->question,
            'answer'=>$request->answer,
        ]);
        $faq->save();

        return response()->json(['status'=>true, 'message'=>$faq],201);
    }
    //update faq
    public function updateFaq(Request $request, $id)
    {
        $faq = FAQ::find($id);

        if (!$faq) {
            return response()->json(['status'=>false, 'message'=> 'FAQ Not Found'], 200);
        }

        $validator = Validator::make($request->all(),[
            'question'=>'nullable|string|max:255',
            'answer'=>'nullable|string',
        ]);

        $data = $validator->validated();
        $faq->question = $data['question'] ?? $faq->question;
        $faq->answer = $data['answer'] ?? $faq->answer;
        $faq->save();

        return response()->json(['status'=>true, 'message'=>$faq],201);
    }
    // faq list get
    public function listFaq(Request $request)
    {
        $faq = FAQ::paginate(10);

        return response()->json(['status'=>true, 'data' =>$faq],201);
    }
    //delete faq
    public function deleteFaq($id)
    {
        $faq = Faq::find($id);
        if (!$faq) {
            return response()->json(['status'=>true,'message'=>'Data not found!'],200);
        }
        $faq->delete();

        return response()->json([
            'status' => true,
            'message' => 'FAQ deleted successfully.',
        ], 200);
    }

}
