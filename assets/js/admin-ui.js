document.addEventListener('DOMContentLoaded', () => {
 const sidebar=document.getElementById('adminSidebarCollapse'); if(!sidebar)return;
 document.body.classList.add('enterprise-admin');
 const main=sidebar.nextElementSibling;
 const bar=document.querySelector('.enterprise-topbar');
 if(!main||!bar)return;
 main.classList.add('enterprise-main');main.prepend(bar);
 const navigation=sidebar.querySelector('.flex-grow-1');
 if(navigation){
   const original=[...navigation.querySelectorAll('a[href]')];
   const groups=[['Overview',['dashboard.php']],['Content management',['add-resource.php','manage-resource.php','manage-team.php','cms-content.php','content.php','media.php']],['Commerce',['manage-orders.php']],['Users & access',['manage-students.php','manage-admins.php']],['Analytics',['sales-reports.php']],['Workspace',[]]];
   const fragment=document.createDocumentFragment();const used=new Set();
   groups.forEach(([label,paths])=>{const items=original.filter(a=>!used.has(a)&&((paths.includes(new URL(a.href).pathname.split('/').pop())&&!new URL(a.href).hash)||(label==='Workspace')));if(!items.length)return;const section=document.createElement('section');const h=document.createElement('div');h.className='small text-uppercase fw-bold text-white-50 mb-2';h.textContent=label;section.append(h);const destinations=new Set();items.forEach(a=>{used.add(a);if(!destinations.has(a.href)){section.append(a);destinations.add(a.href);}});fragment.append(section);});
   navigation.replaceChildren(fragment);
   const cms=[...navigation.querySelectorAll('a')].find(a=>new URL(a.href).pathname.endsWith('/cms-content.php'));
   if(cms){[['Testimonials','#tab-testimonials','bi-chat-quote'],['Notices','#tab-notices','bi-megaphone'],['Page sections','#tab-hero','bi-layout-text-window']].forEach(([label,hash,icon])=>{const link=document.createElement('a');link.href=cms.href+hash;const glyph=document.createElement('i');glyph.className=`bi ${icon}`;link.append(glyph,document.createTextNode(label));cms.parentElement.append(link);});}
   const dashboard=[...navigation.querySelectorAll('a')].find(a=>new URL(a.href).pathname.endsWith('/dashboard.php')&&!new URL(a.href).hash);
   if(dashboard){const section=document.createElement('section');const label=document.createElement('div');label.className='small text-uppercase fw-bold text-white-50 mb-2';label.textContent='Academic services';const link=document.createElement('a');link.href=dashboard.href+'#tab-inquiries';link.innerHTML='<i class="bi bi-headset"></i> Service requests';section.append(label,link);navigation.append(section);}
 }
 const analytics=main.querySelector('section[aria-label="Sales analytics"]'), kpi=main.querySelector('.admin-kpi-card')?.closest('.row');
 if(analytics&&kpi)kpi.after(analytics);
 const activateHash=()=>{if(!location.hash)return;const target=[...main.querySelectorAll('[data-bs-target]')].find(el=>el.dataset.bsTarget===location.hash);if(target)bootstrap.Tab.getOrCreateInstance(target).show();};activateHash();addEventListener('hashchange',activateHash);
 const heading=main.querySelector('h1,h2.h3');
 bar.querySelector('.enterprise-page-title').textContent=heading?.textContent.trim()||'Workspace';
 if(/\/(add|edit)-resource\.php$/.test(location.pathname)){
   const marked=new Set();
   [['title','Resource information'],['price','Pricing & document details'],['resource_file','Files & preview'],['visibility','Visibility settings']].forEach(([name,label])=>{
     const field=main.querySelector(`[name="${name}"]`);if(!field)return;
     const block=field.closest('.row')||field.closest('.mb-3');if(!block||marked.has(block))return;marked.add(block);
     const h=document.createElement('h3');h.className='enterprise-form-heading';h.textContent=label;block.before(h);
   });
 }
 const toggle=bar.querySelector('.enterprise-collapse');
 sidebar.addEventListener('shown.bs.collapse',()=>toggle.setAttribute('aria-expanded','true'));
 sidebar.addEventListener('hidden.bs.collapse',()=>toggle.setAttribute('aria-expanded','false'));
 toggle.addEventListener('click',()=>{
   if(innerWidth<768){bootstrap.Collapse.getOrCreateInstance(sidebar).toggle();return;}
   const collapsed=document.body.classList.toggle('enterprise-compact');
   toggle.setAttribute('aria-expanded',String(!collapsed));toggle.setAttribute('aria-label',collapsed?'Expand sidebar':'Collapse sidebar');
 });
 sidebar.querySelectorAll('a').forEach(a=>{a.title=a.textContent.trim();if(a.classList.contains('active'))a.setAttribute('aria-current','page');});
 const search=bar.querySelector('input[type=search]'), results=bar.querySelector('.enterprise-search-results');
 search.addEventListener('input',()=>{
   const q=search.value.trim().toLowerCase();results.replaceChildren();results.hidden=!q;if(!q)return;
   const seen=new Set();const links=[...sidebar.querySelectorAll('a[href]')].filter(a=>a.textContent.toLowerCase().includes(q)&&!seen.has(a.href)&&seen.add(a.href));
   links.slice(0,7).forEach(a=>{const link=document.createElement('a');link.href=a.href;link.textContent=a.textContent.trim();results.append(link);});
   if(!links.length){const empty=document.createElement('p');empty.textContent='No matching page';results.append(empty);}
 });
 document.addEventListener('click',event=>{if(!event.target.closest('.enterprise-search'))results.hidden=true;});
 search.addEventListener('keydown',event=>{if(event.key==='Escape'){results.hidden=true;search.value='';}if(event.key==='ArrowDown'){event.preventDefault();results.querySelector('a')?.focus();}});
 document.querySelectorAll('input[name="permissions[]"]').forEach(input=>input.closest('.form-check')?.classList.add('enterprise-permission'));
 const orders=main.querySelector('#ordersTable');
 if(orders){
   const modal=document.createElement('div');modal.className='modal fade';modal.id='enterpriseOrderDetails';modal.tabIndex=-1;modal.setAttribute('aria-labelledby','enterpriseOrderTitle');modal.innerHTML='<div class="modal-dialog modal-dialog-centered"><div class="modal-content"><div class="modal-header"><h2 class="modal-title h5" id="enterpriseOrderTitle">Order details</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close order details"></button></div><div class="modal-body"><dl class="mb-0"></dl></div></div></div>';document.body.append(modal);
   const headers=[...orders.tHead.rows[0].cells];
   [...orders.tBodies[0].rows].filter(row=>row.cells.length===headers.length).forEach(row=>{
     const button=document.createElement('button');button.type='button';button.className='btn btn-sm btn-outline-secondary';button.textContent='Details';button.addEventListener('click',()=>{const list=modal.querySelector('dl');list.replaceChildren();[...row.cells].slice(0,-1).forEach((cell,i)=>{const term=document.createElement('dt');term.className='small text-muted mb-1';term.textContent=headers[i].textContent.trim();const detail=document.createElement('dd');detail.className='mb-3';detail.textContent=cell.textContent.trim();list.append(term,detail);});bootstrap.Modal.getOrCreateInstance(modal).show();});row.cells[row.cells.length-1].append(button);
   });
 }
 document.querySelectorAll('input[type=file]').forEach(input=>{
   const zone=document.createElement('div');zone.className='enterprise-upload';input.before(zone);zone.append(input);
   const intro=document.createElement('div');intro.className='enterprise-upload-intro';intro.innerHTML='<i class="bi bi-cloud-arrow-up"></i><strong>Choose a file or drop it here</strong><span>Use the formats and limits shown below.</span>';zone.prepend(intro);
   const status=document.createElement('div');status.className='enterprise-upload-status';status.setAttribute('role','status');zone.append(status);
   const clear=document.createElement('button');clear.type='button';clear.className='btn btn-sm btn-outline-secondary';clear.textContent='Remove selection';clear.hidden=true;zone.append(clear);
   const render=()=>{const file=input.files?.[0];status.textContent=file?`${file.name} · ${(file.size/1024).toFixed(1)} KB`:'';clear.hidden=!file;};input.addEventListener('change',render);
   clear.addEventListener('click',()=>{input.value='';input.dispatchEvent(new Event('change',{bubbles:true}));render();});
   zone.addEventListener('dragover',e=>{e.preventDefault();zone.classList.add('drag-over');});zone.addEventListener('dragleave',()=>zone.classList.remove('drag-over'));
   zone.addEventListener('drop',e=>{e.preventDefault();zone.classList.remove('drag-over');if(e.dataTransfer?.files.length){const transfer=new DataTransfer();transfer.items.add(e.dataTransfer.files[0]);input.files=transfer.files;input.dispatchEvent(new Event('change',{bubbles:true}));}});
 });
 document.querySelectorAll('.table').forEach(table=>{table.querySelectorAll('tbody tr').forEach(row=>{const cell=row.cells[row.cells.length-1];if(!cell||cell.querySelector('input,form,select,textarea')||cell.querySelectorAll('a,button').length<3)return;const details=document.createElement('details');details.className='enterprise-actions';const summary=document.createElement('summary');summary.textContent='Actions';details.append(summary);while(cell.firstChild)details.append(cell.firstChild);cell.append(details);});});
});
