<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\KnowledgeBaseArticle;
use Illuminate\Http\Request;

class WelcomeController extends Controller
{


    public function index()
    {
        $knowledgeBase = KnowledgeBaseArticle::select('id', 'title', 'body', 'slug')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($item) {
                // Ensure content is a JSON string
                $item->body = is_string($item->body)
                    ? $item->body
                    : json_encode($item->body);
                return $item;
            });

        return Inertia::render('Welcome', [
            'knowledgeBase' => $knowledgeBase
        ]);
    }
}
