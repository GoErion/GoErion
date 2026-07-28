<?php
declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\RegisterUser;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;

final class RegisterController extends Controller
{
    public function store(RegisterRequest $request,RegisterUser $action)
    {
        $action->handle($request->validated());
    }
}
