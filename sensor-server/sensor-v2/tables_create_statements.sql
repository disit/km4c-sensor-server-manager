/* Sensor Server
   Copyright (C) 2017 DISIT Lab http://www.disit.org - University of Florence

   This program is free software: you can redistribute it and/or modify
   it under the terms of the GNU Affero General Public License as
   published by the Free Software Foundation, either version 3 of the
   License, or (at your option) any later version.

   This program is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
   GNU Affero General Public License for more details.

   You should have received a copy of the GNU Affero General Public License
   along with this program.  If not, see <http://www.gnu.org/licenses/>. */


use sensors;
CREATE TABLE `sensors` (
  `idmeasure` int(11) NOT NULL AUTO_INCREMENT,
  `UUID` varchar(45) COLLATE utf8_unicode_ci DEFAULT '',
  `id` varchar(35) COLLATE utf8_unicode_ci DEFAULT '',
  `sender_IP` varchar(25) COLLATE utf8_unicode_ci NOT NULL,
  `date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `type` varchar(45) COLLATE utf8_unicode_ci NOT NULL,
  `latitude` double NOT NULL DEFAULT '0',
  `longitude` double NOT NULL DEFAULT '0',
  `network_name` varchar(45) COLLATE utf8_unicode_ci DEFAULT '',
  `sensor_name` varchar(45) COLLATE utf8_unicode_ci DEFAULT '',
  `MAC_address` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
  `power` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
  `rssi` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
  `minor` int(11) DEFAULT NULL,
  `major` int(11) DEFAULT NULL,
  `frequency` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
  `capabilities` varchar(125) COLLATE utf8_unicode_ci DEFAULT NULL,
  `speed` double DEFAULT NULL,
  `altitude` double DEFAULT NULL,
  `provider` varchar(45) COLLATE utf8_unicode_ci DEFAULT '',
  `accuracy` double DEFAULT NULL,
  `heading` double DEFAULT NULL,
  `lat_pre_scan` double DEFAULT NULL,
  `long_pre_scan` double DEFAULT NULL,
  `date_pre_scan` datetime DEFAULT NULL,
  `device_id` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `frequency_n` int(11) DEFAULT NULL,
  `power_n` int(11) DEFAULT NULL,
  `rssi_n` int(11) DEFAULT NULL,
  `device_model` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `status` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
  `prev_status` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
  `appID` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
  `version` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
  `lang` varchar(10) COLLATE utf8_unicode_ci DEFAULT NULL,
  `uid2` varchar(64) CHARACTER SET utf8 DEFAULT NULL,
  `profile` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`idmeasure`) USING BTREE,
  KEY `idx_sensors_device_id_date` (`device_id`,`date`),
  KEY `idx_sensors_date` (`date`),
  KEY `idx_sensors_latitude_longitude` (`latitude`,`longitude`)
) ENGINE=InnoDB AUTO_INCREMENT=3428048 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE `user_eval` (
  `user_eval_id` int(11) NOT NULL AUTO_INCREMENT,
  `date` datetime DEFAULT NULL,
  `device_id` varchar(64) DEFAULT NULL,
  `latitude` double DEFAULT NULL,
  `longitude` double DEFAULT NULL,
  `cc_x` int(11) DEFAULT NULL,
  `cc_y` int(11) DEFAULT NULL,
  `speed` double DEFAULT NULL,
  `altitude` double DEFAULT NULL,
  `provider` varchar(45) DEFAULT NULL,
  `accuracy` varchar(45) DEFAULT NULL,
  `heading` varchar(45) DEFAULT NULL,
  `lin_acc_x` double DEFAULT NULL,
  `lin_acc_y` double DEFAULT NULL,
  `lin_acc_z` double DEFAULT NULL,
  `avg_lin_acc_magn` double DEFAULT NULL,
  `avg_speed` double DEFAULT NULL,
  `lat_pre_scan` double DEFAULT NULL,
  `long_pre_scan` double DEFAULT NULL,
  `date_pre_scan` datetime DEFAULT NULL,
  `prev_status` varchar(45) DEFAULT NULL,
  `curr_status` varchar(45) DEFAULT NULL,
  `curr_status_new` varchar(45) DEFAULT NULL,
  `curr_status_time_new` double DEFAULT NULL,
  `lat_centroid` double DEFAULT NULL,
  `lon_centroid` double DEFAULT NULL,
  `last_status_row` int(11) DEFAULT NULL,
  `appID` varchar(45) DEFAULT NULL,
  `version` varchar(45) DEFAULT NULL,
  `lang` varchar(10) DEFAULT NULL,
  `uid2` varchar(64) DEFAULT NULL,
  `profile` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`user_eval_id`),
  KEY `idx_user_eval_cc_x_cc_y` (`cc_x`,`cc_y`),
  KEY `idx_user_eval_device_id_date` (`device_id`,`date`),
  KEY `idx_user_eval_date` (`date`),
  KEY `idx_user_eval_curr_status_new` (`curr_status_new`),
  KEY `idx_user_eval_latitude_longitude` (`latitude`,`longitude`),
  KEY `Index_7` (`user_eval_id`,`date`),
  KEY `idx_user_eval_curr_status_time_new` (`curr_status_time_new`),
  KEY `idx_user_eval_last_status_row` (`last_status_row`)
) ENGINE=InnoDB AUTO_INCREMENT=599370 DEFAULT CHARSET=utf8;