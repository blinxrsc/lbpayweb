<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TermsOfService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TermsOfServiceController extends Controller
{
    public function index()
    {
        $terms = TermsOfService::orderBy('created_at', 'desc')->get();
        return view('admin.terms.index', compact('terms'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string', // CKEditor sends HTML strings
        ]);

        TermsOfService::create([
            'title' => $request->title,
            'slug' => Str::slug($request->title),
            'content' => $request->content,
            'version' => 1,
            'is_active' => true,
        ]);

        return back()->with('success', 'New policy created successfully.');
    }

    public function update(Request $request, TermsOfService $term)
    {
        $request->validate([
            'content' => 'required|string',
        ]);
        // Update the content from CKEditor
        $term->content = $request->content;

        // If 'major_update' is checked, increment version to force users to re-agree
        if ($request->boolean('major_update')) {
            $term->version++;
        }
        // Only update status if the input exists in the request
        if ($request->has('is_active')) {
            $term->is_active = $request->boolean('is_active');
        }
        $term->save();

        return back()->with('success', 'Policy v' . $term->version . ' updated successfully.');
    }
}