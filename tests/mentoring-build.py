"""Structural, preservation and release checks; complements real WP/API/browser tests."""
from pathlib import Path
import json,subprocess,zipfile,hashlib
R=Path(__file__).resolve().parent.parent
c=json.loads((R/'source/catalogue.json').read_text(encoding='utf-8'))
baseline_path=R/'tests/foundation-baseline.json'
baseline=json.loads(baseline_path.read_text(encoding='utf-8')) if baseline_path.exists() else None
old=baseline['catalogue'] if baseline else json.loads(subprocess.check_output(['git','show','HEAD:source/catalogue.json'],cwd=R,text=True,encoding='utf-8'))
for group in ['lessons','categories','courses','learningPathways','resources','media']:
 for item in old[group]:
  assert next(x for x in c[group] if x['id']==item['id'])==item,(group,item['id'])
for group in ['lessons','resources']:
 for item in old[group]:
  filename='source/content/'+group+'/'+item['id']+'.json'
  actual=(R/filename).read_bytes().replace(b'\r\n',b'\n')
  assert (hashlib.sha256(actual).hexdigest()==baseline['textHashes'][filename] if baseline else actual==subprocess.check_output(['git','show','HEAD:'+filename],cwd=R).replace(b'\r\n',b'\n')),filename
for m in old['media']:
 f='source/content/media/'+m['file'];assert (hashlib.sha256((R/f).read_bytes()).hexdigest()==baseline['binaryHashes'][f] if baseline else (R/f).read_bytes()==subprocess.check_output(['git','show','HEAD:'+f],cwd=R))
runtime=json.loads((R/'preview/catalogue.json').read_text(encoding='utf-8'))
assert all('file' not in s for s in runtime['sources'])
runtime=json.loads((R/'preview/catalogue.json').read_text(encoding='utf-8'))
assert all('file' not in s for s in runtime['sources'])
assert not any(k in json.dumps(c) for k in ['"quiz"','"sections"','data:image/','"visits"','"observations"'])
category=next(x for x in c['categories'] if x['id']=='coach-mentoring')
assert len(category['lessonIds'])==7
for id in category['lessonIds']:
 d=json.loads((R/'source/content/lessons'/f'{id}.json').read_text(encoding='utf-8'))
 assert len(d['quiz'])==3 and all(len(q['options'])==3 and 0<=q['correct']<3 and q['explanation'] for q in d['quiz'])
 assert len(''.join(s['html'] for s in d['sections']))>1200
 assert not any(x in json.dumps(d) for x in ['mail.google.com','docs.google.com/spreadsheets','72%','five times'])
for kind in ['scys-coach-hub-','scys-coach-hub-modular-preview-']:
 p=R/'releases'/f'{kind}{c["version"]}.zip'
 with zipfile.ZipFile(p) as z:
  assert z.testzip() is None
  assert not any(n.startswith('/') or '..' in Path(n).parts for n in z.namelist())
  if kind=='scys-coach-hub-':
   for file in (R/'plugin/scys-coach-hub').rglob('*'):
    if file.is_file():assert z.read(file.relative_to(R/'plugin').as_posix())==file.read_bytes()
   assert not any(n.endswith('.json') or '/tests/' in n for n in z.namelist())
print('PASS: existing lessons, foundations, resources and original visuals unchanged; seven complete new lessons; lean manifest; ZIP integrity and package/source equality')
