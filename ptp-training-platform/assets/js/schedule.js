/**
 * PTP Schedule Calendar JavaScript
 */

(function() {
    'use strict';

    document.addEventListener('DOMContentLoaded', init);

    let calendar = null;
    let selectedTrainers = [];
    let focusedTrainer = null;

    function init() {
        const calendarEl = document.getElementById('calendar');
        if (!calendarEl) return;

        initCalendar(calendarEl);
        initTrainerFilters();
        initModal();
        initCustomerSearch();
        loadTrainerCounts();
    }

    // ===== Calendar =====
    function initCalendar(el) {
        document.querySelectorAll('#trainerList input[type="checkbox"]:checked').forEach(cb => {
            selectedTrainers.push(parseInt(cb.value));
        });

        calendar = new FullCalendar.Calendar(el, {
            initialView: 'timeGridWeek',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek'
            },
            slotMinTime: '06:00:00',
            slotMaxTime: '21:00:00',
            allDaySlot: false,
            nowIndicator: true,
            selectable: true,
            selectMirror: true,
            editable: true,
            eventDurationEditable: true,
            slotDuration: '00:30:00',
            height: 'auto',
            weekNumbers: true,
            navLinks: true,

            events: function(info, success, failure) {
                const trainers = focusedTrainer ? [focusedTrainer] : selectedTrainers;
                
                fetch(PTPSchedule.ajax + '?' + new URLSearchParams({
                    action: 'ptp_schedule_get_events',
                    nonce: PTPSchedule.nonce,
                    start: info.startStr,
                    end: info.endStr,
                    trainers: trainers.join(',')
                }))
                .then(r => r.json())
                .then(data => success(data))
                .catch(err => failure(err));
            },

            select: function(info) {
                openModal();
                document.getElementById('sessionDate').value = info.startStr.split('T')[0];
                const time = info.startStr.split('T')[1];
                document.getElementById('startTime').value = time ? time.slice(0, 5) : '09:00';
                
                if (focusedTrainer) {
                    document.getElementById('trainerId').value = focusedTrainer;
                }
            },

            eventClick: function(info) {
                openModal(info.event);
            },

            eventDrop: function(info) {
                quickUpdate(info.event);
            },

            eventResize: function(info) {
                quickUpdate(info.event);
            }
        });

        calendar.render();
    }

    // ===== Trainer Filters =====
    function initTrainerFilters() {
        document.getElementById('selectAll')?.addEventListener('click', function() {
            selectedTrainers = [];
            document.querySelectorAll('#trainerList .ptp-trainer-row').forEach(row => {
                const cb = row.querySelector('input[type="checkbox"]');
                if (cb) {
                    cb.checked = true;
                    selectedTrainers.push(parseInt(cb.value));
                    row.classList.add('selected');
                }
            });
            this.classList.add('active');
            document.getElementById('selectNone')?.classList.remove('active');
            calendar?.refetchEvents();
        });

        document.getElementById('selectNone')?.addEventListener('click', function() {
            selectedTrainers = [];
            document.querySelectorAll('#trainerList .ptp-trainer-row').forEach(row => {
                const cb = row.querySelector('input[type="checkbox"]');
                if (cb) {
                    cb.checked = false;
                    row.classList.remove('selected');
                }
            });
            this.classList.add('active');
            document.getElementById('selectAll')?.classList.remove('active');
            calendar?.refetchEvents();
        });

        document.getElementById('trainerList')?.addEventListener('click', function(e) {
            const row = e.target.closest('.ptp-trainer-row');
            if (!row) return;
            
            const cb = row.querySelector('input[type="checkbox"]');
            if (!cb) return;

            if (e.target.type !== 'checkbox') {
                cb.checked = !cb.checked;
            }

            const id = parseInt(cb.value);
            if (cb.checked) {
                if (!selectedTrainers.includes(id)) selectedTrainers.push(id);
                row.classList.add('selected');
            } else {
                selectedTrainers = selectedTrainers.filter(t => t !== id);
                row.classList.remove('selected');
            }

            updateFilterButtons();
            calendar?.refetchEvents();
        });

        document.getElementById('trainerList')?.addEventListener('dblclick', function(e) {
            const row = e.target.closest('.ptp-trainer-row');
            if (!row) return;
            enterFocusMode(row);
        });

        document.getElementById('exitFocus')?.addEventListener('click', exitFocusMode);

        document.querySelectorAll('.ptp-view-toggle button').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.ptp-view-toggle button').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                
                const list = document.getElementById('trainerList');
                if (this.dataset.view === 'grid') {
                    list.style.display = 'grid';
                    list.style.gridTemplateColumns = 'repeat(3, 1fr)';
                    list.style.gap = '8px';
                } else {
                    list.style.display = 'block';
                }
            });
        });

        document.getElementById('btnNewSession')?.addEventListener('click', function() {
            openModal();
            document.getElementById('sessionDate').value = new Date().toISOString().split('T')[0];
            document.getElementById('startTime').value = '09:00';
        });

        document.getElementById('btnToday')?.addEventListener('click', function() {
            calendar?.today();
            calendar?.changeView('timeGridDay');
        });
    }

    function updateFilterButtons() {
        const total = document.querySelectorAll('#trainerList .ptp-trainer-row').length;
        document.getElementById('selectAll')?.classList.toggle('active', selectedTrainers.length === total);
        document.getElementById('selectNone')?.classList.toggle('active', selectedTrainers.length === 0);
    }

    function enterFocusMode(row) {
        focusedTrainer = parseInt(row.dataset.id);
        document.getElementById('focusName').textContent = row.dataset.name;
        document.getElementById('focusBanner').classList.add('show');
        document.querySelectorAll('.ptp-trainer-row').forEach(r => r.classList.remove('focused'));
        row.classList.add('focused');
        calendar?.refetchEvents();
    }

    function exitFocusMode() {
        focusedTrainer = null;
        document.getElementById('focusBanner')?.classList.remove('show');
        document.querySelectorAll('.ptp-trainer-row').forEach(r => r.classList.remove('focused'));
        calendar?.refetchEvents();
    }

    // ===== Modal =====
    function initModal() {
        document.querySelectorAll('.ptp-modal-close, .ptp-modal-cancel').forEach(btn => {
            btn.addEventListener('click', closeModal);
        });

        document.getElementById('sessionModal')?.addEventListener('click', function(e) {
            if (e.target === this) closeModal();
        });

        document.addEventListener('keydown', e => {
            if (e.key === 'Escape') closeModal();
        });

        document.getElementById('sessionForm')?.addEventListener('submit', function(e) {
            e.preventDefault();
            saveSession();
        });

        document.getElementById('deleteSession')?.addEventListener('click', deleteSession);
    }

    function openModal(event = null) {
        const modal = document.getElementById('sessionModal');
        const form = document.getElementById('sessionForm');
        const sourceLabel = document.getElementById('sessionSource');
        
        form.reset();
        document.getElementById('sessionId').value = '';
        document.getElementById('sessionType').value = 'admin';
        document.getElementById('deleteSession').style.display = 'none';
        document.getElementById('modalTitle').textContent = 'New Session';
        document.getElementById('selectedCustomer').style.display = 'none';
        document.getElementById('customerId').value = '';
        document.getElementById('parentId').value = '';
        document.getElementById('playerId').innerHTML = '<option value="">-- Select or enter manually --</option>';
        if (sourceLabel) sourceLabel.textContent = '';

        if (event) {
            const p = event.extendedProps;
            const isBooking = p.source === 'booking';
            
            document.getElementById('modalTitle').textContent = 'Edit Session';
            document.getElementById('sessionId').value = event.id;
            document.getElementById('sessionType').value = p.source || 'admin';
            document.getElementById('deleteSession').style.display = 'inline-flex';
            
            if (sourceLabel) {
                sourceLabel.textContent = isBooking ? '📘 Parent Booking' : '📙 Admin Session';
                sourceLabel.style.background = isBooking ? '#dbeafe' : '#fef3c7';
                sourceLabel.style.color = isBooking ? '#1e40af' : '#92400e';
            }
            
            document.getElementById('trainerId').value = p.trainer_id || '';
            document.getElementById('sessionStatus').value = p.session_status || 'scheduled';
            document.getElementById('paymentStatus').value = p.payment_status || 'unpaid';
            document.getElementById('customerId').value = p.customer_id || '';
            document.getElementById('parentId').value = p.parent_id || '';
            document.getElementById('playerName').value = p.player_name || '';
            document.getElementById('playerAge').value = p.player_age || '';
            document.getElementById('sessionDate').value = event.startStr.split('T')[0];
            document.getElementById('startTime').value = event.startStr.split('T')[1]?.slice(0, 5) || '';
            document.getElementById('sessionTypeSelect').value = p.session_type || '1on1';
            document.getElementById('locationText').value = p.location_text || '';
            document.getElementById('price').value = p.price || '';
            document.getElementById('internalNotes').value = p.internal_notes || '';

            if (event.end) {
                const dur = (new Date(event.end) - new Date(event.start)) / 60000;
                document.getElementById('duration').value = dur;
            }

            if (p.customer_id && p.customer_name) {
                document.getElementById('customerName').textContent = p.customer_name;
                document.getElementById('selectedCustomer').style.display = 'flex';
                
                // Load players for this parent
                if (p.parent_id) {
                    loadPlayersForParent(p.parent_id, p.player_id);
                }
            }
        }

        modal.classList.add('open');
    }

    function closeModal() {
        document.getElementById('sessionModal')?.classList.remove('open');
    }

    function saveSession() {
        const form = document.getElementById('sessionForm');
        const formData = new FormData(form);
        
        const sessionId = document.getElementById('sessionId').value;
        formData.append('action', sessionId ? 'ptp_schedule_update_session' : 'ptp_schedule_create_session');
        formData.append('nonce', PTPSchedule.nonce);

        const btn = form.querySelector('button[type="submit"]');
        const origText = btn.textContent;
        btn.textContent = 'Saving...';
        btn.disabled = true;

        fetch(PTPSchedule.ajax, {
            method: 'POST',
            body: formData
        })
        .then(r => r.json())
        .then(result => {
            if (result.success) {
                closeModal();
                calendar?.refetchEvents();
                loadTrainerCounts();
                toast('Session saved!', 'success');
            } else {
                toast(result.data || 'Error saving session', 'error');
            }
        })
        .catch(() => toast('Error saving session', 'error'))
        .finally(() => {
            btn.textContent = origText;
            btn.disabled = false;
        });
    }

    function deleteSession() {
        if (!confirm('Delete this session? This cannot be undone.')) return;

        const sessionId = document.getElementById('sessionId').value;
        const formData = new FormData();
        formData.append('action', 'ptp_schedule_delete_session');
        formData.append('nonce', PTPSchedule.nonce);
        formData.append('id', sessionId);

        fetch(PTPSchedule.ajax, {
            method: 'POST',
            body: formData
        })
        .then(r => r.json())
        .then(result => {
            if (result.success) {
                closeModal();
                calendar?.refetchEvents();
                loadTrainerCounts();
                toast('Session deleted', 'success');
            } else {
                toast('Error deleting session', 'error');
            }
        });
    }

    function quickUpdate(event) {
        const formData = new FormData();
        formData.append('action', 'ptp_schedule_update_session');
        formData.append('nonce', PTPSchedule.nonce);
        formData.append('id', event.id);
        formData.append('session_date', event.startStr.split('T')[0]);
        formData.append('start_time', event.startStr.split('T')[1]?.slice(0, 5));

        const p = event.extendedProps;
        formData.append('trainer_id', p.trainer_id);
        formData.append('customer_id', p.customer_id || 0);
        formData.append('parent_id', p.parent_id || 0);
        formData.append('player_id', p.player_id || 0);
        formData.append('player_name', p.player_name || '');
        formData.append('player_age', p.player_age || '');
        formData.append('session_status', p.session_status || 'scheduled');
        formData.append('payment_status', p.payment_status || 'unpaid');
        formData.append('session_type', p.session_type || '1on1');
        formData.append('location_text', p.location_text || '');
        formData.append('price', p.price || 0);
        formData.append('internal_notes', p.internal_notes || '');

        if (event.end) {
            const dur = (new Date(event.end) - new Date(event.start)) / 60000;
            formData.append('duration_minutes', dur);
        }

        fetch(PTPSchedule.ajax, {
            method: 'POST',
            body: formData
        })
        .then(r => r.json())
        .then(result => {
            if (result.success) {
                toast('Session updated', 'success');
            } else {
                calendar?.refetchEvents();
                toast('Failed to update', 'error');
            }
        });
    }

    // ===== Customer Search =====
    function initCustomerSearch() {
        const input = document.getElementById('customerSearch');
        const results = document.getElementById('customerResults');
        let timeout;

        input?.addEventListener('input', function() {
            clearTimeout(timeout);
            const q = this.value.trim();

            if (q.length < 2) {
                results.classList.remove('show');
                return;
            }

            timeout = setTimeout(() => {
                fetch(PTPSchedule.ajax + '?' + new URLSearchParams({
                    action: 'ptp_schedule_search_customers',
                    nonce: PTPSchedule.nonce,
                    q: q
                }))
                .then(r => r.json())
                .then(result => {
                    if (result.success && result.data.length) {
                        results.innerHTML = result.data.map(u => `
                            <div class="ptp-search-result" data-id="${u.user_id}" data-parent-id="${u.parent_id}" data-name="${u.display_name}">
                                <strong>${u.display_name}</strong>
                                <small>${u.user_email}</small>
                            </div>
                        `).join('');
                        results.classList.add('show');
                    } else {
                        results.innerHTML = '<div class="ptp-search-result"><small>No results</small></div>';
                        results.classList.add('show');
                    }
                });
            }, 300);
        });

        input?.addEventListener('blur', () => {
            setTimeout(() => results.classList.remove('show'), 200);
        });

        results?.addEventListener('click', function(e) {
            const item = e.target.closest('.ptp-search-result');
            if (item && item.dataset.id) {
                document.getElementById('customerId').value = item.dataset.id;
                document.getElementById('parentId').value = item.dataset.parentId || '';
                document.getElementById('customerName').textContent = item.dataset.name;
                document.getElementById('selectedCustomer').style.display = 'flex';
                input.value = '';
                results.classList.remove('show');
                
                // Load players for this parent
                if (item.dataset.parentId) {
                    loadPlayersForParent(item.dataset.parentId);
                }
            }
        });

        document.getElementById('clearCustomer')?.addEventListener('click', function() {
            document.getElementById('customerId').value = '';
            document.getElementById('parentId').value = '';
            document.getElementById('selectedCustomer').style.display = 'none';
            document.getElementById('playerId').innerHTML = '<option value="">-- Select or enter manually --</option>';
        });
        
        // Player selection
        document.getElementById('playerId')?.addEventListener('change', function() {
            const opt = this.options[this.selectedIndex];
            if (opt && opt.value) {
                document.getElementById('playerName').value = opt.dataset.name || '';
                document.getElementById('playerAge').value = opt.dataset.age || '';
            }
        });
    }
    
    function loadPlayersForParent(parentId, selectedPlayerId = null) {
        if (!parentId) return;
        
        fetch(PTPSchedule.ajax + '?' + new URLSearchParams({
            action: 'ptp_schedule_search_players',
            nonce: PTPSchedule.nonce,
            parent_id: parentId
        }))
        .then(r => r.json())
        .then(data => {
            let html = '<option value="">-- Select or enter manually --</option>';
            if (data.success && data.data.length) {
                data.data.forEach(p => {
                    const selected = selectedPlayerId && p.id == selectedPlayerId ? ' selected' : '';
                    html += `<option value="${p.id}" data-name="${p.name}" data-age="${p.age || ''}"${selected}>${p.name}${p.age ? ' (Age ' + p.age + ')' : ''}</option>`;
                });
            }
            document.getElementById('playerId').innerHTML = html;
        });
    }

    // ===== Stats =====
    function loadTrainerCounts() {
        const now = new Date();
        const start = new Date(now);
        start.setDate(now.getDate() - now.getDay());
        const end = new Date(start);
        end.setDate(start.getDate() + 6);

        fetch(PTPSchedule.ajax + '?' + new URLSearchParams({
            action: 'ptp_schedule_get_trainer_stats',
            nonce: PTPSchedule.nonce,
            start: start.toISOString().split('T')[0],
            end: end.toISOString().split('T')[0]
        }))
        .then(r => r.json())
        .then(result => {
            if (result.success) {
                document.querySelectorAll('.ptp-trainer-row').forEach(row => {
                    const id = row.dataset.id;
                    const count = result.data[id] || 0;
                    const el = row.querySelector('.ptp-trainer-count');
                    if (el) el.textContent = count + ' session' + (count !== 1 ? 's' : '');
                });
            }
        });
    }

    // ===== Toast =====
    function toast(message, type = 'success') {
        document.querySelectorAll('.ptp-toast').forEach(t => t.remove());

        const el = document.createElement('div');
        el.className = 'ptp-toast ' + type;
        el.textContent = message;
        document.body.appendChild(el);

        setTimeout(() => {
            el.style.opacity = '0';
            el.style.transform = 'translateX(100%)';
            setTimeout(() => el.remove(), 300);
        }, 3000);
    }

})();
