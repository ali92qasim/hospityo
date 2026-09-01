import{j as d}from"./jquery.module-R5Nq7kwZ.js";import{i as u}from"./datatable-v8ySp_8g.js";import{c as m}from"./confirm-dialog-BrQV9gIW.js";function f(t){return t?t.charAt(0).toUpperCase()+t.slice(1):""}function c(t,e){const n=document.getElementById("medicine-import-result");if(!n)return;const i={success:"bg-green-50 border-green-200 text-green-800",warning:"bg-yellow-50 border-yellow-200 text-yellow-800",error:"bg-red-50 border-red-200 text-red-800"},s={success:"fa-check-circle",warning:"fa-exclamation-triangle",error:"fa-times-circle"};n.className="mb-4 border rounded-lg p-4 text-sm "+(i[t]||i.warning),n.innerHTML='<i class="fas '+(s[t]||s.warning)+' mr-1"></i>'+e,n.classList.remove("hidden")}function g(t){if(t.status==="failed"){c("error",t.message||"Import failed. Please check your file and try again.");return}const e="<strong>"+(t.created??0).toLocaleString()+"</strong> medicine(s) created, <strong>"+(t.updated??0).toLocaleString()+"</strong> updated.";if(t.errors&&t.errors.length>0){const n=t.errors.map(function(i){return"<li>"+i+"</li>"}).join("");c("warning",e+'<br><span class="font-medium mt-1 block">Warnings ('+t.errors.length+'):</span><ul class="list-disc list-inside text-xs mt-1 space-y-0.5 max-h-40 overflow-y-auto">'+n+"</ul>");return}c("success",e)}const r={category_id:"",status:"",low_stock:!1};let o=null;function l(){o&&o.ajax.reload(null,!1)}function p(){const t=document.getElementById("medicine-category-filter"),e=document.getElementById("medicine-status-filter"),n=document.getElementById("medicine-low-stock-filter"),i=document.getElementById("apply-medicine-filters"),s=document.getElementById("clear-medicine-filters");i?.addEventListener("click",()=>{r.category_id=t?.value??"",r.status=e?.value??"",r.low_stock=n?.checked??!1,l()}),s?.addEventListener("click",()=>{r.category_id="",r.status="",r.low_stock=!1,t&&(t.value=""),e&&(e.value=""),n&&(n.checked=!1),l()})}function y(t){const e=t.querySelector("[data-medicine-import-trigger]"),n=t.querySelector("[data-medicine-import-file]"),i=t.querySelector("[data-medicine-import-form]");!e||!n||!i||(e.addEventListener("click",function(){n.click()}),n.addEventListener("change",function(){if(!n.files||!n.files.length)return;const s=n.files[0].name;m({title:"Import medicines",message:'Import "'+s+'"?',detail:"The import runs in the background — you can continue using the application. Import categories, brands, and units first. Existing medicines with the same SKU will be updated.",confirmText:"Import",cancelText:"Cancel",variant:"success"}).then(function(a){if(a){i.submit();return}n.value=""})}))}d(document).ready(function(){const t=document.getElementById("medicines-index");t&&y(t),window.addEventListener("medicine-import-done",function(e){g(e.detail),l()}),p(),o=u(".medicines-table",{ajax:{url:"/medicines/data",data:function(e){e.category_id=r.category_id,e.status=r.status,e.low_stock=r.low_stock?1:0}},initComplete:function(){d(".medicines-table").removeClass("invisible").addClass("visible")},columns:[{data:"name",render:function(e,n,i){const s=i.strength??"",a=i.brand?.name?`<div class="text-xs text-gray-400">${i.brand.name}</div>`:"";return`
                        <div class="font-medium text-gray-900">${e??"-"}</div>
                        ${s?`<div class="text-sm text-gray-500">${s}</div>`:""}
                        ${a}
                    `}},{data:"sku",render:function(e){return`
                        <div class="text-sm font-mono text-gray-700 bg-gray-100 px-2 py-1 rounded inline-block">
                            ${e??"-"}
                        </div>
                    `}},{data:"category",render:function(e){return`<span class="text-sm text-gray-500">${e?.name??"-"}</span>`}},{data:"brand",visible:!1,searchable:!0},{data:"stock_quantity",orderable:!1,searchable:!1,render:function(e,n,i){if(!i.manage_stock)return`
                            <div class="text-sm text-gray-500">
                                <i class="fas fa-ban mr-1"></i>Not Managed
                            </div>
                        `;const s=i.is_low_stock?"text-red-600":"text-gray-900",a=i.is_low_stock?`<div class="text-xs text-red-500">Low Stock (Reorder: ${i.reorder_level??0})</div>`:"";return`
                        <div class="text-sm font-medium ${s}">
                            ${i.stock_quantity??0} ${i.stock_unit??""}
                        </div>
                        ${a}
                    `}},{data:"status",render:function(e){return`<span class="px-2 py-1 text-xs rounded-full ${e==="active"?"bg-green-100 text-green-800":"bg-red-100 text-red-800"}">${f(e??"")}</span>`}},{data:"id",orderable:!1,searchable:!1,render:function(e){return`
                        <div class="flex items-center space-x-3">
                            <a href="/medicines/${e}/edit" class="text-medical-blue hover:text-blue-700" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form method="POST" action="/medicines/${e}" data-confirm="Are you sure?" data-confirm-variant="danger" data-confirm-text="Delete">
                                <input type="hidden" name="_token" value="${window.csrf}">
                                <input type="hidden" name="_method" value="DELETE">
                                <button type="submit" class="text-red-600 hover:text-red-700" title="Delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    `}}]})});
