# php-curl

php 网络请求库

## 安装

~~~
composer require xpf0000/php-curl
~~~

## 使用

链式操作
```php
$curl = new Curl('https://www.google.com'); // 初始化一个实例
$curl = Curl::init('https://www.google.com'); // 初始化一个实例 使用类方法
$curl->head(['token'=>'aaabbbccc'])
     ->data(['a'=>0,'b'=>1])
     ->post(); // 返回字符串数据
$curl->get(CURL_RETURN_JSON); // 把返回字符串格式化成JSON
```

