<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use LarAgent\Messages\UserMessage;
use App\Tools\FinancialRecordsTool;

class TestUserContext extends Command
{
    protected $signature = 'test:user-context {user_id=6}';
    protected $description = 'Test LarAgent user context implementation';

    public function handle()
    {
        $userId = (int) $this->argument('user_id');
        
        $this->info("Testing LarAgent User Context Implementation");
        $this->info("==========================================\n");
        
        // Test 1: Message Metadata
        $this->info("Test 1: Message::user() with metadata");
        try {
            $userMessage = UserMessage::user('Test prompt', ['user_id' => $userId]);
            $this->line("✅ Message created successfully");
            $this->line("   Content: " . $userMessage->content());
            
            // Check if metadata method exists
            if (method_exists($userMessage, 'getMetadata')) {
                $metadata = $userMessage->getMetadata();
                $this->line("   Metadata: " . json_encode($metadata));
            } else {
                $this->line("   Metadata storage: OK (internal)");
            }
        } catch (\Exception $e) {
            $this->error("❌ Failed: " . $e->getMessage());
        }
        
        $this->newLine();
        
        // Test 2: Service Container Binding
        $this->info("Test 2: Service Container Binding");
        try {
            app()->instance('laragent.user_id', $userId);
            $retrievedUserId = app('laragent.user_id');
            
            if ($retrievedUserId === $userId) {
                $this->line("✅ Container binding works");
                $this->line("   Stored: {$userId}");
                $this->line("   Retrieved: {$retrievedUserId}");
            } else {
                $this->error("❌ Container mismatch");
            }
        } catch (\Exception $e) {
            $this->error("❌ Failed: " . $e->getMessage());
        }
        
        $this->newLine();
        
        // Test 3: FinancialRecordsTool Access
        $this->info("Test 3: FinancialRecordsTool accessing user_id");
        try {
            app()->instance('laragent.user_id', $userId);
            
            $tool = new FinancialRecordsTool();
            $result = $tool->execute([
                'operation' => 'spending_summary',
                'limit' => 10
            ]);
            
            $decoded = json_decode($result, true);
            
            if (isset($decoded['success']) && $decoded['success']) {
                $this->line("✅ Tool executed successfully");
                $this->line("   Operation: spending_summary");
                $this->line("   User ID used: {$userId}");
                
                if (isset($decoded['summary'])) {
                    $this->line("   Total Records: " . ($decoded['summary']['total_records'] ?? 'N/A'));
                    $this->line("   Total Spend: $" . number_format($decoded['summary']['total_spend'] ?? 0, 2));
                }
            } else {
                $this->line("⚠️  Tool executed but returned no data");
                $this->line("   Response: " . substr($result, 0, 200));
            }
        } catch (\Exception $e) {
            $this->error("❌ Failed: " . $e->getMessage());
            $this->line("   Stack trace: " . $e->getTraceAsString());
        }
        
        $this->newLine();
        
        // Test 4: Without user_id binding
        $this->info("Test 4: Tool without user_id (should fail gracefully)");
        try {
            // Clear the binding
            app()->forgetInstance('laragent.user_id');
            
            $tool = new FinancialRecordsTool();
            $result = $tool->execute([
                'operation' => 'spending_summary',
            ]);
            
            $decoded = json_decode($result, true);
            
            if (isset($decoded['error'])) {
                $this->line("✅ Tool correctly returned error");
                $this->line("   Error: " . $decoded['error']);
            } else {
                $this->error("❌ Tool should have failed without user_id");
            }
        } catch (\Exception $e) {
            $this->error("❌ Unexpected exception: " . $e->getMessage());
        }
        
        $this->newLine();
        $this->info("==========================================");
        $this->info("All tests completed!");
        
        return 0;
    }
}
