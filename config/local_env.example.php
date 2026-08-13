<?php
/**
 * TEMPLATE — copy this file to "local_env.php" and fill in your own
 * Gmail App Password. "local_env.php" is gitignored and will never be
 * committed or pushed to GitHub.
 *
 * On Railway, do NOT use this file at all — set SMTP_USER and SMTP_PASS
 * directly in the "Variables" tab of your Railway service instead.
 */
putenv('SMTP_USER=your_admin_gmail@gmail.com');
putenv('SMTP_PASS=your_16_character_app_password');
