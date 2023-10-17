<?php


require 'king-base.php';
require_once QA_INCLUDE_DIR . 'king-db/selects.php';

reset($_FILES);
$temp    = current($_FILES);
$TempSrc = $temp['tmp_name'];

if ( isset( $TempSrc ) && is_uploaded_file( $TempSrc ) ) {
    $UploadDirectory = 'uploads/';
    $ffmpeg          = qa_opt('video_ffmpeg');
    $second          = 2;
    $vowels          = array(' ', '&', '');
    $ImageName       = str_replace($vowels, '-', strtolower($temp['name']));
    $temptype        = $temp['type'];
    if (!isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
        die();
    }

    switch ( strtolower( $temptype ) ) {
        case 'video/mp4':
            break;
        case 'video/mov':
            break;
        case 'video/quicktime':
            break;
        case 'audio/mpeg':
            break;
        case 'audio/mp3':
            break;
        default:
            die('Unsupported File!');
    }

    $Random_Number = rand(0, 999999);
    $NewFileName = $Random_Number . '-' . basename($ImageName);


    $NewFileName2  = $Random_Number;
    $year_folder   = $UploadDirectory . date("Y");
    $month_folder  = $year_folder . '/' . date("m");

    !file_exists($year_folder) && mkdir($year_folder, 0777);
    !file_exists($month_folder) && mkdir($month_folder, 0777);
    $path  = $month_folder . '/' . $NewFileName;
    $image = $month_folder . '/' . $Random_Number . '.jpg';
    $ret['thumb'] = '';
    $ret['prev']  = '';
    if ( isset( $TempSrc ) ) {
        if (qa_opt('enable_aws')) {

            require QA_INCLUDE_DIR . 's3/aws.phar';
            $s3Client = new Aws\S3\S3Client([
            'region'  => ''.qa_opt('aws_region').'',
            'version' => 'latest',
            'credentials' => [
                  'key'    => ''.qa_opt('aws_key').'',
                  'secret' => ''.qa_opt('aws_secret').''
                  ]
            ]);     

            $result = $s3Client->putObject([
                'Bucket' => ''.qa_opt('aws_bucket').'',
                'Key'    => $NewFileName,
                'SourceFile' => $TempSrc          
            ]);
            $output = king_insert_uploads($result['ObjectURL'], $temptype, null, null, 'aws');
            $path  = $result['ObjectURL'];
        } else {
            move_uploaded_file($TempSrc, $path);
            $output = king_insert_uploads($path, $temptype);
        }
        if ( $ffmpeg ) {
            $cmd    = "$ffmpeg -i $path -deinterlace -an -ss $second -t 00:00:01 -r 1 -y -vcodec mjpeg -f mjpeg $image 2>&1";
            $return = `$cmd`;
            if (file_exists($image)) {
                list($CurWidth, $CurHeight) = getimagesize($image);
                $output_image               = king_insert_uploads( $image, 'image/jpeg', $CurWidth, $CurHeight );
                $ret['thumb']               = $output_image;
                $ret['prev']                = $image;
            }
        }


        $ret['main'] = $output;
        $ret['id'] = $output;
        echo json_encode($ret);
    }
    
} else {
    die('Something wrong with upload! Is "upload_max_filesize" set correctly?');
}
