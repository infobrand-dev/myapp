<?php

namespace App\Modules\SocialMedia\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class SocialMediaController extends Controller
{
    public function index(): View
    {
        return view('socialmedia::index');
    }
}
