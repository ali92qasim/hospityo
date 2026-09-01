import{j as a}from"./jquery.module-R5Nq7kwZ.js";import{i as n}from"./datatable-v8ySp_8g.js";function r(e){return e?e.charAt(0).toUpperCase()+e.slice(1):""}function d(e){const i=Number(e||0).toLocaleString(void 0,{minimumFractionDigits:2,maximumFractionDigits:2});return(window.appConfig?.currency||"")+" "+i}a(document).ready(function(){n(".doctors-table",{ajax:"/doctors/data",initComplete:function(){a(".doctors-table").removeClass("invisible").addClass("visible")},columns:[{data:"name",render:function(e,i,t){return`
                        <div class="flex items-center">
                            <div class="w-9 h-9 bg-medical-green rounded-full flex items-center justify-center mr-3 shrink-0">
                                <i class="fas fa-user-md text-white text-sm"></i>
                            </div>
                            <div class="min-w-0">
                                <div class="text-sm font-medium text-gray-900 truncate max-w-[160px]" title="${e??""}">${e??"-"}</div>
                                <div class="text-sm text-gray-500">${t.doctor_no??""}</div>
                                <div class="text-xs text-gray-400">${t.experience_years??0} yrs exp.</div>
                            </div>
                        </div>
                    `}},{data:"specialization",render:function(e,i,t){return`
                        <div class="text-sm text-gray-900 truncate max-w-[130px]" title="${e??""}">${e??"-"}</div>
                        <div class="text-xs text-gray-500 truncate max-w-[130px]">${t.qualification??""}</div>
                    `}},{data:"phone",render:function(e,i,t){return`
                        <div class="text-sm text-gray-900">${e??"-"}</div>
                        <div class="text-xs text-gray-500 truncate max-w-[140px]" title="${t.email??""}">${t.email??""}</div>
                    `}},{data:"shift_start",render:function(e,i,t){const s=t.shift_end??"";return`
                        <div class="text-sm text-gray-900 whitespace-nowrap">${e&&s?`${e} - ${s}`:"-"}</div>
                        <div class="text-xs text-gray-500">${d(t.consultation_fee)}</div>
                    `}},{data:"status",render:function(e){return`<span class="px-2 py-1 text-xs rounded-full ${e==="active"?"bg-green-100 text-green-800":"bg-red-100 text-red-800"}">${r(e??"")}</span>`}},{data:"id",orderable:!1,searchable:!1,render:function(e){return`
                        <div class="flex items-center space-x-1">
                            <a href="/doctors/${e}" class="inline-flex items-center justify-center w-7 h-7 rounded text-medical-blue hover:bg-blue-50" title="View">
                                <i class="fas fa-eye text-xs"></i>
                            </a>
                            <a href="/doctors/${e}/edit" class="inline-flex items-center justify-center w-7 h-7 rounded text-medical-green hover:bg-green-50" title="Edit">
                                <i class="fas fa-edit text-xs"></i>
                            </a>
                            <form method="POST" action="/doctors/${e}" data-confirm="Are you sure?" data-confirm-variant="danger" data-confirm-text="Delete">
                                <input type="hidden" name="_token" value="${window.csrf}">
                                <input type="hidden" name="_method" value="DELETE">
                                <button type="submit" class="inline-flex items-center justify-center w-7 h-7 rounded text-red-600 hover:bg-red-50" title="Delete">
                                    <i class="fas fa-trash text-xs"></i>
                                </button>
                            </form>
                        </div>
                    `}}]})});
