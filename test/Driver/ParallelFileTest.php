<?php

namespace Amp\File\Test\Driver;

use Amp\File;
use Amp\File\Driver\ParallelDriver;
use Amp\File\Test\AsyncFileTest;
use Amp\Parallel\Worker\DefaultPool;
use Amp\Parallel\Worker\Pool;

class ParallelFileTest extends AsyncFileTest
{
    private const DEFAULT_WORKER_LIMIT = 8;

    private Pool $pool;

    protected function createDriver(int $workerLimit = self::DEFAULT_WORKER_LIMIT): File\Driver
    {
        $this->pool = new DefaultPool;

        return new ParallelDriver($this->pool, $workerLimit);
    }

    protected function tearDownAsync(): void
    {
        $this->pool->shutdown();
    }

    public function getWorkerLimits(): iterable
    {
        return \array_map(fn(int $count): array => [$count], \range(4, 16, 4));
    }

    /**
     * @dataProvider getWorkerLimits
     */
    public function testMultipleOpenFiles(int $maxCount)
    {
        $driver = $this->createDriver($maxCount);

        $files = [];
        for ($i = 0; $i < $maxCount * 2; ++$i) {
            $files[] = $driver->openFile(__FILE__, 'r');
        }

        try {
            $this->assertSame($maxCount, $this->pool->getWorkerCount());
        } finally {
            foreach ($files as $file) {
                $file->close();
            }
        }
    }
}
