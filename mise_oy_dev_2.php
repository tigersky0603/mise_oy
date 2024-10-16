<?php
// API 키 설정
$weather_api_key = "LCuy9eWKCdC6S6qsHWBEl4Yfuxv0UPJP1yJVDl1jxPP5Ne6PbGHPI9%2BQLRWdqOJ1fdGMEhdRONk4O9X7AZPT9A%3D%3D"; // 기상청 API 키
$air_quality_api_key = "LCuy9eWKCdC6S6qsHWBEl4Yfuxv0UPJP1yJVDl1jxPP5Ne6PbGHPI9%2BQLRWdqOJ1fdGMEhdRONk4O9X7AZPT9A%3D%3D"; // 대기질 API 키

// OpenAI API 키 설정
$openai_api_key = "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";

// OpenAI API 호출 함수
function callOpenAI($prompt) {
    global $openai_api_key;
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://api.openai.com/v1/chat/completions');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $openai_api_key
    ]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'model' => 'gpt-3.5-turbo',
        'messages' => [
            ['role' => 'system', 'content' => '당신은 날씨와 대기질에 대해 전문적인 지식을 가진 도우미입니다.'],
            ['role' => 'user', 'content' => $prompt]
        ],
        'temperature' => 0.7,
        'max_tokens' => 150
    ]));

    $response = curl_exec($ch);
    
    if ($response === false) {
        $error = curl_error($ch);
        curl_close($ch);
        return '오류가 발생했습니다: ' . $error;
    }
    
    curl_close($ch);

    $result = json_decode($response, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        return '응답을 파싱하는 데 오류가 발생했습니다.';
    }
    
    if (isset($result['error'])) {
        return '오류가 발생했습니다: ' . $result['error']['message'];
    }
    
    return $result['choices'][0]['message']['content'] ?? '죄송합니다. 응답을 생성할 수 없습니다.';
}

// AJAX 요청 처리
if (isset($_POST['message'])) {
    $user_message = $_POST['message'];
    $bot_response = callOpenAI($user_message);
    echo json_encode(['response' => $bot_response]);
    exit;
}

// 사용자 위치 정보 가져오기
$latitude = $_GET['lat'] ?? null; // URL 파라미터에서 위도 가져오기, 없으면 null
$longitude = $_GET['lng'] ?? null; // URL 파라미터에서 경도 가져오기, 없으면 null

// 위도나 경도가 없을 경우 처리
if ($latitude === null || $longitude === null) {
    // 기본 위치 설정 (예: 서울시청)
    $latitude = 37.5665;
    $longitude = 126.9780;
}

// 기상청 단기예보 조회 API 호출
$weather_url = "http://apis.data.go.kr/1360000/VilageFcstInfoService_2.0/getVilageFcst"; // API URL

$now = new DateTime();  // 현재 시각을 받아 DateTime 객체 생성
$base_date = $now->format('Ymd');   // YYYYMMDD 형식의 날짜 문자열 추출
$cur_time = $now->format('H');  // HH 형식의 시간 문자열 추출 (분을 00으로 고정)
$base_times = ["02","05","08","11","14","17","20","23"];
$base_time = null;

foreach ($base_times as $item_time) {
    if ($item_time <= $cur_time) {
        $base_time = $item_time . '00';
    } else {
        if (!isset($base_time) && ($item_time == '02')) {// 2시보다 이른 시간의 경우 데이터가 없어서, 전날 23시로 해야 함.
            $base_date = $now->modify('-1 day')->format('Ymd') ;    // 1일 빼고 계산.
            $base_time = '2300';
        }
        break;
    }
}

$queryParams = '?' . urlencode('serviceKey') . '=' . $weather_api_key;
$queryParams .= '&' . urlencode('pageNo') . '=' . urlencode('1');
$queryParams .= '&' . urlencode('numOfRows') . '=' . urlencode('1000');
$queryParams .= '&' . urlencode('dataType') . '=' . urlencode('JSON');
$queryParams .= '&' . urlencode('base_date') . '=' . urlencode($base_date);
$queryParams .= '&' . urlencode('base_time') . '=' . urlencode($base_time);
$queryParams .= '&' . urlencode('nx') . '=' . floor($latitude * 1.5); // 위도를 기상청 격자 좌표로 변환
$queryParams .= '&' . urlencode('ny') . '=' . floor($longitude * 1.5); // 경도를 기상청 격자 좌표로 변환

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $weather_url . $queryParams);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
curl_setopt($ch, CURLOPT_HEADER, FALSE);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');

$weather_response = curl_exec($ch);
curl_close($ch);

/*
$weather_params = array(
    'serviceKey' => $weather_api_key, // API 키
    'pageNo' => '1', // 페이지 번호
    'numOfRows' => '10', // 한 페이지 결과 수
    'dataType' => 'JSON', // 응답 데이터 타입
    'base_date' => date('Ymd'), // 기준 날짜 (오늘 날짜)
    'base_time' => '0500', // 기준 시간
    'nx' => floor($latitude * 1.5), // 위도를 기상청 격자 좌표로 변환
    'ny' => floor($longitude * 1.5) // 경도를 기상청 격자 좌표로 변환
);
$weather_response = file_get_contents($weather_url . '?' . http_build_query($weather_params)); // API 호출 및 응답 받기
*/
$weather_data = json_decode($weather_response, true); // JSON 데이터를 배열로 변환

