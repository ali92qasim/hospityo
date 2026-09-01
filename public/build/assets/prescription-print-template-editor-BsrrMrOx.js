const o=document.querySelector("#prescription-print-editor");if(o){const g=Number(o.dataset.paperWidthMm),p=Number(o.dataset.paperHeightMm),u=JSON.parse(o.dataset.fields||"{}"),i=JSON.parse(o.dataset.rx||"{}"),s=Math.min(2.5,760/g);let $="mm",c=null,a=null;o.innerHTML=`
        <div class="mb-4 flex items-center justify-end gap-2">
            <label for="prescription-editor-unit" class="text-sm font-medium text-gray-700">Display units</label>
            <select id="prescription-editor-unit" class="rounded-lg border-gray-300 text-sm">
                <option value="mm">Millimetres (mm)</option>
                <option value="in">Inches (in)</option>
            </select>
        </div>
        <div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_18rem]">
            <div class="overflow-auto rounded-lg bg-gray-100 p-4">
                <div data-editor-stage class="relative mx-auto overflow-hidden bg-white shadow-lg"></div>
            </div>
            <aside class="rounded-lg border border-gray-200 bg-gray-50 p-4">
                <h4 class="font-semibold text-gray-800">Field settings</h4>
                <p data-empty-settings class="mt-2 text-sm text-gray-500">Select a field on the page to edit its typography and visibility.</p>
                <div data-field-settings class="mt-4 hidden space-y-4">
                    <div>
                        <p data-field-name class="font-medium text-gray-800"></p>
                        <p data-field-position class="mt-1 text-xs text-gray-500"></p>
                    </div>
                    <label class="block text-sm font-medium text-gray-700">
                        Font size
                        <input data-setting="font_size" type="number" min="1" step="0.5"
                               class="mt-1 w-full rounded-lg border-gray-300">
                    </label>
                    <label class="block text-sm font-medium text-gray-700">
                        Font weight
                        <select data-setting="font_weight" class="mt-1 w-full rounded-lg border-gray-300">
                            <option value="normal">Normal</option>
                            <option value="bold">Bold</option>
                        </select>
                    </label>
                    <label class="block text-sm font-medium text-gray-700">
                        Alignment
                        <select data-setting="align" class="mt-1 w-full rounded-lg border-gray-300">
                            <option value="left">Left</option>
                            <option value="center">Centre</option>
                            <option value="right">Right</option>
                        </select>
                    </label>
                    <label class="flex items-center gap-2 text-sm font-medium text-gray-700">
                        <input data-setting="visible" type="checkbox" class="rounded border-gray-300 text-medical-blue">
                        Print this field
                    </label>
                </div>
                <div class="mt-6 border-t border-gray-200 pt-4">
                    <h4 class="font-semibold text-gray-800">Prescription region</h4>
                    <p data-rx-position class="mt-1 text-xs text-gray-500"></p>
                    <p class="mt-2 text-xs text-gray-500">Drag the region to move it. Drag its bottom handle to change row height.</p>
                </div>
            </aside>
        </div>
    `;const l=o.querySelector("[data-editor-stage]"),m=o.querySelector("[data-field-settings]"),z=o.querySelector("[data-empty-settings]"),M=o.querySelector("[data-field-name]"),k=o.querySelector("[data-field-position]"),C=o.querySelector("[data-rx-position]"),E=o.querySelector("#prescription-editor-unit"),L=o.dataset.backgroundUrl;if(l.style.width=`${g*s}px`,l.style.height=`${p*s}px`,L){const e=document.createElement("img");e.src=L,e.alt="",e.draggable=!1,e.className="pointer-events-none absolute inset-0 h-full w-full select-none object-fill",l.append(e)}const v=e=>document.querySelector(`[name="${e}"]`),_=(e,t)=>v(`fields[${e}][${t}]`),b=(e,t,r)=>Math.min(Math.max(e,t),r),x=e=>Math.round(e*100)/100,y=e=>$==="in"?`${(e/25.4).toFixed(2)} in`:`${e.toFixed(2)} mm`,w=()=>{if(!c)return;const e=u[c];k.textContent=`X ${y(Number(e.x_mm))} · Y ${y(Number(e.y_mm))}`},q=()=>{C.textContent=`Starts at ${y(Number(i.start_y))} · row height ${y(Number(i.row_height))}`},Y=e=>{c=e;const t=u[e];l.querySelectorAll("[data-field-key]").forEach(r=>{r.classList.toggle("ring-2",r.dataset.fieldKey===e),r.classList.toggle("ring-medical-blue",r.dataset.fieldKey===e)}),z.classList.add("hidden"),m.classList.remove("hidden"),M.textContent=t.label,m.querySelector('[data-setting="font_size"]').value=t.font_size,m.querySelector('[data-setting="font_weight"]').value=t.font_weight,m.querySelector('[data-setting="align"]').value=t.align,m.querySelector('[data-setting="visible"]').checked=!!t.visible,w()},N=(e,t)=>{e.style.left=`${Number(t.x_mm)*s}px`,e.style.top=`${Number(t.y_mm)*s}px`,e.style.fontSize=`${Math.max(9,Number(t.font_size)*s*.55)}px`,e.style.fontWeight=t.font_weight,e.style.textAlign=t.align,e.style.opacity=t.visible?"1":"0.4"};Object.entries(u).forEach(([e,t])=>{const r=document.createElement("button");r.type="button",r.dataset.fieldKey=e,r.textContent=t.label,r.className="absolute z-20 max-w-48 cursor-grab touch-none select-none truncate rounded border border-blue-400 bg-blue-50/90 px-2 py-1 text-blue-900 shadow-sm active:cursor-grabbing",N(r,t),r.addEventListener("click",()=>Y(e)),r.addEventListener("pointerdown",f=>{f.preventDefault(),Y(e),a={type:"field",key:e,startX:f.clientX,startY:f.clientY,originX:Number(t.x_mm),originY:Number(t.y_mm)},r.setPointerCapture(f.pointerId)}),l.append(r)});const n=document.createElement("div");n.dataset.rxRegion="",n.className="absolute z-10 cursor-move touch-none select-none border-2 border-dashed border-emerald-600 bg-emerald-50/40 text-emerald-900",n.style.left=`${15*s}px`,n.style.width=`${(g-30)*s}px`;const h=document.createElement("div");h.className="pointer-events-none flex h-full flex-col overflow-hidden",n.append(h);const d=document.createElement("button");d.type="button",d.title="Resize prescription row height",d.setAttribute("aria-label","Resize prescription row height"),d.className="absolute -bottom-2 left-1/2 h-4 w-16 -translate-x-1/2 cursor-ns-resize rounded-full bg-emerald-600",n.append(d),l.append(n);const S=()=>{const e=Number(i.row_height)*Number(i.max_rows);n.style.top=`${Number(i.start_y)*s}px`,n.style.height=`${e*s}px`,h.replaceChildren();for(let t=1;t<=Number(i.max_rows);t+=1){const r=document.createElement("div");r.textContent=`${t}. Sample medicine`,r.className="border-b border-emerald-300 px-2 text-left",r.style.height=`${Number(i.row_height)*s}px`,r.style.fontSize=`${Math.max(8,Number(i.row_height)*s*.45)}px`,h.append(r)}q()};n.addEventListener("pointerdown",e=>{e.target!==d&&(e.preventDefault(),a={type:"rx-move",startY:e.clientY,originY:Number(i.start_y)},n.setPointerCapture(e.pointerId))}),d.addEventListener("pointerdown",e=>{e.preventDefault(),e.stopPropagation(),a={type:"rx-resize",startY:e.clientY,originHeight:Number(i.row_height)},d.setPointerCapture(e.pointerId)}),o.addEventListener("pointermove",e=>{if(a){if(a.type==="field"){const t=u[a.key];t.x_mm=x(b(a.originX+(e.clientX-a.startX)/s,0,g)),t.y_mm=x(b(a.originY+(e.clientY-a.startY)/s,0,p)),_(a.key,"x_mm").value=t.x_mm,_(a.key,"y_mm").value=t.y_mm,N(l.querySelector(`[data-field-key="${a.key}"]`),t),w()}if(a.type==="rx-move"){const t=Number(i.row_height)*Number(i.max_rows);i.start_y=x(b(a.originY+(e.clientY-a.startY)/s,0,Math.max(0,p-t))),v("rx_start_y").value=i.start_y,S()}if(a.type==="rx-resize"){const t=(e.clientY-a.startY)/s/Number(i.max_rows),r=Math.max(1,(p-Number(i.start_y))/Number(i.max_rows));i.row_height=x(b(a.originHeight+t,1,r)),v("rx_row_height").value=i.row_height,S()}}}),o.addEventListener("pointerup",()=>{a=null}),o.addEventListener("pointercancel",()=>{a=null}),m.querySelectorAll("[data-setting]").forEach(e=>{e.addEventListener("change",()=>{if(!c)return;const t=e.dataset.setting,r=u[c];r[t]=t==="visible"?e.checked:t==="font_size"?Number(e.value):e.value,_(c,t).value=t==="visible"?r.visible?"1":"0":r[t],N(l.querySelector(`[data-field-key="${c}"]`),r)})}),E.addEventListener("change",()=>{$=E.value,w(),q()}),S()}
