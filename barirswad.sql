-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 07, 2025 at 02:05 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `barirswad`
--

-- --------------------------------------------------------

--
-- Table structure for table `catering_has_meals`
--

CREATE TABLE `catering_has_meals` (
  `Catering_ID` int(10) NOT NULL,
  `Meal_ID` int(10) NOT NULL,
  `Quantity_Per_Person` decimal(3,2) NOT NULL DEFAULT 1.00,
  `Total_Quantity` int(11) NOT NULL,
  `Unit_Price` decimal(10,2) NOT NULL,
  `Total_Price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `catering_services`
--

CREATE TABLE `catering_services` (
  `Catering_ID` int(10) NOT NULL,
  `Customer_ID` int(10) NOT NULL,
  `Event_Name` varchar(100) NOT NULL,
  `Event_Date` date NOT NULL,
  `Event_Time` time NOT NULL,
  `Event_Location` text NOT NULL,
  `Number_of_People` int(11) NOT NULL,
  `Total_Cost` decimal(10,2) NOT NULL DEFAULT 0.00,
  `Status` enum('Pending','Confirmed','In Progress','Completed','Cancelled') NOT NULL DEFAULT 'Pending',
  `Special_Requirements` text DEFAULT NULL,
  `Contact_Person` varchar(100) NOT NULL,
  `Contact_Phone` varchar(15) NOT NULL,
  `Advance_Payment` decimal(10,2) DEFAULT 0.00,
  `Payment_Status` enum('Pending','Partial','Full') NOT NULL DEFAULT 'Pending',
  `Created_Date` timestamp NOT NULL DEFAULT current_timestamp(),
  `Updated_Date` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `catering_services`
--

INSERT INTO `catering_services` (`Catering_ID`, `Customer_ID`, `Event_Name`, `Event_Date`, `Event_Time`, `Event_Location`, `Number_of_People`, `Total_Cost`, `Status`, `Special_Requirements`, `Contact_Person`, `Contact_Phone`, `Advance_Payment`, `Payment_Status`, `Created_Date`, `Updated_Date`) VALUES
(4, 1001, 'Wedding Reception', '2025-09-15', '18:00:00', 'Community Center, Banani', 150, 45000.00, 'Confirmed', 'Vegetarian options needed, decorative presentation', 'Farhan Zahin', '01552306466', 15000.00, 'Partial', '2025-09-02 14:39:36', '2025-09-02 14:39:36'),
(5, 1002, 'Corporate Event', '2025-09-20', '12:00:00', 'Office Building, Gulshan', 50, 15000.00, 'Pending', 'Lunch meeting, professional setup', 'Ahona Hasan', '01316733425', 0.00, 'Pending', '2025-09-02 14:39:36', '2025-09-02 14:39:36'),
(6, 1003, 'Birthday Party', '2025-09-25', '15:00:00', 'Private Residence, Dhanmondi', 25, 7500.00, 'Confirmed', 'Kids party, colorful presentation', 'Jung Kook', '01635895385', 2500.00, 'Partial', '2025-09-02 14:39:36', '2025-09-02 14:39:36');

-- --------------------------------------------------------

--
-- Table structure for table `complaint_support`
--

CREATE TABLE `complaint_support` (
  `User_ID` int(10) NOT NULL,
  `Complaint_ID` int(10) NOT NULL,
  `Description` text NOT NULL,
  `Status` enum('Open','In Progress','Resolved','Closed') NOT NULL DEFAULT 'Open',
  `Submitted_Date` date NOT NULL,
  `Messages` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `complaint_support`
--

INSERT INTO `complaint_support` (`User_ID`, `Complaint_ID`, `Description`, `Status`, `Submitted_Date`, `Messages`) VALUES
(1004, 84774, 'Food was cold.', 'Open', '2025-09-07', '');

-- --------------------------------------------------------

--
-- Stand-in structure for view `cook_ratings_summary`
-- (See below for the actual view)
--
CREATE TABLE `cook_ratings_summary` (
`Cook_ID` int(10)
,`Cook_Name` varchar(100)
,`Cook_Email` varchar(50)
,`Exp_Years` int(50)
,`Total_Reviews` bigint(21)
,`Average_Rating` decimal(3,1)
,`Avg_Food_Quality` decimal(3,1)
,`Avg_Service_Rating` decimal(3,1)
,`Recommend_Count` bigint(21)
,`Recommend_Percentage` decimal(25,1)
,`Last_Review_Date` timestamp
);

-- --------------------------------------------------------

--
-- Table structure for table `customer_rates_cooks`
--

CREATE TABLE `customer_rates_cooks` (
  `Review_ID` int(10) NOT NULL,
  `CustomerID` int(11) NOT NULL,
  `CookID` int(11) NOT NULL,
  `Order_ID` int(10) DEFAULT NULL,
  `Rating` decimal(2,1) NOT NULL CHECK (`Rating` >= 1.0 and `Rating` <= 5.0),
  `Review_Title` varchar(100) NOT NULL DEFAULT 'Review',
  `Comment` text NOT NULL,
  `Food_Quality_Rating` decimal(2,1) DEFAULT NULL,
  `Service_Rating` decimal(2,1) DEFAULT NULL,
  `Would_Recommend` tinyint(1) DEFAULT 1,
  `Status` enum('Active','Hidden','Reported') NOT NULL DEFAULT 'Active',
  `Admin_Notes` text DEFAULT NULL,
  `Created_Date` timestamp NOT NULL DEFAULT current_timestamp(),
  `Updated_Date` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customer_rates_cooks`
--

INSERT INTO `customer_rates_cooks` (`Review_ID`, `CustomerID`, `CookID`, `Order_ID`, `Rating`, `Review_Title`, `Comment`, `Food_Quality_Rating`, `Service_Rating`, `Would_Recommend`, `Status`, `Admin_Notes`, `Created_Date`, `Updated_Date`) VALUES
(1, 1001, 1, NULL, 4.5, 'Excellent Bengali Food', 'The Vorta Thali was absolutely delicious! Authentic flavors and perfectly cooked. Mehjabin is a talented cook.', 4.5, 4.0, 1, 'Active', NULL, '2025-09-04 17:27:05', '2025-09-04 17:27:05'),
(2, 1002, 1, NULL, 5.0, 'Outstanding Experience', 'Amazing Korean food! The ramen was perfect and the service was excellent. Highly recommended!', 5.0, 5.0, 1, 'Active', NULL, '2025-09-04 17:27:05', '2025-09-04 17:27:05'),
(3, 1003, 2, NULL, 3.5, 'Good but could be better', 'The Indian thali was good but the curry was a bit too spicy for my taste. Overall decent experience.', 3.5, 4.0, 1, 'Active', NULL, '2025-09-04 17:27:05', '2025-09-04 17:27:05'),
(4, 1001, 2, NULL, 4.0, 'Great Burmese Cuisine', 'Enjoyed the Myanmar curry very much. Authentic taste and good presentation. Will order again!', 4.0, 4.5, 1, 'Active', NULL, '2025-09-04 17:27:05', '2025-09-04 17:27:05'),
(5, 1004, 1, NULL, 2.5, 'Below Expectations', 'The food was okay but not as described. Expected better quality for the price.', 2.5, 3.0, 0, 'Active', NULL, '2025-09-04 17:27:05', '2025-09-04 17:27:05'),
(6, 1001, 1, 10, 5.0, 'Good ', 'food was good i love it', 4.5, 5.0, 1, 'Active', NULL, '2025-09-05 15:51:09', '2025-09-05 15:51:09');

-- --------------------------------------------------------

--
-- Table structure for table `meal`
--

CREATE TABLE `meal` (
  `Meal_ID` int(10) NOT NULL,
  `Name` varchar(100) NOT NULL,
  `Description` text NOT NULL,
  `Proportion` varchar(50) NOT NULL,
  `Pricing` decimal(10,2) NOT NULL,
  `Cuisine` varchar(50) NOT NULL,
  `Status` enum('Available','Takes Time','Unavailable') DEFAULT 'Takes Time'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `meal`
--

INSERT INTO `meal` (`Meal_ID`, `Name`, `Description`, `Proportion`, `Pricing`, `Cuisine`, `Status`) VALUES
(1, 'Murgi Thali', 'Rice, Chicken curry, Cashewnut Salad', '1:1', 150.00, 'Bengali', 'Takes Time'),
(2, 'Vorta Thali', 'Aloo Vorta, Shutki Vorta, Potol Vorta, Begoon Vorta, Dim Vorta, Morich Vorta', '1:1', 100.00, 'Bengali', 'Takes Time'),
(3, 'Indian Thali', 'Dosa, Sambar, Rayta, Pickle', '1:1', 120.00, 'Indian', 'Takes Time'),
(4, 'Chinese Thali', 'Chowmein, Dim Sum, Fried Rice, Chili Chicken', '1:1', 200.00, 'Chinese', 'Takes Time'),
(5, 'Korean Thali', 'Beef Ramen, Boiled eggs(x2), Dumplings', '1:1', 300.00, 'Korean', 'Takes Time'),
(6, 'Burmese Thali', 'Myanmar Curry, Mohinga, Tea leaf Salad', '1:1', 300.00, 'Burmese', 'Takes Time'),
(7, 'Italian Thali', 'Prawn Spaghetti, Tiramisu, Margherita Pizza', '1:1', 500.00, 'Italian', 'Takes Time'),
(8, 'Veg Thali', 'Veg Biriyani, Tomato Curry, Green Salad', '1:1', 200.00, 'Continental', 'Takes Time'),
(9, 'Bengali Mach Thali', 'Shorisha Hilsha, Fresh Rupchanda, Loitta fry, Rice, Dal', '1:1', 600.00, 'Bengali', 'Takes Time');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `OrderID` int(11) NOT NULL,
  `Customer_ID` int(10) NOT NULL,
  `Cost` decimal(10,2) NOT NULL,
  `Status` enum('Pending','On the way','Accepted','Delivered','Cancelled') NOT NULL DEFAULT 'Pending',
  `Date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`OrderID`, `Customer_ID`, `Cost`, `Status`, `Date`) VALUES
(6, 1004, 350.00, 'On the way', '2025-09-02'),
(7, 1004, 100.00, 'Delivered', '2025-09-02'),
(8, 1004, 300.00, 'Accepted', '2025-09-02'),
(9, 1004, 300.00, 'Accepted', '2025-09-02'),
(10, 1001, 500.00, 'Delivered', '2025-09-04'),
(11, 1001, 150.00, 'Pending', '2025-09-05'),
(12, 1001, 750.00, 'Accepted', '2025-09-05'),
(13, 1001, 100.00, 'Accepted', '2025-09-05'),
(14, 1001, 300.00, 'Delivered', '2025-09-05'),
(15, 1005, 1200.00, 'Accepted', '2025-09-05'),
(16, 1004, 240.00, 'Cancelled', '2025-09-07'),
(17, 1004, 120.00, 'Accepted', '2025-09-07'),
(18, 1004, 300.00, 'Accepted', '2025-09-07');

-- --------------------------------------------------------

--
-- Table structure for table `orders_have_meal`
--

CREATE TABLE `orders_have_meal` (
  `M_ID` int(10) NOT NULL,
  `OrderID` int(10) NOT NULL,
  `Quantity` int(11) NOT NULL DEFAULT 1,
  `Price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders_have_meal`
--

INSERT INTO `orders_have_meal` (`M_ID`, `OrderID`, `Quantity`, `Price`) VALUES
(1, 6, 1, 150.00),
(1, 11, 1, 150.00),
(1, 12, 5, 150.00),
(2, 6, 2, 100.00),
(2, 7, 1, 100.00),
(2, 10, 5, 100.00),
(2, 13, 1, 100.00),
(3, 16, 2, 120.00),
(3, 17, 1, 120.00),
(5, 8, 1, 300.00),
(6, 9, 1, 300.00),
(6, 14, 1, 300.00),
(6, 18, 1, 300.00),
(9, 15, 2, 600.00);

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `U_ID` int(10) NOT NULL,
  `Email` varchar(50) NOT NULL,
  `Exp_Years` int(50) NOT NULL,
  `Name` varchar(100) NOT NULL,
  `Address` varchar(100) NOT NULL,
  `Type` enum('Admin','Customer','Cook','') NOT NULL DEFAULT 'Customer',
  `Password` varchar(15) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`U_ID`, `Email`, `Exp_Years`, `Name`, `Address`, `Type`, `Password`) VALUES
(1, 'mehjabin.hasan@gmail.com', 11, 'Mehjabin Hasan', '3/B Selpark, Mirpur', 'Cook', 'mou1111'),
(2, 'araf.cooker@gmail.com', 3, 'Araf Rakib', 'Sec-4, Uttara, Dhaka', 'Cook', 'araf4200'),
(3, 'rahim123@yahoo.com', 4, 'rahim hasan', 'rampura, dhaka', 'Cook', 'rahim123'),
(101, 'admin101@gmail.com', 0, 'Admin01', '34/d Baily Road, Dhaka', 'Admin', 'admin123'),
(1001, 'farhan.zahin@gmail.com', 0, 'Farhan Zahin', '7/A Banasree, Dhaka', 'Customer', 'zahin1234'),
(1002, 'ahona.hasan@gmail.com', 0, 'Ahona Hasan', '33/c Banani, Dhaka', 'Customer', 'ahona1234'),
(1003, 'jungkook@gmail.com', 0, 'Jung Kook', '16/f Gulshan-1, Dhaka', 'Customer', 'kook97'),
(1004, 'farzana.eti@gmail.com', 0, 'Farzana Eti', 'mirpur', 'Customer', '1234'),
(1005, 'tahmid@gmail.com', 0, 'tahmid', 'rampura, dhaka', 'Customer', 'tahmid123'),
(1006, 'amrakib21@gmail.com', 0, 'Araf Mahfuz Rakib', 'Probal Housing', 'Customer', '1234');

--
-- Triggers `user`
--
DELIMITER $$
CREATE TRIGGER `auto_assign_user_id` BEFORE INSERT ON `user` FOR EACH ROW BEGIN
    DECLARE next_user_id INT DEFAULT 0;
    
    -- Only assign ID if it's not provided (0 or NULL)
    IF NEW.U_ID IS NULL OR NEW.U_ID = 0 THEN
        -- Get the next ID for this user type
        SELECT next_id INTO next_user_id 
        FROM user_id_tracker 
        WHERE user_type = NEW.Type;
        
        -- Assign the ID
        SET NEW.U_ID = next_user_id;
        
        -- Update the tracker for next time
        UPDATE user_id_tracker 
        SET next_id = next_id + 1 
        WHERE user_type = NEW.Type;
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `user_cooks_meal`
--

CREATE TABLE `user_cooks_meal` (
  `Cook_ID` int(10) NOT NULL,
  `Meal_ID` int(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_cooks_meal`
--

INSERT INTO `user_cooks_meal` (`Cook_ID`, `Meal_ID`) VALUES
(1, 2),
(1, 3),
(1, 5),
(2, 3),
(2, 6);

-- --------------------------------------------------------

--
-- Table structure for table `user_id_tracker`
--

CREATE TABLE `user_id_tracker` (
  `user_type` enum('Cook','Customer','Admin') NOT NULL,
  `next_id` int(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_id_tracker`
--

INSERT INTO `user_id_tracker` (`user_type`, `next_id`) VALUES
('Cook', 4),
('Customer', 1007),
('Admin', 102);

-- --------------------------------------------------------

--
-- Table structure for table `user_phone_no`
--

CREATE TABLE `user_phone_no` (
  `User_ID` int(10) NOT NULL,
  `Phone_No` varchar(15) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_phone_no`
--

INSERT INTO `user_phone_no` (`User_ID`, `Phone_No`) VALUES
(2, '+8801540560255'),
(3, '+8801664572762'),
(1001, '+8801952306466'),
(1002, '+8801316733425'),
(1003, '+8801635895385'),
(1005, '+8801452306466'),
(1006, '8801833856681');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `catering_has_meals`
--
ALTER TABLE `catering_has_meals`
  ADD PRIMARY KEY (`Catering_ID`,`Meal_ID`),
  ADD KEY `catering_meal_fk` (`Meal_ID`);

--
-- Indexes for table `catering_services`
--
ALTER TABLE `catering_services`
  ADD PRIMARY KEY (`Catering_ID`),
  ADD KEY `customer_catering_fk` (`Customer_ID`);

--
-- Indexes for table `complaint_support`
--
ALTER TABLE `complaint_support`
  ADD PRIMARY KEY (`Complaint_ID`),
  ADD KEY `user_complaint_fk` (`User_ID`);

--
-- Indexes for table `customer_rates_cooks`
--
ALTER TABLE `customer_rates_cooks`
  ADD PRIMARY KEY (`Review_ID`),
  ADD KEY `order_review_fk` (`Order_ID`),
  ADD KEY `cook_rate_fk` (`CookID`),
  ADD KEY `customer_rate_fk` (`CustomerID`);

--
-- Indexes for table `meal`
--
ALTER TABLE `meal`
  ADD PRIMARY KEY (`Meal_ID`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`OrderID`),
  ADD KEY `customer_order_fk` (`Customer_ID`);

--
-- Indexes for table `orders_have_meal`
--
ALTER TABLE `orders_have_meal`
  ADD PRIMARY KEY (`M_ID`,`OrderID`),
  ADD KEY `order_meal_fk` (`OrderID`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`U_ID`);

--
-- Indexes for table `user_cooks_meal`
--
ALTER TABLE `user_cooks_meal`
  ADD PRIMARY KEY (`Cook_ID`,`Meal_ID`),
  ADD KEY `meal_cook_fk` (`Meal_ID`);

--
-- Indexes for table `user_id_tracker`
--
ALTER TABLE `user_id_tracker`
  ADD PRIMARY KEY (`user_type`);

--
-- Indexes for table `user_phone_no`
--
ALTER TABLE `user_phone_no`
  ADD PRIMARY KEY (`User_ID`,`Phone_No`),
  ADD KEY `user_phone_fk` (`User_ID`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `catering_services`
--
ALTER TABLE `catering_services`
  MODIFY `Catering_ID` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `complaint_support`
--
ALTER TABLE `complaint_support`
  MODIFY `Complaint_ID` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=84775;

--
-- AUTO_INCREMENT for table `customer_rates_cooks`
--
ALTER TABLE `customer_rates_cooks`
  MODIFY `Review_ID` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `meal`
--
ALTER TABLE `meal`
  MODIFY `Meal_ID` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `OrderID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `U_ID` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1007;

-- --------------------------------------------------------

--
-- Structure for view `cook_ratings_summary`
--
DROP TABLE IF EXISTS `cook_ratings_summary`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `cook_ratings_summary`  AS SELECT `c`.`U_ID` AS `Cook_ID`, `c`.`Name` AS `Cook_Name`, `c`.`Email` AS `Cook_Email`, `c`.`Exp_Years` AS `Exp_Years`, count(`r`.`Review_ID`) AS `Total_Reviews`, round(avg(`r`.`Rating`),1) AS `Average_Rating`, round(avg(`r`.`Food_Quality_Rating`),1) AS `Avg_Food_Quality`, round(avg(`r`.`Service_Rating`),1) AS `Avg_Service_Rating`, count(case when `r`.`Would_Recommend` = 1 then 1 end) AS `Recommend_Count`, round(count(case when `r`.`Would_Recommend` = 1 then 1 end) * 100.0 / count(`r`.`Review_ID`),1) AS `Recommend_Percentage`, max(`r`.`Created_Date`) AS `Last_Review_Date` FROM (`user` `c` left join `customer_rates_cooks` `r` on(`c`.`U_ID` = `r`.`CookID`)) WHERE `c`.`Type` = 'Cook' GROUP BY `c`.`U_ID`, `c`.`Name`, `c`.`Email`, `c`.`Exp_Years` ;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `catering_has_meals`
--
ALTER TABLE `catering_has_meals`
  ADD CONSTRAINT `catering_meal_fk` FOREIGN KEY (`Meal_ID`) REFERENCES `meal` (`Meal_ID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `catering_service_fk` FOREIGN KEY (`Catering_ID`) REFERENCES `catering_services` (`Catering_ID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `catering_services`
--
ALTER TABLE `catering_services`
  ADD CONSTRAINT `customer_catering_fk` FOREIGN KEY (`Customer_ID`) REFERENCES `user` (`U_ID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `complaint_support`
--
ALTER TABLE `complaint_support`
  ADD CONSTRAINT `user_complaint_fk` FOREIGN KEY (`User_ID`) REFERENCES `user` (`U_ID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `customer_rates_cooks`
--
ALTER TABLE `customer_rates_cooks`
  ADD CONSTRAINT `cook_rate_fk` FOREIGN KEY (`CookID`) REFERENCES `user` (`U_ID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `customer_rate_fk` FOREIGN KEY (`CustomerID`) REFERENCES `user` (`U_ID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `order_review_fk` FOREIGN KEY (`Order_ID`) REFERENCES `orders` (`OrderID`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `customer_order_fk` FOREIGN KEY (`Customer_ID`) REFERENCES `user` (`U_ID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `orders_have_meal`
--
ALTER TABLE `orders_have_meal`
  ADD CONSTRAINT `meal_order_fk` FOREIGN KEY (`M_ID`) REFERENCES `meal` (`Meal_ID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `order_meal_fk` FOREIGN KEY (`OrderID`) REFERENCES `orders` (`OrderID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `user_cooks_meal`
--
ALTER TABLE `user_cooks_meal`
  ADD CONSTRAINT `cook_meal_fk` FOREIGN KEY (`Cook_ID`) REFERENCES `user` (`U_ID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `meal_cook_fk` FOREIGN KEY (`Meal_ID`) REFERENCES `meal` (`Meal_ID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `user_phone_no`
--
ALTER TABLE `user_phone_no`
  ADD CONSTRAINT `user_phone_fk` FOREIGN KEY (`User_ID`) REFERENCES `user` (`U_ID`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
