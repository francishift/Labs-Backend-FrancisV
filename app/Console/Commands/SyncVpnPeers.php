<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\VpnService;

class SyncVpnPeers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'vpn:sync-peers';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Restores all VPN peers from the database to the WireGuard interface';

    /**
     * Execute the console command.
     */
    public function handle(VpnService $vpnService)
    {
        $this->info('Restoring VPN peers...');

        $results = $vpnService->syncAllPeers();

        $this->info("Successfully restored {$results['success']} peers.");
        
        if ($results['failed'] > 0) {
            $this->error("Failed to restore {$results['failed']} peers. Check logs for details.");
            return 1;
        }

        return 0;
    }
}
