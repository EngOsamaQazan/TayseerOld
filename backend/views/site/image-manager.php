<?php
/**
 * صفحة إدارة ومراجعة صور العملاء
 * ─────────────────────────────────
 * تعرض جميع صور ImageManager مع إمكانية:
 * - البحث والفلترة
 * - مراجعة الربط الحالي
 * - إعادة ربط الصور بالعملاء الصحيحين
 */

use yii\helpers\Url;

$this->title = 'إدارة صور العملاء';
$this->registerCssFile('@web/css/image-manager-admin.css');
?>

<div class="img-mgr-page">

    <!-- ═══ الشريط العلوي ═══ -->
    <div class="img-mgr-header">
        <div class="img-mgr-title-area">
            <h1><i class="fa fa-images"></i> إدارة صور العملاء</h1>
            <p class="img-mgr-subtitle">مراجعة وتصحيح ربط الصور بالعملاء — إجمالي: <span id="totalCount">...</span> صورة</p>
        </div>
        <div class="img-mgr-actions">
            <a href="<?= Url::to(['/site/system-settings']) ?>" class="btn-back">
                <i class="fa fa-arrow-right"></i> إعدادات النظام
            </a>
        </div>
    </div>

    <!-- ═══ بطاقات الإحصائيات ═══ -->
    <div class="img-stats-row" id="statsRow">
        <div class="img-stat-card stat-total">
            <div class="stat-icon"><i class="fa fa-image"></i></div>
            <div class="stat-info">
                <span class="stat-value" id="statTotal">—</span>
                <span class="stat-label">إجمالي الصور</span>
            </div>
        </div>
        <div class="img-stat-card stat-linked">
            <div class="stat-icon"><i class="fa fa-link"></i></div>
            <div class="stat-info">
                <span class="stat-value" id="statLinked">—</span>
                <span class="stat-label">مرتبطة بعملاء</span>
            </div>
        </div>
        <div class="img-stat-card stat-orphans">
            <div class="stat-icon"><i class="fa fa-chain-broken"></i></div>
            <div class="stat-info">
                <span class="stat-value" id="statOrphans">—</span>
                <span class="stat-label">صور يتيمة</span>
            </div>
        </div>
        <div class="img-stat-card stat-contracts">
            <div class="stat-icon"><i class="fa fa-file-text"></i></div>
            <div class="stat-info">
                <span class="stat-value" id="statContracts">—</span>
                <span class="stat-label">صور عقود</span>
            </div>
        </div>
        <div class="img-stat-card stat-smart">
            <div class="stat-icon"><i class="fa fa-magic"></i></div>
            <div class="stat-info">
                <span class="stat-value" id="statSmart">—</span>
                <span class="stat-label">النظام الذكي</span>
            </div>
        </div>
        <div class="img-stat-card stat-missing">
            <div class="stat-icon"><i class="fa fa-exclamation-triangle"></i></div>
            <div class="stat-info">
                <span class="stat-value" id="statMissing">—</span>
                <span class="stat-label">ملفات مفقودة (تقديري)</span>
            </div>
        </div>
    </div>

    <!-- ═══ شريط الفلترة ═══ -->
    <div class="img-filter-bar">
        <div class="filter-group">
            <label>عرض:</label>
            <div class="filter-tabs" id="filterTabs">
                <button class="filter-tab active" data-filter="all">الكل</button>
                <button class="filter-tab" data-filter="customers">مرتبطة بعملاء</button>
                <button class="filter-tab" data-filter="orphans">يتيمة <span class="orphan-badge" id="orphanBadge"></span></button>
                <button class="filter-tab" data-filter="contracts">عقود</button>
                <button class="filter-tab" data-filter="smart_media">النظام الذكي <span class="smart-badge" id="smartBadge"></span></button>
            </div>
        </div>
        <div class="filter-group">
            <label>بحث:</label>
            <div class="search-box">
                <i class="fa fa-search"></i>
                <input type="text" id="searchInput" placeholder="اسم العميل، رقم العميل، أو رقم الصورة..." />
            </div>
        </div>
        <div class="filter-group filter-dates">
            <label>من:</label>
            <input type="date" id="dateFrom" />
            <label>إلى:</label>
            <input type="date" id="dateTo" />
        </div>
        <button class="btn-export" onclick="exportImages()">
            <i class="fa fa-download"></i> تصدير ZIP
        </button>
    </div>

    <!-- ═══ شبكة الصور ═══ -->
    <div class="img-grid-container">
        <div class="img-grid" id="imageGrid">
            <div class="loading-placeholder">
                <i class="fa fa-spinner fa-spin"></i>
                <p>جاري تحميل الصور...</p>
            </div>
        </div>
    </div>

    <!-- ═══ ترقيم الصفحات ═══ -->
    <div class="img-pagination" id="pagination"></div>

