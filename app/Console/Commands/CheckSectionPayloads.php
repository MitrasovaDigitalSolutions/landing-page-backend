<?php

namespace App\Console\Commands;

use App\Models\PageSection;
use App\Models\SectionType;
use App\Support\SectionPayloadValidator;
use Illuminate\Console\Command;

class CheckSectionPayloads extends Command
{
    protected $signature = 'cms:check-section-payloads {--site= : Filter by site slug}';

    protected $description = 'Validasi payload semua section terhadap master section_types (read-only)';

    public function handle(): int
    {
        $types = SectionType::all();

        $query = PageSection::with('page.site')->whereNotNull('payload');

        if ($site = $this->option('site')) {
            $query->whereHas('page.site', fn ($q) => $q->where('slug', $site));
        }

        $checked = 0;
        $passed = 0;
        $failed = 0;
        $unknown = 0;

        foreach ($query->cursor() as $section) {
            if (empty($section->payload)) {
                continue;
            }

            $checked++;
            $label = $section->page?->site?->slug . '/' . $section->page?->slug;
            $type = SectionType::resolveForKey($section->key, $types);

            if (! $type) {
                $unknown++;
                $failed++;
                $this->line("Section #{$section->id} [{$label}] key '{$section->key}': tipe tidak dikenal");

                continue;
            }

            $errors = SectionPayloadValidator::validate($type->fields ?? [], $section->payload);

            if ($errors === []) {
                $passed++;

                continue;
            }

            $failed++;
            $this->line("Section #{$section->id} [{$label}] key '{$section->key}':");

            foreach ($errors as $path => $message) {
                $this->line("  - {$path}: {$message}");
            }
        }

        $this->newLine();
        $this->line("Dicek: {$checked} | Lolos: {$passed} | Gagal: {$failed} | Key tak dikenal: {$unknown}");

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }
}
