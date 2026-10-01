/* Progressive enhancements: forms and navigation work without this file. */
document.addEventListener('DOMContentLoaded', () => {
  const reduced = matchMedia('(prefers-reduced-motion: reduce)').matches;
  if (!reduced && 'IntersectionObserver' in window) {
    const observer = new IntersectionObserver(entries => entries.forEach(entry => {
      if (entry.isIntersecting) { entry.target.classList.add('is-visible'); observer.unobserve(entry.target); }
    }), {threshold:0.06});
    document.querySelectorAll('.community-card,.service-card,.campus-card,.program-card,.section-heading').forEach(el => {
      el.classList.add('reveal-ready'); observer.observe(el);
    });
  }
  document.addEventListener('click', event => {
    const button=event.target.closest('.btn');
    if (!button || reduced || button.disabled) return;
    const circle=document.createElement('span'); circle.className='button-ripple';
    const rect=button.getBoundingClientRect();
    circle.style.left=`${event.clientX ? event.clientX-rect.left : rect.width/2}px`;
    circle.style.top=`${event.clientY ? event.clientY-rect.top : rect.height/2}px`;
    button.append(circle);circle.addEventListener('animationend',()=>circle.remove(),{once:true});
  });
  document.querySelectorAll('form').forEach(form => {
    form.addEventListener('invalid', event => {
      const input=event.target; input.setAttribute('aria-invalid','true');
      let message=document.getElementById(input.getAttribute('aria-describedby'));
      if(message && !message.classList.contains('field-error')) message=null;
      if (!message) {message=document.createElement('span');message.className='field-error';message.id=`error-${input.id||Math.random().toString(36).slice(2)}`;input.parentElement.after(message);input.setAttribute('aria-describedby',message.id);}
      message.textContent=input.validationMessage;
    },true);
    form.addEventListener('input',event=>{
      const field=event.target;
      if(field.validity?.valid){field.removeAttribute('aria-invalid');const message=document.getElementById(field.getAttribute('aria-describedby'));if(message?.classList.contains('field-error'))message.remove();}
    });
    form.addEventListener('submit',event=>{
      // Defer so existing confirmation/cancel handlers can run first.
      queueMicrotask(()=>{
        if(event.defaultPrevented||!form.checkValidity())return;
        event.submitter?.classList.add('is-loading');
        if(form.id==='filterForm'){
          const skeleton=document.createElement('div');skeleton.className='skeleton-loading';skeleton.setAttribute('role','status');
          skeleton.innerHTML='<span class="visually-hidden">Finding resources…</span><div></div><div></div><div></div>';document.body.append(skeleton);
        }
      });
    });
  });
  addEventListener('pageshow',()=>{document.querySelectorAll('.skeleton-loading').forEach(el=>el.remove());document.querySelectorAll('.is-loading,.hub-busy').forEach(el=>el.classList.remove('is-loading','hub-busy'));document.querySelectorAll('[aria-busy]').forEach(el=>el.removeAttribute('aria-busy'));});
  // Image optimization happens before submission, preserving ordinary PHP multipart handling.
  document.querySelectorAll('[data-optimize-image]').forEach(input=>{
    let pending=Promise.resolve();
    input.addEventListener('change',()=>{
      input.setCustomValidity('');
      const file=input.files[0]; if(!file)return;
      const message=document.createElement('p');message.className='small text-muted';message.setAttribute('role','status');input.after(message);
      pending=(async()=>{
        if(!['image/jpeg','image/png','image/webp'].includes(file.type)||file.size>5*1024*1024){input.setCustomValidity('Choose a JPG, PNG or WebP image up to 5 MB.');message.textContent=input.validationMessage;return;}
        if(!window.createImageBitmap || !window.DataTransfer){message.textContent='Image ready. Your browser will upload the original.';return;}
        message.textContent='Optimizing image…';
        try {
          const image=await createImageBitmap(file);
          if(image.width*image.height>24000000){image.close();throw new Error('Use an image up to 24 megapixels.');}
          const scale=Math.min(1,1600/Math.max(image.width,image.height));const canvas=document.createElement('canvas');
          canvas.width=Math.max(1,Math.round(image.width*scale));canvas.height=Math.max(1,Math.round(image.height*scale));canvas.getContext('2d').drawImage(image,0,0,canvas.width,canvas.height);image.close();
          const blob=await new Promise(resolve=>canvas.toBlob(resolve,'image/webp',.82));
          if(!blob)throw new Error('Unable to process this image. Try another file.');
          const transfer=new DataTransfer();transfer.items.add(new File([blob],blob.type==='image/webp'?'photo.webp':'photo.png',{type:blob.type}));input.files=transfer.files;
          message.textContent=`Ready · ${canvas.width} × ${canvas.height} · ${Math.ceil(blob.size/1024)} KB`;
        }catch(error){input.setCustomValidity(error.message);message.textContent=error.message;}
      })();
    });
    input.form?.addEventListener('submit',async event=>{
      if(input.dataset.ready==='true')return;
      event.preventDefault();const submitter=event.submitter;await pending;
      if(!input.form.reportValidity()){input.form.removeAttribute('aria-busy');submitter?.classList.remove('hub-busy');return;}
      input.dataset.ready='true';input.form.requestSubmit(submitter);delete input.dataset.ready;
    });
  });
  const categoryButtons=document.querySelectorAll('[data-team-filter]');
  categoryButtons.forEach(button=>button.addEventListener('click',()=>{
    categoryButtons.forEach(b=>b.setAttribute('aria-pressed',String(b===button)));
    let count=0;document.querySelectorAll('[data-team-category]').forEach(card=>{card.hidden=button.dataset.teamFilter!=='all'&&card.dataset.teamCategory!==button.dataset.teamFilter;if(!card.hidden)count++;});
    const status=document.getElementById('team-filter-status');if(status)status.textContent=`${count} team members`;
  }));
  document.querySelectorAll('[data-enhance-table],body.hub-admin table.table, .admin-sidebar ~ main table.table,body.page-dashboard table.table').forEach(enhanceHubTable);
});

