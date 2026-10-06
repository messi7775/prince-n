/* فلترة موحّدة: البحث ظاهر دائمًا + باقي الفلاتر داخل Bottom Sheet */
(function () {
    var EXCLUDE_COUNT = ['sort', 'dir', 'period', 'page'];

    function enhance(form) {
        if (form.dataset.filterEnhanced) return;
        form.dataset.filterEnhanced = '1';

        var isBar = form.classList.contains('filter-bar');
        var grid = form.querySelector('.form-grid');
        if (!grid && !isBar) return;

        var qInput = form.querySelector('input[name="q"]');
        var qWrap = qInput ? (qInput.closest('.filter-field') || qInput.closest('label')) : null;
        var fieldEls = isBar
            ? Array.prototype.filter.call(form.querySelectorAll(':scope > .filter-field'), function (el) { return el !== qWrap; })
            : Array.prototype.filter.call(grid.children, function (el) { return el !== qWrap; });
        var actionsWrap = form.querySelector(':scope > .filter-actions');
        var directActions = Array.prototype.filter.call(form.children, function (el) {
            return el.tagName === 'BUTTON' || el.tagName === 'A';
        });
        var actionsChildren = actionsWrap ? Array.prototype.slice.call(actionsWrap.children) : directActions;

        // بحث فقط بدون فلاتر إضافية — تبقى كما هي
        if (!fieldEls.length) {
            form.classList.add('filter-search-only');
            return;
        }

        // الصف العلوي: البحث + زر فلترة
        var top = document.createElement('div');
        top.className = 'filter-top-row';
        if (qWrap) top.appendChild(qWrap);
        var toggle = document.createElement('button');
        toggle.type = 'button';
        toggle.className = 'btn filter-toggle';
        toggle.innerHTML = 'فلترة<span class="filter-count" hidden></span>';
        top.appendChild(toggle);
        form.insertBefore(top, form.firstChild);

        // النافذة (Bottom Sheet) — داخل النموذج لتبقى الحقول تُرسل معه
        var sheet = document.createElement('div');
        sheet.className = 'filter-sheet';
        sheet.hidden = true;

        var backdrop = document.createElement('div');
        backdrop.className = 'filter-sheet-backdrop';

        var panel = document.createElement('div');
        panel.className = 'filter-sheet-panel';
        panel.setAttribute('role', 'dialog');
        panel.setAttribute('aria-label', 'الفلاتر');

        var head = document.createElement('div');
        head.className = 'filter-sheet-head';
        var headTitle = document.createElement('strong');
        headTitle.textContent = 'الفلاتر';
        var closeBtn = document.createElement('button');
        closeBtn.type = 'button';
        closeBtn.className = 'filter-sheet-close';
        closeBtn.setAttribute('aria-label', 'إغلاق');
        closeBtn.textContent = '×';
        head.appendChild(headTitle);
        head.appendChild(closeBtn);

        var body = document.createElement('div');
        body.className = 'filter-sheet-body';
        var sheetGrid = document.createElement('div');
        sheetGrid.className = isBar ? 'filter-sheet-grid filter-sheet-flex' : 'filter-sheet-grid';
        fieldEls.forEach(function (el) { sheetGrid.appendChild(el); });
        body.appendChild(sheetGrid);

        var foot = document.createElement('div');
        foot.className = 'filter-sheet-foot';
        var applyBtn = document.createElement('button');
        applyBtn.type = 'submit';
        applyBtn.className = 'btn primary';
        applyBtn.textContent = 'تطبيق';
        var resetBtn = document.createElement('button');
        resetBtn.type = 'button';
        resetBtn.className = 'btn';
        resetBtn.textContent = 'افتراضي';
        foot.appendChild(applyBtn);
        foot.appendChild(resetBtn);

        panel.appendChild(head);
        panel.appendChild(body);
        panel.appendChild(foot);
        sheet.appendChild(backdrop);
        sheet.appendChild(panel);
        form.appendChild(sheet);

        // إزالة أزرار التطبيق/المسح القديمة والشبكة الفارغة
        if (actionsWrap) {
            actionsWrap.remove();
        } else {
            actionsChildren.forEach(function (el) { el.remove(); });
        }
        if (grid && !grid.children.length) grid.remove();

        function countActive() {
            var n = 0;
            fieldEls.forEach(function (el) {
                Array.prototype.forEach.call(el.querySelectorAll('input:not([type=hidden]),select'), function (f) {
                    if (EXCLUDE_COUNT.indexOf(f.name) > -1) return;
                    if (String(f.value).trim() !== '') n++;
                });
            });
            return n;
        }

        function updateCount() {
            var badge = toggle.querySelector('.filter-count');
            var n = countActive();
            if (n > 0) { badge.textContent = String(n); badge.hidden = false; }
            else { badge.hidden = true; }
        }

        function open() {
            sheet.hidden = false;
            requestAnimationFrame(function () { sheet.classList.add('open'); });
        }
        function close() {
            sheet.classList.remove('open');
            setTimeout(function () { sheet.hidden = true; }, 220);
        }

        toggle.addEventListener('click', open);
        closeBtn.addEventListener('click', close);
        backdrop.addEventListener('click', close);
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && !sheet.hidden) close();
        });

        // «افتراضي»: تصفير الحقول مع الحفاظ على الحقول المخفية ثم إعادة التحميل
        resetBtn.addEventListener('click', function () {
            fieldEls.forEach(function (el) {
                Array.prototype.forEach.call(el.querySelectorAll('input:not([type=hidden])'), function (f) { f.value = ''; });
                Array.prototype.forEach.call(el.querySelectorAll('select'), function (s) { s.selectedIndex = 0; });
            });
            form.submit();
        });

        updateCount();
    }

    document.querySelectorAll('form[method="get"].entity-form, form[method="get"].filter-bar').forEach(enhance);
})();
