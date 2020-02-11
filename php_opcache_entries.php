#!/usr/bin/php
<?php

/**
 * Plugin to draw a graph on Number of APC Cache Entries
 *
 * @copyright  Holger Segnitz - 2009
 * @author     Holger Segnitz
 * @version    $Id$
 * @filesource $HeadURL$
 */

# Parameters understood:
#
#       config   (required)
#       autoconf (optional - used by munin-config)
#
# Environment
#				nothing
#
#
# Magic markers (optional - used by munin-config and installation
# scripts):
#
#%# family=php
#%# capabilities=autoconf

$request_urls = array(
	'http://home.segnitz.net/opcacheinfo.php',
);

#seg_size
#avail_mem

function print_values()
{
	$num_system = 0;

	foreach ($GLOBALS['request_urls'] as $url) {
		$data = unserialize(file_get_contents($url));
		$num_system = $data['opcache_statistics']['num_cached_scripts'];
	}

	echo "opcode.value {$num_system}\n";
}

if (array_key_exists(1, $argv) && 'autoconf' === $argv[1]) {
	if (!file_exists($home)) {
		echo "no\n";
	} else {
		echo "yes\n";
	}
	die();
}

if (array_key_exists(1, $argv) && 'config' === $argv[1]) {
	# The headers
	print "graph_title PHP OpCache Entries\n";
	print "graph_args --base 1000 -l 0\n";
	print "graph_vlabel n\n";
	print "graph_scale yes\n";
	print "graph_category php_opcache\n";
	echo "graph_total total\n";
	
	# Create and print labels
	print "opcode.label opcode\n";
	print "opcode.draw AREA\n";

    if (!isset($_SERVER['MUNIN_CAP_DIRTYCONFIG']) || $_SERVER['MUNIN_CAP_DIRTYCONFIG'] != '1') {
        die();
    }
}

print_values();
