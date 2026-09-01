import{j as t}from"./jquery.module-R5Nq7kwZ.js";import{s as p}from"./select2-CG5SnV5s.js";import{f as o}from"./index-BQtzFXJz.js";p(window,t);let c=1,a="";const s=document.querySelector(".price-header")?.textContent.match(/\(([^)]+)\)/)?.[1]??"";function u(e){const n=window._purchaseMedicineUnits?.[e];if(!n)return[];const i=n.base_unit_id;return(window._allUnits??[]).filter(r=>r.base_unit_id===i||r.id===i)}function m(e,n){const i=u(n);e.empty().append('<option value="">Select unit</option>'),i.forEach(r=>{e.append(t("<option></option>").val(r.id).text(`${r.name} (${r.abbreviation})`).attr("data-abbrev",r.abbreviation))}),e.val("")}function l(e){const n=e.find(".unit-select option:selected").data("abbrev")||"",i=n?`Purchase price per ${n} (${s})`:`Purchase price (${s})`;t(".price-header").text(i)}function f(e){const n=e.find(".medicine-select").val();m(e.find(".unit-select"),n),l(e)}function y(e){l(e)}t(function(){a=t(".item-row").first().find(".medicine-select").html(),t("#supplier-select").select2({placeholder:"Search supplier...",allowClear:!0,width:"100%"}),d(t(".item-row").first()),o("#order-date",{dateFormat:"Y-m-d",defaultDate:new Date,allowInput:!0}),o("#expected-delivery",{dateFormat:"Y-m-d",allowInput:!0}),window.addItem=function(){const e=t(`
            <tr class="item-row">
                <td class="px-4 py-3">
                    <select name="items[${c}][medicine_id]" class="medicine-select w-full px-2 py-1 border border-gray-300 rounded text-sm" required>
                        ${a}
                    </select>
                </td>
                <td class="px-4 py-3">
                    <select name="items[${c}][unit_id]" class="unit-select w-full px-2 py-1 border border-gray-300 rounded text-sm" required>
                        <option value="">Select unit</option>
                    </select>
                </td>
                <td class="px-4 py-3">
                    <input type="number" name="items[${c}][quantity]" min="1" class="w-full px-2 py-1 border border-gray-300 rounded text-sm quantity-input" required>
                </td>
                <td class="px-4 py-3">
                    <input type="number" name="items[${c}][unit_price]" step="0.01" min="0" class="w-full px-2 py-1 border border-gray-300 rounded text-sm price-input" required>
                </td>
                <td class="px-4 py-3">
                    <span class="total-display">0.00</span>
                </td>
                <td class="px-4 py-3 text-center">
                    <button type="button" class="remove-item-btn text-red-600 hover:text-red-800">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `);t("#items-table").append(e),e.find(".medicine-select").val(""),d(e),c++},t(document).on("click",".remove-item-btn",function(){t(".item-row").length>1&&t(this).closest("tr").remove()}),t(document).on("change",".medicine-select",function(){f(t(this).closest("tr"))}),t(document).on("change",".unit-select",function(){y(t(this).closest("tr"))}),t(document).on("input change",".quantity-input, .price-input",function(){const e=t(this).closest("tr"),n=parseFloat(e.find(".quantity-input").val())||0,i=parseFloat(e.find(".price-input").val())||0;e.find(".total-display").text((n*i).toFixed(2))})});function d(e){if(typeof t.fn.select2=="function")try{e.find(".medicine-select").select2({placeholder:"Search medicine...",allowClear:!0,width:"100%"})}catch(n){console.error("Select2 init error:",n)}}