// 찾고자 하는 카테고리 목록
$categories = ["SKY", "TMP", "REH", "PTY", "WSD"];

// 결과값을 저장할 배열
$cur_results = [
    "SKY" => null,
    "TMP" => null,
    "REH" => null,
    "PTY" => null,
    "WSD" => null
];

// 24시간 온도 값을 저장할 배열
$hourly_fcst_temp = [];
$fcst_temp_cnt = 0;

// items 배열에서 각각의 카테고리의 첫 번째 해당 항목 찾기
foreach ($weather_data['response']['body']['items']['item'] as $item) {
    $category = $item['category'];
    if (in_array($category, $categories) && $cur_results[$category] === null) {
        $cur_results[$category] = $item['fcstValue'];
    }

    if ($category == 'TMP') {
        $key = $item['fcstDate'] . $item['fcstTime'];
        $hourly_fcst_temp[$key] = $item['fcstValue']; 
        $fcst_temp_cnt++;
    }

    if ($fcst_temp_cnt == 24)
        break;

    // 모든 카테고리의 첫 번째 값을 찾으면 루프 종료
//    if (!in_array(null, $cur_results)) {
//        break;
//    }
}

$weather_class = ''; // 날씨에 따른 CSS 클래스
if (isset($cur_results['SKY'])) { // 하늘 상태인 경우
    switch ($cur_results['SKY']) {
        case '1':
            $weather_class = 'sunny'; // 맑음
            break;
        case '3':
            $weather_class = 'cloudy'; // 구름 많음
            break;
        case '4':
            $weather_class = 'rainy'; // 흐림
            break;
        default:
            $weather_class = 'default'; // 기본 상태
    }
} elseif (isset($cur_results['PTY'])) { // 강수 형태이고 비가 오는 경우
    switch ($weather_value) {
        case '1':
            $weather_class = 'rainy'; // 비
            break;
        case '2':
            $weather_class = 'rainy/snowy'; // 비/눈
            break;
        case '3':
            $weather_class = 'snowy'; // 눈
            break;
        case '5':
            $weather_class = 'rainy'; // 빗방울
            break;
        case '6':
            $weather_class = 'rainy/snowy'; // 빗방울 눈날림
            break;
        case '7':
            $weather_class = 'snowy'; // 눈날림
            break;
        default:
            $weather_class = 'default'; // 기본 상태
    }
}

// 천리안 위성 조회 API 호출
$satellite_url = "http://apis.data.go.kr/1360000/SatlitImgInfoService/getInsightSatlit"; // API URL

$now = new DateTime();  // 현재 시각을 받아 DateTime 객체 생성
$now->modify('-1 day');    // 1일 빼고 계산.
$base_date = $now->format('Ymd');   // YYYYMMDD 형식의 날짜 문자열 추출

$queryParams = '?' . urlencode('serviceKey') . '=' . $weather_api_key;
$queryParams .= '&' . urlencode('pageNo') . '=' . urlencode('1');
$queryParams .= '&' . urlencode('numOfRows') . '=' . urlencode('10');
$queryParams .= '&' . urlencode('dataType') . '=' . urlencode('JSON');
$queryParams .= '&' . urlencode('sat') . '=' . urlencode('G2');
$queryParams .= '&' . urlencode('data') . '=' . urlencode('rgbt');
$queryParams .= '&' . urlencode('area') . '=' . urlencode('ko');
$queryParams .= '&' . urlencode('time') . '=' . urlencode($base_date); 

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $satellite_url . $queryParams);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
curl_setopt($ch, CURLOPT_HEADER, FALSE);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');

$satellite_response = curl_exec($ch);
curl_close($ch);

$satellite_data = json_decode($satellite_response, true); // JSON 데이터를 배열로 변환

$satellite_img_files = $satellite_data['response']['body']['items']['item']['0']['satImgC-file'];
$satellite_img_files = trim($satellite_img_files, '[]');
$image_urls_array = explode(', ', $satellite_img_files);

// 에어코리아 대기오염정보 API 호출 
$air_quality_rt_url = "http://apis.data.go.kr/B552584/ArpltnInforInqireSvc/getCtprvnRltmMesureDnsty"; // API URL

$air_rt_queryParams = '?' . urlencode('serviceKey') . '=' . $air_quality_api_key;
$air_rt_queryParams .= '&' . urlencode('returnType') . '=' . urlencode('json');
$air_rt_queryParams .= '&' . urlencode('numOfRows') . '=' . urlencode('100');
$air_rt_queryParams .= '&' . urlencode('pageNo') . '=' . urlencode('1');
$air_rt_queryParams .= '&' . urlencode('sidoName') . '=' . urlencode('서울');
$air_rt_queryParams .= '&' . urlencode('ver') . '=' . urlencode('1.0');

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $air_quality_rt_url . $air_rt_queryParams);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
curl_setopt($ch, CURLOPT_HEADER, FALSE);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');

$response = curl_exec($ch);
curl_close($ch);

$air_quality_data = json_decode($response, true);

// 대기질 실시간 데이터 처리
$pm10Value = isset($air_quality_data['response']['body']['items'][1]['pm10Value']) ? $air_quality_data['response']['body']['items'][1]['pm10Value'] : 'No Data';
$pm25Value = isset($air_quality_data['response']['body']['items'][1]['pm25Value']) ? $air_quality_data['response']['body']['items'][1]['pm25Value'] : 'No Data';
$o3Value = isset($air_quality_data['response']['body']['items'][1]['o3Value']) ? $air_quality_data['response']['body']['items'][1]['o3Value'] : 'No Data';

