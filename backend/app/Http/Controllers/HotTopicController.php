<?php

namespace App\Http\Controllers;

use App\Models\Bookmark;
use App\Models\Comment;
use App\Models\CommentReport;
use App\Models\Like;
use App\Models\ThisCase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class HotTopicController extends Controller
{
    public function index(Request $request){
        $queryText = $request->input('query');
        if ($queryText) {
            // Get the list of columns in the 'cases' table
            $columns = Schema::getColumnListing('cases');

            // Create a query to search through the string columns
            $cases = ThisCase::query();

            // Loop through columns and apply where clauses
            foreach ($columns as $column) {
                if (Schema::getColumnType('cases', $column) == 'string') {
                    $cases->orWhere($column, 'LIKE', '%' . $queryText . '%');
                }
            }

            // Get the results
            $cases = $cases->get();
        } else {
            $cases = ThisCase::all();
        }

        $year_start = $request->input('year_start');
        $year_end = $request->input('year_end');
        $month_start = $request->input('month_start');
        $month_end = $request->input('month_end');
        $unprocessed = ($request->input('unprocessed') == 'on') ? true : false;
        $processed = ($request->input('processed') == 'on') ? true : false;
        $finished = ($request->input('finished') == 'on') ? true : false;

        $cases = $cases -> filter(function($case) use ($year_start, $year_end, $month_start, $month_end){
            $date = $case->created_at;
            $year = $date->format('Y');
            $month = $date->format('m');
            if ($year_start && $year < $year_start) return false;
            if ($year_end && $year > $year_end) return false;
            if ($month_start && $month < $month_start) return false;
            if ($month_end && $month > $month_end) return false;
            return true;
        });
        // dump($cases);
        return view('hottopic.index', ['cases' => $cases]);
    }

    public function detail($case_number){
        $case = ThisCase::where('case_number', $case_number)->first();
        return view('hottopic.detail', ['case' => $case]);
    }

    public function addComment(Request $request){
        $case_number = $request->input('case_number');
        $comment = $request->input('content');

        if (!auth()->check()){
            return redirect()->back()->with('error', 'You need to login to add a comment.');
        }

        Comment::create([
            'content' => $comment,
            'case_id' => ThisCase::where('case_number', $case_number)->first()->id,
            'user_id' => auth()->user()->id
        ]);
        return redirect()->back()->with('success', 'Comment added successfully.');
    }

    public function click_like(Request $request){
        $case_number = $request->input('case_number');
        $case = ThisCase::where('case_number', $case_number)->first();
        if(!$case){
            return redirect()->back();
        }
        $check = Like::where('user_id', auth()->user()->id)->where('case_id', $case->id)->first();

        if($check){
            //delete the like
            Like::where('user_id', auth()->user()->id)->where('case_id', $case->id)->delete();
            return redirect()->back();
        }

        //add the like
        Like::create([
            'user_id' => auth()->user()->id,
            'case_id' => $case->id
        ]);
        return redirect()->back();
    }

    public function click_bookmark(Request $request){
        $case_number = $request->input('case_number');
        $case = ThisCase::where('case_number', $case_number)->first();
        if(!$case){
            return redirect()->back();
        }

        $check = Bookmark::where('user_id', auth()->user()->id)->where('case_id', $case->id)->first();

        if($check){
            //delete the bookmark
            Bookmark::where('user_id', auth()->user()->id)->where('case_id', $case->id)->delete();
            return redirect()->back();
        }

        //add the bookmark
        Bookmark::create([
            'user_id' => auth()->user()->id,
            'case_id' => $case->id
        ]);

        return redirect()->back();
    }

    public function report(Request $request){
        $comment_id = $request->input('comment_id');
        $comment = Comment::find($comment_id);
        if(!$comment){
            return redirect()->back();
        }

        $check = CommentReport::where('user_id', auth()->user()->id)->where('comment_id', $comment_id)->first();

        if($check){
            return redirect()->back();
        }

        //add the report
        CommentReport::create([
            'user_id' => auth()->user()->id,
            'comment_id' => $comment_id
        ]);

        return redirect()->back();
    }

    public function bookmarks(Request $request)
    {
        $cases = auth()->user()->bookmarks;
        // for each cases loops through the bookmarks and get the case
        $cases = $cases->map(function ($bookmark) {
            return ThisCase::find($bookmark->case_id);
        });
        // dd($cases);

        $year_start = $request->input('year_start');
        $year_end = $request->input('year_end');
        $month_start = $request->input('month_start');
        $month_end = $request->input('month_end');
        $unprocessed = ($request->input('unprocessed') == 'on') ? true : false;
        $processed = ($request->input('processed') == 'on') ? true : false;
        $finished = ($request->input('finished') == 'on') ? true : false;

        $cases = $cases->filter(function ($case) use ($year_start, $year_end, $month_start, $month_end) {
            $date = $case->created_at;
            $year = $date->format('Y');
            $month = $date->format('m');
            if ($year_start && $year < $year_start) return false;
            if ($year_end && $year > $year_end) return false;
            if ($month_start && $month < $month_start) return false;
            if ($month_end && $month > $month_end) return false;
            return true;
        });
        // dump($cases);
        return view('hottopic.index', ['cases' => $cases]);
    }


}
