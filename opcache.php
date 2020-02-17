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
		];
	}
}

foreach ($pools as $pool => $values) {
	$data = unserialize(file_get_contents('http://' . $values['domain'] . '/opcacheinfo.php'));
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

if ($printValues) {
    foreach ($pools as $pool => $stats) {
        echo "{$pool}.value {$stats['entries']}\n";
    }
}

echo "\n";

foreach ($pools as $pool => $stats) {
	echo "multigraph php_opcache_hits_{$pool}\n";
	if ($printConfig) {
		# The headers
		echo "graph_title php opcache hits and misses {$pool}\n";
		echo "graph_args --base 1000 -l 0\n";
		echo "graph_vlabel count\n";
		echo "graph_scale yes\n";
		echo "graph_category php\n";

		# Create and print labels
		echo "miss.label miss\n";
		echo "miss.type DERIVE\n";
		echo "miss.draw AREA\n";
		echo "miss.min 0\n";
		echo "miss.graph no\n";
		echo "miss.max 420000\n";

		echo "hit.label hit\n";
		echo "hit.type DERIVE\n";
		echo "hit.draw AREA\n";
		echo "hit.min 0\n";
		echo "hit.max 420000\n";
		echo "hit.negative miss\n";
	}

	if ($printValues) {
		echo "hit.value {$stats['hits']}\n";
		echo "miss.value {$stats['misses']}\n";
	}

	echo "\n";
}

foreach ($pools as $pool => $stats) {
    echo "multigraph php_opcache_memory_{$pool}\n";
    if ($printConfig) {
        # The headers
        echo "graph_title php opcache memory usage {$pool}\n";
        echo "graph_args --base 1024 -l 0\n";
        echo "graph_vlabel memory in bytes\n";
        echo "graph_scale yes\n";
        echo "graph_category php\n";

        # Create and print labels
        echo "free.label free\n";
        echo "free.draw AREASTACK\n";
        echo "free.min 0\n";

        echo "used.label used\n";
        echo "used.draw AREASTACK\n";
        echo "used.min 0\n";

        echo "overhead.label overhead\n";
        echo "overhead.draw AREASTACK\n";
        echo "overhead.min 0\n";
    }

    if ($printValues) {
        echo "used.value {$stats['used']}\n";
        echo "free.value {$stats['free']}\n";
        echo "overhead.value {$stats['overhead']}\n";
    }

	echo "\n";
}
