<?php

require_once(dirname(__FILE__) . '/../../config.php');
header('Content-Type: text/javascript');
header('Service-Worker-Allowed: /');

global $CFG;

readfile($CFG->dirroot . '/filter/edusharing/amd/build/assets/edu-service-worker.js');
