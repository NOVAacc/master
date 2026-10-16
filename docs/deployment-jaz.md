# Серверная публикация на Fornex

Статическое демо уже публикуется отдельным workflow. Следующие шаги относятся к Laravel-приложению и не выполняются workflow демо.

1. Подтвердить PHP 8.2+ для домена и CLI, расширения mbstring, PDO MySQL, XML/DOM, ctype, fileinfo, tokenizer, OpenSSL; доступ к Composer/SSH либо загрузке собранного release.
2. Разместить приложение в приватном каталоге, например `/home/n66843/master-app`. Корень домена должен указывать только на его `public/`. Существующий FTP-аккаунт изолирован каталогом демо и не даёт доступа к приватному каталогу.
3. Создать приватный `.env`: APP_ENV=production, APP_DEBUG=false, APP_URL=https://edu.china.kg, APP_KEY, DB_CONNECTION=mysql, DB_HOST=localhost и фактические DB_*; SESSION_SECURE_COOKIE=true. Ключ генерируется один раз и сохраняется при следующих deploy.
4. `composer install --no-dev --prefer-dist --optimize-autoloader`; `npm ci && npm run build`. В production не размещать node_modules и tests.
5. Сделать резервную копию базы и предыдущего release. Проверить новые миграции на тестовой копии. Только затем выполнить `php artisan migrate --force` и `php artisan optimize`. Storage и bootstrap/cache должны быть доступны для записи PHP.
6. Проверить HTTPS, `/up`, регистрацию, вход, создание услуг/расписания, публичную запись, приватность и недоступность `.env`, composer.json и storage/logs из веба. При ошибке переключить release обратно; не откатывать данные без оценки новых записей.

Нельзя заменить статический index.html на Laravel index.php без подготовленной серверной конфигурации: это ещё не deploy приложения. Не загружать весь репозиторий в document root.
