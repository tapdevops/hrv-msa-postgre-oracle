#!/bin/sh
while true; do
    php artisan Kafka:HRV_MSA_PROCESS_TR_DELIVERY_ITEM_D
done
