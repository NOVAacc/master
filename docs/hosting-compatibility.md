# Проверенная среда Fornex

16 октября 2026 на edu.china.kg подтверждён PHP 8.3.33, SAPI litespeed. Установлены mbstring, PDO MySQL, DOM, ctype, fileinfo, tokenizer, OpenSSL и ZipArchive. Приватный каталог /home/n66843 доступен PHP для записи.

В cPanel был выбран ea-php83, но фактически использовался PHP 8.1.34. Временный тест показал, что нужен именно обработчик LiteSpeed:

```apache
AddHandler application/x-httpd-ea-php83___lsphp .php .phtml
```

Он проверен в отдельной папке, затем добавлен к корню с проверкой сайта и возможностью восстановления прежнего .htaccess. Временные диагностические файлы удалены.

MySQL ранее сообщён как 5.7.44-cll-lve, host localhost. Laravel 12 поддерживает MySQL 5.7+, PHP 8.2+. CI приложения проходит на MySQL 5.7 и 8.0, включая конкурентное бронирование. Установка/откат релиза отдельно проверяются на одноразовой MySQL 8.0.

Источники:
- https://laravel.com/docs/12.x/releases
- https://laravel.com/docs/12.x/database
- https://fornex.com/help/processing-php-html/
- https://docs.litespeedtech.com/lsws/cp/cpanel/php/

FTP-корень / соответствует /home/n66843/public_html/edu.china.kg. FTPS hostch01.fornex.org:21 с проверкой TLS.
