#!/bin/sh
#Kill All Artisan Kafka Process
pkill -f "php artisan Kafka:"

#PROD
#cd /var/www/html/inspection_web/tapmi 
#nohup php artisan Kafka:INS_MSA_EBCCVAL_TR_EBCC_VALIDATION_H &
#nohup php artisan Kafka:INS_MSA_EBCCVAL_TR_EBCC_VALIDATION_D &
#nohup php artisan Kafka:INS_MSA_FINDING_TR_FINDING &
#nohup php artisan Kafka:INS_MSA_INSPECTION_TR_INSPECTION_GENBA &
#nohup php artisan Kafka:INS_MSA_INSPECTION_TR_BLOCK_INSPECTION_H &
#nohup php artisan Kafka:INS_MSA_INSPECTION_TR_BLOCK_INSPECTION_D &
#nohup php artisan Kafka:INS_MSA_INSPECTION_TR_TRACK_INSPECTION &

#sleep 1

#QA
 cd /var/www/html/ins-qa-webs/tapmi 
 nohup php artisan Kafka:INS_MSA_EBCCVAL_TR_EBCC_VALIDATION_H &
 nohup php artisan Kafka:INS_MSA_EBCCVAL_TR_EBCC_VALIDATION_D &
 nohup php artisan Kafka:INS_MSA_AUTH_TM_USER_AUTH &
 nohup php artisan Kafka:INS_MSA_FINDING_TR_FINDING &
 nohup php artisan Kafka:INS_MSA_INSPECTION_TR_INSPECTION_GENBA &
 nohup php artisan Kafka:INS_MSA_INSPECTION_TR_BLOCK_INSPECTION_H &
 nohup php artisan Kafka:INS_MSA_INSPECTION_TR_BLOCK_INSPECTION_D &
 nohup php artisan Kafka:INS_MSA_INSPECTION_TR_TRACK_INSPECTION &

#sleep 1

#DEV
#cd /var/www/html/ins-dev-webs/tapmi 
#nohup php artisan Kafka:INS_MSA_AUTH_TM_USER_AUTH &

sleep 1

return 1
