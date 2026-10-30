/* D'SERVICE RH — JavaScript da interface (sem dependências). */
(function () {
    'use strict';

    var meta = document.querySelector('meta[name="csrf-token"]');
    var csrf = meta ? meta.content : '';
    var brl = new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' });

    function api(url, method, body) {
        return fetch(url, {
            method: method,
            headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'Content-Type': 'application/json' },
            body: body ? JSON.stringify(body) : undefined
        }).then(function (r) {
            if (!r.ok) throw new Error('HTTP ' + r.status);
            return r.json();
        });
    }

    /* menu lateral no celular */
    document.addEventListener('click', function (e) {
        var t = e.target.closest('[data-toggle]');
        if (t) document.querySelector(t.dataset.toggle).classList.toggle('open');
    });

    /* confirmação antes de excluir */
    document.addEventListener('submit', function (e) {
        var msg = e.target.dataset ? e.target.dataset.confirm : null;
        if (msg && !window.confirm(msg)) e.preventDefault();
    });

    /* máscaras */
    var masks = {
        cpf: function (v) {
            v = v.replace(/\D/g, '').slice(0, 11);
            return v.replace(/(\d{3})(\d)/, '$1.$2').replace(/(\d{3})(\d)/, '$1.$2').replace(/(\d{3})(\d{1,2})$/, '$1-$2');
        }
    };
    document.querySelectorAll('input[data-mask]').forEach(function (inp) {
        var fn = masks[inp.dataset.mask];
        if (!fn) return;
        inp.value = fn(inp.value);
        inp.addEventListener('input', function () { inp.value = fn(inp.value); });
    });

    /* CID-10: ao sair do campo, busca a descrição */
    document.querySelectorAll('input[data-cid-lookup]').forEach(function (inp) {
        inp.addEventListener('blur', function () {
            var code = inp.value.trim().toUpperCase();
            var target = document.querySelector(inp.dataset.cidLookup);
            if (!code || !target) return;
            inp.value = code;
            fetch('/api/cids/' + encodeURIComponent(code), { headers: { 'Accept': 'application/json' } })
                .then(function (r) { return r.json(); })
                .then(function (d) { if (d.found && !target.value) target.value = d.description; })
                .catch(function () { /* silencioso: preenchimento é opcional */ });
        });
    });

    /* feedback visual de salvamento inline */
    function flash(el, ok) {
        el.classList.remove('saved', 'failed');
        el.classList.add(ok ? 'saved' : 'failed');
        setTimeout(function () { el.classList.remove('saved'); }, 1200);
    }

    /* Benefícios VT/VR: edição inline com recálculo */
    document.querySelectorAll('tr[data-url] .js-inline').forEach(function (inp) {
        inp.addEventListener('change', function () {
            var tr = inp.closest('tr');
            var body = {};
            body[inp.name] = inp.value === '' && inp.type === 'number' ? 0 : inp.value;
            api(tr.dataset.url, 'PATCH', body).then(function (d) {
                tr.querySelector('[data-out="vt"]').textContent = brl.format(d.vt);
                tr.querySelector('[data-out="vr"]').textContent = brl.format(d.vr);
                tr.querySelector('[data-out="total"]').innerHTML = '<b>' + brl.format(d.total) + '</b>';
                document.getElementById('tot-vt').textContent = brl.format(d.totals.vt);
                document.getElementById('tot-vr').textContent = brl.format(d.totals.vr);
                document.getElementById('tot-total').textContent = brl.format(d.totals.total);
                flash(inp, true);
            }).catch(function () { flash(inp, false); });
        });
    });

    /* Padrão mensal de escala: edição inline */
    document.querySelectorAll('input.js-pattern').forEach(function (inp) {
        inp.addEventListener('change', function () {
            api(inp.dataset.url, 'PATCH', { pattern: inp.value }).then(function (d) {
                inp.value = d.pattern; flash(inp, true);
            }).catch(function () { flash(inp, false); });
        });
    });

    /* Escalas: edição de célula (nome + liderança) */
    document.querySelectorAll('.cell[data-url]').forEach(function (cell) {
        var person = cell.querySelector('input[name="person"]');
        var leader = cell.querySelector('input[name="leader_rotation"]');
        function save() {
            api(cell.dataset.url, 'PATCH', { person: person.value, leader_rotation: leader.checked })
                .then(function (d) {
                    person.value = d.person || '';
                    person.classList.toggle('off', d.person === 'FOLGA');
                    flash(person, true);
                }).catch(function () { flash(person, false); });
        }
        person.addEventListener('change', save);
        leader.addEventListener('change', save);
    });
})();
