# NOTE: targets the pre-005 public event page (lessons section). Kept for reference only;
# see specs/005-seerah-schema-refactor — lessons no longer exist.
"""Pull the reviewable fields out of a saved public seerah event page."""
import re, sys, html, json, os

def strip(t):
    t = re.sub(r'<(script|style|svg)[^>]*>.*?</\1>', ' ', t, flags=re.S|re.I)
    t = re.sub(r'<br\s*/?>', '\n', t, flags=re.I)
    t = re.sub(r'</(p|h[1-6]|li|div|blockquote)>', '\n', t, flags=re.I)
    t = re.sub(r'<[^>]+>', '', t)
    t = html.unescape(t)
    t = re.sub(r'[ \t ]+', ' ', t)
    return re.sub(r'\n\s*\n+', '\n', t).strip()

def grab(doc, slug):
    out = {'slug': slug}
    m = re.search(r'<title>(.*?)</title>', doc, re.S)
    out['title'] = html.unescape(m.group(1)).split('—')[0].strip() if m else ''
    m = re.search(r'<meta name="description" content="([^"]*)"', doc)
    out['description'] = html.unescape(m.group(1)).strip() if m else ''
    # body: between the "السياق التاريخي" marker and the lessons heading
    body = doc
    i = body.find('السياق التاريخي')
    j = body.find('الدروس والعبر')
    k = body.find('المصادر والمراجع')
    out['body'] = strip(body[i:j]) if i > -1 and j > i else ''
    out['lessons'] = strip(body[j:k]) if j > -1 and k > j else ''
    tail = body[k:k+20000] if k > -1 else ''
    e = tail.find('أحداث ذات صلة')
    out['sources'] = strip(tail[:e if e > 0 else 6000])
    return out

rows = []
for slug in open('prod/m01_slugs.txt').read().split():
    p = f'prod/html/{slug}.html'
    if not os.path.exists(p): continue
    rows.append(grab(open(p, encoding='utf-8').read(), slug))
json.dump(rows, open('prod/m01.json','w'), ensure_ascii=False, indent=1)
print(len(rows), 'events')
for r in rows:
    print(f"{r['slug']:<45} title={len(r['title']):>3} desc={len(r['description']):>4} body={len(r['body']):>5} less={len(r['lessons']):>4} src={len(r['sources']):>4}")
