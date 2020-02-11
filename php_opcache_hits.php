#!/usr/bin/php
<?php

/**
 * Plugin to draw a graph on APC Cache Hits
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
	$hits   = 0;
	$misses = 0;

	foreach ($GLOBALS['request_urls'] as $url) {
		$data   = unserialize(file_get_contents($url));
		$hits   = $data['opcache_statistics']['hits'];
		$misses = $data['opcache_statistics']['misses'];
	}

	echo "hit.value {$hits}\n";
	echo "miss.value {$misses}\n";
}

if(array_key_exists(1, $argv) && 'autoconf' === $argv[1])
{
	if(!file_exists($home))
	{
		echo "no\n";
	}
	else
	{
		echo "yes\n";
	}
	die();
}

if(array_key_exists(1, $argv) && 'config' === $argv[1])
{
	# The headers
	print "graph_title Opcache Cache Hits\n";
	print "graph_args --base 1000 -l 0 -r\n";
	print "graph_vlabel per second\n";
	print "graph_total Total\n";
	print "graph_scale yes\n";
	print "graph_category php_opcache\n";

	# Create and print labels
	print "hit.label hits\n";
	print "hit.type DERIVE\n";
	print "hit.draw AREA\n";
	print "hit.min 0\n";
	print "hit.max 4200000\n";
	print "miss.label opcode misses\n";
	print "miss.type DERIVE\n";
	print "miss.draw STACK\n";
	print "miss.min 0\n";
	print "miss.max 4200000\n";

    if (!isset($_SERVER['MUNIN_CAP_DIRTYCONFIG']) || $_SERVER['MUNIN_CAP_DIRTYCONFIG'] != '1') {
        die();
    }
}

print_values();
