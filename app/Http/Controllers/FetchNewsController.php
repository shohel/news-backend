<?php

namespace App\Http\Controllers;

use App\Models\Author;
use App\Models\News;
use App\Models\Source;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class FetchNewsController extends Controller
{

    public function getFromNewsAPI(){
        $newsAPIKey = env('NEWS_API');
        $newsAPIHttp = Http::withOptions(['verify' => false])->timeout(30)->get( "https://newsapi.org/v2/top-headlines?country=us&pageSize=30&apiKey={$newsAPIKey}");

        if ( ! $newsAPIHttp->ok() ) {
            return false;
        }

        $results = json_decode($newsAPIHttp->body(), true);

        foreach ( $results['articles'] as $article ) {
            $article_url = $article['url'];

            //Checking duplicate entries.
            $found_duplicate = News::query()->select('id')->where('url', $article_url)->first();
            if ( $found_duplicate ) {
                continue;
            }

            $newsData = [
                'title' => $article['title'],
                'slug' => Str::slug($article['title']),
                'description' => $article['description'],
                'url' => $article['url'],
                'url_to_image' => $article['urlToImage'],
                'content' => $article['content'],
                'published_at' => Carbon::parse($article['publishedAt']),
                'apiSource' => 'NewsAPI',
            ];

            /**
             * Attaching authors
             */
            $author_ids = [];

            if ( ! empty( $article['author'] ) ) {

                $authors = explode(',',str_replace( '|' ,',', $article['author']));
                //Removing empty value
                $authors = array_filter($authors);

                foreach ( $authors as $author ) {
                    $author_slug = Str::slug($author);
                    $author_model = Author::firstOrCreate(['author_slug' => $author_slug], ['author_slug' => $author_slug, 'author_name' => $author]);
                    $author_ids[] = $author_model->id;
                }

                $newsData['raw_author'] = implode(',', $authors);
            }

            /**
             * Adding source
             */

            $source_slug = Arr::get($article, 'source.id');
            $source_name = Arr::get($article, 'source.name');
            if ( empty( $source_slug ) ) {
                $source_slug = Str::slug($source_name);
            }

            $source_model = Source::firstOrCreate(['source_slug' => $source_slug], ['source_slug' => $source_slug, 'source' => $source_name]);
            $newsData['source_id'] = $source_model->id;

            $news = News::query()->create($newsData);
            if ( count( $author_ids ) ) {
                $news->author()->attach($author_ids);
            }
        }

        return response()->json(['success' => true]);

    }

}
