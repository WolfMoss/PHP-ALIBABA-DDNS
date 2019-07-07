<?php
error_reporting(0);
error_reporting(E_ALL & ~E_NOTICE);
require __DIR__ . '/vendor/autoload.php';
use AlibabaCloud\Client\AlibabaCloud;
use AlibabaCloud\Client\Exception\ClientException;
use AlibabaCloud\Client\Exception\ServerException;
//常量设置
define("RRKey","axiba"); //要修改的主机记录
// php /volume1/9G/www/DDNS/DDNS.php
// chmod +x /volume1/9G/www/DDNS/DDNS.php

//参与主逻辑的方法：
//GetIP() //获取本机公网IP
//GetAlibabaIP("axiba") //获取当前阿里云指定前缀的域名解析记录
//IsSame($myip) //判断当前公网IP是否和上次修改的解析地址一样
//SetIP($ip, $Record, $RRKey) //设置IP，参数1=要设置的IP；参数2=获取当前解析的RecordID；参数3=要修改的主机记录

//运行主逻辑
DoMain();
//主逻辑
function DoMain()
{
    echo 1;
    $myip = GetIP(); //本机公网IP

    //判断当前公网IP是否和上次修改的解析地址
    if(IsSame($myip)){
        echo "当前公网IP和上次修改的解析地址一样";
        return "当前公网IP和上次修改的解析地址一样";
    }
    echo 2;
    //公网IP和上次修改的解析地址不一致，再进行获取当前解析地址
    $alibabaArr = GetAlibabaIP(); //获取当前解析记录
    $alibabaIP = $alibabaArr["DomainRecords"]["Record"][0]["Value"]; //当前解析IP
    $alibabaRecordId = $alibabaArr["DomainRecords"]["Record"][0]["RecordId"]; //当前解析RecordId
    echo 3;
    //公网IP和当前解析地址的比较
    if($alibabaIP == $myip){
        return "公网IP和当前解析地址一样";
    }
    echo 4;
    //如果公网IP和当前解析不一样，则修改解析记录
    return SetIP($myip, $alibabaRecordId, RRKey);
}

