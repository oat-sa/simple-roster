<?php

namespace OAT\SimpleRoster\Tests\Unit\Service\AwsS3;

use League\Flysystem\Filesystem;
use League\Flysystem\Memory\MemoryAdapter;
use OAT\SimpleRoster\Service\AwsS3\FolderSyncService;
use PHPUnit\Framework\TestCase;

class FolderSyncServiceTest extends TestCase
{
    public function testSync(): void
    {
        $service = new FolderSyncService(
            $source = new Filesystem(new MemoryAdapter()),
            $dest = new Filesystem(new MemoryAdapter())
        );

        $dir = 'some_dir';

        $source->put($path1 = $this->path($dir, 'path/one.txt'), $content1 = 'some_data1');
        $source->put($path2 = $this->path($dir, 'path/two/file.txt'), $content2 = 'some_data2');

        $service->sync($dir);

        $this->assertTrue($dest->has($path1));
        $this->assertTrue($dest->has($path2));
        $this->assertEquals($content1, $dest->read($path1));
        $this->assertEquals($content2, $dest->read($path2));
    }

    protected function path(string $root, string $path): string
    {
        return trim($root, '/') . '/' . trim($path, '/');
    }
}
