<?php
declare(strict_types=1);
namespace ParagonIE\Argon2Refiner\Tests;

use ParagonIE\Argon2Refiner\ParameterRecommender;
use PHPUnit\Framework\TestCase;

/**
 * Class ParameterRecommenderTest
 * @package ParagonIE\Argon2Refiner\Tests
 */
class ParameterRecommenderTest extends TestCase
{
    public function testConstructor()
    {
        $par = new ParameterRecommender(500);
        $this->assertSame(500, $par->getTarget());

        $this->assertSame(250, ParameterRecommender::forRequestsPerSecond(4)->getTarget());
        $this->assertSame(125, ParameterRecommender::forRequestsPerSecond(8)->getTarget());
        $this->assertSame(100, ParameterRecommender::forRequestsPerSecond(10)->getTarget());
        $this->assertSame( 40, ParameterRecommender::forRequestsPerSecond(25)->getTarget());
    }

    public function testDecision()
    {
        $par = new ParameterRecommender(500);
        $this->assertSame(-1, $par->setTolerance(100)->decide(250));
        $this->assertSame(0, $par->setTolerance(250)->decide(250));
        $this->assertSame(-1, $par->setTolerance(250)->decide(100));
        $this->assertSame(1, $par->setTolerance(100)->decide(750));
        $this->assertSame(0, $par->setTolerance(250)->decide(750));
        $this->assertSame(1, $par->setTolerance(250)->decide(1000));

        $par = new ParameterRecommender(250);
        $this->assertSame(-1, $par->decide(124));
        $this->assertSame(0, $par->decide(125));
        $this->assertSame(0, $par->decide(375));
        $this->assertSame(1, $par->decide(376));

        $par->setTolerance(50);
        $this->assertSame(-1, $par->decide(199));
        $this->assertSame(0, $par->decide(200));
        $this->assertSame(0, $par->decide(300));
        $this->assertSame(1, $par->decide(301));
    }
}
