(() => {
  'use strict';

  const STORAGE_KEY = 'velocargo_data_v1';
  const $ = (selector) => document.querySelector(selector);
  const $$ = (selector) => [...document.querySelectorAll(selector)];

  const seed = () => ({
    nextCode: 1003,
    vehicles: [
      { id: 'v1', plate: 'ABC1D23', model: 'Honda CG 160', year: 2023, capacity: 25, type: 'Motocicleta', details: '160cc | Baú: Sim', costKm: 1.20, costKg: 0.15, available: true, refrigerated: false, maxDistance: 100 },
      { id: 'v2', plate: 'EFG4H56', model: 'Fiat Fiorino', year: 2022, capacity: 650, type: 'Utilitário', details: 'Baú: 3.2 m³ | Refrigerado: Não', costKm: 2.50, costKg: 0.25, available: false, refrigerated: false },
      { id: 'v3', plate: 'IJK7L89', model: 'Mercedes Sprinter Frigorífica', year: 2024, capacity: 2500, type: 'Caminhão Refrigerado', details: 'Temp. mín: -15°C | Rastreador: Ativo', costKm: 4.80, costKg: 0.40, available: false, refrigerated: true }
    ],
    clients: [
      { id: 'c1', name: 'Ana Souza', document: '123.456.789-00', address: 'Rua das Flores, 100 - São Paulo/SP', corporate: false },
      { id: 'c2', name: 'Mercado Central Ltda.', document: '12.345.678/0001-90', address: 'Av. Brasil, 500 - Campinas/SP', corporate: true }
    ],
    orders: [
      { code: 'VC-1001', clientId: 'c2', recipient: 'Carlos Lima - Campinas/SP', distance: 80, status: 'Em Trânsito', vehicleId: 'v2', volumes: [{ description: 'Caixas de vidro', weight: 120, type: 'Frágil' }], history: [], createdAt: new Date().toISOString() },
      { code: 'VC-1002', clientId: 'c1', recipient: 'Farmácia Vida - Santos/SP', distance: 140, status: 'Aguardando Coleta', vehicleId: 'v3', volumes: [{ description: 'Medicamentos refrigerados', weight: 300, type: 'Refrigerada' }], history: [], createdAt: new Date().toISOString() }
    ]
  });

  let data;
  try {
    const saved = localStorage.getItem(STORAGE_KEY);
    data = saved ? JSON.parse(saved) : seed();
  } catch (error) {
    data = seed();
  }

  function save() {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(data));
  }
  function money(value) {
    return Number(value || 0).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
  }
  function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, char => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[char]));
  }
  function clientOf(order) {
    return data.clients.find(client => client.id === order.clientId) || { name: 'Cliente não encontrado', document: '—', corporate: false };
  }
  function vehicleOf(order) {
    return data.vehicles.find(vehicle => vehicle.id === order.vehicleId) || null;
  }
  function addEvent(order, description) {
    order.history = order.history || [];
    order.history.unshift({ description, date: new Date().toISOString() });
  }
  function totals(order) {
    const vehicle = vehicleOf(order);
    if (!vehicle) return { base: 0, additional: 0, discount: 0, freight: 0, insurance: 0, total: 0 };
    const weight = order.volumes.reduce((sum, volume) => sum + Number(volume.weight), 0);
    const base = Number(order.distance) * vehicle.costKm + weight * vehicle.costKg;
    let rate = 0;
    order.volumes.forEach(volume => {
      if (volume.type === 'Frágil') rate = Math.max(rate, .12);
      if (volume.type === 'Refrigerada') rate = Math.max(rate, .18);
    });
    const additional = base * rate;
    const subtotal = base + additional;
    const discount = clientOf(order).corporate && Number(order.distance) > 50 ? subtotal * .08 : 0;
    const freight = subtotal - discount;
    const needsInsurance = order.volumes.some(v => ['Frágil', 'Refrigerada'].includes(v.type)) || Number(order.distance) > 100;
    const insurance = needsInsurance ? Math.max(10, freight * .03) : 0;
    if (needsInsurance && !order.policy) order.policy = `AP-${order.code.replace('VC-', '')}-${new Date().getFullYear()}`;
    if (!needsInsurance) order.policy = 'Não aplicável';
    return { base, additional, discount, freight, insurance, total: freight + insurance };
  }
  function weightOf(order) {
    return order.volumes.reduce((sum, volume) => sum + Number(volume.weight), 0);
  }
  function statusClass(status) {
    if (status === 'Em Trânsito') return 'transit';
    if (status === 'Entregue') return 'delivered';
    if (status === 'Ocorrência') return 'issue';
    return 'waiting';
  }
  function statusBadge(status) {
    return `<span class="status ${statusClass(status)}">${escapeHtml(status)}</span>`;
  }
  function toast(message) {
    const element = $('#toast');
    element.textContent = message;
    element.classList.add('show');
    clearTimeout(toast.timer);
    toast.timer = setTimeout(() => element.classList.remove('show'), 2600);
  }
  function dateText(date) {
    return new Date(date).toLocaleString('pt-BR', { dateStyle: 'short', timeStyle: 'short' });
  }
  function renderDashboard() {
    const available = data.vehicles.filter(v => v.available).length;
    const transit = data.orders.filter(o => o.status === 'Em Trânsito').length;
    const revenue = data.orders.reduce((sum, order) => sum + totals(order).freight, 0);
    $('#statOrders').textContent = data.orders.length;
    $('#statAvailable').textContent = available;
    $('#statFleetCaption').textContent = `De ${data.vehicles.length} veículos cadastrados`;
    $('#statTransit').textContent = transit;
    $('#statRevenue').textContent = money(revenue);
    const percent = data.vehicles.length ? Math.round(available / data.vehicles.length * 100) : 0;
    $('#fleetRing').style.background = `conic-gradient(#1da879 ${percent * 3.6}deg,#e9edf3 0deg)`;
    $('#fleetPercent').textContent = `${percent}%`;
    $('#fleetAvailableText').textContent = `${available} de ${data.vehicles.length}`;
    const recent = [...data.orders].slice(-5).reverse();
    $('#recentOrders').innerHTML = recent.length ? recent.map(order => {
      const total = totals(order);
      return `<tr><td>${escapeHtml(order.code)}</td><td>${escapeHtml(clientOf(order).name)}</td><td>${escapeHtml(order.recipient)}</td><td>${statusBadge(order.status)}</td><td>${money(total.total)}</td></tr>`;
    }).join('') : '<tr><td class="empty" colspan="5">Nenhum pedido cadastrado.</td></tr>';
  }
  function renderFleet() {
    $('#fleetCards').innerHTML = data.vehicles.map(vehicle => `
      <article class="vehicle-card">
        <div class="vehicle-card-top"><span class="vehicle-symbol">${vehicle.type === 'Motocicleta' ? '🏍' : vehicle.type === 'Utilitário' ? '🚐' : '🚚'}</span>${statusBadge(vehicle.available ? 'Disponível' : 'Em rota')}</div>
        <h3>${escapeHtml(vehicle.model)}</h3><p>${escapeHtml(vehicle.type)} · ${escapeHtml(vehicle.plate)}</p>
        <div class="vehicle-meta"><div><small>CAPACIDADE</small><strong>${vehicle.capacity.toLocaleString('pt-BR')} kg</strong></div><div><small>ANO</small><strong>${vehicle.year}</strong></div><div><small>CUSTO POR KM</small><strong>${money(vehicle.costKm)}</strong></div><div><small>DETALHES</small><strong>${escapeHtml(vehicle.details)}</strong></div></div>
      </article>`).join('');
  }
  function renderOrders() {
    const query = ($('#orderSearch')?.value || '').toLowerCase();
    const status = $('#statusFilter')?.value || '';
    const orders = [...data.orders].reverse().filter(order => {
      const haystack = `${order.code} ${clientOf(order).name} ${order.recipient}`.toLowerCase();
      return haystack.includes(query) && (!status || order.status === status);
    });
    $('#ordersTable').innerHTML = orders.length ? orders.map(order => {
      const total = totals(order);
      return `<tr><td>${escapeHtml(order.code)}</td><td>${escapeHtml(clientOf(order).name)}</td><td>${escapeHtml(order.recipient)}</td><td>${Number(order.distance).toLocaleString('pt-BR')} km</td><td>${statusBadge(order.status)}</td><td>${money(total.total)}</td><td><button class="text-button" data-detail="${escapeHtml(order.code)}">Detalhes</button></td></tr>`;
    }).join('') : '<tr><td colspan="7" class="empty">Nenhum pedido encontrado.</td></tr>';
  }
  function renderReports() {
    const values = data.orders.map(order => ({ order, value: totals(order) }));
    const freight = values.reduce((sum, item) => sum + item.value.freight, 0);
    const insurance = values.reduce((sum, item) => sum + item.value.insurance, 0);
    const weight = data.orders.reduce((sum, order) => sum + weightOf(order), 0);
    const largest = values.reduce((best, item) => !best || item.value.total > best.value.total ? item : best, null);
    $('#reportFreight').textContent = money(freight);
    $('#reportInsurance').textContent = money(insurance);
    $('#reportWeight').textContent = `${weight.toLocaleString('pt-BR')} kg`;
    $('#reportLargest').textContent = largest ? `${largest.order.code} · ${money(largest.value.total)}` : '—';
    $('#reportTable').innerHTML = values.length ? values.map(({ order, value }) => `<tr><td>${escapeHtml(order.code)}</td><td>${money(value.freight)}</td><td>${money(value.insurance)}</td><td><strong>${money(value.total)}</strong></td></tr>`).join('') : '<tr><td colspan="4" class="empty">Sem dados para exibir.</td></tr>';
  }
  function renderAll() {
    data.orders.forEach(totals);
    save();
    renderDashboard();
    renderFleet();
    renderOrders();
    renderReports();
  }
  function showPage(page) {
    $$('.page').forEach(element => element.classList.toggle('active', element.id === `page-${page}`));
    $$('.nav-link').forEach(element => element.classList.toggle('active', element.dataset.page === page));
    const titles = { dashboard: 'Visão geral', fleet: 'Frota', orders: 'Pedidos', tracking: 'Rastreamento', reports: 'Relatórios' };
    $('#pageTitle').textContent = titles[page] || 'Visão geral';
    $('#sidebar').classList.remove('open');
    if (page === 'orders') renderOrders();
  }
  function openDetails(code) {
    const order = data.orders.find(item => item.code === code);
    if (!order) return;
    const value = totals(order);
    const vehicle = vehicleOf(order);
    $('#detailTitle').textContent = order.code;
    $('#detailContent').innerHTML = `
      <div class="detail-grid detail-block">
        <div><small>CLIENTE</small><strong>${escapeHtml(clientOf(order).name)}</strong></div>
        <div><small>DESTINATÁRIO</small><strong>${escapeHtml(order.recipient)}</strong></div>
        <div><small>DISTÂNCIA</small><strong>${order.distance} km</strong></div>
        <div><small>STATUS</small><strong>${statusBadge(order.status)}</strong></div>
        <div><small>VEÍCULO</small><strong>${vehicle ? `${escapeHtml(vehicle.type)} · ${escapeHtml(vehicle.plate)}` : 'Não alocado'}</strong></div>
        <div><small>PESO TOTAL</small><strong>${weightOf(order)} kg</strong></div>
      </div>
      <div class="detail-block"><strong>Volumes</strong>${order.volumes.map(v => `<p class="muted">${escapeHtml(v.description)} · ${v.weight} kg · ${escapeHtml(v.type)}</p>`).join('')}</div>
      <div class="detail-block detail-grid">
        <div><small>VALOR BASE</small><strong>${money(value.base)}</strong></div><div><small>ADICIONAIS</small><strong>${money(value.additional)}</strong></div>
        <div><small>DESCONTO</small><strong>− ${money(value.discount)}</strong></div><div><small>FRETE</small><strong>${money(value.freight)}</strong></div>
        <div><small>SEGURO · ${escapeHtml(order.policy || 'Não aplicável')}</small><strong>${money(value.insurance)}</strong></div><div><small>TOTAL</small><strong>${money(value.total)}</strong></div>
      </div>
      <div class="detail-block"><strong>Histórico de rastreamento</strong><div class="timeline">${(order.history || []).map(event => `<div class="timeline-item"><strong>${escapeHtml(event.description)}</strong><small>${dateText(event.date)}</small></div>`).join('')}</div></div>
      <div class="detail-actions">
        <button class="button button-light" data-update="${escapeHtml(code)}" data-status="Em Trânsito">Marcar em trânsito</button>
        <button class="button button-light" data-update="${escapeHtml(code)}" data-status="Entregue">Marcar entregue</button>
        <button class="button button-light" data-occurrence="${escapeHtml(code)}">Registrar ocorrência</button>
      </div>`;
    $('#detailsDialog').showModal();
  }
  function updateStatus(code, status) {
    const order = data.orders.find(item => item.code === code);
    if (!order) return;
    order.status = status;
    addEvent(order, `Status alterado para: ${status}`);
    const vehicle = vehicleOf(order);
    if (vehicle) vehicle.available = status === 'Entregue';
    save();
    renderAll();
    $('#detailsDialog').close();
    toast('Status do pedido atualizado.');
  }
  function trackOrder() {
    const code = $('#trackingCode').value.trim().toUpperCase();
    const order = data.orders.find(item => item.code.toUpperCase() === code);
    if (!order) {
      $('#trackingResult').innerHTML = '<article class="panel tracking-card"><p class="empty">Não encontramos um pedido com esse código. Confira e tente novamente.</p></article>';
      return;
    }
    const value = totals(order);
    const vehicle = vehicleOf(order);
    $('#trackingResult').innerHTML = `<article class="panel tracking-card"><div class="panel-heading"><div><span class="eyebrow">RASTREAMENTO</span><h3>${escapeHtml(order.code)}</h3></div>${statusBadge(order.status)}</div>
      <div class="tracking-summary"><div><small>DESTINATÁRIO</small><strong>${escapeHtml(order.recipient)}</strong></div><div><small>PREVISÃO / DISTÂNCIA</small><strong>${order.distance} km</strong></div><div><small>VEÍCULO</small><strong>${vehicle ? escapeHtml(vehicle.plate) : '—'}</strong></div></div>
      <p class="muted">Cliente: ${escapeHtml(clientOf(order).name)} · Total: ${money(value.total)}</p>
      <h3>Histórico de movimentações</h3><div class="timeline">${(order.history || []).map(event => `<div class="timeline-item"><strong>${escapeHtml(event.description)}</strong><small>${dateText(event.date)}</small></div>`).join('')}</div></article>`;
  }
  function addVolumeRow() {
    const row = document.createElement('div');
    row.className = 'volume-row';
    row.innerHTML = `<input class="volume-description" required placeholder="Descrição do volume"><input class="volume-weight" type="number" min="0.1" step="0.1" required placeholder="Peso kg"><select class="volume-type"><option>Geral</option><option>Frágil</option><option>Refrigerada</option></select><button type="button" class="remove-volume" aria-label="Remover volume">×</button>`;
    $('#volumeRows').append(row);
  }
  function compatibleVehicles(volumes, distance) {
    const weight = volumes.reduce((sum, volume) => sum + volume.weight, 0);
    return data.vehicles.filter(vehicle => {
      if (!vehicle.available || weight > vehicle.capacity) return false;
      if (vehicle.maxDistance && distance > vehicle.maxDistance) return false;
      if (volumes.some(v => v.type === 'Refrigerada') && !vehicle.refrigerated) return false;
      return true;
    });
  }
  function refreshVehicleOptions() {
    const volumes = $$('.volume-row').map(row => ({
      weight: Number(row.querySelector('.volume-weight').value) || 0,
      type: row.querySelector('.volume-type').value
    }));
    const distance = Number($('#distance').value) || 0;
    const options = compatibleVehicles(volumes, distance);
    $('#vehicleSelect').innerHTML = options.length
      ? '<option value="">Selecione um veículo</option>' + options.map(v => `<option value="${v.id}">${escapeHtml(v.type)} · ${escapeHtml(v.plate)} · ${v.capacity} kg</option>`).join('')
      : '<option value="">Nenhum veículo compatível disponível</option>';
    $('#formHint').textContent = options.length ? 'O sistema calcula o frete, os adicionais, o desconto e o seguro automaticamente.' : 'Não há veículo compatível disponível para os dados informados.';
  }
  function openOrderDialog() {
    $('#clientSelect').innerHTML = data.clients.map(client => `<option value="${client.id}">${escapeHtml(client.name)}${client.corporate ? ' (Corporativo)' : ''}</option>`).join('');
    $('#orderForm').reset();
    $('#volumeRows').innerHTML = '';
    addVolumeRow();
    refreshVehicleOptions();
    $('#orderDialog').showModal();
  }
  function submitOrder(event) {
    event.preventDefault();
    const volumes = $$('.volume-row').map(row => ({
      description: row.querySelector('.volume-description').value.trim(),
      weight: Number(row.querySelector('.volume-weight').value),
      type: row.querySelector('.volume-type').value
    }));
    if (!volumes.length || volumes.some(v => !v.description || !Number.isFinite(v.weight) || v.weight <= 0)) {
      toast('Confira os dados dos volumes.');
      return;
    }
    const distance = Number($('#distance').value);
    const vehicle = data.vehicles.find(v => v.id === $('#vehicleSelect').value);
    if (!vehicle || !compatibleVehicles(volumes, distance).some(v => v.id === vehicle.id)) {
      toast('Selecione um veículo compatível.');
      refreshVehicleOptions();
      return;
    }
    const order = {
      code: `VC-${data.nextCode++}`,
      clientId: $('#clientSelect').value,
      recipient: $('#recipient').value.trim(),
      distance,
      status: 'Em Trânsito',
      vehicleId: vehicle.id,
      volumes,
      history: [],
      createdAt: new Date().toISOString()
    };
    addEvent(order, 'Pedido criado');
    addEvent(order, `Alocado ao veículo ${vehicle.plate}`);
    addEvent(order, 'Status alterado para: Em Trânsito');
    vehicle.available = false;
    data.orders.push(order);
    totals(order);
    save();
    $('#orderDialog').close();
    renderAll();
    toast(`Pedido ${order.code} criado com sucesso.`);
    showPage('orders');
  }

  $$('.nav-link').forEach(button => button.addEventListener('click', () => showPage(button.dataset.page)));
  $$('[data-go]').forEach(button => button.addEventListener('click', () => showPage(button.dataset.go)));
  $('#newOrder').addEventListener('click', openOrderDialog);
  $('#newOrderTop').addEventListener('click', openOrderDialog);
  $('#closeDialog').addEventListener('click', () => $('#orderDialog').close());
  $('#cancelDialog').addEventListener('click', () => $('#orderDialog').close());
  $('#closeDetails').addEventListener('click', () => $('#detailsDialog').close());
  $('#detailClose').addEventListener('click', () => $('#detailsDialog').close());
  $('#orderForm').addEventListener('submit', submitOrder);
  $('#addVolume').addEventListener('click', () => { addVolumeRow(); refreshVehicleOptions(); });
  $('#volumeRows').addEventListener('input', refreshVehicleOptions);
  $('#volumeRows').addEventListener('change', refreshVehicleOptions);
  $('#volumeRows').addEventListener('click', event => {
    if (event.target.classList.contains('remove-volume')) {
      if ($$('.volume-row').length > 1) event.target.closest('.volume-row').remove();
      refreshVehicleOptions();
    }
  });
  $('#distance').addEventListener('input', refreshVehicleOptions);
  $('#orderSearch').addEventListener('input', renderOrders);
  $('#statusFilter').addEventListener('change', renderOrders);
  $('#ordersTable').addEventListener('click', event => {
    const button = event.target.closest('[data-detail]');
    if (button) openDetails(button.dataset.detail);
  });
  $('#detailContent').addEventListener('click', event => {
    const update = event.target.closest('[data-update]');
    const occurrence = event.target.closest('[data-occurrence]');
    if (update) updateStatus(update.dataset.update, update.dataset.status);
    if (occurrence) {
      const description = prompt('Descreva a ocorrência:');
      if (description && description.trim()) {
        const order = data.orders.find(item => item.code === occurrence.dataset.occurrence);
        order.status = 'Ocorrência';
        addEvent(order, `Ocorrência: ${description.trim()}`);
        save();
        renderAll();
        $('#detailsDialog').close();
        toast('Ocorrência registrada.');
      }
    }
  });
  $('#trackButton').addEventListener('click', trackOrder);
  $('#trackingCode').addEventListener('keydown', event => { if (event.key === 'Enter') trackOrder(); });
  $('#resetData').addEventListener('click', () => {
    if (confirm('Restaurar os dados iniciais? Isso apagará os pedidos criados neste navegador.')) {
      data = seed();
      data.orders[0].history = [];
      data.orders[1].history = [];
      addEvent(data.orders[0], 'Pedido criado');
      addEvent(data.orders[0], 'Alocado ao veículo EFG4H56');
      addEvent(data.orders[0], 'Status alterado para: Em Trânsito');
      addEvent(data.orders[1], 'Pedido criado');
      addEvent(data.orders[1], 'Alocado ao veículo IJK7L89');
      save();
      renderAll();
      toast('Dados de exemplo restaurados.');
    }
  });
  $('#mobileMenu').addEventListener('click', () => $('#sidebar').classList.toggle('open'));
  $('#today').textContent = new Date().toLocaleDateString('pt-BR', { weekday: 'short', day: '2-digit', month: 'short', year: 'numeric' });

  if (!data.orders[0].history?.length) {
    addEvent(data.orders[0], 'Pedido criado');
    addEvent(data.orders[0], 'Alocado ao veículo EFG4H56');
    addEvent(data.orders[0], 'Status alterado para: Em Trânsito');
  }
  if (!data.orders[1].history?.length) {
    addEvent(data.orders[1], 'Pedido criado');
    addEvent(data.orders[1], 'Alocado ao veículo IJK7L89');
  }
  renderAll();
})();
