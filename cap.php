<?php

    $cap = "";
	// 获得画布
	$img = imagecreatetruecolor(200, 50);

	// 背景色 浅一些
	$bg_color = imagecolorallocate($img, 220, 220, 220);
	imagefill($img, 0, 0, $bg_color);

	// 文字池 
	$str = '想问天你在哪里我想问问我自己放弃所有抛下所有让我漂流在安静的夜夜空里';
	// 汉字在utf-8中一个汉字占3个字节
	// 获得字节长度
	$len = strlen($str);
	// 获得汉字长度
	$cn_len = $len / 3;
	// 获得字体真实路径
	$font_set = scandir('fonts');
	$font_num = count($font_set) - 2;
	
	// 获得随机文字
	for($i = 0; $i < 4; $i++)
	{
		$font_index = mt_rand(2, $font_num + 1);
		$font_path = realpath('fonts/'.$font_set[$font_index]);

		$char_index = mt_rand(0, $cn_len - 1);
		$char = substr($str, $char_index * 3, 3);

        $cap .= $char;

		$str_color = imagecolorallocate($img, mt_rand(0, 150), mt_rand(0, 150), mt_rand(0, 150));

		imagettftext($img, mt_rand(27, 47), mt_rand(-30, 30), 10 + $i * 43, 40, $str_color, $font_path, $char);

		//echo $char;
	}

	// 增加干扰点
	for($i = 0; $i < 20; $i++)
	{
		$dot_color = imagecolorallocate($img, mt_rand(170, 200), mt_rand(170, 200), mt_rand(170, 200));
		imagestring($img, mt_rand(0, 5), mt_rand(0, 200), mt_rand(0, 50), '*', $dot_color);
	}

	// 增加干扰线
	for($i = 0; $i < 10; $i++)
	{
		$line_color = imagecolorallocate($img, mt_rand(200, 210), mt_rand(200, 210), mt_rand(200, 210));
		imageline($img, mt_rand(0, 200), mt_rand(0, 50), mt_rand(0, 200), mt_rand(0, 50), $line_color);
	}

	// 点击验证码刷新
	
	/*

	*/


    session_name("marsbank");
    session_start();
    $_SESSION["cap"] = $cap; 
    // var_dump($_SESSION["cap"]);

	header("content-type:image/png");
	imagepng($img);
	imagedestroy($img);

?>