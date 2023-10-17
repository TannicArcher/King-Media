<?php
/*
This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

More about this license: LICENCE.html
 */
require 'king-base.php';
require_once QA_INCLUDE_DIR . 'king-db/selects.php';
require_once QA_INCLUDE_DIR . 'king-app-video.php';

$temp    = $_FILES['file'];
$TempSrc = $temp['tmp_name'];
$ret     = array();

if ( isset( $TempSrc ) && is_uploaded_file( $TempSrc ) ) {
    $DestinationDirectory = 'uploads/';
    $Quality              = 90;
    $RandomNumber         = rand( 0, 999999 );
    $ImageName            = str_replace( ' ', '-', strtolower( $temp['name'] ) );
    $ImageType            = $temp['type'];

    switch ( strtolower( $ImageType ) ) {
        case 'image/png':
            $CreatedImage = imagecreatefrompng( $TempSrc );
            break;
        case 'image/gif':
            $CreatedImage = imagecreatefromgif( $TempSrc );
            break;
        case 'image/webp':
            $CreatedImage = imagecreatefromwebp( $TempSrc );
            break;
        case 'image/jpeg':
        case 'image/pjpeg':
            $CreatedImage = imagecreatefromjpeg( $TempSrc );
            break;
        default:
            die( 'Unsupported File!' );
    }

    list( $CurWidth, $CurHeight ) = getimagesize( $TempSrc );

    $NewImageName = $RandomNumber . '-' . basename( $ImageName );

    $year_folder  = $DestinationDirectory . date( "Y" );
    $month_folder = $year_folder . '/' . date( "m" );

    ! file_exists( $year_folder ) && mkdir( $year_folder, 0777 );
    ! file_exists( $month_folder ) && mkdir( $month_folder, 0777 );
    $path = $month_folder . '/' . $NewImageName;

    if ( qa_opt( 'enable_aws' ) ) {
        require QA_INCLUDE_DIR . 's3/aws.phar';

        $s3Client = new Aws\S3\S3Client( array(
            'region'      => '' . qa_opt( 'aws_region' ) . '',
            'version'     => 'latest',
            'credentials' => array(
                'key'    => '' . qa_opt( 'aws_key' ) . '',
                'secret' => '' . qa_opt( 'aws_secret' ) . '',
            ),
        ) );

        $result = $s3Client->putObject( array(
            'Bucket'     => '' . qa_opt( 'aws_bucket' ) . '',
            'Key'        => $NewImageName,
            'SourceFile' => $TempSrc,
        ) );

        $output       = king_insert_uploads( $result['ObjectURL'], $ImageType, $CurWidth, $CurHeight, 'aws' );
        $dthumb       = king_uploadthumb( $ImageName, $TempSrc, $ImageType );
        $ret['main']  = $output;
        $ret['thumb'] = $dthumb['id'];
    } else {
        if ( king_uploadthumb( $ImageName, $TempSrc, $ImageType ) ) {
            move_uploaded_file( $TempSrc, $path );
            $output = king_insert_uploads( $path, $ImageType, $CurWidth, $CurHeight );

            $ret['main']  = $output;
            $ret['thumb'] = $output - 1;
        }
    }

    echo json_encode( $ret );
}
