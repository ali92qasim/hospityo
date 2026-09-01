import{j as t}from"./jquery.module-R5Nq7kwZ.js";import{s as m}from"./select2-CG5SnV5s.js";m(window,t);let s=1,l="",o="";const u=document.getElementById("prescription-form")?.dataset?.currencySymbol??"";function p(e){const n=e.closest(".prescription-item");if(!n)return;const i=n.querySelector(".medicine-price-hint");if(!i)return;const r=e.options[e.selectedIndex],c=r?.dataset?.price,a=r?.dataset?.unitAbbrev||"";if(!e.value||!c||parseFloat(c)<=0){i.classList.add("hidden"),i.textContent="";return}i.textContent=`Est. ${u} ${parseFloat(c).toFixed(2)} / ${a}`,i.classList.remove("hidden")}t(function(){l=t(".prescription-item").first().find(".medicine-select").html(),o=t(".prescription-item").first().find(".instruction-select").html(),d(t(".prescription-item").first()),window.addPrescriptionItem=function(){const e=t(`
            <div class="prescription-item border border-gray-200 rounded-lg p-3 mb-3">
                <div class="flex items-start gap-3">
                    <div class="flex-[2] min-w-0">
                        <label class="block text-xs font-medium text-gray-500 mb-1">Medicine</label>
                        <select name="medicines[${s}][medicine_id]" class="medicine-select w-full" required>
                            ${l}
                        </select>
                        <p class="medicine-price-hint text-xs text-gray-500 mt-1 hidden"></p>
                    </div>
                    <div class="flex-[2] min-w-0">
                        <label class="block text-xs font-medium text-gray-500 mb-1">Instruction</label>
                        <select name="medicines[${s}][instruction_id]" class="instruction-select w-full">
                            ${o}
                        </select>
                    </div>
                    <div class="w-20 flex-shrink-0">
                        <label class="block text-xs font-medium text-gray-500 mb-1">Qty</label>
                        <input type="number" name="medicines[${s}][quantity]" value="1" min="1" max="999"
                               class="w-full px-2 py-2 text-sm text-center border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue">
                    </div>
                    <div class="pt-5 flex-shrink-0">
                        <button type="button" class="remove-item-btn p-2 text-red-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="Remove">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            </div>
        `);t("#prescription-items").append(e),e.find(".medicine-select").val(""),e.find(".instruction-select").val(""),d(e),s++},t(document).on("click",".remove-item-btn",function(){t(".prescription-item").length>1&&t(this).closest(".prescription-item").remove()}),t(document).on("change",".medicine-select",function(){p(this)})});function d(e){if(typeof t.fn.select2=="function")try{e.find(".medicine-select").select2({placeholder:"Search medicine...",allowClear:!0,width:"100%"}),e.find(".instruction-select").select2({placeholder:"Select instruction",allowClear:!0,width:"100%"})}catch(n){console.error("Select2 init error:",n)}}
