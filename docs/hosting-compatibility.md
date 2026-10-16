# Совместимость Fornex

Ранее сообщены PHP 8.1 (позже возможность PHP 8.3), MySQL 5.7.44-cll-lve и DB_HOST=localhost. Фактическую версию PHP для edu.china.kg нужно подтвердить перед серверным deploy.

Laravel 12 требует PHP 8.2+ и поддерживает MySQL 5.7+. Прежнее утверждение в этом проекте о требовании MySQL 8 было ошибочным. Документация:
- https://laravel.com/docs/12.x/releases
- https://laravel.com/docs/12.x/database

CI запускается на PHP 8.3 с MySQL 5.7 и 8.0. MySQL 5.7 устарел; обновление предпочтительно, но не является обязательным для текущего кода.

Демо опубликовано по FTPS hostch01.fornex.org:21, TLS проверяется. FTP-корень `/` должен соответствовать `/home/n66843/public_html/edu.china.kg`. Секреты не хранятся в репозитории.
