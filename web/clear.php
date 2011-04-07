<?php

require_once __DIR__.'/../app/bootstrap.php.cache';
require '../app/autoload.php';

use Symfony\Component\HttpKernel\Util\Filesystem;

$file = new Filesystem();
$file->remove(sys_get_temp_dir() . '/sf2standard');