</div>

<!-- ═══ نافذة إعادة الربط ═══ -->
<div class="modal-overlay" id="reassignModal" style="display:none;">
    <div class="modal-box">
        <div class="modal-header">
            <h3><i class="fa fa-exchange"></i> إعادة ربط الصورة</h3>
            <button class="modal-close" onclick="closeModal()">&times;</button>
        </div>
        <div class="modal-body">
            <div class="modal-image-preview">
                <img id="modalImage" src="" alt="معاينة" />
            </div>
            <div class="modal-info">
                <div class="info-row">
                    <span class="info-label">رقم الصورة:</span>
                    <span id="modalImageId" class="info-value"></span>
                </div>
                <div class="info-row">
                    <span class="info-label">اسم الملف:</span>
                    <span id="modalFileName" class="info-value"></span>
                </div>
                <div class="info-row">
                    <span class="info-label">الربط الحالي:</span>
                    <span id="modalCurrentLink" class="info-value"></span>
                </div>
                <div class="info-row">
                    <span class="info-label">تاريخ الرفع:</span>
                    <span id="modalUploadDate" class="info-value"></span>
                </div>
            </div>
            <hr />
            <div class="reassign-form">
                <!-- ═══ نوع الصورة (مطلوب - فقط للربط الفردي) ═══ -->
                <div id="singleDocTypeSection">
                    <label>نوع الصورة: <span style="color:#e74c3c">*</span></label>
                    <select id="docTypeSelect" class="reassign-select" onchange="validateReassignForm()">
                        <option value="">— اختر نوع الصورة —</option>
                        <option value="0">هوية وطنية</option>
                        <option value="1">جواز سفر</option>
                        <option value="2">رخصة قيادة</option>
                        <option value="3">شهادة ميلاد</option>
                        <option value="4">شهادة تعيين</option>
                        <option value="5">كتاب ضمان اجتماعي</option>
                        <option value="6">كشف راتب</option>
                        <option value="7">شهادة تعيين عسكري</option>
                        <option value="8">صورة شخصية</option>
                        <option value="9">أخرى</option>
                    </select>
                    <div id="docTypeError" class="field-error" style="display:none;">يرجى تحديد نوع الصورة قبل الربط</div>
                </div>

                <!-- ═══ بحث العميل ═══ -->
                <label style="margin-top:12px;">بحث عن العميل: <span style="color:#e74c3c">*</span></label>
                <div class="select2-wrapper" id="customerSelect2Wrapper">
                    <div class="s2-control" id="s2Control" onclick="openCustomerDropdown()">
                        <span class="s2-placeholder" id="s2Placeholder">ابحث بالاسم أو الرقم الوطني أو الهاتف...</span>
                        <span class="s2-selected" id="s2Selected" style="display:none;"></span>
                        <button type="button" class="s2-clear" id="s2Clear" style="display:none;" onclick="event.stopPropagation(); clearSelectedCustomer()">&times;</button>
                        <span class="s2-arrow"><i class="fa fa-caret-down"></i></span>
                    </div>
                    <div class="s2-dropdown" id="s2Dropdown" style="display:none;">
                        <div class="s2-search-box">
                            <input type="text" id="s2SearchInput" placeholder="ابحث..." oninput="liveSearchCustomer()" autocomplete="off" />
                        </div>
                        <div class="s2-results" id="s2Results">
                            <div class="s2-message">اكتب للبحث...</div>
                        </div>
                    </div>
                </div>
                <input type="hidden" id="newCustomerId" value="" />
                <div id="customerError" class="field-error" style="display:none;">يرجى اختيار العميل</div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn-cancel" onclick="closeModal()">إلغاء</button>
            <button class="btn-save" id="btnReassign" onclick="submitReassign()" disabled>
                <i class="fa fa-check"></i> تأكيد الربط
            </button>
        </div>
    </div>
</div>

<!-- ═══ نافذة عرض الصورة بحجم كامل ═══ -->
<div class="lightbox-overlay" id="lightbox" style="display:none;" onclick="closeLightbox()">
    <img id="lightboxImage" src="" alt="" />
