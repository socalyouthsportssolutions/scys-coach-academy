import fs from 'node:fs';
import path from 'node:path';
import {createRequire} from 'node:module';
const runtime=process.env.SCYS_TEST_RUNTIME;
fs.mkdirSync('releases',{recursive:true});
if(!runtime)throw Error('Set SCYS_TEST_RUNTIME to the isolated npm test-runner directory.');
const require=createRequire(path.join(runtime,'package.json'));
const {PHP}=require('@php-wasm/universal');
const {loadNodeRuntime}=require('@php-wasm/node');
const php=new PHP(await loadNodeRuntime(process.env.SCYS_PHP||'8.3',{emscriptenOptions:{processId:1}}));
const files=[];
function walk(dir){for(const name of fs.readdirSync(dir)){const p=path.join(dir,name);if(fs.statSync(p).isDirectory())walk(p);else if(p.endsWith('.php'))files.push(p);}}
walk(path.resolve('plugin/scys-coach-hub'));
for(const [i,file] of files.entries())php.writeFile('/lint-'+i+'.php',fs.readFileSync(file));
const result=await php.run({code:'<?php $errors=[]; foreach(glob("/lint-*.php") as $file){try{token_get_all(file_get_contents($file), TOKEN_PARSE);}catch(ParseError $e){$errors[]=$file.": ".$e->getMessage();}} echo json_encode(["php"=>PHP_VERSION,"files"=>count(glob("/lint-*.php")),"errors"=>$errors]);'});
const report=JSON.parse(result.text);console.log(JSON.stringify(report));fs.writeFileSync('releases/mentoring-php-lint-'+(process.env.SCYS_PHP||'8.3')+'.json',JSON.stringify(report,null,2));
php.exit();if(report.errors.length)process.exitCode=1;

