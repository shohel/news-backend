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

    public function testGetNews($source = ''){
        switch ( $source ) {
            case 'NewsAPI':
                $this->getFromNewsAPI();
                break;

            case 'Guardian':
                $this->getFromGuardian();
                break;
        }

    }

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

    public function getFromGuardian(){
        $apiKey = env('THE_GUARDIAN_API');
        $guardianAPIHttp = Http::withOptions(['verify' => false])->timeout(30)->get( "https://content.guardianapis.com/search", [
            'api-key' => $apiKey,
            'show-fields' => 'thumbnail,byline,trailText,headline',
            'page-size' => 30,
        ]);

        if ( ! $guardianAPIHttp->ok() ) {
            return false;
        }

        $results = json_decode($guardianAPIHttp->body(), true);

        /**
         * Getting Source model, for this API, it's The Guardian
         */
        $source_model = Source::firstOrCreate(['source_slug' => 'the-guardian'], ['source_slug' => 'the-guardian', 'source' => 'The Guardian']);

        foreach ( $results['response']['results'] as $article ) {
            $article_url = $article['webUrl'];

            //Checking duplicate entries.
            $found_duplicate = News::query()->select('id')->where('url', $article_url)->first();
            if ( $found_duplicate ) {
                continue;
            }

            $title = Arr::get($article, 'fields.headline');
            $description = Arr::get($article, 'fields.trailText');
            $thumbnail = Arr::get($article, 'fields.thumbnail');

            $newsData = [
                'title' => $title,
                'slug' => Str::slug($title),
                'description' => $description,
                'url' => $article_url,
                'url_to_image' => $thumbnail,
                'published_at' => Carbon::parse($article['webPublicationDate']),
                'apiSource' => 'TheGuardian',
            ];

            /**
             * Attaching authors
             */
            $author_ids = [];

            $byLineAuthors = Arr::get($article, 'fields.byline');
            if ( ! empty( $byLineAuthors ) ) {
                $authors = str_replace( ['(and)', '(earlier)'], '', $byLineAuthors );
                $authors = explode(',',str_replace( ['|', 'and', ';'] ,',', $authors));
                //Removing empty value
                $authors = array_filter($authors);

                foreach ( $authors as $author ) {
                    $author_slug = Str::slug($author);
                    $author_model = Author::firstOrCreate(['author_slug' => $author_slug], ['author_slug' => $author_slug, 'author_name' => $author]);
                    $author_ids[] = $author_model->id;
                }

                $newsData['raw_author'] = $byLineAuthors;
            }

            $newsData['source_id'] = $source_model->id;

            $news = News::query()->create($newsData);
            if ( count( $author_ids ) ) {
                $news->author()->attach($author_ids);
            }
        }

        return response()->json(['success' => true]);

    }

}
