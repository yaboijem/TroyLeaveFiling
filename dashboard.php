<?php 
    if (!isset($_SESSION)) {session_start();} //To recognize the $_SESSION variable you have set
    
    //$loginname = $_SESSION['employeename'];
    $loginemail = $_SESSION['email'];
    $idno = $_SESSION['idno'];
    //echo $idno . ' ' . $loginemail;
    require_once "dbconnect.php";
    
    $sql = "SELECT employeefiletbl.*,departmentfiletbl.deptname,divisionfiletbl.divname FROM employeefiletbl 
            LEFT OUTER JOIN departmentfiletbl ON employeefiletbl.deptcode = departmentfiletbl.deptcode
            LEFT OUTER JOIN divisionfiletbl ON employeefiletbl.divcode = divisionfiletbl.divcode
            WHERE idno = '".$idno."'";
    if($result = mysqli_query($conn, $sql)){
      if(mysqli_num_rows($result) > 0){
        $x = 0;
        while($row = mysqli_fetch_array($result)){
          $employeename = $row['employeename'];
          if ($row['imagesrc'] == NULL) {
            $imagesrc = "uploadfiles/imagedefault.jpg";
          } else {
            $imagesrc = $row['imagesrc'];
          }
        }
      }
    }
    
    // if ($idno == 1015832026) {
    //   $employeename = 'Jiriel Sicangco';
    // } 


    include('verifytoken.php');
    
    if (!isset($_SESSION['admin_or_user'])) { //To check if the admin or user is not log in it will go back to index.php
      echo "<script>window.open('index.php','_self')</script>";
    } else {
        //if ($_SESSION['admin_or_user'] == "admin") { //Dashboard is for user and admin this test $pagedisplayfor to check if user or admin
        //   $pagedisplayfor = "admin";
        // } else {
        //   $pagedisplayfor = "user";
        // }
        $pagedisplayfor = $_SESSION['admin_or_user'];
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>LSLMS | Dashboard</title>
  <!-- Search -->
  <link rel="stylesheet" href="plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
  <link rel="icon" href="dist/img/Logo.png">
  <!-- Google Font: Source Sans Pro -->
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="plugins/fontawesome-free/css/all.min.css">
  <!-- Ionicons -->
  <link rel="stylesheet" href="https://code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css">
  <!-- Tempusdominus Bootstrap 4 -->
  <!-- <link rel="stylesheet" href="plugins/tempusdominus-bootstrap-4/css/tempusdominus-bootstrap-4.min.css"> -->
  <!-- iCheck -->
  <!-- <link rel="stylesheet" href="plugins/icheck-bootstrap/icheck-bootstrap.min.css"> -->
  <!-- Theme style -->
  <link rel="stylesheet" href="dist/css/adminlte.min.css"> <!--for the template of dashboard-->
  <!-- overlayScrollbars -->
  <!-- <link rel="stylesheet" href="plugins/overlayScrollbars/css/OverlayScrollbars.min.css"> -->
  <!-- Daterange picker -->
  <!-- <link rel="stylesheet" href="plugins/daterangepicker/daterangepicker.css"> -->
  <!-- summernote -->
  <!-- <link rel="stylesheet" href="plugins/summernote/summernote-bs4.min.css"> -->
  <!-- SweetAlert2 -->
  <link rel="stylesheet" href="plugins/sweetalert2-theme-bootstrap-4/bootstrap-4.min.css">
  <!-- Password Toggle -->
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

  <style>
    span.requiredfield {
        color: red;
        font-style: bold;
    }

    @media only screen and (max-width: 768px) {
      #barsmenu {
        margin-left: 1rem;
      }
      #logouticon {
        margin-right: 1rem;
      }

      #nrprintbar {
        text-align: center;
        float:none;
        /* border:1px solid red; */
      }
    }

    @media only screen and (min-width: 769px) {
      #nrprintbar {
        text-align: center;
        float:right;
        /* border:1px solid red; */
      }
    }

    @media (max-width: 768px) {
      div.dataTables_wrapper div.dataTables_length,
      div.dataTables_wrapper div.dataTables_filter {
        float: none;
        text-align: left;
        width: 100%;
      }

      div.dataTables_wrapper div.dataTables_length {
        margin-right: 20px; /* 👈 your margin */
      }

      div.dataTables_length select {
        margin-left: 15px;
      }
    }


    /* Paste this css to your style sheet file or under head tag */
    /* This only works with JavaScript, 
    if it's not present, don't show loader */
    /* .no-js #loader { display: none;  }
    .js #loader { display: block; position: absolute; left: 100px; top: 0; }
    .se-pre-con {
      left: 0px;
      top: 0px;
      width: 100%;
      height: 100%;
      z-index: 9999;
      background: url(file/img/loading.gif) center no-repeat #fff;
    } */

    /* basic positioning */
    .legend { list-style: none; }
    .legend li { float: left; margin-right: 10px; }
    .legend span { border: 1px solid #ccc; float: left; width: 12px; height: 12px; margin: 2px; }
    /* your colors */
    .legend .AV { background-color: #00aeff; }
    .legend .CI { background-color: green; }
    .legend .CO { background-color: purple; }
    .legend .COC { background-color: #fefe81; }
    .legend .RC { background-color: orange; }
    .legend .GC { background-color: red; }
    .legend .OOO { background-color: gray; }
    .legend .FI { background-color: #00ffff; }


    #modalLoadingOverlay {
      display: none !important;
    }

    #modalLoadingOverlay.show {
      display: flex !important;
    }


  </style>
</head>
<body class="hold-transition sidebar-mini layout-fixed">

<div id="modalLoadingOverlay" class="position-fixed top-0 start-0 w-100 h-100 d-flex justify-content-center align-items-center"
     style="background-color: rgba(255,255,255,0.6); z-index: 2000;">
  <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
    <span class="sr-only">Loading...</span>
  </div>
</div>

<div class="wrapper">
  <!-- Navbar -->
  <nav class="main-header navbar navbar-expand navbar-white navbar-light">
    <!-- Left navbar links -->
    <ul class="navbar-nav">
      <li class="nav-item">
        <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i id="barsmenu" class="fas fa-bars"></i></a>
      </li>
      <li class="nav-item d-none d-sm-inline-block">
        <a href="index.php" class="nav-link">Home</a>
      </li>
    </ul>
    <!-- Right navbar links -->
    <ul class="navbar-nav ml-auto">
      <li class="nav-item">
        <a class="nav-link" data-widget="fullscreen" href="#" role="button">
          <i class="fas fa-expand-arrows-alt"></i>
        </a>
      </li>
      <li class="nav-item">
        <a onclick="logoutsystem()" class="nav-link" id="logoutli" href="#" role="button">
          <i id="logouticon" class="fas fa-sign-out-alt"></i>
        </a>
      </li>
    </ul>
  </nav>
  <!-- /.navbar -->

  <!-- Main Sidebar Container -->
  <aside class="main-sidebar sidebar-dark-primary elevation-4" style="background-color: #146fd6;">
    <!-- Brand Logo -->
    <a href="index.php" class="brand-link" style="padding-bottom:10px">
      <img src="dist/img/Logo.png" alt="Logo png" class="brand-image img-circle " style="opacity: .8;height:25px;width:25px;">
      <span class="brand-text font-weight-light" title="L-Soft Leave Management System">LSLMS</span>
    </a>

    <!-- Sidebar -->
    <div class="sidebar">
      <!-- Sidebar user panel (optional) -->
      <div class="user-panel mt-3 pb-3 mb-3 d-flex">
        <!-- <div class="image"> -->
        <div class="image">
          <img id="dashboardimagesrc" src="<?php echo $imagesrc; ?>" class="img-circle elevation-2" style="height:25px;width:25px;" alt="User Image">
        </div>
        <div class="info">
          <!-- <a href="#" class="d-block">John Davis</a> -->
          
          <p style="color:#ffffff"><?php echo ucwords($employeename); ?></p> 
          <p style="color:#ffffff"><?php echo strtoupper($pagedisplayfor); ?></p>
          <p style="color:#ffffff" id="dept_name"><?php //echo $department; ?></p>
          <p style="color:#ffffff" id="user_shift"><?php //echo $user_shift; ?></p>
          <p style="color:#ffffff" id="user_shift"><?php //echo $_SESSION['datelogin']; ?></p>
          
          
        </div>
      </div>

      <!-- Sidebar Menu -->
      <nav class="mt-2">
        <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
          <!-- Add icons to the links using the .nav-icon class
               with font-awesome or any other icon font library -->
          
          
          <!-- <li class="nav-item">
            <a href="#" onclick="loadfile(\'roomtype\')" class="nav-link">
              <i class="nav-icon fas fa-user-friends"></i>
              <p>
                Room Type
              </p>
            </a>
          </li>

          <li class="nav-item">
            <a href="#" onclick="loadfile(\'department\')" class="nav-link">
              <i class="nav-icon fas fa-user-friends"></i>
              <p>
                Department
              </p>
            </a>
          </li>

          <li class="nav-item">
            <a href="#" onclick="loadfile(\'department\')" class="nav-link">
              <i class="nav-icon fas fa-user-friends"></i>
              <p>
                Type of Cleaning
              </p>
            </a>
          </li> -->

          

          <?php
            if ($pagedisplayfor=='admin' || $pagedisplayfor=='user' || $pagedisplayfor=='approver') {
              echo '
              <li class="nav-item">
                  <a href="#" onclick="loadfile(\'user\')" class="nav-link" >
                    <i class="nav-icon fas fa-user-circle"></i>
                    <p>
                      Profile
                    </p>
                  </a>
                </li>';
            }

            if (isset($_SESSION['idno'], $_SESSION['session_token'])
                && ($pagedisplayfor=='admin' || $pagedisplayfor=='user' || $pagedisplayfor=='approver')) {
              echo '
                <li class="nav-item">
                  <a href="portal/" target="_top" class="nav-link" title="Open the DTR Portal">
                    <i class="nav-icon fas fa-clock"></i>
                    <p>
                      DTR Portal
                    </p>
                  </a>
                </li>';
            }

            if (isset($_SESSION['idno'], $_SESSION['session_token'])
                && ($pagedisplayfor=='admin' || $pagedisplayfor=='approver')) {
              echo '
                <li class="nav-item">
                  <a href="admin/" target="_top" class="nav-link" title="Open the DTR Admin configuration">
                    <i class="nav-icon fas fa-cog"></i>
                    <p>
                      DTR Admin
                    </p>
                  </a>
                </li>';
            }

            if ($pagedisplayfor=='admin' || $pagedisplayfor=='approver') {
              echo '
                <li class="nav-item">
                  <a href="#" onclick="loadfile(\'employeefile\')" class="nav-link" >
                    <i class="nav-icon fas fa-user-group"></i>
                    <p>
                      Employee List
                    </p>
                  </a>
                </li>';
            }

            if ($pagedisplayfor=='admin' || $pagedisplayfor=='user' || $pagedisplayfor=='approver') {
              echo '
                <li class="nav-item">
                  <a href="#" onclick="loadfile(\'leavefile\')" class="nav-link" >
                    <i class="nav-icon fas fa-folder-open"></i>
                    <p>
                      Leave File
                    </p>
                  </a>
                </li>';
            }

            if ($pagedisplayfor=='admin' || $pagedisplayfor=='approver') {
              echo '
                <li class="nav-item">
                  <a href="#" onclick="loadfile(\'leaveforapproval\')" class="nav-link">
                    <i class="nav-icon fas fa-check-circle"></i>
                    <p>
                      Leave for Approval
                    </p>
                  </a>
                </li>';
            }
            
            // if ($pagedisplayfor=='admin' || $pagedisplayfor=='approver') {
            //   echo '
            //     <li class="nav-item">
            //       <a href="#" onclick="loadfile(\'leavereport\')" class="nav-link">
            //         <i class="nav-icon fas fa-file-lines"></i>
            //         <p>
            //           Reports
            //         </p>
            //       </a>
            //     </li>';
            // }

            if ($pagedisplayfor=='admin' || $pagedisplayfor=='approver') {
              echo '
                <li class="nav-item has-treeview">
                  <a href="#" class="nav-link">
                    <i class="nav-icon fas fa-file-lines"></i>
                    <p>
                      Reports
                      <i class="right fas fa-angle-left"></i>
                    </p>
                  </a>
                  <ul class="nav nav-treeview">
                    <li class="nav-item">
                      <a href="#" onclick="loadfile(\'leavereport\')" class="nav-link">
                        <i class="far fa-circle nav-icon"></i>
                        <p>Leave Report</p>
                      </a>
                    </li>
                    <li class="nav-item">
                      <a href="#" onclick="loadfile(\'leavereportarchive\')" class="nav-link">
                        <i class="far fa-circle nav-icon"></i>
                        <p>Leave Archive Report</p>
                      </a>
                    </li>
                    <li class="nav-item">
                      <a href="#" onclick="loadfile(\'leavebalance\')" class="nav-link">
                        <i class="far fa-circle nav-icon"></i>
                        <p>Leave Balance Report</p>
                      </a>
                    </li>
                  </ul>
                </li>';
            }

            if ($pagedisplayfor=='admin' || $pagedisplayfor=='user' || $pagedisplayfor=='approver') {
              if ($pagedisplayfor=='user') {
                echo ' <li class="nav-item">
                        <a href="#" onclick="loadfile(\'leavereportarchive\')" class="nav-link">
                          <i class="far fas fa-file-lines"></i>
                          <p>&nbsp;&nbsp;Leave Archive Report</p>
                        </a>
                    </li>';
              }

              if ($pagedisplayfor=='admin') {
                echo ' <li class="nav-item">
                        <a href="#" onclick="loadfile(\'\')" class="nav-link">
                          <i class="far fas fa-sync"></i>
                          <p>&nbsp;&nbsp;Reset Leave Balance</p>
                        </a>
                    </li>';
              }

              echo '
                  <li class="nav-item">
                    <a href="#" onclick="loadfile(\'usercp\')" class="nav-link">
                      <i class="nav-icon fas fa-key"></i>
                      <p>
                        Change Password
                      </p>
                    </a>
                  </li>';
            }
          ?>

          

          <li class="nav-item">
            <a href="#" onclick="logoutsystem()" class="nav-link">
              <i class="fas fa-sign-out-alt"></i>
              <p>
                Log out
              </p>
            </a>
          </li>

        </ul>
      </nav>
      <!-- /.sidebar-menu -->
    </div>
    <!-- /.sidebar -->
  </aside>

  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-12">
            <h1 class="m-0 mb-2" style="text-align:center">DASHBOARD</h1>
            <div>
              <ul class="legend p-0 m-0">
              </ul>
            </div>
          </div><!-- /.col -->
        </div><!-- /.row -->
      </div><!-- /.container-fluid -->
    </div>
    <!-- /.content-header -->

    <!-- Main content -->
    <section class="content">
      <div class="container-fluid">
        <!-- Small boxes (Stat box) -->
        <div class="row" id="homepagebox">

          <?php
            if ($pagedisplayfor=='admin' || $pagedisplayfor=='user' || $pagedisplayfor=='approver') {
              echo '<div class="col-lg-3 col-12 mb-3">
                  <div class="small-box text-bg-warning" style="cursor:pointer" onclick="loadfile(\'user\')">
                    <div class="inner">
                      <h3>Profile</h3>
                      <p>Account</p>
                    </div>
                    <i class="nav-icon fas fa-user-circle small-box-icon" style="right:10px;top:10px;font-size:30px;padding:5px;"></i>
                    <a href="#" class="small-box-footer" style="background-color: #146fd6;">More info <i class="fas fa-arrow-circle-right"></i></a>
                  </div>
                </div>';
            }
          
            if ($pagedisplayfor=='admin' || $pagedisplayfor=='approver') {
              echo '<div class="col-lg-3 col-12 mb-3">
                  <div class="small-box text-bg-warning" style="cursor:pointer" onclick="loadfile(\'employeefile\')">
                    <div class="inner">
                      <h3>Employee List</h3>
                      <p>Information</p>
                    </div>
                    <i class="nav-icon fas fa-user-group small-box-icon" style="right:10px;top:10px;font-size:30px;padding:5px;"></i>
                    <a href="#" class="small-box-footer" style="background-color: #146fd6;">More info <i class="fas fa-arrow-circle-right"></i></a>
                  </div>
                </div>';
            } 

            if ($pagedisplayfor=='admin' || $pagedisplayfor=='user' || $pagedisplayfor=='approver') {
              echo '<div class="col-lg-3 col-12 mb-3">
                  <div class="small-box text-bg-primary" style="cursor:pointer" onclick="loadfile(\'leavefile\')">
                    <div class="inner">
                      <h3>Leave File</h3>
                      <p>Leave Balances/Filing</p>
                    </div>
                    <i class="nav-icon fas fa-folder-open small-box-icon" style="right:10px;top:10px;font-size:30px;padding:5px;"></i>
                    <a href="#" class="small-box-footer" style="background-color: #146fd6;">More info <i class="fas fa-arrow-circle-right"></i></a>
                  </div>
                </div>';
            } 
            
            if ($pagedisplayfor=='admin' || $pagedisplayfor=='approver') {
              echo '<div class="col-lg-3 col-12 mb-3">
                  <div class="small-box text-bg-success" style="cursor:pointer" onclick="loadfile(\'leaveforapproval\')">
                    <div class="inner">
                      <h3>Leave for Approval</h3>
                      <p>Manager Access</p>
                    </div>
                    <i class="nav-icon fas fa-check-circle small-box-icon" style="right:10px;top:10px;font-size:30px;padding:5px;"></i>
                    <a href="#" class="small-box-footer" style="background-color: #146fd6;">More info <i class="fas fa-arrow-circle-right"></i></a>
                  </div>
                </div>';
            } 
            
            if ($pagedisplayfor=='admin' || $pagedisplayfor=='approver') {
              echo '<div class="col-lg-3 col-12 mb-3">
                  <div class="small-box text-bg-danger" style="cursor:pointer" onclick="loadfile(\'leavereport\')">
                    <div class="inner">
                      <h3>Reports</h3>
                      <p>Leave (Approved/Disapproved)</p>
                    </div>
                    <i class="nav-icon fas fa-file-lines small-box-icon" style="right:10px;top:10px;font-size:30px;padding:5px;"></i>
                    <a href="#" class="small-box-footer" style="background-color: #146fd6;">More info <i class="fas fa-arrow-circle-right"></i></a>
                  </div>
                </div>';
            } 
          ?>
        </div>
        <!-- /.row -->
        <!-- Main row -->
        
        <!-- /.row (main row) -->
      
      </div><!-- /.container-fluid -->

       <!-- Create Request -->
       <div class="container-fluid" id="createrequestlist">
      </div>
      <!-- Create Request -->  

      <!-- Open  Request -->
      <div class="container-fluid" id="newrequestlist">
      </div>
      <!-- Open  Request -->

      <!-- Pending Request -->
      <!-- <div class="container-fluid" id="pendingrequestlist">
      </div> -->
      <!-- Pending Request -->

      <!-- Old Request -->
      <div class="container-fluid" id="leaveforapprovallist">
      </div>
      <!-- Old Request -->
      
      <!-- Room Type -->
      <div class="container-fluid" id="roomtypelist">
      </div>
      <!-- Room Type -->

      <!-- Room -->
      <div class="container-fluid" id="roomlist">
      </div>
      <!-- Room -->

      <!-- Report -->
      <div class="container-fluid" id="leavereportlist">
      </div>
      <!-- Report -->
      
      <!-- Leave Balance -->
      <div class="container-fluid" id="leavebalancelist">
      </div>
      <!-- Leave Balance -->

      <!-- Leave Archive -->
      <div class="container-fluid" id="leavereportarchivelist">
      </div>
      <!-- Leave Archive -->

      <!-- Leave file List -->
      <div class="container-fluid" id="leavefilelist">
      </div>

      <div class="container-fluid" id="employeeleavefilelist">
      </div>
      
       <div class="container-fluid" id="employeeleavefiledisapprovedlist">
      </div>
      <!-- Leave file List -->

      <!-- User List -->
      <div class="container-fluid" id="userlist">
      </div>
      <!-- User List -->
      
      <!-- Employee List -->
      <div class="container-fluid" id="employeefilelist">
      </div>
      <!-- Employee List -->      

      <!-- User Change Password -->
      <div class="container-fluid" id="usercplist">
      </div>
      <!-- User Change Password -->  

      <!-- Admin List -->
      <div class="container-fluid" id="adminlist">
      </div>
      <!-- Admin List -->
      
    </section>
    <!-- /.content -->
  </div>
  <!-- /.content-wrapper -->
  
  <footer class="main-footer mt-5">
    
  </footer>

  <!-- Control Sidebar -->
  <aside class="control-sidebar control-sidebar-dark">
    <!-- Control sidebar content goes here -->
  </aside>
  <!-- /.control-sidebar -->
</div>
<!-- ./wrapper -->

<!-- jQuery -->
<script src="plugins/jquery/jquery.min.js"></script>
<!-- jQuery UI 1.11.4 -->
<!-- <script src="plugins/jquery-ui/jquery-ui.min.js"></script> -->
<!-- Resolve conflict in jQuery UI tooltip with Bootstrap tooltip -->
<!-- <script>$.widget.bridge('uibutton', $.ui.button)</script> -->
<!-- Bootstrap 4 -->
<!-- <script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script> -->
<!-- ChartJS -->
<!-- <script src="plugins/chart.js/Chart.min.js"></script> -->
<!-- Sparkline -->
<!-- <script src="plugins/sparklines/sparkline.js"></script> -->
<!-- <script src="plugins/jquery-knob/jquery.knob.min.js"></script> -->
<!-- daterangepicker -->
<!-- <script src="plugins/moment/moment.min.js"></script> -->
<!-- <script src="plugins/daterangepicker/daterangepicker.js"></script> -->
<!-- Tempusdominus Bootstrap 4 -->
<!-- <script src="plugins/tempusdominus-bootstrap-4/js/tempusdominus-bootstrap-4.min.js"></script> -->
<!-- Summernote -->
<!-- <script src="plugins/summernote/summernote-bs4.min.js"></script> -->
<!-- overlayScrollbars -->
<!-- <script src="plugins/overlayScrollbars/js/jquery.overlayScrollbars.min.js"></script> -->
<!-- AdminLTE App -->
<script src="dist/js/adminlte.js"></script>
<script src="plugins/sweetalert2/sweetalert.min.js"></script>
<script>
  // var userType = "<?php echo $pagedisplayfor; ?>";
  // alert("<?php echo $pagedisplayfor; ?>");

  function showModalLoading() {
    document.getElementById("modalLoadingOverlay").classList.add("show");
  }

  function hideModalLoading() {
    document.getElementById("modalLoadingOverlay").classList.remove("show");
  }     

  function loadfile(sbname,fromdate,todate) {
    // Remove the content of inner html of a div (the advantage of this is you can use the same id with the menu you have selected)
    document.getElementById("homepagebox").innerHTML = "";
    document.getElementById("leaveforapprovallist").innerHTML = "";
    document.getElementById("leavereportlist").innerHTML = "";
    document.getElementById("leavereportarchivelist").innerHTML = "";
    document.getElementById("leavebalancelist").innerHTML = "";
    document.getElementById("leavefilelist").innerHTML = "";
    document.getElementById("employeeleavefilelist").innerHTML = "";
    document.getElementById("employeeleavefiledisapprovedlist").innerHTML = "";
    document.getElementById("userlist").innerHTML = "";
    document.getElementById("employeefilelist").innerHTML = "";
    document.getElementById("usercplist").innerHTML = "";
    document.getElementById("adminlist").innerHTML = "";
    
    $("#" + sbname + "list").html("<div style='position:absolute; width:80%; height:100%;'><img src='loading.gif' style='margin-left: auto;margin-right: auto;display: block;' alt='loading...' /></div>");
    if (sbname=="leavefile") { 
        $( "#" + sbname + "list" ).load( sbname + "list.php" );
        $( "#employee" + sbname + "list" ).load( "employee" + sbname + "list.php" );
        $( "#employee" + sbname + "disapprovedlist" ).load( "employee" + sbname + "disapprovedlist.php" );
    } else {
      $( "#" + sbname + "list" ).load( sbname + "list.php" );
    }  
  }
  
  function logoutsystem() {
    swal({
      title: "Log Out in the System?",
      text: "",
      icon: "warning",
      buttons: [
        'No',
        'Yes'
      ],
      dangerMode: false,
    }).then(function(isConfirm) {
      if (isConfirm) {
        var fd = new FormData();
        fd.append('log_out','admin'); //for crud.php validation
        
        $.ajax({  
          url: 'crud.php',
          type: 'post',
          data: fd,
          contentType: false,
          processData: false,
          success: function(response){
              window.open('index.php','_self')
          }
        });
      } else {
      }
    });
  }
</script>


</body>
</html>
