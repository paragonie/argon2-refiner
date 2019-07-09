<?php
declare(strict_types=1);
namespace ParagonIE\Argon2Refiner;

use http\Exception\InvalidArgumentException;

/**
 * Class ParameterRecommender
 * @package ParagonIE\Argon2Refiner
 */
class ParameterRecommender
{
    /** @var int $targetMilliseconds */
    private $targetMilliseconds = 500;

    /** @var string $backend Resolves to "argon" or "sodium" */
    private $backend = 'auto';

    /** @var int $minMemory */
    private $minMemory = 16777216;

    /** @var int $maxMemory */
    private $maxMemory = 268435456;

    /** @var int $minTime */
    private $minTime = 2;

    /** @var int $maxTime */
    private $maxTime = 9;

    /** @var string $testPassword */
    private $testPassword = '';

    /** @var int|null $tolerance */
    private $tolerance = null;

    /**
     * ParameterRecommender constructor.
     * @param int $milliseconds
     */
    public function __construct(int $milliseconds = 500)
    {
        $this->targetMilliseconds = $milliseconds;
        try {
            $this->testPassword = bin2hex(random_bytes(64));
        } catch (\Throwable $ex) {
            $this->testPassword = str_repeat("X", 128);
        }
    }

    /**
     * @return string
     */
    private function getBackend(): string
    {
        if ($this->backend === 'auto') {
            if (extension_loaded('sodium') && is_callable('sodium_crypto_pwhash_str')) {
                return 'sodium';
            }
            return 'argon';
        }
        return $this->backend;
    }

    /**
     * @return int
     */
    public function getTarget(): int
    {
        return $this->targetMilliseconds;
    }

    /**
     * @param int $t
     * @param int $m
     * @return int (milliseconds)
     */
    public function getMillisecondCost(int $t, int $m): int
    {
        $backend = $this->getBackend();
        $start = $stop = 0.0;
        if ($backend === 'sodium') {
            $start = microtime(true);
            sodium_crypto_pwhash_str(
                $this->testPassword,
                $t,
                $m
            );
            $stop = microtime(true);
        } elseif ($backend === 'argon') {
            $arr = [
                'memory_cost' => $m,
                'time_cost' => $t
            ];
            $start = microtime(true);
            password_hash(
                $this->testPassword,
                PASSWORD_ARGON2ID,
                $arr
            );
            $stop = microtime(true);
        }
        return (int) round(1000 * ($stop - $start));
    }

    /**
     * @param int|null $distance
     * @return self
     */
    public function setTolerance(?int $distance = null): self
    {
        $this->tolerance = $distance;
        return $this;
    }

    /**
     * @param int $milliseconds
     * @return int
     */
    public function decide(int $milliseconds): int
    {
        if (is_null($this->tolerance)) {
            $diff = $this->targetMilliseconds >> 1;
        } else {
            $diff = $this->tolerance;
        }
        $min = $this->targetMilliseconds - $diff;
        $max = $this->targetMilliseconds + $diff;
        if ($milliseconds < $min) {
            // Too small
            return -1;
        }
        if ($milliseconds > $max) {
            // Too big
            return 1;
        }
        // Within reasonable bounds
        return 0;
    }

    /**
     * Returns an array of candidate values. It is structured like so:
     * [
     *   ['mem_cost' => X1, 'time_cost' => Y1, 'bench_time' => Z1],
     *   ['mem_cost' => X2, 'time_cost' => Y2, 'bench_time' => Z2],
     * ]
     *
     * Internally, this uses a strategy similar to a binary search
     * rather than a linear scan to quickly identify candidate memory costs
     * within an acceptable range. All memory costs given are even multiples of 1KiB.
     *
     * Time costs are evaluated by a linear scan from min to max. Memory
     * costs are evaluated for each time cost.
     *
     * @return array
     */
    public function runBenchmarks(): array
    {
        $success = [];
        for ($t = $this->minTime; $t <= $this->maxTime; ++$t) {
            $m = $this->minMemory;
            $diff = $this->maxMemory - $this->minMemory;
            while ($diff >= 1024) {
                $cost = $this->getMillisecondCost($t, $m);
                $decision = $this->decide($cost);

                $diff >>= 1;
                if ($decision === -1) {
                    // Too small
                    $m += $diff;
                } elseif ($decision === 1) {
                    // Too big
                    $m -= $diff;
                } else {
                    // We found one within range!
                    $success[]= [
                        'mem_cost' => $m,
                        'time_cost' => $t,
                        'bench_time' => $cost
                    ];
                    /*
                    We're still going to look for other values to the right of this one,
                    since we want to prioritize conservative security estimates that still
                    meet acceptable performance benchmarks. If performance was a higher
                    concern, we'd decrease $diff in this case.
                    */
                    $m += $diff;
                }
                // Mask the lower bits so we're always dealing with KB blocks
                $m &= 0x7fffffffffffe000;
            }
        }
        usort($success, function (array $a, array $b): int {
            return $b['bench_time'] <=> $a['bench_time'];
        });
        return $success;
    }

    /**
     * @param int $requestsPerSecond
     * @return self
     */
    public static function forRequestsPerSecond(int $requestsPerSecond = 5): self
    {
        if ($requestsPerSecond < 1) {
            throw new \RangeException('Requests per second cannot be zero or negative');
        }
        /** @var int $time */
        $time = (int) round(1000 / $requestsPerSecond);
        return new self($time);
    }

    /**
     * @param string $target
     * @return self
     */
    public function specifyBackend(string $target): self
    {
        switch (strtolower($target)) {
            case 'auto':
            case 'argon':
            case 'sodium':
                $this->backend = $target;
                break;
            case 'argon2':
            case 'libargon':
            case 'libargon2':
                $this->backend = 'argon';
                break;
            case 'nacl':
            case 'libsodium':
                $this->backend = 'sodium';
                break;
            default:
                throw new \InvalidArgumentException(
                    "Invalid backend: ". $target
                );
        }
        return $this;
    }

    /**
     * @param int $min
     * @return self
     */
    public function setMinMemory(int $min): self
    {
        $this->minMemory = $min;
        return $this;
    }

    /**
     * @param int $max
     * @return self
     */
    public function setMaxMemory(int $max): self
    {
        $this->maxMemory = $max;
        return $this;
    }

    /**
     * @param int $min
     * @return self
     */
    public function setMinTime(int $min): self
    {
        $this->minTime = $min;
        return $this;
    }

    /**
     * @param int $max
     * @return self
     */
    public function setMaxTime(int $max): self
    {
        $this->maxTime = $max;
        return $this;
    }
}
