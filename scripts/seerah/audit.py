# -*- coding: utf-8 -*-
# NOTE: targets the pre-005 public event page (lessons section). Kept for reference only;
# see specs/005-seerah-schema-refactor — lessons no longer exist.
"""Register + attribution audit over the saved m01 event pages. No book needed."""
import json, re, collections

AR = r'[ء-ي]'
def wb(w):  # whole-word-ish match with Arabic letter guards
    return re.compile(rf'(?<!{AR}){re.escape(w)}(?!{AR})')

COLLOQ = ["اللي","اللى","مش","إزاي","ازاي","علشان","عشان","كده","كدة","ده","دي","دول","بقى","بقت","بقوا",
 "مين","عايز","عاوز","ساب","سابت","سابوا","يسيب","جه","جت","جابوا","جاب","حاجة","حاجات","بيقول","بتقول",
 "بيحصل","بيتعمل","محدش","مافيش","مفيش","لسه","أوي","قوي","خالص","بتاع","بتاعة","الراجل","راجل","رجالة",
 "الست","الستّ","واحد","واحدة","دلوقتي","إنت","انت","هيبقى","هتلاقي","تلاقي","بص","يعني","أهو","برضه",
 "كتير","شوية","زي","وشّه","وشه","اتقال","اتعمل","اتكتب","اتنقل","اتغير","اتخانق","اتضرب","بيتكلم","بنقرا",
 "بيفتح","بيحكي","بيرتب","بيعرض","بيقدم","بيحط","بيشرح","بيسأل","بيختم","بيعلق","بيداري","بيحسم","بيوصف",
 "ماكانش","مابدأش","مابقاش","ماوصلناش","مانالوش","ماجاش","مافضلش","بتتدبح","بتتحدف","بتحفظ","مابتكتبش"]
PAT = [(w, wb(w)) for w in COLLOQ]

# words that, applied to the Prophet ﷺ / a Companion / an Imam, break the register
MAQAM = ["الراجل","راجل","رجالة","الست","الستّ","واحد اسمه","أول واحد","الواد","اتّهمه","اتهمه"]
MAQAM = [(w, wb(w)) for w in MAQAM]

rows = json.load(open('prod/m01.json'))
FIELDS = ['title','description','body','lessons']

tot = collections.Counter(); per_ev = []
for r in rows:
    hits = collections.Counter(); where = collections.defaultdict(set)
    for f in FIELDS:
        t = r.get(f) or ''
        for w, p in PAT:
            n = len(p.findall(t))
            if n:
                hits[w] += n; where[f].add(w); tot[w] += n
    maq = []
    for f in FIELDS:
        t = r.get(f) or ''
        for w, p in MAQAM:
            if p.search(t): maq.append(f"{f}:{w}")
    per_ev.append((r['slug'], sum(hits.values()), sorted(where['title']), sorted(set(maq)), r['title']))

print("="*100)
print("A. REGISTER — colloquial hits per event (title/description/body/lessons)")
print("="*100)
clean = 0
for slug, n, tw, maq, title in per_ev:
    if n == 0: clean += 1
    flag = "  ⚠MAQAM" if maq else ""
    print(f"{n:>4}  {slug:<45} {title[:46]}{flag}")
print(f"\nevents with ZERO colloquial hits: {clean} / {len(rows)}")
print(f"TOTAL colloquial hits across milestone: {sum(tot.values())}")
print("\ntop markers:", ", ".join(f"{w}={c}" for w,c in tot.most_common(22)))

print("\n"+"="*100)
print("B. MAQAM — wording that lowers the standing of the Prophet ﷺ / Companions / Imams")
print("="*100)
for slug, n, tw, maq, title in per_ev:
    if maq: print(f"{slug:<45} {title[:48]}\n      {maq}")

print("\n"+"="*100)
print("C. SOURCES — which references each event claims")
print("="*100)
src_count = collections.Counter()
for r in rows:
    for line in (r['sources'] or '').split('\n'):
        line = line.strip()
        if not line or line == 'المصادر والمراجع': continue
        if '—' not in line: src_count[line] += 1
for s, c in src_count.most_common(40):
    print(f"{c:>3}  {s}")
