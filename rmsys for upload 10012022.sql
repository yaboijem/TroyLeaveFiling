-- phpMyAdmin SQL Dump
-- version 5.0.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 01, 2022 at 08:08 AM
-- Server version: 10.4.13-MariaDB
-- PHP Version: 7.2.32

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `epiz_32124996_rmsys`
--

-- --------------------------------------------------------

--
-- Table structure for table `adminfiletbl`
--

CREATE TABLE `adminfiletbl` (
  `adminid` int(11) NOT NULL,
  `adminname` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `active` tinyint(4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `adminfiletbl`
--

INSERT INTO `adminfiletbl` (`adminid`, `adminname`, `email`, `password`, `active`) VALUES
(4, 'john', 'jiriellsicangco@gmail.com', 'd670a7be9e3429a3220acc43da8547925cfbdfd4', 1),
(5, 'john', 'jiriellsicangco@gmail.com', 'd670a7be9e3429a3220acc43da8547925cfbdfd4', 1),
(6, 'Jiriel', 'it@lakshmibemt.com', '7c4a8d09ca3762af61e59520943dc26494f8941b', 1),
(7, 'J', 'jsicangco22-1375@cca.edu.ph', '7c4a8d09ca3762af61e59520943dc26494f8941b', 1);

-- --------------------------------------------------------

--
-- Table structure for table `approvalfiletbl`
--

CREATE TABLE `approvalfiletbl` (
  `appno` int(11) NOT NULL,
  `appdate` date NOT NULL,
  `apptime` datetime NOT NULL,
  `reqno` bigint(20) NOT NULL,
  `appuserid` int(11) NOT NULL,
  `appdeptid` int(11) NOT NULL,
  `status` varchar(20) NOT NULL,
  `verifytodept` int(11) DEFAULT NULL,
  `remarks` varchar(500) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `cleaningtypefiletbl`
--

CREATE TABLE `cleaningtypefiletbl` (
  `id` int(11) NOT NULL,
  `ctypeid` varchar(5) NOT NULL,
  `ctypename` varchar(100) DEFAULT NULL,
  `active` tinyint(4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `cleaningtypefiletbl`
--

INSERT INTO `cleaningtypefiletbl` (`id`, `ctypeid`, `ctypename`, `active`) VALUES
(1, 'AV', 'AVAILABLE', 1),
(3, 'CC', 'CHECK OUT CLEANING', 1),
(8, 'CI', 'CHECK IN', 1),
(7, 'CO', 'CHECK OUT', 1),
(5, 'FI', 'FOR INSPECTION', 0),
(4, 'GC', 'GENERAL CLEANING', 1),
(6, 'OOO', 'OUT OF ORDER', 1),
(2, 'RC', 'REGULAR CLEANING', 1);

-- --------------------------------------------------------

--
-- Table structure for table `departmentfiletbl`
--

CREATE TABLE `departmentfiletbl` (
  `deptid` int(11) NOT NULL,
  `deptname` varchar(100) DEFAULT NULL,
  `active` tinyint(4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `departmentfiletbl`
--

INSERT INTO `departmentfiletbl` (`deptid`, `deptname`, `active`) VALUES
(1, 'IT', 1),
(2, 'FRONT OFFICE', 1),
(3, 'HOUSEKEEPING', 1);

-- --------------------------------------------------------

--
-- Table structure for table `fotranfiletbl`
--

CREATE TABLE `fotranfiletbl` (
  `fotranid` int(11) NOT NULL,
  `roomcode` varchar(20) DEFAULT NULL,
  `checkinuserid` int(11) DEFAULT NULL,
  `checkintrandate` date DEFAULT NULL,
  `checkintime` datetime DEFAULT NULL,
  `checkinshift` varchar(10) DEFAULT NULL,
  `checkouttrandate` date DEFAULT NULL,
  `checkouttime` datetime DEFAULT NULL,
  `checkoutuserid` int(11) DEFAULT NULL,
  `checkoutshift` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `hktranfiletbl`
--

CREATE TABLE `hktranfiletbl` (
  `hktranid` int(11) NOT NULL,
  `roomcode` varchar(20) DEFAULT NULL,
  `status` varchar(10) DEFAULT NULL,
  `checkoutuserid` int(11) DEFAULT NULL,
  `housekeeperid` int(11) DEFAULT NULL,
  `housekeepershift` varchar(10) DEFAULT NULL,
  `onprocesscleaning` tinyint(4) DEFAULT NULL,
  `trandatestart` date DEFAULT NULL,
  `timestart` datetime DEFAULT NULL,
  `remarks_start` varchar(500) DEFAULT NULL,
  `trandateend` date DEFAULT NULL,
  `timeend` datetime DEFAULT NULL,
  `remarks_end` varchar(500) NOT NULL,
  `active` tinyint(4) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `housekeeperfiletbl`
--

CREATE TABLE `housekeeperfiletbl` (
  `housekeeperid` int(11) NOT NULL,
  `housekeepername` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `active` tinyint(4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `housekeeperfiletbl`
--

INSERT INTO `housekeeperfiletbl` (`housekeeperid`, `housekeepername`, `email`, `password`, `active`) VALUES
(1, 'NERI', '', '', 1),
(2, 'LOU', '', '', 1),
(4, 'EDMARC', '', '', 1),
(5, 'CARL', '', '', 1),
(6, 'CHAT', NULL, '', 1),
(7, 'JOSHUA', NULL, '', 1),
(8, 'MARK', NULL, '', 1),
(9, 'JOSEPH', NULL, '', 1);

-- --------------------------------------------------------

--
-- Table structure for table `loginfiletbl`
--

CREATE TABLE `loginfiletbl` (
  `systemtype` varchar(5) DEFAULT NULL,
  `username` varchar(100) NOT NULL,
  `shift` varchar(10) DEFAULT NULL,
  `datelogin` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `logintranfiletbl`
--

CREATE TABLE `logintranfiletbl` (
  `id` int(11) NOT NULL,
  `systemtype` varchar(5) DEFAULT NULL,
  `username` varchar(100) NOT NULL,
  `shift` varchar(10) DEFAULT NULL,
  `datelogin` date DEFAULT NULL,
  `closedshiftdatetime` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `requestfiletbl`
--

CREATE TABLE `requestfiletbl` (
  `reqno` bigint(20) NOT NULL,
  `reqnohash` varchar(255) NOT NULL,
  `reqdate` date NOT NULL,
  `reqtime` datetime NOT NULL,
  `userid` int(11) NOT NULL,
  `useridhash` varchar(255) NOT NULL,
  `deptidfrom` int(11) NOT NULL,
  `deptidto` int(11) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `remarks` varchar(500) NOT NULL,
  `closedrequest` tinyint(4) NOT NULL,
  `closedrequestremarks` varchar(500) NOT NULL,
  `deptidtolatest` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `roomfiletbl`
--

CREATE TABLE `roomfiletbl` (
  `roomid` int(11) NOT NULL,
  `roomcode` varchar(20) DEFAULT NULL,
  `roomtypeid` int(11) DEFAULT NULL,
  `status` varchar(10) DEFAULT NULL,
  `checkinuserid` int(11) DEFAULT NULL,
  `checkintrandate` date DEFAULT NULL,
  `checkintime` datetime DEFAULT NULL,
  `checkinshift` varchar(10) DEFAULT NULL,
  `checkouttrandate` date DEFAULT NULL,
  `checkouttime` datetime DEFAULT NULL,
  `checkoutuserid` int(11) DEFAULT NULL,
  `checkoutshift` varchar(10) DEFAULT NULL,
  `housekeeperid` int(11) DEFAULT NULL,
  `housekeepershift` varchar(10) DEFAULT NULL,
  `onprocesscleaning` tinyint(4) DEFAULT NULL,
  `trandatestart` date DEFAULT NULL,
  `timestart` datetime DEFAULT NULL,
  `remarks_start` varchar(500) DEFAULT NULL,
  `trandateend` date DEFAULT NULL,
  `timeend` datetime DEFAULT NULL,
  `remarks_end` varchar(500) DEFAULT NULL,
  `active` tinyint(4) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `roomfiletbl`
--

INSERT INTO `roomfiletbl` (`roomid`, `roomcode`, `roomtypeid`, `status`, `checkoutuserid`, `housekeeperid`, `onprocesscleaning`, `timestart`, `remarks_start`, `timeend`, `remarks_end`, `active`) VALUES
(1, '201', 1, 'AV', NULL, 0, 0, '0000-00-00 00:00:00', NULL, '0000-00-00 00:00:00', NULL, 1),
(2, '202', 1, 'AV', NULL, 0, 0, '0000-00-00 00:00:00', NULL, '0000-00-00 00:00:00', NULL, 1),
(3, '203', 1, 'AV', NULL, 0, 0, '0000-00-00 00:00:00', NULL, '0000-00-00 00:00:00', NULL, 1),
(4, '204', 1, 'AV', NULL, 0, 0, '0000-00-00 00:00:00', NULL, '0000-00-00 00:00:00', NULL, 1),
(5, '205', 1, 'AV', NULL, 0, 0, '0000-00-00 00:00:00', NULL, '0000-00-00 00:00:00', NULL, 1),
(6, '206', 1, 'AV', NULL, 0, 0, '0000-00-00 00:00:00', NULL, '0000-00-00 00:00:00', NULL, 1),
(7, '207', 1, 'AV', NULL, 0, 0, '0000-00-00 00:00:00', NULL, '0000-00-00 00:00:00', NULL, 1),
(8, '208', 1, 'AV', NULL, 0, 0, '0000-00-00 00:00:00', NULL, '0000-00-00 00:00:00', NULL, 1),
(9, '209', 1, 'AV', NULL, 0, 0, '0000-00-00 00:00:00', NULL, '0000-00-00 00:00:00', NULL, 1),
(10, '210', 1, 'AV', NULL, 0, 0, '0000-00-00 00:00:00', NULL, '0000-00-00 00:00:00', NULL, 1),
(11, '211', 1, 'AV', NULL, 0, 0, '0000-00-00 00:00:00', NULL, '0000-00-00 00:00:00', NULL, 1),
(12, '212', 1, 'AV', NULL, 0, 0, '0000-00-00 00:00:00', NULL, '0000-00-00 00:00:00', NULL, 1),
(13, '214', 1, 'AV', NULL, 0, 0, '0000-00-00 00:00:00', NULL, '0000-00-00 00:00:00', NULL, 1),
(14, '215', 1, 'AV', NULL, 0, 0, '0000-00-00 00:00:00', NULL, '0000-00-00 00:00:00', NULL, 1),
(15, '216', 1, 'AV', NULL, 0, 0, '0000-00-00 00:00:00', NULL, '0000-00-00 00:00:00', NULL, 1),
(16, '217', 1, 'AV', NULL, 0, 0, '0000-00-00 00:00:00', NULL, '0000-00-00 00:00:00', NULL, 1),
(17, '218', 1, 'AV', NULL, 0, 0, '0000-00-00 00:00:00', NULL, '0000-00-00 00:00:00', NULL, 1),
(18, '219', 1, 'AV', NULL, 0, 0, '0000-00-00 00:00:00', NULL, '0000-00-00 00:00:00', NULL, 1),
(19, '220', 2, 'AV', NULL, 0, 0, '0000-00-00 00:00:00', NULL, '0000-00-00 00:00:00', NULL, 1),
(20, '221', 7, 'AV', NULL, 0, 0, '0000-00-00 00:00:00', NULL, '0000-00-00 00:00:00', NULL, 1),
(21, '222', 7, 'AV', NULL, 0, 0, '0000-00-00 00:00:00', NULL, '0000-00-00 00:00:00', NULL, 1),
(22, '223', 7, 'AV', NULL, 0, 0, '0000-00-00 00:00:00', NULL, '0000-00-00 00:00:00', NULL, 1),
(23, '224', 7, 'AV', NULL, 0, 0, '0000-00-00 00:00:00', NULL, '0000-00-00 00:00:00', NULL, 1),
(25, '225', 7, 'AV', NULL, 0, 0, '0000-00-00 00:00:00', NULL, '0000-00-00 00:00:00', NULL, 1),
(26, '226', 7, 'AV', NULL, 0, 0, '0000-00-00 00:00:00', NULL, '0000-00-00 00:00:00', NULL, 1),
(27, '227', 7, 'AV', NULL, 0, 0, '0000-00-00 00:00:00', NULL, '0000-00-00 00:00:00', NULL, 1),
(28, '228', 7, 'AV', NULL, 0, 0, '0000-00-00 00:00:00', NULL, '0000-00-00 00:00:00', NULL, 1),
(29, '229', 7, 'AV', NULL, 0, NULL, '0000-00-00 00:00:00', NULL, '0000-00-00 00:00:00', NULL, 1),
(30, '230', 7, 'AV', NULL, 0, NULL, '0000-00-00 00:00:00', NULL, '0000-00-00 00:00:00', NULL, 1),
(31, '301', 3, 'AV', NULL, 0, 0, '0000-00-00 00:00:00', NULL, '0000-00-00 00:00:00', NULL, 1),
(32, '302', 4, 'AV', NULL, 0, 0, '0000-00-00 00:00:00', NULL, '0000-00-00 00:00:00', NULL, 1),
(33, '303', 3, 'AV', NULL, 0, 0, '0000-00-00 00:00:00', NULL, '0000-00-00 00:00:00', NULL, 1),
(34, '304', 3, 'AV', NULL, 0, 0, '0000-00-00 00:00:00', NULL, '0000-00-00 00:00:00', NULL, 1),
(35, '305', 4, 'AV', NULL, 0, 0, '0000-00-00 00:00:00', NULL, '0000-00-00 00:00:00', NULL, 1),
(36, '306', 3, 'AV', NULL, 0, 0, '0000-00-00 00:00:00', NULL, '0000-00-00 00:00:00', NULL, 1),
(37, '307', 4, 'AV', NULL, 0, 0, '0000-00-00 00:00:00', NULL, '0000-00-00 00:00:00', NULL, 1),
(38, '308', 3, 'AV', NULL, 0, 0, '0000-00-00 00:00:00', NULL, '0000-00-00 00:00:00', NULL, 1),
(39, '309', 3, 'AV', NULL, 0, 0, '0000-00-00 00:00:00', NULL, '0000-00-00 00:00:00', NULL, 1),
(40, '310', 4, 'AV', NULL, 0, 0, '0000-00-00 00:00:00', NULL, '0000-00-00 00:00:00', NULL, 1),
(41, '311', 4, 'AV', NULL, 0, 0, '0000-00-00 00:00:00', NULL, '0000-00-00 00:00:00', NULL, 1),
(42, '312', 3, 'AV', NULL, 0, 0, '0000-00-00 00:00:00', NULL, '0000-00-00 00:00:00', NULL, 1),
(43, '314', 4, 'AV', NULL, 0, 0, '0000-00-00 00:00:00', NULL, '0000-00-00 00:00:00', NULL, 1),
(44, '315', 3, 'AV', NULL, 0, 0, '0000-00-00 00:00:00', NULL, '0000-00-00 00:00:00', NULL, 1),
(45, '316', 4, 'AV', NULL, 0, 0, '0000-00-00 00:00:00', NULL, '0000-00-00 00:00:00', NULL, 1),
(46, '317', 3, 'CO', 15, 0, 0, '0000-00-00 00:00:00', NULL, '0000-00-00 00:00:00', NULL, 1),
(47, '318', 3, 'AV', NULL, 0, 0, '0000-00-00 00:00:00', NULL, '0000-00-00 00:00:00', NULL, 1),
(48, '319', 4, 'AV', NULL, 0, 0, '0000-00-00 00:00:00', NULL, '0000-00-00 00:00:00', NULL, 1),
(49, '320', 4, 'AV', NULL, 0, 0, '0000-00-00 00:00:00', NULL, '0000-00-00 00:00:00', NULL, 1),
(50, '321', 3, 'AV', NULL, 0, 0, '0000-00-00 00:00:00', NULL, '0000-00-00 00:00:00', NULL, 1),
(51, '322', 3, 'AV', NULL, 0, 0, '0000-00-00 00:00:00', NULL, '0000-00-00 00:00:00', NULL, 1),
(52, '323', 4, 'AV', NULL, 0, 0, '0000-00-00 00:00:00', NULL, '0000-00-00 00:00:00', NULL, 1),
(53, '324', 5, 'AV', NULL, 0, 0, '0000-00-00 00:00:00', NULL, '0000-00-00 00:00:00', NULL, 1),
(54, '325', 3, 'AV', NULL, 0, 0, '0000-00-00 00:00:00', NULL, '0000-00-00 00:00:00', NULL, 1),
(55, '326', 3, 'AV', NULL, 0, 0, '0000-00-00 00:00:00', NULL, '0000-00-00 00:00:00', NULL, 1),
(56, '327', 6, 'AV', NULL, 0, 0, '0000-00-00 00:00:00', NULL, '0000-00-00 00:00:00', NULL, 1),
(57, '328', 6, 'AV', NULL, 0, 0, '0000-00-00 00:00:00', NULL, '0000-00-00 00:00:00', NULL, 1),
(58, '329', 3, 'AV', NULL, 0, 0, '0000-00-00 00:00:00', NULL, '0000-00-00 00:00:00', NULL, 1),
(59, '330', 3, 'AV', NULL, 0, 0, '0000-00-00 00:00:00', NULL, '0000-00-00 00:00:00', NULL, 1),
(60, '331', 6, 'AV', NULL, 0, 0, '0000-00-00 00:00:00', NULL, '0000-00-00 00:00:00', NULL, 1),
(61, '332', 3, 'AV', NULL, 0, 0, '0000-00-00 00:00:00', NULL, '0000-00-00 00:00:00', NULL, 1),
(62, '333', 6, 'AV', NULL, 0, 0, '0000-00-00 00:00:00', NULL, '0000-00-00 00:00:00', NULL, 1),
(63, '334', 3, 'AV', NULL, 0, 0, '0000-00-00 00:00:00', NULL, '0000-00-00 00:00:00', NULL, 1),
(64, '335', 6, 'AV', NULL, 0, NULL, '0000-00-00 00:00:00', NULL, '0000-00-00 00:00:00', NULL, 1),
(65, '336', 6, 'AV', NULL, 0, 0, '0000-00-00 00:00:00', NULL, '0000-00-00 00:00:00', NULL, 1),
(66, '337', 3, 'AV', NULL, 0, 0, '0000-00-00 00:00:00', NULL, '0000-00-00 00:00:00', NULL, 1),
(67, '338', 3, 'AV', NULL, 0, 0, '0000-00-00 00:00:00', NULL, '0000-00-00 00:00:00', NULL, 1),
(68, '339', 6, 'AV', NULL, 0, 0, '0000-00-00 00:00:00', NULL, '0000-00-00 00:00:00', NULL, 1),
(69, '340', 3, 'AV', NULL, 0, 0, '0000-00-00 00:00:00', NULL, '0000-00-00 00:00:00', NULL, 1),
(70, '341', 6, 'AV', NULL, 0, 0, '0000-00-00 00:00:00', NULL, '0000-00-00 00:00:00', NULL, 1),
(71, '342', 3, 'AV', NULL, 0, 0, '0000-00-00 00:00:00', NULL, '0000-00-00 00:00:00', NULL, 1),
(72, '343', 3, 'AV', NULL, 0, 0, '0000-00-00 00:00:00', NULL, '0000-00-00 00:00:00', NULL, 1),
(73, '344', 6, 'AV', NULL, 0, 0, '0000-00-00 00:00:00', NULL, '0000-00-00 00:00:00', NULL, 1),
(74, '345', 5, 'AV', NULL, 0, NULL, '0000-00-00 00:00:00', NULL, '0000-00-00 00:00:00', NULL, 1),
(75, '346', 3, 'AV', NULL, 0, 0, '0000-00-00 00:00:00', NULL, '0000-00-00 00:00:00', NULL, 1);

-- --------------------------------------------------------

--
-- Table structure for table `roomtypefiletbl`
--

CREATE TABLE `roomtypefiletbl` (
  `roomtypeid` int(11) NOT NULL,
  `roomtypename` varchar(100) DEFAULT NULL,
  `active` tinyint(4) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `roomtypefiletbl`
--

INSERT INTO `roomtypefiletbl` (`roomtypeid`, `roomtypename`, `active`) VALUES
(1, 'Regular', 1),
(2, 'Suite Room', 1),
(3, 'Budget', 1),
(4, 'Balcony', 1),
(5, 'Regency', 1),
(6, 'Deluxe', 1),
(7, 'Annex', 1);

-- --------------------------------------------------------

--
-- Table structure for table `timerforupdatetbl`
--

CREATE TABLE `timerforupdatetbl` (
  `id` int(11) NOT NULL,
  `utoken` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `timerforupdatetbl`
--

INSERT INTO `timerforupdatetbl` (`id`, `utoken`) VALUES
(1, '221001131801166337cd89964f4');

-- --------------------------------------------------------

--
-- Table structure for table `userfiletbl`
--

CREATE TABLE `userfiletbl` (
  `userid` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `deptid` int(11) NOT NULL,
  `closedrequest` tinyint(4) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `active` tinyint(4) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `userfiletbl`
--

INSERT INTO `userfiletbl` (`userid`, `username`, `email`, `deptid`, `closedrequest`, `password`, `active`) VALUES
(1, 'john', 'john@gmail.com', 3, 1, '7c4a8d09ca3762af61e59520943dc26494f8941b', 1),
(2, 'Jiriel', 'john@gmail.com', 1, 1, 'ba03b0b7ef06ce74a467cbc11ab0025c7ae0849d', 1),
(15, 'User1', 'user1@gmail.com', 2, 1, '7c4a8d09ca3762af61e59520943dc26494f8941b', 1),
(16, 'User2', 'user2@gmail.com', 3, 1, '7c4a8d09ca3762af61e59520943dc26494f8941b', 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `adminfiletbl`
--
ALTER TABLE `adminfiletbl`
  ADD PRIMARY KEY (`adminid`),
  ADD KEY `active` (`active`);

--
-- Indexes for table `approvalfiletbl`
--
ALTER TABLE `approvalfiletbl`
  ADD PRIMARY KEY (`appno`);

--
-- Indexes for table `cleaningtypefiletbl`
--
ALTER TABLE `cleaningtypefiletbl`
  ADD PRIMARY KEY (`ctypeid`) USING BTREE,
  ADD KEY `active` (`active`),
  ADD KEY `ctypename` (`ctypename`) USING BTREE;

--
-- Indexes for table `departmentfiletbl`
--
ALTER TABLE `departmentfiletbl`
  ADD PRIMARY KEY (`deptid`),
  ADD KEY `deptname` (`deptname`),
  ADD KEY `active` (`active`);

--
-- Indexes for table `fotranfiletbl`
--
ALTER TABLE `fotranfiletbl`
  ADD PRIMARY KEY (`fotranid`),
  ADD KEY `checkintrandate` (`checkintrandate`) USING BTREE,
  ADD KEY `checkouttrandate` (`checkouttrandate`,`checkoutshift`) USING BTREE;

--
-- Indexes for table `hktranfiletbl`
--
ALTER TABLE `hktranfiletbl`
  ADD PRIMARY KEY (`hktranid`),
  ADD KEY `active` (`active`),
  ADD KEY `trandatestart` (`trandatestart`),
  ADD KEY `trandateend` (`trandateend`,`housekeepershift`) USING BTREE;

--
-- Indexes for table `housekeeperfiletbl`
--
ALTER TABLE `housekeeperfiletbl`
  ADD PRIMARY KEY (`housekeeperid`),
  ADD KEY `active` (`active`);

--
-- Indexes for table `loginfiletbl`
--
ALTER TABLE `loginfiletbl`
  ADD PRIMARY KEY (`username`),
  ADD KEY `systemtype` (`systemtype`);

--
-- Indexes for table `logintranfiletbl`
--
ALTER TABLE `logintranfiletbl`
  ADD PRIMARY KEY (`id`),
  ADD KEY `systemtype` (`systemtype`,`shift`,`datelogin`) USING BTREE;

--
-- Indexes for table `requestfiletbl`
--
ALTER TABLE `requestfiletbl`
  ADD PRIMARY KEY (`reqno`),
  ADD KEY `reqnohash` (`reqnohash`);

--
-- Indexes for table `roomfiletbl`
--
ALTER TABLE `roomfiletbl`
  ADD PRIMARY KEY (`roomid`),
  ADD KEY `active` (`active`);

--
-- Indexes for table `roomtypefiletbl`
--
ALTER TABLE `roomtypefiletbl`
  ADD PRIMARY KEY (`roomtypeid`),
  ADD KEY `deptname` (`roomtypename`),
  ADD KEY `active` (`active`);

--
-- Indexes for table `timerforupdatetbl`
--
ALTER TABLE `timerforupdatetbl`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `userfiletbl`
--
ALTER TABLE `userfiletbl`
  ADD PRIMARY KEY (`userid`),
  ADD UNIQUE KEY `UserID_Password` (`userid`,`password`),
  ADD KEY `active` (`active`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `adminfiletbl`
--
ALTER TABLE `adminfiletbl`
  MODIFY `adminid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `approvalfiletbl`
--
ALTER TABLE `approvalfiletbl`
  MODIFY `appno` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `departmentfiletbl`
--
ALTER TABLE `departmentfiletbl`
  MODIFY `deptid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=343;

--
-- AUTO_INCREMENT for table `fotranfiletbl`
--
ALTER TABLE `fotranfiletbl`
  MODIFY `fotranid` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `hktranfiletbl`
--
ALTER TABLE `hktranfiletbl`
  MODIFY `hktranid` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `housekeeperfiletbl`
--
ALTER TABLE `housekeeperfiletbl`
  MODIFY `housekeeperid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `logintranfiletbl`
--
ALTER TABLE `logintranfiletbl`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `requestfiletbl`
--
ALTER TABLE `requestfiletbl`
  MODIFY `reqno` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=150;

--
-- AUTO_INCREMENT for table `roomfiletbl`
--
ALTER TABLE `roomfiletbl`
  MODIFY `roomid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `roomtypefiletbl`
--
ALTER TABLE `roomtypefiletbl`
  MODIFY `roomtypeid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `timerforupdatetbl`
--
ALTER TABLE `timerforupdatetbl`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `userfiletbl`
--
ALTER TABLE `userfiletbl`
  MODIFY `userid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