</div>

<script>
// ─── متغيرات عامة ───
let currentPage = 1;
let currentFilter = 'all';
let searchTimeout = null;
let currentImageId = null;

const API_BASE = '<?= Url::to(['/site/']) ?>';

// ─── تحميل أولي ───
document.addEventListener('DOMContentLoaded', function() {
    loadStats();
    loadImages();
    
    // بحث مع تأخير
    document.getElementById('searchInput').addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => { currentPage = 1; loadImages(); }, 400);
    });
    
    // فلاتر التاريخ
    document.getElementById('dateFrom').addEventListener('change', () => { currentPage = 1; loadImages(); });
    document.getElementById('dateTo').addEventListener('change', () => { currentPage = 1; loadImages(); });
    
    // تبويبات الفلتر
    document.querySelectorAll('.filter-tab').forEach(tab => {
        tab.addEventListener('click', function() {
            document.querySelectorAll('.filter-tab').forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            currentFilter = this.dataset.filter;
            currentPage = 1;
            loadImages();
        });
    });
});

// ─── تحميل الإحصائيات ───
function loadStats() {
    fetch('<?= Url::to(['/site/image-manager-stats']) ?>')
        .then(r => r.json())
        .then(data => {
            var grandTotal = data.total + (data.smart_media_count || 0);
            document.getElementById('statTotal').textContent = grandTotal.toLocaleString('ar');
            document.getElementById('statLinked').textContent = data.linked.toLocaleString('ar');
            document.getElementById('statOrphans').textContent = data.orphans.toLocaleString('ar');
            document.getElementById('statContracts').textContent = data.contract_images.toLocaleString('ar');
            document.getElementById('statSmart').textContent = (data.smart_media_count || 0).toLocaleString('ar');
            document.getElementById('statMissing').textContent = '~' + data.estimated_missing.toLocaleString('ar');
            document.getElementById('orphanBadge').textContent = data.orphans;
            document.getElementById('smartBadge').textContent = data.smart_media_count || 0;
            document.getElementById('totalCount').textContent = grandTotal.toLocaleString('ar');
        })
        .catch(err => console.error('Stats error:', err));
}

// ─── تحميل الصور ───
function loadImages() {
    const grid = document.getElementById('imageGrid');
    grid.innerHTML = '<div class="loading-placeholder"><i class="fa fa-spinner fa-spin"></i><p>جاري التحميل...</p></div>';
    
    const params = new URLSearchParams({
        page: currentPage,
        per_page: 48,
        filter: currentFilter,
        search: document.getElementById('searchInput').value,
        date_from: document.getElementById('dateFrom').value,
        date_to: document.getElementById('dateTo').value,
    });
    
    fetch('<?= Url::to(['/site/image-manager-data']) ?>?' + params)
        .then(r => r.json())
        .then(data => {
            renderGrid(data.images);
            renderPagination(data);
        })
        .catch(err => {
            grid.innerHTML = '<div class="error-placeholder"><i class="fa fa-exclamation-circle"></i><p>حدث خطأ في تحميل البيانات</p></div>';
            console.error('Load error:', err);
        });
}

