<?php

namespace App\Console\Commands;

use App\Modules\SampleData\Support\CommerceDemoBuilder;
use Illuminate\Console\Command;

class SampleDataCommerceDemoCommand extends Command
{
    protected $signature = 'sample-data:commerce-demo {--with-mails : Queue invoice and payment receipt tenant mails too}';

    protected $description = 'Bootstrap demo commerce data: contacts, products, order, payment, and testable email flow.';

    public function handle(CommerceDemoBuilder $builder): int
    {
        $result = $builder->run((bool) $this->option('with-mails'));

        $this->info('Commerce demo sample data berhasil disiapkan.');
        $this->line('Order: ' . $result['sale']->sale_number);
        $this->line('Payment: ' . $result['payment']->payment_number);

        foreach ($result['notes'] as $note) {
            $this->line('- ' . $note);
        }

        return self::SUCCESS;
    }
}
