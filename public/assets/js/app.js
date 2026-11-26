(() => {
    const toggle = document.querySelector('.nav-toggle');
    const nav = document.getElementById('site-nav');

    if (toggle && nav) {
        toggle.addEventListener('click', () => {
            const open = toggle.getAttribute('aria-expanded') === 'true';
            toggle.setAttribute('aria-expanded', String(!open));
            nav.classList.toggle('is-open', !open);
        });
    }

    const form = document.getElementById('booking-form');
    const slotsBox = document.getElementById('slots');
    if (!form || !slotsBox) {
        return;
    }

    const dateInput = form.querySelector('input[name="date"]');
    const tokenInput = document.getElementById('slot-token');
    let requestId = 0;

    const value = (name) => {
        const checked = form.querySelector(`input[name="${name}"]:checked`);
        if (checked) {
            return checked.value;
        }
        const field = form.querySelector(`[name="${name}"]:not([type="radio"])`);
        return field ? field.value : '';
    };

    const hint = (text) => {
        slotsBox.replaceChildren();
        const p = document.createElement('p');
        p.className = 'muted slots-hint';
        p.textContent = text;
        slotsBox.append(p);
    };

    const filterBarbers = () => {
        const service = value('service');
        form.querySelectorAll('.choice[data-services]').forEach((choice) => {
            const input = choice.querySelector('input');
            const offers = choice.dataset.services.split(',').includes(service);
            input.disabled = service !== '' && !offers;
            choice.classList.toggle('is-disabled', input.disabled);
            if (input.disabled && input.checked) {
                form.querySelector('input[name="barber"][value="0"]').checked = true;
            }
        });
    };

    const loadSlots = async () => {
        const service = value('service');
        const date = dateInput.value;
        if (!service || !date) {
            hint('Kies een behandeling en datum om de vrije tijden te zien.');
            return;
        }

        const previous = value('time');
        const params = new URLSearchParams({ service, barber: value('barber') || '0', date });
        if (tokenInput) {
            params.set('token', tokenInput.value);
        }
        if (slotsBox.dataset.ignore && slotsBox.dataset.ignore !== '0') {
            params.set('ignore', slotsBox.dataset.ignore);
        }

        const current = ++requestId;
        slotsBox.setAttribute('aria-busy', 'true');
        hint('Vrije tijden laden…');

        try {
            const response = await fetch(`${slotsBox.dataset.slotsUrl}?${params}`, { headers: { Accept: 'application/json' } });
            const data = await response.json();
            if (current !== requestId) {
                return;
            }
            if (!data.slots || data.slots.length === 0) {
                hint('Op deze dag zijn geen tijden vrij. Probeer een andere datum.');
                return;
            }
            let currentPart = '';
            slotsBox.replaceChildren(...data.slots.flatMap((time) => {
                const nodes = [];
                const part = time < '12:00' ? 'Ochtend' : (time < '17:00' ? 'Middag' : 'Avond');
                if (part !== currentPart) {
                    currentPart = part;
                    const heading = document.createElement('p');
                    heading.className = 'slots-group';
                    heading.textContent = part;
                    nodes.push(heading);
                }
                const label = document.createElement('label');
                label.className = 'slot';
                const input = document.createElement('input');
                input.type = 'radio';
                input.name = 'time';
                input.value = time;
                input.required = true;
                input.checked = time === previous;
                const span = document.createElement('span');
                span.textContent = time;
                label.append(input, span);
                nodes.push(label);
                return nodes;
            }));
        } catch (error) {
            hint('De tijden konden niet worden geladen. Ververs de pagina en probeer het opnieuw.');
        } finally {
            slotsBox.removeAttribute('aria-busy');
        }
    };

    form.addEventListener('change', (event) => {
        const name = event.target.name;
        if (name === 'service') {
            filterBarbers();
        }
        if (['service', 'barber', 'date'].includes(name)) {
            loadSlots();
        }
    });

    form.addEventListener('submit', (event) => {
        if (!form.checkValidity()) {
            event.preventDefault();
            const invalid = form.querySelector(':invalid');
            if (invalid) {
                invalid.focus();
                invalid.reportValidity();
            }
        }
    });

    filterBarbers();
})();