//获取公网IP主方法，随机使用接口获取IP，直到获取到为止
function GetIP()
{
    //URL接口数组
    $urlArr = array(
        'http://checkip.dyndns.com/',
        'ns1.dnspod.net:6666',
        'http://ip.chinaz.com/getip.aspx',
        'http://www.net.cn/static/customercare/yourip.asp',
        'https://ip.cn/',
        'http://www.ip168.com/json.do?view=myipaddress',
        'http://pv.sohu.com/cityjson',
        'http://pv.sohu.com/cityjson',
        'http://ip.taobao.com/service/getIpInfo.php?ip=myip',
        'http://2018.ip138.com/ic.asp'
    );
    //随机用一个接口，直到获取到返回值为止
    while (!isset($ip) || empty($ip)) {
        $ip = GetIPCommon($urlArr[rand(0, 9)]);
    }
    //提取出接口返回的字符串中的IP地址
    $preg = '/(\d{1,3}\.){3}\d{1,3}/';
    preg_match($preg, $ip, $matches);
    //返回最终的公网IP地址
    return $matches[0];
}
//使用IP接口
function GetIPCommon($url)
{
    try {
        $my_curl = curl_init();
        curl_setopt($my_curl, CURLOPT_URL, $url);
        curl_setopt($my_curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($my_curl, CURLOPT_TIMEOUT, 20);
        $ip = curl_exec($my_curl);
        curl_close($my_curl);
        if (!isset($ip) || empty($ip)) {
            return null;
        }
        return $ip;
    } catch (\Throwable $th) {
        return null;
    }
}


//获取阿里云指定二级域名的当前解析记录
function GetAlibabaIP()
{
    AlibabaCloud::accessKeyClient('LTAImwlA4y6LSkxO', 'w4UTIh77QpNwOEI7gRmUVkcAMJ3tmP')
        ->regionId('cn-hangzhou') // replace regionId as you need
        ->asDefaultClient();
    try {
        $result = AlibabaCloud::rpc()
            ->product('Alidns')
            // ->scheme('https') // https | http
            ->version('2015-01-09')
            ->action('DescribeDomainRecords')
            ->method('POST')
            ->options([
                'query' => [
                    'DomainName' => "idnmd.top",
                    'PageNumber' => "1",
                    'RRKeyWord' => RRKey,
                ],
            ])
            ->request();
        $arr = $result->toArray();
        return $arr;
        //return $arr["DomainRecords"]["Record"][0]["Value"];
    } catch (ClientException $e) {
        echo $e->getErrorMessage() . PHP_EOL;
    } catch (ServerException $e) {
        echo $e->getErrorMessage() . PHP_EOL;
    }
}

//判断指定IP是否和上次修改的解析地址一样(如果在修改方法中，成功修改了解析地址，则修改的解析地址将存入ip.txt)
function IsSame($ip){
    $file_path = dirname(__FILE__)."/ip.txt";
    echo $file_path;
    if(file_exists($file_path)){
    $str = file_get_contents($file_path);//将整个文件内容读入到一个字符串中
    $str = str_replace("\r\n","<br />",$str);
    }
    // $con = mysqli_connect('127.0.0.1','root','ilikecs123!','test','3307');
    // // 检测连接
    // if ($con->connect_error) {
    //     die("连接失败: " . $con->connect_error);
    // } 

    // $result = $con->query("SELECT * FROM ip");
    // if ($result->num_rows > 0) {
    //     // 输出数据
    //     while($row = $result->fetch_assoc()) {
    //         $str = $row["ip"];
    //     }
    // } else {
    //     echo "0 结果";
    // }

    if($str == $ip)
    {
        echo $str;
        echo $ip;
        return true;
    }
    else
    {
        return false;
    }
    // $con->close();
}

//调用阿里云API修改解析记录
function APIIP($ip, $Record)
{
    AlibabaCloud::accessKeyClient('LTAImwlA4y6LSkxO', 'w4UTIh77QpNwOEI7gRmUVkcAMJ3tmP')
        ->regionId('cn-hangzhou') // replace regionId as you need
        ->asDefaultClient();

    try {
        $result = AlibabaCloud::rpc()
            ->product('Alidns')
            // ->scheme('https') // https | http
            ->version('2015-01-09')
            ->action('UpdateDomainRecord')
            ->method('POST')
            ->options([
                'query' => [
                    'RecordId' => $Record,
                    'RR' => RRKey,
                    'Type' => "A",
                    'Value' => $ip,
                ],
            ])
            ->request();
        return $result->toArray();
    } catch (ClientException $e) {
        echo $e->getErrorMessage() . PHP_EOL;
    } catch (ServerException $e) {
        echo $e->getErrorMessage() . PHP_EOL;
    }
}
//修改解析记录并保存进ip.txt
function SetIP($ip, $Record){
    if (count(APIIP($ip, $Record)) > 1) {
        // $con=mysqli_connect("127.0.0.1","root","ilikecs123!","test",'3307');
        // // 检测连接
        // if (mysqli_connect_errno())
        // {
        //     echo "连接失败: " . mysqli_connect_error();
        // }

        // mysqli_query($con,"UPDATE ip SET ip=$ip");

        // mysqli_close($con);
        echo 5;
        $file_path = dirname(__FILE__)."/ip.txt";
        $fp = fopen($file_path, "w"); //文件被清空后再写入
        if ($fp) {
            $flag = fwrite($fp, $ip);
            if (!$flag) {
                echo "写入文件失败<br>";
            }
            else{
                echo $ip;
            }
        }
        fclose($fp);

        $myfile = fopen($file_path, "w") or die("Unable to open file!");
        fwrite($myfile, $ip);
        echo $file_path;
        fclose($myfile);
    }
    else {
        echo 6;
        return false;
    }
    
}
?>