// ─── رسم الشبكة مع تجميع الدفعات ───
function renderGrid(images) {
    const grid = document.getElementById('imageGrid');
    
    if (images.length === 0) {
        grid.innerHTML = '<div class="empty-placeholder"><i class="fa fa-inbox"></i><p>لا توجد صور بالفلاتر المحددة</p></div>';
        return;
    }
    
    // تجميع الصور حسب الدفعة
    const batchMap = {};
    images.forEach(img => {
        const bid = img.batchId || ('solo_' + img.id);
        if (!batchMap[bid]) batchMap[bid] = [];
        batchMap[bid].push(img);
    });
    
    // ألوان الدفعات (تتكرر)
    const batchColors = ['#6B2D5B','#2980b9','#27ae60','#e67e22','#8e44ad','#16a085','#c0392b','#2c3e50','#d4ac0d','#1abc9c'];
    let colorIndex = 0;
    
    let html = '';
    
    Object.keys(batchMap).forEach(batchId => {
        const batch = batchMap[batchId];
        const isMulti = batch.length > 1;
        const color = batchColors[colorIndex % batchColors.length];
        
        if (isMulti) {
            // عرض مجموعة الدفعة
            const first = batch[0];
            const batchDate = first.created ? first.created.substring(0, 16) : '—';
            const batchLabel = first.customerName 
                ? `${first.customerName} (#${first.customerId})`
                : (first.isOrphan ? `يتيم — contractId: ${first.contractId}` : `${first.groupName}: ${first.contractId}`);
            
            html += `<div class="batch-group" style="--batch-color:${color}">
                <div class="batch-header">
                    <span class="batch-color-dot" style="background:${color}"></span>
                    <span class="batch-label"><i class="fa fa-layer-group"></i> دفعة: ${batch.length} صور</span>
                    <span class="batch-meta">${batchLabel}</span>
                    <span class="batch-time"><i class="fa fa-clock-o"></i> ${batchDate}</span>
                    <button class="btn-batch-reassign" onclick="openBatchReassign('${batchId}', ${JSON.stringify(batch.map(b=>b.id)).replace(/"/g, '&quot;')})" title="إعادة ربط الدفعة كاملة">
                        <i class="fa fa-exchange"></i> ربط الدفعة
                    </button>
                </div>
                <div class="batch-images">`;
        }
        
        batch.forEach(img => {
            const statusClass = img.isOrphan ? 'orphan' : (img.fileExists ? 'linked' : 'missing-file');
            const statusLabel = img.isOrphan ? 'يتيمة' : (img.fileExists ? '' : 'ملف مفقود');
            const selectedBadge = img.isSelected ? '<span class="selected-badge" title="الصورة المختارة للعميل"><i class="fa fa-star"></i></span>' : '';
            const batchDot = isMulti ? `<span class="card-batch-dot" style="background:${color}" title="دفعة واحدة"></span>` : '';
            const sourceIcon = img.source === 'smart_media' ? '<span class="source-badge smart" title="النظام الذكي"><i class="fa fa-magic"></i></span>' 
                             : img.source === 'photos' ? '<span class="source-badge photos" title="صور"><i class="fa fa-camera"></i></span>'
                             : '';
            
            html += `
            <div class="img-card ${statusClass}${isMulti ? ' in-batch' : ''}" data-id="${img.id}" data-batch="${img.batchId || ''}">
                <div class="img-card-thumb" onclick="openLightbox('${img.imageUrl}')">
                    ${img.fileExists 
                        ? `<img src="${img.imageUrl}" alt="${img.fileName}" loading="lazy" onerror="this.parentElement.innerHTML='<div class=\\'no-img\\'><i class=\\'fa fa-image\\'></i></div>'" />`
                        : `<div class="no-img"><i class="fa fa-ban"></i><small>ملف مفقود</small></div>`
                    }
                    ${selectedBadge}
                    ${batchDot}
                    ${sourceIcon}
                    ${statusLabel ? `<span class="status-tag ${statusClass}">${statusLabel}</span>` : ''}
                </div>
                <div class="img-card-info">
                    <div class="img-id">#${img.id}</div>
                    <div class="img-customer">
                        ${img.isOrphan 
                            ? `<span class="orphan-label"><i class="fa fa-chain-broken"></i> contractId: ${img.contractId}</span>`
                            : (img.customerName 
                                ? `<span class="customer-label"><i class="fa fa-user"></i> ${img.customerName} <small>(#${img.customerId})</small></span>`
                                : (img.groupName === 'contracts' 
                                    ? `<span class="contract-label"><i class="fa fa-file-text-o"></i> عقد #${img.contractId}</span>`
                                    : `<span class="other-label">${img.groupName}: ${img.contractId}</span>`
                                )
                            )
                        }
                    </div>
                    <div class="img-date"><i class="fa fa-calendar-o"></i> ${img.created ? img.created.substring(0,10) : '—'}</div>
                    <div class="img-doc-type">
                        <select class="doc-type-select" data-image-id="${img.id}" onchange="updateDocType(this)" title="نوع الصورة">
                            <option value="">— النوع —</option>
                            <option value="0" ${img.docType === '0' ? 'selected' : ''}>هوية وطنية</option>
                            <option value="1" ${img.docType === '1' ? 'selected' : ''}>جواز سفر</option>
                            <option value="2" ${img.docType === '2' ? 'selected' : ''}>رخصة قيادة</option>
                            <option value="3" ${img.docType === '3' ? 'selected' : ''}>شهادة ميلاد</option>
                            <option value="4" ${img.docType === '4' ? 'selected' : ''}>شهادة تعيين</option>
                            <option value="5" ${img.docType === '5' ? 'selected' : ''}>كتاب ضمان اجتماعي</option>
                            <option value="6" ${img.docType === '6' ? 'selected' : ''}>كشف راتب</option>
                            <option value="7" ${img.docType === '7' ? 'selected' : ''}>شهادة تعيين عسكري</option>
                            <option value="8" ${img.docType === '8' ? 'selected' : ''}>صورة شخصية</option>
                            <option value="9" ${img.docType === '9' ? 'selected' : ''}>أخرى</option>
                        </select>
                    </div>
                    <div class="img-card-actions">
                        <button class="btn-mini btn-reassign" onclick="openReassign(${JSON.stringify(img).replace(/"/g, '&quot;')})" title="إعادة ربط">
                            <i class="fa fa-exchange"></i>
                        </button>
                        <button class="btn-mini btn-view" onclick="openLightbox('${img.imageUrl}')" title="عرض">
                            <i class="fa fa-expand"></i>
                        </button>
                    </div>
                </div>
            </div>`;
        });
        
        if (isMulti) {
            html += `</div></div>`;
            colorIndex++;
        }
    });
    
    grid.innerHTML = html;
}

// ─── ترقيم الصفحات ───
function renderPagination(data) {
    const el = document.getElementById('pagination');
    if (data.pages <= 1) { el.innerHTML = ''; return; }
    
    let html = '<div class="pag-info">صفحة ' + data.page + ' من ' + data.pages + ' — إجمالي: ' + data.total.toLocaleString('ar') + '</div>';
    html += '<div class="pag-buttons">';
    
    if (data.page > 1) {
        html += `<button onclick="goToPage(${data.page - 1})"><i class="fa fa-chevron-right"></i></button>`;
    }
    
    // عرض أرقام الصفحات
    let start = Math.max(1, data.page - 3);
    let end = Math.min(data.pages, data.page + 3);
    
    if (start > 1) html += `<button onclick="goToPage(1)">1</button><span class="pag-dots">...</span>`;
    
    for (let i = start; i <= end; i++) {
        html += `<button class="${i === data.page ? 'active' : ''}" onclick="goToPage(${i})">${i}</button>`;
    }
    
    if (end < data.pages) html += `<span class="pag-dots">...</span><button onclick="goToPage(${data.pages})">${data.pages}</button>`;
    
    if (data.page < data.pages) {
        html += `<button onclick="goToPage(${data.page + 1})"><i class="fa fa-chevron-left"></i></button>`;
    }
    
    html += '</div>';
    el.innerHTML = html;
}

function goToPage(page) {
    currentPage = page;
    loadImages();
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

// ─── نوع الصورة: تحديث مباشر من البطاقة ───
function updateDocType(selectEl) {
    const imgId = selectEl.dataset.imageId;
    const docType = selectEl.value;
    if (!docType) return;

    const formData = new FormData();
    formData.append('image_id', imgId);
    formData.append('doc_type', docType);
    formData.append('<?= Yii::$app->request->csrfParam ?>', '<?= Yii::$app->request->csrfToken ?>');

    fetch('<?= Url::to(['/site/image-update-doc-type']) ?>', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                selectEl.style.borderColor = '#27ae60';
                setTimeout(() => selectEl.style.borderColor = '', 1500);
                if (docType === '8' && data.autoSelected) {
                    showNotification('تم تعيين الصورة الشخصية للعميل تلقائياً', 'success');
                }
            } else {
                showNotification(data.error || 'فشل التحديث', 'error');
                selectEl.value = '';
            }
        });
}

// ─── نافذة إعادة الربط ───
let isBatchMode = false;

function openReassign(img) {
    isBatchMode = false;
    currentImageId = img.id;
    document.getElementById('modalImage').src = img.imageUrl;
    document.getElementById('modalImageId').textContent = '#' + img.id;
    document.getElementById('modalFileName').textContent = img.fileName;
    document.getElementById('modalUploadDate').textContent = img.created || '—';
    document.getElementById('modalCurrentLink').innerHTML = img.customerName 
        ? `<span style="color:#27ae60">${img.customerName} (#${img.customerId})</span>`
        : `<span style="color:#e74c3c">يتيمة — contractId: ${img.contractId}</span>`;
    
    // فردي: إظهار اختيار النوع
    document.getElementById('singleDocTypeSection').style.display = '';
    document.getElementById('docTypeSelect').value = img.docType || '';
    document.getElementById('docTypeError').style.display = 'none';
    document.getElementById('customerError').style.display = 'none';
    
    // Reset customer select2
    resetCustomerSelect2();
    if (img.customerName && img.customerId) {
        setCustomerSelect2(img.customerId, img.customerName + ' (#' + img.customerId + ')');
    }
    
    validateReassignForm();
    document.getElementById('btnReassign').onclick = submitReassign;
    document.getElementById('reassignModal').style.display = 'flex';
}

function closeModal() {
    document.getElementById('reassignModal').style.display = 'none';
    closeCustomerDropdown();
    currentImageId = null;
    isBatchMode = false;
}

// ─── Select2-style بحث العملاء ───
let customerSearchTimeout = null;

function openCustomerDropdown() {
    const dd = document.getElementById('s2Dropdown');
    dd.style.display = 'block';
    document.getElementById('s2SearchInput').value = '';
    document.getElementById('s2SearchInput').focus();
    document.getElementById('s2Results').innerHTML = '<div class="s2-message">اكتب للبحث...</div>';
    // إغلاق عند النقر خارج
    setTimeout(() => document.addEventListener('click', outsideClickHandler), 10);
}

function closeCustomerDropdown() {
    document.getElementById('s2Dropdown').style.display = 'none';
    document.removeEventListener('click', outsideClickHandler);
}

function outsideClickHandler(e) {
    const wrapper = document.getElementById('customerSelect2Wrapper');
    if (!wrapper.contains(e.target)) closeCustomerDropdown();
}

function liveSearchCustomer() {
    const query = document.getElementById('s2SearchInput').value.trim();
    const resultsDiv = document.getElementById('s2Results');
    
    if (query.length < 1) { resultsDiv.innerHTML = '<div class="s2-message">اكتب للبحث...</div>'; return; }
    
    clearTimeout(customerSearchTimeout);
    customerSearchTimeout = setTimeout(() => {
        resultsDiv.innerHTML = '<div class="s2-message"><i class="fa fa-spinner fa-spin"></i> جاري البحث...</div>';
        
        fetch('<?= Url::to(['/customers/customers/search-customers']) ?>?mode=id&q=' + encodeURIComponent(query))
            .then(r => r.json())
            .then(data => {
                const results = data.results || [];
                if (results.length === 0) {
                    resultsDiv.innerHTML = '<div class="s2-message">لا توجد نتائج</div>';
                    return;
                }
                let html = '';
                results.forEach(c => {
                    let extra = '';
                    if (c.id_number) extra += ` <small class="s2-meta">· ${c.id_number}</small>`;
                    if (c.phone) extra += ` <small class="s2-meta phone">☎ ${c.phone}</small>`;
                    html += `<div class="s2-option" onclick="selectCustomer(${c.id}, '${c.text.replace(/'/g, "\\'")}')">
                        <b>${c.text}</b>${extra}
                        <span class="s2-opt-id">#${c.id}</span>
                    </div>`;
                });
                resultsDiv.innerHTML = html;
            })
            .catch(() => {
                resultsDiv.innerHTML = '<div class="s2-message" style="color:#e74c3c">خطأ في البحث</div>';
            });
    }, 250);
}

function selectCustomer(id, name) {
    document.getElementById('newCustomerId').value = id;
    document.getElementById('s2Placeholder').style.display = 'none';
    document.getElementById('s2Selected').style.display = '';
    document.getElementById('s2Selected').textContent = name + ' (#' + id + ')';
    document.getElementById('s2Clear').style.display = '';
    document.getElementById('customerError').style.display = 'none';
    closeCustomerDropdown();
    validateReassignForm();
}

function setCustomerSelect2(id, text) {
    document.getElementById('newCustomerId').value = id;
    document.getElementById('s2Placeholder').style.display = 'none';
    document.getElementById('s2Selected').style.display = '';
    document.getElementById('s2Selected').textContent = text;
    document.getElementById('s2Clear').style.display = '';
}

function resetCustomerSelect2() {
    document.getElementById('newCustomerId').value = '';
    document.getElementById('s2Placeholder').style.display = '';
    document.getElementById('s2Selected').style.display = 'none';
    document.getElementById('s2Selected').textContent = '';
    document.getElementById('s2Clear').style.display = 'none';
    closeCustomerDropdown();
}

function clearSelectedCustomer() {
    resetCustomerSelect2();
    validateReassignForm();
}

function validateReassignForm() {
    const customerId = document.getElementById('newCustomerId').value;
    const btn = document.getElementById('btnReassign');
    if (isBatchMode) {
        btn.disabled = !customerId;
    } else {
        const docType = document.getElementById('docTypeSelect').value;
        btn.disabled = !(docType && customerId);
    }
}

function submitReassign() {
    const newCid = document.getElementById('newCustomerId').value.trim();
    const docType = document.getElementById('docTypeSelect').value;
    
    let valid = true;
    if (!docType) { document.getElementById('docTypeError').style.display = 'block'; valid = false; }
    else { document.getElementById('docTypeError').style.display = 'none'; }
    if (!newCid) { document.getElementById('customerError').style.display = 'block'; valid = false; }
    else { document.getElementById('customerError').style.display = 'none'; }
    if (!valid || !currentImageId) return;
    
    const btn = document.getElementById('btnReassign');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> جاري الربط...';
    
    const formData = new FormData();
    formData.append('image_id', currentImageId);
    formData.append('customer_id', newCid);
    formData.append('doc_type', docType);
    formData.append('set_selected', docType === '8' ? '1' : '0');
    formData.append('<?= Yii::$app->request->csrfParam ?>', '<?= Yii::$app->request->csrfToken ?>');
    
    fetch('<?= Url::to(['/site/image-reassign']) ?>', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fa fa-check"></i> تأكيد الربط';
        if (data.success) {
            const card = document.querySelector(`.img-card[data-id="${currentImageId}"]`);
            if (card) {
                card.classList.remove('orphan'); card.classList.add('linked');
                const customerEl = card.querySelector('.img-customer');
                if (customerEl) customerEl.innerHTML = `<span class="customer-label"><i class="fa fa-user"></i> ${data.customerName} <small>(#${newCid})</small></span>`;
                const typeSelect = card.querySelector('.doc-type-select');
                if (typeSelect) typeSelect.value = docType;
            }
            closeModal(); loadStats();
            showNotification(data.message, 'success');
        } else { showNotification(data.error, 'error'); }
    })
    .catch(() => { btn.disabled = false; btn.innerHTML = '<i class="fa fa-check"></i> تأكيد الربط'; showNotification('خطأ في الاتصال', 'error'); });
}

