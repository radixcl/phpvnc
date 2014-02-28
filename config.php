<?php
$config = new stdClass();

$config->shm = new stdClass();
$config->shm->permissions = 0666;
$config->shm->size = (1024*10);
$config->shm->key = '18638202';

define('_DEBUG', true);
