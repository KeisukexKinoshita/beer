#!/usr/bin/env python3
"""新サイトのページを PHP 組み込みサーバ(php -S)に実 HTTP で叩き、本文・ステータス・応答ヘッダを保存する。
CLI の PHP は header() の内容を保持しない(headers_list() が常に空)ため、ヘッダの比較には実サーバが要る。
  http_page.py <page> <params.json> <body-out> <head-out>
params.json: get / post / cookies / host / uri / method (tests/runner/exec_page.php と同じ意味)
終了コード: 本文に PHP の Fatal があるか HTTP 5xx なら 255、それ以外 0(.exit に記録される)
head-out: 1行目 "Status: <code>"、以降 "Name: value"。Date / Connection / X-Powered-By は除く(毎回変わる)
"""
import http.client, json, os, socket, subprocess, sys, time, urllib.parse

page, pj, body_out, head_out = sys.argv[1:5]
root = os.path.abspath(os.path.join(os.path.dirname(__file__), '..', 'sandbox'))
p = json.load(open(pj))
get, post = p.get('get') or {}, p.get('post') or {}
method = p.get('method', 'GET')
uri = p.get('uri') or ('/' + page + ('?' + urllib.parse.urlencode(get) if get else ''))
host = p.get('host', 'beer.test')
cookies = p.get('cookies') or {}

s = socket.socket(); s.bind(('127.0.0.1', 0)); port = s.getsockname()[1]; s.close()
srv = subprocess.Popen(['php', '-d', 'display_errors=1', '-d', 'error_reporting=-1', '-d', 'log_errors=0',
                        '-d', 'html_errors=0', '-S', f'127.0.0.1:{port}', '-t', root],
                       stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL)
try:
    for _ in range(100):
        try:
            socket.create_connection(('127.0.0.1', port), 0.2).close(); break
        except OSError:
            time.sleep(0.05)
    c = http.client.HTTPConnection('127.0.0.1', port, timeout=30)
    headers = {'Host': host}
    if cookies:
        headers['Cookie'] = '; '.join(f'{k}={v}' for k, v in cookies.items())
    body = None
    if method == 'POST':
        body = urllib.parse.urlencode(post)
        headers['Content-Type'] = 'application/x-www-form-urlencoded'
    c.request(method, uri, body=body, headers=headers)
    r = c.getresponse()
    data = r.read()
    skip = {'date', 'connection', 'x-powered-by', 'content-length', 'host'}
    lines = [f'Status: {r.status}'] + [f'{k}: {v}' for k, v in r.getheaders() if k.lower() not in skip]
    open(head_out, 'w').write('\n'.join(lines) + '\n')
    open(body_out, 'wb').write(data)
    txt = data.decode('utf-8', 'replace')
    bad = r.status >= 500 or any(l.startswith('Fatal error') or l.startswith('PHP Fatal') for l in txt.splitlines())
    sys.exit(255 if bad else 0)
finally:
    srv.terminate(); srv.wait()
