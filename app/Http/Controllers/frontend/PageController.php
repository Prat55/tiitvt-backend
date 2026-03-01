<?php

namespace App\Http\Controllers\frontend;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\{Category, ContactForm, Course, Testimonial, Blog, HeroSlider};

class PageController extends Controller
{
    public function index()
    {
        $heroSliders = HeroSlider::active()->orderBy('sort_order')->latest()->get();
        $categories = Category::active()->withCount('courses')->orderByDesc('courses_count')->take(10)->get();
        $courses = Course::active()->withCount('students')->orderByDesc('students_count')->take(10)->get();
        $testimonials = Testimonial::active()->latest()->take(10)->get();
        $blogs = Blog::active()->latest()->take(10)->get();

        return view('frontend.index', compact('heroSliders', 'categories', 'courses', 'testimonials', 'blogs'));
    }

    public function coursesIndex(Request $request)
    {
        $query = Course::active()->with('categories')->withCount('students');

        if ($request->filled('search')) {
            $query->where('name', 'like', "%{$request->search}%");
        }

        if ($request->filled('category')) {
            $query->whereHas('categories', fn($q) => $q->where('categories.id', $request->category));
        }

        $courses    = $query->orderByDesc('students_count')->paginate(12);
        $categories = Category::active()->withCount('courses')->orderByDesc('courses_count')->get();

        return view('frontend.courses.index', compact('courses', 'categories'));
    }

    public function about()
    {
        $testimonials = Testimonial::active()->latest()->take(10)->get();
        return view('frontend.about', compact('testimonials'));
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

    public function blogIndex()
    {
        if (request()->has('tag')) {
            $blogs = Blog::active()->whereHas('tags', function ($query) {
                $query->where('slug', request()->tag);
            })->latest()->paginate(6);
        } else {
            $blogs = Blog::active()->latest()->paginate(6);
        }

        return view('frontend.blog.index', compact('blogs'));
    }

    public function blogShow($slug)
    {
        $blog = Blog::with('tags')->where('slug', $slug)->where('is_active', true)->firstOrFail();
        return view('frontend.blog.show', compact('blog'));
    }
}
