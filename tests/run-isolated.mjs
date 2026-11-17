/** Disposable in-memory WordPress; no native directory mounts or real accounts. */
import fs from 'node:fs';import path from 'node:path';import os from 'node:os';import {createRequire} from 'node:module';
const runtime=process.env.SCYS_TEST_RUNTIME;if(!runtime)throw Error('Set SCYS_TEST_RUNTIME to an npm folder containing @wp-playground/cli.');
fs.mkdirSync('releases',{recursive:true});
const require=createRequire(path.join(runtime,'package.json'));
const {loadNodeRuntime}=require('@php-wasm/node');const {bootWordPressAndRequestHandler}=require('@wp-playground/wordpress');
const phpVersion=process.env.SCYS_PHP||'8.3',wpVersion=process.env.SCYS_WP||'6.9.11';
const wpZip=path.join(os.homedir(),'.wordpress-playground',wpVersion+'.zip');
const cliDir=path.dirname(require.resolve('@wp-playground/cli/package.json'));
let sqliteZip=path.join(cliDir,'sqlite-database-integration.zip');
if(!fs.existsSync(sqliteZip)){const buildsDir=path.dirname(require.resolve('@wp-playground/wordpress-builds/package.json'));sqliteZip=path.join(buildsDir,'src/sqlite-database-integration/sqlite-database-integration-trunk.zip');}
if(!/^\d+\.\d+(?:\.\d+)?$/.test(wpVersion))throw Error('Use a numeric WordPress version.');
if(!fs.existsSync(wpZip)){
 const url='https://wordpress.org/wordpress-'+wpVersion+'.zip';console.log('Downloading official isolated-test WordPress release: '+url);
 const response=await fetch(url);if(!response.ok)throw Error('WordPress download failed: '+response.status);
 fs.mkdirSync(path.dirname(wpZip),{recursive:true});fs.writeFileSync(wpZip+'.partial',new Uint8Array(await response.arrayBuffer()));fs.renameSync(wpZip+'.partial',wpZip);
}
if(!sqliteZip)throw Error('The official SQLite integration bundled with Playground is required.');
let pid=1;
const handler=await bootWordPressAndRequestHandler({siteUrl:'http://isolated.scys.test',phpVersion,maxPhpInstances:1,createPhpRuntime:()=>loadNodeRuntime(phpVersion,{emscriptenOptions:{processId:pid++}}),wordPressZip:new File([fs.readFileSync(wpZip)],'wordpress.zip'),sqliteIntegrationPluginZip:new File([fs.readFileSync(sqliteZip)],'sqlite.zip')});
const php=await handler.getPrimaryPhp();
function copy(dir,target){php.mkdir(target);for(const entry of fs.readdirSync(dir,{withFileTypes:true})){const from=path.join(dir,entry.name),to=target+'/'+entry.name;if(entry.isDirectory())copy(from,to);else if(entry.isFile())php.writeFile(to,fs.readFileSync(from));}}
copy(path.resolve('plugin/scys-coach-hub'),'/wordpress/wp-content/plugins/scys-coach-hub');
php.mkdir('/tests');for(const file of ['all-integration.php','wordpress-integration.php','mentoring-integration.php','claude-review-integration.php'])php.writeFile('/tests/'+file,fs.readFileSync('tests/'+file));
try{let result;try{result=await php.run({scriptPath:'/tests/all-integration.php'});}catch(error){if(!error.response)throw error;result=error.response;}console.log(result.text);if(result.errors)console.error(result.errors);fs.writeFileSync('releases/mentoring-wordpress-'+phpVersion+'.log',result.text+'\n'+result.errors);if(result.exitCode!==0||!result.text.includes('ALL CLAUDE REVIEW INTEGRATION CHECKS PASSED')||result.text.split('\n').filter(line=>line.startsWith('PASS:')).length!==111)process.exitCode=1;}finally{php.exit();}
