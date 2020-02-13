#!/usr/bin/php
<?php

/**
 * Plugin to monitor opcache stats of multiple php-fpm pools on the same machine
 * @author Holger Segnitz (https://github.com/hsegnitz)
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
# Magic markers (optional - used by munin-config and installation scripts):
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
foreach ($_SERVER as $key => $value) {
	if (preg_match('/^pool_(.*)$/', $key, $out)) {
		$pools[$out[1]] = [
			'domain' => $value,
			'info'   => [],
		];
	}
}

foreach ($pools as $pool => $values) {
	$data = explode("\n", file_get_contents('http://' . $values['domain'] . '/opcachinfo.php'));
	$pools[$pool]['entries']  = $data['opcache_statistics']['num_cached_scripts'];
	$pools[$pool]['hits']     = $data['opcache_statistics']['hits'];
	$pools[$pool]['misses']   = $data['opcache_statistics']['misses'];
    $pools[$pool]['used']     = $data['memory_usage']['used_memory'];
    $pools[$pool]['overhead'] = $data['memory_usage']['wasted_memory'];
    $pools[$pool]['free']     = $data['memory_usage']['free_memory'];
}

echo "multigraph php_opcache_entries\n";
if ($printConfig) {
    # The headers
    echo "graph_title php opcache entries\n";
    echo "graph_args --base 1000\n";
    echo "graph_vlabel entries\n";
    echo "graph_scale yes\n";
    echo "graph_category php\n";

    # Create and print labels
	foreach ($pools as $pool => $stats) {
		echo "{$pool}.label {$pool}\n";
		echo "{$pool}.draw AREASTACK\n";
		echo "{$pool}.min 0\n";
	}
}

if ($printValues) {
    foreach ($pools as $pool => $stats) {
        echo "{$pool}.value {$stats['entries']}\n";
    }
}

echo "\n";

echo "multigraph php_opcache_hits\n";
if ($printConfig) {
    # The headers
    echo "graph_title php opcache hits and misses\n";
    echo "graph_args --base 1000 -l 0\n";
    echo "graph_vlabel count\n";
    echo "graph_scale yes\n";
    echo "graph_category php\n";

    # Create and print labels
    foreach ($pools as $pool => $stats) {
        echo "{$pool}_miss.label {$pool} miss\n";
        echo "{$pool}_miss.type DERIVE\n";
        echo "{$pool}_miss.draw AREASTACK\n";
        echo "{$pool}_miss.min 0\n";
        echo "{$pool}_miss.max 420000\n";
        echo "{$pool}_miss.graph no\n";

        echo "{$pool}_hit.label {$pool} hit\n";
        echo "{$pool}_hit.type DERIVE\n";
        echo "{$pool}_hit.draw AREASTACK\n";
        echo "{$pool}_hit.min 0\n";
        echo "{$pool}_hit.max 420000\n";
        echo "{$pool}_hit.negative {$pool}_miss\n";
    }
}

if ($printValues) {
    foreach ($pools as $pool => $stats) {
        echo "{$pool}_hit.value {$stats['hits']}\n";
        echo "{$pool}_miss.value {$stats['misses']}\n";
    }
}

echo "\n";

echo "multigraph php_opcache_memory\n";
if ($printConfig) {
    # The headers
    echo "graph_title php opcache memory usage\n";
    echo "graph_args --base 1024 -l 0\n";
    echo "graph_vlabel RAM free (-) / used (+)\n";
    echo "graph_scale yes\n";
    echo "graph_category php\n";

    # Create and print labels
    foreach ($pools as $pool => $stats) {
        echo "{$pool}_free.label {$pool} free\n";
        echo "{$pool}_free.draw AREASTACK\n";
        echo "{$pool}_free.min 0\n";
        echo "{$pool}_free.graph no\n";

        echo "{$pool}_used.label {$pool} used\n";
        echo "{$pool}_used.draw AREASTACK\n";
        echo "{$pool}_used.min 0\n";
        echo "{$pool}_used.negative {$pool}_free\n";
    }

    echo "overhead.label total overhead\n";
    echo "overhead.draw LINE1\n";
    echo "overhead.colour 000000\n";
}

if ($printValues) {
	$overhead = 0;
    foreach ($pools as $pool => $stats) {
        echo "{$pool}_used.value {$stats['used']}\n";
        echo "{$pool}_free.value {$stats['free']}\n";
        $overhead += $stats['overhead'];
    }
	echo "overhead.value {$overhead}\n";
}

echo "\n";