// ─── إعادة ربط دفعة كاملة ───
let batchImageIds = [];

function openBatchReassign(batchId, imageIds) {
    // تحقق أن كل صورة محدد نوعها من البطاقات
    let missingTypes = [];
    imageIds.forEach(imgId => {
        const card = document.querySelector(`.img-card[data-id="${imgId}"]`);
        if (card) {
            const sel = card.querySelector('.doc-type-select');
            if (!sel || !sel.value) missingTypes.push(imgId);
        }
    });
    
    if (missingTypes.length > 0) {
        showNotification(`يرجى تحديد نوع كل صورة من البطاقات أولاً (${missingTypes.length} صورة بدون نوع)`, 'error');
        // تمييز البطاقات الناقصة
        missingTypes.forEach(imgId => {
            const card = document.querySelector(`.img-card[data-id="${imgId}"]`);
            if (card) {
                const sel = card.querySelector('.doc-type-select');
                if (sel) { sel.style.borderColor = '#e74c3c'; sel.style.boxShadow = '0 0 6px rgba(231,76,60,.4)'; }
            }
        });
        return;
    }
    
    isBatchMode = true;
    batchImageIds = imageIds;
    currentImageId = imageIds[0];
    const firstCard = document.querySelector(`.img-card[data-id="${imageIds[0]}"]`);
    const imgEl = firstCard ? firstCard.querySelector('img') : null;
    
    document.getElementById('modalImage').src = imgEl ? imgEl.src : '';
    document.getElementById('modalImageId').innerHTML = `دفعة: ${imageIds.length} صورة <small>(${imageIds.join(', ')})</small>`;
    document.getElementById('modalFileName').textContent = `سيتم ربط ${imageIds.length} صور بأنواعها المحددة`;
    document.getElementById('modalUploadDate').textContent = '—';
    document.getElementById('modalCurrentLink').innerHTML = '<span style="color:#e67e22">ربط جماعي لدفعة كاملة</span>';
    
    // إخفاء اختيار النوع — الأنواع محددة مسبقاً من البطاقات
    document.getElementById('singleDocTypeSection').style.display = 'none';
    document.getElementById('docTypeError').style.display = 'none';
    document.getElementById('customerError').style.display = 'none';
    
    resetCustomerSelect2();
    validateReassignForm();
    
    document.getElementById('btnReassign').onclick = submitBatchReassign;
    document.getElementById('reassignModal').style.display = 'flex';
}

