<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ZKTecoService;
use Illuminate\Support\Facades\Log;

class SyncAttendanceLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'attendance:sync';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync attendance logs from ZKTeco device to database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (config('zkteco.mode', 'adms') === 'adms') {
            $this->info('ADMS mode enabled — device pushes logs automatically. Skipping UDP pull sync.');
            return Command::SUCCESS;
        }

        $this->info('Starting attendance log synchronization...');
        
        try {
            $zkTecoService = new ZKTecoService();
            $result = $zkTecoService->syncAttendanceLogs();
            
            $this->info("Synchronization completed successfully!");
            $this->info("Total logs found: {$result['total_logs']}");
            $this->info("New logs synced: {$result['synced_count']}");
            
            if (!empty($result['errors'])) {
                $this->warn("Errors encountered: " . count($result['errors']));
                foreach ($result['errors'] as $error) {
                    $this->error($error);
                }
            }
            
            // Log the sync operation
            Log::info('Scheduled attendance sync completed', [
                'total_logs' => $result['total_logs'],
                'synced_count' => $result['synced_count'],
                'errors_count' => count($result['errors'])
            ]);
            
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $this->error('Failed to sync attendance logs: ' . $e->getMessage());
            Log::error('Scheduled attendance sync failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return Command::FAILURE;
        }
    }
}
