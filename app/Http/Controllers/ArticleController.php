<?php

namespace App\Http\Controllers;

use App\Models\Author;
use App\Models\News;
use App\Models\Source;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ArticleController extends Controller
{
    public function getArticles(Request $request){

        $articles = News::query();
        $authors = array_filter(explode(',', $request->authors));
        $sources = array_filter(explode(',', $request->sources));

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

        if ( count( $authors ) ) {
            $articles = $articles->whereHas('author', function ($query) use ($authors) {
                $query->whereIn('authors.id', $authors);
            });
        }

        if ( count( $sources ) ) {
            $articles = $articles->whereHas('source', function ($query) use ($sources) {
                $query->whereIn('sources.id', $sources);
            });
        }


        $articles = $articles->with('source')->orderBy('published_at', 'desc')
            ->simplePaginate(10, ['*'], 'page');

        return response()->json([
            'status' => true,
            'results' => $articles
        ]);
    }

    public function getAuthors( Request $request ){
        $authors = Author::query();
        if ( $request->user() ) {
            $author_ids = $request->user()->preferred_author_ids;

            if ( is_array( $author_ids ) && count($author_ids) ) {
                $authors = $authors->whereIn('id', $author_ids);
            }
        }
        if ( ! empty( $request->search ) ) {
            $authors = $authors->where( 'author_name', 'LIKE', "%{$request->search}%" );
        }
        $authors = $authors->orderBy('author_name')->simplePaginate(10, ['*']);

        return response()->json([
            'status' => true,
            'results' => $authors
        ]);
    }


    public function getSources( Request $request ){
        $sources = Source::query();
        if ( $request->user() ) {
            $source_ids = $request->user()->preferred_source_ids;

            if ( is_array( $source_ids ) && count($source_ids) ) {
                $sources = $sources->whereIn('id', $source_ids);
            }
        }
        if ( ! empty( $request->search ) ) {
            $sources = $sources->where( 'source', 'LIKE', "%{$request->search}%" );
        }
        $sources = $sources->orderBy('source')->simplePaginate(10, ['*'], 'page');;

        return response()->json([
            'status' => true,
            'results' => $sources
        ]);
    }


}
