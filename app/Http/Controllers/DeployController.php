<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\HttpFoundation\Response;

class DeployController extends Controller
{
    /**
     * One-time remote setup hook for container deployments (Vercel).
     * Runs pending migrations and seeds demo data when the database is empty.
     */
    public function setup(Request $request): Response
    {
        $token = (string) config('app.setup_token');

        abort_if($token === '', 404);
        abort_unless(hash_equals($token, (string) $request->query('token', $request->bearerToken())), 403);

        Artisan::call('migrate', ['--force' => true]);
        $output = [Artisan::output()];

        if (User::query()->count() === 0) {
            Artisan::call('db:seed', ['--force' => true]);
            $output[] = Artisan::output();
        }

        return response(implode('', $output), 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}
