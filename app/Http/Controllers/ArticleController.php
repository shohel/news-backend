<?php

namespace App\Http\Controllers;

use App\Models\News;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;

class ArticleController extends Controller
{
    public function getArticles(Request $request){

        $articles = News::query();

        if ( Auth::check() ) {
            $preference = json_decode($request->user()->preference, true);
            $preferred_authors = (array) Arr::get($preference, 'authors');
            $preferred_source = (array) Arr::get($preference, 'sources');

            /**
             * Get news from preferred authors and sources
             */
            if ( count($preferred_authors) || count( $preferred_source ) ) {
                $articles = $articles->where(function ($query) use ($preferred_authors, $preferred_source) {
                    $query->whereHas('author', function ($query) use ($preferred_authors) {
                        $query->whereIn('authors.id', $preferred_authors);
                    })->orWhereHas('source', function ($query) use ($preferred_source) {
                        $query->whereIn('sources.id', $preferred_source);
                    });
                });
            }

        }


        $articles = $articles->with('source')->orderBy('published_at', 'desc')
            ->paginate(10, ['*'], 'page');


        return response()->json([
            'status' => true,
            'results' => $articles
        ]);
    }
}
