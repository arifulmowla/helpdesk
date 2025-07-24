<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\Contact;
use Illuminate\Console\Command;

class MigrateContactsToCompanyRelation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'helpdesk:migrate-contacts-to-company-relation';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate existing contacts from company string to company relation';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting migration of contacts to company relations...');
        
        // Get all contacts with company string values
        $contacts = Contact::whereNotNull('company')->whereNull('company_id')->get();
        
        $this->info("Found {$contacts->count()} contacts to migrate.");
        
        $migratedCount = 0;
        $createdCompanies = 0;
        
        foreach ($contacts as $contact) {
            if ($contact->company) {
                // Try to find existing company by name
                $company = Company::where('name', $contact->company)->first();
                
                // If no company exists, create one
                if (!$company) {
                    $company = Company::create(['name' => $contact->company]);
                    $createdCompanies++;
                    $this->line("Created company: {$company->name}");
                }
                
                // Update contact to use company_id
                $contact->update(['company_id' => $company->id]);
                $migratedCount++;
            }
        }
        
        $this->info("Migration completed!");
        $this->info("- Migrated {$migratedCount} contacts");
        $this->info("- Created {$createdCompanies} new companies");
        
        return 0;
    }
}
