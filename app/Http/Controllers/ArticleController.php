<?php

namespace App\Http\Controllers;

use App\Models\Author;
use App\Models\News;
use App\Models\Source;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

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

    public function filterableFields( Request $request ){
        $authors = Author::query();
        if ( $request->user() ) {
            $author_ids = $request->user()->preferred_author_ids;

            if ( is_array( $author_ids ) && count($author_ids) ) {
                $authors = $authors->whereIn('id', $author_ids);
            }
        }
        $authors = $authors->orderBy('author_name', 'asc')->get();

        $sources = Source::query();
        if ( $request->user() ) {
            $source_ids = $request->user()->preferred_source_ids;

            if ( is_array( $source_ids ) && count($source_ids) ) {
                $sources = $sources->whereIn('id', $source_ids);
            }
        }
        $sources = $sources->orderBy('source', 'asc')->get();

        return response()->json([
            'status' => true,
            'results' => [
                'authors' => $authors,
                'sources' => $sources,
            ]
        ]);
    }


}
