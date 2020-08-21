<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Quote;
use App\UserFeed;
use Auth;

class ProfileController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
    	$services = Quote::Services()->get();
    	$values = UserFeed::where('user_id', Auth::id())->first();
    	$countries = file_get_contents(public_path('js/countries.json'));

    	$data = [
    		'countries' => json_decode($countries),
    		'services' => $services,
            'values' => $values,
    		'is_new' => UserFeed::where('user_id',Auth::id())->count(),
    	];
    	return view('profile',$data);
    }

    public function store(Request $request)
    {

        $rules = [
            'trait' => 'required',
            'practice-area' => 'required',
            'yearsofexp' => 'required',
            //'num_of_lawyers_firm' => 'required',
            'mention_in_int_dir' => 'required',
            'additional_languages' => 'required',
            'biography'=>'required'
        ];

        if($request->input('trait')=='company'){
            $rules['num_of_employees'] = 'required|digits_between:1,99999';
        }

        if( @in_array('not-sure' , $request->input('practice-area'))){
            $rules['user-practice-area'] = 'required|string|max:255';
        }

        $validatedData = $request->validate($rules);

        $insert = [
		    	'trait' => $request->input('trait'), 
		    	'num_of_employees' => $request->input('num_of_employees'),
		    	'practice_area' => implode(',', $request->input('practice-area')),
		    	'user_practice_area' => $request->input('user-practice-area'),
		    	'yearsofexp' => $request->input('yearsofexp'),
		    	'num_of_lawyers_firm' => $request->input('num_of_lawyers_firm'),
		    	'mention_in_int_dir' => $request->input('mention_in_int_dir'),
		    	'additional_languages' => $request->input('additional_languages'),
		    	'biography' => $request->input('biography'),
		    	'user_id' => Auth::id(),
		    ];


        $result = UserFeed::updateOrCreate(['user_id' => Auth::id()],$insert);

        $request->session()->flash('save', 'true');

        return redirect('profile');

    }
}