// 대기질 예보통보 가져오기
$air_quality_frcst_url = "http://apis.data.go.kr/B552584/ArpltnInforInqireSvc/getMinuDustFrcstDspth";
// 현재 시각을 받아 DateTime 객체 생성
$now = new DateTime();
// 1시간 빼고 계산.
$now->modify('-1 hour');
// YYYY-MM-DD 형식의 날짜 문자열 추출
$base_date = $now->format('Y-m-d');

$frcst_queryParams = '?' . urlencode('serviceKey') . '=' . $air_quality_api_key;
$frcst_queryParams .= '&' . urlencode('returnType') . '=' . urlencode('json');
$frcst_queryParams .= '&' . urlencode('numOfRows') . '=' . urlencode('100');
$frcst_queryParams .= '&' . urlencode('pageNo') . '=' . urlencode('1');
$frcst_queryParams .= '&' . urlencode('searchDate') . '=' . urlencode($base_date);
$frcst_queryParams .= '&' . urlencode('InformCode') . '=' . urlencode('PM10');

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $air_quality_frcst_url . $frcst_queryParams);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
curl_setopt($ch, CURLOPT_HEADER, FALSE);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');

$response = curl_exec($ch);
curl_close($ch);

$air_quality_frcst_data = json_decode($response, true);

$ImageUrl1 = $air_quality_frcst_data['response']['body']['items'][0]['imageUrl1'];
$ImageUrl2 = $air_quality_frcst_data['response']['body']['items'][0]['imageUrl2'];
$ImageUrl3 = $air_quality_frcst_data['response']['body']['items'][0]['imageUrl3'];
$ImageUrl4 = $air_quality_frcst_data['response']['body']['items'][0]['imageUrl4'];
$ImageUrl5 = $air_quality_frcst_data['response']['body']['items'][0]['imageUrl5'];
$ImageUrl6 = $air_quality_frcst_data['response']['body']['items'][0]['imageUrl6'];

// 날씨 상태 확인 (예시 코드, 실제 구현시에는 API 응답에 따라 결정해야 함)
$weather_condition = $weather_data['response']['body']['items']['item'][0]['category'] ?? 'SKY'; // 날씨 상태 코드, 없으면 'SKY'
$weather_value = $weather_data['response']['body']['items']['item'][0]['fcstValue'] ?? '1'; // 날씨 상태 값, 없으면 '1'

