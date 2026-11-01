<?php

namespace App\Http\Controllers;

use App\Models\Quote;
use Illuminate\Contracts\View\View;

class QuoteController extends Controller
{
    public function show(Quote $quote): View
    {
        $comments = $quote->comments()->approved()->with('user')->get();

        return view('quotes.show', [
            'quote' => $quote,
            'comments' => $comments,
            'commentableType' => 'quote',
        ]);
    }
}
