import{j as e}from"./jquery.module-R5Nq7kwZ.js";import{s as f}from"./select2-CG5SnV5s.js";f(window,e);let s=1,u="";const p=document.getElementById("order-form")?.dataset.itemFk||"lab_test_id";e(function(){const n=e("#items-body");s=parseInt(n.data("row-index"),10)||e(".item-row").length,u=e(".item-row").first().find(".investigation-select").html(),g(),e(".item-row").each(function(){m(e(this))}),e(".investigation-select").each(function(){l(this)}),e("#add-investigation-row").on("click",y),e(document).on("click",".remove-row-btn",function(){b(e(this))}),e("#order-form").on("submit",function(t){w()&&(t.preventDefault(),window.Toast&&window.Toast.error("Please remove duplicate rows before submitting."))}),a()});function g(){typeof e.fn.select2=="function"&&(e("#patient_id").select2({placeholder:"Search patient by name or number...",allowClear:!0,width:"100%"}),e("#doctor_id").select2({placeholder:"Search doctor by name or specialization...",allowClear:!0,width:"100%"}))}function m(n){const t=n.find(".investigation-select");if(!(!t.length||typeof e.fn.select2!="function"))try{t.hasClass("select2-hidden-accessible")&&t.select2("destroy"),t.select2({placeholder:"Search...",allowClear:!0,width:"100%"}),t.off("change.investigationOrder select2:select.investigationOrder select2:clear.investigationOrder"),t.on("change.investigationOrder select2:select.investigationOrder select2:clear.investigationOrder",function(){l(this)})}catch(o){console.error("Select2 init error:",o)}}function h(n){return e(".investigation-select").not(n).map(function(){return e(this).val()}).get().filter(t=>t!==""&&t!=null)}function l(n){const t=e(n),o=t.closest("td")[0];let i=o.querySelector(".dup-warning");i||(i=document.createElement("p"),i.className="dup-warning text-xs text-red-600 mt-1",i.textContent="Already added. Remove the duplicate row.",o.appendChild(i));const r=t.val(),c=r!==""&&r!=null&&h(n).includes(String(r));i.style.display=c?"block":"none";const d=t.next(".select2-container").find(".select2-selection");d.length?d.toggleClass("select2-selection--duplicate-error",c):n.classList.toggle("border-red-400",c)}function w(){const n=e(".investigation-select").map(function(){return e(this).val()}).get().filter(t=>t!==""&&t!=null);return n.length!==new Set(n).size}function y(){const n=e(`
        <tr class="item-row border-t border-gray-100">
            <td class="px-4 py-2">
                <select name="items[${s}][${p}]" class="investigation-select w-full px-2 py-1.5 border border-gray-300 rounded-md focus:ring-2 focus:ring-medical-blue text-sm" required>
                    ${u}
                </select>
            </td>
            <td class="px-4 py-2">
                <input type="number" name="items[${s}][quantity]" value="1" min="1" max="99" class="w-full px-2 py-1.5 text-center border border-gray-300 rounded-md focus:ring-2 focus:ring-medical-blue text-sm" required>
            </td>
            <td class="px-4 py-2">
                <select name="items[${s}][priority]" class="w-full px-2 py-1.5 border border-gray-300 rounded-md focus:ring-2 focus:ring-medical-blue text-sm" required>
                    <option value="routine" selected>Routine</option>
                    <option value="urgent">Urgent</option>
                    <option value="stat">STAT</option>
                </select>
            </td>
            <td class="px-4 py-2">
                <input type="text" name="items[${s}][clinical_notes]" class="w-full px-2 py-1.5 border border-gray-300 rounded-md focus:ring-2 focus:ring-medical-blue text-sm" placeholder="Optional notes...">
            </td>
            <td class="px-4 py-2 text-center">
                <button type="button" class="text-red-400 hover:text-red-600 transition-colors remove-row-btn remove-btn" title="Remove">
                    <i class="fas fa-times"></i>
                </button>
            </td>
        </tr>
    `);e("#items-body").append(n),n.find(".investigation-select").val(""),m(n),a(),s++}function b(n){const t=n.closest("tr"),o=t.find(".investigation-select");if(o.hasClass("select2-hidden-accessible"))try{o.select2("destroy")}catch(i){console.error("Select2 destroy error:",i)}t.remove(),a(),e(".investigation-select").each(function(){l(this)})}function a(){const n=e(".item-row");n.each(function(){const t=e(this).find(".remove-btn");t.length&&t.css("display",n.length>1?"inline":"none")})}
