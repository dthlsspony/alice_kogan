<?php

use App\Http\Controllers\ArticleController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\FrontendController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\QuoteController;
use App\Http\Controllers\QuoteSearchController;
use Illuminate\Support\Facades\Route;

// Frontend pages
Route::get('/', [FrontendController::class, 'home'])->name('home');
Route::get('/search', [FrontendController::class, 'search'])->name('search.results');
Route::get('/books', [FrontendController::class, 'bibliography'])->name('books.index');
Route::get('/books/{book}', [FrontendController::class, 'book'])->name('books.show');

// Articles
Route::get('/articles', [ArticleController::class, 'index'])->name('articles.index');
Route::get('/articles/{article:slug}', [ArticleController::class, 'show'])->name('articles.show');

// Single quote page (with comments)
Route::get('/quotes/{quote}', [QuoteController::class, 'show'])->name('quotes.show');

// Comments (authenticated users only)
Route::post('/comments', [CommentController::class, 'store'])
    ->middleware('auth')
    ->name('comments.store');

// Authentication
Route::middleware('guest')->group(function () {
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.attempt');
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:5,1')
        ->name('login.attempt');
});
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

// Static pages (created in the admin panel) — fallback for any unmatched path,
// including nested slugs like about/author.
Route::fallback([PageController::class, 'show'])->name('pages.show');

// API
Route::get('/api/quotes', [QuoteSearchController::class, 'search'])->name('api.quotes')->middleware('throttle:60,1');
