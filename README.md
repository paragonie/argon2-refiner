# Argon2 Refiner

[![Build Status](https://travis-ci.org/paragonie/argon2-refiner.svg?branch=master)](https://travis-ci.org/paragonie/argon2-refiner)
[![Latest Stable Version](https://poser.pugx.org/paragonie/argon2-refiner/v/stable)](https://packagist.org/packages/paragonie/argon2-refiner)
[![Latest Unstable Version](https://poser.pugx.org/paragonie/argon2-refiner/v/unstable)](https://packagist.org/packages/paragonie/argon2-refiner)
[![License](https://poser.pugx.org/paragonie/argon2-refiner/license)](https://packagist.org/packages/paragonie/argon2-refiner)
[![Downloads](https://img.shields.io/packagist/dt/paragonie/argon2-refiner.svg)](https://packagist.org/packages/paragonie/argon2-refiner)

Easily and effectively benchmark the real time to perform
Argon2id password hashes on your machine.

> Warning: This might take many seconds or minutes to complete.

## Installation Instructions

Use Composer.

```
composer require paragonie/argon2-refiner
```

## Usage Instructions

### Command Line

Run the bundled `benchmark` script like so:

```
composer run-benchmarks [milliseconds=500] [tolerance=250]
```

The expected output will look something like this:

```
$ composer run-benchmarks 125
> bin/benchmark '125'
Recommended Argon2id parameters:
	Memory cost: 79691776
	  Time cost: 3

	  Real time: 121ms
```

This means that if you set your Argon2id mem_cost to `79691776`
and the `time_cost` to 3, you will get the closest parameters that
take about 125 milliseconds to process (in this example, it took 121).

### Object-Oriented API

You can fine-tune your min/max costs to search within from the object
by invoking the appropriate methods.

```php
<?php
use ParagonIE\Argon2Refiner\ParameterRecommender;

$refiner = (new ParameterRecommender(125))
    ->setMinMemory(1 << 20)
    ->setMaxMemory(1 << 31)
    ->setMinTime(2)
    ->setMaxTime(4)
    ->setTolerance(25);

$results = $refiner->runBenchmarks();
```

The `runBenchmarks()` method returns a two-dimensional array of arrays.
Each child array consists of the following data:

* `mem_cost` (int) -- Candidate parameter
* `time_cost` (int) -- Candidate parameter
* `bench_time` (int) -- Milliseconds elapsed in Argon2id calculation

From this data, you can devise your own strategy for selecting which
parameters set is most suitable for your environment.
