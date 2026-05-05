<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('UTC');

const APP_NAME = 'Hospital Management System';
const BASE_URL = '/HMS_M';
