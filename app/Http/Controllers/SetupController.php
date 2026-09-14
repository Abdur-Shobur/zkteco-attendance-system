<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Throwable;

class SetupController extends Controller
{
    /**
     * Public setup endpoints (migrate / seed).
     * Protected by SETUP_KEY query/header — not by login.
     */
    private function authorizeSetup(Request $request): ?JsonResponse
    {
        $configured = (string) config('app.setup_key', env('SETUP_KEY', ''));

        if ($configured === '') {
            return response()->json([
                'success' => false,
                'message' => 'SETUP_KEY is not configured in .env. Add SETUP_KEY=your-secret then retry.',
            ], 503);
        }

        $provided = (string) (
            $request->query('key')
            ?? $request->header('X-Setup-Key')
            ?? $request->input('key')
            ?? ''
        );

        if (!hash_equals($configured, $provided)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or missing setup key. Pass ?key=YOUR_SETUP_KEY',
            ], 403);
        }

        return null;
    }

    public function migrate(Request $request): JsonResponse
    {
        if ($deny = $this->authorizeSetup($request)) {
            return $deny;
        }

        try {
            Artisan::call('migrate', ['--force' => true]);
            $output = trim(Artisan::output());

            return response()->json([
                'success' => true,
                'action' => 'migrate',
                'message' => 'Migrations completed.',
                'output' => $output,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'action' => 'migrate',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function seed(Request $request): JsonResponse
    {
        if ($deny = $this->authorizeSetup($request)) {
            return $deny;
        }

        $class = $request->query('class', $request->input('class'));

        try {
            $params = ['--force' => true];
            if ($class) {
                $params['--class'] = $class;
            }

            Artisan::call('db:seed', $params);
            $output = trim(Artisan::output());

            return response()->json([
                'success' => true,
                'action' => 'seed',
                'message' => $class ? "Seeder {$class} completed." : 'Database seeding completed.',
                'output' => $output,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'action' => 'seed',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function migrateAndSeed(Request $request): JsonResponse
    {
        if ($deny = $this->authorizeSetup($request)) {
            return $deny;
        }

        try {
            Artisan::call('migrate', ['--force' => true]);
            $migrateOutput = trim(Artisan::output());

            Artisan::call('db:seed', ['--force' => true]);
            $seedOutput = trim(Artisan::output());

            return response()->json([
                'success' => true,
                'action' => 'migrate-seed',
                'message' => 'Migrations and seeders completed.',
                'migrate_output' => $migrateOutput,
                'seed_output' => $seedOutput,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'action' => 'migrate-seed',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function status(Request $request): JsonResponse
    {
        if ($deny = $this->authorizeSetup($request)) {
            return $deny;
        }

        try {
            $migrations = DB::table('migrations')->orderBy('id')->get(['migration', 'batch']);
        } catch (Throwable $e) {
            $migrations = [];
        }

        return response()->json([
            'success' => true,
            'action' => 'status',
            'app_env' => config('app.env'),
            'migrations_count' => is_countable($migrations) ? count($migrations) : 0,
            'migrations' => $migrations,
            'setup_key_configured' => filled(config('app.setup_key', env('SETUP_KEY'))),
        ]);
    }
}
