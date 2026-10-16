import html
import http.cookiejar
import json
import re
import secrets
import urllib.error
import urllib.parse
import urllib.request

base = 'https://edu.china.kg'
jar = http.cookiejar.CookieJar()
version = None


class NoRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self, *args, **kwargs):
        return None


opener = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(jar), NoRedirect())


def call(path, data=None, method='GET'):
    headers = {'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html,application/xhtml+xml', 'Referer': base+'/dashboard'}
    if version is not None:
        headers.update({'X-Inertia': 'true', 'X-Inertia-Version': version})
    for cookie in jar:
        if cookie.name == 'XSRF-TOKEN':
            headers['X-XSRF-TOKEN'] = urllib.parse.unquote(cookie.value)
    if data is not None:
        headers['Content-Type'] = 'application/json'
    request = urllib.request.Request(base+path, data=json.dumps(data).encode() if data is not None else None,
                                     headers=headers, method=method)
    try:
        with opener.open(request, timeout=30) as response:
            return response.status, response.read()
    except urllib.error.HTTPError as error:
        return error.code, error.read()


marker = secrets.token_hex(10)
email = 'master-check-'+marker+'@example.test'
slug = 'master-check-'+marker
password = secrets.token_urlsafe(24)
with open('/tmp/test-identity.json', 'w') as file:
    json.dump({'email': email, 'slug': slug}, file)
code, initial = call('/register')
assert code == 200
page = json.loads(html.unescape(re.search(r'data-page="([^"]+)"', initial.decode()).group(1)))
version = page['version']
code, _ = call('/register', {'name': 'Проверка расписания', 'email': email, 'slug': slug,
                           'password': password, 'password_confirmation': password}, 'POST')
assert code in (302, 303), 'Test registration failed: '+str(code)
code, body = call('/dashboard')
assert code == 200 and json.loads(body)['component'] == 'Dashboard'
hours = [{'weekday': 1, 'starts_at': '09:00', 'ends_at': '18:00'},
         {'weekday': 2, 'starts_at': '10:00', 'ends_at': '19:00'}]
code, _ = call('/schedule', {'hours': hours}, 'POST')
print('Schedule POST response:', code)
assert code in (302, 303)
code, body = call('/dashboard')
assert code == 200
page = json.loads(body)
assert page['props'].get('errors', {}) == {}, page['props'].get('errors')
assert page['props']['hours'] == hours, 'Saved schedule did not survive reload'
print('Initial schedule persisted across reload')
hours = [{'weekday': 2, 'starts_at': '11:30', 'ends_at': '20:15'}]
code, _ = call('/schedule', {'hours': hours}, 'POST')
assert code in (302, 303)
code, body = call('/dashboard')
assert json.loads(body)['props']['hours'] == hours, 'Edited schedule did not survive reload'
print('Changed times and removed day persisted')
code, _ = call('/schedule', {'hours': []}, 'POST')
assert code in (302, 303)
code, body = call('/dashboard')
assert json.loads(body)['props']['hours'] == []
print('All days can be set to non-working')
