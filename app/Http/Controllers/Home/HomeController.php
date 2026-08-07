<?php
declare(strict_types=1);

namespace App\Http\Controllers\Home;

use App\Http\Controllers\Controller;
use Illuminate\Routing\Attributes\Controllers\Middleware;

#[Middleware('auth')]
final class HomeController extends Controller
{
    public function home()
    {
        return view('home.home');
    }
}
