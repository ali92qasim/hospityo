import{j as n}from"./jquery.module-R5Nq7kwZ.js";import{i as u}from"./datatable-v8ySp_8g.js";function r(e){return e?e.charAt(0).toUpperCase()+e.slice(1):""}function m(e){const i=document.getElementById("patients-index");if(i?.dataset.canCreateVisits!=="1")return"";const t=i.dataset.quickRegisterUrl,a=document.querySelector('meta[name="csrf-token"]')?.content??"";if(!t||!a)return"";const s=(l,c,d,o)=>`
        <form method="POST" action="${t}" class="inline">
            <input type="hidden" name="_token" value="${a}">
            <input type="hidden" name="patient_id" value="${e}">
            <input type="hidden" name="visit_type" value="${l}">
            <input type="hidden" name="from" value="patients">
            <button type="submit" class="${o}" title="${d}">
                <i class="fas ${c}"></i>
            </button>
        </form>
    `;return`
        ${s("opd","fa-stethoscope","Register OPD visit and open workflow","text-blue-600 hover:text-blue-800")}
        ${s("emergency","fa-ambulance","Register Emergency visit and open workflow","text-red-600 hover:text-red-800")}
    `}n(document).ready(function(){u(".patients-table",{ajax:"/patients/data",initComplete:function(){n(".patients-table").removeClass("invisible").addClass("visible")},columns:[{data:"name",render:function(e,i,t){const a=r(t.gender),s=t.age??"-";return`
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-medical-blue rounded-full flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-user text-white"></i>
                            </div>
                            <div class="min-w-0">
                                <div class="text-sm font-medium text-gray-900 truncate">${e??"-"}</div>
                                <div class="text-sm text-gray-500">${t.patient_no??""}</div>
                                <div class="text-xs text-gray-400">${a}, ${s} years</div>
                            </div>
                        </div>
                    `}},{data:"phone",render:function(e,i,t){const a=t.marital_status?`<div class="text-xs text-gray-500">${r(t.marital_status)}</div>`:"";return`
                        <div class="text-sm text-gray-900">${e??"-"}</div>
                        ${a}
                    `}},{data:"emergency_name",render:function(e,i,t){const a=t.emergency_phone?`<div class="text-xs text-gray-500">${t.emergency_phone}${t.emergency_relation?` (${t.emergency_relation})`:""}</div>`:"";return`
                        <div class="text-sm text-gray-900">${e??"—"}</div>
                        ${a}
                    `}},{data:"id",orderable:!1,searchable:!1,render:function(e){return`
                        <div class="flex items-center space-x-3">
                            ${m(e)}
                            <a href="/patients/${e}" class="text-medical-blue hover:text-blue-700" title="View Details">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="/patients/${e}/history" class="text-purple-600 hover:text-purple-700" title="Patient History">
                                <i class="fas fa-history"></i>
                            </a>
                            <a href="/patients/${e}/edit" class="text-medical-green hover:text-green-700" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                        </div>
                    `}}]})});
