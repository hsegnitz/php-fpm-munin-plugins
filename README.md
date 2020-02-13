# php-fpm-munin-plugins

## mission statement
Inspired by tjstein / php5-fpm-munin-plugins, this is a PHP rewrite aiming to improve the following aspects:
* multigraph: only read status info once and print out multiple graphs
* multipool: php-fpm allows for multiple pools running on the same machine, we combine them into one graph
* opcache: as a separate plugin opcache monitoring within the same pools

## installation

### status page

Per domain that uses it's own php-fpm pool, you need to allow loading of the status page.
This can be achieved with the following block **per vhost**:

```
location ~ ^/(status|ping|opcacheinfo.php)$ {
    include fastcgi_params;
    fastcgi_pass unix:/var/run/php/php7.2-fpm.sock;
    fastcgi_param SCRIPT_FILENAME $fastcgi_script_name;
    allow 127.0.0.1;
    allow 192.168.0.0/24;
    allow ::1;
    deny all;
}
```
You might need to `allow` your global IPs if you go by domains only with their public IPs.

### munin config

Config needs to store pool name and domain name to access the status page.

```
[phpfpm*]
  env.phpbin php-fpm
  env.pool_www localhost
  env.pool_example example.org
```

### symlinks

* `git clone $url /opt/php-fpm-munin-plugins`
* `chmod 777 /opt/php-fpm-munin-plugins/*.php`
* `cd /etc/munin/plugins`
* `ln -s /opt/php-fpm-munin-plugins/*.php .`

### opcache monitoring

* create a symlink for public/opcacheinfo.php into the document root of each
  domain used to monitor the respective pool.
