<?php

namespace App\Commands;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use LaravelZero\Framework\Commands\Command;

class PollSpaceInvader extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'poll:spaceinvader';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Poll the spaceinvader shop and ping if the product page is up';

    protected const BASE_URL = 'https://space-invaders.com/spaceshop/product';

    protected const KNOWN_PRODUCT_IDS = [21, 30];

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->checkForNewProducts();
        $this->pollKnownProductPages();
        $this->pollUnknownProductPages();
    }

    protected function checkForNewProducts()
    {
        $this->browse(function ($browser) {
            $url = sprintf("%s/%s", self::BASE_URL, self::KNOWN_PRODUCT_IDS[0]);
            $firstProductLink = $browser->visit($url)
                ->attribute('#controls a:last-of-type', 'href');

            if ($this->isNewProductLink($firstProductLink)) {
                $this->sendNotification("Hit: {$firstProductLink}");
                return;
            }

            $browser->click('#controls a:last-of-type', 'href');
            $nextProductLink = $browser->attribute('#controls a:last-of-type', 'href');

            if ($this->isNewProductLink($nextProductLink)) {
                $this->sendNotification("Hit: {$nextProductLink}");
                return;
            }

            $browser->click('#controls a:last-of-type', 'href');
            $finalProductLink = $browser->attribute('#controls a:last-of-type', 'href');

            if ($this->isNewProductLink($finalProductLink)) {
                $this->sendNotification("Hit: {$finalProductLink}");
                return;
            }
        });
    }

    protected function pollKnownProductPages()
    {
        collect(self::KNOWN_PRODUCT_IDS)->each(
            fn ($productId) => $this->pollKnownProductPage($productId)
        );
    }

    protected function pollUnknownProductPages()
    {
        $this->productIds()->each(
            fn ($productId) => $this->pollUnknownProductPage($productId)
        );
    }

    protected function isNewProductLink($productLink)
    {
        $productId = last(explode("/", $productLink));

        return !in_array($productId, self::KNOWN_PRODUCT_IDS);
    }

    protected function pollKnownProductPage($productId)
    {
        $url = sprintf("%s/%s", self::BASE_URL, $productId);
        $this->line("Polling {$url}");

        $response = Http::get($url);
        $body = $response->body();

        if (str_contains($body, 'AVAILABLE<br>SOON')) {
            $this->line('Page still contains AVAILABLE SOON');
            return;
        }

        $this->sendNotification("Hit: {$url}");
    }

    protected function productIds(): Collection
    {
        return collect()
            ->range(1, 100)
            ->reject(fn ($num, $key) => in_array($num, self::KNOWN_PRODUCT_IDS))
            ->values();
    }

    protected function pollUnknownProductPage(int $productId)
    {
        $url = sprintf("%s/%s", self::BASE_URL, $productId);
        $this->line("Polling {$url}");

        $response = Http::get($url);

        if ($response->failed()) {
            $this->line("Page still fails with error code: {$response->status()}");
            return;
        }

        $this->sendNotification("Hit: {$url}");
    }

    protected function sendNotification(string $content)
    {
        Http::post(env('DISCORD_WEBHOOK_URL'), [
            'content' => $content,
        ]);
    }

    /**
     * Define the command's schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule $schedule
     * @return void
     */
    public function schedule(Schedule $schedule): void
    {
        // $schedule->command(static::class)->everyMinute();
    }
}
