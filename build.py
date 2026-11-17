"""Build independent private modules, lightweight preview, and WordPress package."""
from pathlib import Path
import json, shutil, zipfile, hashlib, base64
ROOT=Path(__file__).parent
source=ROOT/'source'; plugin=ROOT/'plugin/scys-coach-hub'
c=json.loads((source/'catalogue.json').read_text(encoding='utf-8'))
for group in ['lessons','resources','categories','courses','learningPathways']:
 assert len({i['id'] for i in c[group]})==len(c[group])
for parents,key,children in [('categories','lessonIds','lessons'),('courses','categoryIds','categories'),('learningPathways','courseIds','courses')]:
 for item in c[parents]: assert set(item[key]) <= {x['id'] for x in c[children]}
for p in c['pathways']:
 for s in p['stages']: assert set(s['resourceIds']) <= {x['id'] for x in c['resources']}
def write_php(path,data):
 path.parent.mkdir(parents=True,exist_ok=True)
 payload=json.dumps(data,ensure_ascii=False,separators=(',',':'))
 path.write_text("<?php\ndefined( 'ABSPATH' ) || exit;\nreturn json_decode( <<<'SCYS_JSON'\n"+payload+"\nSCYS_JSON\n, true, 512, JSON_THROW_ON_ERROR );\n",encoding='utf-8')
# Keep authoring provenance local; runtime source citations need titles and IDs only.
runtime_catalogue=json.loads(json.dumps(c))
for source_ref in runtime_catalogue.get('sources',[]): source_ref.pop('file',None)
write_php(plugin/'content/index.php',runtime_catalogue)
for item in c['research']: assert item['url'].startswith('https://')
questions=0
for group in ['lessons','resources']:
 for item in c[group]:
  data=json.loads((source/'content'/group/(item['id']+'.json')).read_text(encoding='utf-8'))
  assert data['id']==item['id']
  assert set(data.get('mediaIds', [data['mediaId']] if data.get('mediaId') else [])) <= {m['id'] for m in c['media']}
  if group=='lessons':
   assert len(data['quiz'])>=3
   for q in data['quiz']:assert 0<=q['correct']<len(q['options'])
   questions+=len(data['quiz'])
  write_php(plugin/'content'/group/(item['id']+'.php'),data)
for m in c['media']:
 data=(source/'content/media'/m['file']).read_bytes()
 write_php(plugin/'content/media'/(m['id']+'.php'),{'id':m['id'],'src':'data:image/'+('jpeg' if m['file'].endswith('.jpg') else 'png')+';base64,'+base64.b64encode(data).decode()})
# Retire the former monolithic bundle; only this exact generated file is removed.
legacy=plugin/'content.php'
if legacy.exists():legacy.unlink()
preview=ROOT/'preview';preview.mkdir(exist_ok=True)
shutil.copytree(source/'content',preview/'content',dirs_exist_ok=True)
(preview/'catalogue.json').write_text(json.dumps(runtime_catalogue,ensure_ascii=False,separators=(',',':')),encoding='utf-8')
shutil.copyfile(source/'preview.html',preview/'index.html')
for name in ['hub.css','brand.css','hub.js','mentoring.js']:shutil.copyfile(plugin/'assets'/name,preview/name)
shutil.copyfile(source/'start-preview.py',preview/'start-preview.py')
(preview/'Start Preview.cmd').write_text('@echo off\npython "%~dp0start-preview.py"\npause\n',encoding='utf-8')
(ROOT/'Coach Academy Preview.html').write_text('''<!doctype html><html lang="en"><meta charset="utf-8"><title>Coach Academy modular preview</title><h1>Coach Academy modular preview</h1><p>The preview now loads lessons separately. Run <strong>preview/Start Preview.cmd</strong> from the hub folder, and the local preview will open in your browser. Keep its server window open while using it.</p><p>For Mike, use the modular-preview ZIP in releases. Do not upload preview files to a public website; preview accounts and answers are simulations.</p></html>''',encoding='utf-8')
# Keep the former development-server directory modular too.
shutil.copytree(preview,ROOT/'dist',dirs_exist_ok=True)
release=ROOT/'releases';release.mkdir(exist_ok=True)
package=release/f"scys-coach-hub-{c['version']}.zip"
with zipfile.ZipFile(package,'w',zipfile.ZIP_DEFLATED) as z:
 for path in sorted(plugin.rglob('*')):
  if path.is_file():z.write(path,path.relative_to(plugin.parent).as_posix())
with zipfile.ZipFile(release/f"scys-coach-hub-modular-preview-{c['version']}.zip",'w',zipfile.ZIP_DEFLATED) as z:
 for path in sorted(preview.rglob('*')):
  if path.is_file():z.write(path,path.relative_to(preview).as_posix())
receipt={'version':c['version'],'lessons':len(c['lessons']),'quiz_questions':questions,'categories':len(c['categories']),'courses':len(c['courses']),'learning_pathways':len(c['learningPathways']),'activities':len(c['resources']),'original_visuals':len(c['media']),'catalogue_bytes':len(json.dumps(runtime_catalogue,separators=(',',':'),ensure_ascii=False).encode()),'package':str(package),'sha256':hashlib.sha256(package.read_bytes()).hexdigest()}
(release/'build-receipt.json').write_text(json.dumps(receipt,indent=2),encoding='utf-8');print(json.dumps(receipt))
