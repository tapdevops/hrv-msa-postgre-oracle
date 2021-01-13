#!/bin/sh
#Kill All Artisan Kafka Process
pkill -f "php artisan Kafka:HRV_"

#PROD
#cd /var/www/html/hrv-msa-postgre-oracle
#  nohup php artisan Kafka:HRV_MSA_PROCESS_TR_DELIVERY_H &
#  nohup php artisan Kafka:HRV_MSA_PROCESS_TR_DELIVERY_ITEM_D &
#  nohup php artisan Kafka:HRV_MSA_PROCESS_TR_DELIVERY_USER_D &
#  nohup php artisan Kafka:HRV_MSA_PROCESS_TR_DELIVERY_VEHICLE_D &
#  nohup php artisan Kafka:HRV_MSA_PROCESS_TR_HARVEST_ACTIVITY_H &
#  nohup php artisan Kafka:HRV_MSA_PROCESS_TR_HARVEST_ACTIVITY_ITEM_D &
#  nohup php artisan Kafka:HRV_MSA_PROCESS_TR_HARVEST_ACTIVITY_USER_D &
#  nohup php artisan Kafka:HRV_MSA_PROCESS_TR_HARVEST_REPORT_H &
#  nohup php artisan Kafka:HRV_MSA_PROCESS_TR_HARVEST_REPORT_ITEM_D &
#  nohup php artisan Kafka:HRV_MSA_PROCESS_TR_HARVEST_REPORT_USER_D &
#  nohup php artisan Kafka:HRV_MSA_PROCESS_TR_LHM_REPORT_H &
#  nohup php artisan Kafka:HRV_MSA_PROCESS_TR_LHM_REPORT_ITEM_D &
#  nohup php artisan Kafka:HRV_MSA_PROCESS_T_STATUS_TO_SAP_DENDA_PANEN &
#  nohup php artisan Kafka:HRV_MSA_PROCESS_T_STATUS_TO_SAP_EBCC &
#  nohup php artisan Kafka:HRV_MSA_PROCESS_T_STATUS_TO_SAP_NAB &

#sleep 1

#QA
 cd /var/www/html/hrv-msa-qa-postgre-oracle
 nohup php artisan Kafka:HRV_MSA_PROCESS_TR_DELIVERY_H &
 nohup php artisan Kafka:HRV_MSA_PROCESS_TR_DELIVERY_ITEM_D &
 nohup php artisan Kafka:HRV_MSA_PROCESS_TR_DELIVERY_USER_D &
 nohup php artisan Kafka:HRV_MSA_PROCESS_TR_DELIVERY_VEHICLE_D &
 nohup php artisan Kafka:HRV_MSA_PROCESS_TR_HARVEST_ACTIVITY_H &
 nohup php artisan Kafka:HRV_MSA_PROCESS_TR_HARVEST_ACTIVITY_ITEM_D &
 nohup php artisan Kafka:HRV_MSA_PROCESS_TR_HARVEST_ACTIVITY_USER_D &
 nohup php artisan Kafka:HRV_MSA_PROCESS_TR_HARVEST_REPORT_H &
 nohup php artisan Kafka:HRV_MSA_PROCESS_TR_HARVEST_REPORT_ITEM_D &
 nohup php artisan Kafka:HRV_MSA_PROCESS_TR_HARVEST_REPORT_USER_D &
 nohup php artisan Kafka:HRV_MSA_PROCESS_TR_LHM_REPORT_H &
 nohup php artisan Kafka:HRV_MSA_PROCESS_TR_LHM_REPORT_ITEM_D &
 nohup php artisan Kafka:HRV_MSA_PROCESS_T_STATUS_TO_SAP_DENDA_PANEN &
 nohup php artisan Kafka:HRV_MSA_PROCESS_T_STATUS_TO_SAP_EBCC &
 nohup php artisan Kafka:HRV_MSA_PROCESS_T_STATUS_TO_SAP_NAB &

#sleep 1

#DEV
#  cd /var/www/html/hrv-msa-dev-postgre-oracle
#  nohup php artisan Kafka:HRV_MSA_PROCESS_TR_DELIVERY_H &
#  nohup php artisan Kafka:HRV_MSA_PROCESS_TR_DELIVERY_ITEM_D &
#  nohup php artisan Kafka:HRV_MSA_PROCESS_TR_DELIVERY_USER_D &
#  nohup php artisan Kafka:HRV_MSA_PROCESS_TR_DELIVERY_VEHICLE_D &
#  nohup php artisan Kafka:HRV_MSA_PROCESS_TR_HARVEST_ACTIVITY_H &
#  nohup php artisan Kafka:HRV_MSA_PROCESS_TR_HARVEST_ACTIVITY_ITEM_D &
#  nohup php artisan Kafka:HRV_MSA_PROCESS_TR_HARVEST_ACTIVITY_USER_D &
#  nohup php artisan Kafka:HRV_MSA_PROCESS_TR_HARVEST_REPORT_H &
#  nohup php artisan Kafka:HRV_MSA_PROCESS_TR_HARVEST_REPORT_ITEM_D &
#  nohup php artisan Kafka:HRV_MSA_PROCESS_TR_HARVEST_REPORT_USER_D &
#  nohup php artisan Kafka:HRV_MSA_PROCESS_TR_LHM_REPORT_H &
#  nohup php artisan Kafka:HRV_MSA_PROCESS_TR_LHM_REPORT_ITEM_D &
#  nohup php artisan Kafka:HRV_MSA_PROCESS_T_STATUS_TO_SAP_DENDA_PANEN &
#  nohup php artisan Kafka:HRV_MSA_PROCESS_T_STATUS_TO_SAP_EBCC &
#  nohup php artisan Kafka:HRV_MSA_PROCESS_T_STATUS_TO_SAP_NAB &

sleep 1

return 1
