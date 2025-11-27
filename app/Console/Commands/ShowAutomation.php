<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ShowAutomation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'show:automation {id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Display automation record details';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $id = $this->argument('id');
        
        $automation = \App\Models\Automation::find($id);
        
        if (!$automation) {
            $this->error("Automation #{$id} not found");
            return 1;
        }
        
        $this->info("=== Automation Record ===");
        $this->line("ID: {$automation->id}");
        $this->line("Type: {$automation->type}");
        $this->line("Name: {$automation->name}");
        $this->line("Status: {$automation->status}");
        $this->line("User ID: {$automation->user_id}");
        $this->line("Created: {$automation->created_at}");
        $this->line("");
        $this->info("=== Markdown Content ===");
        $this->line($automation->markdown_content);
        
        return 0;
    }
}
