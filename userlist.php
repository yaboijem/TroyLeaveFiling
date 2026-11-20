<?php 
    if (!isset($_SESSION)) {session_start();} //To recognize the $_SESSION variable you have set
    // echo $_SESSION['admin_or_user'];

    if (!isset($_SESSION['admin_or_user'])) { //To check if the admin or user is not log in it will go back to index.php
      echo "<script>window.open('index.php','_self')</script>";
    } else {
        $idno = $_SESSION['idno'];
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
              $email = $_SESSION['email'];
              $deptname = $row['deptname'];
              $divname = $row['divname'];
              $active = $row['active'];
              $approver1 = $row['approver1'];
              $approver2 = $row['approver2'];
              
              if ($row['imagesrc'] == NULL) {
                $imagesrc = "uploadfiles/imagedefault.jpg";
              } else {
                $imagesrc = $row['imagesrc'];
              }
            }
          }
        }
        
    }
?>
<div class="container">
  <div class="row justify-content-center">
    <div class="col-md-6 text-center">

      <!-- Profile Picture -->
      <img src="<?php echo $imagesrc ?>" alt="Profile Picture" class="profile-pic" id="profileImage">

      <!-- Upload Info + Button -->
      <div class="upload-btn text-start mt-3">
        <label><strong>JPG or PNG, no larger than 5 MB</strong></label><br>
        <!-- Hidden file input -->
        <input type="file" id="uploadInput" accept="image/png, image/jpeg" style="display:none;">
        <!-- Custom button to trigger file input -->
        <button type="button" class="btn btn-primary mt-2" onclick="document.getElementById('uploadInput').click();">
          Upload New Image
        </button>
        <button type="button" id="saveimg" class="btn btn-primary mt-2" onclick="saveimg()" style="display: none;">
          Save New Image
        </button>
      </div>

      <!-- ID No & Employee Name -->
      <div class="mt-3">
        <p><strong>ID No:</strong> <span id="idNo"><?php echo $idno ?></p>
        <p><strong>Employee Name:</strong> <span id="empName"><?php echo $employeename ?></span></p>
      </div>

      <!-- 2-Column Fields (View Only) -->
      <div class="row text-start mt-4">
        <div class="col-md-6 mb-3">
          <label>Email</label>
          <input type="email" class="form-control" id="empEmail" value="<?php echo $email ?>" readonly>
        </div>
        <div class="col-md-6 mb-3">
          <label>Department</label>
          <input type="text" class="form-control" id="empDepartment" value="<?php echo $deptname ?>" readonly>
        </div>
        <div class="col-md-6 mb-3">
          <label>Division</label>
          <input type="text" class="form-control" id="empDivision" value="<?php echo $divname ?>" readonly>
        </div>
        <div class="col-md-6 mb-3">
          <label>Active</label>
          <input type="text" class="form-control" id="empActive" value="<?php echo $active ?>" readonly>
        </div>
        <div class="col-md-6 mb-3">
          <label>Approver 1</label>
          <input type="text" class="form-control" id="approver1" value="<?php echo $approver1 ?>" readonly>
        </div>
        <div class="col-md-6 mb-3">
          <label>Approver 2</label>
          <input type="text" class="form-control" id="approver2" value="<?php echo $approver2 ?>" readonly>
        </div>

        <!-- Approver 1 -->
        <!-- Approver 1 -->
        <!-- <div class="col-md-6 mb-3">
          <label>Approver 1</label>
          <select class="form-control" id="approver1" name="approver1">
            <option value="" disabled selected>Select Approver 1</option> -->
            <?php
              // $query = "SELECT email FROM employeefiletbl WHERE (usertype='approver' or usertype='admin') AND active='yes'";
              // $result = $conn->query($query);
              // if ($result && $result->num_rows > 0) {
              //   while ($row = $result->fetch_assoc()) {
              //     $selected = ($approver1 == $row['email']) ? "selected" : "";
              //     echo "<option value='{$row['email']}' $selected>{$row['email']}</option>";
              //   }
              // }
            ?>
          <!-- </select>
        </div> -->

        <!-- Approver 2 -->
        <!-- <div class="col-md-6 mb-3">
          <label>Approver 2</label>
          <select class="form-control" id="approver2" name="approver2">
            <option value="" disabled selected>Select Approver 2</option> -->
            <?php
              // $result = $conn->query($query); // reuse same query
              // if ($result && $result->num_rows > 0) {
              //   while ($row = $result->fetch_assoc()) {
              //     $selected = ($approver2 == $row['email']) ? "selected" : "";
              //     echo "<option value='{$row['email']}' $selected>{$row['email']}</option>";
              //   }
              // }
            ?>
          <!-- </select>
        </div> -->

        
      </div>
      <div class="upload-btn text-start mt-3">
        <?php
          // echo
          // '<button style="width:150px;" type="button" class="btn btn-primary mt-2" onclick="updateApprover();">
          //     Update
          // </button>'
        ?>
      </div>
    </div>
  </div>
</div>

<style>
  .profile-pic {
    width: 150px;
    height: 150px;
    object-fit: cover;
    border-radius: 50%;
    border: 2px solid #007bff;
  }
</style>

<!-- Image Preview Script -->
<script>
  document.getElementById('uploadInput').addEventListener('change', function(event) {
    const [file] = event.target.files;
    // alert(file);
    if (file) {
      document.getElementById('profileImage').src = URL.createObjectURL(file);
    }
  });

  // Get references to the buttons and file input
  const uploadInput = document.getElementById("uploadInput");
  const uploadBtn = uploadInput.nextElementSibling; // Assumes it's the next button
  const saveBtn = document.getElementById("saveimg");

  // Trigger when a file is selected
  uploadInput.addEventListener("change", function () {
    if (uploadInput.files.length > 0) {
      // Hide Upload button
      uploadBtn.style.display = "none";
      // Show Save button
      saveBtn.style.display = "inline-block";
    }
  });

  function saveimg() {
    const fileInput = document.getElementById("uploadInput");
    const file = fileInput.files[0];
    const filename = file.name;

    if (!file) {
      alert("No file selected.");
      return;
    }

    const formData = new FormData();
    formData.append("image", file);
    formData.append('saveemployeeimage','yes');

    fetch("crud.php", {
      method: "POST",
      body: formData
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        alert("Image uploaded successfully!");
        const imgElement = document.getElementById("dashboardimagesrc");
        imgElement.src = data.filename; 


        // Hide Upload button
        uploadBtn.style.display = "inline-block";
        // Show Save button
        saveBtn.style.display = "none";

      } else {
        alert(data.message);
      }
    })
    .catch(error => {
      console.error("Error:", error);
      alert("An error occurred while uploading.");
    });
  }

  function updateApprover() {
    approver1 = document.getElementById("approver1").value.trim();
    approver2 = document.getElementById("approver2").value.trim();
    // alert(approver1 + ' ' + approver2);
    const formData = new FormData();
    formData.append("approver1", approver1);
    formData.append("approver2", approver2);
    formData.append('update_aprrover','yes');

    fetch("crud.php", {
      method: "POST",
      body: formData
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        alert(data.message);
      } else {
        alert("Upload failed: " + data.message);
      }
    })
    .catch(error => {
      console.error("Error:", error);
      alert("An error occurred while uploading.");
    });
  }
</script>
  