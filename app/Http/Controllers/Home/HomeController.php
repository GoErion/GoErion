<?php
declare(strict_types=1);

namespace App\Http\Controllers\Home;

use App\Http\Controllers\Controller;

final class HomeController extends Controller
{
    public function home()
    {
        return view('home.home');
    }
}
