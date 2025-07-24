<?php

namespace App\Http\Controllers;

use App\Data\ContactData;
use App\Models\Contact;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ContactController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->get('search');
        
        $contacts = Contact::with('company')
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhereHas('company', function ($companyQuery) use ($search) {
                          $companyQuery->where('name', 'like', "%{$search}%");
                      });
                });
            })
            ->orderBy('name')
            ->paginate(20);
            
        return Inertia::render('contacts/Index', [
            'contacts' => [
                'data' => ContactData::collect($contacts->items()),
                'links' => $contacts->linkCollection()->toArray(),
                'meta' => [
                    'current_page' => $contacts->currentPage(),
                    'from' => $contacts->firstItem(),
                    'last_page' => $contacts->lastPage(),
                    'per_page' => $contacts->perPage(),
                    'to' => $contacts->lastItem(),
                    'total' => $contacts->total(),
                ],
            ],
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Contact $contact)
    {
        $contact->load('company', 'conversations');
        
        return Inertia::render('contacts/Show', [
            'contact' => ContactData::from($contact),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
