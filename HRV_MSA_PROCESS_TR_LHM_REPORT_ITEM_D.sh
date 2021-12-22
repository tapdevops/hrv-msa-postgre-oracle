#!/bin/sh
while true; do
    php artisan Kafka:HRV_MSA_PROCESS_TR_LHM_REPORT_ITEM_D
done
