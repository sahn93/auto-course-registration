<?php 

require_once 'TesseractOCR.php';

attempt(0);

$tried_num = 0;
$applied_num = 0;

function attempt($check) {
    $class_num = $check+1;
    if ($class_num==3) {
        $class_num=7;
    } else if ($class_num==5) {
        $class_num=10;
    }
    echo "강좌번호: ".$class_num."\n";
        

	global $tried_num;
	global $applied_num;
	
	$STD_NUMBER = 
	$PASSWORD = 

	$login_url = "https://sugang.snu.ac.kr/sugang/j_login";
	// STD_NUMBER에 학점을 2012-xxxx 형식으로, PASSWORD에 비번을 넣는다.
	$post_fields = "j_username=".$STD_NUMBER."&j_password=".$PASSWORD&."v_password=".$PASSWORD."&t_password=%EC%88%98%EA%B0%95%EC%8B%A0%EC%B2%AD+%EB%B9%84%EB%B0%80%EB%B2%88%ED%98%B8";

	// 관심강의 화면
	$listurl = "http://sugang.snu.ac.kr/sugang/ca/ca103.action";
	// 메인화면
	$mainurl = "http://sugang.snu.ac.kr/sugang/co/co010.action";

	// 신청 버튼
	$apply_url = "http://sugang.snu.ac.kr/sugang/ca/ca101.action";

	// 1. 메인화면 get 할때 생기는 JSESSIONID를 얻는다
	$initCookie = site_cookie_store($mainurl);

	// 2. 초기 쿠키를 써서 로그인
	$loginCookie = auth_site_cookie_store($login_url, $post_fields);

	// // 3. 로그인 쿠키로 페이지 조회
	// $list = auth_site_get($listurl, $loginCookie);

	// 4. 관심강좌 페이지 파일로 저장 (안해도되지만)
	// $file = fopen("list.html", "w");
	// fwrite($file, $list);
	// fclose($file);
    
	// 확인문자 url
	$v = rand(0,1);
	$number_url = "http://sugang.snu.ac.kr/sugang/ca/number.action?v=".$v;

	// 5. ocr 해서 숫자 알아내기 
	$ocr_num = run_ocr($number_url, $loginCookie);
	echo $ocr_num;

	// 6. 수강신청 요청 보내기 (아래 필드 중 check이 관심강좌 리스트 중 위에서 몇번째냐는것. 0이면 맨 위에거 체크한것임)
    // 크롬에서 수강신청 요청 보내보고 그 파라미터부분 복사해오면됨.

	$reqfields = "inputText=".$ocr_num."&workType=I&repeat=C&check=0&sbjtCd=031.001&ltNo=00".$class_num;

	$apply_result = apply($apply_url, $loginCookie, $reqfields);

	if ($apply_result==1) {
		$applied_num++;
	}
	$tried_num++;

	echo $applied_num.'/'.$tried_num."\n";

	attempt((++$check % 5));
}






function apply($url, $cookiefile, $fields) {
	$ch = curl_init ($url);

    // 안돌아갈때 헤더를 고치면 되는 경우가 있음.
	$headers = array(
        'Host: sugang.snu.ac.kr',
        'Connection: keep-alive',
        'Content-Length: 64',
        'Cache-Control: max-age=0',
        'Origin: http://sugang.snu.ac.kr',
        'Upgrade-Insecure-Requests: 1',
        'Content-Type: application/x-www-form-urlencoded',
        'User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_12_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/61.0.3163.100 Safari/537.36',
        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8',
        'Referer: http://sugang.snu.ac.kr/sugang/cc/cc210.action',
        'Accept-Encoding: gzip, deflate',
        'Accept-Language: ko-KR,ko;q=0.8,en-US;q=0.6,en;q=0.4'
	);

	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	curl_setopt($ch, CURLOPT_POST, 1);
 	curl_setopt($ch, CURLOPT_POSTFIELDS, "$fields");
 	curl_setopt($ch, CURLOPT_COOKIEFILE, "$cookiefile");
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_TIMEOUT, 1);
 	
 	$result = curl_exec ($ch);
	
	while(curl_errno($ch) == 28){
		echo "apply failed...\n";
		return 0;
	}

	curl_close ($ch);

	echo $result;
	return 1;
}

function run_ocr($numurl, $cookie) {

	grab_image($numurl, "number.png", $cookie);
    sleep(0.4);
	$tesseract = new TesseractOCR("number.png");
	$tesseract->whitelist(range(0,9));
	$number = $tesseract->run();

    sleep(0.3);

	if ($number === '') {
		echo "인식실패, 이미지 다시 가져옵니다\n";
		run_ocr($numurl, $cookie);
	} else {
		echo "인식성공, ".$number."\n";
		return $number;
	}
}

