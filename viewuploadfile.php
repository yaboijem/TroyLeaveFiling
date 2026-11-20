<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
    <body>
        <?php
             // Get the path of uploaded file
            // if (isset($_POST['view_upload_file'])) {
            //     $filename = $_FILES['file']['name'];
            //     echo $filename;
            //     exit;
                // echo $_GET['file'];
                // exit;
                $filename = "https://uploadfiles/or1_11211014171917.pdf";//$_GET['file']; //'C:/Users/Dell/Desktop/iClock880-H.pdf';
                // $filename = 'C:/Users/Dell/Desktop/iClock880-H.pdf';
                header('Content-type: application/pdf');
                header('Content-Disposition: inline; filename="' . $filename . '"');
                header('Content-Transfer-Encoding: binary');
                header('Content-Length: ' . filesize($filename));
                header('Accept-Ranges: bytes');
                @readfile($filename);
            // }
        ?>
    </body>
</html>