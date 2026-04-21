-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Jan 26, 2025 at 07:42 PM
-- Server version: 10.3.39-MariaDB-log
-- PHP Version: 8.1.31

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `itccam5_itcph2`
--

-- --------------------------------------------------------

--
-- Table structure for table `tblattendance`
--

CREATE TABLE `tblattendance` (
  `att_id` int(10) NOT NULL,
  `resp_id` int(10) NOT NULL DEFAULT 0,
  `client_id` tinyint(3) NOT NULL DEFAULT 0,
  `project_id` smallint(5) NOT NULL DEFAULT 0,
  `team_id` smallint(5) NOT NULL DEFAULT 0,
  `s_id` int(11) NOT NULL DEFAULT 0,
  `uni_id` varchar(40) DEFAULT NULL,
  `mob_img_id` varchar(40) DEFAULT NULL,
  `call_type` enum('0','1') NOT NULL DEFAULT '0' COMMENT '0=Attendance,1=Dayend',
  `other_details` text DEFAULT NULL,
  `quiz_received_score` tinyint(3) NOT NULL DEFAULT 0,
  `quiz_target_score` tinyint(3) NOT NULL DEFAULT 0,
  `summary_updated` enum('0','1') NOT NULL DEFAULT '0' COMMENT '0=Not updated,1=Updated',
  `pickup_stock_updated` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0=Not updated,1=Updated	',
  `capture_date` date DEFAULT NULL,
  `capture_datetime` datetime DEFAULT NULL,
  `lt` double NOT NULL DEFAULT 0,
  `lg` double NOT NULL DEFAULT 0,
  `dstatus` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0=Not deleted,1=Deleted',
  `creator_id` smallint(5) NOT NULL DEFAULT 0,
  `rcd` date DEFAULT NULL,
  `rdt` datetime DEFAULT NULL,
  `modif_id` smallint(5) NOT NULL DEFAULT 0,
  `rlm` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblbranch`
--

CREATE TABLE `tblbranch` (
  `branch_id` int(11) NOT NULL,
  `branch_name` varchar(30) NOT NULL,
  `main_branch` varchar(20) DEFAULT NULL,
  `to_email` varchar(1000) NOT NULL,
  `cc_email` varchar(400) NOT NULL,
  `dstatus` int(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblbranch_keydetails`
--

CREATE TABLE `tblbranch_keydetails` (
  `pro_id` int(10) NOT NULL,
  `year` int(4) DEFAULT NULL,
  `month` int(2) DEFAULT NULL,
  `branch_id` int(10) DEFAULT NULL,
  `main_branch` varchar(50) DEFAULT NULL,
  `active_teams` int(10) DEFAULT NULL,
  `avg_route_perteam` int(10) DEFAULT NULL,
  `ds_having6routes` int(10) DEFAULT NULL,
  `ds_having15att` int(10) DEFAULT NULL,
  `ds_having15qulatt` int(10) DEFAULT NULL,
  `ttl_outlets` int(10) DEFAULT NULL,
  `ds_havingless60ol` int(10) DEFAULT NULL,
  `ttl_unique_revisit_ol` int(10) DEFAULT NULL,
  `ds_90p_unique_ol` int(10) DEFAULT NULL,
  `ds_less_12ol_perday` int(10) DEFAULT NULL,
  `avg_time_perday_hr` float DEFAULT NULL,
  `avg_distance_perday_km` float DEFAULT NULL,
  `avg_day_stockissue` int(10) DEFAULT NULL,
  `avg_ttlstock_perds` int(10) DEFAULT NULL,
  `avg_ttlsell_perds` int(10) DEFAULT NULL,
  `no_of_p1ds` int(10) DEFAULT NULL,
  `rlm` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblbranch_pickupstock_products`
--

CREATE TABLE `tblbranch_pickupstock_products` (
  `rec_id` int(11) NOT NULL,
  `branch_id` int(10) NOT NULL,
  `json_id` int(11) NOT NULL DEFAULT 0,
  `team_type` int(10) NOT NULL DEFAULT 0 COMMENT '0=Van DS, 1=Hybrid, 2=Town SWD',
  `is_focusbrand` int(1) NOT NULL DEFAULT 0,
  `category_name` varchar(100) DEFAULT NULL,
  `product_name` varchar(40) NOT NULL,
  `summary_column_name` varchar(100) DEFAULT NULL,
  `net_rate` double NOT NULL DEFAULT 1,
  `sort_order` smallint(6) NOT NULL DEFAULT 0 COMMENT 'It should be as per products order in JSON',
  `rcd` date DEFAULT NULL,
  `rdt` datetime DEFAULT NULL,
  `dstatus` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Each branch can have max 60 products';

-- --------------------------------------------------------

--
-- Table structure for table `tblbreak`
--

CREATE TABLE `tblbreak` (
  `break_id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `team_id` int(11) NOT NULL,
  `start_datetime` datetime NOT NULL,
  `end_datetime` datetime NOT NULL,
  `break_duration_in_sec` int(11) NOT NULL DEFAULT 0,
  `reason` varchar(40) DEFAULT NULL,
  `lt` double NOT NULL DEFAULT 0,
  `lg` double NOT NULL DEFAULT 0,
  `dstatus` tinyint(4) NOT NULL DEFAULT 0,
  `creator_id` smallint(5) NOT NULL DEFAULT 0,
  `rcd` date NOT NULL,
  `rdt` datetime NOT NULL,
  `modif_id` smallint(5) NOT NULL DEFAULT 0,
  `rlm` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblclients`
--

CREATE TABLE `tblclients` (
  `client_id` tinyint(3) NOT NULL,
  `client_name` varchar(50) NOT NULL,
  `client_dir_path` varchar(70) NOT NULL,
  `client_desc` varchar(150) NOT NULL,
  `image_name` varchar(120) NOT NULL,
  `dstatus` tinyint(1) NOT NULL DEFAULT 0,
  `creator_id` smallint(5) NOT NULL DEFAULT 0,
  `rcd` date DEFAULT NULL,
  `rdt` datetime DEFAULT NULL,
  `modif_id` smallint(5) NOT NULL DEFAULT 0,
  `rlm` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblcloudring_live`
--

CREATE TABLE `tblcloudring_live` (
  `rec_id` int(10) NOT NULL,
  `dup_process` enum('0','1') NOT NULL DEFAULT '0' COMMENT '0=Not Process,1=Processed',
  `dup_processed_on` datetime DEFAULT NULL,
  `process` enum('0','1') NOT NULL DEFAULT '0' COMMENT '0=Not Process,1=Processed',
  `processed_on` datetime DEFAULT NULL,
  `token` varchar(10) NOT NULL,
  `rec_who` varchar(15) NOT NULL,
  `rec_circle` varchar(40) DEFAULT NULL,
  `rec_op` varchar(30) DEFAULT NULL,
  `rec_misscallno` varchar(20) DEFAULT NULL,
  `api_output` text DEFAULT NULL,
  `dstatus` tinyint(1) NOT NULL DEFAULT 0,
  `creator_id` smallint(5) NOT NULL DEFAULT 0,
  `rcd` date DEFAULT NULL,
  `rdt` datetime DEFAULT NULL,
  `modif_id` smallint(5) NOT NULL DEFAULT 0,
  `rlm` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblcloudring_live_login`
--

CREATE TABLE `tblcloudring_live_login` (
  `rec_id` int(10) NOT NULL,
  `dup_process` enum('0','1') NOT NULL DEFAULT '0' COMMENT '0=Not Process,1=Processed',
  `dup_processed_on` datetime DEFAULT NULL,
  `process` enum('0','1') NOT NULL DEFAULT '0' COMMENT '0=Not Process,1=Processed',
  `processed_on` datetime DEFAULT NULL,
  `token` varchar(10) NOT NULL,
  `rec_who` varchar(15) NOT NULL,
  `rec_circle` varchar(40) DEFAULT NULL,
  `rec_op` varchar(30) DEFAULT NULL,
  `rec_misscallno` varchar(20) DEFAULT NULL,
  `api_output` text DEFAULT NULL,
  `dstatus` tinyint(1) NOT NULL DEFAULT 0,
  `creator_id` smallint(5) NOT NULL DEFAULT 0,
  `rcd` date DEFAULT NULL,
  `rdt` datetime DEFAULT NULL,
  `modif_id` smallint(5) NOT NULL DEFAULT 0,
  `rlm` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblconstants`
--

CREATE TABLE `tblconstants` (
  `con_id` int(11) NOT NULL,
  `con_name` varchar(100) DEFAULT NULL,
  `con_value` varchar(100) DEFAULT NULL,
  `con_desc` text DEFAULT NULL,
  `dstatus` tinyint(4) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblgroups`
--

CREATE TABLE `tblgroups` (
  `group_id` tinyint(3) NOT NULL,
  `group_name` varchar(40) NOT NULL,
  `role_permission` mediumtext NOT NULL,
  `dstatus` tinyint(1) NOT NULL DEFAULT 0,
  `creator_id` smallint(5) NOT NULL DEFAULT 0,
  `rcd` date DEFAULT NULL,
  `rdt` datetime DEFAULT NULL,
  `modif_id` smallint(5) NOT NULL DEFAULT 0,
  `rlm` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblmobile_calendar_data`
--

CREATE TABLE `tblmobile_calendar_data` (
  `mcd_id` bigint(20) NOT NULL,
  `client_id` int(11) NOT NULL DEFAULT 0,
  `project_id` int(11) NOT NULL DEFAULT 0,
  `team_id` int(11) NOT NULL,
  `year` int(11) NOT NULL,
  `month` tinyint(4) NOT NULL COMMENT '1 means January',
  `day_1` int(11) NOT NULL DEFAULT 0,
  `day_2` int(11) NOT NULL DEFAULT 0,
  `day_3` int(11) NOT NULL DEFAULT 0,
  `day_4` int(11) NOT NULL DEFAULT 0,
  `day_5` int(11) NOT NULL DEFAULT 0,
  `day_6` int(11) NOT NULL DEFAULT 0,
  `day_7` int(11) NOT NULL DEFAULT 0,
  `day_8` int(11) NOT NULL DEFAULT 0,
  `day_9` int(11) NOT NULL DEFAULT 0,
  `day_10` int(11) NOT NULL DEFAULT 0,
  `day_11` int(11) NOT NULL DEFAULT 0,
  `day_12` int(11) NOT NULL DEFAULT 0,
  `day_13` int(11) NOT NULL DEFAULT 0,
  `day_14` int(11) NOT NULL DEFAULT 0,
  `day_15` int(11) NOT NULL DEFAULT 0,
  `day_16` int(11) NOT NULL DEFAULT 0,
  `day_17` int(11) NOT NULL DEFAULT 0,
  `day_18` int(11) NOT NULL DEFAULT 0,
  `day_19` int(11) NOT NULL DEFAULT 0,
  `day_20` int(11) NOT NULL DEFAULT 0,
  `day_21` int(11) NOT NULL DEFAULT 0,
  `day_22` int(11) NOT NULL DEFAULT 0,
  `day_23` int(11) NOT NULL DEFAULT 0,
  `day_24` int(11) NOT NULL DEFAULT 0,
  `day_25` int(11) NOT NULL DEFAULT 0,
  `day_26` int(11) NOT NULL DEFAULT 0,
  `day_27` int(11) NOT NULL DEFAULT 0,
  `day_28` int(11) NOT NULL DEFAULT 0,
  `day_29` int(11) NOT NULL DEFAULT 0,
  `day_30` int(11) NOT NULL DEFAULT 0,
  `day_31` int(11) NOT NULL DEFAULT 0,
  `dstatus` tinyint(4) NOT NULL DEFAULT 0,
  `creator_id` int(11) NOT NULL DEFAULT 0,
  `rcd` date DEFAULT NULL,
  `rdt` datetime DEFAULT NULL,
  `modif_id` int(11) NOT NULL DEFAULT 0,
  `rlm` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci COMMENT='Store calendar data used to create summary';

-- --------------------------------------------------------

--
-- Table structure for table `tblmobile_calendar_summary`
--

CREATE TABLE `tblmobile_calendar_summary` (
  `mcs_id` bigint(20) NOT NULL,
  `client_id` int(11) NOT NULL DEFAULT 0,
  `project_id` int(11) NOT NULL DEFAULT 0,
  `team_id` int(11) NOT NULL,
  `summary` text DEFAULT NULL,
  `activity_date` date DEFAULT NULL,
  `activity_datetime` datetime DEFAULT NULL,
  `dstatus` tinyint(1) NOT NULL DEFAULT 0,
  `rlm` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Store calendar summary in mobile format';

-- --------------------------------------------------------

--
-- Table structure for table `tblmobile_calendar_summary_keydetails`
--

CREATE TABLE `tblmobile_calendar_summary_keydetails` (
  `ms_id` bigint(11) NOT NULL,
  `branch_id` int(11) DEFAULT NULL,
  `team_id` int(11) DEFAULT NULL,
  `total_meter_travelled` float NOT NULL,
  `time_spent_today` varchar(70) DEFAULT NULL,
  `planned_outlets` int(11) NOT NULL DEFAULT 0,
  `sell_in_shops_count_today` int(11) NOT NULL DEFAULT 0,
  `oulet_covered_today` int(11) NOT NULL DEFAULT 0,
  `other_sell_in_shops_count_today` int(11) NOT NULL DEFAULT 0,
  `add_oulet_covered_today` int(11) NOT NULL DEFAULT 0,
  `total_sales_today` varchar(11) NOT NULL DEFAULT '0',
  `dstatus` int(1) NOT NULL DEFAULT 0,
  `rcd` date DEFAULT NULL,
  `rdt` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblmobile_summary`
--

CREATE TABLE `tblmobile_summary` (
  `ms_id` bigint(11) NOT NULL,
  `branch_id` int(11) DEFAULT NULL,
  `team_id` int(11) DEFAULT NULL,
  `total_meter_travelled` float NOT NULL,
  `time_spent_today` varchar(70) DEFAULT NULL,
  `planned_outlets` int(11) NOT NULL DEFAULT 0,
  `planned_outlets_mtd` int(11) NOT NULL DEFAULT 0,
  `total_shops_count` int(11) NOT NULL DEFAULT 0,
  `sell_in_shops_count_today` int(11) NOT NULL DEFAULT 0,
  `sell_in_shops_count_mtd` int(11) NOT NULL DEFAULT 0,
  `oulet_covered_today` int(11) NOT NULL DEFAULT 0,
  `oulet_covered_mtd` int(11) NOT NULL DEFAULT 0,
  `other_sell_in_shops_count_today` int(11) NOT NULL DEFAULT 0,
  `other_sell_in_shops_count_mtd` int(11) NOT NULL DEFAULT 0,
  `add_oulet_covered_today` int(11) NOT NULL DEFAULT 0,
  `add_oulet_covered_mtd` int(11) NOT NULL DEFAULT 0,
  `total_sales_today` float NOT NULL DEFAULT 0,
  `total_sales_mtd` float NOT NULL DEFAULT 0,
  `chart_response_current_month` text DEFAULT NULL,
  `chart_response_previous_month` text DEFAULT NULL,
  `dstatus` int(1) NOT NULL DEFAULT 0,
  `rcd` date DEFAULT NULL,
  `rdt` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblmodules`
--

CREATE TABLE `tblmodules` (
  `module_id` smallint(5) NOT NULL,
  `module_name` varchar(60) NOT NULL,
  `module_code` varchar(10) NOT NULL,
  `parent_module_code` varchar(10) NOT NULL,
  `module_component` varchar(100) DEFAULT NULL,
  `module_actioncode` varchar(10) NOT NULL,
  `module_url_link` varchar(100) NOT NULL,
  `module_icon` varchar(30) DEFAULT NULL,
  `module_position` varchar(15) NOT NULL,
  `module_sort` smallint(5) NOT NULL,
  `show_breadcrumb` enum('0','1') NOT NULL DEFAULT '0' COMMENT '0=Hide,1=Show',
  `dstatus` tinyint(1) NOT NULL DEFAULT 0,
  `creator_id` smallint(5) NOT NULL DEFAULT 0,
  `rcd` date DEFAULT NULL,
  `rdt` datetime DEFAULT NULL,
  `modif_id` smallint(5) NOT NULL DEFAULT 0,
  `rlm` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbloffline_dropdown_options`
--

CREATE TABLE `tbloffline_dropdown_options` (
  `odo_id` bigint(20) NOT NULL,
  `client_id` int(11) NOT NULL DEFAULT 0,
  `project_id` int(11) NOT NULL DEFAULT 0,
  `team_id` int(11) NOT NULL DEFAULT 0,
  `options_list` mediumtext DEFAULT NULL,
  `activity_date` date DEFAULT NULL,
  `activity_datetime` datetime DEFAULT NULL,
  `dstatus` tinyint(1) NOT NULL DEFAULT 0,
  `rlm` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblprojects`
--

CREATE TABLE `tblprojects` (
  `project_id` smallint(5) NOT NULL,
  `client_id` tinyint(3) NOT NULL,
  `project_name` varchar(60) NOT NULL,
  `dsh_modc` varchar(10) NOT NULL DEFAULT 'MKRT05',
  `dsh_pmodc` varchar(10) NOT NULL DEFAULT 'MKRT01',
  `require_agreement_upload` enum('0','1') NOT NULL DEFAULT '0' COMMENT '0=No,1=Yes. It means if user login on app, he has to upload agreement first',
  `dstatus` tinyint(1) NOT NULL DEFAULT 0,
  `creator_id` smallint(5) NOT NULL DEFAULT 0,
  `rcd` date DEFAULT NULL,
  `rdt` datetime DEFAULT NULL,
  `modif_id` smallint(5) NOT NULL DEFAULT 0,
  `rlm` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblproject_team`
--

CREATE TABLE `tblproject_team` (
  `team_id` smallint(5) NOT NULL,
  `project_id` smallint(5) NOT NULL,
  `team_name` varchar(50) NOT NULL,
  `is_type` int(1) NOT NULL DEFAULT 0 COMMENT '0 = DS,\r\n1 = Niche,\r\n2 = Town SWD,\r\n3 = Hybrid,\r\n4 = SCP',
  `s_id` int(20) NOT NULL DEFAULT 0 COMMENT '99=DS 100=WD 101=AE',
  `branch_id` int(2) NOT NULL,
  `wd_code` varchar(20) DEFAULT NULL COMMENT 'only put for 99 and 100 s_id',
  `circle` varchar(20) DEFAULT NULL,
  `section` varchar(20) DEFAULT NULL,
  `district` varchar(25) DEFAULT NULL,
  `ds_number` varchar(10) NOT NULL,
  `ae_name` varchar(50) DEFAULT NULL,
  `ae_number` varchar(100) DEFAULT NULL,
  `am_name` varchar(10) DEFAULT NULL,
  `am_number` int(10) DEFAULT NULL,
  `on_break` enum('0','1') NOT NULL DEFAULT '0' COMMENT '0=Not on break,1=On break',
  `uploaded_agreement` enum('0','1') NOT NULL DEFAULT '0' COMMENT '0=No, 1=Yes',
  `agreement_uni_id` varchar(40) DEFAULT NULL,
  `agreement_mob_img_id` varchar(40) DEFAULT NULL,
  `dstatus` tinyint(1) NOT NULL DEFAULT 0,
  `creator_id` smallint(5) NOT NULL DEFAULT 0,
  `rcd` date DEFAULT NULL,
  `rdt` datetime DEFAULT NULL,
  `modif_id` smallint(5) NOT NULL DEFAULT 0,
  `rlm` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblroute_details`
--

CREATE TABLE `tblroute_details` (
  `rec_id` int(6) NOT NULL,
  `resp_id` bigint(20) NOT NULL DEFAULT 0,
  `team_id` int(4) NOT NULL DEFAULT 0,
  `section_code` varchar(30) DEFAULT NULL,
  `state` varchar(20) DEFAULT NULL,
  `district` varchar(30) DEFAULT NULL,
  `sub_district_goi` varchar(50) DEFAULT NULL,
  `route_name` varchar(200) DEFAULT NULL,
  `beat_day` varchar(20) DEFAULT NULL,
  `outlet_name` varchar(250) DEFAULT NULL,
  `outlet_mobile` varchar(100) DEFAULT NULL,
  `market_name` varchar(400) DEFAULT NULL,
  `goi_market_id` varchar(200) DEFAULT NULL,
  `wd_code` varchar(10) DEFAULT NULL,
  `wd_town` varchar(35) DEFAULT NULL,
  `goi_pop_group` varchar(40) DEFAULT NULL,
  `ds_sify_id` varchar(15) DEFAULT NULL,
  `ds_mobile` varchar(10) DEFAULT NULL,
  `outlet_type` enum('ROC','Other','TOWN SWD','RURAL SWD','VILLAGE SWD') NOT NULL DEFAULT 'ROC',
  `shop_type` varchar(30) DEFAULT NULL,
  `shop_uniq_code_alpha` varchar(200) DEFAULT NULL,
  `shop_uniq_code` varchar(10) DEFAULT NULL,
  `capture_date` date DEFAULT NULL,
  `capture_datetime` datetime DEFAULT NULL,
  `lt` double NOT NULL DEFAULT 0,
  `lg` double NOT NULL DEFAULT 0,
  `dstatus` int(1) NOT NULL DEFAULT 0,
  `modifiedbyapp` int(1) NOT NULL DEFAULT 0,
  `rlm` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `sort_order` int(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblstock_summary`
--

CREATE TABLE `tblstock_summary` (
  `sp_id` bigint(20) NOT NULL,
  `team_id` int(11) NOT NULL DEFAULT 0,
  `capture_date` date DEFAULT NULL,
  `stock_type` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0=Attendance Pickup Qty',
  `rec_id` bigint(20) NOT NULL DEFAULT 0 COMMENT 'For stock_type=0,1, attendance ID, For stock_type=2, response id',
  `total_sale_product1` double NOT NULL DEFAULT 0,
  `total_sale_product2` double NOT NULL DEFAULT 0,
  `total_sale_product3` double NOT NULL DEFAULT 0,
  `total_sale_product4` double NOT NULL DEFAULT 0,
  `total_sale_product5` double NOT NULL DEFAULT 0,
  `total_sale_product6` double NOT NULL DEFAULT 0,
  `total_sale_product7` double NOT NULL DEFAULT 0,
  `total_sale_product8` double NOT NULL DEFAULT 0,
  `total_sale_product9` double NOT NULL DEFAULT 0,
  `total_sale_product10` double NOT NULL DEFAULT 0,
  `total_sale_product11` double NOT NULL DEFAULT 0,
  `total_sale_product12` double NOT NULL DEFAULT 0,
  `total_sale_product13` double NOT NULL DEFAULT 0,
  `total_sale_product14` double NOT NULL DEFAULT 0,
  `total_sale_product15` double NOT NULL DEFAULT 0,
  `total_sale_product16` double NOT NULL DEFAULT 0,
  `total_sale_product17` double NOT NULL DEFAULT 0,
  `total_sale_product18` double NOT NULL DEFAULT 0,
  `total_sale_product19` double NOT NULL DEFAULT 0,
  `total_sale_product20` double NOT NULL DEFAULT 0,
  `total_sale_product21` double NOT NULL DEFAULT 0,
  `total_sale_product22` double NOT NULL DEFAULT 0,
  `total_sale_product23` double NOT NULL DEFAULT 0,
  `total_sale_product24` double NOT NULL DEFAULT 0,
  `total_sale_product25` double NOT NULL DEFAULT 0,
  `total_sale_product26` double NOT NULL DEFAULT 0,
  `total_sale_product27` double NOT NULL DEFAULT 0,
  `total_sale_product28` double NOT NULL DEFAULT 0,
  `total_sale_product29` double NOT NULL DEFAULT 0,
  `total_sale_product30` double NOT NULL DEFAULT 0,
  `total_sale_product31` double NOT NULL DEFAULT 0,
  `total_sale_product32` double NOT NULL DEFAULT 0,
  `total_sale_product33` double NOT NULL DEFAULT 0,
  `total_sale_product34` double NOT NULL DEFAULT 0,
  `total_sale_product35` double NOT NULL DEFAULT 0,
  `total_sale_product36` double NOT NULL DEFAULT 0,
  `total_sale_product37` double NOT NULL DEFAULT 0,
  `total_sale_product38` double NOT NULL DEFAULT 0,
  `total_sale_product39` double NOT NULL DEFAULT 0,
  `total_sale_product40` double NOT NULL DEFAULT 0,
  `total_sale_product41` double NOT NULL DEFAULT 0,
  `total_sale_product42` double NOT NULL DEFAULT 0,
  `total_sale_product43` double NOT NULL DEFAULT 0,
  `total_sale_product44` double NOT NULL DEFAULT 0,
  `total_sale_product45` double NOT NULL DEFAULT 0,
  `total_sale_product46` double NOT NULL DEFAULT 0,
  `total_sale_product47` double NOT NULL DEFAULT 0,
  `total_sale_product48` double NOT NULL DEFAULT 0,
  `total_sale_product49` double NOT NULL DEFAULT 0,
  `total_sale_product50` double NOT NULL DEFAULT 0,
  `total_sale_product51` double NOT NULL DEFAULT 0,
  `total_sale_product52` double NOT NULL DEFAULT 0,
  `total_sale_product53` double NOT NULL DEFAULT 0,
  `total_sale_product54` double NOT NULL DEFAULT 0,
  `total_sale_product55` double NOT NULL DEFAULT 0,
  `total_sale_product56` double NOT NULL DEFAULT 0,
  `total_sale_product57` double NOT NULL DEFAULT 0,
  `total_sale_product58` double NOT NULL DEFAULT 0,
  `total_sale_product59` double NOT NULL DEFAULT 0,
  `total_sale_product60` double NOT NULL DEFAULT 0,
  `total_sale_product61` double NOT NULL DEFAULT 0,
  `total_sale_product62` double NOT NULL DEFAULT 0,
  `total_sale_product63` double NOT NULL DEFAULT 0,
  `total_sale_product64` double NOT NULL DEFAULT 0,
  `total_sale_product65` double NOT NULL DEFAULT 0,
  `total_sale_product66` double NOT NULL DEFAULT 0,
  `total_sale_product67` double NOT NULL DEFAULT 0,
  `total_sale_product68` double NOT NULL DEFAULT 0,
  `total_sale_product69` double NOT NULL DEFAULT 0,
  `total_sale_product70` double NOT NULL DEFAULT 0,
  `total_sale_product71` double NOT NULL DEFAULT 0,
  `total_sale_product72` double NOT NULL DEFAULT 0,
  `total_sale_product73` double NOT NULL DEFAULT 0,
  `total_sale_product74` double NOT NULL DEFAULT 0,
  `total_sale_product75` double NOT NULL DEFAULT 0,
  `total_sale_product76` double NOT NULL DEFAULT 0,
  `total_sale_product77` double NOT NULL DEFAULT 0,
  `total_sale_product78` double NOT NULL DEFAULT 0,
  `total_sale_product79` double NOT NULL DEFAULT 0,
  `total_sale_product80` double NOT NULL DEFAULT 0,
  `dstatus` tinyint(1) NOT NULL DEFAULT 0,
  `rcd` date DEFAULT NULL,
  `rdt` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblsurvey_response_details`
--

CREATE TABLE `tblsurvey_response_details` (
  `pro_id` int(11) NOT NULL,
  `resp_id` int(10) NOT NULL,
  `uni_id` varchar(40) NOT NULL,
  `client_id` tinyint(3) NOT NULL,
  `project_id` smallint(5) NOT NULL,
  `team_id` smallint(5) NOT NULL,
  `dup_processed` int(1) NOT NULL DEFAULT 0 COMMENT '0=Not processed,1=Processed',
  `dup_status` int(1) DEFAULT NULL COMMENT '0=Invalid Data,1=Miss call not found,2=Miss call not found but code matches,3=Duplicate,4=Call found but code not match,5=Valid,6=OTP Expire',
  `s_id` tinyint(3) NOT NULL DEFAULT 1,
  `imei` varchar(20) DEFAULT NULL,
  `call_time` bigint(20) NOT NULL DEFAULT 0,
  `capture_date` date NOT NULL,
  `capture_datetime` datetime NOT NULL,
  `lt` double NOT NULL,
  `lg` double NOT NULL,
  `dstatus` tinyint(1) NOT NULL DEFAULT 0,
  `netAmount` float NOT NULL DEFAULT 0,
  `discount` bigint(20) NOT NULL DEFAULT 0,
  `ques_0` varchar(50) DEFAULT NULL,
  `ques_1` text DEFAULT NULL COMMENT 'route',
  `ques_2` varchar(50) DEFAULT NULL,
  `ques_3` text DEFAULT NULL COMMENT 'shop_id',
  `ques_4` text DEFAULT NULL COMMENT 'Sale_status',
  `ques_5` text DEFAULT NULL,
  `ques_6` text DEFAULT NULL,
  `ques_7` text DEFAULT NULL,
  `ques_8` text DEFAULT NULL,
  `ques_9` text DEFAULT NULL,
  `ques_10` text DEFAULT NULL,
  `update_sale` int(1) NOT NULL DEFAULT 0 COMMENT '0=Not Updated,1=Updated',
  `update_distance` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0=Not Updated,1=Updated,2=Error i.e lt or lg is 0',
  `distance_in_meter` double NOT NULL DEFAULT 0,
  `total_sale_product1` double NOT NULL DEFAULT 0,
  `total_sale_product2` double NOT NULL DEFAULT 0,
  `total_sale_product3` double NOT NULL DEFAULT 0,
  `total_sale_product4` double NOT NULL DEFAULT 0,
  `total_sale_product5` double NOT NULL DEFAULT 0,
  `total_sale_product6` double NOT NULL DEFAULT 0,
  `total_sale_product7` double NOT NULL DEFAULT 0,
  `total_sale_product8` double NOT NULL DEFAULT 0,
  `total_sale_product9` double NOT NULL DEFAULT 0,
  `total_sale_product10` double NOT NULL DEFAULT 0,
  `total_sale_product11` double NOT NULL DEFAULT 0,
  `total_sale_product12` double NOT NULL DEFAULT 0,
  `total_sale_product13` double NOT NULL DEFAULT 0,
  `total_sale_product14` double NOT NULL DEFAULT 0,
  `total_sale_product15` double NOT NULL DEFAULT 0,
  `total_sale_product16` double NOT NULL DEFAULT 0,
  `total_sale_product17` double NOT NULL DEFAULT 0,
  `total_sale_product18` double NOT NULL DEFAULT 0,
  `total_sale_product19` double NOT NULL DEFAULT 0,
  `total_sale_product20` double NOT NULL DEFAULT 0,
  `total_sale_product21` double NOT NULL DEFAULT 0,
  `total_sale_product22` double NOT NULL DEFAULT 0,
  `total_sale_product23` double NOT NULL DEFAULT 0,
  `total_sale_product24` double NOT NULL DEFAULT 0,
  `total_sale_product25` double NOT NULL DEFAULT 0,
  `total_sale_product26` double NOT NULL DEFAULT 0,
  `total_sale_product27` double NOT NULL DEFAULT 0,
  `total_sale_product28` double NOT NULL DEFAULT 0,
  `total_sale_product29` double NOT NULL DEFAULT 0,
  `total_sale_product30` double NOT NULL DEFAULT 0,
  `total_sale_product31` double NOT NULL DEFAULT 0,
  `total_sale_product32` double NOT NULL DEFAULT 0,
  `total_sale_product33` double NOT NULL DEFAULT 0,
  `total_sale_product34` double NOT NULL DEFAULT 0,
  `total_sale_product35` double NOT NULL DEFAULT 0,
  `total_sale_product36` double NOT NULL DEFAULT 0,
  `total_sale_product37` double NOT NULL DEFAULT 0,
  `total_sale_product38` double NOT NULL DEFAULT 0,
  `total_sale_product39` double NOT NULL DEFAULT 0,
  `total_sale_product40` double NOT NULL DEFAULT 0,
  `total_sale_product41` double NOT NULL DEFAULT 0,
  `total_sale_product42` double NOT NULL DEFAULT 0,
  `total_sale_product43` double NOT NULL DEFAULT 0,
  `total_sale_product44` double NOT NULL DEFAULT 0,
  `total_sale_product45` double NOT NULL DEFAULT 0,
  `total_sale_product46` double NOT NULL DEFAULT 0,
  `total_sale_product47` double NOT NULL DEFAULT 0,
  `total_sale_product48` double NOT NULL DEFAULT 0,
  `total_sale_product49` double NOT NULL DEFAULT 0,
  `total_sale_product50` double NOT NULL DEFAULT 0,
  `total_sale_product51` double NOT NULL DEFAULT 0,
  `total_sale_product52` double NOT NULL DEFAULT 0,
  `total_sale_product53` double NOT NULL DEFAULT 0,
  `total_sale_product54` double NOT NULL DEFAULT 0,
  `total_sale_product55` double NOT NULL DEFAULT 0,
  `total_sale_product56` double NOT NULL DEFAULT 0,
  `total_sale_product57` double NOT NULL DEFAULT 0,
  `total_sale_product58` double NOT NULL DEFAULT 0,
  `total_sale_product59` double NOT NULL DEFAULT 0,
  `total_sale_product60` double NOT NULL DEFAULT 0,
  `total_sale_product61` double NOT NULL DEFAULT 0,
  `total_sale_product62` double NOT NULL DEFAULT 0,
  `total_sale_product63` double NOT NULL DEFAULT 0,
  `total_sale_product64` double NOT NULL DEFAULT 0,
  `total_sale_product65` double NOT NULL DEFAULT 0,
  `total_sale_product66` double NOT NULL DEFAULT 0,
  `total_sale_product67` double NOT NULL DEFAULT 0,
  `total_sale_product68` double NOT NULL DEFAULT 0,
  `total_sale_product69` double NOT NULL DEFAULT 0,
  `total_sale_product70` double NOT NULL DEFAULT 0,
  `total_sale_product71` double NOT NULL DEFAULT 0,
  `total_sale_product72` double NOT NULL DEFAULT 0,
  `total_sale_product73` double NOT NULL DEFAULT 0,
  `total_sale_product74` double NOT NULL DEFAULT 0,
  `total_sale_product75` double NOT NULL DEFAULT 0,
  `total_sale_product76` double NOT NULL DEFAULT 0,
  `total_sale_product77` double NOT NULL DEFAULT 0,
  `total_sale_product78` double NOT NULL DEFAULT 0,
  `is_ai_result_processed` int(1) NOT NULL DEFAULT 0 COMMENT '0=No,1=Yes',
  `ai_result_org` varchar(1) DEFAULT NULL,
  `ai_result` varchar(1) DEFAULT NULL,
  `creator_id` smallint(5) NOT NULL DEFAULT 0,
  `rcd` date NOT NULL,
  `rdt` datetime NOT NULL,
  `modif_id` smallint(5) NOT NULL DEFAULT 0,
  `rlm` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblsurvey_response_file_new`
--

CREATE TABLE `tblsurvey_response_file_new` (
  `resp_id` int(10) NOT NULL,
  `uni_id` varchar(40) NOT NULL,
  `mob_img_id` varchar(40) DEFAULT NULL,
  `client_id` tinyint(3) NOT NULL,
  `project_id` smallint(5) NOT NULL,
  `team_id` smallint(5) NOT NULL,
  `move_image_to_digitalocean` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0=Not moved,1=Moved,2=File not exists',
  `s_id` tinyint(3) NOT NULL,
  `imei` varchar(20) DEFAULT NULL,
  `lic_auth_code` varchar(80) NOT NULL,
  `page_id` tinyint(2) NOT NULL DEFAULT 0,
  `ques_id` tinyint(2) NOT NULL DEFAULT 0,
  `file_caption` varchar(200) DEFAULT NULL,
  `file_domain` varchar(100) DEFAULT NULL,
  `file_path` mediumtext DEFAULT NULL,
  `file_name` varchar(100) DEFAULT NULL,
  `capture_date` date NOT NULL,
  `capture_datetime` datetime NOT NULL,
  `lt` double NOT NULL DEFAULT 0,
  `lg` double NOT NULL DEFAULT 0,
  `dstatus` tinyint(1) NOT NULL DEFAULT 0,
  `creator_id` smallint(5) NOT NULL DEFAULT 0,
  `rcd` date DEFAULT NULL,
  `rdt` datetime DEFAULT NULL,
  `modif_id` smallint(5) NOT NULL DEFAULT 0,
  `rlm` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblsurvey_response_new`
--

CREATE TABLE `tblsurvey_response_new` (
  `resp_id` int(10) NOT NULL,
  `uni_id` varchar(40) NOT NULL,
  `client_id` tinyint(3) NOT NULL,
  `project_id` smallint(5) NOT NULL,
  `team_id` smallint(5) NOT NULL,
  `processed` enum('0','1') NOT NULL DEFAULT '0',
  `s_id` tinyint(3) NOT NULL DEFAULT 0,
  `imei` varchar(20) DEFAULT NULL,
  `lic_auth_code` varchar(80) NOT NULL,
  `sur_response` text DEFAULT NULL,
  `distance_travelled_in_km` double NOT NULL DEFAULT 0,
  `distance_travelled_in_meter` bigint(20) NOT NULL DEFAULT 0,
  `call_time` bigint(20) NOT NULL DEFAULT 0,
  `capture_date` date NOT NULL,
  `capture_datetime` datetime NOT NULL,
  `lt` double NOT NULL DEFAULT 0,
  `lg` double NOT NULL DEFAULT 0,
  `dstatus` tinyint(1) NOT NULL DEFAULT 0,
  `creator_id` smallint(5) NOT NULL DEFAULT 0,
  `rcd` date DEFAULT NULL,
  `rdt` datetime DEFAULT NULL,
  `modif_id` smallint(5) NOT NULL DEFAULT 0,
  `rlm` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbluser_access`
--

CREATE TABLE `tbluser_access` (
  `ua_id` mediumint(6) NOT NULL,
  `user_id` smallint(5) NOT NULL,
  `client_id` tinyint(3) NOT NULL,
  `project_id` smallint(5) NOT NULL,
  `branch_id` smallint(5) NOT NULL,
  `wd_code` varchar(20) DEFAULT NULL,
  `dstatus` tinyint(1) NOT NULL DEFAULT 0,
  `creator_id` smallint(5) DEFAULT 0,
  `rcd` date DEFAULT NULL,
  `rdt` datetime DEFAULT NULL,
  `modif_id` smallint(5) NOT NULL DEFAULT 0,
  `rlm` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbluser_authdetails`
--

CREATE TABLE `tbluser_authdetails` (
  `user_id` smallint(5) NOT NULL,
  `auth_name` varchar(20) NOT NULL,
  `group_id` int(3) NOT NULL,
  `landing_modc` varchar(15) NOT NULL,
  `landing_pmodc` varchar(15) NOT NULL,
  `access_type` int(1) NOT NULL DEFAULT 0 COMMENT '0=Admin,1=Client,2=Project,3=Branch,4=WD Code',
  `usr_fullname` varchar(40) DEFAULT NULL,
  `usr_email` varchar(50) DEFAULT NULL,
  `auth_pwd` varchar(200) NOT NULL,
  `temp_pwd` varchar(100) DEFAULT NULL,
  `temp_flag` int(1) NOT NULL DEFAULT 0,
  `last_pwd_update` datetime NOT NULL,
  `login_attempts` tinyint(1) NOT NULL DEFAULT 0,
  `dstatus` tinyint(1) NOT NULL DEFAULT 0,
  `creator_id` smallint(5) NOT NULL DEFAULT 0,
  `rcd` date DEFAULT NULL,
  `rdt` datetime DEFAULT NULL,
  `modif_id` smallint(5) NOT NULL DEFAULT 0,
  `rlm` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbluser_group`
--

CREATE TABLE `tbluser_group` (
  `user_group_id` smallint(5) NOT NULL,
  `user_id` smallint(5) NOT NULL,
  `group_id` tinyint(3) NOT NULL,
  `de_modc` varchar(10) NOT NULL,
  `de_pmodc` varchar(10) NOT NULL,
  `client_filter` smallint(5) NOT NULL DEFAULT 0 COMMENT '0=Admin,1=Selected Clients,2=Selected Projects,3=Selected Branches,4=Selected WD Codes',
  `dstatus` tinyint(1) NOT NULL DEFAULT 0,
  `creator_id` smallint(5) NOT NULL DEFAULT 0,
  `rcd` date DEFAULT NULL,
  `rdt` datetime DEFAULT NULL,
  `modif_id` smallint(5) NOT NULL DEFAULT 0,
  `rlm` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbluser_session_token`
--

CREATE TABLE `tbluser_session_token` (
  `rec_id` int(11) NOT NULL,
  `user_id` smallint(5) NOT NULL,
  `csrf_token` varchar(128) NOT NULL,
  `permitted_ids` mediumtext NOT NULL,
  `dstatus` tinyint(1) NOT NULL DEFAULT 0,
  `creator_id` smallint(5) NOT NULL DEFAULT 0,
  `rcd` date DEFAULT NULL,
  `rdt` datetime DEFAULT NULL,
  `modif_id` smallint(5) NOT NULL DEFAULT 0,
  `rlm` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblvands_summary`
--

CREATE TABLE `tblvands_summary` (
  `summary_id` int(11) NOT NULL,
  `team_id` smallint(5) NOT NULL,
  `activity_date` date DEFAULT NULL,
  `route` text DEFAULT NULL,
  `last_rec_id` bigint(20) NOT NULL DEFAULT 0 COMMENT 'Last record ID to track which record is this',
  `last_rec_lt` double NOT NULL DEFAULT 0 COMMENT 'If last record coordinates were 0,0 OR more than max limit, then those corordinates are not stored in lt, lg columns',
  `last_rec_lg` double NOT NULL DEFAULT 0,
  `attendance_datetime` datetime DEFAULT NULL,
  `dayend_datetime` datetime DEFAULT NULL,
  `start_datetime` datetime DEFAULT NULL,
  `end_datetime` datetime DEFAULT NULL,
  `resp_startdatetime` datetime DEFAULT NULL,
  `resp_enddatetime` datetime DEFAULT NULL,
  `is_beat_adherence` varchar(20) DEFAULT 'Yes',
  `beat_adherence_reason` varchar(50) DEFAULT NULL,
  `total_meter_travelled` float NOT NULL DEFAULT 0,
  `total_sales_deliveries` smallint(4) NOT NULL DEFAULT 0,
  `total_sellin_shops` smallint(4) NOT NULL DEFAULT 0,
  `total_other_shops` smallint(4) NOT NULL DEFAULT 0,
  `netAmount` float NOT NULL DEFAULT 0,
  `discount` bigint(20) NOT NULL DEFAULT 0,
  `total_sale_product1` double NOT NULL DEFAULT 0,
  `total_sale_product2` double NOT NULL DEFAULT 0,
  `total_sale_product3` double NOT NULL DEFAULT 0,
  `total_sale_product4` double NOT NULL DEFAULT 0,
  `total_sale_product5` double NOT NULL DEFAULT 0,
  `total_sale_product6` double NOT NULL DEFAULT 0,
  `total_sale_product7` double NOT NULL DEFAULT 0,
  `total_sale_product8` double NOT NULL DEFAULT 0,
  `total_sale_product9` double NOT NULL DEFAULT 0,
  `total_sale_product10` double NOT NULL DEFAULT 0,
  `total_sale_product11` double NOT NULL DEFAULT 0,
  `total_sale_product12` double NOT NULL DEFAULT 0,
  `total_sale_product13` double NOT NULL DEFAULT 0,
  `total_sale_product14` double NOT NULL DEFAULT 0,
  `total_sale_product15` double NOT NULL DEFAULT 0,
  `total_sale_product16` double NOT NULL DEFAULT 0,
  `total_sale_product17` double NOT NULL DEFAULT 0,
  `total_sale_product18` double NOT NULL DEFAULT 0,
  `total_sale_product19` double NOT NULL DEFAULT 0,
  `total_sale_product20` double NOT NULL DEFAULT 0,
  `total_sale_product21` double NOT NULL DEFAULT 0,
  `total_sale_product22` double NOT NULL DEFAULT 0,
  `total_sale_product23` double NOT NULL DEFAULT 0,
  `total_sale_product24` double NOT NULL DEFAULT 0,
  `total_sale_product25` double NOT NULL DEFAULT 0,
  `total_sale_product26` double NOT NULL DEFAULT 0,
  `total_sale_product27` double NOT NULL DEFAULT 0,
  `total_sale_product28` double NOT NULL DEFAULT 0,
  `total_sale_product29` double NOT NULL DEFAULT 0,
  `total_sale_product30` double NOT NULL DEFAULT 0,
  `total_sale_product31` double NOT NULL DEFAULT 0,
  `total_sale_product32` double NOT NULL DEFAULT 0,
  `total_sale_product33` double NOT NULL DEFAULT 0,
  `total_sale_product34` double NOT NULL DEFAULT 0,
  `total_sale_product35` double NOT NULL DEFAULT 0,
  `total_sale_product36` double NOT NULL DEFAULT 0,
  `total_sale_product37` double NOT NULL DEFAULT 0,
  `total_sale_product38` double NOT NULL DEFAULT 0,
  `total_sale_product39` double NOT NULL DEFAULT 0,
  `total_sale_product40` double NOT NULL DEFAULT 0,
  `total_sale_product41` double NOT NULL DEFAULT 0,
  `total_sale_product42` double NOT NULL DEFAULT 0,
  `total_sale_product43` double NOT NULL DEFAULT 0,
  `total_sale_product44` double NOT NULL DEFAULT 0,
  `total_sale_product45` double NOT NULL DEFAULT 0,
  `total_sale_product46` double NOT NULL DEFAULT 0,
  `total_sale_product47` double NOT NULL DEFAULT 0,
  `total_sale_product48` double NOT NULL DEFAULT 0,
  `total_sale_product49` double NOT NULL DEFAULT 0,
  `total_sale_product50` double NOT NULL DEFAULT 0,
  `total_sale_product51` double NOT NULL DEFAULT 0,
  `total_sale_product52` double NOT NULL DEFAULT 0,
  `total_sale_product53` double NOT NULL DEFAULT 0,
  `total_sale_product54` double NOT NULL DEFAULT 0,
  `total_sale_product55` double NOT NULL DEFAULT 0,
  `total_sale_product56` double NOT NULL DEFAULT 0,
  `total_sale_product57` double NOT NULL DEFAULT 0,
  `total_sale_product58` double NOT NULL DEFAULT 0,
  `total_sale_product59` double NOT NULL DEFAULT 0,
  `total_sale_product60` double NOT NULL DEFAULT 0,
  `total_sale_product61` double NOT NULL DEFAULT 0,
  `total_sale_product62` double NOT NULL DEFAULT 0,
  `total_sale_product63` double NOT NULL DEFAULT 0,
  `total_sale_product64` double NOT NULL DEFAULT 0,
  `total_sale_product65` double NOT NULL DEFAULT 0,
  `total_sale_product66` double NOT NULL DEFAULT 0,
  `total_sale_product67` double NOT NULL DEFAULT 0,
  `total_sale_product68` double NOT NULL DEFAULT 0,
  `total_sale_product69` double NOT NULL DEFAULT 0,
  `total_sale_product70` double NOT NULL DEFAULT 0,
  `total_sale_product71` double NOT NULL DEFAULT 0,
  `total_sale_product72` double NOT NULL DEFAULT 0,
  `total_sale_product73` double NOT NULL DEFAULT 0,
  `total_sale_product74` double NOT NULL DEFAULT 0,
  `total_sale_product75` double NOT NULL DEFAULT 0,
  `total_sale_product76` double NOT NULL DEFAULT 0,
  `total_sale_product77` double NOT NULL DEFAULT 0,
  `total_sale_product78` double NOT NULL DEFAULT 0,
  `dstatus` tinyint(1) NOT NULL DEFAULT 0,
  `rcd` date DEFAULT NULL,
  `rdt` datetime DEFAULT NULL,
  `creator_id` smallint(5) NOT NULL DEFAULT 0,
  `modif_id` smallint(5) NOT NULL DEFAULT 0,
  `rlm` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblwdapp_uob_sales_data`
--

CREATE TABLE `tblwdapp_uob_sales_data` (
  `ms_id` bigint(11) NOT NULL,
  `team_id` int(11) DEFAULT NULL,
  `month` varchar(10) DEFAULT NULL,
  `product_name` varchar(50) NOT NULL,
  `uob` float NOT NULL DEFAULT 0 COMMENT 'unique outlet billed',
  `sales` float DEFAULT 0,
  `dstatus` int(1) NOT NULL DEFAULT 0,
  `rcd` date DEFAULT NULL,
  `rdt` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblwdapp_uob_sales_data_weekly`
--

CREATE TABLE `tblwdapp_uob_sales_data_weekly` (
  `ms_id` bigint(11) NOT NULL,
  `team_id` int(11) DEFAULT NULL,
  `month` varchar(10) DEFAULT NULL,
  `product_name` varchar(50) NOT NULL,
  `week_1uob` int(10) NOT NULL DEFAULT 0,
  `week_1sales` int(10) NOT NULL DEFAULT 0,
  `week_2uob` int(10) NOT NULL DEFAULT 0,
  `week_2sales` int(10) NOT NULL DEFAULT 0,
  `week_3uob` int(10) NOT NULL DEFAULT 0,
  `week_3sales` int(10) NOT NULL DEFAULT 0,
  `week_4uob` int(10) NOT NULL DEFAULT 0,
  `week_4sales` int(10) NOT NULL DEFAULT 0,
  `monthy_uob` int(10) NOT NULL DEFAULT 0,
  `monthy_sales` int(10) NOT NULL DEFAULT 0,
  `dstatus` int(1) NOT NULL DEFAULT 0,
  `rcd` date DEFAULT NULL,
  `rdt` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblwd_product_net_rate_update`
--

CREATE TABLE `tblwd_product_net_rate_update` (
  `rec_id` int(11) NOT NULL,
  `branch_id` varchar(20) DEFAULT NULL,
  `wd_code` varchar(20) NOT NULL,
  `json_id` int(20) DEFAULT NULL,
  `team_type` int(20) DEFAULT NULL,
  `is_focusbrand` int(10) DEFAULT NULL,
  `category_name` varchar(20) DEFAULT NULL,
  `product_name` varchar(40) NOT NULL,
  `summary_column_name` varchar(20) DEFAULT NULL,
  `net_rate` double NOT NULL DEFAULT 1,
  `sort_order` int(11) DEFAULT NULL,
  `rcd` date DEFAULT NULL,
  `rdt` datetime DEFAULT NULL,
  `dstatus` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Each branch can have max 60 products';

-- --------------------------------------------------------

--
-- Table structure for table `tbl_leaderboard`
--

CREATE TABLE `tbl_leaderboard` (
  `lb_id` bigint(20) NOT NULL,
  `branch_id` int(11) DEFAULT NULL,
  `team_id` int(11) NOT NULL DEFAULT 0,
  `qualifiedDays` int(10) NOT NULL DEFAULT 0,
  `ttldays` int(10) NOT NULL DEFAULT 0,
  `para1_score` float NOT NULL DEFAULT 0 COMMENT '% of para1',
  `ttloutlets` int(10) NOT NULL DEFAULT 0,
  `uob` int(10) NOT NULL DEFAULT 0,
  `para2_score` float NOT NULL DEFAULT 0 COMMENT '% of para2',
  `fb1uob` int(10) NOT NULL DEFAULT 0,
  `para3_score` float NOT NULL DEFAULT 0 COMMENT '% of para3',
  `fb2uob` int(10) NOT NULL DEFAULT 0,
  `para4_score` float NOT NULL DEFAULT 0 COMMENT '% of para4',
  `total_score` float NOT NULL DEFAULT 0,
  `dstatus` int(11) NOT NULL DEFAULT 0,
  `capture_date` date DEFAULT NULL,
  `capture_datetime` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_notification`
--

CREATE TABLE `tbl_notification` (
  `rec_id` int(11) NOT NULL,
  `branch_id` int(11) DEFAULT NULL,
  `wd_code` varchar(10) DEFAULT NULL,
  `product_name` varchar(50) DEFAULT NULL,
  `old_net_rate` float DEFAULT NULL,
  `new_net_rate` float DEFAULT NULL,
  `is_branch_update` int(1) NOT NULL DEFAULT 0,
  `rcd` date DEFAULT NULL,
  `rdt` datetime DEFAULT NULL,
  `dstatus` int(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_price_history`
--

CREATE TABLE `tbl_price_history` (
  `rec_id` int(11) NOT NULL,
  `branch_id` int(11) DEFAULT NULL,
  `wd_code` varchar(10) DEFAULT NULL,
  `product_name` varchar(20) DEFAULT NULL,
  `net_rate` int(10) DEFAULT NULL,
  `rcd` date DEFAULT NULL,
  `rdt` datetime DEFAULT NULL,
  `dstatus` int(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_usergrouprole`
-- (See below for the actual view)
--
CREATE TABLE `v_usergrouprole` (
`user_id` smallint(5)
,`group_id` int(3)
,`group_name` varchar(40)
,`role_permission` mediumtext
,`dstatus` tinyint(1)
);

-- --------------------------------------------------------

--
-- Table structure for table `whatsapp_summary_logs`
--

CREATE TABLE `whatsapp_summary_logs` (
  `id` int(11) NOT NULL,
  `ae_name` varchar(255) DEFAULT NULL,
  `team_name` varchar(255) DEFAULT NULL,
  `team_id` int(100) NOT NULL,
  `wd_code` varchar(255) DEFAULT NULL,
  `qualified_attendance` varchar(5) DEFAULT NULL,
  `market_time_formatted` varchar(20) DEFAULT NULL,
  `day_end_marked` varchar(5) DEFAULT NULL,
  `shops_summary` varchar(50) DEFAULT NULL,
  `not_visited_shops` text DEFAULT NULL,
  `api_response` text DEFAULT NULL,
  `capture_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure for view `v_usergrouprole`
--
DROP TABLE IF EXISTS `v_usergrouprole`;

CREATE VIEW `v_usergrouprole`  AS SELECT `a`.`user_id` AS `user_id`, `a`.`group_id` AS `group_id`, `b`.`group_name` AS `group_name`, `b`.`role_permission` AS `role_permission`, `b`.`dstatus` AS `dstatus` FROM (`tbluser_authdetails` `a` join `tblgroups` `b`) WHERE `a`.`group_id` = `b`.`group_id` AND `a`.`dstatus` = 0 AND `b`.`dstatus` = 0 ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `tblattendance`
--
ALTER TABLE `tblattendance`
  ADD PRIMARY KEY (`att_id`),
  ADD KEY `capture_date` (`capture_date`),
  ADD KEY `team_id` (`team_id`),
  ADD KEY `capture_date_team_id` (`capture_date`,`team_id`) USING BTREE,
  ADD KEY `pickup_stock_updated_call_type` (`pickup_stock_updated`,`call_type`) USING BTREE;

--
-- Indexes for table `tblbranch`
--
ALTER TABLE `tblbranch`
  ADD PRIMARY KEY (`branch_id`),
  ADD KEY `dstatus_branch_id` (`dstatus`,`branch_id`);

--
-- Indexes for table `tblbranch_keydetails`
--
ALTER TABLE `tblbranch_keydetails`
  ADD PRIMARY KEY (`pro_id`);

--
-- Indexes for table `tblbranch_pickupstock_products`
--
ALTER TABLE `tblbranch_pickupstock_products`
  ADD PRIMARY KEY (`rec_id`),
  ADD KEY `branch_id` (`branch_id`);

--
-- Indexes for table `tblbreak`
--
ALTER TABLE `tblbreak`
  ADD PRIMARY KEY (`break_id`),
  ADD KEY `team_id_rcd` (`team_id`,`rcd`);

--
-- Indexes for table `tblclients`
--
ALTER TABLE `tblclients`
  ADD PRIMARY KEY (`client_id`);

--
-- Indexes for table `tblcloudring_live`
--
ALTER TABLE `tblcloudring_live`
  ADD PRIMARY KEY (`rec_id`),
  ADD KEY `token` (`token`),
  ADD KEY `rec_who` (`rec_who`);

--
-- Indexes for table `tblcloudring_live_login`
--
ALTER TABLE `tblcloudring_live_login`
  ADD PRIMARY KEY (`rec_id`),
  ADD KEY `token` (`token`),
  ADD KEY `rec_who` (`rec_who`);

--
-- Indexes for table `tblconstants`
--
ALTER TABLE `tblconstants`
  ADD PRIMARY KEY (`con_id`);

--
-- Indexes for table `tblgroups`
--
ALTER TABLE `tblgroups`
  ADD PRIMARY KEY (`group_id`);

--
-- Indexes for table `tblmobile_calendar_data`
--
ALTER TABLE `tblmobile_calendar_data`
  ADD PRIMARY KEY (`mcd_id`);

--
-- Indexes for table `tblmobile_calendar_summary`
--
ALTER TABLE `tblmobile_calendar_summary`
  ADD PRIMARY KEY (`mcs_id`),
  ADD KEY `team_id_rcd` (`team_id`);

--
-- Indexes for table `tblmobile_calendar_summary_keydetails`
--
ALTER TABLE `tblmobile_calendar_summary_keydetails`
  ADD PRIMARY KEY (`ms_id`),
  ADD KEY `team_id` (`team_id`);

--
-- Indexes for table `tblmobile_summary`
--
ALTER TABLE `tblmobile_summary`
  ADD PRIMARY KEY (`ms_id`),
  ADD KEY `team_id` (`team_id`);

--
-- Indexes for table `tblmodules`
--
ALTER TABLE `tblmodules`
  ADD PRIMARY KEY (`module_id`),
  ADD KEY `parent_module_code` (`parent_module_code`);

--
-- Indexes for table `tbloffline_dropdown_options`
--
ALTER TABLE `tbloffline_dropdown_options`
  ADD PRIMARY KEY (`odo_id`),
  ADD KEY `team_id` (`team_id`),
  ADD KEY `activity_date` (`activity_date`);

--
-- Indexes for table `tblprojects`
--
ALTER TABLE `tblprojects`
  ADD PRIMARY KEY (`project_id`),
  ADD KEY `client_id` (`client_id`);

--
-- Indexes for table `tblproject_team`
--
ALTER TABLE `tblproject_team`
  ADD PRIMARY KEY (`team_id`),
  ADD KEY `branch_id` (`branch_id`),
  ADD KEY `project_id` (`project_id`);

--
-- Indexes for table `tblroute_details`
--
ALTER TABLE `tblroute_details`
  ADD PRIMARY KEY (`rec_id`),
  ADD KEY `route_name` (`route_name`),
  ADD KEY `team_id_outlet_type` (`team_id`,`outlet_type`) USING BTREE,
  ADD KEY `team_id` (`team_id`);

--
-- Indexes for table `tblstock_summary`
--
ALTER TABLE `tblstock_summary`
  ADD PRIMARY KEY (`sp_id`),
  ADD KEY `rec_id_stock_type` (`rec_id`,`stock_type`),
  ADD KEY `capture_date_team_id` (`capture_date`,`team_id`) USING BTREE,
  ADD KEY `team_id_stock_type_capture_date` (`team_id`,`stock_type`,`capture_date`) USING BTREE;

--
-- Indexes for table `tblsurvey_response_details`
--
ALTER TABLE `tblsurvey_response_details`
  ADD PRIMARY KEY (`pro_id`),
  ADD KEY `capture_datetime` (`capture_datetime`),
  ADD KEY `capture_date` (`capture_date`),
  ADD KEY `update_sale` (`update_sale`),
  ADD KEY `ques_2_dstatus` (`ques_2`,`dstatus`) USING BTREE,
  ADD KEY `team_id_ques_0_capture_date` (`team_id`,`ques_0`(25),`capture_date`),
  ADD KEY `team_id_capture_date` (`team_id`,`capture_date`) USING BTREE,
  ADD KEY `is_ai_result_processed` (`is_ai_result_processed`),
  ADD KEY `idx_ques_2` (`ques_2`),
  ADD KEY `ques_3_capture_date_dstatus` (`ques_3`(20),`capture_date`,`dstatus`),
  ADD KEY `ques_2_capture_date_dstatus` (`ques_2`,`capture_date`,`dstatus`),
  ADD KEY `shop_id` (`netAmount`);

--
-- Indexes for table `tblsurvey_response_file_new`
--
ALTER TABLE `tblsurvey_response_file_new`
  ADD PRIMARY KEY (`resp_id`),
  ADD KEY `uni_id` (`uni_id`) USING BTREE,
  ADD KEY `pid_move_image_to_digitalocean` (`project_id`,`move_image_to_digitalocean`),
  ADD KEY `capture_date_pid_move_image_to_digitalocean` (`capture_date`,`project_id`,`move_image_to_digitalocean`);

--
-- Indexes for table `tblsurvey_response_new`
--
ALTER TABLE `tblsurvey_response_new`
  ADD PRIMARY KEY (`resp_id`),
  ADD KEY `processed` (`processed`),
  ADD KEY `uni_id` (`uni_id`),
  ADD KEY `capture_date` (`capture_date`),
  ADD KEY `dstatus_capture_date` (`dstatus`,`capture_date`) USING BTREE;

--
-- Indexes for table `tbluser_access`
--
ALTER TABLE `tbluser_access`
  ADD PRIMARY KEY (`ua_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `tbluser_authdetails`
--
ALTER TABLE `tbluser_authdetails`
  ADD PRIMARY KEY (`user_id`);

--
-- Indexes for table `tbluser_group`
--
ALTER TABLE `tbluser_group`
  ADD PRIMARY KEY (`user_group_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `group_id` (`group_id`);

--
-- Indexes for table `tbluser_session_token`
--
ALTER TABLE `tbluser_session_token`
  ADD PRIMARY KEY (`rec_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `csrf_token` (`csrf_token`);

--
-- Indexes for table `tblvands_summary`
--
ALTER TABLE `tblvands_summary`
  ADD PRIMARY KEY (`summary_id`),
  ADD KEY `team_id` (`team_id`),
  ADD KEY `activity_date` (`activity_date`);

--
-- Indexes for table `tblwdapp_uob_sales_data`
--
ALTER TABLE `tblwdapp_uob_sales_data`
  ADD PRIMARY KEY (`ms_id`),
  ADD KEY `team_id` (`team_id`);

--
-- Indexes for table `tblwdapp_uob_sales_data_weekly`
--
ALTER TABLE `tblwdapp_uob_sales_data_weekly`
  ADD PRIMARY KEY (`ms_id`),
  ADD KEY `team_id` (`team_id`);

--
-- Indexes for table `tblwd_product_net_rate_update`
--
ALTER TABLE `tblwd_product_net_rate_update`
  ADD PRIMARY KEY (`rec_id`);

--
-- Indexes for table `tbl_leaderboard`
--
ALTER TABLE `tbl_leaderboard`
  ADD PRIMARY KEY (`lb_id`),
  ADD KEY `idx_team_date` (`team_id`,`capture_date`);

--
-- Indexes for table `tbl_notification`
--
ALTER TABLE `tbl_notification`
  ADD PRIMARY KEY (`rec_id`),
  ADD UNIQUE KEY `rec_id` (`rec_id`);

--
-- Indexes for table `tbl_price_history`
--
ALTER TABLE `tbl_price_history`
  ADD PRIMARY KEY (`rec_id`);

--
-- Indexes for table `whatsapp_summary_logs`
--
ALTER TABLE `whatsapp_summary_logs`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `tblattendance`
--
ALTER TABLE `tblattendance`
  MODIFY `att_id` int(10) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblbranch`
--
ALTER TABLE `tblbranch`
  MODIFY `branch_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblbranch_keydetails`
--
ALTER TABLE `tblbranch_keydetails`
  MODIFY `pro_id` int(10) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblbranch_pickupstock_products`
--
ALTER TABLE `tblbranch_pickupstock_products`
  MODIFY `rec_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblbreak`
--
ALTER TABLE `tblbreak`
  MODIFY `break_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblclients`
--
ALTER TABLE `tblclients`
  MODIFY `client_id` tinyint(3) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblcloudring_live`
--
ALTER TABLE `tblcloudring_live`
  MODIFY `rec_id` int(10) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblcloudring_live_login`
--
ALTER TABLE `tblcloudring_live_login`
  MODIFY `rec_id` int(10) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblconstants`
--
ALTER TABLE `tblconstants`
  MODIFY `con_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblgroups`
--
ALTER TABLE `tblgroups`
  MODIFY `group_id` tinyint(3) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblmobile_calendar_data`
--
ALTER TABLE `tblmobile_calendar_data`
  MODIFY `mcd_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblmobile_calendar_summary`
--
ALTER TABLE `tblmobile_calendar_summary`
  MODIFY `mcs_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblmobile_calendar_summary_keydetails`
--
ALTER TABLE `tblmobile_calendar_summary_keydetails`
  MODIFY `ms_id` bigint(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblmobile_summary`
--
ALTER TABLE `tblmobile_summary`
  MODIFY `ms_id` bigint(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblmodules`
--
ALTER TABLE `tblmodules`
  MODIFY `module_id` smallint(5) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbloffline_dropdown_options`
--
ALTER TABLE `tbloffline_dropdown_options`
  MODIFY `odo_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblprojects`
--
ALTER TABLE `tblprojects`
  MODIFY `project_id` smallint(5) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblproject_team`
--
ALTER TABLE `tblproject_team`
  MODIFY `team_id` smallint(5) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblroute_details`
--
ALTER TABLE `tblroute_details`
  MODIFY `rec_id` int(6) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblstock_summary`
--
ALTER TABLE `tblstock_summary`
  MODIFY `sp_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblsurvey_response_details`
--
ALTER TABLE `tblsurvey_response_details`
  MODIFY `pro_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblsurvey_response_file_new`
--
ALTER TABLE `tblsurvey_response_file_new`
  MODIFY `resp_id` int(10) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblsurvey_response_new`
--
ALTER TABLE `tblsurvey_response_new`
  MODIFY `resp_id` int(10) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbluser_access`
--
ALTER TABLE `tbluser_access`
  MODIFY `ua_id` mediumint(6) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbluser_authdetails`
--
ALTER TABLE `tbluser_authdetails`
  MODIFY `user_id` smallint(5) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbluser_group`
--
ALTER TABLE `tbluser_group`
  MODIFY `user_group_id` smallint(5) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbluser_session_token`
--
ALTER TABLE `tbluser_session_token`
  MODIFY `rec_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblvands_summary`
--
ALTER TABLE `tblvands_summary`
  MODIFY `summary_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblwdapp_uob_sales_data`
--
ALTER TABLE `tblwdapp_uob_sales_data`
  MODIFY `ms_id` bigint(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblwdapp_uob_sales_data_weekly`
--
ALTER TABLE `tblwdapp_uob_sales_data_weekly`
  MODIFY `ms_id` bigint(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblwd_product_net_rate_update`
--
ALTER TABLE `tblwd_product_net_rate_update`
  MODIFY `rec_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_leaderboard`
--
ALTER TABLE `tbl_leaderboard`
  MODIFY `lb_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_notification`
--
ALTER TABLE `tbl_notification`
  MODIFY `rec_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_price_history`
--
ALTER TABLE `tbl_price_history`
  MODIFY `rec_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `whatsapp_summary_logs`
--
ALTER TABLE `whatsapp_summary_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
