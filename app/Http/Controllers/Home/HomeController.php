<?php
declare(strict_types=1);

namespace App\Http\Controllers\Home;

use App\Http\Controllers\Controller;
use App\Models\Company\Company;
use Illuminate\Http\Request;
use Illuminate\Routing\Attributes\Controllers\Middleware;

#[Middleware('auth')]
final class HomeController extends Controller
{
    public function home(Request $request)
    {
        $companies = $request->user()->companies()->get();
        return view('home.home',compact('companies'));
    }
}
