#!/bin/sh
while true; do
    php artisan Kafka:HRV_MSA_PROCESS_TR_HARVEST_ACTIVITY_USER_D
done
