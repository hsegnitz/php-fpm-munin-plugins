<?php

// put this file into the root of your domain
// symlink should be fine.

echo serialize(opcache_get_status(false));
