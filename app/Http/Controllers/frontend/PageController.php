<?php

namespace App\Http\Controllers\frontend;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Course;
use Illuminate\Http\Request;

class PageController extends Controller
{
    public function index()
    {
        $categories = Category::active()->latest()->get();
        $courses = Course::active()->latest()->get();
        return view('frontend.index', compact('categories', 'courses'));
    }
}
