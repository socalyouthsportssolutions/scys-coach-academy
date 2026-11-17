/* Shared operational mentoring UI. Preview stores synthetic examples only. */
window.SCYSCreateMentoring = ({root, demo, request, getMe, getCatalogue, esc, status}) => {
 const statuses=['requested','arranging','confirmed','delivered','follow-up due','closed','postponed','cancelled'];
 let current=null, page=1, listing=null, serial=0, busy=false, formDraft=null, visitDraft=null, selectedVisit='';
 const demoKey='scys-mentoring-synthetic-preview-v1';
 const now=()=>new Date().toISOString();
 // Human-friendly local date/time for stored ISO-style values (e.g. 2026-11-09T16:00 -> Mon, Nov 9 · 4:00 PM).
 const when=value=>{if(!value)return '';const m=/^(\d{4})-(\d{2})-(\d{2})(?:T(\d{2}):(\d{2}))?$/.exec(String(value));if(!m)return String(value);const d=new Date(+m[1],+m[2]-1,+m[3],+(m[4]||0),+(m[5]||0));const day=d.toLocaleDateString(undefined,{weekday:'short',month:'short',day:'numeric'});return m[4]?day+' · '+d.toLocaleTimeString(undefined,{hour:'numeric',minute:'2-digit'}):day;};
 const uuid=()=>crypto.randomUUID();
 const button=(label,action,id='')=>`<button class="hub-button" type="button" data-support="${action}" data-id="${esc(id)}">${label}</button>`;
 const heading=title=>`<h1 tabindex="-1">${esc(title)}</h1>`;
 const readDemo=()=>JSON.parse(localStorage.getItem(demoKey)||'[]');
 function owner(d) {return getMe().canCoordinate || d.coachId===getMe().id || (getMe().canReview&&d.assignedMentor===getMe().id);}
 async function api(path,body) {
  if(!demo)return request(path,body);
  let records=readDemo(); const me=getMe();
  if(path==='support')return {records:records.filter(owner).slice((page-1)*50,page*50).map(d=>({...d,followUps:Object.values(d.visits).filter(v=>v.followUpDate&&(!v.result||v.followUpState==='open')).map(v=>({date:v.followUpDate,owner:v.followUpOwner,overdue:v.followUpDate<now().slice(0,10)})),upcoming:Object.values(d.visits).filter(v=>!v.attendanceBy&&v.date)})),page,hasMore:records.filter(owner).length>page*50,enabled:true,canCoordinate:!!me.canCoordinate,reportingUrl:''};
  if(path.startsWith('support/'))return structuredClone(records.find(d=>d.id===Number(path.split('/')[1])&&owner(d))||null);
  if(path!=='support/save')throw Error('Unknown preview action.');
 }
 // Keep preview writes distinct from authenticated server storage and checks.
 async function save(payload) {
  if(!demo)return request('support/save',payload);
  const records=readDemo(),me=getMe();let d=records.find(x=>x.id===payload.id);
  if(d&&!owner(d))throw Error('Record unavailable for this preview persona.');
  if(d?.receipts?.[payload.token])return structuredClone(d);
  if(d&&d.version!==payload.version)throw Error('Another save changed this preview record. Reopen and reconcile your draft.');
  d=d?structuredClone(d):{id:Date.now(),coachId:me.id,coachName:me.name,assignedMentor:103,mentorName:'Mentor preview',visits:{},history:[],version:0,receipts:{},createdAt:now(),createdBy:me.id};
  if(payload.action==='request') {
   const f=payload.fields;
   if(!f.seasonTeam||!f.ageGroup||!f.topic||!f.location)throw Error('Complete the team, age group, topic and location.');
   const changed=['seasonTeam','ageGroup','topic','type','proposedAt','location','arrangement'].some(k=>(d[k]||'')!==f[k]);
   if(changed){d.coachAgreedBy=0;d.mentorAcceptedBy=0;}
   Object.assign(d,f);
   if(payload.agree&&me.id===d.coachId)d.coachAgreedBy=me.id;
   if(payload.accept&&me.id===d.assignedMentor)d.mentorAcceptedBy=me.id;
   if(f.status==='confirmed'&&(!d.coachAgreedBy||!d.mentorAcceptedBy||!f.proposedAt||!f.arrangement))throw Error('Confirmation needs coach agreement, mentor acceptance, time and arrangement.');
   if(['delivered','follow-up due','closed'].includes(f.status)&&!Object.values(d.visits).some(v=>v.attendanceBy))throw Error('Confirm a delivered visit first.');
   if(['closed','cancelled'].includes(f.status)&&Object.values(d.visits).some(v=>v.nextAction&&(!v.result||v.followUpState==='open')))throw Error('Resolve outstanding actions first.');
   if(f.status==='closed'&&!f.closureReason)throw Error('Add a closure reason.');
  } else {
   const vid=payload.visitId||uuid(),v={...d.visits[vid],...payload.fields,id:vid};if(v.followUpState==='complete'&&!v.result)throw Error('Record a result or keep the action open.');
   v.mentorId=v.mentorId||d.assignedMentor;v.draft=!payload.complete&&!v.attendanceBy;
   if(!v.draft){for(const k of ['date','type','focus','observation1','observation2','strength','nextAction','resourceId','followUpOwner','followUpDate'])if(!v[k])throw Error(`Complete ${k} or save a draft.`);
    if(v.date.slice(0,10)>now().slice(0,10))throw Error('A future visit cannot be delivered.');
    if(!v.attendanceBy){if(!payload.confirmAttendance||![d.coachId,d.assignedMentor].includes(me.id))throw Error('Coach or mentor must confirm attendance.');v.attendanceBy=me.id;v.attendanceAt=now();}}
   v.updatedBy=me.id;v.updatedAt=now();d.visits[vid]=v;
   if(Object.values(d.visits).some(v=>v.attendanceBy))d.status=Object.values(d.visits).some(v=>v.nextAction&&(!v.result||v.followUpState==='open'))?'follow-up due':'delivered';
  }
  d.version++;d.updatedAt=now();d.updatedBy=me.id;d.history.push({by:me.id,at:now(),action:payload.action,visitId:payload.visitId||null});d.receipts[payload.token]=true;
  const index=records.findIndex(x=>x.id===d.id);if(index<0)records.push(d);else records[index]=d;
  localStorage.setItem(demoKey,JSON.stringify(records));return d;
 }
 function warning(){return `<div class="hub-note"><strong>Shared adult development records — linked to accounts</strong><p>The coach, current assigned mentor and authorised programme administrator see these notes. Do not enter children’s names, identifying details, health or family information, safeguarding allegations or identifiable child footage. Use the approved restricted reporting procedure immediately for safeguarding concerns. Maintain never-alone and supervision requirements.</p>${listing?.reportingUrl?`<p><a href="${esc(listing.reportingUrl)}" target="_blank" rel="noopener noreferrer">Approved reporting procedure ↗</a></p>`:'<p class="hub-small">Local reporting procedure still needs approval. <a href="https://www.ussoccer.com/safeguarding/report-a-concern" target="_blank" rel="noopener noreferrer">Official U.S. Soccer reporting guidance ↗</a></p>'}${demo?'<p><strong>Synthetic preview only.</strong> Use invented adult examples. Preview records save in this browser; personas simulate access.</p>':''}</div>`;}
 const input=(name,label,value='',type='text',required=false)=>`<label>${esc(label)}<input name="${name}" aria-label="${esc(label)}" type="${type}" value="${esc(value)}" maxlength="200" ${required?'required':''}></label>`;
 const area=(name,label,value='',required=false)=>`<label>${esc(label)}<textarea name="${name}" aria-label="${esc(label)}" rows="3" maxlength="1500" ${required?'required':''}>${esc(value)}</textarea></label>`;
 const select=(name,label,options,value)=>`<label>${esc(label)}<select name="${name}" aria-label="${esc(label)}">${options.map(o=>{const [v,l]=Array.isArray(o)?o:[o,o];return `<option value="${esc(v)}" ${v===value?'selected':''}>${esc(l)}</option>`;}).join('')}</select></label>`;
 function requestForm(d={}) {
  const f=formDraft||d,me=getMe();
  return `<form data-support-form="request" class="hub-support-form"><h2>${d.id?'Request and agreement':'Request mentoring'}</h2><p>Assigned mentor: <strong>${esc(d.mentorName||'Coordinator assigns through the existing adult account')}</strong>. A request is not a confirmed appointment; cover needs a separate staffing arrangement.</p><div class="hub-support-grid">${input('seasonTeam','Current season / team',f.seasonTeam,'text',true)}${input('ageGroup','Age group',f.ageGroup,'text',true)}${select('type','Support type',['practice','game'],f.type||'practice')}${input('proposedAt','Proposed date and time (local)',f.proposedAt,'datetime-local')}${input('location','Location',f.location,'text',true)}</div>${area('topic','One coaching topic',f.topic,true)}${area('arrangement','Agreed arrangement: focus, child-experience sign, intervention, supervision and hand-back',f.arrangement)}${d.id?select('status','Request status',statuses,f.status||'requested'):'<input type="hidden" name="status" value="requested">'}${area('closureReason','Closure reason (when closing)',f.closureReason)}${(!d.id||d.coachId===me.id)?'<label class="hub-option"><input type="checkbox" name="agree">I am the coach and agree to the displayed arrangement.</label>':''}${d.assignedMentor===me.id?'<label class="hub-option"><input type="checkbox" name="accept">I am the assigned mentor and accept this arrangement.</label>':''}${d.id?`<p class="hub-small">Coach agreement: ${d.coachAgreedBy?'recorded':'awaiting'} · Current mentor acceptance: ${d.mentorAcceptedBy?'recorded':'awaiting'}. Changing the arrangement requires fresh agreement.</p>`:''}<div class="hub-actions"><button class="hub-button hub-button-primary" type="submit" ${listing?.enabled?'':'disabled'}>Save request</button><span data-support-save role="status">${formDraft?'Unsaved draft':d.id?'Saved record':'Not saved'}</span></div></form>`;
 }
 function visitForm(v={}) {
  const f=visitDraft||v,resources=[['','Choose a Hub resource'],...getCatalogue().resources.map(r=>[r.id,r.title]),...getCatalogue().lessons.map(r=>[r.id,r.title])];
  return `<form data-support-form="visit" class="hub-support-form"><h2>${v.id?'Edit visit / follow-up':'Add a linked visit'}</h2><p>Save a draft at any point. Confirm attendance only after the visit happened. Each visit keeps its own action and follow-up.</p><div class="hub-support-grid">${input('date','Actual visit date and time (local)',f.date,'datetime-local')}${select('type','Visit type',['practice','game'],f.type||current.type)}${select('resourceId','Relevant resource',resources,f.resourceId||'')}${select('followUpOwner','Follow-up owner',[['','Choose owner'],['coach','Coach'],['mentor','Current mentor'],['coordinator','Coordinator']],f.followUpOwner||'')}${input('followUpDate','Follow-up due',f.followUpDate,'date')}</div>${area('focus','Agreed focus',f.focus)}<details ${v.id?'':'open'}><summary>Observation and next action</summary>${area('observation1','Factual observation 1',f.observation1)}${area('observation2','Factual observation 2',f.observation2)}${area('strength','Strength to keep',f.strength)}${area('nextAction','One next action',f.nextAction)}</details>${area('result','Follow-up result or closure reason — “not yet” and “it did not help” are valid',f.result)}${select('followUpState','Follow-up action state',[['open','Keep open — another check is needed'],['complete','Result recorded — close this action']],f.followUpState||'open')}<label class="hub-option"><input type="checkbox" name="confirmAttendance">I am the coach or assigned mentor and confirm this visit happened.</label><p class="hub-small">${v.attendanceBy?`Attendance confirmed by adult account ${esc(v.attendanceBy)} at ${esc(v.attendanceAt)}.`:'Attendance has not been confirmed.'} Mentor for this visit: ${esc(v.mentorId||current.assignedMentor||'not assigned')}.</p><div class="hub-actions"><button class="hub-button" type="submit" name="saveMode" value="draft" ${listing?.enabled?'':'disabled'}>Save draft</button><button class="hub-button hub-button-primary" type="submit" name="saveMode" value="complete" ${listing?.enabled?'':'disabled'}>${v.attendanceBy?'Save follow-up':'Save delivered visit'}</button>${v.id?button('Add another visit','new-visit'):''}<span data-support-save role="status">${visitDraft?'Unsaved draft':v.id?'Saved record':'Not saved'}</span></div></form>`;
 }
 async function render() {
  const token=++serial;listing=demo?await api('support'):await request('support?page='+page);
  if(token!==serial)return '';
  const title=listing.canCoordinate?'Mentoring coordination':getMe().canReview?'Assigned-coach mentoring':'My mentoring';
  return heading(title)+`<p class="hub-sub">One useful change, one next check. Learning completion, delivered support and improved practice are separate.</p><div class="hub-actions">${button('New request','new')}${button('Field cards','cards')}${button('Refresh records','refresh')}</div>${!listing.enabled?'<p class="hub-note">Pilot collection is disabled. An administrator must record content/privacy acceptance and the approved reporting procedure in Users → Mentoring pilot settings.</p>':''}${warning()}<div data-support-content>${current?detailMarkup():listMarkup()}</div>`;
 }
 function listMarkup(){
  const due=listing.records.flatMap(d=>d.followUps.map(f=>({...f,request:d.id,coach:d.coachName}))).sort((a,b)=>a.date.localeCompare(b.date));
  const upcoming=listing.records.filter(d=>d.status==='confirmed'||d.upcoming?.length).sort((a,b)=>(a.proposedAt||'').localeCompare(b.proposedAt||''));
  return `<div class="hub-card"><h2>Follow-ups on this page</h2>${due.length?due.map(f=>`<p>${esc(when(f.date))} · ${esc(f.coach)} · ${esc(f.owner)} ${f.overdue?'<strong>Overdue</strong>':''} ${button('Open','open',f.request)}</p>`).join(''):'<p>No outstanding follow-ups on this page.</p>'}<h2>Upcoming arrangements / unconfirmed visits</h2>${upcoming.length?upcoming.map(d=>`<p>${esc(when(d.proposedAt||d.upcoming[0]?.date))} · ${esc(d.coachName)} ${button('Open','open',d.id)}</p>`).join(''):'<p>No upcoming items on this page.</p>'}</div><div class="hub-card"><h2>Requests</h2>${listing.records.length?listing.records.map(d=>`<section class="hub-stage"><span class="hub-chip">${esc(d.status)}</span><h3>${esc(d.coachName)} · ${esc(d.seasonTeam)}</h3><p>${esc(d.ageGroup)} · ${esc(d.type)} · Mentor: ${esc(d.mentorName)}</p>${button('Open request','open',d.id)}</section>`).join(''):'<p>No requests for this account. Start with one coaching topic.</p>'}<div class="hub-actions">${page>1?button('Previous page','page',page-1):''}<span>Page ${page}</span>${listing.hasMore?button('Next page','page',page+1):''}</div></div>`;
 }
 function detailMarkup(){return `${button('← Requests','back')}<div class="hub-card">${requestForm(current)}</div><div class="hub-card"><h2>Linked visits</h2>${Object.values(current.visits||{}).map(v=>`<section class="hub-stage"><span class="hub-chip">${v.attendanceBy?'Delivered':v.draft?'Draft':'Planned'}</span><h3>${esc(v.date?when(v.date):'Date not yet entered')} · ${esc(v.type)}</h3><p>${esc(v.focus)}</p><p>Follow-up: ${esc(v.followUpDate?when(v.followUpDate):'not agreed')} · ${esc(v.result||'awaiting result')}</p>${button('Edit visit / follow-up','visit',v.id)}</section>`).join('')||'<p>No visits recorded.</p>'}${visitForm(current.visits?.[selectedVisit]||{})}</div><details class="hub-card"><summary>Change record — authorship and timestamps</summary><p>Created by adult account ${esc(current.createdBy)} · ${esc(current.createdAt)}. Latest version ${esc(current.version)}.</p><ol>${(current.history||[]).slice().reverse().map(h=>`<li>${esc(h.at)} · account ${esc(h.by)} · ${esc(h.action)} ${esc(h.visitId||'')} · ${esc((h.fields||[]).join(', '))}</li>`).join('')}</ol></details>`;}
 function paint(html){const target=root.querySelector('[data-support-content]');if(target){target.innerHTML=html;target.querySelector('h2')?.focus();}}
 function capture(form){const values=Object.fromEntries(new FormData(form));delete values.agree;delete values.accept;delete values.confirmAttendance;delete values.saveMode;return values;}
 function dirty(){return !!formDraft||!!visitDraft;}
 function mayLeave(){return !dirty()||window.confirm('Leave this page and discard the unsaved mentoring draft? Copy it first if needed.');}
 function clear(){current=null;formDraft=null;visitDraft=null;selectedVisit='';serial++;}
 root.addEventListener('input',e=>{const form=e.target.closest('[data-support-form]');if(!form)return;if(form.dataset.supportForm==='request')formDraft=capture(form);else visitDraft=capture(form);delete form._retryPayload;form.querySelector('[data-support-save]').textContent='Unsaved changes — copy before closing or signing out';});
 root.addEventListener('change',e=>{const form=e.target.closest('[data-support-form]');if(form){delete form._retryPayload;if(form.dataset.supportForm==='request')formDraft=capture(form);else visitDraft=capture(form);form.querySelector('[data-support-save]').textContent='Unsaved changes';}});
 root.addEventListener('click',async e=>{
  const b=e.target.closest('[data-support]');if(!b)return;e.preventDefault();if(busy)return;const action=b.dataset.support,id=b.dataset.id;
  if(!mayLeave())return;
  try {status('');formDraft=null;visitDraft=null;
   if(action==='cards'){paint(cards());return;}
   if(action==='new'){current=null;paint(`<div class="hub-card">${requestForm()}</div>`);return;}
   if(action==='open'){current=await api('support/'+id);if(!current)throw Error('Record unavailable.');selectedVisit='';paint(detailMarkup());return;}
   if(action==='visit'){selectedVisit=id;paint(detailMarkup());return;}
   if(action==='new-visit'){selectedVisit='';paint(detailMarkup());return;}
   if(action==='page')page=Number(id);
   current=null;const html=await render();root.querySelector('.hub-view').innerHTML=html;
  }catch(e){status(e.message,true);}
 });
 root.addEventListener('submit',async e=>{
  const form=e.target.closest('[data-support-form]');if(!form)return;e.preventDefault();e.stopImmediatePropagation();if(busy)return;busy=true;
  const fields=capture(form),isVisit=form.dataset.supportForm==='visit';
  const payload=form._retryPayload||{id:current?.id||0,version:current?.version||0,token:uuid(),action:isVisit?'visit':'request',fields,agree:!!form.elements.agree?.checked,accept:!!form.elements.accept?.checked,confirmAttendance:!!form.elements.confirmAttendance?.checked,complete:e.submitter?.value==='complete',...(isVisit&&selectedVisit?{visitId:selectedVisit}:{})};
  form._retryPayload=payload;form.querySelectorAll('button').forEach(b=>b.disabled=true);status('Saving mentoring record…');
  try {const saved=await save(payload);current=saved;formDraft=null;visitDraft=null;selectedVisit='';paint(detailMarkup());status(demo?'Saved synthetic record in this browser.':'Mentoring record saved.');}
  catch(error){form.querySelector('[data-support-save]').textContent='Not saved — draft retained here. Retry unchanged, or copy it and reopen the current record to reconcile.';status(error.message,true);}
  finally{busy=false;form.querySelectorAll('button').forEach(b=>b.disabled=false);}
 });
 window.addEventListener('beforeunload',e=>{if(dirty()||busy){e.preventDefault();e.returnValue='';}});
 const blank=(label)=>`<p class="hub-blank"><strong>${label}</strong><br>________________________________________________________________</p>`;
 function cards(){const card=(title,html)=>`<article class="hub-field-card"><h2>${title}</h2><p>Blank field template · adult coaching only · no child-identifying information</p>${html}</article>`;return `<div data-blank-cards>${heading('Mentoring field cards')}<p>Print blank templates only. Keep any completed paper securely under the approved policy.</p><div class="hub-actions">${button('Print blank field cards','print-cards')}${button('Back to requests','back')}</div>${card('Before the visit',blank('Season/team, age, time, location and assigned mentor')+blank('One coach behaviour / child-experience sign')+blank('Watch me / Show me / Work with me; intervention and hand-back')+blank('Supervision, emergency duties and accepted arrangement'))}${card('Observation and debrief',blank('Actual date/type and mentor; attendance confirmed by')+blank('Factual observation 1')+blank('Factual observation 2')+blank('Coach reflection / strength to keep')+blank('One next action / resource / follow-up owner and date'))}${card('Matchday support',`<h3>Before kick-off</h3><p>Connect to practice. Agree focus, location, quiet prompt, responsibilities and whole-match or timed support. Check eligibility, local playing-time/substitution rules and emergencies.</p>${blank('Our agreement')}<h3>During play</h3><p>Coach leads; mentor observes. Only agreed quiet prompts. No competing instructions, arguments or unauthorised entry. Act immediately on safety concerns.</p><h3>Half-time</h3><p>Water and recovery first. Coach leads; one observation and one achievable cue, with player input.</p><h3>After the final whistle</h3><p>Finish supervision and safe handover. Private adult debrief now or later; no public critique. Score is context, not the measure of success.</p>${blank('One learning observation / next-practice action')}`)}${card('Follow-up',blank('Request / visit reference and adult owner / date')+blank('Previous agreed action and resource')+blank('Tried / not yet / it did not help; what happened?')+blank('Next check / adaptation / closure reason'))}</div>`;}
 root.addEventListener('click',e=>{if(e.target.closest('[data-support="print-cards"]')){e.stopImmediatePropagation();window.print();}},true);
 return {render,mayLeave,clear,showCards:()=>paint(cards()),isBusy:()=>busy};
};
