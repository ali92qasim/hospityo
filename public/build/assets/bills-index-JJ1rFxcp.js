import{j as n}from"./jquery.module-R5Nq7kwZ.js";import{i as l}from"./datatable-v8ySp_8g.js";function r(e){const t=Number(e||0).toLocaleString(void 0,{minimumFractionDigits:2,maximumFractionDigits:2});return(window.appConfig?.currency||"")+" "+t}function o(e){return{paid:"green",partial:"yellow",pending:"red",draft:"blue",cancelled:"gray"}[e]||"gray"}n(document).ready(function(){l(".bills-table",{ajax:"/bills/data",initComplete:function(){n(".bills-table").removeClass("invisible").addClass("visible")},columns:[{data:"bill_number",render:function(e){return`<div class="font-medium text-gray-900">${e}</div>`}},{data:"patient",render:function(e){return e?`
                        <div class="text-sm text-gray-900">${e.name??"-"}</div>
                        <div class="text-sm text-gray-500">${e.phone??""}</div>
                    `:"-"}},{data:"bill_type",render:function(e){return`<span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded uppercase">${e??""}</span>`}},{data:"total_amount",render:function(e,t,i){const a=Number(i.due_amount||0),s=a>0?`<div class="text-sm text-red-500">Due: ${r(a)}</div>`:"";return`
                        <div class="text-sm text-gray-900">${r(e)}</div>
                        ${s}
                    `}},{data:"status",render:function(e){const t=o(e);return`<span class="bg-${t}-100 text-${t}-800 text-xs px-2 py-1 rounded capitalize">${e??""}</span>`}},{data:"bill_date",render:function(e){return e?new Date(e).toLocaleDateString(void 0,{month:"short",day:"numeric",year:"numeric"}):"-"}},{data:"id",orderable:!1,searchable:!1,render:function(e){return`
                        <div class="flex items-center space-x-3">
                            <a href="/bills/${e}" class="text-medical-blue hover:text-blue-700" title="View">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="/bills/${e}/print" target="_blank" class="text-green-600 hover:text-green-700" title="Print">
                                <i class="fas fa-print"></i>
                            </a>
                            <a href="/bills/${e}/edit" class="text-medical-blue hover:text-blue-700" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form method="POST" action="/bills/${e}" data-confirm="Are you sure?" data-confirm-variant="danger" data-confirm-text="Delete">
                                <input type="hidden" name="_token" value="${window.csrf}">
                                <input type="hidden" name="_method" value="DELETE">
                                <button type="submit" class="text-red-600 hover:text-red-700" title="Delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    `}}]})});
