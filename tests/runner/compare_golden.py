#!/usr/bin/env python3
"""tests/out/(最新実行)と tests/golden/ を、ケース定義(tests/runner/cases/*.json)を起点に比較する。
golden のファイルを数え上げる方式ではなく、ケースから「あるべきファイル」を決める(ケースが
増えたのに golden が無い、を見逃さないため)。失敗にするもの:
  DIFF / MISSING     : golden と違う、または出力・golden が無い(known-failures.txt に理由つきで載せたものを除く)
  NOISE              : 実行が Fatal / Warning / Notice / Deprecated を出した、または非 0 終了
                       (golden と一致していても失敗。理由つきの "noise:" 例外を除く)
  STALE              : known-failures / noise 例外にあるのに、もう当てはまらない
  BAD-KNOWN          : 理由の無い行、未知のケース、新サイト(scope=site)のケースを例外にしている行
  ORPHAN             : ケースの無い golden
最後に「比較したケース数 / 既知として除外した数 / 内訳」を必ず出す。
"""
import glob, json, os, re, sys

os.chdir(os.path.join(os.path.dirname(os.path.abspath(__file__)), '..', '..'))
cases = {}
for f in sorted(glob.glob('tests/runner/cases/*.json')):
    c = json.load(open(f))
    cases[c['id']] = c

def files_of(c):
    fs = ['html', 'exit', 'params.json']
    if c.get('scope') == 'site':
        fs.append('head.txt')
    if c.get('dump_tables') or c.get('dump_queries'):
        fs.append('db.txt')
    return [f"{c['id']}.{x}" for x in fs]

problems = []
known, noise = {}, {}   # known: ファイル名 -> 理由 / noise: ケースID -> 理由
for n, line in enumerate(open('tests/runner/known-failures.txt', encoding='utf-8'), 1):
    raw = line.rstrip('\n')
    s = raw.split('#', 1)[0].strip() if not raw.lstrip().startswith('noise:') else raw.strip()
    if not s:
        continue
    if '|' not in s or not s.split('|', 1)[1].strip():
        problems.append(f"BAD-KNOWN: known-failures.txt:{n} 理由が無い: {raw}")
        continue
    key, reason = [x.strip() for x in s.split('|', 1)]
    is_noise = key.startswith('noise:')
    key = key[len('noise:'):].strip() if is_noise else key
    cid = key if is_noise else key.split('.', 1)[0]
    if cid not in cases:
        problems.append(f"BAD-KNOWN: known-failures.txt:{n} 未知のケース: {key}")
        continue
    if cases[cid].get('scope') == 'site':
        problems.append(f"BAD-KNOWN: known-failures.txt:{n} 新サイトのページのケースは例外にできない: {key}")
        continue
    if not is_noise and key not in files_of(cases[cid]):
        problems.append(f"BAD-KNOWN: known-failures.txt:{n} このケースに無いファイル: {key}")
        continue
    (noise if is_noise else known)[key] = reason

NOISE_RE = re.compile(r'^(Warning|Notice|Deprecated|Fatal error|Parse error|PHP Warning|PHP Fatal error): |^Stack trace:', re.M)
used_known, used_noise = set(), set()
compared = {'site': 0, 'legacy': 0}
for cid, c in cases.items():
    compared[c.get('scope', 'legacy')] += 1
    for name in files_of(c):
        g, o = f'tests/golden/{name}', f'tests/out/{name}'
        if not os.path.exists(g):
            problems.append(f"MISSING: {g} (golden が無い。内容を読んでから tests/runner/update_golden.sh)"); continue
        if not os.path.exists(o):
            problems.append(f"MISSING: {o} (run_all.sh を先に)"); continue
        same = open(g, 'rb').read() == open(o, 'rb').read()
        if not same:
            if name in known: used_known.add(name)
            else: problems.append(f"DIFF: {name}  (diff {g} {o})")
        elif name in known:
            problems.append(f"STALE: {name} は known-failures.txt にあるが一致した。行を外してください")
    # 「落ちた出力が golden になっている」状態を防ぐ: golden との一致とは別に、実行の健全性を見る
    o_exit, o_html = f"tests/out/{cid}.exit", f"tests/out/{cid}.html"
    if os.path.exists(o_exit) and os.path.exists(o_html):
        code = open(o_exit).read().strip()
        txt = open(o_html, encoding='utf-8', errors='replace').read()
        dirty = code != '0' or NOISE_RE.search(txt)
        if dirty and cid in noise: used_noise.add(cid)
        elif dirty: problems.append(f"NOISE: {cid} exit={code} / Fatal・Warning・Notice・Deprecated の出力あり(golden と一致していても失敗)")
        elif cid in noise: problems.append(f"STALE: noise:{cid} は例外にあるが、クリーンに実行できた。行を外してください")
    if c.get('scope') == 'site':
        h = f"tests/out/{cid}.head.txt"
        if os.path.exists(h) and not open(h).read().startswith('Status: '):
            problems.append(f"NOISE: {cid} 応答ヘッダの記録が壊れている")
for k in known:
    if k not in used_known and not any(p.startswith(f'STALE: {k}') for p in problems):
        pass
golden_names = {os.path.basename(p) for p in glob.glob('tests/golden/TC-*')}
expected = {n for c in cases.values() for n in files_of(c)}
for n in sorted(golden_names - expected):
    problems.append(f"ORPHAN: tests/golden/{n} (対応するケースが無い)")

for p in problems: print(p)
print()
print(f"比較したケース: {len(cases)} 件(新サイト {compared['site']} / 旧コード {compared['legacy']})")
print(f"既知の失敗として比較から外したファイル: {len(known)} 件 / 実行の汚れ(Warning 等)を理由つきで許したケース: {len(noise)} 件")
for k, r in sorted(known.items()): print(f"  除外 {k}: {r}")
for k, r in sorted(noise.items()): print(f"  許容 {k}: {r}")
if problems:
    print(f"FAIL: {len(problems)} 件"); sys.exit(1)
print("ALL MATCH")
