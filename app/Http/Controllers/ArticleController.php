<?php

namespace App\Http\Controllers;

use App\Models\Article;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ArticleController extends Controller
{
    /**
     * Retrieve all articles with paginate links.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $articles = Article::with('author')
            ->paginate($request->per_page);

        return response()->json([
            'articles' => $articles,
        ]);
    }

    /**
     * Store an article to the database.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $this->validateArticle($request);

        $article = new Article();
        $article->title = $request->title;
        $article->slug = Str::slug($request->title);
        $article->body = $request->body;
        $article->author_id = 1;
        $article->image = $this->uploadImage($request->file('image'));
        $article->save();

        return response()->json([
            'article' => $article,
            'message' => 'New article has been created.',
        ], 201);
    }

    /**
     * Retrieve the specified article.
     *
     * @param integer $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(int $id)
    {
        $article = Article::with('author')
            ->findOrFail($id);

        return response()->json([
            'article' => $article,
        ]);
    }

    /**
     * Update a specified article from the database.
     *
     * @param \Illuminate\Http\Request $request
     * @param integer $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, int $id)
    {
        $this->validateArticle($request, $id);

        $article = Article::findOrFail($id);
        $article->title = $request->title;
        $article->slug = Str::slug($request->title);
        $article->body = $request->body;

        if ($request->hasFile('image')) {
            Storage::delete("public/article-images/{$article->image}");
            $article->image = $this->uploadImage($request->file('image'));
        }

        $article->save();

        return response()->json([
            'article' => $article,
            'message' => 'Selected article has been updated.',
        ]);
    }

    /**
     * Delete the specified article from the database.
     *
     * @param integer $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(int $id)
    {
        $article = Article::findOrFail($id);
        Storage::delete("public/article-images/{$article->image}");
        $article->delete();

        return response()->json([
            'message' => 'Selected article has been deleted.',
        ]);
    }

    /**
     * Validate create or update article request.
     *
     * @param \Illuminate\Http\Request $request
     * @param integer $id
     * @return void
     */
    private function validateArticle(Request $request, int $id = null)
    {
        $uniqueTitle = $id
            ? "unique:articles,title,{$id}"
            : 'unique:articles,title';

        $request->validate([
            'title' => [
                'required', $uniqueTitle, 'string', 'min:5', 'max:190'
            ],
            'body'  => 'required|string|min:10|max:100000',
            'image' => 'required|image|mimes:jpeg,jpg,png,bmp',
        ]);
    }

    /**
     * Upload the image to the local disk.
     *
     * @param \Illuminate\Http\UploadedFile $image
     * @return string
     */
    private function uploadImage(UploadedFile $image)
    {
        $name = Str::random(32) . '.' . $image->extension();
        $image->storeAs('public/article-images', $name);

        return $name;
    }
}
