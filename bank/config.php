<?php
// Database settings: create a dedicated MySQL user with only SELECT/INSERT/UPDATE rights in production.
const DB_HOST = '127.0.0.1';
const DB_PORT = 3307;
const DB_NAME = 'bank_db';
const DB_USER = 'root';
const DB_PASS = '';
const MAX_TXN = 500000;        // per-transaction limit
const SESSION_TTL = 900;       // 15 min inactivity logout
const MAX_LOGIN_FAILS = 5;     // then lock for 15 min
