const l={success:`<svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2"
                   viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round"
                   d="M5 13l4 4L19 7"/></svg>`,error:`<svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2"
                   viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round"
                   d="M6 18L18 6M6 6l12 12"/></svg>`,warning:`<svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2"
                   viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round"
                   d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>`,info:`<svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2"
                   viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round"
                   d="M13 16h-1v-4h-1m1-4h.01M12 2a10 10 0 100 20A10 10 0 0012 2z"/></svg>`},a={success:"bg-green-600",error:"bg-red-600",warning:"bg-yellow-500",info:"bg-blue-600"},u=`<svg class="w-5 h-5 flex-shrink-0 animate-spin" fill="none" viewBox="0 0 24 24">
    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
</svg>`;function L(){let e=document.getElementById("toast-container");return e||(e=document.createElement("div"),e.id="toast-container",e.className="fixed top-4 right-4 z-[9999] flex flex-col gap-2 w-80 max-w-[calc(100vw-2rem)]",document.body.appendChild(e)),e}function n(e,o="info",t=4e3,f=!1){const h=L(),c=a[o]??a.info,m=f?u:l[o]??l.info,s=document.createElement("div");s.className=[c,"text-white rounded-lg shadow-lg px-4 py-3 flex items-start gap-3","transform transition-all duration-300 translate-x-full opacity-0"].join(" "),s.innerHTML=`
        <span class="icon-slot mt-0.5">${m}</span>
        <span class="message-slot flex-1 text-sm leading-snug">${e}</span>
        <button class="flex-shrink-0 opacity-70 hover:opacity-100 ml-1 mt-0.5" aria-label="Dismiss">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    `,h.appendChild(s),requestAnimationFrame(()=>{requestAnimationFrame(()=>{s.classList.remove("translate-x-full","opacity-0")})});let r=null;const i=()=>{clearTimeout(r),s.classList.add("opacity-0","translate-x-full"),s.addEventListener("transitionend",()=>s.remove(),{once:!0})},g=(w,d,v=!1)=>{clearTimeout(r);const p=a[d??o]??c,k=v?u:l[d??o]??l.info;Object.values(a).forEach(x=>s.classList.remove(x)),s.classList.add(p),s.querySelector(".icon-slot").innerHTML=k,s.querySelector(".message-slot").innerHTML=w,t>0&&(r=setTimeout(i,t))};return s.querySelector("button").addEventListener("click",i),t>0&&(r=setTimeout(i,t)),{dismiss:i,update:g}}const C={success:(e,o=5e3)=>n(e,"success",o),error:(e,o=7e3)=>n(e,"error",o),warning:(e,o=6e3)=>n(e,"warning",o),info:(e,o=4e3)=>n(e,"info",o),loading:(e,o=0)=>n(e,"info",o,!0)};window.Toast=C;export{C as T};
