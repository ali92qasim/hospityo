import{j as t}from"./jquery.module-R5Nq7kwZ.js";import{s as w}from"./select2-CG5SnV5s.js";import{f as _}from"./index-BQtzFXJz.js";w(window,t);let a=window._billItemCount??1,u="",m="",p="";const v=window._currencySymbol||"",c=parseFloat(window._billPaidAmount)||0;t(function(){const n=t(".bill-item").first();u=n.find(".service-select").html(),m=n.find(".lab-test-select").html(),p=n.find(".imaging-study-select").html(),t("#patient_id").select2({placeholder:"Select Patient",allowClear:!0,width:"100%"}),t("#bill_type").select2({placeholder:"Select Type",allowClear:!0,width:"100%",minimumResultsForSearch:1/0}),_("#bill_date",{dateFormat:"Y-m-d",defaultDate:window._billDate||new Date,allowInput:!0}),t(".bill-item").each(function(){b(t(this))}),t("#addItem").on("click",function(){const e=t(`
            <div class="bill-item border border-gray-200 rounded-lg p-4 mb-3">
                <div class="grid grid-cols-12 gap-3">
                    <div class="col-span-2">
                        <label class="block text-xs font-medium text-gray-500 mb-1">Item Type</label>
                        <select class="item-type-select w-full px-2 py-2 border border-gray-300 rounded-lg text-sm">
                            <option value="service">Service</option>
                            <option value="lab">Lab test</option>
                            <option value="imaging">Imaging study</option>
                        </select>
                    </div>
                    <div class="col-span-3 item-service-col">
                        <label class="block text-xs font-medium text-gray-500 mb-1">Service</label>
                        <select name="items[${a}][service_id]" class="service-select w-full px-2 py-2 border border-gray-300 rounded-lg text-sm">
                            ${u}
                        </select>
                        <input type="hidden" name="items[${a}][lab_test_id]" class="lab-test-id-input" value="">
                        <input type="hidden" name="items[${a}][imaging_study_id]" class="imaging-study-id-input" value="">
                    </div>
                    <div class="col-span-3 item-lab-col hidden">
                        <label class="block text-xs font-medium text-gray-500 mb-1">Lab test</label>
                        <select class="lab-test-select w-full px-2 py-2 border border-gray-300 rounded-lg text-sm">
                            ${m}
                        </select>
                    </div>
                    <div class="col-span-3 item-imaging-col hidden">
                        <label class="block text-xs font-medium text-gray-500 mb-1">Imaging study</label>
                        <select class="imaging-study-select w-full px-2 py-2 border border-gray-300 rounded-lg text-sm">
                            ${p}
                        </select>
                    </div>
                    <div class="col-span-2">
                        <label class="block text-xs font-medium text-gray-500 mb-1">Description</label>
                        <input type="text" name="items[${a}][description]" placeholder="Description" class="description-input w-full px-2 py-2 border border-gray-300 rounded-lg text-sm" required>
                    </div>
                    <div class="col-span-1">
                        <label class="block text-xs font-medium text-gray-500 mb-1">Qty</label>
                        <input type="number" name="items[${a}][quantity]" value="1" min="1" class="quantity w-full px-2 py-2 border border-gray-300 rounded-lg text-sm text-center" required>
                    </div>
                    <div class="col-span-2">
                        <label class="block text-xs font-medium text-gray-500 mb-1">Price</label>
                        <input type="number" name="items[${a}][unit_price]" step="0.01" class="unit-price w-full px-2 py-2 border border-gray-300 rounded-lg text-sm" required>
                    </div>
                    <div class="col-span-2 flex items-end gap-2">
                        <div class="flex-1">
                            <label class="block text-xs font-medium text-gray-500 mb-1">Total</label>
                            <span class="total-display block py-2 text-sm font-medium text-gray-700">0.00</span>
                        </div>
                        <button type="button" class="remove-item mb-1 p-2 text-red-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="Remove">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            </div>
        `);t("#billItems").append(e),e.find(".service-select, .lab-test-select, .imaging-study-select").val(""),b(e),a++}),t(document).on("click",".remove-item",function(){t(".bill-item").length>1&&(t(this).closest(".bill-item").remove(),l())}),t(document).on("change",".item-type-select",function(){const e=t(this).closest(".bill-item"),i=t(this).val();e.find(".item-service-col, .item-lab-col, .item-imaging-col").addClass("hidden"),i==="lab"?e.find(".item-lab-col").removeClass("hidden"):i==="imaging"?e.find(".item-imaging-col").removeClass("hidden"):e.find(".item-service-col").removeClass("hidden"),e.find(".service-select, .lab-test-select, .imaging-study-select").val("").trigger("change"),e.find('select[name*="[service_id]"]').val(""),e.find(".lab-test-id-input, .imaging-study-id-input").val(""),e.find(".unit-price").val(""),e.find(".description-input").val(""),e.find(".total-display").text("0.00"),l()}),t(document).on("change",".service-select",function(){const e=t(this).closest(".bill-item"),i=t(this).find(":selected");i.data("price")&&(e.find(".unit-price").val(i.data("price")),e.find(".description-input").val(i.data("name")||i.text().split(" - ")[0].trim())),e.find(".lab-test-id-input, .imaging-study-id-input").val(""),l()}),t(document).on("change",".lab-test-select",function(){const e=t(this).closest(".bill-item"),i=t(this).find(":selected");i.val()&&(e.find(".unit-price").val(i.data("price")),e.find(".description-input").val(i.data("name")||i.text().split(" - ")[0].trim()),e.find(".lab-test-id-input").val(i.val()),e.find(".imaging-study-id-input").val(""),e.find(".service-select").val("")),l()}),t(document).on("change",".imaging-study-select",function(){const e=t(this).closest(".bill-item"),i=t(this).find(":selected");i.val()&&(e.find(".unit-price").val(i.data("price")),e.find(".description-input").val(i.data("name")||i.text().split(" - ")[0].trim()),e.find(".imaging-study-id-input").val(i.val()),e.find(".lab-test-id-input").val(""),e.find(".service-select").val("")),l()}),t(document).on("input change",".quantity, .unit-price",function(){l()}),t(document).on("change","#discount_type_select",function(){const e=t(this).val()==="percentage";t("#discount_type_fixed").prop("checked",!e),t("#discount_type_percentage").prop("checked",e),t("#discount_input_hint").text(e?"Enter percentage (0–100)":"Enter fixed amount"),t("#discount_computed_wrap").toggleClass("hidden",!e),t("#discount_input_value").val("0").attr("max",e?100:""),e?d():(t("#discount_amount").val("0"),t("#discount_percentage").val("0")),l()}),t(document).on("input","#discount_input_value",function(){t("#discount_type_select").val()==="percentage"?(t("#discount_percentage").val(t(this).val()),d()):t("#discount_amount").val(t(this).val()),l()}),t("#bill_type").on("change",function(){g()}),l()});function b(n){if(typeof t.fn.select2=="function")try{n.find(".service-select").select2({placeholder:"Search service...",allowClear:!0,width:"100%"}),n.find(".lab-test-select").select2({placeholder:"Search lab test...",allowClear:!0,width:"100%"}),n.find(".imaging-study-select").select2({placeholder:"Search imaging study...",allowClear:!0,width:"100%"})}catch(e){console.error("Select2 init error:",e)}}function r(){let n=0;return t(".bill-item").each(function(){const e=parseFloat(t(this).find(".quantity").val())||0,i=parseFloat(t(this).find(".unit-price").val())||0;n+=e*i}),n}function d(){const n=parseFloat(t("#discount_percentage").val())||0,e=r(),i=n/100*e;t("#discount_amount").val(i.toFixed(2));const s=window._currencySymbol||"";t("#discount_computed_amount").text(s+i.toFixed(2))}var f=null;function g(){clearTimeout(f),f=setTimeout(function(){var n=r(),e=t("#bill_type").val();if(n<=0||!e){t("#tax_amount").val("0"),t("#tax-breakdown").html(""),l();return}t.ajax({url:"/taxes/calculate",method:"POST",data:{_token:t('input[name="_token"]').val(),bill_type:e,subtotal:n},success:function(i){t("#tax_amount").val(i.total_tax);var s="";i.breakdown.forEach(function(o){s+='<p class="text-xs text-gray-500">'+o.name+" ("+o.percentage+"%) = "+o.amount.toFixed(2)+"</p>"}),t("#tax-breakdown").html(s),l()},error:function(){t("#tax_amount").val("0"),t("#tax-breakdown").html(""),l()}})},300)}function l(){let n=r();t(".bill-item").each(function(){const x=parseFloat(t(this).find(".quantity").val())||0,h=parseFloat(t(this).find(".unit-price").val())||0;t(this).find(".total-display").text((x*h).toFixed(2))}),(t("#discount_type_select").val()||t('input[name="discount_type"]:checked').val())==="percentage"&&d();const i=parseFloat(t("#tax_amount").val())||0,s=parseFloat(t("#discount_amount").val())||0,o=n+i-s,y=v||window.appConfig?.currency||"";t("#totalAmount").text(y+o.toFixed(2)),k(o),g()}function k(n){if(c<=0||c<=n){t("#overpayment-edit-warning").addClass("hidden");return}const e=(c-n).toFixed(2),i=v||window.appConfig?.currency||"";t("#overpayment-edit-warning").length||t("#totalAmount").after('<div id="overpayment-edit-warning" class="mt-2 text-sm text-blue-700 bg-blue-50 border border-blue-200 rounded-lg p-3"></div>'),t("#overpayment-edit-warning").html('<i class="fas fa-info-circle mr-1"></i>Patient credit of <strong>'+i+e+"</strong> will be recorded (paid exceeds new total).").removeClass("hidden")}