$weather_class = ''; // 날씨에 따른 CSS 클래스
if ($weather_condition == 'SKY') { // 하늘 상태인 경우
    switch ($weather_value) {
        case '1':
            $weather_class = 'sunny'; // 맑음
            break;
        case '3':
            $weather_class = 'cloudy'; // 구름 많음
            break;
        case '4':
            $weather_class = 'rainy'; // 흐림
            break;
        default:
            $weather_class = 'default'; // 기본 상태
    }
} elseif ($weather_condition == 'PTY' && $weather_value != '0') { // 강수 형태이고 비가 오는 경우
    switch ($weather_value) {
        case '1':
            $weather_class = 'rainy'; // 비
            break;
        case '2':
            $weather_class = 'rainy/snowy'; // 비/눈
            break;
        case '3':
            $weather_class = 'snowy'; // 눈
            break;
        case '5':
            $weather_class = 'rainy'; // 빗방울
            break;
        case '6':
            $weather_class = 'rainy/snowy'; // 빗방울 눈날림
            break;
        case '7':
            $weather_class = 'snowy'; // 눈날림
            break;
        default:
            $weather_class = 'default'; // 기본 상태
    }
    $weather_class = 'rainy'; // 비
}
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8"> <!-- 문자 인코딩 설정 -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> <!-- 반응형 웹 설정 -->
    <title>미세로운 생활</title> <!-- 페이지 제목 -->
    <style>
        /* 기존 스타일 */
        body {
            font-family: 'Arial', sans-serif; /* 기본 폰트 설정 */
            margin: 0; /* 바깥 여백 제거 */
            padding: 0; /* 안쪽 여백 제거 */
            background-color: #F0F4F8; /* 배경색 설정 */
            color: #333333; /* 텍스트 색상 설정 */
            transition: background-color 0.3s, color 0.3s; /* 배경색과 텍스트 색상 전환 효과 */
        }
        
        /* 다크모드 스타일 */
        body.dark-mode {
            background-color: #1a1a1a; /* 다크모드 배경색 */
            color: #f0f0f0; /* 다크모드 텍스트 색상 */
        }
        
        .dark-mode .section {
            background-color: #2a2a2a; /* 다크모드 섹션 배경색 */
            color: #f0f0f0; /* 다크모드 섹션 텍스트 색상 */
        }
        
        .dark-mode .page-title {
            background-color: #2c3e50; /* 다크모드 페이지 제목 배경색 */
            color: #ffffff; /* 다크모드 페이지 제목 텍스트 색상 */
        }
        
        .dark-mode .current-weather {
            background-color: #1c2833; /* 다크모드 현재 날씨 섹션 배경색 */
        }
        
        .dark-mode .air-quality {
            background-color: #1e3a2e; /* 다크모드 대기질 섹션 배경색 */
        }
        
        .dark-mode .weather-map {
            background-color: #2c3e50; /* 다크모드 날씨 지도 섹션 배경색 */
        }
        
        .dark-mode .news-section {
            background-color: #1c2833; /* 다크모드 뉴스 섹션 배경색 */
        }
        
        .dark-mode .news-item {
            background-color: #2a2a2a; /* 다크모드 뉴스 아이템 배경색 */
            color: #f0f0f0; /* 다크모드 뉴스 아이템 텍스트 색상 */
        }
        
        .dark-mode .news-item h3 {
            color: #4da6ff; /* 다크모드 뉴스 아이템 제목 색상 */
        }
        
        .dark-mode .temperature {
            color: #4da6ff; /* 다크모드 온도 텍스트 색상 */
        }
        
        /* 챗봇 다크모드 스타일 */
        .dark-mode .chatbot {
            background-color: #2a2a2a; /* 다크모드 챗봇 배경색 */
            color: #f0f0f0; /* 다크모드 챗봇 텍스트 색상 */
        }
        
        .dark-mode .chatbot-header {
            background-color: #1E3A5F; /* 다크모드 챗봇 헤더 경색 */
        }
        
        .dark-mode .chatbot-input input {
            background-color: #3a3a3a; /* 다크모드 챗봇 입력 필드 배경색 */
            color: #f0f0f0; /* 다크모드 챗봇 입력 필드 텍스트 색상 */
            border-color: #4a4a4a; /* 다크모드 챗봇 입력 필드 테두리 색상 */
        }
        
        .dark-mode .chatbot-input button {
            background-color: #4da6ff; /* 다크모드 챗봇 전송 버튼 배경색 */
            color: #ffffff; /* 다크모드 챗봇 전송 버튼 텍스트 색상 */
        }
        
        /* 다크모드 토글 버튼 스타일 */
        .dark-mode-toggle {
            position: fixed; /* 고정 위치 */
            top: 20px; /* 상단에서 20px 떨어짐 */
            right: 20px; /* 우측에서 20px 떨어짐 */
            padding: 10px; /* 안쪽 여백 */
            background-color: #333; /* 배경색 */
            color: #fff; /* 텍스트 색상 */
            border: none; /* 테두리 없음 */
            border-radius: 5px; /* 모서리 둥글게 */
            cursor: pointer; /* 마우스 오버 시 커서 변경 */
            z-index: 1000; /* 다른 요소 위에 표시 */
            font-size: 14px; /* 기본 폰트 크기 설정 */
        }
        
        .dark-mode .dark-mode-toggle {
            background-color: #f0f0f0; /* 다크모드에서의 토글 버튼 배경색 */
            color: #333; /* 다크모드에서의 토글 버튼 텍스트 색상 */
        }
        
        .page-title {
            text-align: center; /* 텍스트 중앙 정렬 */
            padding: 40px 0; /* 상하 여백 */
            background-color: #3D86F5; /* 배경색 */
            color: #EFF4FA; /* 텍스트 색상 */
            margin: 0; /* 바깥 여백 제거 */
            position: relative; /* 상대 위치 설정 */
            overflow: hidden; /* 내용이 넘치면 숨김 */
            box-shadow: 0 2px 4px rgba(0,0,0,0.1); /* 그림자 효과 */
            width: 100%; /* 전체 너비 */
        }
        .page-title h1 {
            margin: 0; /* 여백 제거 */
            font-size: 2.5em; /* 폰트 크기 */
            position: relative; /* 상대 위치 설정 */
            z-index: 2; /* 겹침 순서 */
            text-shadow: 1px 1px 2px rgba(255,255,255,0.5); /* 텍스트 그림자 */
        }
        .weather-icon {
            position: absolute; /* 절대 위치 설정 */
            top: 10px; /* 상단에서 10px 떨어짐 */
            right: 10px; /* 우측에서 10px 떨어짐 */
            width: 60px; /* 너비 */
            height: 60px; /* 높이 */
            z-index: 1; /* 겹침 순서 */
        }
        .container {
            display: grid; /* 그리드 레이아웃 사용 */
            grid-template-columns: repeat(3, 1fr); /* 3열 그리드 */
            grid-gap: 20px; /* 그리드 간격 */
            padding: 20px; /* 안쪽 여백 */
            max-width: 1200px; /* 최대 너비 */
            margin: 0 auto; /* 가운데 정렬 */
        }
        .section {
            background-color: #FFFFFF; /* 배경색 */
            border-radius: 10px; /* 모서리 둥글게 */
            padding: 20px; /* 안쪽 여백 */
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); /* 그림자 효과 */
        }
        .section h2 {
            margin-top: 0; /* 상단 여백 제거 */
            border-bottom: 1px solid #E0E0E0; /* 하단 테두리 */
            padding-bottom: 10px; /* 하단 여백 */
            color: #1E3A5F; /* 텍스트 색상 */
        }
        .current-weather {
            grid-column: span 2; /* 2열 차지 */
            background-color: #E3F2FD; /* 배경색 */
        }
        .temperature {
            font-size: 48px; /* 폰트 크기 */
            font-weight: bold; /* 굵은 글씨 */
            color: #1E88E5; /* 텍스트 색상 */
        }

        .hourly-forecast, .daily-forecast {
            display: flex; /* 플렉스 레이아웃 사용 */
            justify-content: space-between; /* 요소 간 간격 균등 분배 */
            flex-direction: column; /* 플렉스 항목을 세로로 정렬 */
        }
        .hourly-forecast table {
            width: 100%;
            border-collapse: collapse;
        }
        .hourly-forecast th, .hourly-forecast td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: center;
        }
        .hourly-forecast th {
            background-color: #f2f2f2;
        }

        .forecast-item {
            text-align: center; /* 텍스트 중앙 정렬 */
        }
        .air-quality {
            background-color: #E8F5E9; /* 배경색 */
        }
        .air-quality p {
            margin: 5px 0; /* 상하 여백 */
        }
        .weather-map {
            background-color: #FFF3E0; /* 배경색 */
        }
        
        /* 챗봇 스타일 수정 */
        .chatbot {
            position: fixed; /* 고정 위치 */
            bottom: 20px; /* 하단에서 20px 떨어짐 */
            right: 20px; /* 우측에서 20px 떨어짐 */
            width: 300px; /* 너비 */
            height: 400px; /* 높이 */
            background-color: #FFFFFF; /* 배경색 */
            border-radius: 10px; /* 모서리 둥글게 */
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); /* 그림자 효과 */
            display: flex; /* 플렉스 레이아웃 사용 */
            flex-direction: column; /* 세로 방향 정렬 */
            overflow: hidden; /* 내용이 넘치면 숨김 */
            transition: all 0.3s ease; /* 전환 효과 */
        }

        .chatbot-header {
            padding: 10px; /* 안쪽 여백 */
            background-color: #1E3A5F; /* 배경색 */
            color: white; /* 텍스트 색상 */
            display: flex; /* 플렉스 레이아웃 사용 */
            justify-content: space-between; /* 요소 간 간격 균등 분배 */
            align-items: center; /* 세로 중앙 정렬 */
            cursor: pointer; /* 마우스 오버 시 커서 변경 */
        }

        /* 챗봇 최소화 스타일 수정 */
        .chatbot.minimized {
            width: auto; /* 자동 너비 */
            height: auto; /* 자동 높이 */
        }
        
        .chatbot.minimized .chatbot-content,
        .chatbot.minimized .chatbot-input,
        .chatbot.minimized .resize-handle {
            display: none; /* 최소화 시 숨김 */
        }
        
        .chatbot.minimized .chatbot-header {
            border-radius: 10px; /* 모서리 둥글게 */
            padding: 10px 15px; /* 안쪽 여백 */
        }

        .chatbot-controls {
            display: flex; /* 플렉스 레이아웃 사용 */
            align-items: center; /* 세로 중앙 정렬 */
        }
        .minimize-btn, .resize-handle {
            background: none; /* 배경 음 */
            border: none; /* 테두리 없음 */
            color: white; /* 텍스트 색상 */
            font-size: 20px; /* 폰트 크기 */
            cursor: pointer; /* 마우스 오버 시 커서 변경 */
            margin-left: 10px; /* 좌측 여백 */
        }
        .resize-handle {
            cursor: nwse-resize; /* 크기 조절 커서 */
        }
        
        /* 크기 조절 시 우측 하단에 나타나는 크기 조절 핸들 스타일 */
        .chatbot::-webkit-resizer {
            border-bottom-right-radius: 10px; /* 우측 하단 모서리 둥글게 */
        }
        
        .chatbot-content {
            flex-grow: 1; /* 남은 공간 차지 */
            overflow-y: auto; /* 세로 스크롤 */
            padding: 10px; /* 안쪽 여백 */
        }
        .chatbot-messages {
            padding: 10px; /* 안쪽 여백 */
        }
        .chatbot-input {
            display: flex; /* 플렉스 레이아웃 사용 */
            padding: 10px; /* 안쪽 여백 */
            border-top: 1px solid #E0E0E0; /* 상단 테두리 */
        }
        .chatbot-input input {
            flex-grow: 1; /* 남은 공간 차지 */
            border: 1px solid #E0E0E0; /* 테두리 */
            padding: 5px; /* 안쪽 여백 */
            margin-right: 5px; /* 우측 여백 */
        }
        .chatbot-input button {
            background-color: #1E88E5; /* 배경색 */
            color: white; /* 텍스트 색상 */
            border: none; /* 테두리 없음 */
            padding: 5px 10px; /* 안쪽 여백 */
            cursor: pointer; /* 마우스 오버 시 커서 변경 */
        }
        .chatbot.minimized {
            height: auto; /* 자동 높이 */
            width: auto; /* 자동 너비 */
        }
        
        .chatbot.minimized .chatbot-content,
        .chatbot.minimized .chatbot-input {
            display: none; /* 최소화 시 숨김 */
        }
        
        .chatbot.minimized .chatbot-header {
            border-radius: 10px; /* 모서리 둥글게 */
            padding: 10px; /* 안쪽 여백 */
            cursor: pointer; /* 마우스 오버 시 커서 변경 */
        }

        /* 뉴스 섹션 스타일 수정 */
        .news-section {
            grid-column: span 3; /* 3열 차지 */
            background-color: #E1F5FE; /* 배경색 */
        }
        .news-list {
            list-style-type: none; /* 리스트 스타일 제거 */
            padding: 0; /* 안쪽 여백 제거 */
        }
        .news-item {
            margin-bottom: 10px; /* 하단 여백 */
            padding: 10px; /* 안쪽 여백 */
            background-color: #FFFFFF; /* 배경색 */
            border-radius: 5px; /* 모서리 둥글게 */
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1); /* 그림자 효과 */
        }
        .news-item h3 {
            margin: 0 0 5px 0; /* 여백 설정 */
            color: #1E3A5F; /* 텍스트 색상 */
        }
        .news-item p {
            margin: 0; /* 여백 제거 */
            color: #666666; /* 텍스트 색상 */
        }
        .news-item a {
            text-decoration: none; /* 링크 밑줄 제거 */
            color: inherit; /* 부모 요소의 색상 상속 */
        }
        .news-item a:hover {
            text-decoration: underline; /* 호버 시 밑줄 표시 (선택사항) */
        }

        /* 기존 스타일에 추가 */
        .chatbot-header {
            display: flex; /* 플렉스 레이아웃 사용 */
            justify-content: space-between; /* 요소 간 간격 균등 분배 */
            align-items: center; /* 세로 중앙 정렬 */
        }
        .minimize-btn {
            background: none; /* 배경 없음 */
            border: none; /* 테두리 없음 */
            color: white; /* 텍스트 색상 */
            font-size: 20px; /* 폰트 크기 */
            cursor: pointer; /* 마우스 오버 시 커서 변경 */
        }
        .chatbot.minimized {
            height: auto; /* 자동 높이 */
        }
        .chatbot.minimized .chatbot-content {
            display: none; /* 최소화 시 숨김 */
        }

        .dark-mode .section h2 {
            color: #ffffff; /* 다크모드에서의 섹션 제목 색상 */
        }

        /* 모바일 환경을 위 미디어 쿼리 */
        @media (max-width: 768px) {
            .container {
                display: flex; /* 플렉스 레이아웃 사용 */
                flex-direction: column; /* 세로 향 정렬 */
                padding: 10px; /* 안쪽 여백 */
            }
            .section {
                width: 100%; /* 전체 너비 */
                margin-bottom: 10px; /* 하단 여백 */
            }
            .page-title {
                padding: 20px 0; /* 상하 여백 */
            }
            .page-title h1 {
                font-size: 2em; /* 폰트 크기 */
            }
            .chatbot {
                width: 100%; /* 전체 너비 */
                max-width: none; /* 최대 너비 제한 없음 */
                bottom: 0; /* 하단에 붙임 */
                right: 0; /* 우측에 붙임 */
                border-radius: 10px 10px 0 0; /* 상단 모서리만 둥글게 */
            }
            .dark-mode-toggle {
                top: 10px; /* 상단에서 10px 떨어짐 */
                right: 10px; /* 우측에서 10px 떨어짐 */
                padding: 5px 8px; /* 안쪽 여백 */
                font-size: 12px; /* 폰트 크기 */
            }
            
            .chatbot {
                bottom: 10px; /* 하단에서 10px 떨어짐 */
                right: 10px; /* 우측에서 10px 떨어짐 */
                width: auto; /* 자동 너비 */
                height: auto; /* 자동 높이 */
            }
            
            .chatbot.minimized {
                width: auto; /* 자동 너비 */
                height: auto; /* 자동 높이 */
            }
        }
    </style>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
    // 페이지 로드 시 위치 정보 가져오기
    window.addEventListener('load', function() {
        if ("geolocation" in navigator) {
            navigator.geolocation.getCurrentPosition(function(position) {
                var lat = position.coords.latitude;
                var lng = position.coords.longitude;
                
                // 위 정보를 서버로 전송
                fetch(window.location.pathname + '?lat=' + lat + '&lng=' + lng)
                    .then(response => response.text())
                    .then(html => {
                        document.body.innerHTML = html;
                    })
                    .catch(error => {
                        console.error("위치 정보 업데이트 실패:", error);
                    });
            }, function(error) {
                console.error("위치 정보를 가져오는데 실패했습니다:", error);
            });
        } else {
            console.log("이 브라우저에서는 위치 정보를 지원하지 않습니다.");
        }
    });

    // 원래 크기를 저장할 변수 추가
    var originalWidth = '300px';
    var originalHeight = '400px';

    // 챗봇 최소화/최대화 토글 함수
    function toggleChatbot() {
        var chatbot = document.getElementById('chatbot');
        chatbot.classList.toggle('minimized');
        var minimizeBtn = chatbot.querySelector('.minimize-btn');
        if (chatbot.classList.contains('minimized')) {
            // 최소화 전 크기 저장
            originalWidth = chatbot.style.width || '300px';
            originalHeight = chatbot.style.height || '400px';
            minimizeBtn.textContent = '+';
            chatbot.style.width = 'auto';
            chatbot.style.height = 'auto';
        } else {
            minimizeBtn.textContent = '-';
            // 원래 크기로 복원
            chatbot.style.width = originalWidth;
            chatbot.style.height = originalHeight;
        }
    }

    // 챗봇 헤더 클릭 이벤트 수정
    document.querySelector('.chatbot-header').addEventListener('click', function(e) {
        if (!e.target.classList.contains('minimize-btn')) {
            toggleChatbot();
        }
    });

    // 챗봇 크기 조절 함수
    function initResize(e) {
        window.addEventListener('mousemove', resize);
        window.addEventListener('mouseup', stopResize);
    }

    function resize(e) {
        const chatbot = document.getElementById('chatbot');
        chatbot.style.width = (e.clientX - chatbot.offsetLeft) + 'px';
        chatbot.style.height = (e.clientY - chatbot.offsetTop) + 'px';
    }

    function stopResize() {
        window.removeEventListener('mousemove', resize);
        // 크기 조절 후 현재 크기를 원래 크기로 저장
        var chatbot = document.getElementById('chatbot');
        originalWidth = chatbot.style.width;
        originalHeight = chatbot.style.height;
    }

    // 메시지 전송 함수
    function sendMessage() {
        var input = document.getElementById('chatbotInput');
        var message = input.value.trim();
        if (message !== '') {
            var messagesContainer = document.getElementById('chatbotMessages');
            messagesContainer.innerHTML += '<p><strong>사용자:</strong> ' + message + '</p>';
            input.value = '';
            messagesContainer.scrollTop = messagesContainer.scrollHeight;

            // AJAX를 사용하여 서버에 메시지 전송
            $.ajax({
                url: window.location.href,
                method: 'POST',
                data: { message: message },
                dataType: 'json',
                success: function(data) {
                    messagesContainer.innerHTML += '<p><strong>챗봇:</strong> ' + data.response + '</p>';
                    messagesContainer.scrollTop = messagesContainer.scrollHeight;
                },
                error: function() {
                    messagesContainer.innerHTML += '<p><strong>챗봇:</strong> 죄송합니다. 오류가 발생했습니다.</p>';
                    messagesContainer.scrollTop = messagesContainer.scrollHeight;
                }
            });
        }
    }

    // 엔터 키 이벤트 리스너
    document.getElementById('chatbotInput').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault(); // 기본 동작 방지 (폼 제출 방지)
            sendMessage();
        }
    });
    
    // 다크모드 토글 함수
    function toggleDarkMode() {
        document.body.classList.toggle('dark-mode');
        localStorage.setItem('darkMode', document.body.classList.contains('dark-mode'));
        updateChatbotStyle(); // 챗봇 스타일 업데이트
        updateToggleButtonText(); // 토글 버튼 텍스트 업데이트
    }
    
    // 챗봇 스타일 업데이트 함수
    function updateChatbotStyle() {
        var chatbot = document.getElementById('chatbot');
        if (document.body.classList.contains('dark-mode')) {
            chatbot.style.backgroundColor = '#2a2a2a';
            chatbot.style.color = '#f0f0f0';
        } else {
            chatbot.style.backgroundColor = '#FFFFFF';
            chatbot.style.color = '#333333';
        }
    }
    
    // 토글 버튼 텍스트 업데이트 함수
    function updateToggleButtonText() {
        var toggleButton = document.querySelector('.dark-mode-toggle');
        if (document.body.classList.contains('dark-mode')) {
            toggleButton.textContent = '라이트모드';
        } else {
            toggleButton.textContent = '다크모드';
        }
    }
    
    // 페이지 로드 시 다크모드 상태 확인
    document.addEventListener('DOMContentLoaded', (event) => {
        if (localStorage.getItem('darkMode') === 'true') {
            document.body.classList.add('dark-mode');
        }
        updateChatbotStyle(); // 초기 챗봇 스타일 설정
        updateToggleButtonText(); // 초기 토글 버튼 텍스트 설정
        
        // 모바 환경 확인 및 챗봇 최소화
        if (window.innerWidth <= 768) {
            minimizeChatbot();
        }
    });

    // 챗봇 최소화 함수
    function minimizeChatbot() {
        var chatbot = document.getElementById('chatbot');
        chatbot.classList.add('minimized');
        var minimizeBtn = chatbot.querySelector('.minimize-btn');
        minimizeBtn.textContent = '+';
    }

    // 윈도우 리사이즈 이벤트 리스너 추가
    window.addEventListener('resize', function() {
        var chatbot = document.getElementById('chatbot');
        if (window.innerWidth <= 768 && !chatbot.classList.contains('minimized')) {
            minimizeChatbot();
        }
    });
    </script>
