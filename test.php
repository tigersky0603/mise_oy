<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
 
        <title>PHP 기본 동작</title>
    </head>
    <body>
        <?php
        /* 1. PHP 기본 동작
         * PHP는 .php 파일에 작성을 한다.
         * .php이나, 기본적으로는 html 언어로 작성하며, <?php ?> 내부에서만 php 코드를 작성한다.
        */
 
        // 2. 주석은 일반 프로그래밍 언어들과 같다.
 
        // 3. 출력
        echo '<h1>출력 테스트</h1>';
        print('print로 출력해볼까</br>');
        echo 'echo로 출력해볼까</br>'; // 출력문은 echo를 주로 사용함
        echo "따옴표와 쌍따옴표는 같은 의미입니다<br/>";
        echo '문자를 합치는것은 +가 아니라 .입니다'.'<br/><br/>'; // +는 덧셈 연산자로만 사용하기 위해 . 기호로 문자열 합침
 
        /* 4. 변수
         * 기본 선언 : $변수명 = 값;
         * Javascript처럼 자동으로 자료형을 결정함.
        */
        echo '<h1>변수 테스트</h1>';
        $a = 1;
        echo $a.'<br/>'; // 변수를 사용할땐 언제나 앞에 $기호를 붙여야 함.
        $b = 2;
        echo $a . $b . '<br/><br/>'; // 변수끼리도 . 기호로 이어 출력 가능
 
        // 5. 배열
        $arr = array(1, 2, 3, 4); // 기본 선언
        $arr2 = [1, 2, 3, 4]; // PHP5.4 버전 이후부터는 이렇게도 선언 가능
        $strArr = array('a' => 10); // key를 숫자가 아닌 문자로도 지정하여 Map 비슷하게 사용 가능
 
        array_push($arr, 5); // 새로운 값 추가(array_로 시작하는 배열 관련 함수들이 존재)
        unset($arr[0]); // 0번째 인덱스 제거(제거 후 0번째 인덱스에 값은 비어있으나, 공간은 차지하고 있음=>당겨지지 않음)
 
        // 6. type과 값 출력
        echo '<h1>타입 테스트</h1>';
        var_dump($arr); echo '<br/>'; // 출력 결과 : array(4) { [1]=> int(2) [2]=> int(3) [3]=> int(4) [4]=> int(5) }
        var_dump($a); echo '<br/>'; // 출력 결과 : int(1)
 
        // 7. 비교 연산자
        echo '<h1>비교 연산자 테스트</h1>';
        $num = 5;
        $strNum = "5";
 
        var_dump($num == $strNum); echo "</br>"; // 값 비교
        var_dump($num === $strNum); echo "</br>"; // 값, type 비교
        var_dump($num != $strNum); echo "</br>"; // 값이 동일하지 않은지
        var_dump($num <> $strNum); echo "</br>"; // !=와 동일
        var_dump($num !== $strNum); echo "</br>"; // 값, type이 동일하지 않은지 비교
        var_dump($num > $strNum); echo "</br>"; // 큰지
        var_dump($num >= $strNum); echo "</br>"; // 크거나 같은지
        /* 출력 결과
         * bool(true)
         * bool(false)
         * bool(false)
         * bool(false)
         * bool(true)
         * bool(false)
         * bool(true)
        */
 
        // 8. 조건문
        echo '<h1>조건문 테스트</h1>';
        $ifNum = 0;
        if ($ifNum == 0) {
            echo "ifNum 변수는 0이다";
        } elseif ($ifNum == 1) {
            echo "ifNum 변수는 1이다";
        } else {
            echo 'ifNum 변수는 0도 1도 아니다';
        }
 
        // 9. 반복문
        echo '<h1>반복문 테스트</h1>';
        $repeatArr1 = array(1, 2, 3, 4, 5);
        foreach($repeatArr1 as $i) {
            echo $i." ";
        }
        echo "<br/>";
 
        $repeatArr2 = array('a'=>"hi, ", 'b'=>"I'm ", 'c'=>"so ", 'd'=>"happy.");
        foreach($repeatArr2 as $key=>$value) {
            echo "$value";
        }
        echo "<br/>";
 
        // 10. 함수
        echo '<h1>함수 테스트</h1>';
        function hello() {
            echo "Hello PHP!<br/>";
        }
        hello();
 
        function add($num1, $num2) {
            return $num1 + $num2;
        }
        echo add(1, 2).'<br/>';
 
        function defaultValueFunction($song="Say yes") {
            echo "제가 가장 좋아하는 노래는 $song 입니다<br/>";
        }
        defaultValueFunction(); // 출력 : 제가 가장 좋아하는 노래는 Say yes 입니다
        defaultValueFunction("Swing baby"); // 출력 : 제가 가장 좋아하는 노래는 Swing baby 입니다
        ?>
    </body>
</html>