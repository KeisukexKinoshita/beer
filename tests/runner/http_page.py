#!/usr/bin/env python3
"""新サイトのページを PHP 組み込みサーバ(php -S)に実 HTTP で叩き、本文・ステータス・応答ヘッダを保存する。
CLI の PHP は header() の内容を保持しない(headers_list() が常に空)ため、ヘッダの比較には実サーバが要る。
  http_page.py <page> <params.json> <body-out> <head-out>
params.json(1リクエストのとき): get / post / cookies / host / uri / method
  (steps が無ければ従来どおり。出力は本文そのまま・head は "Status: n" と応答ヘッダ)
params.json に "steps": [ {method, uri, host, get, post, cookies}, ... ] があれば、同じ cookie jar で順に叩く。
  - 前の応答の Set-Cookie は jar に入り、次のリクエストの Cookie に付く(ブラウザと同じ)。
  - step の cookies / post の値に "{db_age_tok}" "{db_vid}" と書くと、その時点の jar の値に置き換わる。
  - step の "no_jar": true で、jar を使わない(新しい訪問者として叩く)。
  - 出力は、各 step の前に "=== step N: METHOD URI ===" の見出しを付けて連結する。
params.json の "check_distinct_cookie": "<名前>" があれば、各 step が発行したその Cookie の値が
  すべて違うことを head の末尾に "# check: <名前> distinct = yes/no" と書く(乱数性の検査。値は正規化で消えるため)。
実行のたびに、発行された Cookie の値を <body-out> の隣の tests/out/.vars/<ID>.json にも書く(run_case.sh が
dump_queries の {db_vid} を置き換えて、発行された db_vid と DB の行の ID が一致することを見る)。
終了コード: 本文に PHP の Fatal があるか HTTP 5xx なら 255、それ以外 0(.exit に記録される)
head の除外: Date / Connection / Content-Length / X-Powered-By / Host(毎回変わる・意味が無い)
"""
import http.client, json, os, re, socket, subprocess, sys, time, urllib.parse

page, pj, body_out, head_out = sys.argv[1:5]
root = os.path.abspath(os.path.join(os.path.dirname(__file__), '..', 'sandbox'))
p = json.load(open(pj))
steps = p.get('steps') or [p]
multi = 'steps' in p

s = socket.socket(); s.bind(('127.0.0.1', 0)); port = s.getsockname()[1]; s.close()
srv = subprocess.Popen(['php', '-d', 'display_errors=1', '-d', 'error_reporting=-1', '-d', 'log_errors=0',
                        '-d', 'html_errors=0', '-S', f'127.0.0.1:{port}', '-t', root],
                       stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL)
SKIP = {'date', 'connection', 'x-powered-by', 'content-length', 'host'}
jar, issued, bodies, heads, bad = {}, {}, [], [], False
try:
    for _ in range(100):
        try:
            socket.create_connection(('127.0.0.1', port), 0.2).close(); break
        except OSError:
            time.sleep(0.05)
    def sub(v):
        return re.sub(r'\{(db_age_tok|db_vid)\}', lambda m: jar.get(m.group(1), ''), v) if isinstance(v, str) else v
    for i, st in enumerate(steps, 1):
        get, post = st.get('get') or {}, st.get('post') or {}
        method = st.get('method', 'GET')
        uri = st.get('uri') or ('/' + page + ('?' + urllib.parse.urlencode(get) if get else ''))
        cookies = {} if st.get('no_jar') else dict(jar)
        cookies.update({k: sub(v) for k, v in (st.get('cookies') or {}).items()})
        if st.get('no_jar') is None and 'no_cookie' in st:
            for k in st['no_cookie']: cookies.pop(k, None)
        headers = {'Host': st.get('host', p.get('host', 'beer.test'))}
        if cookies:
            headers['Cookie'] = '; '.join(f'{k}={v}' for k, v in cookies.items())
        body = None
        if method == 'POST':
            body = urllib.parse.urlencode({k: sub(v) for k, v in post.items()})
            headers['Content-Type'] = 'application/x-www-form-urlencoded'
        c = http.client.HTTPConnection('127.0.0.1', port, timeout=30)
        c.request(method, uri, body=body, headers=headers)
        r = c.getresponse()
        data = r.read()
        lines = [f'Status: {r.status}']
        for k, v in r.getheaders():
            if k.lower() in SKIP: continue
            lines.append(f'{k}: {v}')
            if k.lower() == 'set-cookie':
                nv = v.split(';', 1)[0].split('=', 1)
                if len(nv) == 2:
                    jar[nv[0]] = nv[1]
                    issued.setdefault(nv[0], []).append(nv[1])
        txt = data.decode('utf-8', 'replace')
        if r.status >= 500 or any(l.startswith('Fatal error') or l.startswith('PHP Fatal') for l in txt.splitlines()):
            bad = True
        if multi:
            bodies.append(f'=== step {i}: {method} {uri} ===\n'.encode() + data + b'\n')
            heads.append(f'=== step {i}: {method} {uri} ===\n' + '\n'.join(lines) + '\n')
        else:
            bodies.append(data); heads.append('\n'.join(lines) + '\n')
    name = p.get('check_distinct_cookie')
    if name:
        vals = issued.get(name, [])
        heads.append(f"# check: {name} issued {len(vals)} times, distinct = {'yes' if len(set(vals)) == len(vals) and len(vals) > 1 else 'no'}\n")
    open(head_out, 'w').write(''.join(heads))
    open(body_out, 'wb').write(b''.join(bodies))
    vars_dir = os.path.join(os.path.dirname(body_out), '.vars'); os.makedirs(vars_dir, exist_ok=True)
    vars_path = os.path.join(vars_dir, os.path.basename(body_out)[:-5] + '.json')
    json.dump({k: v[-1] for k, v in issued.items()}, open(vars_path, 'w'))
    sys.exit(255 if bad else 0)
finally:
    srv.terminate(); srv.wait()