</head>
<body>
    <button class="dark-mode-toggle" onclick="toggleDarkMode()">다크모드</button>
    
    <div class="page-title <?php echo $weather_class; ?>"> <!-- 날씨에 따른 클래스 적용 -->
        <h1>미세로운 생활</h1> <!-- 페이지 제목 -->
        <div class="weather-icon"></div> <!-- 날씨 아이콘 -->
    </div>
    
    <!-- 메인 컨테이너 -->
    <div class="container">
        <!-- 현재 날씨 섹션 -->
        <section class="section current-weather">
        <h2>현재 날씨</h2> <!-- 섹션 제목 -->
            <p>위치: <?php echo $latitude, ', ', $longitude; ?></p> <!-- 위치 정보 표시 -->
            <p>온도: <span class="temperature"><?php echo isset($cur_results['TMP']) ? $cur_results['TMP'] : 'No Data'; ?>℃</span></p> <!-- 온도 표시 -->
            <p>습도: <?php echo isset($cur_results['REH']) ? $cur_results['REH'] : 'No Data'; ?>%</p> <!-- 습도 표시 -->
            <p>풍속: <?php echo isset($cur_results['WSD']) ? $cur_results['WSD'] : 'No Data'; ?>m/s</p> <!-- 풍속 표시 -->
        </section>

        <!-- 대기질 정보 섹션 -->
        <section class="section air-quality">
        <h2>대기질 정보</h2> <!-- 섹션 제목 -->
            <p>PM10: <?php echo $pm10Value; ?>㎍/㎥</p> <!-- PM10 수치 표시 -->
            <p>PM2.5: <?php echo $pm25Value; ?>㎍/㎥</p> <!-- PM2.5 수치 표시 -->
            <p>O3: <?php echo $o3Value; ?>ppm</p> <!-- O3 수치 표시 -->
        </section>

        <section class="section hourly-forecast">
            <h2>시간별 예보</h2>
            <table>
                <thead>
                    <tr>
                        <th>날짜 + 시간</th>
                        <th>온도 (℃)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($hourly_fcst_temp as $datetime => $temp) : ?>
                        <tr>
                            <td><?php echo $datetime; ?></td>
                            <td><?php echo $temp; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>

        <section class="section daily-forecast">
            <h2>주간 예보</h2>
            <p>주간 예보 정보는 준비 중입니다.</p>
        </section>

        <section class="section weather-map">
            <h2>날씨 지도</h2>
            <div class="row my-4">
                <!-- 첫 번째 이미지 세트 -->
                <div class="col-md-4">
                    <img src="<?php echo $image_urls_array[0]; ?>" class="img-fluid" alt="Image 1" style="width: 100%; height: auto;">
                </div>
            </div>
        </section>

        <!-- 뉴스 섹션 추가 -->
        <section class="section news-section">
            <h2>날씨 및 미세먼지 관련 뉴스</h2>
            <ul class="news-list">
                <?php
                $apiKey = 'd570ffb039ae4aa1a8f889662890d2b0';
                $query = urlencode('날씨 OR 기상 OR 기온 OR 강수 OR 폭염 OR 한파 OR 태풍 OR 장마 OR 눈 OR 비 OR 바람 OR 습도 OR 미세먼지 OR 초미세먼지 OR PM10 OR PM2.5 OR 대기질 OR 황사 OR 공기');
                $from = date('Y-m-d', strtotime('-7 days'));
                $to = date('Y-m-d');
                $sortBy = 'publishedAt';
                $language = 'ko';
                $pageSize = 20;

                $endpoint = "https://newsapi.org/v2/everything?q={$query}&from={$from}&to={$to}&sortBy={$sortBy}&language={$language}&pageSize={$pageSize}&apiKey={$apiKey}";

                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $endpoint);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_USERAGENT, 'WeatherNewsApp/1.0');
                $response = curl_exec($ch);
                curl_close($ch);

                $news_data = json_decode($response, true);

                if ($news_data && isset($news_data['articles'])) {
                    $displayed_count = 0;
                    $allowed_keywords = array('날씨', '기상', '기온', '강수', '폭염', '한파', '태풍', '장마', '눈', '비', '바람', '습도', '미세먼지', '초미세먼지', 'PM10', 'PM2.5', '대기질', '황사', '공기 오염');
                    $forbidden_keywords = array('사설', '정치', '경향', '경제', '사회', '문화', '스포츠', '연예', '금융', '주식', '증권', '부동산', '환율', '무역', '소비자 물가', '투자', '소비', '교육', '복지', '노동', '범죄', '교통', '주택', '청년', '성폭력', '대통령', '국회', '선거', '정당', '외교', '법원', '정부', '법안', '의원', '국제', '분쟁', '유엔', '전쟁', '수출', '수입', '관세', '세계', '난민');

                    foreach ($news_data['articles'] as $item) {
                        $is_relevant = false;
                        $is_forbidden = false;

                        foreach ($allowed_keywords as $keyword) {
                            if (mb_stripos($item['title'], $keyword) !== false || 
                                mb_stripos($item['description'], $keyword) !== false) {
                                $is_relevant = true;
                                break;
                            }
                        }

                        foreach ($forbidden_keywords as $keyword) {
                            if (mb_stripos($item['title'], $keyword) !== false || 
                                mb_stripos($item['description'], $keyword) !== false) {
                                $is_forbidden = true;
                                break;
                            }
                        }

                        if ($is_relevant && !$is_forbidden) {
                            echo "<li class='news-item'>";
                            echo "<a href='{$item['url']}' target='_blank'>";
                            echo "<h3>{$item['title']}</h3>";
                            echo "<p>{$item['description']}</p>";
                            echo "<p>출처: {$item['source']['name']}, 게시일: " . date('Y-m-d H:i', strtotime($item['publishedAt'])) . "</p>";
                            echo "</a>";
                            echo "</li>";
                            
                            $displayed_count++;
                            if ($displayed_count >= 3) break;
                        }
                    }
                    if ($displayed_count == 0) {
                        echo "<li>관련 뉴스를 찾을 수 없습니다.</li>";
                    }
                } else {
                    echo "<li>뉴스를 불러오는 데 실패했습니다.</li>";
                }
                ?>
            </ul>
        </section>
    </div>

    <!-- 챗봇 -->
    <div class="chatbot" id="chatbot">
        <div class="chatbot-header">
            날씨 챗봇
            <div class="chatbot-controls">
                <button class="resize-handle" onmousedown="initResize(event)">⇲</button>
                <button class="minimize-btn" onclick="toggleChatbot()">-</button>
            </div>
        </div>
        <div class="chatbot-content"> <!-- 챗봇 내용 -->
            <div class="chatbot-messages" id="chatbotMessages"></div> <!-- 메시지 표시 영역 -->
        </div>
        <div class="chatbot-input"> <!-- 챗봇 입력 영역 -->
            <input type="text" id="chatbotInput" placeholder="메시지를 입력하세요..."> <!-- 메시지 입력 필드 -->
            <button onclick="sendMessage()">전송</button> <!-- 전송 버튼 -->
        </div>
    </div>
</body>
</html>