function submitBatchReassign() {
    const newCid = document.getElementById('newCustomerId').value.trim();
    if (!newCid) { document.getElementById('customerError').style.display = 'block'; return; }
    
    const btn = document.getElementById('btnReassign');
    btn.disabled = true;
    btn.innerHTML = `<i class="fa fa-spinner fa-spin"></i> جاري ربط ${batchImageIds.length} صور...`;
    
    let completed = 0, errors = 0;
    let hasPersonalPhoto = false;
    
    batchImageIds.forEach((imgId, idx) => {
        // جلب النوع من dropdown البطاقة
        const card = document.querySelector(`.img-card[data-id="${imgId}"]`);
        const docType = card ? (card.querySelector('.doc-type-select')?.value || '9') : '9';
        if (docType === '8' && !hasPersonalPhoto) hasPersonalPhoto = true;
        
        const formData = new FormData();
        formData.append('image_id', imgId);
        formData.append('customer_id', newCid);
        formData.append('doc_type', docType);
        formData.append('set_selected', (docType === '8' && !hasPersonalPhoto) ? '1' : '0');
        formData.append('<?= Yii::$app->request->csrfParam ?>', '<?= Yii::$app->request->csrfToken ?>');
        
        fetch('<?= Url::to(['/site/image-reassign']) ?>', { method: 'POST', body: formData })
            .then(r => r.json())
            .then(data => {
                if (!data.success) errors++;
                completed++;
                if (completed === batchImageIds.length) {
                    btn.disabled = false; btn.innerHTML = '<i class="fa fa-check"></i> تأكيد الربط';
                    btn.onclick = submitReassign;
                    closeModal(); loadImages(); loadStats();
                    if (errors === 0) showNotification(`تم ربط ${batchImageIds.length} صور بالعميل #${newCid} بنجاح`, 'success');
                    else showNotification(`تم ربط ${completed - errors} من ${batchImageIds.length} — ${errors} أخطاء`, 'error');
                    batchImageIds = [];
                }
            });
    });
}

