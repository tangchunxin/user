<?php
error_reporting(7);
error_reporting(E_ALL|E_STRICT);
ini_set('display_errors', 'on');

//10>设置session,必须处于脚本最顶部
session_start();
vCode(4, 18); //4个数字，显示大小为15

function vCode($num = 4, $size = 20, $width = 0, $height = 0) {
	if(!$width)
	{
		$width = $num * $size * 4 / 5 + 5;
	}
	if(!$height)
	{
		$height = $size + 10;
	}
	// 去掉了 0 1 O l 等
	$text='';
	$str = "2345679abcdfghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVW";
	for ($i = 0; $i < $num; $i++) {
		$text .= $str[mt_rand(0, strlen($str)-1)];
	}
	$_SESSION['authcode']=$text;

	// 画图像
	//$font  =  "Arial" ;
	$fontsize  =  $size ;
	$fontcolor  =  "gray" ;
	//$glow_radius  =  15 ;
	# Three glow colors
	$glow  = array(  "#ff0000" ,  "#ff8800" ,  "#ffff00"  );
	# moves text down
	$offset  =  1 ;
	# make a black pallete
	$pallete  = new  Imagick ;
	$pallete -> newimage ( $width , $height ,  "#000000" );
	# set pallet format to gif
	$pallete -> setimageformat ( "png" );
	# make a draw object with settings
	$draw  = new  imagickdraw ();
	$draw -> setgravity ( imagick :: GRAVITY_CENTER );
	//$draw -> setfont (  $font );
	$draw -> setfontsize ( $fontsize );
	# Loop through glow colors
	foreach(  $glow  as  $var )
	{
		$draw -> setfillcolor ( " $var " );
		$pallete -> annotateImage  (  $draw , 0  , $offset ,  0 ,  $text  );
		$pallete -> annotateImage  (  $draw , 0  , $offset ,  0 ,  $text  );
		//$pallete -> BlurImage (  $glow_radius ,  $glow_radius  );
	}
	# top layer
	$draw -> setfillcolor ( " $fontcolor " );
	# center annotate on top of offset annotates
	$pallete -> annotateImage  (  $draw , 0  , $offset ,  0 ,  $text  );
	# output to browser
	$pallete -> setImageFormat ( "png" );
	header (  "Content-Type: image/png"  );
	echo  $pallete ;
}