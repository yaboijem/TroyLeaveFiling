<?php
    @ob_start(); if (!isset($_SESSION)) {session_start();}
    if (!isset($_SESSION['admin_or_user'])) {exit;}

    include("dbconnect.php");
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        //Updating Room Display Status
        if(isset($_POST['roomdisplay_action'])){
            // echo $_SESSION['datelogin'];
            // exit;
            $datelogin = date('m/d/Y H:i:s', strtotime(str_replace('-', '/', $_SESSION['datelogin']))); //$datelogin = $_SESSION['datelogin'];
            $itroomid = $_POST['itroomid'];
            date_default_timezone_set("asia/manila");
            $date_time = new DateTime(); //date('m/d/Y', strtotime(str_replace('-', '/', new DateTime())));
            $date_time = $date_time->format('m/d/Y H:i:s');
            $date_time2 = date('m/d/Y H:i:s', strtotime(str_replace('-', '/', $date_time))); //date_format($date_time, 'y-m-d H:i:s');
            // echo $date_time2;
            // exit;
            // $time_time = date("h:i:s");
            if ($_SESSION['admin_or_user'] == "admin") {
                $userwholog = $_SESSION['adminid'];
            } else {
                $userwholog = $_SESSION['userid'];
            }
            $user_shift = $_SESSION['shift'];
            
            if ($_POST['roomdisplay_action'] == 'room_checkout') { //Front Office
                $stmt = $conn->prepare("update roomfiletbl set status = 'CO',checkoutuserid = ?,checkouttrandate = ?,checkouttime = ?,checkoutshift = ? where roomid = ?");
                $stmt->bind_param("sssss",$userwholog,$datelogin,$date_time2,$user_shift,$itroomid);
                $stmt->execute();
                $stmt->close();
                
                $stmt = $conn->prepare("Select * from roomfiletbl where roomid = ?"); 
                $stmt->bind_param("i", $itroomid);
                $stmt->execute();
                $result = $stmt->get_result();
                while ($row = $result->fetch_assoc()) {
                    //Insert the transac to fotranfile
                    try {
                        $date_time_start = $row['timestart'];
                        
                        $stmt = $conn->prepare("INSERT INTO fotranfiletbl (roomcode,checkinuserid,checkintrandate,checkintime,checkinshift,checkoutuserid,checkouttrandate,checkouttime,checkoutshift) VALUES (?,?,?,?,?,?,?,?,?)");
                        $stmt->bind_param("sssssssss", $row['roomcode'],$row['checkinuserid'],$row['checkintrandate'],$row['checkintime'],$row['checkinshift'],$row['checkoutuserid'],$row['checkouttrandate'],$row['checkouttime'],$row['checkoutshift']);
                        $stmt->execute();
                        $stmt->close();
                    } catch(Exception $e) {
                        echo("Error description: " . $conn -> error);
                        exit;
                    }
                    // echo $row['checkouttrandate'];
                }
            } else if ($_POST['roomdisplay_action'] == 'room_checkin') { //Front Office
                // try {
                    $stmt = $conn->prepare("update roomfiletbl set status = 'CI',checkinuserid = ?,checkintrandate = ?,checkintime = ?,checkinshift = ? where roomid = ?");
                    $stmt->bind_param("sssss",$userwholog,$datelogin,$date_time2,$user_shift,$itroomid);
                    $stmt->execute();
                    $stmt->close();
                // } catch(Exception $e) {
                    // echo("Error description: " . $conn -> error);
                    // exit;
                // }
            } elseif ($_POST['roomdisplay_action'] == 'room_get_hk') { //Housekeeper get details
                $stmt = $connms->prepare("SELECT housekeeperid FROM roomfile WHERE roomid = :itroomid");
                $stmt->bindParam(':itroomid', $itroomid, PDO::PARAM_INT);
                $stmt->execute();
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($row) {
                    echo $row['housekeeperid'];
                    exit;
                }
            } else { //Housekeeper - Update clickbtnroomtype()
                //echo "dinalan ya keni";
                $ithousekeeperid = $_POST['ithousekeeperid'];
                $itremarks = $_POST['itremarks'];
                $itcleaningtypeUse = $_POST['itcleaningtypeUse'];
                $cbonprocesscleaning = $_POST['cbonprocesscleaning'];
                // ken nakung gawan ya ing FI dapat kapag AV ya pa mag update ya pa keng room detang user_shift
                // trandateend = $datelogin, timeend = $date_time2, remarks_end = $itremarks tapos i change
                // to FI ang room, kapag na FI na dapat mag insert into na sa hktranfile at meron dapat inspection by
                // gawang account keng pang supervisor para mag inspect
                if ($itcleaningtypeUse == "AV") { // Housekeeper finished cleaning
                //     //For Inspection
                //     $stmt = $connms->prepare("UPDATE roomfile SET RoomStatus = 'FI', ROSNoOld = Null, CheckOutUserID = NULL, housekeeperid = Null, onprocesscleaning = Null, housekeepershift = Null, trandatestart = NULL, timestart = Null, remarks_start = NULL, trandateend = NULL, timeend = Null, remarks_end = NULL WHERE roomid = :itroomid");
                //     //For Available
                //     //$stmt = $connms->prepare("UPDATE roomfile SET RoomStatus = 'AV', ROSNoOld = Null, CheckOutUserID = NULL, housekeeperid = Null, onprocesscleaning = Null, housekeepershift = Null, trandatestart = NULL, timestart = Null, remarks_start = NULL, trandateend = NULL, timeend = Null, remarks_end = NULL WHERE roomid = :itroomid");
                //     $stmt->bindParam(':itroomid', $itroomid, PDO::PARAM_INT);
                //     $stmt->execute();
                // elseif ($itcleaningtypeUse == "FI") { // Housekeeper finished cleaning {}
                    $stmt = $connms->prepare("SELECT * FROM roomfile WHERE roomid = :itroomid");
                    $stmt->bindParam(':itroomid', $itroomid, PDO::PARAM_INT);
                    $stmt->execute();
                    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    foreach ($rows as $row) {
                        // Insert transaction to hktranfile
                        try {
                            // keni naku na i check ya ing transaction a ini nun gok ya ing hktranfiletbl
                            $date_time_start = $row['timestart'];
                            $date_trandate_start = $row['trandatestart'];
                            
                            $stmt = $connms->prepare("INSERT INTO hktranfiletbl (RoomID, [RoomStatus], checkoutuserid, housekeeperid, housekeepershift, trandatestart, timestart, remarks_start, trandateend, timeend, remarks_end, ROSNo) 
                                                    VALUES (:RoomID, :RoomStatus, :checkoutuserid, :housekeeperid, :housekeepershift, :trandatestart, :timestart, :remarks_start, :trandateend, :timeend, :remarks_end, :ROSNo)");
                            $stmt->bindParam(':RoomID', $row['RoomID']);
                            $stmt->bindParam(':RoomStatus', $row['RoomStatus']);
                            $stmt->bindParam(':checkoutuserid', $row['CheckOutUserID']);
                            $stmt->bindParam(':housekeeperid', $row['housekeeperid']);
                            $stmt->bindParam(':housekeepershift', $user_shift);
                            $stmt->bindParam(':trandatestart', $date_trandate_start);
                            $stmt->bindParam(':timestart', $date_time_start);
                            $stmt->bindParam(':remarks_start', $row['remarks_start']);
                            $stmt->bindParam(':trandateend', $datelogin);
                            $stmt->bindParam(':timeend', $date_time2);
                            $stmt->bindParam(':remarks_end', $itremarks);
                            $stmt->bindParam(':ROSNo', $row['ROSNoOld']);
                            $stmt->execute();
                            
                        } catch (Exception $e) {
                            echo "Error description: " . $e->getMessage();
                            exit;
                        }
                        
                        //For Available
                        $stmt = $connms->prepare("UPDATE roomfile SET RoomStatus = 'AV', ROSNoOld = Null, CheckOutUserID = NULL, housekeeperid = Null, onprocesscleaning = Null, housekeepershift = Null, trandatestart = NULL, timestart = Null, remarks_start = NULL, trandateend = NULL, timeend = Null, remarks_end = NULL WHERE roomid = :itroomid");
                        $stmt->bindParam(':itroomid', $itroomid, PDO::PARAM_INT);
                        $stmt->execute();
                        
                    }
                } else { // Housekeeper doing the cleaning
                    
                    $date_time2 = date('m/d/Y H:i:s', strtotime(str_replace('-', '/', $date_time2)));
                    
                    $stmt = $connms->prepare("UPDATE roomfile SET roomstatus = :itcleaningtypeUse, onprocesscleaning = :cbonprocesscleaning, remarks_start = :itremarks, trandatestart = :datelogin, timestart = :date_time2, housekeeperid = :ithousekeeperid, housekeepershift = :user_shift WHERE roomid = :itroomid");
                    $stmt->bindParam(':itcleaningtypeUse', $itcleaningtypeUse);
                    $stmt->bindParam(':cbonprocesscleaning', $cbonprocesscleaning);
                    $stmt->bindParam(':itremarks', $itremarks);
                    $stmt->bindParam(':datelogin', $datelogin);
                    $stmt->bindParam(':date_time2', $date_time2);
                    $stmt->bindParam(':ithousekeeperid', $ithousekeeperid);
                    $stmt->bindParam(':user_shift', $user_shift);
                    $stmt->bindParam(':itroomid', $itroomid, PDO::PARAM_INT);
                    $stmt->execute();
                }
            }

            // //Update Token Here
            // $utoken = date_format($date_time, 'ymdHis') . $userwholog . uniqid();
            // $_SESSION['utoken'] = $utoken;
            
            // $sql = "SELECT * FROM timerforupdatetbl";
            // if($result = mysqli_query($connms, $sql)){
            //     if(mysqli_num_rows($result) > 0){
            //         $stmt = $connms->prepare("update timerforupdatetbl set utoken = ?");
            //         $stmt->bind_param("s", $utoken);
            //         $stmt->execute();
            //         $stmt->close();
            //     } else {
            //         try {
            //         $stmt = $connms->prepare("INSERT INTO timerforupdatetbl (utoken) VALUES (?)");
            //         $stmt->bind_param("s",$utoken);
            //         $stmt->execute();
            //         $stmt->close();
            //         } catch(Exception $e) {
            //             echo("Error description: " . $conn -> error);
            //             exit;
            //         }
            //     }
            // }

            // Update Token Here
            $utoken = date('ymdHis') . $userwholog . uniqid();
            $_SESSION['utoken'] = $utoken;

            $sql = "SELECT * FROM timerforupdatetbl";
            $stmt = $connms->prepare($sql);
            $stmt->execute();

            $noofRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (!empty($noofRows)) {
                $updateSql = "UPDATE timerforupdatetbl SET utoken = :utoken";
                $updateStmt = $connms->prepare($updateSql);
                $updateStmt->bindParam(':utoken', $utoken);
                $updateStmt->execute();
            } else {
                $insertSql = "INSERT INTO timerforupdatetbl (utoken) VALUES (:utoken)";
                $insertStmt = $connms->prepare($insertSql);
                $insertStmt->bindParam(':utoken', $utoken);
                $insertStmt->execute();
            }

            if ($_POST['roomdisplay_action'] == 'room_checkout') { 
                echo "Successfuly Check Out!";
            } else if ($_POST['roomdisplay_action'] == 'room_checkin') { 
                echo "Successfuly Check IN!";
            } else if ($_POST['roomdisplay_action'] == 'room_get_hk') { //Housekeeper get details 
            } else { //Housekeeping update status
                echo "Successfuly Updated!"; 
            }

        }    

        if(isset($_POST['room_check_housekeeper'])){ //Check House Keeper
            $itroomid = $_POST['itroomid'];
            $stmt = $conn->prepare("SELECT roomfiletbl.timestart,housekeeperfiletbl.housekeepername FROM roomfiletbl Left Outer Join housekeeperfiletbl on roomfiletbl.housekeeperid = housekeeperfiletbl.housekeeperid where roomid = ?"); 
            $stmt->bind_param("i", $itroomid);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                date_default_timezone_set("asia/manila");
                $datetime1 = strtotime($row['timestart']);
                $datetime2 = strtotime(date("Y-m-d H:i:s"));
                $seconds = round(($datetime2 - $datetime1));
                $minutes = (int) (round($seconds) / 60);
                $hours = (int) (round($minutes) / 60);
                $interval1 = $minutes - (round($hours * 60)).'min(s)';
                $interval2 = $hours.'hour(s) and ';
                $interval = $interval2 . $interval1;

                echo "Housekeeper Name : ".$row['housekeepername']. PHP_EOL ."Time Start : ".$row['timestart']
                . PHP_EOL. "Durations : " .$interval;
                exit;
            }
        }

        // For Timer Getting the latest Utoke
        if (isset($_POST['check_value']) && isset($_SESSION['utoken'])) {
            // $sql = "SELECT utoken FROM timerforupdatetbl";
            // if($result = mysqli_query($conn, $sql)){
            //     if(mysqli_num_rows($result) > 0){
            //         $row = mysqli_fetch_array($result) ;
            //         if ($_SESSION['utoken'] == $row['utoken']) {
            //             // echo "pareho";
            //             echo "0"; 
            //         } else {
            //             // echo "dito yung update sa room";
            //             $data = array();
            //             $sql2 = "SELECT roomid,status FROM roomfiletbl";
            //             if($result2 = mysqli_query($conn, $sql2)){
            //                 if(mysqli_num_rows($result2) > 0){
            //                     $row2 = mysqli_fetch_array($result2) ;
            //                     foreach ($result2 as $row2) { //Loop the result
            //                         $data[] = $row2;
            //                     }
            //                     echo json_encode($data);
            //                     // $_SESSION['utoken'] = $row['utoken'];
            //                     exit;
            //                 }
            //             } else { echo "0"; }
            //         }
            //     } else {
            //         echo "0"; //Walang record yung timerforupdatetbl       
            //     }
            // }
            
            $sql = "SELECT utoken FROM timerforupdatetbl";
            $stmt = $connms->prepare($sql);
            $stmt->execute();

            $utokenRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (!empty($utokenRows)) {
                $row = $utokenRows[0];
                if ($_SESSION['utoken'] == $row['utoken']) {
                    echo "0";  // $_SESSION['utoken'] matches the database value
                } else {
                    $data = array();
                    $sql2 = "SELECT roomid, roomstatus FROM roomfile";
                    $stmt2 = $connms->prepare($sql2);
                    $stmt2->execute();

                    $roomRows = $stmt2->fetchAll(PDO::FETCH_ASSOC);

                    if (!empty($roomRows)) {
                        echo json_encode($roomRows);
                        exit;
                    } else {
                        echo "0"; // No records in roomfile
                    }
                }
            } else {
                echo "0"; // No records in timerforupdatetbl
            }

            // echo $_SESSION['utoken'];
        }


        // Room CRUD
        if(isset($_POST['room_action'])){
            
            $itroomid = $_POST['itroomid'];
            // $itroomcode = $_POST['itroomcode'];
            $itroomcode = mysqli_real_escape_string($conn, $_POST['itroomcode']);
            $cbactiveroom = $_POST['cbactiveroom'];
            $itroomtypeid = $_POST['itroomtypeid'];
            $itstatus = $_POST['itstatus'];

            if ($_POST['room_action'] == 'add') {
                //OOP Prepared Statement 
                $stmt = $conn->prepare("SELECT roomcode from roomfiletbl where roomcode = ?");
                $stmt->bind_param("s", $itroomcode);
                $stmt->execute();
                $result = $stmt->get_result();
                $total_records = $result->num_rows;
                $stmt->close();

                if ($total_records > 0) {
                    //echo "<script>alert('$c_email is already exist!')</script>";
                    echo "Room Code [" . $itroomcode . "] already exist!";
                    exit;
                } else {
                    //OOP Prepared Statement
                    try {
                    $stmt = $conn->prepare("INSERT INTO roomfiletbl (roomcode,roomtypeid,`status`,active) VALUES (?,?,?,?)");
                    $stmt->bind_param("ssss", $itroomcode,$itroomtypeid,$itstatus,$cbactiveroom);
                    $stmt->execute();
                    $stmt->close();
                    } catch(Exception $e) {
                        echo("Error description: " . $conn -> error);
                        exit;
                    }
                    echo "Successfuly Added!";
                }
            } else { //Update
                // OOP Prepared Statement
                $stmt = $conn->prepare("SELECT roomid,roomcode from roomfiletbl where roomcode = ?");
                $stmt->bind_param("s", $itroomcode);
                $stmt->execute();
                $result = $stmt->get_result();
                $row = $result->fetch_assoc();
                $total_records = $result->num_rows;
                $stmt->close();
                
                if (empty($row['roomid'])) { //if the $row['roomid'] is empty meaning the roomcode is not existing and it is ok to update
                    goto updateroom;
                }

                //echo "<script>alert('$c_email is already exist!')</script>";
                if ($itroomid != $row['roomid']) {
                    echo "Room Code [" . $itroomcode . "] already exist!";
                    exit;
                } else {
updateroom:
                    $stmt = $conn->prepare("update roomfiletbl set roomtypeid = ?, status = ?, active = ? where roomid = ?");
                    $stmt->bind_param("ssss", $itroomtypeid,$itstatus,$cbactiveroom,$itroomid);
                    $stmt->execute();
                    $stmt->close();
                    echo "Successfuly Updated!";
                }
                
                //Update Token Here
                if ($_SESSION['admin_or_user'] == "admin") {
                    $userwholog = $_SESSION['adminid'];
                } else {
                    $userwholog = $_SESSION['userid'];
                }

                date_default_timezone_set("asia/manila");
                $date_time = new DateTime();
                $utoken = date_format($date_time, 'ymdHis') . $userwholog . uniqid();
                $_SESSION['utoken'] = $utoken;
                
                $sql = "SELECT * FROM timerforupdatetbl";
                if($result = mysqli_query($conn, $sql)){
                    if(mysqli_num_rows($result) > 0){
                        $stmt = $conn->prepare("update timerforupdatetbl set utoken = ?");
                        $stmt->bind_param("s", $utoken);
                        $stmt->execute();
                        $stmt->close();
                    } else {
                        try {
                        $stmt = $conn->prepare("INSERT INTO timerforupdatetbl (utoken) VALUES (?)");
                        $stmt->bind_param("s",$utoken);
                        $stmt->execute();
                        $stmt->close();
                        } catch(Exception $e) {
                            echo("Error description: " . $conn -> error);
                            exit;
                        }
                    }
                }
            }
        }
        if(isset($_POST['room_delete'])){
            $roomid = $_POST['roomid'];
            // OOP Prepared Statement
            $stmt = $conn->prepare("Delete from roomfiletbl where roomid = ?");
            $stmt->bind_param("s",$roomid);
            $stmt->execute();
            $stmt->close();
            echo "Successfuly Deleted!";
        }



        // Department CRUD
        if(isset($_POST['department_action'])){
            
            $itdepartmentid = $_POST['itdepartmentid'];
            // $itdepartmentname = $_POST['itdepartmentname'];
            $itdepartmentname = mysqli_real_escape_string($conn, $_POST['itdepartmentname']);
            $cbactivedepartment = $_POST['cbactivedepartment'];

            if ($_POST['department_action'] == 'add') {
                //OOP Prepared Statement 
                $stmt = $conn->prepare("SELECT deptname from departmentfiletbl where deptname = ?");
                $stmt->bind_param("s", $itdepartmentname);
                $stmt->execute();
                $result = $stmt->get_result();
                $total_records = $result->num_rows;
                $stmt->close();

                if ($total_records > 0) {
                    //echo "<script>alert('$c_email is already exist!')</script>";
                    echo "Department Name [" . $itdepartmentname . "] already exist!";
                    exit;
                } else {
                    //OOP Prepared Statement
                    try {
                    $stmt = $conn->prepare("INSERT INTO departmentfiletbl (deptname,active) VALUES (?,?)");
                    $stmt->bind_param("ss", $itdepartmentname,$cbactivedepartment);
                    $stmt->execute();
                    $stmt->close();
                    } catch(Exception $e) {
                        echo("Error description: " . $conn -> error);
                        exit;
                    }
                    echo "Successfuly Added!";
                }
            } else { //Update
                // OOP Prepared Statement
                $stmt = $conn->prepare("SELECT deptid,deptname from departmentfiletbl where deptname = ?");
                $stmt->bind_param("s", $itdepartmentname);
                $stmt->execute();
                $result = $stmt->get_result();
                $row = $result->fetch_assoc();
                $total_records = $result->num_rows;
                $stmt->close();
                
                if (empty($row['deptid'])) { //if the $row['deptid'] is empty meaning the deptname is not existing and it is ok to update
                    goto updatedept;
                }

                //echo "<script>alert('$c_email is already exist!')</script>";
                if ($itdepartmentid != $row['deptid']) {
                    echo "Department Name [" . $itdepartmentname . "] already exist!";
                    exit;
                } else {
updatedept:
                    $stmt = $conn->prepare("update departmentfiletbl set deptname = ?, active = ? where deptid = ?");
                    $stmt->bind_param("sss", $itdepartmentname,$cbactivedepartment,$itdepartmentid);
                    $stmt->execute();
                    $stmt->close();
                    echo "Successfuly Updated!";
                }
            }
        }
        if(isset($_POST['department_delete'])){
            $departmentid = $_POST['departmentid'];
            // OOP Prepared Statement
            $stmt = $conn->prepare("Delete from departmentfiletbl where deptid = ?");
            $stmt->bind_param("s",$departmentid);
            $stmt->execute();
            $stmt->close();
            echo "Successfuly Deleted!";
        }


        // housekeeper CRUD
        if(isset($_POST['housekeeper_action'])){
            $ithousekeeperid = $_POST['ithousekeeperid'];
            $ithousekeepername = mysqli_real_escape_string($conn, $_POST['ithousekeepername']);
            $itemail = $_POST["itemail"];
            // echo $_POST['itpassword'];
            // exit;   
            // $itpassword = sha1(mysqli_real_escape_string($conn, $_POST['itpassword']));
            // //check email for right format in PHP dahil meron na sa AJAX optional lang ito
            // // $itemail = test_input($_POST["itemail"]);
            // // if (!filter_var($itemail, FILTER_VALIDATE_EMAIL)) {
            // //     echo "Invalid email format";
            // //     exit;
            // // }

            $cbactivehousekeeper = $_POST['cbactivehousekeeper'];
            // echo $cbclosedrequest;
            // exit;    
            if ($_POST['housekeeper_action'] == 'add') {
                //OOP Prepared Statement
                $stmt = $conn->prepare("SELECT housekeepername from housekeeperfiletbl where housekeepername = ?");
                $stmt->bind_param("s", $ithousekeepername);
                $stmt->execute();
                $result = $stmt->get_result();
                $total_records = $result->num_rows;
                $stmt->close();

                if ($total_records > 0) {
                    //echo "<script>alert('$c_email is already exist!')</script>";
                    echo "housekeeper Name [" . $ithousekeepername . "] already exist!";
                    exit;
                } else {
                    //OOP Prepared Statement
                    try {
                    $stmt = $conn->prepare("INSERT INTO housekeeperfiletbl (housekeepername,email,active) VALUES (?,?,?)");
                    $stmt->bind_param("sss", $ithousekeepername,$itemail,$cbactivehousekeeper);
                    $stmt->execute();
                    $stmt->close();
                    } catch(Exception $e) {
                        echo("Error description: " . $conn -> error);
                        exit;
                    }
                    echo "Successfuly Added!";
                }
            } else { //Update
                // OOP Prepared Statement
                $stmt = $conn->prepare("SELECT housekeeperid,housekeepername from housekeeperfiletbl where housekeepername = ?");
                $stmt->bind_param("s", $ithousekeepername);
                $stmt->execute();
                $result = $stmt->get_result();
                $row = $result->fetch_assoc();
                $total_records = $result->num_rows;
                $stmt->close();

                if (empty($row['housekeeperid'])) { //if the $row['housekeeperid'] is empty meaning the housekeepername is not existing and it is ok to update
                    goto updatehousekeeper;
                }

                if ($ithousekeeperid != $row['housekeeperid']) {
                    //echo "<script>alert('$c_email is already exist!')</script>";
                    echo "housekeeper Name [" . $ithousekeepername . "] already exist!";
                    exit;
                } else {
updatehousekeeper:
                    // For password that has not change
                    // if (empty($_POST['itpassword'])) { 
                        $stmt = $conn->prepare("update housekeeperfiletbl set housekeepername = ?,email = ?,active = ? where housekeeperid = ?");
                        $stmt->bind_param("ssss", $ithousekeepername,$itemail,$cbactivehousekeeper,$ithousekeeperid);
                    // } else {
                    //     $stmt = $conn->prepare("update housekeeperfiletbl set housekeepername = ?,email = ?,`password` = ?,active = ? where housekeeperid = ?");
                    //     $stmt->bind_param("sssss", $ithousekeepername,$itemail,$itpassword,$cbactivehousekeeper,$ithousekeeperid);
                    // }
                    $stmt->execute();
                    $stmt->close();
                    echo "Successfuly Updated!";
                }
            }
        }
        if(isset($_POST['housekeeper_delete'])){
            $housekeeperid = $_POST['housekeeperid'];
            // OOP Prepared Statement
            $stmt = $conn->prepare("Delete from housekeeperfiletbl where housekeeperid = ?");
            $stmt->bind_param("s",$housekeeperid);
            $stmt->execute();
            $stmt->close();
            echo "Successfuly Deleted!";
        }


        // Roomtype CRUD
        if(isset($_POST['roomtype_action'])){
            
            $itroomtypeid = $_POST['itroomtypeid'];
            // $itroomtypename = $_POST['itroomtypename'];
            $itroomtypename = mysqli_real_escape_string($conn, $_POST['itroomtypename']);
            $cbactiveroomtype = $_POST['cbactiveroomtype'];

            if ($_POST['roomtype_action'] == 'add') {
                //OOP Prepared Statement 
                $stmt = $conn->prepare("SELECT roomtypename from roomtypefiletbl where roomtypename = ?");
                $stmt->bind_param("s", $itroomtypename);
                $stmt->execute();
                $result = $stmt->get_result();
                $total_records = $result->num_rows;
                $stmt->close();

                if ($total_records > 0) {
                    //echo "<script>alert('$c_email is already exist!')</script>";
                    echo "roomtype Name [" . $itroomtypename . "] already exist!";
                    exit;
                } else {
                    //OOP Prepared Statement
                    try {
                    $stmt = $conn->prepare("INSERT INTO roomtypefiletbl (roomtypename,active) VALUES (?,?)");
                    $stmt->bind_param("ss", $itroomtypename,$cbactiveroomtype);
                    $stmt->execute();
                    $stmt->close();
                    } catch(Exception $e) {
                        echo("Error description: " . $conn -> error);
                        exit;
                    }
                    echo "Successfuly Added!";
                }
            } else { //Update
                // OOP Prepared Statement
                $stmt = $conn->prepare("SELECT roomtypeid,roomtypename from roomtypefiletbl where roomtypename = ?");
                $stmt->bind_param("s", $itroomtypename);
                $stmt->execute();
                $result = $stmt->get_result();
                $row = $result->fetch_assoc();
                $total_records = $result->num_rows;
                $stmt->close();
                
                if (empty($row['roomtypeid'])) { //if the $row['roomtypeid'] is empty meaning the roomtypename is not existing and it is ok to update
                    goto updateroomtype;
                }

                //echo "<script>alert('$c_email is already exist!')</script>";
                if ($itroomtypeid != $row['roomtypeid']) {
                    echo "roomtype Name [" . $itroomtypename . "] already exist!";
                    exit;
                } else {
updateroomtype:
                    $stmt = $conn->prepare("update roomtypefiletbl set roomtypename = ?, active = ? where roomtypeid = ?");
                    $stmt->bind_param("sss", $itroomtypename,$cbactiveroomtype,$itroomtypeid);
                    $stmt->execute();
                    $stmt->close();
                    echo "Successfuly Updated!";
                }
            }
        }
        if(isset($_POST['roomtype_delete'])){
            $roomtypeid = $_POST['roomtypeid'];
            // OOP Prepared Statement
            $stmt = $conn->prepare("Delete from roomtypefiletbl where roomtypeid = ?");
            $stmt->bind_param("s",$roomtypeid);
            $stmt->execute();
            $stmt->close();
            echo "Successfuly Deleted!";
        }

        // User CRUD
        if(isset($_POST['user_action'])){
            $ituserid = $_POST['ituserid'];
            $itusername = mysqli_real_escape_string($conn, $_POST['itusername']);
            $itemail = $_POST["itemail"];
            $itdepartment = mysqli_real_escape_string($conn, $_POST['itdepartment']);
            $itpassword = password_hash($_POST['itpassword'], PASSWORD_DEFAULT); //sha1(mysqli_real_escape_string($conn, $_POST['itpassword']));
            //check email for right format in PHP dahil meron na sa AJAX optional lang ito
            // $itemail = test_input($_POST["itemail"]);
            // if (!filter_var($itemail, FILTER_VALIDATE_EMAIL)) {
            //     echo "Invalid email format";
            //     exit;
            // }
            $cbclosedrequest = $_POST['cbclosedrequest'];
            $cbactiveuser = $_POST['cbactiveuser'];
            // echo $cbclosedrequest;
            // exit;    
            if ($_POST['user_action'] == 'add') {
                //OOP Prepared Statement
                $stmt = $conn->prepare("SELECT username from userfiletbl where username = ?");
                $stmt->bind_param("s", $itusername);
                $stmt->execute();
                $result = $stmt->get_result();
                $total_records = $result->num_rows;
                $stmt->close();

                if ($total_records > 0) {
                    //echo "<script>alert('$c_email is already exist!')</script>";
                    echo "User Name [" . $itusername . "] already exist!";
                    exit;
                } else {
                    //OOP Prepared Statement
                    try {
                    $stmt = $conn->prepare("INSERT INTO userfiletbl (username,email,deptid,`password`,closedrequest,active) VALUES (?,?,?,?,?,?)");
                    $stmt->bind_param("ssssss", $itusername,$itemail,$itdepartment,$itpassword,$cbclosedrequest,$cbactiveuser);
                    $stmt->execute();
                    $stmt->close();
                    } catch(Exception $e) {
                        echo("Error description: " . $conn -> error);
                        exit;
                    }
                    echo "Successfuly Added!";
                }
            } else { //Update
                // OOP Prepared Statement
                $stmt = $conn->prepare("SELECT userid,username from userfiletbl where username = ?");
                $stmt->bind_param("s", $itusername);
                $stmt->execute();
                $result = $stmt->get_result();
                $row = $result->fetch_assoc();
                $total_records = $result->num_rows;
                $stmt->close();

                if (empty($row['userid'])) { //if the $row['userid'] is empty meaning the username is not existing and it is ok to update
                    goto updateuser;
                }

                if ($ituserid != $row['userid']) {
                    //echo "<script>alert('$c_email is already exist!')</script>";
                    echo "User Name [" . $itusername . "] already exist!";
                    exit;
                } else {
updateuser:
                    // For password that has not change
                    if (empty($_POST['itpassword'])) { 
                        $stmt = $conn->prepare("update userfiletbl set username = ?,email = ?,deptid = ?,closedrequest = ?,active = ? where userid = ?");
                        $stmt->bind_param("ssssss", $itusername,$itemail,$itdepartment,$cbclosedrequest,$cbactiveuser,$ituserid);
                    } else {
                        $stmt = $conn->prepare("update userfiletbl set username = ?,email = ?,deptid = ?,`password` = ?,closedrequest = ?,active = ? where userid = ?");
                        $stmt->bind_param("sssssss", $itusername,$itemail,$itdepartment,$itpassword,$cbclosedrequest,$cbactiveuser,$ituserid);
                    }
                    $stmt->execute();
                    $stmt->close();
                    echo "Successfuly Updated!";
                }
            }
        }
        if(isset($_POST['user_delete'])){
            $userid = $_POST['userid'];
            // OOP Prepared Statement
            $stmt = $conn->prepare("Delete from userfiletbl where userid = ?");
            $stmt->bind_param("s",$userid);
            $stmt->execute();
            $stmt->close();
            echo "Successfuly Deleted!";
        }

        if(isset($_POST['employee_delete'])){
            $idno = $_POST['idno'];
            // OOP Prepared Statement
            $stmt = $conn->prepare("Delete from employeefiletbl where idno = ?");
            $stmt->bind_param("s",$idno);
            $stmt->execute();
            $stmt->close();
            echo "Successfuly Deleted!";
        }

        if (isset($_POST['employee_resetpw'])) {
            $idno = $_POST['idno'];
            $email = $_POST['email'];
            $employeeName = $_POST['employeename'];

            include('generateStrongpassword.php');
            $newPassword = generateStrongPassword(8, false, 'luds');
            include('employeetemporarypasswordsendemail.php');
            // // OOP Prepared Statement
            // $stmt = $conn->prepare("UPDATE employeefiletbl SET `password` = ? WHERE idno = ?");
            // $stmt->bind_param("ss", $newPassword, $idno);
            // $stmt->execute();
            // $stmt->close();
            // echo "Password reset successful!";
        }

        // Admin CRUD
        if(isset($_POST['admin_action'])){
            $itadminid = $_POST['itadminid'];
            $itadminname = mysqli_real_escape_string($conn, $_POST['itadminname']);
            $itemail = $_POST["itemail"];
            // echo $_POST['itpassword'];
            // exit;   
            $itpassword = password_hash($_POST['itpassword'], PASSWORD_DEFAULT); //sha1(mysqli_real_escape_string($conn, $_POST['itpassword']));
            //check email for right format in PHP dahil meron na sa AJAX optional lang ito
            // $itemail = test_input($_POST["itemail"]);
            // if (!filter_var($itemail, FILTER_VALIDATE_EMAIL)) {
            //     echo "Invalid email format";
            //     exit;
            // }

                 

            $cbactiveadmin = $_POST['cbactiveadmin'];
            // echo $cbclosedrequest;
            // exit;    
            if ($_POST['admin_action'] == 'add') {
                //OOP Prepared Statement
                $stmt = $conn->prepare("SELECT adminname from adminfiletbl where adminname = ?");
                $stmt->bind_param("s", $itadminname);
                $stmt->execute();
                $result = $stmt->get_result();
                $total_records = $result->num_rows;
                $stmt->close();

                if ($total_records > 0) {
                    //echo "<script>alert('$c_email is already exist!')</script>";
                    echo "Admin Name [" . $itadminname . "] already exist!";
                    exit;
                } else {
                    //OOP Prepared Statement
                    try {
                    $stmt = $conn->prepare("INSERT INTO adminfiletbl (adminname,email,`password`,active) VALUES (?,?,?,?)");
                    $stmt->bind_param("ssss", $itadminname,$itemail,$itpassword,$cbactiveadmin);
                    $stmt->execute();
                    $stmt->close();
                    } catch(Exception $e) {
                        echo("Error description: " . $conn -> error);
                        exit;
                    }
                    echo "Successfuly Added!";
                }
            } else { //Update
                // OOP Prepared Statement
                $stmt = $conn->prepare("SELECT adminid,adminname from adminfiletbl where adminname = ?");
                $stmt->bind_param("s", $itadminname);
                $stmt->execute();
                $result = $stmt->get_result();
                $row = $result->fetch_assoc();
                $total_records = $result->num_rows;
                $stmt->close();

                if (empty($row['adminid'])) { //if the $row['adminid'] is empty meaning the adminname is not existing and it is ok to update
                    goto updateadmin;
                }

                if ($itadminid != $row['adminid']) {
                    //echo "<script>alert('$c_email is already exist!')</script>";
                    echo "Admin Name [" . $itadminname . "] already exist!";
                    exit;
                } else {
updateadmin:
                    // For password that has not change
                    if (empty($_POST['itpassword'])) { 
                        $stmt = $conn->prepare("update adminfiletbl set adminname = ?,email = ?,active = ? where adminid = ?");
                        $stmt->bind_param("ssss", $itadminname,$itemail,$cbactiveadmin,$itadminid);
                    } else {
                        $stmt = $conn->prepare("update adminfiletbl set adminname = ?,email = ?,`password` = ?,active = ? where adminid = ?");
                        $stmt->bind_param("sssss", $itadminname,$itemail,$itpassword,$cbactiveadmin,$itadminid);
                    }
                    $stmt->execute();
                    $stmt->close();
                    echo "Successfuly Updated!";
                }
            }
        }
        if(isset($_POST['admin_delete'])){
            $adminid = $_POST['adminid'];
            // OOP Prepared Statement
            $stmt = $conn->prepare("Delete from adminfiletbl where adminid = ?");
            $stmt->bind_param("s",$adminid);
            $stmt->execute();
            $stmt->close();
            echo "Successfuly Deleted!";
        }
        
        if(isset($_POST['saveemployeeimage'])) {
            $response = ["success" => false, "message" => "", "filename" => ""];
            
            if (!isset($_FILES['image'])) {
                $response['message'] = "No file uploaded.";
            } else {
                $file = $_FILES['image'];
                $allowedTypes = ['image/jpeg', 'image/png'];
                $maxSize = 5 * 1024 * 1024; // 5MB

                if (!in_array($file['type'], $allowedTypes)) {
                    $response['message'] = "Only JPG and PNG files are allowed.";
                } elseif ($file['size'] > $maxSize) {
                    $response['message'] = "File size must be 5MB or less.";
                } elseif ($file['error'] !== UPLOAD_ERR_OK) {
                    $response['message'] = "Upload error code: " . $file['error'];
                } else {
                    // Optional: move to uploads folder
                    $uploadfiles = "uploadfiles/";
                    if (!is_dir($uploadfiles)) {
                        mkdir($uploadfiles, 0755, true);
                    }

                    // 🔍 Step 1: Get current image path from DB
                    $stmt = $conn->prepare("SELECT imagesrc FROM employeefiletbl WHERE idno = ?");
                    $stmt->bind_param("s", $_SESSION['idno']);
                    $stmt->execute();
                    $stmt->bind_result($oldImagePath);
                    $stmt->fetch();
                    $stmt->close();

                    // 🗑 Step 2: Delete old image if it exists
                    if (!empty($oldImagePath) && file_exists($oldImagePath)) {
                        unlink($oldImagePath);
                    }

                    // 📥 Step 3: Save new image
                    $filename = basename($file['name']);
                    $targetPath = $uploadfiles . $_SESSION['idno'] . '-' . $filename;

                    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                        $response['success'] = true;
                        $response['message'] = "File uploaded.";
                        $response['filename'] = $targetPath;
                        //Update or Change the password
                        $stmt = $conn->prepare("update employeefiletbl set `imagesrc` = ? where idno = ?");
                        $stmt->bind_param("ss", $targetPath,$_SESSION['idno']);
                        $stmt->execute();
                        $stmt->close();
                    } else {
                        $response['message'] = "Failed to move uploaded file.";
                    }
                }
            }
            
            header('Content-Type: application/json');
            echo json_encode($response);
        }

        if (isset($_POST['update_aprrover'])) {
            header('Content-Type: application/json');   
            
            $response = ["success" => false, "message" => ""];

            $approver1 = $_POST['approver1']; // or any source
            if (!filter_var($approver1, FILTER_VALIDATE_EMAIL)) {
                // echo "Invalid email format";
                $response['message'] = "Invalid email format " . $approver1;
                echo json_encode($response);
                exit;
            }

            $approver2 = $_POST['approver2']; // or any source
            if (!filter_var($approver2, FILTER_VALIDATE_EMAIL)) {
                // echo "Invalid email format";
                $response['message'] = "Invalid email format " . $approver2;
                echo json_encode($response);
                exit;
            } 

            $stmt = $conn->prepare("Update employeefiletbl set approver1 = ?, approver2 = ? WHERE email = ?");
            $stmt->bind_param("sss", $approver1,$approver2,$_SESSION['email']);
            $stmt->execute();
            $stmt->close();

            $response['success'] = true;
            $response['message'] = "Successfully Update!";
            echo json_encode($response);
            exit;
        }

        // User Change Password
        if (isset($_POST['user_change_password'])) {
            $ituserid = mysqli_real_escape_string($conn, $_SESSION['email']);
            $itpassword = $_POST['itpassword'];

            // VALIDATE current password
            if ($_POST['user_change_password'] === 'validate') {
                $stmt = $conn->prepare("SELECT password FROM employeefiletbl WHERE email = ?");
                $stmt->bind_param("s", $ituserid);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows > 0) {
                    $row = $result->fetch_assoc();
                    if (password_verify($itpassword, $row['password'])) {
                        echo "1"; // Valid password
                    } else {
                        echo "0"; // Invalid password
                    }
                } else {
                    echo "0"; // User not found
                }

                $stmt->close();
            }

            // UPDATE password
            if ($_POST['user_change_password'] === 'update') {
                $newHashedPassword = password_hash($itpassword, PASSWORD_DEFAULT);

                $stmt = $conn->prepare("UPDATE employeefiletbl SET password = ? WHERE email = ?");
                $stmt->bind_param("ss", $newHashedPassword, $ituserid);
                $stmt->execute();
                $stmt->close();

                echo "✅ Successfully Updated!";
            }
        }
        
        if(isset($_POST['validate_user_log'])){
            //Check the system type log if exist
            $system_type = $_POST['system_type'];
            $data = array();
            $stmt = $conn->prepare("SELECT loginfiletbl.* from loginfiletbl 
                                    where systemtype = ?");
            $stmt->bind_param("s", $system_type);
            $stmt->execute();
            $result = $stmt->get_result();
            $total_records = $result->num_rows;
            $row = $result->fetch_assoc();
            if ($total_records > 0) {
                foreach ($result as $row) { //Loop the result
                    $data[] = $row;
                }
                echo json_encode($data);
                exit;
            }
            echo "0";
        }
        
        if(isset($_POST['close_shift'])){
            $system_type= $_POST['systemtype'];
            
            //Insert into loginfiletbl
            $stmt2 = $conn->prepare("SELECT loginfiletbl.* from loginfiletbl where systemtype = ?"); 
            $stmt2->bind_param("s", $system_type);
            $stmt2->execute();
            $result = $stmt2->get_result();
            while ($row = $result->fetch_assoc()) {
                $user_login_username = $row['username'];
                $user_shift = $row['shift'];
                $datelogin = $row['datelogin'];
                date_default_timezone_set("asia/manila");
                $date_time = new DateTime();
                $date_time2 = date_format($date_time, 'y-m-d H:i:s');
                $closedshiftdatetime = $date_time2;
                
                $stmt1 = $conn->prepare("INSERT INTO logintranfiletbl (systemtype,username,shift,datelogin,closedshiftdatetime) VALUES (?,?,?,?,?)");
                $stmt1->bind_param("sssss", $system_type,$user_login_username,$user_shift,$datelogin,$closedshiftdatetime);
                $stmt1->execute();
                $stmt1->close();
            }   
            $stmt2->close();

            //Delete the system type in loginfiletbl
            $stmt = $conn->prepare("Delete from loginfiletbl 
                                    where systemtype = ?");
            $stmt->bind_param("s", $system_type);
            $stmt->execute();
            $stmt->close();
            if ($system_type == 'fo') {
                session_destroy();
            }
            echo "Successfuly Closed the Shift!";
        }
        
        if (isset($_POST['employee_allowvl'])) {
            $idno = $_POST['idno'];
            
            $stmt = $conn->prepare("UPDATE employeefiletbl SET allowvlfile  = NOW() WHERE idno = ?");
            $stmt->bind_param("s", $idno);
            $stmt->execute();
            $stmt->close();

            echo "Successfully Updated Allow VL!";
        }

        //Check Shift If Closed
        if(isset($_POST['Check_Shift_If_Closed'])) {
            $system_type = $_SESSION['system_type'];
            $user_shift =  $_SESSION['shift'];
            $datelogin =  $_SESSION['datelogin'];
            
            if ($system_type == "hk") {
                $system_typename = "Housekeeping";
            } else {
                $system_typename = "Front Office";
            }
            $shiftname = str_replace("s","Shift ",$user_shift);
            try {
                $stmt = $conn->prepare("SELECT shift from logintranfiletbl 
                                        where systemtype = ? and shift = ? and datelogin = ?");
                $stmt->bind_param("sss", $system_type,$user_shift,$datelogin);
                $stmt->execute();
                $result = $stmt->get_result();
                $total_records = $result->num_rows;
                $row = $result->fetch_assoc();
                if ($total_records > 0) {
                    echo $system_typename . ", " . $shiftname . " and date log in " . $datelogin . " already closed!";
                    session_destroy();
                    exit;
                } else {
                    echo "";
                    // echo "SELECT shift from logintranfiletbl where systemtype = ".$system_type." and shift = ".$user_shift." and datelogin = ".$datelogin."";
                }
            } catch(Exception $e) {
                echo("Error description: " . $conn -> error);
                exit;
            }
            $stmt->close();
        }

        // // User Log In
        // if(isset($_POST['user_login'])){
        //     $user_login_username = mysqli_real_escape_string($conn, $_POST['user_login_username']);
        //     $user_login_password = sha1(mysqli_real_escape_string($conn, $_POST['user_login_password']));
        //     // $system_type = $_POST['system_type'];
        //     // $user_shift =  $_POST['user_shift'];
        //     // $datelogin =  $_POST['datelogin'];
        //     // $loginexist=  $_POST['loginexist'];
        //     // // echo $system_type.'-'.$user_login_username.'-'.$user_shift.'-'.$datelogin;
        //     // // exit;
        //     // //Check Shift if already used
        //     // $stmt = $conn->prepare("SELECT shift from logintranfiletbl 
        //     //                         where systemtype = ? and shift = ? and datelogin = ?");
        //     // $stmt->bind_param("sss", $system_type,$user_shift,$datelogin);
        //     // $stmt->execute();
        //     // $result = $stmt->get_result();
        //     // $total_records = $result->num_rows;
        //     // $row = $result->fetch_assoc();
        //     // if ($total_records > 0) {
        //     //     echo "3";
        //     //     exit;
        //     // }
        //     // $stmt->close();

        //     //Check the username
        //     $stmt = $conn->prepare("SELECT employeefiletbl.*,departmentfiletbl.deptname from employeefiletbl 
        //                             Left Outer Join departmentfiletbl on employeefiletbl.deptcode = departmentfiletbl.deptcode
        //                             where email = ?");
        //     $stmt->bind_param("s", $user_login_username);
        //     $stmt->execute();
        //     $result = $stmt->get_result();
        //     $total_records = $result->num_rows;
        //     $row = $result->fetch_assoc();
        //     if ($total_records > 0) {
        //         //Check Password
        //         if ($row['active'] == 0) {
        //             echo "2";
        //         } else {
        //             if ($row['password']==$user_login_password) {
        //                 // if ($row['deptid'] == 2 && $system_type == "hk") {
        //                 //     echo "The user name is not department of Housekeeping!";
        //                 //     exit;
        //                 // } else if ($row['deptid'] == 3 && $system_type == "fo") {
        //                 //     echo "The user name is not department of Front Office!";
        //                 //     exit;
        //                 // }

        //                 echo "1"; //Valid

        //                 $_SESSION['idno']=$row['idno'];
        //                 //$_SESSION['username']=$user_login_username;
        //                 $_SESSION['email']=$row['email'];
        //                 $_SESSION['deptcode']=$row['deptcode'];
        //                 $_SESSION['divcode']=$row['divcode'];
        //                 $_SESSION['admin_or_user']='user';
        //                 $_SESSION['last_login_timestamp'] = time();
        //                 // $_SESSION['system_type']=$system_type;
        //                 // $_SESSION['shift']=$user_shift;
        //                 // $_SESSION['datelogin']=$datelogin;
                        
        //                 // if ($row['closedrequest'] == 1) {
        //                 //     $_SESSION['closedrequest'] = 1;    
        //                 // } else {
        //                 //     $_SESSION['closedrequest'] = 0;
        //                 // }
                        
        //                 // if ($loginexist == "NO") {
        //                 //     //echo $system_type." ".$user_login_username." ".$user_shift." ".$datelogin;

        //                 //     $stmt1 = $conn->prepare("INSERT INTO loginfiletbl (systemtype,username,shift,datelogin) VALUES (?,?,?,?)");
        //                 //     $stmt1->bind_param("ssss", $system_type,$user_login_username,$user_shift,$datelogin);
        //                 //     $stmt1->execute();
        //                 //     $stmt1->close();
        //                 // }
                        
        //                 // $sql = "SELECT utoken FROM timerforupdatetbl";
        //                 // if($result = mysqli_query($conn, $sql)){
        //                 //     if(mysqli_num_rows($result) > 0){
        //                 //     $row = mysqli_fetch_array($result) ;
        //                 //         $_SESSION['utoken'] = $row['utoken'];
        //                 //     } else {
        //                 //         $_SESSION['utoken'] = ""; //Walang record
        //                 //     }
        //                 // }
        //             } else {
        //                 echo "0"; //Invalid
        //             }
        //         }
        //     } 
        //     else {
        //         // $_SESSION['userid']="jiriel";
        //         // $_SESSION['username']="jiriel";
        //         // $_SESSION['useremail']="jiriel";
        //         // $_SESSION['deptid']="jiriel";
        //         // $_SESSION['deptname']="jiriel";
        //         // $_SESSION['admin_or_user']='user';
        //         // $_SESSION['last_login_timestamp'] = time();
        //         // echo "1";
        //         echo "0"; //Invalid
        //     }
        //     $stmt->close();
        // }

        // // Admin Log In
        // if(isset($_POST['admin_login'])){
        //     $admin_login_username = mysqli_real_escape_string($conn, $_POST['admin_login_username']);
        //     $admin_login_password = sha1(mysqli_real_escape_string($conn, $_POST['admin_login_password']));
        //     // echo $admin_login_username;
        //     // echo "-";
        //     // echo $admin_login_password;
        //     // exit;
        //     //Check the username
        //     $stmt = $conn->prepare("SELECT * from adminfiletbl where adminname = ?");
        //     $stmt->bind_param("s", $admin_login_username);
        //     $stmt->execute();
        //     $result = $stmt->get_result();
        //     $total_records = $result->num_rows;
        //     $row = $result->fetch_assoc();
        //     if ($total_records > 0) {
        //         //Check Password
        //         if ($row['active'] == 0) {
        //             echo "2";
        //         } else {
        //             if ($row['password']==$admin_login_password) {
        //                 echo "1"; //Valid

        //                 $_SESSION['adminid']=$row['adminid'];
        //                 $_SESSION['adminname']=$admin_login_username;
        //                 $_SESSION['adminemail']=$row['email'];
        //                 $_SESSION['admin_or_user']='admin';
        //                 $_SESSION['last_login_timestamp'] = time();
        //                 $_SESSION['closedrequest'] = 1;
        //                 $_SESSION['system_type']='';
        //                 $_SESSION['shift']='';
        //                 $_SESSION['datelogin']='';
                        

        //                 $sql = "SELECT utoken FROM timerforupdatetbl";
        //                 if($result = mysqli_query($conn, $sql)){
        //                     if(mysqli_num_rows($result) > 0){
        //                     $row = mysqli_fetch_array($result) ;
        //                         $_SESSION['utoken'] = $row['utoken'];
        //                     } else {
        //                         $_SESSION['utoken'] = ""; //Walang record
        //                     }
        //                 }

        //             } else {
        //                 echo "0"; //Invalid
        //             }
        //         }
        //     } else {
        //         echo "0";
        //     }
        //     $stmt->close();
        // }

        //Leave Request
        if (isset($_POST['leave_action'])) {
            date_default_timezone_set('Asia/Manila');
            //Test na yung from date at to date ay hindi dapat magkaiba ang year
            if (date('Y', strtotime($_POST['fromDate'])) != date('Y', strtotime($_POST['toDate']))) {
                echo "1❌ From Date and To Date must be in the same year!";
                exit;
            }

            // =========================================
            // FILE UPLOAD SETTINGS
            // =========================================
            $savedFiles = [];

            if (!empty($_FILES['leaveFiles']['name'][0])) {
                $maxFiles = 3;
                $maxSize = 2 * 1024 * 1024; // 2MB
                $allowedTypes = ['image/jpeg','image/png','image/webp','image/heic'];
            
                $fileCount = count($_FILES['leaveFiles']['name']);

                // =========================================
                // CHECK MAX FILES
                // =========================================
                if ($fileCount > $maxFiles) {
                    echo "1❌ Maximum of 3 files only.";
                    exit;
                }

                // =========================================
                // VALIDATE FILES FIRST
            // ✅ LOOP VALIDATION FIRST (NO UPLOAD YET)
                // =========================================
                for ($i = 0; $i < $fileCount; $i++) {
                    $tmpName = $_FILES['leaveFiles']['tmp_name'][$i];
                    $fileSize = $_FILES['leaveFiles']['size'][$i];

                    // Check if upload has error
                    if ($_FILES['leaveFiles']['error'][$i] !== UPLOAD_ERR_OK) {
                        echo "1❌ Error uploading file.";
                        exit;
                    }

                    // Check file size
                    if ($fileSize > $maxSize) {
                        echo "1❌ Each file must be less than 2MB.";
                        exit;
                    }

                    // Detect real MIME type (ANTI FAKE FILE)
                    $fileType = mime_content_type($tmpName);
                    if (!in_array($fileType, $allowedTypes)) {
                        echo "1❌ Invalid file type detected.";
                        exit;
                    }
                
                    // Double check image
                    if (!getimagesize($tmpName)) {
                        echo "1❌ File is not a valid image.";
                        exit;
                    }
                }

                // =========================================
                // CREATE UPLOAD DIRECTORY
                // =========================================
                $uploadDir = "uploads/leavefiles/";
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                // =========================================
                // UPLOAD FILES
                // =========================================
                for ($i = 0; $i < $fileCount; $i++) {

                    $tmpName = $_FILES['leaveFiles']['tmp_name'][$i];
                    $originalName = $_FILES['leaveFiles']['name'][$i];

                    // Get file extension
                    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

                    // =========================================
                    // GENERATE UNIQUE FILE NAME
                    // =========================================
                    // Example:
                    // LEAVE_20260518_120522_123456789.jpg
                    // =========================================
                // Clean ID No
                $cleanIdNo = preg_replace('/[^A-Za-z0-9\-]/', '', $_SESSION['idno']);

                // Generate unique filename
                $uniqueFileName =
                    "LEAVE_" .
                    $cleanIdNo . "_" .
                    date('Ymd_His') . "_" .
                    bin2hex(random_bytes(5)) . "." .
                    $extension;

                    $destination = $uploadDir . $uniqueFileName;

                    // Move uploaded file
                    if (move_uploaded_file($tmpName, $destination)) {

                        // Save filename to array
                        $savedFiles[] = $uniqueFileName;

                    } else {

                        echo "1❌ Failed to upload file.";
                        exit;
                    }
                }
            }


            // =========================================
            // OTHER VARIABLES
            // =========================================
            $idno = $_SESSION['idno'];
            $tranDate = date('Y-m-d');
            $leaveCode = str_replace('_', '/', $_POST['selectLeaveCode']);
            $leaveName = $_POST['selectLeaveName'];
            $deptCode = $_SESSION['deptcode'];
            $divCode = $_SESSION['divcode'];

            // =========================================
            // FROM DATE
            // =========================================
            $fromDate = $_POST['fromDate'];
            if (strtotime($fromDate) === false) {
                echo "1❌ ERROR: From Date is invalid!";
                exit;
            }
            $fromDate = date('Y-m-d', strtotime($fromDate));
            $yeardeduct = date('Y', strtotime($_POST['fromDate']));

            // =========================================
            // TO DATE
            // =========================================
            $toDate = $_POST['toDate'];
            if (strtotime($toDate) === false) {
                echo "1❌ ERROR: To Date is invalid!";
                exit;
            }
            $toDate = date('Y-m-d', strtotime($toDate));
            
            // =========================================
            // LEAVE DETAILS
            // =========================================
            $noOfDays = $_POST['totalDays'];
            $noOfHours = $_POST['totalHours'];
            $totalNoOfHours = $_POST['totalHours'];

            $leaveRequestFor = strtoupper($_POST['leaveRequestFor']);
            if ($leaveRequestFor === 'HALF DAY') {
                $toDate = $fromDate;
                $noOfDays = .5;
            } elseif ($leaveRequestFor === 'DAYS') {
            } else {
                echo "1❌ Unknown Leave Request For!".$leaveRequestFor;
                exit;
            }

            // =========================================
            // VERIFY NUMBER OF DAYS
            // =========================================
            $start = new DateTime($fromDate);
            $end = new DateTime($toDate);
            $interval = $start->diff($end);
            $varNoOfDays = $interval->days + 1;
            if ($leaveRequestFor === 'DAYS') {
                if ((int)$varNoOfDays !== (int)$noOfDays) {
                    echo "1The From and To Date is not equal to No. of Day(s)!";
                    exit;
                }
            }

            // =========================================
            // DATE RULES //not earlier than 1 month and not later than 5 days
            // =========================================
            $today = new DateTime();
            
            //dito na yung sa CL and LOA
            if (trim($leaveCode) === "CL") {
                // //1. the same date pwedi wag lang back date
                // if ($start < $today) {
                //     echo "1❌ ERROR: Start date cannot be earlier than today.";
                //     exit;
                // } elseif ($end < $today) {
                //     echo "1❌ ERROR: End date cannot be earlier than today.";
                //     exit;
                // } elseif ($end < $start) {
                //     echo "1❌ ERROR: End date cannot be earlier than start date.";
                //     exit;
                // } 

                //Para sa CL lang ito
                if (trim($leaveCode) === "CL") {
                    // //2. dapat hindi pwedi monday and friday macheck dapat ang fromdate at todate kung monday or friday
                    // for ($date = $start; $date <= $end; $date->modify('+1 day')) {
                    //     if (in_array($date->format('N'), [1, 5])) {
                    //         echo "1❌ ERROR: Leave dates cannot include Monday or Friday.";
                    //         exit;
                    //     }
                    // }

                    //3. test yung leave na CL sa leavefilingfiletbl dapat hangga tatlo within the current year meron noofdays yung bagong ifile plus yung existing na cl_count dapat hindi lalagpas sa 3 at dapat hindi mag overlap sa existing CL leave filings
                    $stmt = $conn->prepare("SELECT sum(noofdays) as cl_count FROM leavefilingfiletbl
                                            WHERE idno = ?
                                            AND leavecode = 'CL'
                                            AND YEAR(fromdate) = YEAR(CURDATE())");
                    $stmt->bind_param("s", $idno);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $row = $result->fetch_assoc();
                    $cl_count = $row['cl_count'];
                    if (($cl_count + $varNoOfDays) > 3) {    
                        echo "1❌ ERROR: You have already filed ".$cl_count." CL leave days for this year.";
                        exit;
                    }
                }
            } else if (trim($leaveCode) === "EL/BL") {  
                //EL leave can be filed anytime, no restrictions
            } else { //|| trim($leaveCode) === "LOA" include dito
                $today = new DateTime('today'); // normalize to midnight
                $start = new DateTime($fromDate);
                $start->setTime(0,0,0);
                $end   = new DateTime($toDate);
                $end->setTime(0,0,0);    
            
                
                $allowvlfile = null;
                $minStartDate = (clone $today)->modify('+5 days');
                $maxEndDate = (clone $today)->modify('+1 month');
                if ($start < $minStartDate) {
                    if (trim($leaveCode) !== "SL") {
                        //Pwedi i allow ito pero dapat i check muna sa employeefiletbl na yung allowvlfile ay may value na timestamp kapag wala hindi siya pweding magfile ng leave na mas maaga sa 5 days from today
                        $stmt = $conn->prepare("SELECT allowvlfile FROM employeefiletbl WHERE idno = ?");
                        $stmt->bind_param("s", $idno);
                        $stmt->execute();
                        $result = $stmt->get_result(); 
                        if ($result->num_rows > 0) {
                            $row = $result->fetch_assoc();
                            $allowvlfile = $row['allowvlfile'];
                            if (empty($allowvlfile)) {
                                echo "1❌ ERROR: Start date must be at least 5 days from today (on or after " . $minStartDate->format('Y-m-d') . ").";//. $start->format('Y-m-d');
                                exit;
                            }

                            //Bago magproceed dapat i check muna kung time stamp ng allowvlfile na dapat
                            //hindi lampas ng 24 hours mula ng binigyan siya ng access
                            //kung lumampas siya hindi allow pero kung wala pang 24 hours pweding magfile ng leave kahit na mas maaga sa 5 days from today
                            $allowvlfileDateTime = new DateTime($allowvlfile);
                            $now = new DateTime();
                            $interval = $now->diff($allowvlfileDateTime);
                            if ($interval->days >= 1) {
                                //Reset to null yung allowvlfile sa employeefiletbl kasi expired na yung access niya
                                $stmt = $conn->prepare("UPDATE employeefiletbl SET allowvlfile = NULL WHERE idno = ?");
                                $stmt->bind_param("s", $idno);    
                                $stmt->execute();

                                echo "1❌ ERROR: The access to file leave earlier than 5 days from today has expired. Please request again contact your HR department.";
                                exit;
                            }

                            $allowvlfile = 1; //May value na yung allowvlfile sa employeefiletbl kaya pweding magfile ng leave na mas maaga sa 5 days from today
                        } else {
                            echo "1❌ ERROR: Start date must be at least 5 days from today (on or after " . $minStartDate->format('Y-m-d') . ").";//. $start->format('Y-m-d');
                            exit;
                        }
                        //echo "1❌ ERROR: Start date must be at least 5 days from today (on or after " . $minStartDate->format('Y-m-d') . ").". $start->format('Y-m-d');
                        //        exit;
                    }
                } elseif ($end > $maxEndDate) {
                    if (trim($leaveCode) !== "SL") {
                        echo "1❌ ERROR: End date must not be later than " . $maxEndDate->format('Y-m-d') . ".";
                                exit;
                    }
                } elseif ($end < $start) {
                    echo "1❌ ERROR: End date cannot be earlier than start date.";
                    exit;
                } else {
                    //echo "2✅ Date range is valid!";
                }

                // $today = new DateTime('today'); // normalize to midnight
                // $start = new DateTime($fromDate);
                // $start->setTime(0,0,0);
                // $end   = new DateTime($toDate);
                // $end->setTime(0,0,0);

                // $minStartDate = (clone $today)->modify('+5 days');
                // if ($start < $minStartDate) {
                //     if (trim($leaveCode) !== "SL") {
                //         echo "❌ ERROR: asdfStart date must be at least 5 days from today (on or after " 
                //             . $minStartDate->format('Y-m-d') . "). Provided: " 
                //             . $start->format('Y-m-d');
                //         exit;
                //     }
                // }

                // if ($end < $start) {
                //     echo "❌ ERROR: End date cannot be earlier than start date.";
                //     exit;
                // }

            } //ito yung CL if statements


            // =========================================
            // OVERLAP CHECK //Test kung mag overlap ang pinili na date (Regardless kung ano mang Leave Code basta meron ng nakafile na date na ganun hindi na pweding magfile
            // =========================================
            // $sql = "SELECT * FROM leavefilingfiletbl
            //         WHERE idno = ?
            //         AND (? <= todate AND ? >= fromdate) ";
            // $stmt = $conn->prepare($sql);
            // $stmt->bind_param("sss", $idno, $fromDate, $toDate);
            // $stmt->execute();
            // $result = $stmt->get_result();
            // if ($result->num_rows > 0) {
            //     echo "1❌ ERROR: Your requested leave dates overlap with an existing leave filing.";
            //     exit;
            // }
            // $stmt->close();
            
            //hindi na dapat masama ang 2-disapproved at 3-cancelled sa overlap check kasi pwede naman magfile ng leave kahit na meron ng disapproved or cancelled na leave filing
            $sql = "SELECT * FROM leavefilingfiletbl
                    WHERE idno = ?
                    AND (? <= todate AND ? >= fromdate)
                    AND approver1status NOT IN (2,3)
                    AND approver2status NOT IN (2,3)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sss", $idno, $fromDate, $toDate);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                echo "1❌ ERROR: Your requested leave dates overlap with an existing leave filing.";
                exit;
            }
            $stmt->close();


            // =========================================
            // LEAVE BALANCE
            // =========================================
            $leaveAllowance = $_POST['leaveAllowance'];
            $leaveUsed = $_POST['leaveUsed'];
            $leaveBalance = $_POST['leaveBalance'];

            //hindi na kailangan ng balance check sa LOA kasi hindi naman siya bawas sa balance, pero gagawan lang siya ng record para makita sa history ng leave filings
            if (trim($leaveCode) === "LOA") {
                //Do nothing
            } else {
                $stmt = $conn->prepare("
                    SELECT *
                    FROM leaveallowancefiletbl
                    WHERE idno = ?
                    AND leavecode = ?
                ");
                $stmt->bind_param("ss", $idno, $leaveCode);
                $stmt->execute();
                $result = $stmt->get_result();
                $row = $result->fetch_assoc();
                if ($result->num_rows > 0) {
                    if ((float)$row['balance'] < (float)$noOfDays) {
                        echo "1Insufficient leave balance!";
                        exit;
                    }
                    $latestUsed = ((float)$row['use'] + (float)$noOfDays);
                    $latestBalance = (float)$row['allowance'] - $latestUsed;
                }
                $stmt->close();
            }
            
            // =========================================
            // REMARKS
            // =========================================
            $remark = trim($_POST['remarks']);
            if (empty($remark)) {
                echo "1Please fill up remarks!";
                exit;
            }

            // =========================================
            // ATTACHMENTS STRING
            // =========================================
            $attachments = implode(",", $savedFiles);



            //         echo "1❌ ERROR: ID No: $idno<br>" .
            //  "Transaction Date: $tranDate<br>" .
            //  "Leave Code: $leaveCode<br>" .
            //  "Department Code: $deptCode<br>" .
            //  "Division Code: $divCode<br>" .
            //  "No. of Days: $noOfDays<br>" .
            //  "No. of Hours: $noOfHours<br>" .
            //  "Total No. of Hours: $totalNoOfHours<br>" .
            //  "Year Deduct: $yeardeduct<br>" .
            //  "From Date: $fromDate<br>" .
            //  "To Date: $toDate<br>" .
            //  "Remark: $remark<br>" .
            //  "Leave Allowance: $leaveAllowance<br>" .
            //  "Leave Used: $leaveUsed<br>" .
            //  "Leave Balance: $leaveBalance<br>" .
            //  "Leave Request For: $leaveRequestFor<br>" .
            //  "Attachments: $attachments<br>";
            //  exit;


            // =========================================
            // INSERT LEAVE
            // =========================================
            try {
                $stmt = $conn->prepare("INSERT INTO leavefilingfiletbl (
                        idno,trandate,leavecode,deptcode,divcode,
                        noofdays,noofhours,totalnoofhours,yeardeduct,
                fromdate,todate,remark,allowance,`use`,balance,leaverequestfor,
                        leaveattachments
                    )
                    VALUES (
                        ?,?,?,?,?,?,
                        ?,?,?,?,?,?,
                        ?,?,?,?,?
                    )
                ");
            
                $stmt->bind_param(
                    "sssssssssssssssss",$idno,$tranDate,$leaveCode,$deptCode,
                    $divCode,$noOfDays,$noOfHours,$totalNoOfHours,$yeardeduct,
                    $fromDate,$toDate,$remark,$leaveAllowance,$leaveUsed,$leaveBalance,
                    $leaveRequestFor,$attachments
                );
                $stmt->execute();
                $leave_id = $conn->insert_id;
                $stmt->close();
                
                // =========================================
                // UPDATE BALANCE
                // =========================================
                //No updating balance for LOA kasi hindi naman siya bawas sa balance, pero gagawan lang siya ng record para makita sa history ng leave filings
                if (trim($leaveCode) === "LOA") {
                    //Do nothing
                } else {
                    $stmt = $conn->prepare("
                        UPDATE leaveallowancefiletbl SET `use` = ?,balance = ?
                        WHERE idno = ? AND leavecode = ?");
                    $stmt->bind_param("ssss",$latestUsed,$latestBalance,$idno,$leaveCode);
                    $stmt->execute();
                    $stmt->close();
                }

                //Kung $allowvlfile ay may value na timestamp sa employeefiletbl dapat i-update yung allowvlfile sa employeefiletbl to NULL para hindi na siya makapagfile ng leave na mas maaga sa 5 days from today
                if (!empty($allowvlfile)) {
                    $stmt = $conn->prepare("UPDATE employeefiletbl SET allowvlfile = NULL WHERE idno = ?");
                    $stmt->bind_param("s", $idno);
                    $stmt->execute();
                    $stmt->close();
                }

                //echo "2✅ Leave request successfully submitted!";
                $sql = "SELECT e.*, 
                                a1.employeename AS approver1_name,
                                a2.employeename AS approver2_name
                    FROM employeefiletbl e
                    LEFT JOIN employeefiletbl a1 ON a1.email = e.approver1
                    LEFT JOIN employeefiletbl a2 ON a2.email = e.approver2
                    WHERE e.idno = ?";

                $stmt = $conn->prepare($sql);
                $stmt->bind_param("s", $idno);
                $stmt->execute();
                $result = $stmt->get_result();
                $total_records = $result->num_rows;
                $row = $result->fetch_assoc();
                $stmt->close();

                $emplEmail = $row['email'];
                $employeeName = $row['employeename'];
                $approver1 = $row['approver1'];
                $approver1Name = $row['approver1_name'];
                $approver2 = $row['approver2'];
                $approver2Name = $row['approver2_name'];
                $emailType = 'LeaveRequest';
                
                
                include('leavesendmail.php');
                echo "Leave request has been successfully submitted!";
            } catch (Exception $e) {
                echo "1❌ ERROR: " . $e->getMessage();
                exit;
            }
        }
        // if (isset($_POST['leave_action'])) {
        //     date_default_timezone_set('Asia/Manila'); // Set timezone to Philippines
            

        //     if (!empty($_FILES['leaveFiles']['name'][0])) {
        //         $maxFiles = 3;
        //         $maxSize = 2 * 1024 * 1024; // 2MB
        //         $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/heic'];

        //         $fileCount = count($_FILES['leaveFiles']['name']);

        //         // ✅ Check number of files
        //         if ($fileCount > $maxFiles) {
        //             echo "1❌ Maximum of 3 files only.";
        //             exit;
        //         }

        //         // ✅ LOOP VALIDATION FIRST (NO UPLOAD YET)
        //         for ($i = 0; $i < $fileCount; $i++) {

        //             $tmpName = $_FILES['leaveFiles']['tmp_name'][$i];
        //             $fileSize = $_FILES['leaveFiles']['size'][$i];

        //             // ✅ Check file size
        //             if ($fileSize > $maxSize) {
        //                 echo "1❌ Each file must be less than 2MB.";
        //                 exit;
        //             }

        //             // ✅ Check MIME TYPE (ANTI FAKE FILE)
        //             $fileType = mime_content_type($tmpName);

        //             if (!in_array($fileType, $allowedTypes)) {
        //                 echo "1❌ Invalid file type detected.";
        //                 exit;
        //             }

        //             // ✅ Optional: double check if image
        //             if (!getimagesize($tmpName)) {
        //                 echo "1❌ File is not a valid image.";
        //                 exit;
        //             }
        //         }

        //         // ✅ If lahat pasado → saka upload
        //     }



        //     $idno = $_SESSION['idno'];
        //     $tranDate = date('Y-m-d'); // Outputs current date and time
        //     $leaveCode = str_replace('_', '/', $_POST['selectLeaveCode']);
        //     $leaveName = $_POST['selectLeaveName'];
        //     $deptCode = $_SESSION['deptcode'];
        //     $divCode = $_SESSION['divcode'];
        //     $fromDate = $_POST['fromDate'];//date('Y-m-d',strtotime($_POST['fromDate']));
        //     if (strtotime($fromDate) === false) {
        //         echo "1❌ ERROR: From Date is invalid!";
        //         exit;
        //     }
        //     $fromDate = date('Y-m-d',strtotime($_POST['fromDate']));
        //     $yeardeduct = date('Y', strtotime($_POST['fromDate']));

        //     $toDate = $_POST['toDate'];
        //     if (strtotime($toDate) === false) {
        //         echo "1❌ ERROR: To Date is invalid!";
        //         exit;
        //     }
        //     $toDate = date('Y-m-d',strtotime($_POST['toDate']));
            
        //     $noOfDays = $_POST['totalDays'];
        //     $noOfHours = $_POST['totalHours'];
        //     $totalNoOfHours = $_POST['totalHours'];

        //     $leaveRequestFor = strtoupper($_POST['leaveRequestFor']);
        //     if ($leaveRequestFor === 'HALF DAY') {
        //         $toDate = $fromDate; //Because per base 
        //         $noOfDays = .5;
        //     } elseif ($leaveRequestFor === 'DAYS') {
        //     } else {
        //         echo "1❌ Unknown Leave Request For!".$leaveRequestFor;
        //         exit;
        //     }
            
        //     //Verify the no. of days
        //     $start = new DateTime($fromDate);
        //     $end = new DateTime($toDate);
        //     // Calculate the difference
        //     $interval = $start->diff($end);
        //     // Add 1 to include both start and end dates
        //     $varNoOfDays = $interval->days + 1;

        //     if ($leaveRequestFor === 'DAYS') {
        //         if ((int)$varNoOfDays !== (int)$noOfDays) {   
        //             echo("1The From and To Date is not equal to No. of Day(s)!");
        //             exit;
        //         }
        //     }
            
        //     //not earlier than 1 month and not later than 5 days
        //     // Rule: Start date must be at least 5 days ahead
        //     $today = new DateTime();  // Today, e.g., August 1
        //     $minStartDate = (clone $today)->modify('+5 days'); // August 6
        //     // Rule: End date must be within 1 month from today
        //     $maxEndDate = (clone $today)->modify('+1 month');  // September 1
        //     // echo "<script>console.log('From Date: " . $start . "');</script>";
        //     // exit;
        //     if ($start < $minStartDate) {
        //         if (trim($leaveCode) !== "SL") {
        //             echo "1❌ ERROR: Start date must be at least 5 days from today (on or after " . $minStartDate->format('Y-m-d') . ").". $start->format('Y-m-d');
        //             exit;
        //         }
        //     } elseif ($end > $maxEndDate) {
        //         if (trim($leaveCode) !== "SL") {
        //             echo "1❌ ERROR: End date must not be later than " . $maxEndDate->format('Y-m-d') . ".";
        //             exit;
        //         }
        //     } elseif ($end < $start) {
        //         echo "1❌ ERROR: End date cannot be earlier than start date.";
        //         exit;
        //     } else {
        //         //echo "2✅ Date range is valid!";
        //     }

        //     //Test kung mag overlap ang pinili na date (Regardless kung ano mang Leave Code basta meron ng nakafile na date na ganun hindi na pweding magfile)
        //     // ✅ SQL overlap check with leavecode
        //     $new_fromdate = $fromDate; // e.g. 2025-08-06
        //     $new_todate = $toDate;     // e.g. 2025-08-08
        //     $sql = "SELECT * FROM leavefilingfiletbl
        //     WHERE idno = '$idno'
        //         AND ('$new_fromdate' <= todate AND '$new_todate' >= fromdate)"; 
        //         //AND leavecode = '$leaveCode'";

        //     // Run query:
        //     $result = mysqli_query($conn, $sql);
        //     if (mysqli_num_rows($result) > 0) {
        //         echo "1❌ ERROR: Your requested leave dates overlap with an existing leave filing.";
        //         exit;
        //     } //else {
        //     // echo "1✅ No overlap, safe to file!";
        //     // exit;
        //     // }
            
        //     $leaveAllowance = $_POST['leaveAllowance'];
        //     $leaveUsed = $_POST['leaveUsed']; 
        //     $leaveBalance =  $_POST['leaveBalance'];
            
            
        //     $stmt = $conn->prepare("SELECT * FROM leaveallowancefiletbl WHERE idno = ? AND leavecode = ?");
        //     $stmt->bind_param("ss", $idno,$leaveCode);
        //     $stmt->execute();
        //     $result = $stmt->get_result();
        //     $total_records = $result->num_rows;
        //     $row = $result->fetch_assoc();
        //     if ($total_records > 0) {
        //         //Check Balance
        //         if ((float)$row['balance'] < (float)$noOfDays) {
        //             echo("1Insufficient leave balance!");
        //             //echo "Balance: " . $row['balance'] . ", Requested Days: " . $noOfDays . "<br>";
        //             exit;
        //         }

        //         //Compute the latest balance
        //         $latestUsed = ((float)$row['use'] + (float)$noOfDays);
        //         $latestBalance = (float)$row['allowance'] - $latestUsed;
        //     }

        //     $remark = $_POST['remarks'];
        //     if (empty(trim($remark))) {
        //         echo "1Please fill up remarks!";
        //         exit;
        //     }
            
        //     //OOP Prepared Statement
        //     try {
        //         $stmt = $conn->prepare("INSERT INTO leavefilingfiletbl (idno,trandate,leavecode,deptcode,divcode,
        //                                             noofdays,noofhours,totalnoofhours,yeardeduct,fromdate,todate,remark,
        //                                             allowance,`use`,balance,leaverequestfor) VALUES (?,?,?,?,?,?,
        //                                                                                              ?,?,?,?,?,
        //                                                                                              ?,?,?,?,?)");
        //         $stmt->bind_param("ssssssssssssssss", $idno,$tranDate,$leaveCode,$deptCode,$divCode,
        //                                             $noOfDays,$noOfHours,$totalNoOfHours,$yeardeduct,$fromDate,$toDate,$remark,
        //                                             $leaveAllowance,$leaveUsed,$leaveBalance,$leaveRequestFor);
        //         $stmt->execute();
        //         $leave_id = $conn->insert_id;
        //         $stmt->close();

        //         $stmt = $conn->prepare("Update leaveallowancefiletbl set `use` = ?, balance = ? WHERE idno = ? AND leavecode = ?");
        //         $stmt->bind_param("ssss", $latestUsed,$latestBalance,$idno,$leaveCode);
        //         $stmt->execute();
        //         $stmt->close();

        //         //Sending Mail
        //         // // $sql = "SELECT employeefiletbl.* FROM employeefiletbl 
        //         // //         WHERE employeefiletbl.idno = ?";
        //         // $sql = "SELECT e.*, 
        //         //     (SELECT employeename FROM employeefiletbl WHERE email = e.approver1) AS approver1_name,
        //         //     (SELECT employeename FROM employeefiletbl WHERE email = e.approver2) AS approver2_name
        //         //     FROM employeefiletbl e WHERE e.idno = ?";
        //         $sql = "SELECT e.*, 
        //             a1.employeename AS approver1_name,
        //             a2.employeename AS approver2_name
        //         FROM employeefiletbl e
        //         LEFT JOIN employeefiletbl a1 ON a1.email = e.approver1
        //         LEFT JOIN employeefiletbl a2 ON a2.email = e.approver2
        //         WHERE e.idno = ?";

        //         $stmt = $conn->prepare($sql);
        //         $stmt->bind_param("s", $idno);
        //         $stmt->execute();
        //         $result = $stmt->get_result();
        //         $total_records = $result->num_rows;
        //         $row = $result->fetch_assoc();
        //         $stmt->close();

        //         $emplEmail = $row['email'];
        //         $employeeName = $row['employeename'];
        //         $approver1 = $row['approver1'];
        //         $approver1Name = $row['approver1_name'];
        //         $approver2 = $row['approver2'];
        //         $approver2Name = $row['approver2_name'];
        //         $emailType = 'LeaveRequest';
                
        //         include('leavesendmail.php');
        //         echo "Leave request has been successfully submitted!";
        //     } catch(Exception $e) {
        //         echo("Error description: " . $conn -> error);
        //         exit;
        //     }
        // }

        if (isset($_POST['approve_leave'])) {
            $action = $_POST['action'];
            include('displayed.php');
            // $requestNo = $_POST['requestNo'];
            $requestNo = decryptHex($_POST['requestNo'], ENCRYPTION_KEY);
            $userLogInEmail = $_SESSION['email'];
            $disapproveReason = $_POST['disapproveReason'];

            if (isset($requestNo) && trim($requestNo) !== '') {
                $status = '0';
                if ($_SESSION['admin_or_user'] == "admin") {
                    $sql = "SELECT leavefilingfiletbl.*,employeefiletbl.email,employeefiletbl.approver1,employeefiletbl.approver2,employeefiletbl.employeename,
                            leavefiletbl.leavename FROM leavefilingfiletbl 
                            LEFT OUTER JOIN employeefiletbl on leavefilingfiletbl.idno = employeefiletbl.idno
                            LEFT OUTER JOIN leavefiletbl on leavefilingfiletbl.leavecode = leavefiletbl.leavecode
                            WHERE id = ?";
                } else {
                    $sql = "SELECT leavefilingfiletbl.*,employeefiletbl.email,employeefiletbl.approver1,employeefiletbl.approver2,employeefiletbl.employeename,
                            leavefiletbl.leavename FROM leavefilingfiletbl 
                            LEFT OUTER JOIN employeefiletbl on leavefilingfiletbl.idno = employeefiletbl.idno
                            LEFT OUTER JOIN leavefiletbl on leavefilingfiletbl.leavecode = leavefiletbl.leavecode
                            WHERE (employeefiletbl.approver1 = ? OR employeefiletbl.approver2 = ?) And id = ?";
                }
                $stmt = $conn->prepare($sql);
                if ($_SESSION['admin_or_user'] == "admin") {
                    $stmt->bind_param("s", $requestNo);
                } else {
                    $stmt->bind_param("sss", $userLogInEmail,$userLogInEmail,$requestNo);
                }
                $stmt->execute();
                $result = $stmt->get_result();
                $total_records = $result->num_rows;
                $row = $result->fetch_assoc();
                if ($total_records > 0) { //Check yung id if existing
                    $idno = $row['idno'];
                    $emplEmail = $row['email'];
                    $leaveCode = $row['leavecode'];
                   
                    $leaveName = $row['leavename'];
                    $fromDate = $row['fromdate'];
                    $toDate = $row['todate'];
                    $noOfDays = $row['noofdays'];
                    $employeeName = $row['employeename'];
                    $remark = $row['remark'];
                    $dateFiled = $row['trandate'];
                    $approver1 = $row['approver1'];
                    $approver2 = $row['approver2'];

                    $userApprover = ''; //ang gamit nito ay para malaman kung approver1 or approver2 yung nag approve para sa email notification
                    if (strtoupper($action) === "APPROVE") {
                        // echo "A";
                        // exit;
                        $statusApproved = '1'; //Approved
                        if (isset($_POST['approverSelect'])) { 
                            //Check if Approver1 or Approver2
                            if ($_POST['approverSelect'] === "Approver 1") {
                                // echo "A 1";
                                // exit;
                                // if (!empty($row['approver1']) && !empty($row['approver2'])) { //Parehong wala pang approval
                                    $stmt = $conn->prepare("Update leavefilingfiletbl set approver1status = ?, approver1update = CURRENT_TIMESTAMP, approver1 = ? WHERE id = ?");    
                                // } else if (!empty($row['approver1']) && empty($row['approver2'])) { //Parehong wala pang approval
                                //     $statusApproved = '1'; //Approved
                                //     $stmt = $conn->prepare("Update leavefilingfiletbl set approver1status = ?, approver1update = CURRENT_TIMESTAMP, approver1 = ? WHERE id = ?");
                                // }
                            } else if ($_POST['approverSelect'] === "Approver 2") {
                                // echo "A 2";
                                // exit;
                                // if (!empty($row['approver1']) && !empty($row['approver2'])) { //Parehong wala pang approval
                                    $stmt = $conn->prepare("Update leavefilingfiletbl set approver2status = ?, approver2update = CURRENT_TIMESTAMP, approver2 = ? WHERE id = ?");    
                                // } else if (!empty($row['approver1']) && empty($row['approver2'])) { //Parehong wala pang approval
                                //     $statusApproved = '1'; //Approved
                                //     $stmt = $conn->prepare("Update leavefilingfiletbl set approver2status = ?, approver2update = CURRENT_TIMESTAMP, approver2 = ? WHERE id = ?");
                                // }
                            } else if ($_POST['approverSelect'] === "Both") {
                                $stmt = $conn->prepare("Update leavefilingfiletbl set approver1status = ?, approver1update = CURRENT_TIMESTAMP, approver1 = ?,
                                                       approver2status = ?, approver2update = CURRENT_TIMESTAMP, approver2 = ? WHERE id = ?");    
                            }
                        } else {
                            //If Approver1 and Approver2 are the same
                            if ($row['approver1'] === $row['approver2']) {
                                 $stmt = $conn->prepare("Update leavefilingfiletbl set approver1status = ?, approver1update = CURRENT_TIMESTAMP, approver1 = ?,
                                                                                       approver2status = ?, approver2update = CURRENT_TIMESTAMP, approver2 = ? WHERE id = ?");    
                            } else {
                                //Check if Approver1 or Approver2
                                if ($userLogInEmail === $row['approver1']) {
                                    // if (!empty($row['approver1']) && !empty($row['approver2'])) { //Parehong wala pang approval
                                        $stmt = $conn->prepare("Update leavefilingfiletbl set approver1status = ?, approver1update = CURRENT_TIMESTAMP, approver1 = ? WHERE id = ?");    
                                    // } else if (!empty($row['approver1']) && empty($row['approver2'])) { //Parehong wala pang approval
                                    //     $statusApproved = '1'; //Approved
                                    //     $stmt = $conn->prepare("Update leavefilingfiletbl set approver1status = ?, approver1update = CURRENT_TIMESTAMP, approver1 = ? WHERE id = ?");
                                    // }
                                    $userApprover = '1';
                                } else if ($userLogInEmail === $row['approver2']) {
                                    // if (!empty($row['approver1']) && !empty($row['approver2'])) { //Parehong wala pang approval
                                        $stmt = $conn->prepare("Update leavefilingfiletbl set approver2status = ?, approver2update = CURRENT_TIMESTAMP, approver2 = ? WHERE id = ?");    
                                    // } else if (!empty($row['approver1']) && empty($row['approver2'])) { //Parehong wala pang approval
                                    //     $statusApproved = '1'; //Approved
                                    //     $stmt = $conn->prepare("Update leavefilingfiletbl set approver2status = ?, approver2update = CURRENT_TIMESTAMP, approver2 = ? WHERE id = ?");
                                    // }
                                    $userApprover = '2';
                                }
                            }
                        }
                        // echo "saving";
                        // exit;

                        if (isset($_POST['approverSelect'])) { 
                            //Check if Approver1 or Approver2
                            if ($_POST['approverSelect'] === "Both") {
                                $stmt->bind_param("sssss", $statusApproved,$userLogInEmail,$statusApproved,$userLogInEmail,$requestNo);
                            } else {
                                $stmt->bind_param("sss", $statusApproved,$userLogInEmail,$requestNo);    
                            }
                        } else {
                            if ($row['approver1'] === $row['approver2']) {
                                $stmt->bind_param("sssss", $statusApproved,$userLogInEmail,$statusApproved,$userLogInEmail,$requestNo);
                            } else {
                                $stmt->bind_param("sss", $statusApproved,$userLogInEmail,$requestNo);
                            }
                        }
                        $stmt->execute();
                        $stmt->close();

                        $emailType = "Approved";
                    } else { //Disapprove
                        $statusApproved = '2'; //Disapproved
                        
                        if (isset($_POST['approverSelect'])) { 
                            //Check if Approver1 or Approver2
                            if ($_POST['approverSelect'] === "Approver 1") {
                                // if (!empty($row['approver1']) && !empty($row['approver2'])) { //Parehong wala pang approval
                                    $stmt = $conn->prepare("Update leavefilingfiletbl set approver1status = ?, approver1update = CURRENT_TIMESTAMP, approver1 = ?, disapprove1reason = ? WHERE id = ?");    
                                // } else if (!empty($row['approver1']) && empty($row['approver2'])) { //Parehong wala pang approval
                                //     $statusApproved = '2'; //Disapproved
                                //     $stmt = $conn->prepare("Update leavefilingfiletbl set approver1status = ?, approver1update = CURRENT_TIMESTAMP, approver1 = ? WHERE id = ?");
                                // }
                            } else if ($_POST['approverSelect'] === "Approver 2") {
                                // if (!empty($row['approver1']) && !empty($row['approver2'])) { //Parehong wala pang approval
                                    $stmt = $conn->prepare("Update leavefilingfiletbl set approver2status = ?, approver2update = CURRENT_TIMESTAMP, approver2 = ?, disapprove2reason = ? WHERE id = ?");    
                                // } else if (!empty($row['approver1']) && empty($row['approver2'])) { //Parehong wala pang approval
                                //     $statusApproved = '2'; //Disapproved
                                //     $stmt = $conn->prepare("Update leavefilingfiletbl set approver2status = ?, approver2update = CURRENT_TIMESTAMP, approver2 = ? WHERE id = ?");
                                // }
                            } else if ($_POST['approverSelect'] === "Both") {
                                    $stmt = $conn->prepare("Update leavefilingfiletbl set approver1status = ?, approver1update = CURRENT_TIMESTAMP, approver1 = ?, disapprove1reason = ?, 
                                                           approver2status = ?, approver2update = CURRENT_TIMESTAMP, approver2 = ?, disapprove2reason = ? WHERE id = ?");    
                            } 
                        } else {
                            //Check if Approver1 or Approver2
                            if ($userLogInEmail === $row['approver1']) {
                                // if (!empty($row['approver1']) && !empty($row['approver2'])) { //Parehong wala pang approval
                                    //kapag disapprove sa approver1, kailangan din i-disapprove sa approver2 para hindi ma-approve ng approver2
                                    $stmt = $conn->prepare("Update leavefilingfiletbl set approver1status = ?, approver1update = CURRENT_TIMESTAMP, approver1 = ?, disapprove1reason = ?, 
                                                            approver2status = ?, approver2update = CURRENT_TIMESTAMP, approver2 = ?, disapprove2reason = ? WHERE id = ?");    
                                // } else if (!empty($row['approver1']) && empty($row['approver2'])) { //Parehong wala pang approval
                                //     $statusApproved = '2'; //Disapproved
                                //     $stmt = $conn->prepare("Update leavefilingfiletbl set approver1status = ?, approver1update = CURRENT_TIMESTAMP, approver1 = ? WHERE id = ?");
                                // }
                            } else if ($userLogInEmail === $row['approver2']) {
                                // if (!empty($row['approver1']) && !empty($row['approver2'])) { //Parehong wala pang approval
                                    $stmt = $conn->prepare("Update leavefilingfiletbl set approver2status = ?, approver2update = CURRENT_TIMESTAMP, approver2 = ?, disapprove2reason = ? WHERE id = ?");    
                                // } else if (!empty($row['approver1']) && empty($row['approver2'])) { //Parehong wala pang approval
                                //     $statusApproved = '2'; //Disapproved
                                //     $stmt = $conn->prepare("Update leavefilingfiletbl set approver2status = ?, approver2update = CURRENT_TIMESTAMP, approver2 = ? WHERE id = ?");
                                // }
                            }
                        }

                        if (isset($_POST['approverSelect'])) { 
                            //Check if Approver1 or Approver2
                            if ($_POST['approverSelect'] === "Both") {
                                $stmt->bind_param("sssssss", $statusApproved,$userLogInEmail,$disapproveReason,
                                                          $statusApproved,$userLogInEmail,$disapproveReason,$requestNo);
                            } else {
                                $stmt->bind_param("ssss", $statusApproved,$userLogInEmail,$disapproveReason,$requestNo);
                            }
                        } else {
                            if ($userLogInEmail === $row['approver1']) {
                                $stmt->bind_param("sssssss", $statusApproved,$userLogInEmail,$disapproveReason,
                                                          $statusApproved,$userLogInEmail,$disapproveReason,$requestNo);
                            } else {
                                $stmt->bind_param("ssss", $statusApproved,$userLogInEmail,$disapproveReason,$requestNo);
                            }
                        }
                        $stmt->execute();
                        $stmt->close();
                        
                        //No updating balance for LOA kasi wala naman allowance
                        if (trim($leaveCode) === "LOA") {
                            //Do nothing
                        } else {
                            //Bring back the balance of Disapproved Request
                            $stmt = $conn->prepare("UPDATE leaveallowancefiletbl SET `use` = `use` - ? WHERE idno = ? AND leavecode = ?");
                            $stmt->bind_param("dss", $noOfDays, $idno, $leaveCode);
                            //$stmt->execute();
                            if (!$stmt->execute()) {
                                echo "Error1: " . $stmt->error;
                            }
                            $stmt->close();

                            $stmt = $conn->prepare("UPDATE leaveallowancefiletbl SET balance = allowance - `use` WHERE idno = ? AND leavecode = ?");
                            $stmt->bind_param("ss", $idno, $leaveCode);
                            //$stmt->execute();
                            if (!$stmt->execute()) {
                                echo "Error2: " . $stmt->error;
                            }
                            $stmt->close();
                        }
                        $emailType = "Disapproved";
                    } // End of Approve or Disapprove

                    //Get the approver name by searching the email who log in the system
                    $sql = "SELECT e.* FROM employeefiletbl e WHERE e.email = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("s", $userLogInEmail);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $total_records = $result->num_rows;
                    $row = $result->fetch_assoc();
                    $stmt->close();
                    $approverName = $row['employeename'];
                    
                    // header("Location: leavesendmail.php?emplEmail=$emplEmail&approver1=$approver1&approver2=$approver2&emailType=$emailType");
                    include('leavesendmail.php');
                    //echo "Successfully Dispproved!";
                } else {
                    echo ". Not Found";
                }
            } 
        }

        if (isset($_POST['follow_up_leave'])) { 
            $tranno = $_POST['tranno'];
            $approverEmail = $_POST['approverEmail'];
            $emailType = 'FollowUp';
            
            //Get the employeename, leavecode, fromdate, todate, noofdays, remark, and trandate based on the id of leavefilingfiletbl
            $sql = "SELECT leavefilingfiletbl.*, employeefiletbl.employeename, leavefiletbl.leavename FROM leavefilingfiletbl 
                    LEFT OUTER JOIN employeefiletbl on leavefilingfiletbl.idno = employeefiletbl.idno
                    LEFT OUTER JOIN leavefiletbl on leavefilingfiletbl.leavecode = leavefiletbl.leavecode
                    WHERE leavefilingfiletbl.id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $tranno);
            $stmt->execute();
            $result = $stmt->get_result();
            $leaveRequest = $result->fetch_assoc();
            $stmt->close();
            $fEmployeeName = $leaveRequest['employeename'];
            $fLeaveName = $leaveRequest['leavename'];
            $noOfDays = $leaveRequest['noofdays'];
            if ((float)$noOfDays < (float)1)  {
                $fLeaveDate = $fromDate." (Half Day)";
            } else {
                $fLeaveDate = $leaveRequest['fromdate'] . " to " . $leaveRequest['todate'];
            }  
            $fRemark = $leaveRequest['remark'];
            $fDateFiled = $leaveRequest['trandate'];
            $emailType = "Follow-up";

            include('leavesendmail.php');
            //echo "Follow up email has been successfully sent!";
            echo "success";
        }
           

        if (isset($_POST['Get_Leave_File_Base_On_RD'])) {  //RD - Range Date
            $_SESSION['Get_Leave_File_Base_On_RD'] = "True";
            $_SESSION['Get_Leave_File_Base_On_RD_FromDate'] = $_POST['fromDate'];
            $_SESSION['Get_Leave_File_Base_On_RD_ToDate'] = $_POST['toDate'];
        }

        if (isset($_POST['cancel_leave'])){
            $requestNo = $_POST['requestNo'];
            $userLogInEmail = $_SESSION['email'];
            $cancelReason = $_POST['cancelReason'];
            $statusApproved = '3'; // Cancel

            $conn->begin_transaction(); // 🔹 Start transaction

            try {
                // 1. Update leave filing status
                $stmt = $conn->prepare("UPDATE leavefilingfiletbl 
                                        SET approver1status = ?, approver1update = CURRENT_TIMESTAMP, approver1 = ?, disapprove1reason = ?,
                                            approver2status = ?, approver2update = CURRENT_TIMESTAMP, approver2 = ?, disapprove2reason = ? 
                                        WHERE id = ?");
                $stmt->bind_param("sssssss", $statusApproved, $userLogInEmail, $cancelReason,
                                            $statusApproved, $userLogInEmail, $cancelReason, $requestNo);
                $stmt->execute();
                $stmt->close();

                // 2. Get idno, leavecode, and NoOfDays
                $stmt = $conn->prepare("SELECT idno, leavecode, noOfdays 
                                        FROM leavefilingfiletbl 
                                        WHERE id = ?");
                $stmt->bind_param("s", $requestNo);
                $stmt->execute();
                $stmt->bind_result($idno, $leaveCode, $noOfDays);

                if ($stmt->fetch()) {
                    $stmt->close();

                    // 3. Restore leave balance
                    $stmt = $conn->prepare("UPDATE leaveallowancefiletbl 
                                            SET `use` = `use` - ? 
                                            WHERE idno = ? AND leavecode = ?");
                    $stmt->bind_param("dss", $noOfDays, $idno, $leaveCode);
                    $stmt->execute();
                    $stmt->close();

                    // 4. Recalculate balance
                    $stmt = $conn->prepare("UPDATE leaveallowancefiletbl 
                                            SET balance = allowance - `use` 
                                            WHERE idno = ? AND leavecode = ?");
                    $stmt->bind_param("ss", $idno, $leaveCode);
                    $stmt->execute();
                    $stmt->close();

                    $conn->commit(); // 🔹 All good — commit transaction
                    echo "Successfully Cancelled!";
                } else {
                    $stmt->close();
                    $conn->rollback(); // 🔹 No record found — rollback
                    echo "No record found for request ID: " . htmlspecialchars($requestNo);
                }

            } catch (Exception $e) {
                $conn->rollback(); // 🔹 Any error — rollback
                echo "Transaction failed: " . $e->getMessage();
            }
            
            // $emailType = "Cancelled";
            
            // //Get the approver name by searching the email who log in the system
            // $sql = "SELECT e.* FROM employeefiletbl e WHERE e.email = ?";
            // $stmt = $conn->prepare($sql);
            // $stmt->bind_param("s", $userLogInEmail);
            // $stmt->execute();
            // $result = $stmt->get_result();
            // $total_records = $result->num_rows;
            // $row = $result->fetch_assoc();
            // $stmt->close();
            // $approverName = $row['employeename'];
            
            // // header("Location: leavesendmail.php?emplEmail=$emplEmail&approver1=$approver1&approver2=$approver2&emailType=$emailType");
            // include('leavesendmail.php');
            // //echo "Successfully Dispproved!";
        }

        // Get Department List and Put to JSon Format
        if (isset($_POST['get_department'])) {
            $sql = "SELECT deptid,deptname FROM departmentfiletbl order by deptname Asc";
            if($result = mysqli_query($conn, $sql)){
                if(mysqli_num_rows($result) > 0){
                    $a = array();
                    
                    while($row = mysqli_fetch_array($result)){
                        $deptid=$row["deptid"];
                        $deptname=$row["deptname"];
                        // $arraydept=$arraydept."".$deptid." : "."'".$deptname."'".",";
                        // $arraydept[$deptid] = $row[$deptname];
                        $a[ $row['deptid'] ] = $row['deptname'];
                    }
                    echo json_encode($a);
                }
            }
        }

        if (isset($_POST['get_request_detail'])) {
            // var table_data_json = '[{"name":"Tiger Nixon","position":"System Architect","salary":"$320,800","fileupload":"<button onclick=view_upload_filefn(this.id) name=ilagayangfile id=btn-ilagayangreqno style=border-radius:5px&#59;>View File</button>","approveverify":"","start_date":"2011/04/25","office":"Edinburgh","extn":"5421"},'+
            // '{"name":"Garrett Winters","position":"Accountant","salary":"$170,750","fileupload":"<button onclick=view_upload_filefn(this.id) name=ilagayangfile id=btn-ilagayangreqno style=border-radius:5px&#59;>View File</button>","approveverify":"","start_date":"2011/07/25","office":"Tokyo","extn":"8422"},'+
            // '{"name":"Ashton Cox","position":"Junior Technical Author","salary":"$86,000","fileupload":"<button onclick=view_upload_filefn(this.id) name=ilagayangfile id=btn-ilagayangreqno style=border-radius:5px&#59;>View File</button>","approveverify":"<a href=# id=ilagayangreqno-update title=Update Record data-toggle=tooltip><span class=\'fas fa-pen-square fa-lg\'></span></a>","start_date":"2009/01/12","office":"San Francisco","extn":"1562"}]';

            //OOP Prepared Statement
            try {
                $deptid = $_SESSION['deptid'];
                $sql = "SELECT requestfiletbl.*,approvalfiletbl.* FROM requestfiletbl 
                        Left Outer Join approvalfiletbl ON requestfiletbl.reqno = approvalfiletbl.reqno
                        WHERE requestfiletbl.deptidfrom = $deptid or requestfiletbl.deptidfrom = $deptid or 
                        approvalfiletbl.appdeptid = $deptid or approvalfiletbl.verifytodept = $deptid";
                $result = $conn->query($sql);
                // $total_filter_records = $result->num_rows;
                
            } catch (Exception $e) {
                error_log($e);
                // exit('<h3 align="center">Page Not Found.</h3>');
            }
            while($row = $result->fetch_assoc()) {
                $reqno = $row['reqno'];
                $reqdate = $row['reqdate'];
                $userid = $row['userid'];
                $deptidfrom = $row['deptidfrom'];
                $deptidto = $row['deptidto'];
                $filename = $row['filename'];
                $remarks = $row['remarks'];
                echo '<td>'. $remarks . '</td>';
            }
        }

        //Approved Disapproved Verify
        if(isset($_POST['save_approved_verify'])) {
            //Validate if the same department
            if ($_POST['verifydeptid']==$_SESSION['deptid']) {
                echo "Cannot verify with the same department!";
                exit;
            }

            date_default_timezone_set("asia/manila");
            $appdate	 = date('Y-m-d');
            $reqno = $_POST['reqno'];
            $appuserid	= $_SESSION['userid'];
            $appdeptid	= $_SESSION['deptid'];
            $status = $_POST['status'];
            
            if ($status=="V") {
                $verifytodept = $_POST['verifydeptid'];
                $remarks = $_POST['remarks'];
                $closedrequest = 0;
                $closedrequestremarks = "";
            } else {
                $verifytodept = 0;
                $remarks = $_POST['closedrequestremarks']; //temporary dapat remarks nemu itang posting
                $closedrequest = 1;
                $closedrequestremarks = $_POST['closedrequestremarks'];
            }

            //Update to requestfiletbl
            $stmt = $conn->prepare("Update requestfiletbl set closedrequest = ?, closedrequestremarks = ?,deptidtolatest = ? where reqno = ?");
            $stmt->bind_param("ssss", $closedrequest,$closedrequestremarks,$verifytodept,$reqno);
            $stmt->execute();
            $stmt->close();
            // echo $appdate.' = '.$reqno.' = '.$appuserid.' = '.$appdeptid.' = '.$status.' = '.$verifytodept.' = '.$remarks;
            //OOP Prepared Statement
            try {
                $stmt = $conn->prepare("INSERT INTO approvalfiletbl (appdate,reqno,appuserid,appdeptid,`status`,verifytodept,remarks) 
                        VALUES (?,?,?,?,?,?,?)");
                $stmt->bind_param("sssssss", $appdate,$reqno,$appuserid,$appdeptid,$status,$verifytodept,$remarks);
                $stmt->execute();
                $stmt->close();
            } catch(Exception $e) {
                echo("Error description: " . $conn -> error);
                exit;
            }
            echo "Successfuly Saved!";
        }

        // Log OUT
        if (isset($_POST['log_out'])) {
            if ($_SESSION['admin_or_user'] == "user") {
                echo "user";
            } else {
                echo "admin";
            }
            
            //Set the token value
            if (isset($_SESSION['idno'])) {
                $stmt = $conn->prepare("UPDATE employeefiletbl SET session_token = NULL WHERE idno = ?");
                $stmt->bind_param("s", $_SESSION['idno']);
                $stmt->execute();
            }

            session_unset();
            session_destroy();
        }
    }
?>