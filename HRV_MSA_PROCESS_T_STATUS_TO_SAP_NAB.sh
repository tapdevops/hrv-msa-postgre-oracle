#!/bin/sh
while true; do
    php artisan Kafka:HRV_MSA_PROCESS_T_STATUS_TO_SAP_NAB
done
