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

Use [Composer](https://getcomposer.org/download).

```
composer require paragonie/argon2-refiner
```

Alternatively, you can install this with Git.

```
git clone https://github.com/paragonie/argon2-refiner
cd argon2-refiner
compsoer install
```

## Usage Instructions

### Command Line

Run the bundled `benchmark` script like so:

```
# Installed via Composer:
vendor/bin/benchmark [milliseconds=500] [tolerance=250]

# Installed via Git:
composer run-benchmarks [milliseconds=500] [tolerance=250]
```

The expected output will look something like this:

```
$ vendor/bin/benchmark 125
 Recommended Argon2id parameters:
 	       Memory cost (sodium): 79691776
 	Memory cost (password_hash): 77824
 	                  Time cost: 3
 
 Real time: 124ms
```

This means that if you set your Argon2id mem_cost to `79691776` bytes
(or `77824` KiB, which is what `password_hash()` expects) and the 
`time_cost` to 3, you will get the closest parameters that take about 
125 milliseconds to process (in this example, it took 124).

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