function enhanceHubTable(table){
  if(table.dataset.enhanced||!table.tHead||!table.tBodies[0])return;table.dataset.enhanced='true';
  const headers=[...table.tHead.rows[0].cells];const rows=[...table.tBodies[0].rows].filter(row=>row.cells.length===headers.length&&!row.classList.contains('no-search'));
  if(!rows.length)return;
  table.classList.add('table-mobile-cards');rows.forEach(row=>[...row.cells].forEach((cell,i)=>cell.dataset.label=headers[i].textContent.trim()));
  const tools=document.createElement('div');tools.className='table-tools';
  const existingSearch=[...document.querySelectorAll('[data-table-search]')].find(input=>input.dataset.tableSearch.replace(/^#/,'')===table.id);
  const search=existingSearch||document.createElement('input');
  if(!existingSearch){search.type='search';search.className='form-control';search.placeholder='Search this table';search.setAttribute('aria-label','Search table records');}
  const size=document.createElement('select');size.className='form-select';size.setAttribute('aria-label','Rows per page');[10,25,50,100].forEach(n=>size.add(new Option(`${n} per page`,n)));
  const sort=document.createElement('select');sort.className='form-select';sort.setAttribute('aria-label','Sort records');sort.add(new Option('Original order',''));
  headers.forEach((header,i)=>{if(!/action/i.test(header.textContent)){sort.add(new Option(header.textContent.trim()+' ↑',i+':asc'));sort.add(new Option(header.textContent.trim()+' ↓',i+':desc'));}});
  if(!existingSearch)tools.append(search);tools.append(sort,size);const anchor=table.closest('.table-responsive')||table;anchor.before(tools);
  const pager=document.createElement('div');pager.className='table-pager';const info=document.createElement('span');info.setAttribute('aria-live','polite');
  const controls=document.createElement('div');const prev=document.createElement('button');const next=document.createElement('button');
  [prev,next].forEach(button=>{button.type='button';button.className='btn btn-sm btn-outline-secondary';});prev.textContent='Previous';next.textContent='Next';controls.append(prev,next);pager.append(info,controls);anchor.after(pager);
  let page=0;let ordered=[...rows];
  function render(){const query=search.value.trim().toLowerCase();const matches=ordered.filter(row=>row.textContent.toLowerCase().includes(query));const total=Math.max(1,Math.ceil(matches.length/Number(size.value)));page=Math.min(page,total-1);rows.forEach(row=>row.hidden=true);matches.slice(page*size.value,(page+1)*size.value).forEach(row=>row.hidden=false);info.textContent=matches.length?`${matches.length} records · Page ${page+1} of ${total}`:'No matching records. Try another search.';prev.disabled=page===0;next.disabled=page>=total-1;}
  search.addEventListener('input',()=>{page=0;render();});size.addEventListener('change',()=>{page=0;render();});prev.addEventListener('click',()=>{page--;render();});next.addEventListener('click',()=>{page++;render();});
  sort.addEventListener('change',()=>{const [index,direction]=sort.value.split(':');ordered=[...rows];if(sort.value)ordered.sort((a,b)=>a.cells[index].textContent.trim().localeCompare(b.cells[index].textContent.trim(),undefined,{numeric:true})*(direction==='asc'?1:-1));ordered.forEach(row=>table.tBodies[0].append(row));page=0;render();});render();
}
