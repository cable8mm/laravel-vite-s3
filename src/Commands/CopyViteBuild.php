<?php

namespace Cable8mm\LaravelViteS3\Commands;

use Cable8mm\LaravelViteS3\CacheControl;
use Illuminate\Console\Command;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use Throwable;

final class CopyViteBuild extends Command
{
    protected $signature = 's3:copy-vite-build
        {--delete : Also delete files in the target disk that no longer exist in the source disk}
        {--dry-run : Print what would be copied without writing anything}';

    protected $description = 'Copy the Vite build output to the configured target disk';

    public function handle(): int
    {
        try {
            $sourceDisk = Storage::disk($this->sourceDiskName());
            $targetDisk = Storage::disk($this->targetDiskName());
        } catch (Throwable $e) {
            $this->error('Cannot access the configured disks: '.$e->getMessage());

            return self::FAILURE;
        }

        $targetPath = trim((string) config('vite-s3.target_path', 'build'), '/');

        if ($this->option('delete') && $targetPath === '') {
            $this->error('The --delete option requires a non-empty "vite-s3.target_path", otherwise it would scan (and empty out) the entire target disk.');

            return self::FAILURE;
        }

        $files = $sourceDisk->allFiles();

        if ($files === []) {
            $this->error('No files found in the source disk. Did you run `npm run build`?');

            return self::FAILURE;
        }

        $cacheControl = CacheControl::fromConfig();
        $copied = $failed = $deleted = 0;

        foreach ($files as $file) {
            $destination = $targetPath === '' ? $file : $targetPath.'/'.$file;

            try {
                if ($this->option('dry-run')) {
                    $this->line("[dry-run] {$file} -> {$destination}");
                    $copied++;

                    continue;
                }

                $targetDisk->put(
                    $destination,
                    $sourceDisk->get($file),
                    $cacheControl->optionsFor($file)
                );
                $copied++;
            } catch (Throwable $e) {
                $this->error("[failed] {$file}: {$e->getMessage()}");
                $failed++;
            }
        }

        if ($this->option('delete')) {
            try {
                $deleted = $this->deleteStaleFiles($files, $targetDisk, $targetPath);
            } catch (Throwable $e) {
                $this->error('Could not delete stale files: '.$e->getMessage());
                $failed++;
            }
        }

        if ($failed > 0) {
            $this->error("Done with errors. {$copied} copied, {$deleted} deleted, {$failed} failed.");

            return self::FAILURE;
        }

        $tail = $this->option('delete') ? ", {$deleted} stale files deleted" : '';

        if ($this->option('dry-run')) {
            $this->info("Dry run. {$copied} files would be copied{$tail}.");

            return self::SUCCESS;
        }

        $this->info("Done. {$copied} files copied{$tail}.");

        return self::SUCCESS;
    }

    /**
     * Delete files in the target disk under the target path that no longer
     * exist in the source disk.
     *
     * @param  array<int, string>  $sourceFiles
     */
    private function deleteStaleFiles(array $sourceFiles, FilesystemAdapter $targetDisk, string $targetPath): int
    {
        $prefix = $targetPath === '' ? '' : $targetPath.'/';
        $expected = array_map(fn (string $file): string => $prefix.$file, $sourceFiles);
        $existing = $targetDisk->allFiles($targetPath === '' ? null : $targetPath);
        $stale = array_diff($existing, $expected);

        foreach ($stale as $path) {
            if ($this->option('dry-run')) {
                $this->line("[dry-run] delete {$path}");

                continue;
            }

            $targetDisk->delete($path);
        }

        return count($stale);
    }

    private function sourceDiskName(): string
    {
        return (string) config('vite-s3.source_disk', 'build');
    }

    private function targetDiskName(): string
    {
        return (string) config('vite-s3.target_disk', 's3');
    }
}