function grab_image($url,$saveto, $cookiefile){
    $ch = curl_init ($url);

    $headers = array(
            'Host: sugang.snu.ac.kr',
			'Connection: keep-alive',
			'Accept: image/webp,image/*,*/*;q=0.8',
            'User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_12_5) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/59.0.3071.115 Safari/537.36',
            'Referer: http://sugang.snu.ac.kr/sugang/cc/cc210.action',
			'Accept-Encoding: gzip, deflate',
			'Accept-Language: ko-KR,ko;q=0.8,en-US;q=0.6,en;q=0.4'
    	);

    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_BINARYTRANSFER,1);
 	curl_setopt($ch, CURLOPT_COOKIEFILE, "$cookiefile");

    $raw=curl_exec($ch);
    curl_close ($ch);
    if(file_exists($saveto)){
        unlink($saveto);
    }
    $fp = fopen($saveto,'x');
    fwrite($fp, $raw);
    fclose($fp);
}

function site_cookie_store($url) {
	$parseURL = parse_url($url);

	$fp = fopen($parseURL['host'].".txt", "w");
 	fclose($fp);

	$headers = array(
	    'Host: sugang.snu.ac.kr',
		'Connection: keep-alive',
		'Cache-Control: max-age=0',
		'Upgrade-Insecure-Requests: 1',
		'User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_12_4) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/57.0.2987.133 Safari/537.36',
		'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
		'Referer: http://sugang.snu.ac.kr/sugang/co/co012.action',
		'Accept-Encoding: gzip, deflate, sdch',
		'Accept-Language: ko-KR,ko;q=0.8,en-US;q=0.6,en;q=0.4'
	);

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL,"$url");
	//return the transfer as a string 
	curl_setopt($ch, CURLOPT_POST, 0);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	curl_setopt($ch, CURLOPT_COOKIEJAR, "initcookie.txt");

	ob_start();
 	$result = curl_exec ($ch);
 	ob_end_clean();
	curl_close ($ch);

	return $parseURL['host'].".txt";
}

function auth_site_cookie_store($loginurl, $postfields)
{

 $parseURL = parse_url($loginurl);

 $headers = array(
	 	'Host: sugang.snu.ac.kr',
		'Connection: keep-alive',
		'Content-Length: 144',
		'Cache-Control: max-age=0',
		'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
		'Origin: http://sugang.snu.ac.kr',
		'Upgrade-Insecure-Requests: 1',
		'User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_2) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/47.0.2526.111 Safari/537.36',
		'Content-Type: application/x-www-form-urlencoded',
		'Referer: http://sugang.snu.ac.kr/sugang/co/co010.action',
		'Accept-Encoding: gzip, deflate',
		'Accept-Language: ko-KR,ko;q=0.8,en-US;q=0.6,en;q=0.4'
 	);

 $fp = fopen($parseURL['host'].".txt", "w");
 fclose($fp);

 $ch = curl_init();
 curl_setopt($ch, CURLOPT_URL,"$loginurl");
 curl_setopt($ch, CURLOPT_POST, 1);
 curl_setopt($ch, CURLOPT_POSTFIELDS, "$postfields");
 curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
 curl_setopt($ch, CURLOPT_COOKIEJAR, $parseURL['host'].".txt");
 curl_setopt($ch, CURLOPT_COOKIEFILE, "initcookie.txt");

 ob_start();
 $result = curl_exec ($ch);
 ob_end_clean();

 curl_close ($ch);

 return $parseURL['host'].".txt";
}

function auth_site_get($geturl, $cookiefile)
{
	 $parseURL = parse_url($geturl);

	 $headers = array(
		 	'Host: sugang.snu.ac.kr',
			'Connection: keep-alive',
			'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
			'Upgrade-Insecure-Requests: 1',
			'User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_2) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/47.0.2526.111 Safari/537.36',
			'Referer: http://sugang.snu.ac.kr/sugang/ca/ca102.action?workType=F',
			'Accept-Encoding: gzip, deflate, sdch',
			'Accept-Language: ko-KR,ko;q=0.8,en-US;q=0.6,en;q=0.4'
	 	);

	 $ch = curl_init();
	 curl_setopt($ch, CURLOPT_URL,"$geturl");
	 curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	 curl_setopt($ch, CURLOPT_COOKIEFILE, "$cookiefile");
	 curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

	 ob_start();
	 $result = curl_exec ($ch);
	 ob_end_clean();
	 curl_close ($ch);

	 return $result;
}

 ?>
