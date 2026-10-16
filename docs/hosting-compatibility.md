# Совместимость Fornex

Проверено реальным временным PHP-скриптом на edu.china.kg 16 октября 2026:

- PHP 8.1.34 — требуется переключение домена на PHP 8.3 (Laravel 12 требует 8.2+).
- mbstring, PDO MySQL, DOM, ctype, fileinfo, tokenizer и OpenSSL установлены.
- ZipArchive доступен.
- Приватный родительский каталог /home/n66843 доступен PHP для записи.
- Проверенный в отдельной временной папке обработчик PHP 8.3 через AddType в .htaccess не изменил runtime: по-прежнему 8.1.34. Основной сайт этим тестом не изменялся.
- Все временные диагностические файлы и папки удалены после проверки.

Ранее сообщена MySQL 5.7.44-cll-lve, DB_HOST=localhost. Laravel 12 поддерживает MySQL 5.7+; CI приложения успешно прошёл на 5.7 и 8.0, включая конкурентное бронирование.

Смена PHP: cPanel → MultiPHP Manager → только edu.china.kg → PHP 8.3 → Apply. Затем можно повторить Check Fornex runtime в GitHub Actions. Если домен использует PHP-FPM, .htaccess не переключает версию — нужна панель.

Документация:
- https://laravel.com/docs/12.x/releases
- https://laravel.com/docs/12.x/database
- https://support.cpanel.net/hc/en-us/articles/24112641934103-How-to-set-a-different-PHP-version-per-directory

FTP: hostch01.fornex.org:21 с проверкой TLS. Изолированный корень / соответствует /home/n66843/public_html/edu.china.kg. Секреты не хранятся в репозитории.
