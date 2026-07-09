@if(($status ?? '') === 'Approved')
    <span class="inline-block px-1.5 py-0.5 text-[9px] font-bold rounded bg-brandGreen/10 text-brandGreen border border-brandGreen/20 uppercase tracking-wide">OK</span>
@else
    <span class="inline-block px-1.5 py-0.5 text-[9px] font-bold rounded bg-brandGold/10 text-brandGold border border-brandGold/20 uppercase tracking-wide">Pndg</span>
@endif
