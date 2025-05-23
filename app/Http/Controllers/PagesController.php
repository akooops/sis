<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use App\Models\Page;
use App\Models\Program;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class PagesController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $page = Page::where('slug', 'home')->first();
        if(!$page) abort(404);

        $banners = Banner::orderBy('order')->get();
        $programs = Program::latest()->get();

        return view('index', compact('page', 'banners', 'programs'));
    }

    public function page(Request $request)
    {
        return view('page');
    }

    public function articles(Request $request)
    {
        return view('articles');
    }

    public function article(Request $request)
    {
        return view('article');
    }

    public function albums(Request $request)
    {
        return view('albums');
    }

    public function album(Request $request)
    {
        return view('album');
    }

    public function events(Request $request)
    {
        return view('events');
    }

    public function event(Request $request)
    {
        return view('event');
    }

    public function program(Request $request)
    {
        return view('program');
    }

    public function grade(Request $request)
    {
        return view('grade');
    }
}
