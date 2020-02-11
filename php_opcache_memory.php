#!/usr/bin/php
<?php

/**
 * Plugin to draw a graph on APC Shared Memory Allocation
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
	$used     = 0;
	$overhead = 0;
	$free     = 0;


	foreach ($GLOBALS['request_urls'] as $url) {
		$data = unserialize(file_get_contents($url));
		$used = $data['memory_usage']['used_memory'];
		$overhead = $data['memory_usage']['wasted_memory'];
		$free     = $data['memory_usage']['free_memory'];
	}

	echo "used.value $used\n";
	echo "overhead.value $overhead\n";
	echo "free.value $free\n";
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
	print "graph_title Opcache Shared Memory Allocation\n";
	print "graph_args --base 1024 -l 0\n";
	print "graph_vlabel byte\n";
	print "graph_scale yes\n";
	print "graph_category php_opcache\n";

	# Create and print labels
	print "used.label used by opcode\n";
	print "used.draw AREA\n";
	print "overhead.label wasted by overhead\n";
	print "overhead.draw STACK\n";
	print "free.label free\n";
	print "free.draw STACK\n";

    if (!isset($_SERVER['MUNIN_CAP_DIRTYCONFIG']) || $_SERVER['MUNIN_CAP_DIRTYCONFIG'] != '1') {
        die();
    }
}

print_values();
