<?php
/**
 * SIGED Keep-Alive Script
 * Este archivo responde a las peticiones de cron-job para evitar el reposo en Render.
 */
header('Content-Type: text/plain');
echo "SIGED_STATUS: ALIVE";
exit;