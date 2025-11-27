<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Tools\FinancialRecordsTool;

class SimpleTest extends Command
{
    protected $signature = 'test:simple {user_id=6}';
    protected $description = 'Simple test for user context';

    public function handle()
    {
        $userId = (int) $this->argument('user_id');
        
        $this->info("Testing user context with user_id: {$userId}");
        
        // Test 1: Bind to container
        app()->instance('laragent.user_id', $userId);
        $this->line("✅ Bound user_id to container");
        
        // Test 2: Retrieve from container
        $retrieved = app('laragent.user_id');
        $this->line("✅ Retrieved from container: {$retrieved}");
        
        // Test 3: Create tool
        $tool = new FinancialRecordsTool();
        $this->line("✅ Created FinancialRecordsTool");
        
        // Test 4: Try to execute (will likely fail without DB records)
        try {
            $result = $tool->execute([
                'operation' => 'spending_summary'
            ]);
            
            $this->line("✅ Tool executed");
            $this->line("   Result: " . substr($result, 0, 100) . "...");
        } catch (\Exception $e) {
            $this->line("⚠️  Tool execution failed (expected): " . $e->getMessage());
        }
        
        // Test 5: Test without user_id
        app()->forgetInstance('laragent.user_id');
        try {
            $result = $tool->execute([
                'operation' => 'spending_summary'
            ]);
            
            $decoded = json_decode($result, true);
            if (isset($decoded['error'])) {
                $this->line("✅ Correctly failed without user_id: " . $decoded['error']);
            } else {
                $this->error("❌ Should have failed without user_id");
            }
        } catch (\Exception $e) {
            // Check if it's the container error
            if (strpos($e->getMessage(), 'Target class [laragent.user_id] does not exist') !== false) {
                $this->line("✅ Correctly failed without user_id (container error)");
            } else {
                $this->error("❌ Unexpected error: " . $e->getMessage());
            }
        }
        
        $this->info("Test completed!");
        return 0;
    }
}