// ─── Lightbox ───
function openLightbox(url) {
    document.getElementById('lightboxImage').src = url;
    document.getElementById('lightbox').style.display = 'flex';
}
function closeLightbox() {
    document.getElementById('lightbox').style.display = 'none';
}

// ─── تصدير ZIP ───
function exportImages() {
    const key = prompt('أدخل مفتاح التصدير:', 'TayseerExport2026!@#');
    if (!key) return;
    
    let url = '/export-images.php?key=' + encodeURIComponent(key);
    
    const from = document.getElementById('dateFrom').value;
    const to = document.getElementById('dateTo').value;
    if (from) url += '&from=' + from;
    if (to) url += '&to=' + to;
    if (currentFilter === 'customers') url += '&group=coustmers';
    else if (currentFilter === 'contracts') url += '&group=contracts';
    
    // معاينة أولاً
    if (confirm('هل تريد معاينة الإحصائيات قبل التحميل؟')) {
        fetch(url + '&preview=1')
            .then(r => r.json())
            .then(data => {
                const msg = `إحصائيات التصدير:
• إجمالي الصور: ${data.total_images}
• موجودة على القرص: ${data.found_on_disk}
• مفقودة: ${data.missing_files}
• يتيمة: ${data.orphan_images}
• حجم الملف: ${data.zip_size_mb} MB

هل تريد التحميل الآن؟`;
                if (confirm(msg)) {
                    window.location.href = url;
                }
            })
            .catch(() => {
                if (confirm('تعذر المعاينة. هل تريد التحميل مباشرة؟')) {
                    window.location.href = url;
                }
            });
    } else {
        window.location.href = url;
    }
}

// ─── إشعار ───
function showNotification(msg, type) {
    const el = document.createElement('div');
    el.className = 'img-mgr-notification ' + type;
    el.innerHTML = `<i class="fa fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i> ${msg}`;
    document.body.appendChild(el);
    
    setTimeout(() => el.classList.add('show'), 10);
    setTimeout(() => {
        el.classList.remove('show');
        setTimeout(() => el.remove(), 300);
    }, 4000);
}
</script>
