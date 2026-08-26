<?php
/**
 * ============================================================
 * MAIN CONFIGURATION FILE
 * ============================================================
 * Update the values below to match your environment.
 */

return [

    // ---------------- General settings ----------------
    'timezone' => 'Asia/Kuala_Lumpur',

    // ---------------- Database settings ----------------
    'db' => [
        'host'    => '127.0.0.1',
        'port'    => 3306,
        'name'    => 'synergy1_liewkimfar_website-monitor',
        'user'    => 'synergy1_yenping',
        'pass'    => 'R.zb0ZwEuGZ}*fW2',
        'charset' => 'utf8mb4',
    ],

    // ---------------- Telegram Bot settings ----------------
    // 1) Create a bot via @BotFather on Telegram -> get the bot token
    // 2) Message your bot, then visit:
    //    https://api.telegram.org/bot<TOKEN>/getUpdates
    //    to find your chat_id
    'telegram' => [
        'bot_token' => '8843850797:AAHwTVrnohbUajRjkyEbcLbazgV0NUozjpo',
        'chat_id'   => '5904580235',
    ],

    // ---------------- Monitoring settings ----------------
    'monitor' => [
        'timeout_seconds'   => 10,     // max seconds to wait for a website response
        'slow_threshold_ms' => 3000,   // response time (ms) considered "slow"
    ],

];
