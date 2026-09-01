import{j as n}from"./jquery.module-R5Nq7kwZ.js";import{i as s}from"./datatable-v8ySp_8g.js";n(document).ready(function(){n(".investigations-table").each(function(){const a=n(this),r=a.data("ajax-url")||"/investigations/data";s(a,{ajax:r,initComplete:function(){a.removeClass("invisible").addClass("visible")},columns:[{data:"code"},{data:"name"},{data:"category",render:function(e){return`<span class="px-2 py-1 text-xs font-medium rounded-full ${{hematology:"bg-red-100 text-red-800",biochemistry:"bg-yellow-100 text-yellow-800",microbiology:"bg-green-100 text-green-800",immunology:"bg-indigo-100 text-indigo-800",histopathology:"bg-pink-100 text-pink-800",molecular:"bg-cyan-100 text-cyan-800","x-ray":"bg-purple-100 text-purple-800",ultrasound:"bg-blue-100 text-blue-800","ct-scan":"bg-orange-100 text-orange-800",mri:"bg-teal-100 text-teal-800","cardiac-diagnostics":"bg-rose-100 text-rose-800"}[e]||"bg-gray-100 text-gray-800"}">
                    ${e.replace(/-/g," ")}
                </span>`}},{data:"price",render:function(e){const t=Number(e||0).toLocaleString();return(window.appConfig?.currency||"")+" "+t}},{data:"turnaround_time",render:e=>e??"-"},{data:"is_active",render:function(e){return e?'<span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">Active</span>':'<span class="px-2 py-1 text-xs rounded-full bg-red-100 text-red-800">Inactive</span>'}},{data:"id",orderable:!1,searchable:!1,render:function(e){const t=a.data("resource-base")||"/lab/tests";return`
                <div class="flex items-center space-x-3">

                    <a href="${t}/${e}" class="text-blue-600">
                        <i class="fas fa-eye"></i>
                    </a>

                    <a href="${t}/${e}/edit" class="text-yellow-600">
                        <i class="fas fa-edit"></i>
                    </a>

                    <form method="POST" action="${t}/${e}" data-confirm="Delete this item?" data-confirm-variant="danger" data-confirm-text="Delete">
                        <input type="hidden" name="_token" value="${window.csrf}">
                        <input type="hidden" name="_method" value="DELETE">
                        <button class="text-red-600">
                            <i class="fas fa-trash"></i>
                        </button>
                    </form>

                </div>
            `}}]})})});
