<?php

namespace App\Http\Controllers;

use App\Models\News;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ArticleController extends Controller
{
    public function getArticles(Request $request){
        $user = Auth::user();

        $articles = News::query()->with('source')->orderBy('published_at', 'desc')
            ->paginate(10, ['*'], 'page');


        return response()->json([
            'status' => true,
            'results' => $articles
        ]);
    }
}
