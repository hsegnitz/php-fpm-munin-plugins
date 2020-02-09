#!/usr/bin/php
<?php

/**
 * Plugin to monitor various php-fpm stats - of multiple pools if present
 * @author Holger Segnitz
 */

# Parameters understood:
#
#       config   (required)
#       autoconf (optional - used by munin-config)
#
# Environment
#		pools  (a list of pool names)
#
#
# Magic markers (optional - used by munin-config and installation
# scripts):
#
#%# family=php
#%# capabilities=autoconf

if (array_key_exists(1, $argv) && 'autoconf' === $argv[1]) {
    echo "yes\n";
    die();
}

$printConfig = isset($argv[1]) && 'config' === $argv[1];
$printValues = !$printConfig;
if (isset($_SERVER['MUNIN_CAP_DIRTYCONFIG']) && $_SERVER['MUNIN_CAP_DIRTYCONFIG'] == '1') {
    $printConfig = $printValues = true;
}

$pools = [];
$phpbin = $_SERVER['phpbin'] ?? 'php-fpm';
foreach ($_SERVER as $key => $value) {
	if (preg_match('/^pool_(.*)$/', $key, $out)) {
		$pools[$out[1]] = [
			'domain'      => $value,
			'processes'   => 0,
			'ram'         => 0,
			'connections' => 0,
			'active'      => 0,
			'idle'        => 0,
		];
	}
}

/*
jsegnitz 11610  0.0  0.8 486048 32576 ?        S    20:27   0:00 php-fpm: pool jsegnitz
jsegnitz 11613  0.0  0.9 486972 38988 ?        S    20:27   0:00 php-fpm: pool jsegnitz
hsegnitz 15504  0.0  0.7 484140 30484 ?        S    20:56   0:01 php-fpm: pool www
hsegnitz 16562  0.0  0.4 483604 17584 ?        S    Feb07   0:00 php-fpm: pool maintenance
jsegnitz 17628  0.0  0.9 486260 37436 ?        S    21:12   0:00 php-fpm: pool jsegnitz
hsegnitz 18238  0.0  0.7 484116 30476 ?        S    21:15   0:01 php-fpm: pool www
hsegnitz 18983  0.0  0.7 484120 30548 ?        S    21:24   0:00 php-fpm: pool www
hsegnitz 19255  0.0  0.4 483604 17584 ?        S    Feb07   0:00 php-fpm: pool maintenance
hsegnitz 20976  0.0  0.7 484088 30556 ?        S    21:37   0:00 php-fpm: pool www


accepted conn:    40163
pool:             www
process manager:  dynamic
idle processes:   6
active processes: 0
total processes:  6

  */

foreach ($pools as $pool => $values) {
	$data = explode("\n", file_get_contents('http://' . $values['domain'] . '/status'));
	foreach ($data as $row) {
		$lineSplit = preg_split('/:\s+/', $row);
		switch ($lineSplit[0]) {
			case 'accepted conn':
				$pools[$pool]['connections'] = $lineSplit[1];
				break;
			case 'idle processes':
				$pools[$pool]['idle'] = $lineSplit[1];
				break;
			case 'active processes':
				$pools[$pool]['active'] = $lineSplit[1];
				break;
		}
	}
}

$result = explode("\n", shell_exec('ps auwx | grep "' . $phpbin . ': pool" | grep -v grep | grep -v phpfpm_memory'));
foreach ($result as $row) {
	$list = preg_split('/\s+/', $row);
	if (count($list) < 12) {
		continue;
	}
	$pools[$out[12]]['processes']++;
	$pools[$out[12]]['ram'] += ($out[5] * 1024);
}


echo "multigraph php_fpm_connections\n";
if ($printConfig) {
    # The headers
    echo "graph_title php-fpm accepted connections\n";
    echo "graph_args --base 1000 -l 0\n";
    echo "graph_vlabel connections\n";
    echo "graph_scale yes\n";
    echo "graph_category php\n";

    # Create and print labels
	foreach ($pools as $pool => $stats) {
		echo "{$pool}.label {$pool}\n";
		echo "{$pool}.draw AREASTACK\n";
		echo "{$pool}.type DERIVE\n";
		echo "{$pool}.min 0\n";
	}
}

if ($printValues) {
    foreach ($pools as $pool => $stats) {
        echo "{$pool}.value {$stats['connections']}\n";
    }
}

echo "\n";

echo "multigraph php_fpm_average_size\n";
if ($printConfig) {
    # The headers
    echo "graph_title php-fpm average process size\n";
    echo "graph_args --base 1024\n";
    echo "graph_vlabel average process size\n";
    echo "graph_scale yes\n";
    echo "graph_category php\n";

    # Create and print labels
    foreach ($pools as $pool => $stats) {
        echo "{$pool}.label {$pool}\n";
        echo "{$pool}.draw LINE2\n";
    }
}

if ($printValues) {
    foreach ($pools as $pool => $stats) {
    	$avg = $stats['ram'] / $stats['processes'];
        echo "{$pool}.value {$avg}\n";
    }
}

echo "\n";

echo "multigraph php_fpm_ram\n";
if ($printConfig) {
    # The headers
    echo "graph_title php-fpm memory usage\n";
    echo "graph_args --base 1024 -l 0\n";
    echo "graph_vlabel RAM\n";
    echo "graph_scale yes\n";
    echo "graph_category php\n";

    # Create and print labels
    foreach ($pools as $pool => $stats) {
        echo "{$pool}.label {$pool}\n";
        echo "{$pool}.draw AREASTACK\n";
    }
}

if ($printValues) {
    foreach ($pools as $pool => $stats) {
        echo "{$pool}.value {$stats['ram']}\n";
    }
}

echo "\n";

echo "multigraph php_fpm_processes\n";
if ($printConfig) {
    # The headers
    echo "graph_title php-fpm processes\n";
    echo "graph_args --base 1000 -l 0\n";
    echo "graph_vlabel processes\n";
    echo "graph_scale yes\n";
    echo "graph_category php\n";

    # Create and print labels
    foreach ($pools as $pool => $stats) {
        echo "{$pool}.label {$pool}\n";
        echo "{$pool}.draw AREASTACK\n";
    }
}

if ($printValues) {
    foreach ($pools as $pool => $stats) {
        echo "{$pool}.value {$stats['processes']}\n";
    }
}

echo "\n";

echo "multigraph php_fpm_status\n";
if ($printConfig) {
    # The headers
    echo "graph_title php-fpm status\n";
    echo "graph_args --base 1000 -l 0\n";
    echo "graph_vlabel connections\n";
    echo "graph_scale yes\n";
    echo "graph_category php\n";

    # Create and print labels
    foreach ($pools as $pool => $stats) {
        echo "{$pool}_active.label {$pool} active\n";
        echo "{$pool}_active.draw AREASTACK\n";
        echo "{$pool}_idle.label {$pool} idle\n";
        echo "{$pool}_idle.draw AREASTACK\n";
    }
}

if ($printValues) {
    foreach ($pools as $pool => $stats) {
        echo "{$pool}_active.value {$stats['active']}\n";
        echo "{$pool}_idle.value {$stats['idle']}\n";
    }
}

echo "\n";
