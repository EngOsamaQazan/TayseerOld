/**
 * Contracts V2 — Modern interactions
 * Filter drawer, chips, quick search, actions menu, copy ID
 */
(function ($) {
  'use strict';

  /* ========== FILTER PANEL / DRAWER ========== */
  var $filterWrap    = $('#ctFilterWrap'),
      $filterPanel   = $('#ctFilterPanel'),
      $backdrop      = $('#ctFilterBackdrop'),
      $toggleBtn     = $('#ctFilterToggle'),
      $drawerClose   = $('#ctDrawerClose');

  // Desktop: toggle collapse
  $(document).on('click', '.ct-filter-hdr', function () {
    if (window.innerWidth > 767) {
      $filterPanel.toggleClass('collapsed');
      localStorage.setItem('ct_filter_collapsed', $filterPanel.hasClass('collapsed') ? '1' : '0');
    }
  });

  // Restore collapse state on desktop
  if (window.innerWidth > 767 && localStorage.getItem('ct_filter_collapsed') === '1') {
    $filterPanel.addClass('collapsed');
  }

  // Mobile: open drawer
  $toggleBtn.on('click', function () {
    $filterWrap.addClass('open');
    $('body').css('overflow', 'hidden');
  });

  // Mobile: close drawer
  function closeDrawer() {
    $filterWrap.removeClass('open');
    $('body').css('overflow', '');
  }
  $backdrop.on('click', closeDrawer);
  $drawerClose.on('click', closeDrawer);
  $(document).on('keydown', function (e) {
    if (e.key === 'Escape') closeDrawer();
  });

  /* ========== FILTER CHIPS ========== */
  var chipLabels = {
    'ContractsSearch[id]':            'رقم العقد',
    'ContractsSearch[customer_name]': 'العميل',
    'ContractsSearch[status]':        'الحالة',
    'ContractsSearch[from_date]':     'من تاريخ',
    'ContractsSearch[to_date]':       'إلى تاريخ',
    'ContractsSearch[seller_id]':     'البائع',
    'ContractsSearch[followed_by]':   'المتابع',
    'ContractsSearch[phone_number]':  'الهاتف',
    'ContractsSearch[job_Type]':      'نوع الوظيفة'
  };
  var statusMap = {
    'active': 'نشط', 'pending': 'معلّق', 'legal_department': 'قانوني',
    'judiciary': 'قضاء', 'settlement': 'تسوية', 'finished': 'منتهي',
    'canceled': 'ملغي', 'refused': 'مرفوض'
  };

  function buildChips() {
    var $container = $('#ctChips');
    $container.empty();
    var params = new URLSearchParams(window.location.search);
    var hasChips = false;

    params.forEach(function (value, key) {
      if (!value || key === 'r' || key === 'page' || key === 'sort' || key === 'per-page') return;
      var label = chipLabels[key];
      if (!label) return;

      var displayVal = value;
      if (key === 'ContractsSearch[status]') displayVal = statusMap[value] || value;

      // Try to get select2 display text
      var $field = $('[name="' + key + '"]');
      if ($field.length && $field.is('select') && $field.find('option:selected').text()) {
        var selText = $field.find('option:selected').text().trim();
        if (selText && selText !== '' && !selText.startsWith('--')) displayVal = selText;
      }

      hasChips = true;
      var $chip = $('<span class="ct-chip">' +
        '<span class="ct-chip-label">' + label + ':</span> ' + displayVal +
        ' <button class="ct-chip-remove" data-param="' + key + '" title="إزالة" aria-label="إزالة فلتر ' + label + '">&times;</button>' +
        '</span>');
      $container.append($chip);
    });

    if (hasChips) {
      $container.append(
        '<span class="ct-chip ct-chip-clear"><button class="ct-chip-remove" data-param="__all" title="مسح الكل">مسح الكل &times;</button></span>'
      );
    }
  }

  $(document).on('click', '.ct-chip-remove', function () {
    var param = $(this).data('param');
    if (param === '__all') {
      // Go to index without params
      window.location.href = window.location.pathname;
      return;
    }
    var params = new URLSearchParams(window.location.search);
    params.delete(param);
    window.location.href = window.location.pathname + '?' + params.toString();
  });

  buildChips();

  /* ========== QUICK SEARCH ========== */
  var quickTimer = null;
  $('#ctQuickSearch').on('input', function () {
    var query = $(this).val().toLowerCase().trim();
    clearTimeout(quickTimer);
    quickTimer = setTimeout(function () {
      var $rows = $('.ct-table tbody tr');
      if (!query) {
        $rows.show();
        return;
      }
      $rows.each(function () {
        var text = $(this).text().toLowerCase();
        $(this).toggle(text.indexOf(query) > -1);
      });
    }, 200);
  });

  /* ========== ACTIONS MENU ========== */
  $(document).on('click', '.ct-act-trigger', function (e) {
    e.stopPropagation();
    var $wrap = $(this).closest('.ct-act-wrap');
    var wasOpen = $wrap.hasClass('open');

    // Close all menus
    $('.ct-act-wrap').removeClass('open');

    if (!wasOpen) {
      $wrap.addClass('open');

      // Adjust position if menu goes off screen
      var $menu = $wrap.find('.ct-act-menu');
      var menuRect = $menu[0].getBoundingClientRect();
      if (menuRect.bottom > window.innerHeight) {
        $menu.css({ top: 'auto', bottom: '100%', marginTop: 0, marginBottom: '4px' });
      }
    }
  });

  $(document).on('click', function () {
    $('.ct-act-wrap').removeClass('open');
  });

  $(document).on('click', '.ct-act-menu', function (e) {
    e.stopPropagation();
  });

  /* ========== COPY CONTRACT ID ========== */
  $(document).on('click', '.ct-td-id', function () {
    var id = $(this).text().trim();
    if (!id || id === '#') return;
    if (navigator.clipboard) {
      navigator.clipboard.writeText(id).then(function () {
        showCopyTip('تم نسخ رقم العقد: ' + id);
      });
    }
  });

  function showCopyTip(text) {
    var $tip = $('<div class="ct-copied-tip">' + text + '</div>');
    $('body').append($tip);
    setTimeout(function () { $tip.fadeOut(300, function () { $tip.remove(); }); }, 1500);
  }

  /* ========== FOLLOW-UP USER CHANGE ========== */
  $(document).on('change', '.ct-follow-select', function () {
    var cid = $(this).data('contract-id'),
        uid = $(this).val();
    if (cid && uid) {
      $.post('/contracts/contracts/change-followed-by', {
        contract_id: cid,
        user_id: uid,
        _csrf: yii.getCsrfToken()
      }).done(function () {
        showCopyTip('تم تغيير المتابع');
      });
    }
  });

  /* ========== FINISH / CANCEL MODALS ========== */
  $(document).on('click', '.yeas-finish', function (e) {
    e.preventDefault();
    $('#finishContractBtn').attr('href', $(this).data('url'));
    $('#finishContractModal').modal('show');
  });
  $(document).on('click', '.yeas-cancel', function (e) {
    e.preventDefault();
    $('#cancelContractBtn').attr('href', $(this).data('url'));
    $('#cancelContractModal').modal('show');
  });

  /* ========== KEYBOARD NAVIGATION ========== */
  $(document).on('keydown', '.ct-act-trigger', function (e) {
    if (e.key === 'Enter' || e.key === ' ') {
      e.preventDefault();
      $(this).trigger('click');
    }
  });

  /* ========== CSV EXPORT ========== */
  $(document).on('click', '#ctExportBtn', function () {
    var params = window.location.search;
    var exportUrl = window.location.pathname.replace(/\/index$/, '') + '/index' + params +
      (params ? '&' : '?') + 'export=csv';
    window.location.href = exportUrl;
  });

  /* ========== RESPONSIVE HANDLER ========== */
  var lastWidth = window.innerWidth;
  $(window).on('resize', function () {
    var w = window.innerWidth;
    if ((lastWidth > 767 && w <= 767) || (lastWidth <= 767 && w > 767)) {
      closeDrawer();
    }
    lastWidth = w;
  });

})(jQuery);
