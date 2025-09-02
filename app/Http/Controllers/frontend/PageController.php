<?php

namespace App\Http\Controllers\frontend;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\{Category, ContactForm, Course, Testimonial, Blog};

class PageController extends Controller
{
    public function index()
    {
        $categories = Category::active()->latest()->get();
        $courses = Course::active()->latest()->get();
        $testimonials = Testimonial::active()->latest()->get();
        $blogs = Blog::active()->latest()->get();

        return view('frontend.index', compact('categories', 'courses', 'testimonials', 'blogs'));
    }

    public function about()
    {
        return view('frontend.about');
    }

    public function contact()
    {
        return view('frontend.contact');
    }

    public function contact_submit(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'email' => 'required|email',
            'phone' => 'nullable',
            'message' => 'required',
        ]);

        ContactForm::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'message' => $request->message,
            'ip_address' => $request->ip(),
        ]);

        return redirect()->back()->with('success', 'Response captured successfully');
    }
}
