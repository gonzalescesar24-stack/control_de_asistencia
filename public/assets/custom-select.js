document.addEventListener('DOMContentLoaded', () => {
    const initCustomSelects = () => {
        document.querySelectorAll('select.form-control:not(.custom-select-initialized)').forEach(select => {
            select.classList.add('custom-select-initialized');
            
            // Create wrapper
            const wrapper = document.createElement('div');
            wrapper.className = 'relative custom-select-wrapper ' + Array.from(select.classList).filter(c => c !== 'form-control' && c !== 'custom-select-initialized' && c !== 'select-compact').join(' ');
            if (select.classList.contains('select-compact')) {
                wrapper.classList.add('custom-select-compact');
            }

            // Hide original select
            select.style.display = 'none';
            select.parentNode.insertBefore(wrapper, select);
            wrapper.appendChild(select);

            // Create trigger
            const trigger = document.createElement('div');
            trigger.className = 'flex items-center justify-between w-full border border-slate-200 bg-white px-3 py-2 rounded-lg cursor-pointer text-sm text-slate-700 transition hover:border-[#1a3a6b] focus:border-[#1a3a6b] focus:ring-2 focus:ring-[#1a3a6b]/20';
            
            if (select.classList.contains('select-compact')) {
                trigger.classList.replace('py-2', 'py-1');
                trigger.classList.replace('text-sm', 'text-xs');
                trigger.style.minHeight = '2rem';
            } else {
                trigger.style.minHeight = '2.25rem';
            }

            const triggerText = document.createElement('span');
            triggerText.className = 'block truncate';
            trigger.appendChild(triggerText);

            const chevron = document.createElement('div');
            chevron.className = 'ml-2 shrink-0';
            chevron.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#64748b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-chevron-down transition-transform duration-200"><path d="m6 9 6 6 6-6"/></svg>`;
            trigger.appendChild(chevron);

            wrapper.appendChild(trigger);

            // Create dropdown menu
            const dropdown = document.createElement('div');
            dropdown.className = 'absolute z-50 mt-1 min-w-full whitespace-nowrap rounded-lg border border-slate-200 bg-white py-1 shadow-lg opacity-0 pointer-events-none transition-opacity duration-200';
            dropdown.style.maxHeight = '250px';
            dropdown.style.overflowY = 'auto';
            wrapper.appendChild(dropdown);

            // Function to update trigger text
            const updateTriggerText = () => {
                const selectedOption = select.options[select.selectedIndex];
                triggerText.textContent = selectedOption ? selectedOption.text : 'Seleccione...';
            };

            updateTriggerText();

            // Listen for native select change
            select.addEventListener('change', updateTriggerText);

            // Render options when opening
            let isOpen = false;
            
            const openDropdown = () => {
                // Close all others first
                document.querySelectorAll('.custom-select-wrapper').forEach(w => {
                    if (w !== wrapper) w.dispatchEvent(new CustomEvent('closeDropdown'));
                });

                dropdown.innerHTML = ''; // Clear previous
                let visibleCount = 0;
                
                Array.from(select.options).forEach((option, index) => {
                    if (option.style.display === 'none') return;
                    
                    const item = document.createElement('div');
                    item.className = 'cursor-pointer px-3 py-2 text-sm text-slate-700 transition hover:bg-blue-50 hover:text-[#1a3a6b]';
                    if (select.classList.contains('select-compact')) {
                        item.classList.replace('py-2', 'py-1');
                        item.classList.replace('text-sm', 'text-xs');
                    }
                    
                    if (option.selected) {
                        item.className = 'cursor-pointer px-3 py-2 text-sm bg-[#1a3a6b] text-white';
                        if (select.classList.contains('select-compact')) {
                            item.classList.replace('py-2', 'py-1');
                            item.classList.replace('text-sm', 'text-xs');
                        }
                    }
                    
                    item.textContent = option.text;
                    
                    item.addEventListener('click', (e) => {
                        e.stopPropagation();
                        select.selectedIndex = index;
                        select.dispatchEvent(new Event('change', { bubbles: true }));
                        closeDropdown();
                    });
                    
                    dropdown.appendChild(item);
                    visibleCount++;
                });

                if (visibleCount === 0) {
                    const empty = document.createElement('div');
                    empty.className = 'px-3 py-2 text-sm text-slate-400 italic text-center';
                    empty.textContent = 'Sin opciones';
                    dropdown.appendChild(empty);
                }

                dropdown.classList.remove('opacity-0', 'pointer-events-none');
                trigger.classList.add('border-[#1a3a6b]', 'ring-2', 'ring-[#1a3a6b]/20');
                chevron.querySelector('svg').classList.add('rotate-180');
                
                // Adjust position if it goes off screen
                const rect = dropdown.getBoundingClientRect();
                if (rect.bottom > window.innerHeight) {
                    dropdown.style.bottom = '100%';
                    dropdown.style.marginTop = '0';
                    dropdown.style.marginBottom = '0.25rem';
                } else {
                    dropdown.style.bottom = 'auto';
                    dropdown.style.marginTop = '0.25rem';
                    dropdown.style.marginBottom = '0';
                }

                isOpen = true;
            };

            const closeDropdown = () => {
                dropdown.classList.add('opacity-0', 'pointer-events-none');
                trigger.classList.remove('border-[#1a3a6b]', 'ring-2', 'ring-[#1a3a6b]/20');
                chevron.querySelector('svg').classList.remove('rotate-180');
                setTimeout(() => { if (!isOpen) dropdown.style.bottom = 'auto'; }, 200);
                isOpen = false;
            };

            wrapper.addEventListener('closeDropdown', closeDropdown);

            trigger.addEventListener('click', (e) => {
                e.stopPropagation();
                if (isOpen) {
                    closeDropdown();
                } else {
                    openDropdown();
                }
            });
        });
    };

    initCustomSelects();

    document.addEventListener('click', (e) => {
        if (!e.target.closest('.custom-select-wrapper')) {
            document.querySelectorAll('.custom-select-wrapper').forEach(w => w.dispatchEvent(new CustomEvent('closeDropdown')));
        }
    });

    // Make init function available globally for dynamically added selects
    window.initCustomSelects = initCustomSelects;
});
